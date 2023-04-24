<?php

namespace JagdishJP\BilldeskHmac\Messages;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JagdishJP\BilldeskHmac\Contracts\Message as Contract;
use JagdishJP\BilldeskHmac\Models\Transaction;
use JagdishJP\BilldeskHmac\Traits\Encryption;

class RefundOrder extends Message implements Contract
{
    use Encryption;

    public const STATUS_SUCCESS = 'refunded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_SUCCESS_CODE = '0699';

    public const STATUS_PENDING_CODE = '0002';

    /** Message Url */
    public $url;

    public function __construct()
    {
        parent::__construct();

        $this->url = App::environment('production')
            ? Config::get('billdesk.urls.production.create_refund')
            : Config::get('billdesk.urls.uat.create_refund');
    }

    /**
     * handle a message.
     *
     * @param array $options
     *
     * @return mixed
     */
    public function handle($options)
    {
        $data = Validator::make($options, [
            'merc_refund_ref_no'  => 'nullable',
            'orderid'             => 'required',
            'transaction_id'      => 'required',
            'transaction_date'    => 'required|date',
            'txn_amount'          => 'required',
            'refund_amount'       => 'required',
        ])->validate();

        $this->responseFormat       = $data['response_format']    ?? 'HTML';
        $this->refundReference      = $data['merc_refund_ref_no'] ?? uniqid('rfnd-');
        $this->reference            = $data['orderid'];
        $this->transaction_id       = $data['transaction_id'];
        $this->amount               = $this->numberFormat($data['txn_amount'], 2);
        $this->refundAmount         = $this->numberFormat($data['refund_amount'], 2);
        $this->transaction_date     = Carbon::parse($data['transaction_date'])->format(config('billdesk.date_format'));

        $this->payload              = $this->format();

        try {
            $this->saveTransaction();

            $response       = $this->api($this->url, $this->payload);
            $this->response = $response->getResponse();
            $this->saveTransaction();

            if ($response->getResponseStatus() != 200) {
                throw new Exception($this->response->message);
            }

            if ($this->response->refund_status == self::STATUS_SUCCESS_CODE) {
                $this->response->message    = 'Refund Request Submitted';

                return [
                    'status'                    => self::STATUS_SUCCESS,
                    'message'                   => $this->response->message,
                    'refundid'                  => $this->response->refundid,
                    'orderid'                   => $this->response->orderid,
                    'transaction_id'            => $this->response->transactionid,
                    'transaction_date'          => Carbon::parse($this->response->transaction_date),
                    'transaction_amount'        => $this->response->txn_amount,
                    'refund_amount'             => $this->response->refund_amount,
                    'refund_date'               => Carbon::parse($this->response->refund_date),
                    'merc_refund_ref_no'        => $this->response->merc_refund_ref_no,
                    'transaction_response'      => $this->response,
                ];
            }

            if ($this->transactionStatus == self::STATUS_PENDING_CODE) {
                $this->response->message    = 'Refund Request Initiated';

                return [
                    'status'                    => self::STATUS_PENDING,
                    'message'                   => $this->response->message,
                    'refundid'                  => $this->response->refundid,
                    'orderid'                   => $this->response->orderid,
                    'transaction_id'            => $this->response->transactionid,
                    'transaction_date'          => Carbon::parse($this->response->transaction_date),
                    'transaction_amount'        => $this->response->txn_amount,
                    'refund_amount'             => $this->response->refund_amount,
                    'refund_date'               => Carbon::parse($this->response->refund_date),
                    'merc_refund_ref_no'        => $this->response->merc_refund_ref_no,
                    'transaction_response'      => $this->response,
                ];
            }

            $this->response->message    = 'Refund Request Failed';

            return [
                'status'                    => self::STATUS_FAILED,
                'message'                   => $this->response->message,
                'refundid'                  => $this->response->refundid,
                'orderid'                   => $this->response->orderid,
                'transaction_id'            => $this->response->transactionid,
                'transaction_date'          => Carbon::parse($this->response->transaction_date),
                'transaction_amount'        => $this->response->txn_amount,
                'refund_amount'             => $this->response->refund_amount,
                'refund_date'               => Carbon::parse($this->response->refund_date),
                'merc_refund_ref_no'        => $this->response->merc_refund_ref_no,
                'transaction_response'      => $this->response,
            ];
        }
        catch (Exception $e) {
            Log::channel('daily')->debug('refund_order-payload', ['payload' => $this->payload]);
            Log::channel('daily')->debug('refund_order-handle', ['error' => $e->getMessage(), 'response' => $this->response ?? null]);

            return [
                'status'                    => self::STATUS_FAILED,
                'orderid'                   => $this->reference,
                'merc_refund_ref_no'        => $this->refundReference,
                'transaction_id'            => $this->transaction_id ?? null,
                'transaction_amount'        => $this->amount,
                'refund_amount'             => $this->refundAmount,
                'message'                   => 'Payment Request Failed',
                'transaction_response'      => $responseBody ?? null,
                'transaction_date'          => null,
                'refund_date'               => null,
                'merc_refund_ref_no'        => null,
            ];
        }
    }

    /**
     * Format data for checksum.
     *
     * @return string
     */
    public function format()
    {
        return $this->list()->toArray();
    }

    /**
     * returns collection of all fields.
     *
     * @return collection
     */
    public function list()
    {
        return collect([
            'merc_refund_ref_no' => $this->uatPrefix . $this->refundReference,
            'orderid'            => $this->reference,
            'mercid'             => $this->merchantId,
            'transactionid'      => $this->transaction_id,
            'transaction_date'   => $this->transaction_date,
            'txn_amount'         => $this->amount,
            'refund_amount'      => $this->refundAmount,
            'currency'           => $this->currency,
            'device'             => $this->device,
        ]);
    }

    /**
     * Save request to transaction.
     */
    public function saveTransaction()
    {
        $transaction                    = new Transaction();
        $transaction->unique_id         = $this->id;
        $transaction->request_type      = 'refund';
        $transaction->orderid           = $this->uatPrefix . $this->refundReference;
        $transaction->response_format   = $this->responseFormat;
        $transaction->request_payload   = $this->list()->toJson();
        $transaction->response_payload  = collect($this->response)?->toJson() ?? null;
        $transaction->save();
    }
}

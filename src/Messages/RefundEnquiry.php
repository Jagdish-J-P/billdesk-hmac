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

class RefundEnquiry extends Message implements Contract
{
    use Encryption;

    /** Message Url */
    public $url;

    public const STATUS_SUCCESS = 'refunded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_SUCCESS_CODE = '0699';

    public const STATUS_PENDING_CODE = '0002';

    public function __construct()
    {
        parent::__construct();

        $this->url = App::environment('production')
            ? Config::get('billdesk.urls.production.get_refund')
            : Config::get('billdesk.urls.uat.get_refund');
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
            'refund_reference_id'   => 'required_if:refund_transaction_id,null',
            'refund_transaction_id' => 'required_if:refund_reference_id,null',
        ])->validate();

        $this->responseFormat           = $data['response_format'] ?? 'HTML';
        $this->refundReference          = $data['refund_reference_id'];
        $this->refund_transaction_id    = $data['refund_transaction_id'];
        
        $this->payload              = $this->format();
        
        try {
            $this->saveTransaction();

            $response                = $this->api($this->url, $this->payload);
            $this->response          = $response->getResponse();
            $this->transactionStatus = $this->response->refund_status;

            $this->saveTransaction();

            if ($response->getResponseStatus() != 200) {
                throw new Exception($this->response->message);
            }

            if ($this->transactionStatus == self::STATUS_SUCCESS_CODE) {
                $this->response->message    = 'Refund Request Submitted';

                return [
                    'status'                    => self::STATUS_SUCCESS,
                    'message'                   => $this->response->message,
                    'refund_transaction_id'     => $this->response->refundid,
                    'reference_id'              => $this->response->orderid,
                    'transaction_id'            => $this->response->transactionid,
                    'transaction_date'          => Carbon::parse($this->response->transaction_date),
                    'transaction_amount'        => $this->response->txn_amount,
                    'refund_amount'             => $this->response->refund_amount,
                    'refund_date'               => Carbon::parse($this->response->refund_date),
                    'refund_reference_id'       => $this->response->merc_refund_ref_no,
                    'transaction_response'      => $this->response,
                ];
            }

            if ($this->transactionStatus == self::STATUS_PENDING_CODE) {
                $this->response->message    = 'Refund Request Initiated';
                
                return [
                    'status'                    => self::STATUS_PENDING,
                    'message'                   => $this->response->message,
                    'refund_transaction_id'     => $this->response->refundid,
                    'reference_id'              => $this->response->orderid,
                    'transaction_id'            => $this->response->transactionid,
                    'transaction_date'          => Carbon::parse($this->response->transaction_date),
                    'transaction_amount'        => $this->response->txn_amount,
                    'refund_amount'             => $this->response->refund_amount,
                    'refund_date'               => Carbon::parse($this->response->refund_date),
                    'refund_reference_id'       => $this->response->merc_refund_ref_no,
                    'transaction_response'      => $this->response,
                ];
            }

            $this->response->message    = 'Refund Request Failed';

            return [
                'status'                    => self::STATUS_FAILED,
                'message'                   => $this->response->message,
                'refund_transaction_id'     => $this->response->refundid,
                'reference_id'              => $this->response->orderid,
                'transaction_id'            => $this->response->transactionid,
                'transaction_date'          => Carbon::parse($this->response->transaction_date),
                'transaction_amount'        => $this->response->txn_amount,
                'refund_amount'             => $this->response->refund_amount,
                'refund_date'               => Carbon::parse($this->response->refund_date),
                'refund_reference_id'       => $this->response->merc_refund_ref_no,
                'transaction_response'      => $this->response,
            ];
        } catch (Exception $e) {

            Log::channel('daily')->debug('refund_order_status-payload', ['payload' => $this->payload]);
            Log::channel('daily')->debug('refund_order_status-handle',  ['error' => $e->getMessage(), 'response' => $this->response ?? null]);

            return [
                'status'                    => self::STATUS_FAILED,
                'reference_id'              => $this->reference,
                'refund_reference_id'       => $this->refundReference,
                'transaction_id'            => $this->transaction_id ?? null,
                'transaction_amount'        => $this->amount,
                'refund_amount'             => $this->refundAmount,
                'message'                   => 'Payment Request Failed',
                'transaction_response'      => $responseBody ?? null,
                'transaction_date'          => null,
                'refund_date'               => null,
                'refund_reference_id'       => null,
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
            'mercid'             => $this->merchantId,
            'refundid'           => $this->refund_transaction_id,
            'merc_refund_ref_no' => $this->refundReference,
        ]);
    }

    /**
     * Save request to transaction.
     */
    public function saveTransaction()
    {
        $transaction                        = Transaction::where('type', 'refund')->where('reference_id', $this->refundReference)->first();
        $transaction->transaction_status    = $this->transactionStatus ?? 'initiated';
        $transaction->request_type          = 'refund';
        $transaction->response_format       = $this->responseFormat;
        $transaction->response_payload      = collect($this->response)?->toJson() ?? null;
        $transaction->save();
    }
}

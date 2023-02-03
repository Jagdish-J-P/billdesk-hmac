<?php

namespace JagdishJP\BilldeskHmac\Messages;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JagdishJP\BilldeskHmac\Contracts\Message as Contract;
use JagdishJP\BilldeskHmac\Models\Transaction;
use JagdishJP\BilldeskHmac\Traits\Encryption;
use stdClass;

class TransactionCreate extends Message implements Contract
{
    use Encryption;

    public const STATUS_SUCCESS = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_SUCCESS_CODE = 'success';

    public const STATUS_PENDING_CODE = '0002';

    /** Message Url */
    public $url;

    public function __construct()
    {
        parent::__construct();

        $this->url = App::environment('production')
            ? Config::get('billdesk.urls.production.create_transaction')
            : Config::get('billdesk.urls.uat.create_transaction');
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
            'orderid'            => 'required',
            'amount'             => 'required',
            'mandateid'          => 'required',
            'subscription_refid' => 'required',
            'invoice_id'         => 'required',
            'debit_request_no'   => 'required',
            'additional_info'    => 'nullable',
            'itemcode'           => 'nullable',
            'customer'           => 'nullable',
        ])->validate();

        $this->mandate                     = new stdClass();
        $this->invoice                     = new stdClass();
        $this->reference                   = $data['orderid'];
        $this->amount                      = $data['amount'];
        $this->mandate->id                 = $data['mandateid'];
        $this->mandate->subscription_refid = $data['subscription_refid'];
        $this->invoice->id                 = $data['invoice_id'];
        $this->invoice->debit_request_no   = $data['debit_request_no'];
        $this->additionalInfo              = $data['additional_info'] ?? null;
        $this->item_code                   = $data['itemcode']        ?? $this->item_code;
        $this->customer                    = $data['customer']        ?? null;

        $response = $this->api($this->url, $this->format());

        $this->response = $response->getResponse();

        if ($response->getResponseStatus() != 200) {
            Log::channel('daily')->debug('billdesk-invoice-create-response', ['response' => $this->response]);

            throw new Exception($this->response->message);
        }

        $this->transaction_id     = $this->response->invoice_id              ?? null;
        $this->transactionStatus  = $this->response->verification_error_type ?? null;
        $this->saveTransaction();

        if ($this->transactionStatus == self::STATUS_SUCCESS_CODE) {
            return [
                'status'                => self::STATUS_SUCCESS,
                'message'               => $this->response->verification_error_desc,
                'transaction_response'  => $this->response,
                'invoice_id'            => $this->transaction_id   ?? null,
                'invoice_status'        => $this->response->status ?? null,
            ];
        }

        return [
            'status'                => self::STATUS_FAILED,
            'message'               => $this->response->verification_error_desc,
            'transaction_response'  => $this->response,
            'invoice_id'            => $this->transaction_id ?? null,
            'invoice_status'        => self::STATUS_FAILED,
        ];
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
            'currency'           => $this->currency,
            'orderid'            => $this->reference,
            'amount'             => $this->amount,
            'mandateid'          => $this->mandate->id,
            'subscription_refid' => $this->mandate->subscription_refid,
            'invoice_id'         => $this->invoice->id,
            'debit_request_no'   => $this->invoice->debit_request_no,
            'additionalInfo'     => $this->additionalInfo,
            'itemcode'           => $this->item_code,
            'customer'           => $this->customer,
            'txn_process_type'   => 'si',
        ]);
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
     * Save response to transaction.
     *
     * @return string initiated from
     */
    public function saveTransaction()
    {
        $transaction = Transaction::where('request_type', 'invoice_transaction')->where(['orderid' => $this->invoice->id])->firstOrNew();

        $transaction->request_type       = 'invoice_transaction';
        $transaction->orderid            = $this->invoice->id;
        $transaction->unique_id          = $this->id;
        $transaction->transaction_id     = $this->transaction_id;
        $transaction->transaction_status = $this->transactionStatus;
        $transaction->request_payload    = $this->list()->toJson();
        $transaction->response_payload   = collect($this->response)->toJson();
        $transaction->save();

        return $transaction->response_format;
    }
}

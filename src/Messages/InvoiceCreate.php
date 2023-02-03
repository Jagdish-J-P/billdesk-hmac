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

class InvoiceCreate extends Message implements Contract
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
            ? Config::get('billdesk.urls.production.create_invoice')
            : Config::get('billdesk.urls.uat.create_invoice');
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
            'subscription_refid'     => 'required',
            'customer_refid'         => 'required',
            'additional_info'        => 'nullable',
            'invoice_number'         => 'required',
            'invoice_display_number' => 'required',
            'invoice_date'           => 'required',
            'duedate'                => 'required',
            'debit_date'             => 'required',
            'amount'                 => 'required',
            'subscriptionid'         => 'nullable',
            'debit_request_no'       => 'nullable',
            'early_payment_duedate'  => 'nullable',
            'early_payment_discount' => 'nullable',
            'early_payment_amount'   => 'nullable',
            'late_payment_charges'   => 'nullable',
            'late_payment_amount'    => 'nullable',
            'net_amount'             => 'required',
            'mandateid'              => 'required',
            'description'            => 'required',
        ])->validate();

        $this->invoice = $data;

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
        $this->invoice['mercid']    = $this->merchantId;
        $this->invoice['currency']  = $this->currency;

        return collect($this->invoice);
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
        $transaction = Transaction::where('request_type', 'invoice')->where(['orderid' => $this->invoice['invoice_number']])->firstOrNew();

        $transaction->request_type       = 'invoice';
        $transaction->orderid            = $this->invoice['invoice_number'];
        $transaction->unique_id          = $this->id;
        $transaction->transaction_id     = $this->transaction_id;
        $transaction->transaction_status = $this->transactionStatus;
        $transaction->request_payload    = $this->list()->toJson();
        $transaction->response_payload   = collect($this->response)->toJson();
        $transaction->save();

        return $transaction->response_format;
    }
}

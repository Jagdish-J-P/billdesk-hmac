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

class InvoiceGet extends Message implements Contract
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
            ? Config::get('billdesk.urls.production.get_invoice')
            : Config::get('billdesk.urls.uat.get_invoice');
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
            'invoice_number'    => 'required',
        ])->validate();

        $this->reference = $data['invoice_number'];
        $response = $this->api($this->url, $this->format());

        $this->response = $response->getResponse();

        if ($response->getResponseStatus() != 200) {
            Log::channel('daily')->debug('billdesk-invoice-get-response', ['response' => $this->response]);

            throw new Exception($this->response->message);
        }

        $this->transaction_id     = $this->response->invoice_id ?? null;
        $this->transactionStatus  = $this->response->verification_error_type ?? null;
        $this->saveTransaction();

        if ($this->transactionStatus == self::STATUS_SUCCESS_CODE) {

            return [
                'status'                => self::STATUS_SUCCESS,
                'message'               => $this->response->verification_error_desc,
                'transaction_response'  => $this->response,
                'invoice_id'            => $this->transaction_id ?? null,
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
            'mercid'         => $this->merchantId,
            'invoice_number' => $this->reference,
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
        $transaction = Transaction::where('request_type', 'invoice')->where(['reference_id' => $this->reference])->firstOrNew();

        $transaction->request_type       = 'invoice';
        $transaction->unique_id          = $this->id;
        $transaction->reference_id       = $this->reference;
        $transaction->transaction_id     = $this->transaction_id;
        $transaction->transaction_status = $this->transactionStatus;
        $transaction->request_payload    = $this->list()->toJson();
        $transaction->response_payload   = collect($this->response)->toJson();
        $transaction->save();

        return $transaction->response_format;
    }
}

<?php

namespace JagdishJP\BilldeskHmac\Messages;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JagdishJP\BilldeskHmac\Constant\Response;
use JagdishJP\BilldeskHmac\Contracts\Message as Contract;
use JagdishJP\BilldeskHmac\Models\Transaction;
use JagdishJP\BilldeskHmac\Traits\Encryption;

class TransactionEnquiry extends Message implements Contract
{
    use Encryption;

    public const STATUS_SUCCESS = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_SUCCESS_CODE = '0300';

    public const STATUS_PENDING_CODE = '0002';

    /** Message Url */
    public $url;

    public function __construct()
    {
        parent::__construct();

        $this->url = App::environment('production')
            ? Config::get('billdesk.urls.production.get_transaction')
            : Config::get('billdesk.urls.uat.get_transaction');
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
            'reference_id'    => 'required',
        ])->validate();

        $this->reference = $data['reference_id'];

        $response = $this->api($this->url, $this->format());

        $this->response = $response->getResponse();

        if ($response->getResponseStatus() != 200) {
            Log::channel('daily')->debug('billdesk-status-response', ['response' => $this->response]);

            throw new Exception($this->response->message);
        }

        $this->transaction_id     = $this->response->transactionid;
        $this->transactionStatus  = $this->response->auth_status;
        $this->transaction_date   = Carbon::parse($this->response->transaction_date);

        $this->saveTransaction();

        if ($this->transactionStatus == self::STATUS_SUCCESS_CODE) {

            return [
                'status'            => self::STATUS_SUCCESS,
                'message'           => 'Payment Transaction Success',
                'status_response'   => $this->response,
                'reference_id'      => $this->reference,
                'transaction_id'    => $this->transaction_id,
                'transaction_date'  => $this->transaction_date,
            ];
        }

        if ($this->transactionStatus == self::STATUS_PENDING_CODE) {
            return [
                'status'                => self::STATUS_PENDING,
                'message'               => 'Payment Transaction Pending',
                'status_response'       => $this->response,
                'reference_id'          => $this->reference,
                'transaction_id'        => $this->transaction_id,
                'transaction_date'      => $this->transaction_date,
            ];
        }

        return [
            'status'                => self::STATUS_FAILED,
            'message'               => @Response::STATUS[$this->transactionStatus] ?? $this->response->transaction_error_desc ?? 'Payment Request Failed',
            'status_response'       => $this->response,
            'reference_id'          => $this->reference,
            'transaction_id'        => $this->transaction_id,
            'transaction_date'      => $this->transaction_date,
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
            'mercid'      => $this->merchantId,
            'orderid'     => $this->reference,
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
        $transaction = Transaction::where(['reference_id' => $this->reference])->first();

        $transaction->transaction_id     = $this->transaction_id;
        $transaction->transaction_status = $this->transactionStatus;
        $transaction->response_payload   = collect($this->response)->toJson();
        $transaction->save();

        return $transaction->response_format;
    }
}

<?php

namespace JagdishJP\BilldeskHmac\Messages;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use JagdishJP\BilldeskHmac\Contracts\Message as Contract;
use JagdishJP\BilldeskHmac\Models\Transaction;
use JagdishJP\BilldeskHmac\Traits\Encryption;

class TransactionEnquiry extends Message implements Contract
{
    use Encryption;

    public const REQUEST_TYPE = '0122';

    public const RESPONSE_TYPE = '0130';

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

        return $this->api($this->url, $this->format())->getResponse();
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
     * Format data for checksum.
     *
     * @return string
     */
    public function responseFormat()
    {
        return $this->responseList()->except('CheckSum')->join('|');
    }

    /**
     * returns collection of all fields.
     *
     * @return collection
     */
    public function responseList()
    {
        $responseValues = explode('|', $this->response);

        return collect(array_combine($this->queryResponseKeys, $responseValues));
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
        $transaction->response_payload   = $this->responseList()->toJson();
        $transaction->save();

        return $transaction->response_format;
    }
}

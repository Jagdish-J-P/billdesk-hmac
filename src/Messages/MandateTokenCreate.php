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

class MandateTokenCreate extends Message implements Contract
{
    use Encryption;

    public const STATUS_SUCCESS = 'succeeded';

    public const STATUS_FAILED = 'failed';

    /** Message Url */
    public $url;
    protected $customer_reference;
    protected $subscription_reference;

    public function __construct()
    {
        parent::__construct();

        $this->url = App::environment('production')
            ? Config::get('billdesk.urls.production.create_mandate_token')
            : Config::get('billdesk.urls.uat.create_mandate_token');
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
            'customer_reference_id'      => 'nullable',
            'subscription_reference_id'  => 'nullable',
        ])->validate();

        $this->customer_reference       = $data['customer_reference_id'] ?? null;
        $this->subscription_reference   = $data['subscription_reference_id'] ?? null;
        
        $response = $this->api($this->url, $this->format());

        $this->response = $response->getResponse();

        $this->saveTransaction();

        if ($response->getResponseStatus() != 200) {
            Log::channel('daily')->debug('mandate-token-create-response', ['response' => $this->response]);

            throw new Exception($this->response->message);
        }

        if ($this->response->status == 'initiated') {

            return [
                'status'          => self::STATUS_SUCCESS,
                'message'         => 'Mandate token retrived',
                'response'        => $this->response,
                'mandateTokenId'  => $this->response->mandate_tokenid,
                'authToken'       => $this->response->links[1]->headers->authorization,
                'response_url'    => $this->ResponseUrl,
                'merchant_logo'   => url(config('billdesk.merchant_logo')),
            ];
        }

        return [
            'status'    => self::STATUS_FAILED,
            'message'   => 'Failed to retrieve mandate list',
            'response'  => $this->response,
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
            'customer_refid'     => $this->customer_reference,
            'subscription_refid' => $this->subscription_reference,
            'ru'                 => $this->ResponseUrl,
            'device'             => $this->device,
            'currency'           => $this->currency,
            'action'             => 'delete',
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
     * Save request to transaction.
     */
    public function saveTransaction()
    {
        $transaction = Transaction::where('request_type', 'mandate_token')->where(['unique_id' => $this->id])->firstOrNew();
        
        $transaction->request_type    = 'mandate_token';
        $transaction->unique_id       = $this->id;
        $transaction->reference_id    = $this->subscription_reference;
        $transaction->response_format = $this->responseFormat ?? 'HTML';
        $transaction->request_payload = $this->list()->toJson();
        if(isset($this->response)) {
            $transaction->request_payload = collect($this->response)->toJson();
        }
        $transaction->save();
    }
}

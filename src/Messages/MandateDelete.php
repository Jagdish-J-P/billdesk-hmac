<?php

namespace JagdishJP\BilldeskHmac\Messages;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JagdishJP\BilldeskHmac\Contracts\Message as Contract;
use JagdishJP\BilldeskHmac\Traits\Encryption;

class MandateDelete extends Message implements Contract
{
    use Encryption;

    public const STATUS_SUCCESS = 'succeeded';

    public const STATUS_FAILED = 'failed';

    /** Message Url */
    public $url;
    protected $subscription_reference;

    public function __construct()
    {
        parent::__construct();

        $this->url = App::environment('production')
            ? Config::get('billdesk.urls.production.update_mandate')
            : Config::get('billdesk.urls.uat.update_mandate');
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

        $this->reference                = $data['customer_reference_id'] ?? null;
        $this->subscription_reference   = $data['subscription_reference_id'] ?? null;

        $response = $this->api($this->url, $this->format());

        $this->response = $response->getResponse();

        if ($response->getResponseStatus() != 200) {
            Log::channel('daily')->debug('mandate-delete-response', ['response' => $this->response]);

            throw new Exception($this->response->message);
        }

        if (isset($this->response->objectid)) {

            return [
                'status'    => self::STATUS_SUCCESS,
                'message'   => 'Mandate retrived',
                'mandates'  => $this->response->mandates,
            ];
        }

        return [
            'status'    => self::STATUS_FAILED,
            'message'   => 'Failed to retrieve mandate list',
            'mandates'  => $this->response,
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
            'customer_refid'     => $this->reference,
            'subscription_refid' => $this->subscription_reference,
            'from_date'          => $this->from_date,
            'to_date'           => $this->to_date,
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
}

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

class MandateModify extends Message implements Contract
{
    use Encryption;

    public const STATUS_SUCCESS = 'succeeded';

    public const STATUS_FAILED = 'failed';

    /** Message Url */
    public $url;
    protected $mandateId;

    public function __construct()
    {
        parent::__construct();

        $this->url = App::environment('production')
            ? Config::get('billdesk.urls.production.update_mandate_token')
            : Config::get('billdesk.urls.uat.update_mandate_token');
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
            'mandateid'      => 'nullable',
        ])->validate();

        $this->mandateId                = $data['mandateid'] ?? null;

        $response = $this->api($this->url, $this->format());

        $this->response = $response->getResponse();

        if ($response->getResponseStatus() != 200) {
            Log::channel('daily')->debug('mandate-delete-response', ['response' => $this->response]);

            throw new Exception($this->response->message);
        }

        if ($this->response->status == 'initiated') {

            return [
                'status'          => self::STATUS_SUCCESS,
                'message'         => 'Mandate token retrived',
                'response'        => $this->response,
                'mandateTokenId'  => $this->response->mandate_tokenid,
                'authToken'       => $this->response->links[1]->headers->authorization,
                'response_url'    => $this->MandateResponseUrl,
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
            'mandateid'          => $this->mandateId,
            'ru'                 => $this->MandateResponseUrl,
            'device'             => $this->device,
            'currency'           => $this->currency,
            'action'             => 'modify',
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

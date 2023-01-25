<?php

namespace JagdishJP\BilldeskHmac\Messages;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JagdishJP\BilldeskHmac\Contracts\Message as Contract;
use JagdishJP\BilldeskHmac\Models\Transaction;
use JagdishJP\BilldeskHmac\Traits\Encryption;

class CreateOrder extends Message implements Contract
{
    use Encryption;

    /** Message Url */
    public $url;

    public function __construct()
    {
        parent::__construct();

        $this->url = App::environment('production')
            ? Config::get('billdesk.urls.production.create_order')
            : Config::get('billdesk.urls.uat.create_order');
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
            'reference_id'       => 'nullable',
            'response_format'    => 'nullable',
            'additional_info'    => 'nullable',
            'invoice'            => 'nullable',
            'mandate'            => 'nullable',
            'customer'           => 'nullable|array',
            'order_date'         => 'nullable',
            'debit_day'          => 'nullable',
            'settlement_lob'     => 'nullable',
            'init_channel'       => 'nullable',
            'subscription_refid' => 'nullable',
            'reference_id'       => 'nullable',
            'mandate_required'   => 'required',
            'amount'             => 'required',
        ])->validate();

        $data['additional_info']['additional_info10'] = $this->id;

        $data['mandate']['mercid']              = $this->merchantId;
        $data['mandate']['currency']            = $this->currency;
        $data['mandate']['subscription_refid']  = $data['subscription_refid'] ?? null;
        $data['mandate']['debit_day']           = $data['debit_day'] ?? config('billdesk.debit_day');
        $this->device['init_channel']           = $data['init_channel'] ?? config('billdesk.init_channel');

        $this->responseFormat       = $data['response_format'] ?? 'HTML';
        $this->reference            = $data['reference_id'] ?? $this->generate_uuid();
        $this->amount               = $data['amount'];
        $this->order_date           = $data['order_date'] ?? now()->format(config('billdesk.date_format'));
        $this->additionalInfo       = $data['additional_info'] ?? null;
        $this->mandate              = $data['mandate_required'] == 'Y' ? ($data['mandate'] ?? null) : null;
        $this->invoice              = $data['invoice'] ?? null;
        $this->customer             = $data['customer'] ?? null;
        $this->settlement_lob       = $data['settlement_lob'] ?? null;
        $this->recurrence_rule      = $data['recurrence_rule'] ?? config('billdesk.recurrence_rule');
        $this->debit_day            = $data['debit_day'] ?? config('billdesk.debit_day');
        $this->mandate_required     = $data['mandate_required'] ?? 'N';
        
        $this->payload              = $this->format();

        $this->saveTransaction();

        try{
            $response = $this->api($this->url, $this->payload)->getResponse();
        }
        catch(Exception $e) {
            
            Log::channel('daily')->debug('create_order-payload', ['payload' => $this->payload]);
            Log::channel('daily')->debug('create_order-handle', ['error' => $e->getMessage()]);
            throw $e;
        }

        $ext = last(explode('.', $merchant_logo = public_path(config('billdesk.merchant_logo'))));
        $logo = base64_encode(file_get_contents($merchant_logo));

        return [
            'create_order_response' => $response,
            'bdOrderId'             => $response->bdorderid,
            'authToken'             => $this->getHeaders($response)->headers->authorization,
            'url'                   => $this->getHeaders($response, 'GET')->href,
            'response_url'          => $this->ResponseUrl,
            'merchant_logo'         => "data:image/$ext;base64:$logo",
        ];
    }
    
    /**
     * Format data for checksum.
     *
     * @return object
     */
    private function getHeaders($response, $method = 'POST')
    {
        return collect($response->links)->filter(fn($arr) => $arr->method == $method)->first();
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
            'orderid'          => $this->uatPrefix . ($this->reference ?? uniqid()),
            'mercid'           => $this->merchantId,
            'order_date'       => $this->order_date,
            'amount'           => $this->amount,
            'currency'         => $this->currency,
            'ru'               => $this->ResponseUrl,
            'additional_info'  => $this->additionalInfo,
            'itemcode'         => $this->item_code,
            'debit_day'        => $this->debit_day,
            'mandate_required' => $this->mandate_required,
            'mandate'          => $this->mandate,
            'customer'         => $this->customer,
            'device'           => $this->device,
            //'invoice'          => $this->invoice,
        ]);
    }

    /**
     * Save request to transaction.
     */
    public function saveTransaction()
    {
        $transaction                  = new Transaction();
        $transaction->unique_id       = $this->id;
        $transaction->reference_id    = $this->reference;
        $transaction->response_format = $this->responseFormat;
        $transaction->request_payload = $this->list()->toJson();
        $transaction->save();
    }
}

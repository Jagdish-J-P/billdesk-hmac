<?php

namespace JagdishJP\BilldeskHmac\Messages;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
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
        //todo: create order api
        $data = Validator::make($options, [
            'reference_id'    => 'nullable',
            'response_format' => 'nullable',
            'additional_info' => 'nullable',
            'invoice'         => 'nullable',
            'mandate'         => 'nullable',
            'order_date'      => 'nullable',
            'remark'          => 'nullable',
            'debit_day'       => 'nullable',
            'settlement_lob'  => 'nullable',
            'amount'          => 'required',
        ])->validate();


        $this->responseFormat       = $data['response_format'] ?? 'HTML';
        $this->reference            = $data['reference_id'] ?? $this->generate_uuid();
        $this->amount               = $data['amount'];
        $this->order_date           = $data['order_date'] ?? now()->format(config('billdesk.date_format'));
        $this->additionalInfo       = $data['additional_info'] ?? null;
        $this->mandate              = $data['mandate'] ?? null;
        $this->invoice              = $data['invoice'] ?? null;
        $this->device               = $data['device'] ?? null;
        $this->customer             = $data['customer'] ?? null;
        $this->settlement_lob       = $data['settlement_lob'] ?? null;
        $this->recurrence_rule      = $data['recurrence_rule'] ?? config('billdesk.recurrence_rule');
        $this->debit_day            = $data['debit_day'] ?? config('billdesk.debit_day');
        $this->mandate_required     = $data['mandate_required'] ?? 'N';
        $this->payload               = $this->format();

        $this->saveTransaction();

        try{
            $response = $this->api($this->url, $this->payload)->getResponse();
        }
        catch(Exception $e) {}

        $ext = last(explode('.', $merchant_logo = public_path(config('billdesk.merchant_logo'))));
        $logo = base64_encode(file_get_contents($merchant_logo));

        return [
            'bdOrderId'     => $response->bdorderid ?? uniqid(),
            'authToken'     => $response->headers->authorization ?? 'OToken DEDC1071B77800A146B6E8D2530E0429E76520C151B40CC3325D8B6D9242CBA3A6BFA643E7E5596FBEBAE0F46A1FB1BCD099EBC1F59DCD82F390B6BC45FCE036F37F7F589BD687A691E1378F1FF432331C62E7E641E857C8F8A405A4BFE2F01B1EB8F3C69817D45F5DDE9DEE346ACABA1B7208DECA9E43CCE7AB3761553E23D9CB36A870C1819C15C7C4B1CFE2802DFD05F651AA537AB81787.4145535F55415431',
            'response_url'  => $this->ResponseUrl,
            'merchant_logo' => "data:image/$ext;base64:$logo",
        ];
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
            'invoice'          => $this->invoice,
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

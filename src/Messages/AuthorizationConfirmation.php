<?php

namespace JagdishJP\BilldeskHmac\Messages;

use Exception;
use Illuminate\Support\Facades\Log;
use JagdishJP\BilldeskHmac\Constant\Response;
use JagdishJP\BilldeskHmac\Contracts\Message as Contract;
use JagdishJP\BilldeskHmac\Models\Transaction;
use JagdishJP\BilldeskHmac\Traits\Encryption;

class AuthorizationConfirmation extends Message implements Contract
{
    use Encryption;

    public const STATUS_SUCCESS = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS_CODE = '0300';

    public const STATUS_PENDING_CODE = '0002';

    /**
     * handle a message.
     *
     * @param array $options
     *
     * @return mixed
     */
    public function handle($options)
    {
        $this->response          = @$options['transaction_response'];
        $this->responseValues    = $this->verifyAndDecrypt($this->response);

        try {

            Log::channel('daily')->debug('billdesk-response', $this->responseValues);

            $this->id                   = $this->responseValues['additional_info']['additional_info10'];
            $this->reference            = $this->responseValues['orderid'];
            $this->transaction_id       = $this->responseValues['transactionid'];
            $this->transactionTimestamp = $this->responseValues['transaction_date'];
            $this->objectid             = $this->responseValues['objectid'];
            $this->transactionStatus    = $this->responseValues['auth_status'];

            $this->responseFormat = $this->saveTransaction();

            if ($this->transactionStatus == self::STATUS_SUCCESS_CODE) {
                return [
                    'status'          => self::STATUS_SUCCESS,
                    'message'         => 'Payment is successfull',
                    'transaction_id'  => $this->transaction_id,
                    'reference_id'    => $this->reference,
                    'response_format' => $this->responseFormat,
                ];
            }

            if ($this->transactionStatus == self::STATUS_PENDING_CODE) {
                return [
                    'status'          => self::STATUS_PENDING,
                    'message'         => 'Payment Transaction Pending',
                    'transaction_id'  => $this->transaction_id,
                    'reference_id'    => $this->reference,
                    'response_format' => $this->responseFormat,
                ];
            }

            return [
                'status'          => self::STATUS_FAILED,
                'message'         => @Response::STATUS[$this->transactionStatus] ?? 'Payment Request Failed',
                'transaction_id'  => $this->transaction_id,
                'reference_id'    => $this->reference,
                'response_format' => $this->responseFormat,
            ];
        } catch (Exception $e) {
            return [
                'status'          => self::STATUS_FAILED,
                'message'         => $e->getMessage(),
                'transaction_id'  => $this->transaction_id,
                'reference_id'    => $this->reference,
                'response_format' => $this->responseFormat,
            ];
        }
    }

    /**
     * Format data for checksum.
     *
     * @return string
     */
    public function format()
    {
        return $this->list()->join('|');
    }

    /**
     * returns collection of all fields.
     *
     * @return collection
     */
    public function list()
    {
        return collect($this->responseValues);
    }

    /**
     * Save response to transaction.
     *
     * @return string initiated from
     */
    public function saveTransaction()
    {
        $transaction = Transaction::where(['unique_id' => $this->id])->firstOrNew();

        $transaction->reference_id = $this->reference;
        $transaction->request_payload ??= '';
        $transaction->response_format ??= '';
        $transaction->unique_id          = $this->id;
        $transaction->transaction_id     = $this->transaction_id;
        $transaction->transaction_status = $this->transactionStatus;
        $transaction->response_payload   = $this->list()->toJson();
        $transaction->save();

        return $transaction->response_format;
    }
}

<?php

namespace JagdishJP\BilldeskHmac\Messages;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use JagdishJP\BilldeskHmac\Constant\Response;
use JagdishJP\BilldeskHmac\Contracts\Message as Contract;
use JagdishJP\BilldeskHmac\Models\Transaction;
use JagdishJP\BilldeskHmac\Traits\Encryption;

class MandateModifyResponse extends Message implements Contract
{
    use Encryption;

    public const STATUS_SUCCESS = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS_CODE = 'success';

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
        try {

            $this->response          = @$options['mandate_response'] ?? @$options['encrypted_response'];
            $this->responseValues    = $this->verifyAndDecrypt($this->response);
            Log::channel('daily')->debug('billdesk-response', ['response' => $this->responseValues]);
            
            if (isset($this->responseValues->verification_error_type) && $this->responseValues->verification_error_type == self::STATUS_SUCCESS_CODE) {

                $this->id                   = @$options['id'];
                $this->reference            = $this->responseValues->mandateid;
                $this->transaction_id       = $this->responseValues->transactionid;
                $this->transaction_date     = Carbon::parse($this->responseValues->createdon);
                $this->objectid             = $this->responseValues->objectid;
                $this->transactionStatus    = $this->responseValues->verification_error_type;
                $this->mandate              = $this->responseValues ?? null;

                //$this->responseFormat = $this->saveTransaction();
            }
            else {
                $this->transactionStatus = @$options['status'] ?? '000';
                $this->errorMessage = @$options['message'] ?? '000';
            } 
            

            if ($this->transactionStatus == self::STATUS_SUCCESS_CODE) {
                return [
                    'status'                => self::STATUS_SUCCESS,
                    'message'               => $this->responseValues->verification_error_desc,
                    'transaction_id'        => $this->transaction_id,
                    'transaction_date'      => $this->transaction_date,
                    'reference_id'          => $this->reference,
                    'mandate'               => $this->mandate ?? null,
                    'response_format'       => $this->responseFormat,
                    'transaction_response'  => $this->list()->toJson(),
                ];
            }

            if ($this->transactionStatus == self::STATUS_PENDING_CODE) {
                return [
                    'status'                => self::STATUS_PENDING,
                    'message'               => 'Payment Transaction Pending',
                    'transaction_id'        => $this->transaction_id,
                    'transaction_date'      => $this->transaction_date,
                    'reference_id'          => $this->reference,
                    'response_format'       => $this->responseFormat,
                    'mandate'               => $this->mandate ?? null,
                    'transaction_response'  => $this->list()->toJson(),
                ];
            }

            return [
                'status'                => self::STATUS_FAILED,
                'message'               => @Response::STATUS[$this->transactionStatus] ?? $this->errorMessage ?? 'Payment Request Failed',
                'transaction_id'        => $this->transaction_id,
                'transaction_date'      => $this->transaction_date,
                'reference_id'          => $this->reference,
                'response_format'       => $this->responseFormat,
                'mandate'               => $this->mandate ?? null,
                'transaction_response'  => $this->list()->toJson(),
            ];
        } catch (Exception $e) {
            return [
                'status'                => self::STATUS_FAILED,
                'message'               => $e->getMessage(),
                'transaction_id'        => $this->transaction_id,
                'transaction_date'      => $this->transaction_date,
                'reference_id'          => $this->reference,
                'response_format'       => $this->responseFormat,
                'mandate'               => $this->mandate ?? null,
                'transaction_response'  => null,
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
        $transaction = Transaction::where('request_type', 'transaction')->where(['unique_id' => $this->id])->firstOrNew();

        $transaction->request_type       = 'transaction';
        $transaction->reference_id       = $this->reference;
        $transaction->request_payload    ??= '';
        $transaction->response_format    ??= '';
        $transaction->unique_id          = $this->id;
        $transaction->transaction_id     = $this->transaction_id;
        $transaction->transaction_status = $this->transactionStatus;
        $transaction->response_payload   = $this->list()->toJson();
        $transaction->save();

        return $transaction->response_format;
    }
}

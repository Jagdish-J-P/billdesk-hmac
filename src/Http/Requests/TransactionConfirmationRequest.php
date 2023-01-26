<?php

namespace JagdishJP\BilldeskHmac\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use JagdishJP\BilldeskHmac\Messages\TransactionConfirmation;

class TransactionConfirmationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Presist the data to the users table.
     */
    public function handle()
    {
        $data = $this->all();

        return (new TransactionConfirmation())->handle($data);
    }
}
<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'orderId' => ['required'],
            // 'transactionId' => ['required'],
            // 'paymentMethod' => ['required'],
            // 'amount' => ['required','integer'],
            // 'createdBy' => ['required'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'order_id' => $this->orderId,
            // 'transaction_id' => $this->transactionId,
            // 'payment_method' => $this->paymentMethod,
            // 'created_by' => $this->createdBy,
        ]);
    }
}

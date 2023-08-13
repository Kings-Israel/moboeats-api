<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'cartId' => ['required','integer'],
            'restaurantId' => ['required','integer'],
            'delivery' => ['required','boolean'],
            'deliveryFee' => ['required_if:delivery,=,true'],
            'deliveryAddress' => ['required_if:delivery,=,true'],
            // 'deliveryStatus' => ['required_if:delivery,true'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'cart_id' => $this->cartId,
            'restaurant_id' => $this->restaurantId,
            'delivery_fee' => $this->deliveryFee,
            'delivery_address' => $this->deliveryAddress,
            // 'delivery_status' => $this->deliveryStatus,
        ]);
    }
}

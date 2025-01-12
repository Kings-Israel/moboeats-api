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
            'cartId' => ['nullable','integer'],
            'restaurantId' => ['required','integer'],
            'delivery' => ['required_without:orphanage_id','boolean'],
            'delivery_address' => ['required_if:delivery,true'],
            'delivery_location_lat' => ['required_if:delivery,true'],
            'delivery_location_lng' => ['required_if:delivery,true'],
            'booking_time' => ['required_if:delivery,false', 'after:'.now(), 'date_format:Y-m-d H:i'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'cart_id' => $this->cartId,
            'restaurant_id' => $this->restaurantId,
        ]);
    }

    public function messages(): array
    {
        return [
            'delivery_address.required_without' => 'The delivery address is required if order is a delivery',
            'delivery_location_lat.required_if' => 'The latitude is required if order is a delivery',
            'delivery_location_lng.required_if' => 'The longitude is required if order is a delivery',
            'booking_time.required_if' => 'Select dining time for non-delivered orders',
        ];
    }
}

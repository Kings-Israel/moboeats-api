<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
        // $user = $this->user();
        // return $user != null && $user->tokenCan('create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'name_short' => ['required'],
            'email' => ['required', 'email'],
            'about' => ['required'],
            'about_short' => ['required'],
            'phone_no' => ['nullable'],
            'address' => ['nullable'],
            'state' => ['nullable'],
            'postal_code' => ['nullable'],
            // 'place_id' => ['required_without_all:latitude,lognitude'],
            // 'latitude' => ['required_without:place_id'],
            // 'longitude' => ['required_without:place_id'],
            'url' => ['nullable'],
            'logo' => ['required'],
            'map_location' => ['nullable'],
            'sitting_capacity' => ['sometimes', 'integer'],
            // 'paypal_email' => ['required', 'email'],
        ];
    }

    public function messages()
    {
        return [
            'place_id.required_without_all' => 'Enter your location to proceed',
            'latitude.required_without' => 'Enter your current location',
            'longitude.required_without' => 'Enter your current location'
        ];
    }
}

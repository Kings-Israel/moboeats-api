<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantRequest extends FormRequest
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
            'name' => ['required'],
            'name_short' => ['required'],
            'email' => ['required', 'email'],
            'about' => ['required'],
            'about_short' => ['required'],
            'phone_no' => ['required'],
            'address' => ['required'],
            'state' => ['required'],
            'postal_code' => ['required'],
            'map_location' => ['nullable'],
            'url' => ['nullable'],
            'logo' => ['nullable'],
            'sitting_capacity' => ['required', 'integer'],
            'latitude' => ['required', 'string', 'not_in:null'],
            'longitude' => ['required', 'string', 'not_in:null'],
            'paypal_email' => ['required', 'email']
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'Please select restaurant location',
            'longitude.required' => 'Please select restaurant location',
            'sitting_capacity.required' => 'Enter Sitting Capacity. (0 if doesn\'t apply)',
            'paypal_email.required' => 'Please enter your paypal or venmo email address',
            'paypal_email.email' => 'Please enter a valid paypal or venmo email address',
        ];
    }
}

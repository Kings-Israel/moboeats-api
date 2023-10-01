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
            // 'uuid' => ['required'],
            'name' => ['required'],
            'nameShort' => ['nullable'],
            'email' => ['required', 'email'],
            // 'userId' => ['required'],
            'about' => ['nullable'],
            'aboutShort' => ['nullable'],
            'phoneNo' => ['nullable'],
            'address' => ['nullable'],
            'city' => ['nullable'],
            'state' => ['nullable'],
            'postalCode' => ['nullable'],
            'mapLocation' => ['nullable'],
            'latitude' => ['nullable'],
            'longitude' => ['nullable'],
            'url' => ['nullable'],
            'logo' => ['nullable'],
        ];
        
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'postal_code' => $this->postalCode,
            'map_location' => $this->mapLocation,
            'name_short' => $this->nameShort,
            'about_short' => $this->aboutShort,
            'phone_no' => $this->phoneNo,
            // 'user_id' => $this->userId,
        ]);
    }
}

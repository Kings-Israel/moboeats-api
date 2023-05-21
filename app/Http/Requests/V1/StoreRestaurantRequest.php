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
            'nameShort' => ['required'],
            'email' => ['required', 'email'],
            'about' => ['required'],
            'aboutShort' => ['required'],
            'phoneNo' => ['required'],
            'address' => ['required'],
            'city' => ['required'],
            'state' => ['required'],
            'postalCode' => ['required'],
            'mapLocation' => ['required'],
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
        ]);
    }
}

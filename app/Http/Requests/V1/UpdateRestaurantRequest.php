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
        $method = $this->method();
        if ($method == 'PUT') {
            return [
                'uuid' => ['required', 'uuid'],
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
                'mapLocation' => ['nullable'],
                'url' => ['nullable'],
                'logo' => ['nullable'],
            ];
        } else {
            return [
                'uuid' => ['required', 'uuid'],
                'name' => ['sometimes', 'required'],
                'nameShort' => ['sometimes', 'required'],
                'email' => ['sometimes', 'required', 'email'],
                'about' => ['sometimes', 'required'],
                'aboutShort' => ['sometimes', 'required'],
                'phoneNo' => ['sometimes', 'required'],
                'address' => ['sometimes', 'required'],
                'city' => ['sometimes', 'required'],
                'state' => ['sometimes', 'required'],
                'postalCode' => ['sometimes', 'required'],
                'mapLocation' => ['sometimes', 'nullable'],
                'url' => ['sometimes', 'nullable'],
                'logo' => ['sometimes', 'nullable'],
            ];
        }
    }
    protected function prepareForValidation()
    { 
        if ($this->postalCode) {
            $this->merge([
                'postal_code' => $this->postalCode,
            ]);
        }
        if ($this->mapLocation) {
            $this->merge([
                'map_location' => $this->mapLocation,
            ]);
        }
        if ($this->nameShort) {
            $this->merge([
                'name_short' => $this->nameShort,
            ]);
        }
        if ($this->aboutShort) {
            $this->merge([
                'about_short' => $this->aboutShort,
            ]);
        }
        if ($this->phoneNo) {
            $this->merge([
                'phone_no' => $this->phoneNo,
            ]);
        }
    }
}

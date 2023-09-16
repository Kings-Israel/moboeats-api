<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdererRequest extends FormRequest
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
                'email' => ['required', 'email'],
                'phoneNo' => ['required'],
                'address' => ['required'],
                'city' => ['required'],
                'state' => ['required'],
                'mapLocation' => ['required'],
                'image' => ['nullable'],
            ];
        } else {
            return [
                'uuid' => ['required', 'uuid'],
                'name' => ['sometimes', 'required'],
                'email' => ['sometimes', 'required', 'email'],
                'phoneNo' => ['sometimes', 'required'],
                'address' => ['sometimes', 'required'],
                'city' => ['sometimes', 'required'],
                'state' => ['sometimes', 'required'],
                'mapLocation' => ['sometimes', 'required'],
                'image' => ['sometimes', 'required'],
            ];
        }
    }

    protected function prepareForValidation()
    { 
    
        if ($this->mapLocation) {
            $this->merge([
                'map_location' => $this->mapLocation,
            ]);
        }
        if ($this->phoneNo) {
            $this->merge([
                'phone_no' => $this->phoneNo,
            ]);
        }
    }
}

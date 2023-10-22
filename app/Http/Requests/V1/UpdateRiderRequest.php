<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiderRequest extends FormRequest
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
                'address' => ['nullable'],
                'city' => ['nullable'],
                'state' => ['nullable'],
                'postalCode' => ['nullable'],
                'profilePicture' => ['nullable'],
                'vehicleType' => ['required'],
                'vehicleLicensePlate' => ['required'],
                'status' => ['required'],
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
                'postalCode' => ['sometimes', 'required'],
                'profilePicture' => ['sometimes', 'required'],
                'vehicleType' => ['sometimes', 'required'],
                'vehicleLicensePlate' => ['sometimes', 'required'],
                'status' => ['sometimes', 'required'],
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
        if ($this->profilePicture) {
            $this->merge([
                'profile_picture' => $this->profilePicture,
            ]);
        }
        if ($this->vehicleType) {
            $this->merge([
                'vehicle_type' => $this->vehicleType,
            ]);
        }
        if ($this->vehicleLicensePlate) {
            $this->merge([
                'vehicle_license_plate' => $this->vehicleLicensePlate,
            ]);
        }
        if ($this->phoneNo) {
            $this->merge([
                'phone_no' => $this->phoneNo,
            ]);
        }
    }
}

<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantBookmarkRequest extends FormRequest
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
            'restaurantId' => ['required', 'integer'],
        ];
    }
    protected function prepareForValidation()
    {
        $this->merge([
            'restaurant_id' => $this->restaurantId,
        ]);
    }
}

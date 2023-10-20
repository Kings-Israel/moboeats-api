<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuRequest extends FormRequest
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
            // 'uuid' => ['required', 'uuid'],
            // 'updatedBy' => ['required'],
            'title' => ['sometimes', 'required'],
            'description' => ['sometimes', 'required'],
            'status' => ['sometimes', 'integer'],
            // 'restaurantId' => ['sometimes','required'],
            // 'standardPrice' => ['sometimes','required'],
            // 'image' => 'sometimes|required|file|mimes:jpeg,png|max:2048', // Adjust the validation rules as per your requirements.
            'categoryIds' => 'sometimes|required|array',
            'categoryIds.*' => 'integer',
            // 'subcategoryIds' => 'sometimes|required|array',
            // 'subcategoryIds.*' => 'integer',

        ];
    }
}

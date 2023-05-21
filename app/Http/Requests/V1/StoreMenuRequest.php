<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
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
            'title' => ['required'],
            'description' => ['required'],
            'status' => ['nullable', 'integer'],
            'createdBy' => ['required'],
            'restaurantId' => ['required'],
            'categoryIds' => 'required|array',
            'subcategoryIds' => 'required|array',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'restaurant_id' => $this->restaurantId,
            'created_by' => $this->createdBy,
            'category_ids' => $this->categoryIds,
            'sub_category_ids' => $this->subcategoryIds,
        ]);
    }
}

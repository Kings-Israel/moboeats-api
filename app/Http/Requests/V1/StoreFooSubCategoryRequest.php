<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreFooSubCategoryRequest extends FormRequest
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
            'title' => ['required'],
            'description' => ['required'],
            'status' => ['nullable', 'integer'],
            'createdBy' => ['required'],
            'categoryIds' => 'required|array',
            'image' => ['nullable', 'sometimes', 'mimes:png,jpg', 'max:3000']
            // 'food_category_ids.*' => 'exists:food_categories,id',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'created_by' => $this->createdBy,
            'category_ids' => $this->categoryIds,
        ]);
    }
}

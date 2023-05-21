<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFooSubCategoryRequest extends FormRequest
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
                // 'uuid' => ['required'],
                'title' => ['required'],
                'description' => ['required'],
                'status' => ['nullable'],
                'updatedBy' => ['required'],
                'categoryIds' => 'required|array',
                // 'food_category_ids.*' => 'exists:food_categories,id',
            ];
        } else {
            return [
                // 'uuid' => ['required'],
                'title' => ['sometimes','required'],
                'description' => ['sometimes','required'],
                'status' => ['sometimes','nullable'],
                'updatedBy' => ['required'],
                'categoryIds' => ['sometimes', 'required','array'],
                // 'food_category_ids.*' => 'exists:food_categories,id',
            ];
        }

    }

    protected function prepareForValidation()
    {
        $this->merge([
            'updated_by' => $this->updatedBy,
            'category_ids' => $this->categoryIds,
        ]);
    }
}

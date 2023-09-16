<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreFooSubCategoryRequest extends FormRequest
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
            '*.title' => ['required'],
            '*.description' => ['required'],
            '*.status' => ['integer', 'nullable'],
            '*.createdBy' => ['required'],
            '*.categoryIds' => ['required','array'],
            // 'category_ids.*' => 'exists:categories,id',
        ];
    }

    protected function prepareForValidation()
    {
        $data = [];
        foreach ($this->toArray() as $obj) {
            $obj['created_by'] = $obj['createdBy'] ?? null;
            $obj['category_ids'] = $obj['categoryIds'] ?? null;

            $data[] = $obj;
        }
        $this->merge($data);
    }
}

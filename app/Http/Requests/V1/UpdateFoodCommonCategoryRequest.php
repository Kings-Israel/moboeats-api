<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFoodCommonCategoryRequest extends FormRequest
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
                'status' => ['required'],
                'updatedBy' => ['required'],
            ];
        } else {
            return [
                // 'uuid' => ['required'],
                'title' => ['sometimes','required'],
                'description' => ['sometimes','required'],
                'status' => ['sometimes','required'],
                'updatedBy' => ['sometimes','required'],
            ];
        }
        
    }
    protected function prepareForValidation()
    { 
        if ($this->updatedBy) {
            $this->merge([
                'updated_by' => $this->updatedBy,
            ]);
        }
        
    }
}

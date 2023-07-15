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
        // return [

        // ];
        $method = $this->method();
        if ($method == 'PUT') {
            return [
                'uuid' => ['required', 'uuid'],
                'title' => ['required'],
                'description' => ['required'],
                'status' => ['nullable', 'integer'],
                'updatedBy' => ['required'],
                'restaurantId' => ['required'],
                'image' => 'required|file|mimes:jpeg,png|max:2048', // Adjust the validation rules as per your requirements.
                'categoryIds' => 'required|array',
                'categoryIds.*' => 'integer',
                'subcategoryIds' => 'required|array',
                'subcategoryIds.*' => 'integer',
                ];
        } else {
            return [
                'uuid' => ['required', 'uuid'],
                'updatedBy' => ['required'],
                'title' => ['sometimes', 'required'],
                'description' => ['sometimes', 'required'],
                'status' => ['sometimes', 'integer'],
                'restaurantId' => ['sometimes','required'],
                'image' => 'sometimes|required|file|mimes:jpeg,png|max:2048', // Adjust the validation rules as per your requirements.
                'categoryIds' => 'sometimes|required|array',
                'categoryIds.*' => 'integer',
                'subcategoryIds' => 'sometimes|required|array',
                'subcategoryIds.*' => 'integer',
               
            ];
        }
    }

    protected function prepareForValidation()
    { 
    
        if ($this->restaurantId) {
            $this->merge([
                'restaurant_id' => $this->restaurantId,
            ]);
        }
        if ($this->updatedBy) {
            $this->merge([
                'updated_by' => $this->updatedBy,
            ]);
        }
        if ($this->categoryIds) {
            $this->merge([
                'category_ids' => $this->categoryIds,
            ]);
        }
        if ($this->subcategoryIds) {
            $this->merge([
                'sub_category_ids' => $this->subcategoryIds,
            ]);
        }

       
    }
}

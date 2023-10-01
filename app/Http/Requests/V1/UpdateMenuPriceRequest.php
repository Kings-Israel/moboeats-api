<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuPriceRequest extends FormRequest
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
                'menuId' => ['required'],
                'price' => ['required','numeric','between:0,9999999999.99'],
                'description' => ['required'],
                'status' => ['required'],
                // 'updatedBy' => ['required'],
            ];
        } else {
            return [
                // 'uuid' => ['required'],
                'menuId' => ['sometimes','required'],
                'description' => ['sometimes','required'],
                'status' => ['sometimes','required'],
                'price' => ['sometimes','numeric','between:0,9999999999.99'],
                // 'updatedBy' => ['sometimes','required'],
            ];
        }
    }

    protected function prepareForValidation()
    { 
        // if ($this->updatedBy) {
        //     info('yes updatedBy');
        //     $this->merge([
        //         'updated_by' => $this->updatedBy,
        //     ]);
        // }
        if ($this->menuId) {
            info('yes menuId');
            $this->merge([
                'menu_id' => $this->menuId,
            ]);
        }
        
    }
}

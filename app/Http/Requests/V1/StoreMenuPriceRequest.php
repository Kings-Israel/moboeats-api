<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuPriceRequest extends FormRequest
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
            'price' => ['required','numeric','between:0,9999999999.99'],
            'description' => ['required'],
            'status' => ['nullable', 'integer'],
            'createdBy' => ['required'],
            'menuId' => ['required'],
        ];
        //'required|numeric|between:-9999999999.99,9999999999.99'
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'menu_id' => $this->menuId,
            'created_by' => $this->createdBy,
        ]);
    }
}

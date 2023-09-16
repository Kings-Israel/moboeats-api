<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
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
            // 'cartId' => ['required', 'integer'],
            'menuId' => ['required', 'integer'],
            'quantity' => ['required','integer'],
            'status' => ['nullable', 'integer'],
        ];
    }
    protected function prepareForValidation()
    {
        $this->merge([
            // 'cart_id' => $this->cartId,
            'menu_id' => $this->menuId,
        ]);
    }
}

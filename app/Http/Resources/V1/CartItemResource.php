<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' =>$this->id,
            'uuid' => $this->uuid,
            'attributes' => [
                'cartId' => $this->cart_id,
                'menuId' => $this->menu_id,
                'quantity' => $this->quantity,
                'status' => (string) $this->status,
            ],
            'relationships' => [
                'menu' => new MenuResource($this->menu->load('images')),
                'cart' => $this->cart->load('user'),
            ]
        ];
    }
}

<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
                'orderId' => $this->order_id,
                'menuId' => $this->menu_id,
                'quantity' => $this->quantity,
                'subtotal' => $this->subtotal,
                'status' => (string) $this->status,
            ],
            'relationships' => [
                'order' => new OrderResource($this->whenLoaded('order')),
                'menu' => new MenuResource($this->whenLoaded('menu')),
            ]
                        
        ];
    }
}

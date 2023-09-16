<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
                'userId' => $this->user_id,
                'status' => (string) $this->status,
            ],
            'relationships' => [
                'user' => new UserResource($this->whenLoaded('user')),
                'cartItems' => CartItemResource::collection($this->whenLoaded('cartItems')),
            ]
                        
        ];
    }
}

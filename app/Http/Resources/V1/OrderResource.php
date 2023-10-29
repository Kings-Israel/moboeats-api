<?php

namespace App\Http\Resources\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
                'totalAmount' => $this->total_amount,
                'delivery' => $this->delivery,
                'deliveryFee' => $this->delivery_fee,
                'deliveryAddress' => $this->delivery_address,
                'deliveryStatus' => $this->delivery_status,
                'createdAt' => $this->created_at->format('D, M j, Y g:i A'),
                'status' => (string) $this->status,
                'service_charge' => $this->service_charge,
                'booking_time' => Carbon::parse($this->booking_time)->format('D, M j, Y g:i A'),
            ],
            'relationships' => [
                'user' => new UserResource($this->whenLoaded('user')),
                'restaurant' => new RestaurantResource($this->whenLoaded('restaurant')),
                'orderItems' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            ]

        ];
    }
}

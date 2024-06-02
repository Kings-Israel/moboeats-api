<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SupplementOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'supplier' => new SupplementSupplierResource($this->whenLoaded('supplier')),
            'supplement' => new SupplementResource($this->whenLoaded('supplement')),
            'quantity' => $this->quantity,
            'currency' => config('currency.Kenya'),
            'amount' => $this->amount,
            'status' => Str::title($this->status),
            'created_at' => $this->created_at
        ];
    }
}

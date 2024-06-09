<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplementSupplierResource extends JsonResource
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
            'name' => $this->name,
            'location' => $this->location,
            'description' => $this->description,
            'supplements' => SupplementResource::collection($this->whenLoaded('supplements')),
            'status' => $this->when(auth()->check() && auth()->user()->hasRole('admin'), $this->status),
            'orders_count' => $this->when(auth()->check() && auth()->user()->hasRole('admin'), $this->orders_count),
            'image' => $this->image,
        ];
    }
}

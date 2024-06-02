<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplementResource extends JsonResource
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
            'supplier' => new SupplementSupplierResource($this->whenLoaded('supplier')),
            'name' => $this->name,
            'currency' => config('currency.Kenya'),
            'price' => $this->price_per_quantity,
            'measuring_unit' => $this->measuring_unit,
            'description' => $this->description,
            'is_available' => $this->when(auth()->check() && auth()->user()->hasRole('admin'), $this->is_available),
            'orders_count' => $this->when(auth()->check() && auth()->user()->hasRole('admin'), $this->orders_count),
        ];
    }
}

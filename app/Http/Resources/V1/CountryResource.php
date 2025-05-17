<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
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
            'iso2' => $this->iso2,
            'iso3' => $this->iso3,
            'numeric_code' => $this->numeric_code,
            'phone_code' => $this->phone_code,
            'region' => $this->region,
            'subregion' => $this->subregion,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'population' => $this->population,
            'area_km2' => $this->area_km2,
            'status' => $this->status,
            'users' => UserResource::collection($this->whenLoaded('users'))
        ];
    }
}

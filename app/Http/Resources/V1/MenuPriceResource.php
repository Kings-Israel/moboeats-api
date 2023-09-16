<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' =>$this->id,
            'uuid' =>$this->uuid,
            'attributes' => [
                'price' =>  $this->price,
                'description' =>$this->description,
                'status' => (string) $this->status,
            ],
            'relationships' => [
                'menu' => [
                    'id' => $this->menu->id,
                    'uuid' => $this->menu->uuid,
                    'title' => $this->menu->title,
                ],
            ]
        ];
    }
}

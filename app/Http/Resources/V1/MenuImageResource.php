<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuImageResource extends JsonResource
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
            'uuid' =>$this->uuid,
            'attributes' => [
                'imageUrl' =>  '/' .config('app.storagePaths')['menus']['readPath'].'/' .$this->image_url,
                'sequence' =>$this->sequence,
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

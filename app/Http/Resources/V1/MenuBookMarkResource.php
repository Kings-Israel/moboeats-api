<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuBookMarkResource extends JsonResource
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
            'uuid' => $this->uuid,
            // 'attributes' => [
            //     'menuId' => $this->menu_id,
            //     'userId' => $this->user_id,
            //     'status' => (string) $this->status,
            // ],
            'relationships' => [
                'menu' => new MenuResource($this->whenLoaded('menu')),
                // 'user' => new UserResource($this->whenLoaded('user')),
            ]
        ];
    }
}

<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
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
            'uuid' => $this->uuid,
            'attributes' => [
                'title' => $this->title,
                'description' => $this->description,
                'status' => $this->status,
                'createdBy' => $this->created_by,
                'updatedBy' => $this->updated_by,
                'restaurant' => $this->restaurant,
                'status' => (string) $this->status,
            ],
            'relationships' => [
                'categories' => FoodCommonCategoryResource::collection($this->whenLoaded('categories')),
                'subCategories' => FooSubCategoryResource::collection($this->whenLoaded('subCategories')),
                'images' => MenuImageResource::collection($this->whenLoaded('images')),
                // 'images' => $this->images,
            ]
                        
        ];
    }
}

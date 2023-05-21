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
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'createdBy' => $this->created_by,
            'updatedBy' => $this->updated_by,
            // 'categories' => $this->categories,
            // 'subCategories' => $this->subCategories,
            'categories' => FoodCommonCategoryResource::collection($this->whenLoaded('categories')),
            'subCategories' => FooSubCategoryResource::collection($this->whenLoaded('subCategories')),
        ];
    }
}

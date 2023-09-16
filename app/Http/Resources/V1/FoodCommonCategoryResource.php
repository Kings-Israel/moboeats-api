<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodCommonCategoryResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'createdBy' => $this->created_by,
            'updatedBy' => $this->updated_by,
            'subCategories' => FooSubCategoryResource::collection($this->whenLoaded('food_sub_categories')),

        ];
    }
}

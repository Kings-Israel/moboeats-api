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
        return [
            'id' =>$this->id,
            'uuid' => $this->uuid,
            'attributes' => [
                'title' => $this->title,
                'description' => $this->description,
                'createdBy' => $this->created_by,
                'updatedBy' => $this->updated_by,
                'status' => (string) $this->status,
                'preparation_time' => $this->preparation_time,
                'average_rating' => $this->averageRating()
            ],
            'relationships' => [
                'categories' => FoodCommonCategoryResource::collection($this->whenLoaded('categories')),
                'subCategories' => FooSubCategoryResource::collection($this->whenLoaded('subCategories')),
                'images' => MenuImageResource::collection($this->whenLoaded('images')),
                'menuPrices' => $this->whenLoaded('menuPrices', function(){
                    return MenuPriceResource::collection($this->whenLoaded('menuPrices'));
                }, function(){
                    return  MenuPriceResource::collection($this->menuPrices);
                }),
                'restaurant' => $this->whenLoaded('restaurant', function(){
                    return new RestaurantResource($this->whenLoaded('restaurant'));
                }, function(){
                    return  new RestaurantResource($this->restaurant);
                }),
                'discount' => $this->whenLoaded('discount'),
                'orderItems' => $this->orderItems->load('order')->groupBy('order_id'),
                'orders_count' => $this->orderItems()->whereHas('order')->count() > 0 ? $this->orderItems()->whereHas('order', fn ($query) => $query->where('status', 5))->groupBy('order_id')->count() : 0,
                'total_orders_value' => $this->getOrdersValue(),
                'reviews' => $this->whenLoaded('reviews'),
            ],
        ];
    }
}

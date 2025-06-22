<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>$this->id,
            'uuid' =>$this->uuid,
            'attributes' => [
                'name' =>$this->name,
                'nameShort' =>$this->name_short,
                'email' =>$this->email,
                'about' =>$this->about,
                'aboutShort' =>$this->about_short,
                'phoneNo' =>$this->phone_no,
                'address' =>$this->address,
                'city' =>$this->city,
                'state' =>$this->state,
                'postalCode' =>$this->postal_code,
                'latitude' =>$this->latitude,
                'longitude' =>$this->longitude,
                'mapLocation' =>$this->map_location,
                'url' =>$this->url,
                'logo' =>$this->logo,
                'sitting_capacity' => $this->sitting_capacity,
                'status' => (string) $this->status,
                'denied_reason' => (string) $this->denied_reason,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'status' => $this->when(auth()->check() && (auth()->user()->hasRole('restaurant') || auth()->user()->hasRole('admin')), $this->status),
                'paypal_email' => $this->when(auth()->check() && (auth()->user()->hasRole('restaurant') || auth()->user()->hasRole('admin')), $this->paypal_email),
                'service_charge_agreement' => $this->when(auth()->check() && (auth()->user()->hasRole('restaurant') || auth()->user()->hasRole('admin')), $this->service_charge_agreement),
                'groceries_service_charge_agreement' => $this->when(auth()->check() && (auth()->user()->hasRole('restaurant') || auth()->user()->hasRole('admin')), $this->groceries_service_charge_agreement),
                'average_rating' => $this->averageRating(),
                'is_open' => $this->isOpen() ? true : false,
                'country' => $this->country,
                'country_code' => $this->country_code
            ],
            'relationships' => [
                'questionnaire' => new QuestionnaireResource($this->whenLoaded('questionnaire')),
                'user' => new UserResource($this->whenLoaded('user')),
                'orders' => $this->whenLoaded('orders'),
                'menus' => $this->whenLoaded('menus.categories'),
                'operating_hours' => $this->whenLoaded('operatingHours'),
                'documents' => $this->when(auth()->check() && auth()->user()->hasRole('restaurant'), $this->whenLoaded('documents')),
                'orders_count' => $this->whenLoaded('orders', function () {
                    return $this->orders->count();
                }),
                'menus_count' => $this->whenLoaded('menus', function () {
                    return $this->menus->count();
                }),
                'reviews' => $this->whenLoaded('reviews'),
                'restaurantTables' => $this->whenLoaded('restaurantTables.seatingArea'),
            ]
        ];
    }
}

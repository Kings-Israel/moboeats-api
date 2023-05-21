<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class QuestionnaireCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'uuid' =>$this->uuid,
            'restaurantId' =>$this->restaurant_id,
            'delivery' =>$this->delivery,
            'booking' =>$this->booking,
        ];
    }
}

<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reviewable' => $this->load('reviewable'),
            // 'reviewable_type' => $this->reviewable_type,
            // 'reviewable_id' => $this->reviewable_id,
            'rating' => $this->rating,
            'review' => $this->review
        ];
    }
}

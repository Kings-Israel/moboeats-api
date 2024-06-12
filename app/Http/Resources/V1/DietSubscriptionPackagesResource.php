<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class DietSubscriptionPackagesResource extends JsonResource
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
            'name' => $this->name,
            'tag_line' => $this->tag_line,
            'description' => $this->description,
            'price' => Str::upper($this->currency).' '.$this->price.'/'.Str::headline($this->duration),
            'subscriptions' => $this->when(auth()->check() && auth()->user()->hasRole('admin'), $this->subscriptions->load('user'))
        ];
    }
}

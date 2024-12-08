<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class OrphanageResource extends JsonResource
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
            'email' => $this->email,
            'contact_user_name' => $this->contact_name,
            'contact_user_email' => $this->contact_email,
            'contact_user_phone_number' => $this->contact_phone_number,
            'logo' => config('app.url').'/storage/orphanages/logo/'.$this->logo,
            'status' => Str::title($this->status),
            'created_by' => User::find($this->created_by),
            'location' => $this->location,
            'latitude' => (double) $this->location_lat,
            'longitude' => (double) $this->location_long,
        ];
    }
}

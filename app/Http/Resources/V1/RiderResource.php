<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiderResource extends JsonResource
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
            'uuid' =>$this->uuid,
            'attributes' => [
                'name' =>$this->name,
                'email' =>$this->email,
                'phoneNo' =>$this->phone_no,
                'address' =>$this->address,
                'city' =>$this->city,
                'state' =>$this->state,
                'postalCode' =>$this->postal_code,
                'profilePicture' =>$this->profile_picture,
                'vehicleType' =>$this->vehicle_type,
                'vehicleLicensePlate' =>$this->vehicle_license_plate,
                'status' => (string) $this->status, 
            ],
            'relationships' => [
                'user' => new UserResource($this->whenLoaded('user')),
            ]
           
        ];
    }
}

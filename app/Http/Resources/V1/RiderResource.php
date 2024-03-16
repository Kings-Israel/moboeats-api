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
        $status = '';
        switch ($this->status) {
            case 1:
                $status = 'Pending';
                break;
            case 2:
                $status = 'Approved';
                break;
            case 3:
                $status = 'Denied';
                break;
            default:
                $status = 'Pending';
                break;
        }
        return [
            'id' =>$this->id,
            'uuid' =>$this->uuid,
            'name' =>$this->name,
            'email' =>$this->email,
            'phone_no' =>$this->phone_no,
            'address' =>$this->address,
            'city' =>$this->city,
            'state' =>$this->state,
            'postal_code' =>$this->postal_code,
            'profile_picture' =>$this->profile_picture,
            'vehicle_type' =>$this->vehicle_type,
            'vehicle_license_plate' =>$this->vehicle_license_plate,
            'status' => $status,
            'paypal_email' => $this->paypal_email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'relationships' => [
                'user' => new UserResource($this->whenLoaded('user')),
            ],
            'reviews' => $this->when($this->reviews()->exists(), new ReviewResource($this->reviews)),
            'rejection_reason' => $this->when($this->status == '3', $this->rejection_reason)
        ];
    }
}

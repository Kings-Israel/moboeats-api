<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationParticipantResource extends JsonResource
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
            'conversation_id' => $this->conversation_id,
            'messageable_id' => $this->messageable_id,
            'messageable_type' => $this->messageable_type,
            'settings' => $this->settings,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'messageable' => new UserResource($this->messageable),
            'is_admin' => $this->messageable->email == 'admin@moboeats.com' ? true : false,
        ];
    }
}

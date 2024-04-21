<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'messageable_id' => $this->messageable_id,
            'notification_id' => $this->notification_id,
            'is_seen' => $this->is_seen,
            'is_sender' => $this->is_sender,
            'id' => $this->id,
            'body' => $this->body,
            'conversation_id' => $this->conversation_id,
            'participation_id' => $this->participation_id,
            'type' => $this->type,
            'data' => $this->data,
            'sender' => new UserResource($this->sender),
            'participation' => [
                'id' => $this->participation->id,
                'conversation_id' => $this->participation->conversation_id,
                'messageabble_id' => $this->participation->messageable_id,
                'settings' => $this->participation->settings,
                'messageable' => new UserResource($this->participation->messageable),
                'created_at' => $this->participation->created_at,
                'updated_at' => $this->participation->updated_at,
            ]
        ];
    }
}

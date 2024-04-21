<?php

namespace App\Http\Resources\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Chat;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $unread_count = 0;
        $conversation = $conversation = Chat::conversations()->getById($this->id);
        $unread_count = Chat::conversation($conversation)->setParticipant(auth()->user())->unreadCount();

        return [
            'id' => $this->id,
            'private' => $this->private,
            'direct_message' => $this->direct_message,
            'data' => $this->data,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'unread_count' => $unread_count,
            'last_message' => [
                'id' => $this->last_message ? $this->last_message->id : NULL,
                'message_id' => $this->last_message ? $this->last_message->message_id : NULL,
                'messageable_id' => $this->last_message ? $this->last_message->messageable_id : NULL,
                'messageable_type' => $this->last_message ? $this->last_message->messageable_type : NULL,
                'conversation_id' => $this->last_message ? $this->last_message->conversation_id : NULL,
                'participation_id' => $this->last_message ? $this->last_message->participation_id : NULL,
                'is_seen' => $this->last_message ? $this->last_message->is_seen : NULL,
                'is_sender' => $this->last_message ? $this->last_message->is_sender : NULL,
                'flagged' => $this->last_message ? $this->last_message->flagged : NULL,
                'created_at' => $this->last_message ? $this->last_message->created_at : NULL,
                'updated_at' => $this->last_message ? $this->last_message->updated_at : NULL,
                'body' => $this->last_message ? $this->last_message->body : NULL,
                'type' => $this->last_message ? $this->last_message->type : NULL,
                'data' => $this->last_message ? $this->last_message->data : NULL,
                'sender' => $this->last_message ? new UserResource($this->last_message->sender) : NULL,
                'from_now' => $this->last_message ? Carbon::parse($this->last_message->created_at)->diffForHumans() : NULL,
            ],
            'participants' => ConversationParticipantResource::collection($this->participants),
            'user_id' => auth()->id(),
        ];
    }
}

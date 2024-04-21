<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $user, $message, $conversation;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user, $message, $conversation)
    {
        $this->user = User::find($user);
        $this->message = $message;
        $this->conversation = $conversation;
    }

    public function broadcastWith()
    {
        return [
            'user' => $this->user,
            'message' => $this->message,
            'conversation' => $this->conversation
        ];
    }

    public function broadcastAs()
    {
        return 'new.message';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [
            new Channel(''.$this->user->email.''),
        ];
    }
}

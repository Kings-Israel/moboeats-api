<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ConversationResource;
use App\Http\Resources\V1\MessageResource;
use App\Jobs\DispatchMessage;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Musonza\Chat\Models\Conversation;
use Chat;

class MessageController extends Controller
{
    use HttpResponses;

    /**
     * Get all user's conversations
     *
     * @response 200
     * @responseParam data List of user's conversations
     */
    public function index()
    {
        $admin = User::find(1);

        $admin_conversation = Chat::conversations()->between(auth()->user(), $admin);

        if (!$admin_conversation) {
            $participants = [auth()->user(), $admin];
            // Create a new conversation
            $conversation = Chat::createConversation($participants);
            $conversation->update([
                'direct_message' => true,
            ]);
        }

        $conversations = Chat::conversations()
                                ->setParticipant(auth()->user())
                                ->get();

        $conversations = Arr::pluck($conversations, 'conversation');

        return $this->success(['conversations' => ConversationResource::collection($conversations), 'user_id' => auth()->id()]);
    }

    /**
     * Send a message
     *
     * @bodyParam message string required The message to send
     * @bodyParam receiver_id integer required The receiver's id
     *
     * @response 201
     *
     * @responseParam message The message was sent
     * @responseParam data The sent message
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => ['required'],
            'message' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        // Abort if user sends message to themselves
        if (auth()->user()->id == $request->receiver_id) {
            return $this->error('', 'Cannot send message to yourself', 403);
        }

        $user = User::find($request->receiver_id);

        if (!$user) {
            return $this->error('', 'Receiver was not found', 404);
        }

        $conversation = Chat::conversations()->between(auth()->user(), $user);

        if (!$conversation) {
            $participants = [auth()->user(), $user];
            // Create a new conversation
            $conversation = Chat::createConversation($participants);
            $conversation->update([
                'direct_message' => true,
            ]);
        }

        $message = Chat::message($request->message)->from(auth()->user())->to($conversation)->send();

        $participants = $conversation->getParticipants()->toArray();

        DispatchMessage::dispatchAfterResponse($participants, $message, new ConversationResource($conversation));

        return $this->success(['message' => 'Message sent', 'data' => new MessageResource($message)]);
    }

    /**
     * Display the messages in a conversation.
     *
     * @urlParam id The Id of the conversation
     *
     * @response 200
     * @responseField data The messages that are in the conversation
     */
    public function show($id)
    {
        // Get one conversation
        $conversation = Chat::conversations()->getById($id);

        if (!$conversation) {
            return $this->error('', 'Conversation not found', 404);
        }

        Chat::conversation($conversation)->setParticipant(auth()->user())->readAll();

        $messages = MessageResource::collection(Chat::conversation($conversation)->setParticipant(auth()->user())->limit(250000)->getMessages());

        return $this->success(['data' => $messages]);
    }

    /**
     * Reply to a message.
     *
     * @urlParam id The ID of the conversation
     *
     * @response 200
     * @responseField message Whether the message was sent or not
     * @responseField data The message that was sent
     *
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'message' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        // Get the conversation
        $conversation = Chat::conversations()->getById($id);

        if (!$conversation) {
            return $this->error('', 'Conversation not found', 404);
        }

        Chat::conversation($conversation)->setParticipant(auth()->user())->readAll();

        // Update the conversation with the new message
        $message = Chat::message($request->message)->from(auth()->user())->to($conversation)->send();

        $participants = $conversation->getParticipants()->toArray();

        DispatchMessage::dispatchAfterResponse($participants, $message, new ConversationResource($conversation));

        return $this->success(['message' => 'Message sent', 'data' => new MessageResource($message)]);
    }

    /**
     *
     * Mark a conversation as read
     *
     * This marks all the messages in a conversation as read
     *
     * @urlParam id The id of the conversation
     *
     * @response 200
     * @responseField message Whether the action was successfull
     * @responseField data The conversation
     *
     */
    public function markConversationAsRead($id)
    {
        $conversation = Chat::conversations()->getById($id);

        if (!$conversation) {
            return $this->error('', 'Conversation not found', 404);
        }

        Chat::conversation($conversation)->setParticipant(auth()->user())->readAll();

        return $this->success(['message' => 'Conversation marked as read', 'data' => $conversation]);
    }

    /**
     *
     * Mark a message as read
     *
     * Marks a specific message as read
     *
     * @urlParam id The id of the conversation
     *
     * @response 200
     * @responseField message Whether the action was successful
     * @responseField data The message that was marked as read
     *
     */
    public function markMessageAsRead($id)
    {
        $message = Chat::messages()->getById($id);

        if (!$message) {
            return $this->error('', 'Message not found', 404);
        }

        Chat::message($message)->setParticipant(auth()->user())->markRead();

        return $this->success(['message' => 'Message marked as read', 'data' => $message]);
    }

    /**
     *
     * Delete a specific message
     *
     * Deletes a specific message in a conversation
     *
     * @urlParam id The id of the conversation
     *
     * @response 200
     *
     * @responseField message The status of the action
     * @responseField data The message that has been deleted
     *
     */
    public function deleteMessage($id)
    {
        $message = Chat::messages()->getById($id);

        if (!$message) {
            return $this->error('', 'Message not found', 404);
        }

        $deletedMessage = Chat::message($message)->setParticipant(auth()->user())->delete();

        return $this->success(['message' => 'Message deleted successfully', 'data' => $deletedMessage]);
    }

    /**
     *
     * Get the unread messages in a conversation count
     *
     * @response 200
     *
     * @responseField data The unread messages count
     *
     */
    public function getCoversationUnreadMessagesCount($id)
    {
        $conversation = Chat::conversations()->getById($id);

        if (!$conversation) {
            return $this->error('', 'Conversation not found', 404);
        }

        $count = Chat::conversation($conversation)->setParticipant(auth()->user())->unreadCount();

        return $this->success(['data' => $count]);
    }

    /**
     *
     * Get the count of the user's unread messages
     *
     * @response 200
     *
     * @responseField data Unread messages count
     *
     */
    public function getUnreadMessagesCount()
    {
        $count = Chat::messages()->setParticipant(auth()->user())->unreadCount();

        return $this->success(['data' => $count]);
    }

    /**
     * Delete a conversation
     *
     * @urlParam id The ID of the conversation
     *
     * @response 200
     *
     * @responseParam message Conversation deleted successfully
     */
    public function deleteConversation($id)
    {
      $conversation = Conversation::with('messages', 'participants')->find($id);

      $conversation->messages->each(fn($message) => $message->delete());
      $conversation->participants->each(fn($participant) => $participant->delete());
      $conversation->delete();

      return $this->success(['message' => 'Conversation deleted successfully']);
    }

    // Admin get conversations
    public function getConversations()
    {
        $conversations = Conversation::with('participants.messageable', 'last_message')->orderBy('updated_at', 'DESC')->get();

        return $this->success(['data' => $conversations]);
    }

    // Admin get conversation
    public function getConversation($id)
    {
        $conversation = Conversation::with('participants.messageable', 'messages')->find($id);

        return $this->success(['data' => $conversation, 'user' => auth()->user()]);
    }

    // Admin Reply Conversation
    public function replyToConversation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        // Get the conversation
        $conversation = Chat::conversations()->getById($request->conversationId);

        if (!$conversation) {
            return $this->error('', 'Conversation not found', 404);
        }

        // Get Participants
        $participants = $conversation->getParticipants()->pluck('id');

        if (!collect($participants)->contains(auth()->id())) {
            $conversation->update([
                'direct_message' => false
            ]);
            $conversation->addParticipants([auth()->user()]);
            $conversation->update([
                'direct_message' => true
            ]);
        }

        // Update the conversation with the new message
        $message = Chat::message($request->message)->from(auth()->user())->to($conversation)->send();

        $participants = $conversation->getParticipants()->toArray();

        DispatchMessage::dispatchAfterResponse($participants, $message);

        $conversation = Conversation::with('participants.messageable', 'messages')->find($request->conversationId);

        return $this->success([
            'message' => 'Message sent',
            'data' => [
                'newMessageData' => [
                    'message' => $request->message,
                    'time' => now(),
                    'senderId' => auth()->id(),
                    'sender' => auth()->user(),
                ],
            ]
        ]);
    }
}

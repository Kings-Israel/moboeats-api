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

/**
 * @group Messaging
 *
 * In-app chat between users (customers, riders, restaurants and the admin).
 */
class MessageController extends Controller
{
    use HttpResponses;

    /**
     * List conversations
     *
     * Returns all chat conversations of the authenticated user. A direct conversation with the admin (user ID 1) is
     * created automatically if it does not exist yet.
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "conversations": [
     *       {
     *         "id": 7,
     *         "private": true,
     *         "direct_message": true,
     *         "data": [],
     *         "created_at": "2024-03-01T11:59:00.000000Z",
     *         "updated_at": "2024-03-01T12:00:00.000000Z",
     *         "unread_count": 2,
     *         "last_message": {"id": 42, "message_id": 42, "messageable_id": 5, "messageable_type": "App\\Models\\User", "conversation_id": 7, "participation_id": 13, "is_seen": 1, "is_sender": 1, "flagged": 0, "created_at": "2024-03-01T12:00:00.000000Z", "updated_at": "2024-03-01T12:00:00.000000Z", "body": "Hello, where is my order?", "type": "text", "data": [], "sender": {"id": 5, "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": null, "referral_code": null, "is_guided": true}, "from_now": "2 minutes ago"},
     *         "participants": [
     *           {"id": 13, "conversation_id": 7, "messageable_id": 5, "messageable_type": "App\\Models\\User", "settings": {"mute_mentions": false, "mute_conversation": false}, "created_at": "2024-03-01T11:59:00.000000Z", "updated_at": "2024-03-01T11:59:00.000000Z", "messageable": {"id": 5, "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": null, "referral_code": null, "is_guided": true}, "is_admin": false},
     *           {"id": 14, "conversation_id": 7, "messageable_id": 1, "messageable_type": "App\\Models\\User", "settings": {"mute_mentions": false, "mute_conversation": false}, "created_at": "2024-03-01T11:59:00.000000Z", "updated_at": "2024-03-01T11:59:00.000000Z", "messageable": {"id": 1, "uuid": "1a2b3c4d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Admin", "email": "admin@moboeats.com", "phone_number": "254700000000", "role": "admin", "avatar": null, "relationships": {"orderer": null}, "type": null, "referral_code": null, "is_guided": true}, "is_admin": true}
     *         ],
     *         "user_id": 5
     *       }
     *     ],
     *     "user_id": 5
     *   }
     * }
     *
     * @responseField data.conversations object[] The user's conversations, each with unread count, last message and participants.
     * @responseField data.user_id integer The authenticated user's ID.
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
     * Sends a message to another user, creating a direct conversation between the two users if one does not exist.
     * Participants are notified in the background.
     *
     * @bodyParam receiver_id integer required The user ID of the receiver. Example: 1
     * @bodyParam message string required The message body. Example: Hello, where is my order?
     *
     * @response 200 scenario="Message sent" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "message": "Message sent",
     *     "data": {
     *       "read_at": null,
     *       "created_at": "2024-03-01T12:00:00.000000Z",
     *       "updated_at": "2024-03-01T12:00:00.000000Z",
     *       "deleted_at": null,
     *       "messageable_id": 5,
     *       "notification_id": 88,
     *       "is_seen": 1,
     *       "is_sender": 1,
     *       "id": 42,
     *       "body": "Hello, where is my order?",
     *       "conversation_id": 7,
     *       "participation_id": 13,
     *       "type": "text",
     *       "data": [],
     *       "sender": {"id": 5, "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": null, "referral_code": null, "is_guided": true},
     *       "participation": {
     *         "id": 13,
     *         "conversation_id": 7,
     *         "messageabble_id": 5,
     *         "settings": {"mute_mentions": false, "mute_conversation": false},
     *         "messageable": {"id": 5, "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": null, "referral_code": null, "is_guided": true},
     *         "created_at": "2024-03-01T11:59:00.000000Z",
     *         "updated_at": "2024-03-01T11:59:00.000000Z"
     *       }
     *     }
     *   }
     * }
     * @response 403 scenario="Messaging yourself" {"status": "Error has occured...", "message": "Cannot send message to yourself", "data": ""}
     * @response 404 scenario="Receiver not found" {"status": "Error has occured...", "message": "Receiver was not found", "data": ""}
     * @response 422 scenario="Validation error" {"receiver_id": ["The receiver id field is required."], "message": ["The message field is required."]}
     *
     * @responseField data.message string Confirmation message.
     * @responseField data.data object The sent message (MessageResource).
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
     * Get conversation messages
     *
     * Returns the messages in a conversation and marks all of them as read for the authenticated user.
     *
     * @urlParam id integer required The ID of the conversation. Example: 7
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "data": [
     *       {
     *         "read_at": null,
     *         "created_at": "2024-03-01T12:00:00.000000Z",
     *         "updated_at": "2024-03-01T12:00:00.000000Z",
     *         "deleted_at": null,
     *         "messageable_id": 5,
     *         "notification_id": 88,
     *         "is_seen": 1,
     *         "is_sender": 1,
     *         "id": 42,
     *         "body": "Hello, where is my order?",
     *         "conversation_id": 7,
     *         "participation_id": 13,
     *         "type": "text",
     *         "data": [],
     *         "sender": {"id": 5, "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": null, "referral_code": null, "is_guided": true},
     *         "participation": {
     *           "id": 13,
     *           "conversation_id": 7,
     *           "messageabble_id": 5,
     *           "settings": {"mute_mentions": false, "mute_conversation": false},
     *           "messageable": {"id": 5, "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": null, "referral_code": null, "is_guided": true},
     *           "created_at": "2024-03-01T11:59:00.000000Z",
     *           "updated_at": "2024-03-01T11:59:00.000000Z"
     *         }
     *       }
     *     ]
     *   }
     * }
     * @response 404 scenario="Conversation not found" {"status": "Error has occured...", "message": "Conversation not found", "data": ""}
     *
     * @responseField data.data object[] The messages in the conversation (MessageResource).
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
     * Reply to a conversation
     *
     * Sends a message to an existing conversation and marks the conversation as read for the authenticated user.
     * Participants are notified in the background.
     *
     * @urlParam id integer required The ID of the conversation. Example: 7
     *
     * @bodyParam message string required The message body. Example: Thanks, received!
     *
     * @response 200 scenario="Message sent" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "message": "Message sent",
     *     "data": {
     *       "read_at": null,
     *       "created_at": "2024-03-01T12:00:00.000000Z",
     *       "updated_at": "2024-03-01T12:00:00.000000Z",
     *       "deleted_at": null,
     *       "messageable_id": 5,
     *       "notification_id": 88,
     *       "is_seen": 1,
     *       "is_sender": 1,
     *       "id": 42,
     *       "body": "Hello, where is my order?",
     *       "conversation_id": 7,
     *       "participation_id": 13,
     *       "type": "text",
     *       "data": [],
     *       "sender": {"id": 5, "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": null, "referral_code": null, "is_guided": true},
     *       "participation": {
     *         "id": 13,
     *         "conversation_id": 7,
     *         "messageabble_id": 5,
     *         "settings": {"mute_mentions": false, "mute_conversation": false},
     *         "messageable": {"id": 5, "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": null, "referral_code": null, "is_guided": true},
     *         "created_at": "2024-03-01T11:59:00.000000Z",
     *         "updated_at": "2024-03-01T11:59:00.000000Z"
     *       }
     *     }
     *   }
     * }
     * @response 404 scenario="Conversation not found" {"status": "Error has occured...", "message": "Conversation not found", "data": ""}
     * @response 422 scenario="Validation error" {"message": ["The message field is required."]}
     *
     * @responseField data.message string Confirmation message.
     * @responseField data.data object The sent message (MessageResource).
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
     * Mark a conversation as read
     *
     * Marks all the messages in a conversation as read for the authenticated user.
     *
     * @urlParam id integer required The ID of the conversation. Example: 7
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": null, "data": {"message": "Conversation marked as read", "data": {"id": 7, "private": true, "direct_message": true, "data": [], "created_at": "2024-03-01T11:59:00.000000Z", "updated_at": "2024-03-01T12:00:00.000000Z"}}}
     * @response 404 scenario="Conversation not found" {"status": "Error has occured...", "message": "Conversation not found", "data": ""}
     *
     * @responseField data.message string Confirmation message.
     * @responseField data.data object The conversation.
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
     * Mark a message as read
     *
     * Marks a specific message as read for the authenticated user.
     *
     * @urlParam id integer required The ID of the message. Example: 42
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": null, "data": {"message": "Message marked as read", "data": {"id": 42, "body": "Hello, where is my order?", "conversation_id": 7, "participation_id": 13, "type": "text", "data": [], "created_at": "2024-03-01T12:00:00.000000Z", "updated_at": "2024-03-01T12:00:00.000000Z", "sender": {"id": 5, "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": null, "referral_code": null, "is_guided": true}}}}
     * @response 404 scenario="Message not found" {"status": "Error has occured...", "message": "Message not found", "data": ""}
     *
     * @responseField data.message string Confirmation message.
     * @responseField data.data object The message that was marked as read.
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
     * Delete a message
     *
     * Deletes (trashes) a specific message for the authenticated user only; other participants still see it.
     *
     * @urlParam id integer required The ID of the message. Example: 42
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": null, "data": {"message": "Message deleted successfully", "data": null}}
     * @response 404 scenario="Message not found" {"status": "Error has occured...", "message": "Message not found", "data": ""}
     *
     * @responseField data.message string Confirmation message.
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
     * Count unread messages in a conversation
     *
     * Returns the number of unread messages in a conversation for the authenticated user.
     *
     * @urlParam id integer required The ID of the conversation. Example: 7
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": null, "data": {"data": 3}}
     * @response 404 scenario="Conversation not found" {"status": "Error has occured...", "message": "Conversation not found", "data": ""}
     *
     * @responseField data.data integer The unread messages count.
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
     * Count all unread messages
     *
     * Returns the total number of unread messages across all conversations of the authenticated user.
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": null, "data": {"data": 5}}
     *
     * @responseField data.data integer The unread messages count.
     */
    public function getUnreadMessagesCount()
    {
        $count = Chat::messages()->setParticipant(auth()->user())->unreadCount();

        return $this->success(['data' => $count]);
    }

    /**
     * Delete a conversation
     *
     * Permanently deletes a conversation together with all its messages and participants (for all participants).
     *
     * @urlParam id integer required The ID of the conversation. Example: 7
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": null, "data": {"message": "Conversation deleted successfully"}}
     *
     * @responseField data.message string Confirmation message.
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

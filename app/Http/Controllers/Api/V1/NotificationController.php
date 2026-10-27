<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\UserRestaurant;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @group Notifications
 *
 * In-app (database) notifications for the authenticated user or their restaurants.
 */
class NotificationController extends Controller
{
    use HttpResponses;

    /**
     * List unread notifications
     *
     * Returns unread database notifications. For restaurant owners, up to 10 unread notifications per owned restaurant
     * are returned; for restaurant employees, up to 10 of their restaurant's unread notifications; for all other users,
     * all of their own unread notifications.
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": null, "data": [{"id": "6f1c2d3e-4a5b-4c6d-8e7f-9a0b1c2d3e4f", "type": "App\\Notifications\\OrderUpdate", "notifiable_type": "App\\Models\\Restaurant", "notifiable_id": 2, "data": {"order_id": 12, "status": "accepted", "message": "Order #12 was accepted by the rider"}, "read_at": null, "created_at": "2024-03-01T12:10:00.000000Z", "updated_at": "2024-03-01T12:10:00.000000Z"}]}
     *
     * @responseField data object[] The unread notifications.
     */
    public function index()
    {
        $notifications = [];
        if (auth()->user()->hasRole('restaurant')) {
            $restaurants = auth()->user()->restaurants;
            foreach ($restaurants as $restaurant) {
                foreach($restaurant->unreadNotifications->take(10) as $notification) {
                    array_push($notifications, $notification);
                }
            }
        } elseif (auth()->user()->hasRole('restaurant employee')) {
            $restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant = Restaurant::find($restaurant->restaurant_id);
            // $restaurant = auth()->user()->restaurants->first();
            foreach ($restaurant->unreadNotifications->take(10) as $notification) {
                array_push($notifications, $notification);
            }
        } else {
            $notifications = auth()->user()->unreadNotifications;
        }

        return $this->success($notifications);
    }

    /**
     * Mark a notification as read
     *
     * Marks a single database notification as read. Note: the response puts the confirmation text in `data` and the
     * notification object in `message`.
     *
     * @urlParam notification string required The UUID of the notification. Example: 6f1c2d3e-4a5b-4c6d-8e7f-9a0b1c2d3e4f
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": {"id": "6f1c2d3e-4a5b-4c6d-8e7f-9a0b1c2d3e4f", "type": "App\\Notifications\\OrderUpdate", "notifiable_type": "App\\Models\\Restaurant", "notifiable_id": 2, "data": {"order_id": 12, "status": "accepted", "message": "Order #12 was accepted by the rider"}, "read_at": "2024-03-01T12:15:00.000000Z", "created_at": "2024-03-01T12:10:00.000000Z", "updated_at": "2024-03-01T12:10:00.000000Z"}, "data": "Notification marked as read"}
     * @response 404 scenario="Notification not found" {"message": "No query results for model [Illuminate\\Notifications\\DatabaseNotification] 6f1c2d3e-4a5b-4c6d-8e7f-9a0b1c2d3e4f"}
     *
     * @responseField message object The notification that was marked as read.
     */
    public function markAsRead($notification)
    {
        $notification = DatabaseNotification::findOrFail($notification);
        $notification->markAsRead();

        return $this->success('Notification marked as read', $notification);
    }

    /**
     * Mark all notifications as read
     *
     * Marks all of the authenticated user's unread notifications as read.
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": null, "data": "All notifications marked as read"}
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return $this->success('All notifications marked as read');
    }
}

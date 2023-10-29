<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\UserRestaurant;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    use HttpResponses;

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

    public function markAsRead($notification)
    {
        $notification = DatabaseNotification::findOrFail($notification);
        $notification->markAsRead();

        return $this->success('Notification marked as read', $notification);
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return $this->success('All notifications marked as read');
    }
}

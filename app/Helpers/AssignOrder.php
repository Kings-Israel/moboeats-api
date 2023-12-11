<?php

namespace App\Helpers;

use App\Jobs\SendNotification;
use App\Models\AssignedOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AssignOrder
{
    public static function assignOrder($order_id)
    {
        $rider = NULL;
        $success = true;
        $order = Order::with('user', 'restaurant')->find($order_id);
        $rider = self::getRider($order_id);

        if($rider) {
            $pickup_address = NULL;
            if ($order->restaurant->latitude && $order->restaurant->longitude) {
                $pickup_address = [$order->restaurant->latitude, $order->restaurant->longitude];
            }

            $delivery_address = NULL;
            if ($order->delivery_location_lat && $order->delivery_location_lng) {
                $delivery_address = [$order->delivery_location_lat, $order->delivery_location_lng];
            }

            $notification_response = false;
            $response = SendNotification::dispatchSync(User::find($rider->id), 'You have been assigned delivery.', ['pickup_address' => $pickup_address, 'delivery_address' => $delivery_address, 'order_code' => $order->id, 'order_details' => $order]);

            if (json_decode($response) != 0) {
                $notification_response = true;
            }
            // $notification_response = json_decode($response)->success == 1 ? true : false;
            while (!$notification_response) {
                AssignedOrder::create([
                    'user_id' => $rider->id,
                    'order_id' => $order->id,
                    'status' => 'rejected'
                ]);
                $response = SendNotification::dispatchSync(User::find($rider->id), 'You have been assigned delivery.', ['pickup_address' => $pickup_address, 'delivery_address' => $delivery_address, 'order_code' => $order->id, 'order_details' => $order]);
                // $notification_response = json_decode($response)->success == 1 ? true : false;
                if (json_decode($response) != 0) {
                    $notification_response = true;
                }

                $rider = self::getRider($order_id);
            }

            AssignedOrder::create([
                'user_id' => $rider->id,
                'order_id' => $order->id,
            ]);

            return $success;
        }

        $success = false;
        return $success;
    }

    private static function getRider($order)
    {
        $order = Order::find($order);
        // Get Unassigned Couriers and order by closest
        $rider = User::where('device_token', '!=', NULL)
                        ->whereHas('roles', function($query) {
                            $query->where('name', 'rider');
                        })
                        ->whereHas('rider')
                        ->where('status', 2)
                        ->where(function($query) use ($order) {
                            $assigned_riders = Order::where('rider_id', '!=', NULL)->get()->pluck('rider_id');
                            // Get couriers who have been assigned delivery to the restaurant
                            // and are going close to another order from the same restaurant
                            // $deliveries = DB::table("orders")
                            //                     ->where('delivery', 1)
                            //                     ->where('rider_id', '!=', NULL)
                            //                     ->where('restaurant_id', $order->restaurant_id)
                            //                     ->where('delivery_status', '!=', 'Delivered')
                            //                     ->where('delivery_status', '!=', 'On Delivery')
                            //                     ->where('delivery_location_lat', '!=', '')
                            //                     ->select(
                            //                         DB::raw("6371 * acos(cos(radians(" . $order->delivery_location_lat . "))
                            //                         * cos(radians(orders.delivery_location_lat))
                            //                         * cos(radians(orders.delivery_location_lng)
                            //                         - radians(" . $order->delivery_location_lng . "))
                            //                         + sin(radians(" . $order->delivery_location_lat. "))
                            //                         * sin(radians(orders.delivery_location_lat))) AS distance"))
                            //                     ->get();

                            $deliveries = Order::where('delivery', 1)
                                                ->where('rider_id', '!=', NULL)
                                                ->where('restaurant_id', $order->restaurant_id)
                                                ->where('delivery_status', '!=', 'Delivered')
                                                ->where('delivery_status', '!=', 'On Delivery')
                                                ->distance($order->delivery_location_lat, $order->delivery_location_lng)
                                                ->get();

                            // Filter to couriers distances less than 3 MILES
                            $nearby_deliveries = $deliveries->filter(function($delivery) {
                                return (int) ($delivery->distance) <= 3;
                            })->pluck('rider_id')->values()->all();

                            // Check if rider rejected the delivery request
                            $rejected_orders = AssignedOrder::where('order_id', $order->id)->where('status', 'rejected')->pluck('user_id');

                            $query->whereNotIn('id', $rejected_orders)
                                    ->where(function ($query) use ($assigned_riders, $nearby_deliveries) {
                                        $query->orWhereIn('id', $nearby_deliveries);
                                    });
                        })
                        ->get()
                        // Filter by distance to restaurant
                        ->each(function($rider, $key) use ($order) {
                            if ($rider->latitude != NULL && $rider->lognitude != NULL) {
                                $business_location = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$rider->latitude.','.$rider->longitude.'&destinations='.$order->restaurant->latitude.','.$order->restaurant->longitude.'&key='.config('services.map.key'));

                                if (json_decode($business_location)->rows[0]->elements[0]->status != "NOT_FOUND" && json_decode($business_location)->rows[0]->elements[0]->status != "ZERO_RESULTS") {
                                    $distance = json_decode($business_location)->rows[0]->elements[0]->distance->text;
                                    $time = json_decode($business_location)->rows[0]->elements[0]->duration->text;
                                    $rider['distance'] = $distance;
                                    $rider['time_away'] = $time;
                                }
                            } else {
                                $rider['distance'] = NULL;
                                $rider['time_away'] = NULL;
                            }
                        })
                        // Order by distance and time
                        ->sortBy([
                            fn($a, $b) => (double) explode(' ', $a['distance'])[0] >= (double) explode(' ',$b['distance'])[0],
                        ])
                        // Get the first courier
                        ->first();

        return $rider;
    }
}

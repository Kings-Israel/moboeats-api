<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\OrderDeliveryLocationUpdate;
use App\Events\UpdateOrder;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RiderResource;
use App\Models\AssignedOrder;
use App\Models\Order;
use App\Models\Rider;
use App\Notifications\OrderUpdate;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * @group Rider Profile APIs
 *
 * @authenticated
 */
class RiderController extends Controller
{
    use HttpResponses;

    /**
     * Get Orders and Assigned Orders
     *
     * @response 200
     *
     * @responseParam orders List of orders
     * @responseParam assigned_orders List of assigned orders
     */
    public function orders()
    {
        $orders = Order::with('restaurant', 'user')->where('rider_id', '=', auth()->id())->get();
        $assigned_orders = AssignedOrder::with('order.user')->where('user_id', '=', auth()->id())->get();
        return $this->success(['orders' => $orders, 'assigned_orders' => $assigned_orders], '', 200);
    }

    /**
     * Get Rider Profile
     *
     * @response 200
     *
     * @repsonseParam data Rider's Profile
     */
    public function profile()
    {
        if (!auth()->user()->rider) {
            return $this->error('', 'Rider Profile not created', 404);
        }

        return $this->success(new RiderResource(auth()->user()->rider));
    }

    /**
     * Create Rider Profile
     *
     * @bodyParam vehicle_type string required The vehicle type of the rider
     * @bodyParam vehicle_licesne_plate sring required The license plate of the rider's vehicle
     * @bodyParam profile_picture file required The profile picture of the rider
     * @bodyParam city string The city where the rider is located
     * @bodyParam state string The state of where the rider is located
     * @bodyParam postal_code string The postal code of the rider
     *
     * @response 200
     *
     * @responseParam data The Rider's created profile
     * @responseParam message Profile created successfully
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_type' => ['required'],
            'vehicle_license_plate' => ['required'],
            'profile_picture' => ['required', 'mimes:png,jpg,jpeg', 'max:10000']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid data', 422);
        }

        if (!auth()->user()->rider) {
            $rider = Rider::create([
                'user_id' => auth()->id(),
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'phone_no' => auth()->user()->phone_number,
                'address' => $request->has('address') ? $request->address : NULL,
                'city' => $request->has('city') ? $request->city : NULL,
                'state' => $request->has('state') ? $request->state : NULL,
                'postal_code' => $request->has('postal_code') ? $request->postal_code : NULL,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_license_plate' => $request->vehicle_license_plate,
                'profile_picture' => pathinfo($request->profile_picture->store('avatar', 'rider'), PATHINFO_BASENAME),
            ]);
        } else {
            $rider = auth()->user()->rider;
        }

        return $this->success(new RiderResource($rider), 'Profile created successfully');
    }

    /**
     * Update Rider Prodile
     *
     * @bodyParam vehicle_type string The vehicle type of the rider
     * @bodyParam vehicle_licesne_plate sring The license plate of the rider's vehicle
     * @bodyParam profile_picture file The profile picture of the rider
     * @bodyParam city string The city where the rider is located
     * @bodyParam state string The state of where the rider is located
     * @bodyParam postal_code string The postal code of the rider
     *
     * @response 200
     *
     * @responseParam data The Rider's updated profile
     * @responseParam message Profile updated successfully
     */
    public function update(Request $request)
    {
        $request->validate([
            'email' => ['nullable', 'unique:riders,email,except,id'],
        ]);

        $rider = Rider::where('user_id', auth()->id())->first();

        if (!$rider) {
            return $this->error('', 'Profile not found', 404);
        }

        $rider->update([
            'email' => $request->has('email') ? $request->get('email') : $rider->email,
            'phone_no' => $request->has('phone_no') ? $request->get('phone_no') : $rider->phone_no,
            'address' => $request->has('address') ? $request->get('address') : $rider->address,
            'city' => $request->has('city') ? $request->get('city') : $rider->city,
            'state' => $request->has('state') ? $request->get('state') : $rider->state,
            'postal_code' => $request->has('postal_code') ? $request->get('postal_code') : $rider->postal_code,
            'vehicle_type' => $request->has('vehicle_type') ? $request->vehicle_type : $rider->vehicle_type,
            'vehicle_license_plate' => $request->has('vehicle_license_plate') ? $request->vehicle_license_plate : $rider->vehicle_license_plate,
            'status' => 1
        ]);

        if ($request->hasFile('profile_picture')) {
            $rider->update([
                'profile_picture' => pathinfo($request->profile_picture->store('avatar', 'rider'), PATHINFO_BASENAME)
            ]);
        }

        return $this->success(new RiderResource($rider), 'Profile updated successfully');
    }

    public function updateOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'status' => ['required', Rule::in(['accept', 'reject', 'on_delivery', 'delivered'])]
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Please select an order', 422);
        }

        $order = Order::with('user', 'restaurant')->where('id', $request->order_id)->orWhere('uuid', $request->order_id)->first();

        if (!$order) {
            return $this->error('Order not found', 'The selected order was not found', 422);
        }

        if ($request->status == 'accept') {
            if ($order->rider_id != NULL) {
                return $this->success('Order assigned', 'The order was already assigned', 200);
            }
        }

        $assignment = AssignedOrder::where('user_id', auth()->id())
                        ->where('order_id', $order->id)
                        ->first();

        if (!$assignment) {
            $assignment = AssignedOrder::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
            ]);
        }

        if ($request->status == 'accept') {
            $order->update([
                'rider_id' => auth()->id(),
                'status' => 2,
                'delivery_status' => 'Awaiting Pick Up'
            ]);

            $assignment->update([
                'status' => 'accepted'
            ]);

            Http::withHeaders([
                'Authorization' => 'key='.config('services.firebase.key'),
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_id' => $order->user->device_token,
                'notification' => 'Your order will be delivered by '. auth()->user()->name,
                'data' => [
                    'rider_id' => auth()->id(),
                ]
            ]);

            $order->restaurant->notify(new OrderUpdate($order, 'accepted'));
            event(new UpdateOrder($order->restaurant, $order, 'accepted'));

            return $this->success($order, 'Order updated successfully', 200);
        }

        if ($request->status == 'on_delivery') {
            $order->update([
                'status' => 4,
                'delivery_status' => 'On Delivery'
            ]);

            Http::withHeaders([
                'Authorization' => 'key='.config('services.firebase.key'),
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_id' => $order->user->device_token,
                'notification' => 'Your order has been picked by the rider',
                'data' => [
                    'rider_id' => auth()->id(),
                ]
            ]);

            $order->restaurant->notify(new OrderUpdate($order, 'on_delivery'));
            event(new UpdateOrder($order->restaurant, $order, 'on_delivery'));

            return $this->success($order, 'Order updated successfully', 200);
        }

        if ($request->status == 'delivered') {
            $order->update([
                'status' => 5, // Delivered
                'delivery_status' => 'Delivered',
            ]);

            $order->restaurant->notify(new OrderUpdate($order, 'delivered'));
            event(new UpdateOrder($order->restaurant, $order, 'delivered'));

            return $this->success($order, 'Order updated successfully', 200);
        }

        $assignment->update([
            'status' => 'rejected'
        ]);

        $order->restaurant->notify(new OrderUpdate($order, 'rejected'));
        event(new UpdateOrder($order->restaurant, $order, 'rejected'));

        return $this->success('', 'Order updated successfully', 200);
    }

    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'longitude' => 'required',
            'latitude' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $location = Http::get('https://maps.googleapis.com/maps/api/geocode/json?', [
                                'latlng' => $request->latitude.','.$request->longitude,
                                'key' => config('services.map.key')
                            ]);
        $location = json_decode($location);
        auth()->user()->update([
            'location' => $location->results[0]->formatted_address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return $this->success('Location update', 'Location updated successfully', 200);
    }

    public function updateDeliveryLocation(Request $request, $order_id)
    {
        $validator = Validator::make($request->all(), [
            'longitude' => 'required',
            'latitude' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $order = Order::with('user')->where('id', $order_id)->orWhere('uuid', $order_id)->first();

        if ($order->user->device_token) {
            // Send Location to User
            Http::withHeaders([
                'Authorization' => 'key='.config('services.firebase.key'),
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_id' => $order->user->device_token,
                'notification' => [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude
                ],
                'data' => [
                    'order_id' => $order->id,
                ]
            ]);
        }

        event(new OrderDeliveryLocationUpdate($order->load('restaurant'), $request->latitude, $request->longitude));

        return $this->success('Location update', 'Location updated successfully', 200);
    }

    public function earnings()
    {
        $earnings = Order::where('rider_id', auth()->id())->where('status', 5)->sum('delivery_fee');

        $earnings_in_past_week = Order::whereBewteen('created_at', [now()->startOfWeek(), now()->endOfWeek()])->where('status', 5)->sum('delivery_fee');

        return $this->success([
            'earnings' => $earnings,
            'earnings_in_past_week' => $earnings_in_past_week
        ]);
    }

    public function withdraw(Request $request)
    {

    }
}

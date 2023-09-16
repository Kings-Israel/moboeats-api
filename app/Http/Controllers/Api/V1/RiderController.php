<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssignedOrder;
use App\Models\Order;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RiderController extends Controller
{
    use HttpResponses;

    // Delivered Ordes
    public function orders()
    {
        $orders = Order::with('restaurant', 'user')->where('rider_id', '=', auth()->id())->get();
        $assigned_orders = AssignedOrder::where('user_id', '=', auth()->id())->get();
        return $this->success(['orders' => $orders, 'assigned_orders' => $assigned_orders], '', 200);
    }

    public function updateOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'status' => ['required', Rule::in(['accept', 'reject', 'delivered'])]
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Please select an order', 422);
        }

        $order = Order::with('user', 'restaurant')->where('id', $request->order_id)->orWhere('uuid', $request->order_id)->first();

        if (!$order) {
            return $this->error('Order not found', 'The selected order was not found', 422);
        }

        if ($order->rider_id != NULL) {
            return $this->success('Order assigned', 'The order was already assigned', 200);
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
                'rider_id' => auth()->id()
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

            return $this->success($order, 'Order updated successfully', 200);
        }

        if ($request->status == 'delivered') {
            $order->update([
                'status' => 3, // Delivered
            ]);

            return $this->success($order, 'Order updated successfully', 200);
        }

        $assignment->update([
            'status' => 'rejected'
        ]);

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

        $location = Http::get('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$request->latitude.','.$request->longitude.'&key='.config('services.maps.partial_key'));
        $location = json_decode($location);

        auth()->user()->update([
            'location' => $location->results[0]->formatted_address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return $this->success('Location update', 'Location updated successfully', 200);
    }

    public function updateDeliveryLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'longitude' => 'required',
            'latitude' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $order = Order::with('user')->find($request->order_id);

        if ($order->user->device_token) {
            // Send Location to User
            Http::withHeaders([
                // TODO: Add Firebase Key
                'Authorization' => 'key=',
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

        return $this->success('Location update', 'Location updated successfully', 200);
    }

    public function earnings()
    {

    }

    public function withdraw(Request $request)
    {

    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Payout;
use App\Models\RiderTip;
use App\Events\UpdateOrder;
use App\Helpers\AssignOrder;
use Illuminate\Http\Request;
use App\Models\AssignedOrder;
use App\Traits\HttpResponses;
use Illuminate\Validation\Rule;
use App\Notifications\OrderUpdate;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\V1\RiderResource;
use Illuminate\Support\Facades\Validator;
use App\Events\OrderDeliveryLocationUpdate;
use App\Jobs\SendCommunication;

/**
 * @group Riders
 *
 * Rider profile management, order delivery workflow, location tracking, earnings and tips.
 *
 * @authenticated
 */
class RiderController extends Controller
{
    use HttpResponses;

    /**
     * List rider orders
     *
     * Returns the orders delivered/being delivered by the authenticated rider and all order assignments
     * (accepted, rejected or pending) made to the rider.
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": "",
     *   "data": {
     *     "orders": [
     *       {
     *         "id": 12,
     *         "uuid": "3f6c1a2b-7d8e-4f90-a1b2-c3d4e5f6a7b8",
     *         "user_id": 5,
     *         "restaurant_id": 2,
     *         "total_amount": 1500,
     *         "delivery": true,
     *         "delivery_address": "Moi Avenue, Nairobi",
     *         "delivery_location_lat": -1.2847473,
     *         "delivery_location_lng": 36.8219,
     *         "delivery_fee": 200,
     *         "delivery_status": "In Progress",
     *         "status": 2,
     *         "rider_id": 9,
     *         "booking_time": null,
     *         "service_charge": 50,
     *         "discount": 0,
     *         "created_at": "2024-03-01T12:00:00.000000Z",
     *         "updated_at": "2024-03-01T12:10:00.000000Z",
     *         "country": "Kenya",
     *         "restaurant": {"id": 2, "uuid": "a1b2c3d4-0000-4f90-a1b2-c3d4e5f6a7b8", "name": "Mama's Kitchen", "address": "Kimathi Street, Nairobi", "latitude": "-1.2833", "longitude": "36.8167"},
     *         "user": {"id": 5, "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678"}
     *       }
     *     ],
     *     "assigned_orders": [
     *       {
     *         "id": 3,
     *         "order_id": 12,
     *         "user_id": 9,
     *         "status": "accepted",
     *         "created_at": "2024-03-01T12:05:00.000000Z",
     *         "updated_at": "2024-03-01T12:10:00.000000Z",
     *         "order": {
     *           "id": 12,
     *           "uuid": "3f6c1a2b-7d8e-4f90-a1b2-c3d4e5f6a7b8",
     *           "total_amount": 1500,
     *           "delivery_status": "In Progress",
     *           "status": 2,
     *           "user": {"id": 5, "name": "Jane Doe", "phone_number": "254712345678"},
     *           "restaurant": {"id": 2, "name": "Mama's Kitchen"}
     *         }
     *       }
     *     ]
     *   }
     * }
     *
     * @responseField data.orders object[] Orders where the authenticated user is the rider, with `restaurant` and `user` loaded.
     * @responseField data.assigned_orders object[] Order assignments for the rider, with `order.user` and `order.restaurant` loaded.
     */
    public function orders()
    {
        $orders = Order::with('restaurant', 'user')->where('rider_id', '=', auth()->id())->get();
        $assigned_orders = AssignedOrder::with('order.user', 'order.restaurant')->where('user_id', '=', auth()->id())->get();
        return $this->success(['orders' => $orders, 'assigned_orders' => $assigned_orders], '', 200);
    }

    /**
     * Get rider profile
     *
     * Returns the rider profile of the authenticated user.
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "id": 4,
     *     "uuid": "c7d8e9f0-1a2b-4c3d-8e4f-5a6b7c8d9e0f",
     *     "name": "John Rider",
     *     "email": "rider@example.com",
     *     "phone_no": "254700111222",
     *     "address": "12 Moi Avenue",
     *     "city": "Nairobi",
     *     "state": "Nairobi",
     *     "postal_code": "00100",
     *     "profile_picture": "rider-avatar.jpg",
     *     "vehicle_type": "motorbike",
     *     "vehicle_license_plate": "KMDA 123A",
     *     "status": "Approved",
     *     "paypal_email": "rider@example.com",
     *     "created_at": "2024-01-10T09:00:00.000000Z",
     *     "updated_at": "2024-01-12T09:00:00.000000Z",
     *     "relationships": []
     *   }
     * }
     * @response 404 scenario="Profile not created" {"status": "Error has occured...", "message": "Rider Profile not created", "data": ""}
     *
     * @responseField data.uuid string The rider profile UUID.
     * @responseField data.status string Approval status: `Pending`, `Approved` or `Denied`.
     * @responseField data.reviews object Reviews of the rider (only present when the rider has reviews).
     * @responseField data.rejection_reason string Reason for rejection (only present when status is `Denied`).
     */
    public function profile()
    {
        if (!auth()->user()->rider) {
            return $this->error('', 'Rider Profile not created', 404);
        }

        return $this->success(new RiderResource(auth()->user()->rider));
    }

    /**
     * Create rider profile
     *
     * Creates a rider profile for the authenticated user using the user's name, email and phone number.
     * If the user already has a rider profile, the existing profile is returned unchanged.
     *
     * @bodyParam vehicle_type string required The vehicle type of the rider. Example: motorbike
     * @bodyParam vehicle_license_plate string required The license plate of the rider's vehicle. Example: KMDA 123A
     * @bodyParam profile_picture file required The profile picture of the rider (png, jpg, jpeg; max 10000 KB).
     * @bodyParam paypal_email string required The rider's PayPal email for payouts. Example: rider@example.com
     * @bodyParam address string The address of the rider. Example: 12 Moi Avenue
     * @bodyParam city string The city where the rider is located. Example: Nairobi
     * @bodyParam state string The state where the rider is located. Example: Nairobi
     * @bodyParam postal_code string The postal code of the rider. Example: 00100
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": "Profile created successfully",
     *   "data": {
     *     "id": 4,
     *     "uuid": "c7d8e9f0-1a2b-4c3d-8e4f-5a6b7c8d9e0f",
     *     "name": "John Rider",
     *     "email": "rider@example.com",
     *     "phone_no": "254700111222",
     *     "address": "12 Moi Avenue",
     *     "city": "Nairobi",
     *     "state": "Nairobi",
     *     "postal_code": "00100",
     *     "profile_picture": "rider-avatar.jpg",
     *     "vehicle_type": "motorbike",
     *     "vehicle_license_plate": "KMDA 123A",
     *     "status": "Pending",
     *     "paypal_email": "rider@example.com",
     *     "created_at": "2024-01-10T09:00:00.000000Z",
     *     "updated_at": "2024-01-10T09:00:00.000000Z",
     *     "relationships": {
     *       "user": {
     *         "id": 9,
     *         "uuid": "d1e2f3a4-5b6c-4d7e-8f90-a1b2c3d4e5f6",
     *         "name": "John Rider",
     *         "email": "rider@example.com",
     *         "phone_number": "254700111222",
     *         "role": "rider",
     *         "avatar": null,
     *         "relationships": {"orderer": null},
     *         "type": null,
     *         "referral_code": null,
     *         "is_guided": false
     *       }
     *     }
     *   }
     * }
     * @response 422 scenario="Validation error" {"status": "Error has occured...", "message": "Invalid data", "data": {"vehicle_type": ["The vehicle type field is required."], "profile_picture": ["The profile picture field is required."]}}
     *
     * @responseField data object The rider profile (RiderResource) with the related user.
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vehicle_type' => ['required'],
            'vehicle_license_plate' => ['required'],
            'profile_picture' => ['required', 'mimes:png,jpg,jpeg', 'max:10000'],
            'paypal_email' => ['required', 'email']
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
                'paypal_email' => $request->paypal_email
            ]);
        } else {
            $rider = auth()->user()->rider;
        }

        return $this->success(new RiderResource($rider->load('user')), 'Profile created successfully');
    }

    /**
     * Update rider profile
     *
     * Updates the authenticated user's rider profile. Fields that are not sent keep their current values.
     * Updating the profile resets the rider status to pending approval (`status` = 1). When a `profile`
     * image is uploaded it is stored as both the rider's profile picture and the user's avatar.
     *
     * @bodyParam vehicle_type string required The vehicle type of the rider. Example: motorbike
     * @bodyParam paypal_email string required The rider's PayPal email for payouts. Example: rider@example.com
     * @bodyParam vehicle_licesne_plate string The license plate of the rider's vehicle (note: the parameter name is spelled `vehicle_licesne_plate`). Example: KMDA 123A
     * @bodyParam email string The rider's email. Example: rider@example.com
     * @bodyParam phone_no string The rider's phone number. Example: 254700111222
     * @bodyParam address string The address of the rider. Example: 12 Moi Avenue
     * @bodyParam city string The city where the rider is located. Example: Nairobi
     * @bodyParam state string The state where the rider is located. Example: Nairobi
     * @bodyParam postal_code string The postal code of the rider. Example: 00100
     * @bodyParam profile file A new profile picture for the rider.
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": "Profile updated successfully",
     *   "data": {
     *     "id": 4,
     *     "uuid": "c7d8e9f0-1a2b-4c3d-8e4f-5a6b7c8d9e0f",
     *     "name": "John Rider",
     *     "email": "rider@example.com",
     *     "phone_no": "254700111222",
     *     "address": "12 Moi Avenue",
     *     "city": "Nairobi",
     *     "state": "Nairobi",
     *     "postal_code": "00100",
     *     "profile_picture": "rider-avatar.jpg",
     *     "vehicle_type": "motorbike",
     *     "vehicle_license_plate": "KMDA 123A",
     *     "status": "Pending",
     *     "paypal_email": "rider@example.com",
     *     "created_at": "2024-01-10T09:00:00.000000Z",
     *     "updated_at": "2024-01-15T09:00:00.000000Z",
     *     "relationships": []
     *   }
     * }
     * @response 404 scenario="Profile not found" {"status": "Error has occured...", "message": "Profile not found", "data": ""}
     * @response 422 scenario="Validation error" {"message": "The paypal email field is required.", "errors": {"paypal_email": ["The paypal email field is required."]}}
     *
     * @responseField data object The updated rider profile (RiderResource).
     */
    public function update(Request $request)
    {
        $request->validate([
            'email' => ['nullable'],
            'paypal_email' => ['required', 'email'],
            'vehicle_type' => ['required']
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
            'vehicle_license_plate' => $request->has('vehicle_licesne_plate') ? $request->vehicle_licesne_plate : $rider->vehicle_license_plate,
            'status' => 1,
            'paypal_email' => $request->paypal_email,
        ]);

        if ($request->hasFile('profile')) {
            info('Uploading profile');
            $rider->update([
                'profile_picture' => pathinfo($request->profile->store('avatar', 'rider'), PATHINFO_BASENAME)
            ]);
            auth()->user()->update([
                'image' => pathinfo($request->profile->store('avatar', 'rider'), PATHINFO_BASENAME)
            ]);
        }

        return $this->success(new RiderResource($rider), 'Profile updated successfully');
    }

    /**
     * Update order delivery status
     *
     * Lets the authenticated rider accept, reject or progress an order delivery. `accept` assigns the order to the rider
     * (status 2, "In Progress"), `awaiting_pick_up` sets status 3, `on_delivery`/`on delivery` sets status 4 and `delivered`
     * sets status 5 and emails a receipt to the customer. Any other allowed value (`reject`) marks the assignment as rejected
     * and re-assigns the order to another rider. The customer and restaurant are notified (FCM push, notifications, broadcast events).
     *
     * @bodyParam order_id string required The order ID or UUID. Example: 3f6c1a2b-7d8e-4f90-a1b2-c3d4e5f6a7b8
     * @bodyParam status string required The new status. One of `accept`, `reject`, `awaiting_pick_up`, `on_delivery`, `on delivery`, `delivered`. Example: accept
     *
     * @response 200 scenario="Order updated" {
     *   "status": "Request was succesful",
     *   "message": "Order updated successfully",
     *   "data": {
     *     "id": 12,
     *     "uuid": "3f6c1a2b-7d8e-4f90-a1b2-c3d4e5f6a7b8",
     *     "user_id": 5,
     *     "restaurant_id": 2,
     *     "total_amount": 1500,
     *     "delivery": true,
     *     "delivery_address": "Moi Avenue, Nairobi",
     *     "delivery_location_lat": -1.2847473,
     *     "delivery_location_lng": 36.8219,
     *     "delivery_fee": 200,
     *     "delivery_status": "In Progress",
     *     "status": 2,
     *     "rider_id": 9,
     *     "booking_time": null,
     *     "service_charge": 50,
     *     "discount": 0,
     *     "created_at": "2024-03-01T12:00:00.000000Z",
     *     "updated_at": "2024-03-01T12:10:00.000000Z",
     *     "country": "Kenya",
     *     "user": {"id": 5, "uuid": "9b1f2c3d-4e5f-4a6b-8c7d-1e2f3a4b5c6d", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678"},
     *     "restaurant": {"id": 2, "uuid": "a1b2c3d4-0000-4f90-a1b2-c3d4e5f6a7b8", "name": "Mama's Kitchen", "address": "Kimathi Street, Nairobi"}
     *   }
     * }
     * @response 200 scenario="Already assigned" {"status": "Request was succesful", "message": "The order was already assigned", "data": "Order assigned"}
     * @response 200 scenario="Order rejected" {"status": "Request was succesful", "message": "Order updated successfully", "data": ""}
     * @response 422 scenario="Validation error" {"status": "Error has occured...", "message": {"status": ["The selected status is invalid."]}, "data": "Please select an order"}
     * @response 422 scenario="Order not found" {"status": "Error has occured...", "message": "The selected order was not found", "data": "Order not found"}
     *
     * @responseField data object The updated order with `user` and `restaurant` loaded (empty string when rejected).
     */
    public function updateOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'status' => ['required', Rule::in(['accept', 'reject', 'awaiting_pick_up', 'on_delivery', 'on delivery', 'delivered'])]
        ]);

        if ($validator->fails()) {
            return $this->error('Please select an order', $validator->messages(), 422);
        }

        $order = Order::with('user', 'restaurant')
                        ->where(function ($query) use ($request) {
                            $query->where('uuid', $request->order_id)->orWhere('id', $request->order_id);
                        })->first();

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
                'delivery_status' => 'In Progress'
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

            activity()->causedBy(auth()->user())->performedOn($order)->log('accepted order delivery');

            return $this->success($order, 'Order updated successfully', 200);
        }

        if ($request->status == 'awaiting_pick_up' || $request->status == 'awaiting pick up') {
            $order->update([
                'rider_id' => auth()->id(),
                'status' => 3,
                'delivery_status' => 'Awaiting Pick Up'
            ]);

            Http::withHeaders([
                'Authorization' => 'key='.config('services.firebase.key'),
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_id' => $order->user->device_token,
                'notification' => 'Your order is awaiting pick up by '. auth()->user()->name,
                'data' => [
                    'rider_id' => auth()->id(),
                ]
            ]);

            // $order->restaurant->notify(new OrderUpdate($order, 'accepted'));
            // event(new UpdateOrder($order->restaurant, $order, 'accepted'));

            activity()->causedBy(auth()->user())->performedOn($order)->log('awaiting pick up');

            return $this->success($order, 'Order updated successfully', 200);
        }

        if ($request->status == 'on_delivery' || $request->status == 'on delivery') {
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

            activity()->causedBy(auth()->user())->performedOn($order)->log('updated order to on delivery');

            return $this->success($order, 'Order updated successfully', 200);
        }

        if ($request->status == 'delivered') {
            $order->update([
                'status' => 5, // Delivered
                'delivery_status' => 'Delivered',
            ]);

            $order->restaurant->notify(new OrderUpdate($order, 'delivered'));
            event(new UpdateOrder($order->restaurant, $order, 'delivered'));
            if ($order->user->email) {
                SendCommunication::dispatchAfterResponse('mail', $order->user->email, 'OrderDetailsReceipt', ['order' => $order->id]);
            }

            activity()->causedBy(auth()->user())->performedOn($order)->log('delivered the order');

            return $this->success($order, 'Order updated successfully', 200);
        }


        $assignment->update([
            'status' => 'rejected'
        ]);

        // Assign Order to Different Rider Rider
        AssignOrder::assignOrder($order->id);

        $order->restaurant->notify(new OrderUpdate($order, 'rejected'));
        event(new UpdateOrder($order->restaurant, $order, 'rejected'));

        return $this->success('', 'Order updated successfully', 200);
    }

    /**
     * Update rider location
     *
     * Updates the authenticated rider's current location when not delivering. The coordinates are reverse geocoded
     * via Google Maps and the formatted address is stored on the user.
     *
     * @bodyParam latitude string required The latitude. Example: -1.2847473
     * @bodyParam longitude string required The longitude. Example: 36.8219
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": "Location updated successfully", "data": "Location update"}
     * @response 422 scenario="Validation error" {"latitude": ["The latitude field is required."], "longitude": ["The longitude field is required."]}
     */
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

    /**
     * Update delivery location
     *
     * Updates the rider's live location during delivery of an order. The location is pushed to the customer via FCM
     * (when they have a device token) and broadcast via the `OrderDeliveryLocationUpdate` event. Rate limited by the `location` throttle.
     *
     * @urlParam order_id string required The numeric ID or UUID of the order. Example: 12
     *
     * @bodyParam latitude string required The latitude of the rider. Example: -1.2847473
     * @bodyParam longitude string required The longitude of the rider. Example: 36.8219
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": "Location updated successfully", "data": "Location update"}
     * @response 422 scenario="Validation error" {"latitude": ["The latitude field is required."], "longitude": ["The longitude field is required."]}
     * @response 429 scenario="Too many requests" {"message": "Too Many Attempts."}
     */
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

    /**
     * Get rider earnings
     *
     * Returns a summary of the authenticated rider's earnings: delivery fees from delivered orders, paid tips,
     * payouts already disbursed and the pending amount.
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "total_earnings": 5300,
     *     "delivery_earnings": 4800,
     *     "tip_earnings": 500,
     *     "paid_amount": 3000,
     *     "pending_payment_amount": 1800,
     *     "earnings_in_past_week": 1200
     *   }
     * }
     *
     * @responseField data.total_earnings number Delivery earnings plus tips.
     * @responseField data.delivery_earnings number Sum of delivery fees of orders delivered by the rider.
     * @responseField data.tip_earnings number Sum of paid tips received by the rider.
     * @responseField data.paid_amount number Total amount already paid out to the rider.
     * @responseField data.pending_payment_amount number Delivery earnings not yet paid out.
     * @responseField data.earnings_in_past_week number Delivery fees of delivered orders created this week.
     */
    public function earnings()
    {
        $earnings = Order::where('rider_id', auth()->id())->where('delivery_status', 'Delivered')->sum('delivery_fee');

        $earnings_in_past_week = Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->where('delivery_status', 'Delivered')->sum('delivery_fee');

        $tips = 0;
        if (auth()->user()->rider) {
            $tips = RiderTip::where('rider_id', auth()->user()->rider->id)->where('transaction_id', '!=', NULL)->sum('amount');
        }

        // Add disbursed amount
        $paid_amount = Payout::with('payable')
                            ->where('payable_type', User::class)
                            ->where('payable_id', auth()->id())
                            ->sum('amount');

        $pending_payment = $earnings - $paid_amount;

        return $this->success([
            'total_earnings' => $earnings + $tips,
            'delivery_earnings' => $earnings,
            'tip_earnings' => $tips,
            'paid_amount' => $paid_amount,
            'pending_payment_amount' => $pending_payment,
            'earnings_in_past_week' => $earnings_in_past_week
        ]);
    }

    public function withdraw(Request $request)
    {}

    /**
     * Review a rider
     *
     * Stores a rating and optional review from the authenticated user for a rider.
     *
     * @group Reviews
     *
     * @bodyParam rider_id integer required The user ID of the rider (users.id of a user with the `rider` role). Example: 9
     * @bodyParam rating integer required The rating, from 1 to 5. Example: 5
     * @bodyParam review string A written review of the rider. Example: Fast and friendly delivery.
     *
     * @response 200 scenario="Success" {"status": "Request was succesful", "message": null, "data": "Review created successfully"}
     * @response 400 scenario="Validation error" {"status": "Error has occured...", "message": {"rating": ["The rating field is required."]}, "data": ""}
     * @response 404 scenario="Rider not found" {"status": "Error has occured...", "message": "No such rider", "data": ""}
     */
    public function storeReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rider_id' => ['required'],
            'rating' => ['required', 'integer', 'max:5', 'min:1'],
            'review' => ['nullable', 'sometimes', 'string']
        ]);

        if ($validator->fails()) {
            return $this->error('', $validator->messages(), 400);
        }

        $user = User::where('id', $request->rider_id)
                        ->whereHas('roles', function ($query) {
                            $query->where('name', 'rider');
                        })
                        ->first();

        $rider = Rider::where('user_id', $user->id)->first();

        if (!$rider) {
            return $this->error('', 'No such rider', 404);
        }

        $rider->reviews()->create([
            'user_id' => auth()->id(),
            'review' => $request->has('review') && !empty($request->review) ? $request->review : NULL,
            'rating' => $request->rating
        ]);

        return $this->success('Review created successfully');
    }

    /**
     * Get rider tips
     *
     * Returns the tips paid to the authenticated rider, split into withdrawn and paid (not yet withdrawn) tips.
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "total_withdrawn": 300,
     *     "total_paid": 200,
     *     "withdrawn": [
     *       {"id": 1, "uuid": "e1f2a3b4-5c6d-4e7f-8a9b-0c1d2e3f4a5b", "rider_id": 4, "order_id": 10, "amount": 300, "status": "withdrawn", "transaction_id": "pi_3Nxyz123", "created_at": "2024-02-11T10:00:00.000000Z", "updated_at": "2024-02-20T10:00:00.000000Z"}
     *     ],
     *     "paid": [
     *       {"id": 2, "uuid": "f2a3b4c5-6d7e-4f8a-9b0c-1d2e3f4a5b6c", "rider_id": 4, "order_id": 12, "amount": 200, "status": "paid", "transaction_id": "pi_3Nabc456", "created_at": "2024-03-01T12:30:00.000000Z", "updated_at": "2024-03-01T12:30:00.000000Z"}
     *     ]
     *   }
     * }
     * @response 400 scenario="No rider profile" {"status": "Error has occured...", "message": "Complete your rider profile to get tips", "data": ""}
     *
     * @responseField data.total_withdrawn number Total amount of tips that have been withdrawn.
     * @responseField data.total_paid number Total amount of tips paid but not yet withdrawn.
     * @responseField data.withdrawn object[] Tips that have been withdrawn.
     * @responseField data.paid object[] Tips paid but not yet withdrawn.
     */
    public function getTips()
    {
        $rider = Rider::where('user_id', auth()->id())->first();

        if (!$rider) {
            return $this->error('', 'Complete your rider profile to get tips', 400);
        }

        $withdrawn = RiderTip::where('rider_id', $rider->id)->where('status', 'withdrawn')->get();
        $paid = RiderTip::where('rider_id', $rider->id)->where('status', 'paid')->get();

        return $this->success([
            'total_withdrawn' => $withdrawn->sum('amount'),
            'total_paid' => $paid->sum('amount'),
            'withdrawn' => $withdrawn,
            'paid' => $paid
        ]);
    }
}

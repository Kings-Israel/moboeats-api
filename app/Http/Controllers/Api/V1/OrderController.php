<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\NewOrder;
use App\Events\UpdateOrder;
use App\Filters\V1\OrderFilter;
use App\Helpers\AssignOrder;
use App\Models\Order;
use App\Http\Requests\V1\StoreOrderRequest;
use App\Http\Requests\V1\UpdateOrderRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderCollection;
use App\Http\Resources\V1\OrderResource;
use App\Jobs\SendNotification;
use App\Models\AssignedOrder;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CategoryMenu;
use App\Models\FoodCommonCategory;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\UserRestaurant;
use App\Notifications\NewOrder as NotificationsNewOrder;
// use App\Notifications\NewOrder;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\PromoCode;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrderExport;
use App\Models\Discount;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\Rider;
use App\Models\Menu;
use App\Models\OrderTable;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @group Customer Order Management
 *
 * Customer Order API resource
 */
class OrderController extends Controller
{
    use HttpResponses;

    public function index(Request $request)
    {
        $filter =  new OrderFilter();
        $filterItems = $filter->transform($request);
        $user = User::where('id',Auth::user()->id)->first();

        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                $orders = Order::where('user_id', Auth::user()->id)
                                ->where($filterItems)
                                ->with(['orderItems.menu.images', 'restaurant', 'rider', 'reservation'])
                                ->orderBy('created_at', 'DESC')
                                ->paginate(10);

                return new OrderCollection($orders);
            } elseif ($role === 'restaurant') {
                $search = $request->query('search');
                $from_created_at = $request->query('from_created_at');
                $to_created_at = $request->query('to_created_at');
                $delivery = $request->query('delivery');
                $from_booking_date = $request->query('from_booking_date');
                $to_booking_date = $request->query('to_booking_date');
                $orders = Order::whereIn('restaurant_id', $user->restaurants->pluck('id'))
                                ->where($filterItems)
                                ->when($from_created_at && $from_created_at != '', function ($query) use ($from_created_at) {
                                    $query->whereDate('created_at', '>=', Carbon::parse($from_created_at));
                                })
                                ->when($to_created_at && $to_created_at != '', function ($query) use ($to_created_at) {
                                    $query->whereDate('created_at', '<=', Carbon::parse($to_created_at));
                                })
                                ->when($delivery && $delivery != '', function ($query) use ($delivery) {
                                    info($delivery);
                                    $query->where('delivery', $delivery);
                                })
                                ->when($from_booking_date && $from_booking_date != '', function ($query) use ($from_booking_date) {
                                    $query->whereDate('booking_time', '>=', Carbon::parse($from_booking_date));
                                })
                                ->when($to_booking_date && $to_booking_date != '', function ($query) use ($to_booking_date) {
                                    $query->whereDate('booking_time', '<=', Carbon::parse($to_booking_date));
                                })
                                ->when($search && $search != '', function ($query) use ($search) {
                                    $query->where(function ($query) use ($search) {
                                        $query->orWhere('uuid', 'LIKE', '%' . strtolower($search) . '%')
                                                ->orWhereHas('user', function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%' . $search . '%');
                                                })
                                                ->orWhereHas('restaurant', function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('name_short', 'LIKE', '%' . $search . '%');
                                                });
                                    });
                                })
                                ->with(['orderItems.menu.images', 'restaurant', 'rider', 'user', 'reservation'])
                                ->orderBy('created_at', 'DESC')
                                ->paginate(10);

                return new OrderCollection($orders);
            } elseif ($role === 'restaurant employee') {
                $search = $request->query('search');
                $from_created_at = $request->query('from_created_at');
                $to_created_at = $request->query('to_created_at');
                $user_restaurant = UserRestaurant::where('user_id', $user->id)->first();
                $restaurant = Restaurant::where('id', $user_restaurant->restaurant_id)->first();
                $delivery = $request->query('delivery');
                $orders = Order::where('restaurant_id', $restaurant->id)
                                ->where($filterItems)
                                ->when($from_created_at && $from_created_at != '', function ($query) use ($from_created_at) {
                                    $query->whereDate('created_at', '>=', Carbon::parse($from_created_at));
                                })
                                ->when($to_created_at && $to_created_at != '', function ($query) use ($to_created_at) {
                                    $query->whereDate('created_at', '<=', Carbon::parse($to_created_at));
                                })
                                ->when($delivery && $delivery != '', function ($query) use ($delivery) {
                                    $query->where('delivery', $delivery);
                                })
                                ->when($search && $search != '', function ($query) use ($search) {
                                    $query->where(function ($query) use ($search) {
                                        $query->orWhere('uuid', 'LIKE', '%' . strtolower($search) . '%')
                                                ->orWhereHas('user', function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%' . $search . '%');
                                                });
                                    });
                                })
                                ->with(['orderItems.menu.images', 'restaurant', 'rider', 'user', 'reservation'])
                                ->orderBy('created_at', 'DESC')
                                ->paginate(10);

                return new OrderCollection($orders);
            } else {
                $orders = Order::where($filterItems);
                return new OrderCollection($orders->with(['orderItems.menu.images', 'restaurant', 'rider']));
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }

    public function export(Request $request)
    {
        $search = $request->query('search');
        $from_created_at = $request->query('from_created_at');
        $to_created_at = $request->query('to_created_at');

        $unique = explode('-', Str::uuid())[0];

        Excel::store(new OrderExport($search, $from_created_at, $to_created_at), 'orders'.$unique.'.xlsx', 'exports');

        return Storage::disk('exports')->download('orders'.$unique.'.xlsx');
    }

    public function store(StoreOrderRequest $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }

            try {
                DB::beginTransaction();
                $cart = Cart::where('user_id', $user->id)
                            ->where('id', $request->cartId)
                            ->first();

                if (!$cart) {
                    // Order is a dine in/reservation
                    $validator = Validator::make($request->all(), [
                        'seating_area_id' => ['required'],
                        'seat_number' => ['required'],
                        'booking_time' => ['required', 'date_format:Y-m-d H:i']
                    ], [
                        'seating_area_id.required' => 'Select sitting area',
                        'seat_number.required' => 'Select number of seats',
                        'booking_time.required' => 'Select date',
                        'booking_time.date_format' => 'The date format should be Y-m-d H:i',
                    ]);

                    if ($validator->fails()) {
                        return $this->error('An error occured while creating the reservation.', $validator->messages(), 403);
                    }

                    $restaurant_tables = RestaurantTable::where('restaurant_id', $request->restaurant_id)->first();

                    if (!$restaurant_tables) {
                        return $this->error('', 'This restaurant does not offer dine in services', 403);
                    }

                    //create Order object
                    $order = Order::create([
                        'user_id' => $user->id,
                        'restaurant_id' => $request->restaurant_id,
                        'delivery' => 0,
                        'total_amount' => 0,
                        'created_by' => $user->name,
                        'booking_time' => $request->booking_time
                    ]);

                    // Create reservation
                    Reservation::create([
                        'order_id' => $order->id,
                        'seat_number' => $request->seat_number,
                        'reservation_date' => $request->booking_time,
                    ]);

                    activity()->causedBy(auth()->user())->performedOn($order)->log('made a new reservation in restaurant'. $order->restaurant->name);

                    DB::commit();

                    return new OrderResource($order->loadMissing('reservation'));
                }

                $cartItems = CartItem::where('cart_id', $cart->id)->get();

                $restaurant = Restaurant::find($request->restaurant_id);

                $discount = 0;

                $promo_code = NULL;

                if ($request->has('promo_code') && $request->promo_code != '' && $request->promo_code != null && $request->promo_code != 'null') {
                    $promo_code = PromoCode::active()->where('code', $request->promo_code)->where('restaurant_id', $request->restaurant_id)->first();

                    if (!$promo_code) {
                        return $this->error('Promo Code', 'The Promo Code is not valid or has expired', 400);
                    }
                }

                $delivery_fee = 0;

                if ($request->delivery) {
                    $customer_restaurant_distance = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$request->delivery_location_lat.','.$request->delivery_location_lng.'&destinations='.$restaurant->latitude.','.$restaurant->longitude.'&key='.config('services.map.key'));

                    if (json_decode($customer_restaurant_distance)->rows[0]->elements[0]->status === 'NOT_FOUND') {
                       return response()->json(['message' => 'Please provide a valid location(longitude and latitude)'], 422);
                    }
                    if (json_decode($customer_restaurant_distance)->rows[0]->elements[0]->status === 'ZERO_RESULTS') {
                       return response()->json(['message' => 'Please provide a valid location(longitude and latitude)'], 422);
                    }

                    $distance = json_decode($customer_restaurant_distance)->rows[0]->elements[0]->distance->text;

                    $distance = explode(' ', $distance)[0];

                    $delivery_fee = (double) (((double) $distance * (double) config('services.kms_to_miles')) * (double) config('services.delivery_rate'));
                }

                //create Order object
                $order = Order::create([
                    'user_id' => $user->id,
                    'restaurant_id' => $request->restaurant_id,
                    'delivery' => ($request->delivery) ? 1 : 0,
                    'total_amount' => 0,
                    'created_by' => $user->name,
                ]);

                $items_are_groceries = true;

                // cart items will translate to order items
                foreach ($cartItems as $item) {
                    $standardMenuPrice = $item->menu->menuPrices->where('status', '2')->first();
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_id' => $item->menu_id,
                        'quantity' => $item->quantity,
                        'subtotal' => ($standardMenuPrice->price * $item->quantity),
                        'created_by' => $user->name,
                    ]);

                    $menu_discount = Discount::where('menu_id', $item->menu_id)->first();

                    if ($menu_discount) {
                        if ($menu_discount->type == 'amount') {
                            for ($i=1; $i <= $item->quantity; $i++) {
                                $discount += $menu_discount->value;
                            }
                        } else {
                            for ($i=1; $i <= $item->quantity; $i++) {
                                $discount += ($menu_discount->value / 100) * $standardMenuPrice->price;
                            }
                        }
                    }

                    $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

                    $item_is_grocery = CategoryMenu::where('category_id', $category->id)->where('menu_id', $item->menu_id)->first();

                    if (!$item_is_grocery) {
                        $items_are_groceries = false;
                    }
                }

                // update total amount in Order
                $totalSubtotal = $order->orderItems->sum('subtotal');

                // Get Promo Code discount before adding service charge
                if ($request->has('promo_code') && $request->promo_code != '' && $request->promo_code != null && $request->promo_code != 'null') {
                    if ($promo_code->type == 'amount') {
                        $totalSubtotal = $totalSubtotal - $promo_code->value;
                        $discount += $promo_code->value;
                    } else {
                        $discount += ($promo_code->value / 100) * $totalSubtotal;
                        $totalSubtotal = $totalSubtotal - $discount;
                    }
                }

                $order->update([
                    'discount' => $discount
                ]);

                if ($items_are_groceries) {
                    // Service Charge
                    $service_charge = $restaurant->groceries_service_charge_agreement ? ((int) $restaurant->groceries_service_charge_agreement / 100) * $totalSubtotal : ((int) config('services.default_service_charge') / 100) * $totalSubtotal;
                } else {
                    // Service Charge
                    $service_charge = $restaurant->service_charge_agreement ? ((int) $restaurant->service_charge_agreement / 100) * $totalSubtotal : ((int) config('services.default_service_charge') / 100) * $totalSubtotal;
                }

                // add delivery fee if customer needs
                if ($request->delivery == true) {
                    $totalSubtotal = $totalSubtotal + $delivery_fee;
                    $totalSubtotal = $totalSubtotal <= 0 ? 0 : $totalSubtotal;
                    $order->update([
                        'total_amount' => $totalSubtotal,
                        'delivery_fee' => $delivery_fee,
                        'delivery_address' => $request->delivery_address,
                        'delivery_location_lat' => $request->delivery_location_lat,
                        'delivery_location_lng' => $request->delivery_location_lng,
                        'service_charge' => $service_charge,
                    ]);
                } else {
                    $order->update([
                        'total_amount' => $totalSubtotal,
                        'service_charge' => $service_charge,
                        'booking_time' => $request->has('booking_time') && $request->booking_time != null && $request->booking_time != '' ? $request->booking_time : NULL
                    ]);
                }

                $cart->delete();

                DB::commit();

                // $order->restaurant->notify(new NotificationsNewOrder($order->load('user')));
                // event(new NewOrder($restaurant, $order->load('user')));

                activity()->causedBy(auth()->user())->performedOn($order)->log('made a new order in restaurant'. $order->restaurant->name);

                return new OrderResource($order->loadMissing(['user', 'restaurant', 'orderItems.menu.images']));
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }

    public function show(Order $order)
    {
        $order = $order->load('restaurant', 'rider', 'orderItems.menu', 'reservation', 'orderTables.restaurantTable');
        $order->preparation_time = $order->getTotalPreparationTime();
        $user = $order->user;
        $restaurant = $order->restaurant;
        $riders = [];
        $restaurant_tables = [];

        if ((auth()->user()->hasRole('restaurant') || auth()->user()->hasRole('restaurant employee'))) {
            if (auth()->user()->hasRole('restaurant')) {
                $restaurant_ids = auth()->user()->restaurants->pluck('id');
            } else {
                $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
                $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
            }

            if ($order->delivery) {
                $latitude = $order->delivery_location_lat ? $order->delivery_location_lat : $order->user->latitude;
                $longitude = $order->delivery_location_lng ? $order->delivery_location_lng : $order->user->longitude;
                $riders = User::whereHas('roles', function($query) {
                                    $query->where('name', 'rider');
                                })
                                ->where(function($query) use ($restaurant, $order, $user, $latitude, $longitude) {
                                    $orders = Order::where('rider_id', '!=', NULL)->get()->pluck('rider_id');
                                    $delivered_orders = Order::where('rider_id', '!=', NULL)->where('status', 5)->get()->pluck('rider_id');

                                    // Get riders who have been assigned delivery to the restaurant
                                    // and are going close to another order from the same restaurant
                                    $deliveries = DB::table("orders")
                                                    ->where('rider_id', '!=', NULL)
                                                    ->where('restaurant_id', $order->restaurant_id)
                                                    ->whereIn('status', [1, 2, 3])
                                                    ->select("*",
                                                        DB::raw("6371 * acos(cos(radians(".$latitude."))
                                                        * cos(radians(".$latitude."))
                                                        * cos(radians(".$longitude.")
                                                        - radians(".$longitude."))
                                                        + sin(radians(".$latitude."))
                                                        * sin(radians(".$latitude."))) AS distance"))
                                                    ->get();

                                    // // Filter to riders distances less than 5 Kms
                                    // $nearby_deliveries = $deliveries->filter(function($delivery) {
                                    //     return (int) $delivery->distance <= 25;
                                    // })->pluck('rider_id')->values()->all();

                                    // $rejected_orders = AssignedOrder::where('order_id', $order->id)->where('status', 'rejected')->get()->pluck('user_id');

                                    // $query->whereNotIn('id', $rejected_orders)
                                    //         ->where(function($query) use ($nearby_deliveries, $orders, $delivered_orders) {
                                    //             $query->whereIn('id', $nearby_deliveries)
                                    //                 ->orWhereIn('id', $delivered_orders)
                                    //                 ->orWhereNotIn('id', $orders);
                                    //         });

                                })
                                ->get()
                                ->each(function($rider, $key) use ($restaurant) {
                                    if ($rider->latitude != NULL && $rider->longitude != NULL && $restaurant->latitude != NULL && $restaurant->longitude != NULL) {
                                        $business_location = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$rider->latitude.','.$rider->longitude.'&destinations='.$restaurant->latitude.','.$restaurant->longitude.'&key='.config('services.map.key'));
                                        if (json_decode($business_location)->rows[0]->elements[0]->status != "NOT_FOUND" && json_decode($business_location)->rows[0]->elements[0]->status != "ZERO_RESULTS") {
                                            $distance = json_decode($business_location)->rows[0]->elements[0]->distance->text;
                                            $time = json_decode($business_location)->rows[0]->elements[0]->duration->text;
                                            $rider['distance'] = $distance;
                                            $rider['time_away'] = $time;
                                        } else {
                                            $rider['distance'] = NULL;
                                            $rider['time_away'] = NULL;
                                        }
                                    } else {
                                       $rider['distance'] = NULL;
                                       $rider['time_away'] = NULL;
                                    }
                                    })->sortBy([
                                        fn($a, $b) => (double) explode(' ', $a['distance'])[0] <= (double) explode(' ',$b['distance'])[0],
                                    ]);
            }

            $restaurant_tables = RestaurantTable::whereIn('restaurant_id', $restaurant_ids)->get();

            return request()->wantsJson() ?
                $this->success([
                    'order' => $order,
                    'riders' => $riders,
                    'restaurant_tables' => $restaurant_tables
                ], '', 200) : '';
        } else {
            return request()->wantsJson() ?
                    $this->success([
                        'order' => $order,
                    ], '', 200) : '';
        }
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $order->update([
            'status' => $request->status
        ]);

        if ($request->status === 'in_progress') {
            Http::withHeaders([
                'Authorization' => 'key='.config('services.firebase.key'),
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_id' => $order->user->device_token,
                'notification' => 'Your order has started being prepared',
            ]);
        }

        if ($request->status === 'denied') {
            Http::withHeaders([
                'Authorization' => 'key='.config('services.firebase.key'),
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_id' => $order->user->device_token,
                'notification' => 'Your order has been rejected by the restaurant.',
            ]);
        }

        return $this->success($order, 'Order status updated successfully');
    }

    public function updateReservedOrder(Request $request, Order $order)
    {
        $request->validate([
            'status' => ['required', 'in:completed,in progress,no show,cancelled']
        ]);

        if($request->status == 'completed') {
            $order->update([
                'status' => '5'
            ]);

            if ($order->reservation()->exists()) {
                $order->reservation()->update([
                    'status' => 'completed'
                ]);
            }

            Http::withHeaders([
                'Authorization' => 'key='.config('services.firebase.key'),
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_id' => $order->user->device_token,
                'notification' => 'Thank you for dining with us.',
            ]);
        }

        if ($order->reservation()->exists()) {
            $order->reservation()->update([
                'status' => $request->status
            ]);
        }

        activity()->causedBy(auth()->user())->performedOn($order)->log('updated the reservation to'. $request->status);

        return $this->success('Reservation updated successfully');
    }

    public function assignReservationToTables(Request $request, Order $order)
    {
        if (Carbon::parse($order->booking_time)->lessThan(now())) {
            return $this->error('', 'This reserved time for this order has already passed', 403);
        }

        $order->update([
            'status' => 2
        ]);

        foreach(explode(',', $request->restaurant_table_ids) as $table_id) {
            OrderTable::create(['restaurant_table_id' => $table_id, 'order_id' => $order->id]);
        }

        // Notify user
        Http::withHeaders([
            'Authorization' => 'key='.config('services.firebase.key'),
            'Content-Type' => 'application/json'
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'registration_id' => $order->user->device_token,
            'notification' => 'Your reservation has started being prepared for. Looking forward to serving you.',
        ]);

        return $this->success('Table(s) assigned to order');
    }

    public function destroy(Order $order)
    {
        //
    }

    public function assignOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
            'rider_id' => 'required',
        ]);

        $order = Order::with('user', 'restaurant')->where('uuid', $request->order_id)->first();

        if (!$order) {
            return $this->error('', 'Order not found. Please use the uuid of the order.', 404);
        }

        $rider = User::find($request->rider_id);

        $pickup_address = NULL;
        if ($order->restaurant->latitude && $order->restaurant->longitude) {
            $pickup_address = [$order->restaurant->latitude, $order->restaurant->longitude];
        }

        $delivery_address = NULL;
        if ($order->delivery_location_lat && $order->delivery_location_lng) {
            $delivery_address = [$order->delivery_location_lat, $order->delivery_location_lng];
        }

        AssignedOrder::firstOrCreate([
            'order_id' => $order->id,
            'user_id' => $rider->id
        ]);

        info(['pickup_address' => $pickup_address, 'delivery_address' => $delivery_address, 'order_code' => $order->id, 'order_details' => $order]);
        SendNotification::dispatchAfterResponse($rider, 'You have been assigned to deliver an order', ['pickup_address' => $pickup_address, 'delivery_address' => $delivery_address, 'order_code' => $order->id, 'order_details' => $order]);

        return $this->success('', 'Delivery request sent successfully', 200);
    }

    public function pendingOrders()
    {
        $orders = 0;
        if (auth()->user()->hasRole('restaurant')) {
            $orders = Order::whereIn('restaurant_id', auth()->user()->restaurants->pluck('id'))
                            ->where('delivery', true)
                            ->where('status', 1)
                            ->count();
        } elseif (auth()->user()->hasRole('restaurant employee')) {
            $user_restaurant = UserRestaurant::where('user_id', auth()->user()->id)->first();
            $restaurant = Restaurant::where('id', $user_restaurant->restaurant_id)->first();
            $orders = Order::where('restaurant_id', $restaurant->id)
                            ->where('delivery', true)
                            ->where('status', 1)
                            ->count();
        }

        return $orders;
    }

    public function pendingDineins()
    {
        $orders = 0;
        if (auth()->user()->hasRole('restaurant')) {
            $orders = Order::whereIn('restaurant_id', auth()->user()->restaurants->pluck('id'))
                            ->where('delivery', false)
                            ->where('status', 1)
                            ->count();
        } elseif (auth()->user()->hasRole('restaurant employee')) {
            $user_restaurant = UserRestaurant::where('user_id', auth()->user()->id)->first();
            $restaurant = Restaurant::where('id', $user_restaurant->restaurant_id)->first();
            $orders = Order::where('restaurant_id', $restaurant->id)
                            ->where('delivery', false)
                            ->where('status', 1)
                            ->count();
        }

        return $orders;
    }

    /**
     * Store review for an order
     * @bodyParam order_id integer The id of the order
     * @bodyParam restaurant_rating integer The rating of the restaurant from 1 - 5
     * @bodyParam rider_rating integer The rating of the rider from 1 - 5
     * @bodyParam restaurant_review string A review of the restaurant
     * @bodyParam rider_review string A review of the rider
     */
    public function storeReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => ['required'],
            'restaurant_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'restaurant_review' => ['nullable', 'sometimes', 'string'],
            'rider_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'rider_review' => ['nullable', 'sometimes', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error('', $validator->messages(), 400);
        }

        if($request->has('order_id') && !empty($request->order_id)) {
            $order = Order::find($request->order_id);

            if (!$order) {
                return $this->error('', 'Order not found', 404);
            }

            $restaurant = Restaurant::find($order->restaurant_id);

            if ($restaurant) {
                $restaurant->reviews()->create([
                    'user_id' => auth()->id(),
                    'order_id' => $request->has('order_id') && !empty($request->order_id) ? $request->order_id : NULL,
                    'rating' => $request->restaurant_rating,
                    'review' => $request->restaurant_review
                ]);
            }

            $user = User::find($order->rider_id);

            if ($user) {
                $rider = Rider::where('user_id', $user->id)->first();

                if ($rider) {
                    $rider->reviews()->create([
                        'user_id' => auth()->id(),
                        'order_id' => $request->has('order_id') && !empty($request->order_id) ? $request->order_id : NULL,
                        'rating' => $request->rider_rating,
                        'review' => $request->rider_review
                    ]);
                }
            }
        }

        return $this->success('Review', 'Review successfully saved');
    }
}

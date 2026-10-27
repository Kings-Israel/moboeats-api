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
use App\Models\Orphanage;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @group Orders
 *
 * Customer and restaurant order endpoints: placing orders and dine-in reservations, listing and viewing orders,
 * updating order/reservation status, assigning tables and riders, and exporting orders.
 */
class OrderController extends Controller
{
    use HttpResponses;

    /**
     * List orders
     *
     * Returns the authenticated user's orders, newest first, paginated 10 per page. Customers (`orderer`) see their own
     * orders; users with role `restaurant` see orders of all their restaurants; `restaurant employee` users see orders
     * of the restaurant they work for. The search/date/delivery filters apply only to restaurant owners and employees
     * (booking date filters only to restaurant owners).
     *
     * @queryParam uuid[eq] string Filter by exact order uuid. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam status[eq] string Filter by exact order status. Example: 2
     * @queryParam userId[eq] integer Filter by user id. Example: 12
     * @queryParam restaurantId[eq] integer Filter by restaurant id. Example: 3
     * @queryParam search string Restaurant users only. Search by order uuid, customer name or (owners only) restaurant name. Example: Jane
     * @queryParam from_created_at string Restaurant users only. Orders created on or after this date. Example: 2024-01-01
     * @queryParam to_created_at string Restaurant users only. Orders created on or before this date. Example: 2024-01-31
     * @queryParam delivery string Restaurant users only. Filter by delivery flag (`1` delivery, `0` dine-in). Example: 1
     * @queryParam from_booking_date string Restaurant owners only. Orders with booking time on or after this date. Example: 2024-01-01
     * @queryParam to_booking_date string Restaurant owners only. Orders with booking time on or before this date. Example: 2024-01-31
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"data":[{"id":10,"uuid":"9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d","attributes":{"userId":12,"totalAmount":"25.50","delivery":true,"deliveryFee":"3.20","deliveryAddress":"221B Baker Street, London","deliveryStatus":null,"deliveryLat":51.5237,"deliveryLng":-0.1585,"createdAt":"Mon, Jan 15, 2024 10:00 AM","status":"1","paymentStatus":"Pending","service_charge":"1.20","booking_time":null,"discount":"0","preparation_time":25,"restaurant_name":"Mama's Kitchen","user_name":"Jane Doe"},"relationships":{"user":{"id":12,"uuid":"0f8fad5b-d9cb-469f-a165-70867728950e","name":"Jane Doe","email":"jane@example.com","phone_number":"+447700900123","role":"orderer"},"restaurant":{"id":3,"uuid":"6fa459ea-ee8a-3ca4-894e-db77e160355e","attributes":{"name":"Mama's Kitchen"}},"orderItems":[{"id":1,"uuid":"1b4e28ba-2fa1-11d2-883f-0016d3cca427","attributes":{"orderId":10,"menuId":4,"quantity":2,"subtotal":"20.00","status":"1"},"relationships":{"menu":{"id":4,"title":"Chicken Burger"}}}],"orphanage":null}}],"links":{"first":"https://api.moboeats.com/api/v1/orderer/orders?page=1","last":"https://api.moboeats.com/api/v1/orderer/orders?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://api.moboeats.com/api/v1/orderer/orders?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"https://api.moboeats.com/api/v1/orderer/orders","per_page":10,"to":1,"total":1}}
     * @response 401 scenario="Unauthorized" {"status":"Error has occured...","message":"Unauthorized","data":""}
     *
     * @responseField data object[] The orders.
     * @responseField data[].uuid string Order uuid.
     * @responseField data[].attributes.totalAmount string Order total.
     * @responseField data[].attributes.paymentStatus string `Paid` or `Pending`.
     * @responseField meta object Pagination metadata.
     */
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

    /**
     * Export orders to Excel
     *
     * Restaurant endpoint that generates and downloads an `.xlsx` export of orders, optionally filtered.
     *
     * @queryParam search string Search term passed to the export. Example: Jane
     * @queryParam from_created_at string Orders created on or after this date. Example: 2024-01-01
     * @queryParam to_created_at string Orders created on or before this date. Example: 2024-01-31
     *
     * @response 200 scenario="File download" <<binary>> The exported Excel file.
     */
    public function export(Request $request)
    {
        $search = $request->query('search');
        $from_created_at = $request->query('from_created_at');
        $to_created_at = $request->query('to_created_at');

        $unique = explode('-', Str::uuid())[0];

        Excel::store(new OrderExport($search, $from_created_at, $to_created_at), 'orders'.$unique.'.xlsx', 'exports');

        return Storage::disk('exports')->download('orders'.$unique.'.xlsx');
    }

    /**
     * Place an order
     *
     * Only users with role `orderer` may call this. If `cartId` matches one of the user's carts, the cart items are turned
     * into order items, menu discounts and an optional promo code are applied, a service charge is added, and a delivery fee
     * is computed for deliveries (from the delivery coordinates) or orphanage deliveries (via Google Distance Matrix); the
     * cart is then deleted. If no cart matches, a dine-in reservation order is created instead (requires `seating_area_id`,
     * `seat_number` and `booking_time`).
     *
     * @bodyParam cartId integer The id of the user's cart to check out. Omit for a dine-in reservation. Example: 5
     * @bodyParam restaurantId integer required The id of the restaurant. Example: 3
     * @bodyParam delivery boolean Whether the order is delivered. Required unless `orphanage_id` is given. Example: true
     * @bodyParam delivery_address string Delivery address. Required if `delivery` is true. Example: 221B Baker Street, London
     * @bodyParam delivery_location_lat number Delivery latitude. Required if `delivery` is true. Example: 51.5237
     * @bodyParam delivery_location_lng number Delivery longitude. Required if `delivery` is true. Example: -0.1585
     * @bodyParam booking_time string Dining/booking time in `Y-m-d H:i`, must be in the future. Required if `delivery` is false, and for reservations. Example: 2030-01-15 19:30
     * @bodyParam promo_code string A promo code for the restaurant. Example: SAVE10
     * @bodyParam orphanage_id integer The id of an orphanage to deliver the order to. Example: 2
     * @bodyParam seating_area_id integer Seating area id. Required for dine-in reservations (no cart). Example: 1
     * @bodyParam seat_number integer Number of seats. Required for dine-in reservations (no cart). Example: 4
     *
     * @response 201 scenario="Order created" {"data":{"id":10,"uuid":"9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d","attributes":{"userId":12,"totalAmount":"25.50","delivery":true,"deliveryFee":"3.20","deliveryAddress":"221B Baker Street, London","deliveryStatus":null,"deliveryLat":51.5237,"deliveryLng":-0.1585,"createdAt":"Mon, Jan 15, 2024 10:00 AM","status":"1","paymentStatus":"Pending","service_charge":"1.20","booking_time":null,"discount":"0","preparation_time":25,"restaurant_name":"Mama's Kitchen","user_name":"Jane Doe"},"relationships":{"user":{"id":12,"uuid":"0f8fad5b-d9cb-469f-a165-70867728950e","name":"Jane Doe","email":"jane@example.com","phone_number":"+447700900123","role":"orderer"},"restaurant":{"id":3,"uuid":"6fa459ea-ee8a-3ca4-894e-db77e160355e","attributes":{"name":"Mama's Kitchen"}},"orderItems":[{"id":1,"uuid":"1b4e28ba-2fa1-11d2-883f-0016d3cca427","attributes":{"orderId":10,"menuId":4,"quantity":2,"subtotal":"20.00","status":"1"},"relationships":{"menu":{"id":4,"title":"Chicken Burger"}}}],"orphanage":null}}}
     * @response 400 scenario="Invalid promo code" {"status":"Error has occured...","message":"The Promo Code is not valid or has expired","data":"Promo Code"}
     * @response 401 scenario="Not an orderer" {"status":"Error has occured...","message":"Unauthorized","data":""}
     * @response 403 scenario="Reservation validation error" {"status":"Error has occured...","message":{"seat_number":["Select number of seats"]},"data":"An error occured while creating the reservation."}
     * @response 403 scenario="No dine-in at restaurant" {"status":"Error has occured...","message":"This restaurant does not offer dine in services","data":""}
     * @response 403 scenario="Unexpected error" {"status":"Error has occured...","message":"Server error message","data":""}
     * @response 422 scenario="Validation error" {"message":"The restaurant id field is required.","errors":{"restaurantId":["The restaurant id field is required."]}}
     * @response 422 scenario="Invalid orphanage location" {"message":"Please provide a valid location(longitude and latitude)"}
     *
     * @responseField data.uuid string The new order's uuid (used for payment).
     * @responseField data.attributes.totalAmount string Order total including delivery fee and discounts.
     * @responseField data.attributes.service_charge string Service charge.
     * @responseField data.relationships.reservation object Reservation details (dine-in reservations only).
     */
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
                        'booking_time' => $request->booking_time,
                        'orphanage_id' => $request->orphanage_id,
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

                // If Delivery is for orphanage, find the orphanage
                $orphanage = NULL;
                if ($request->has('orphanage_id') && !empty($request->orphanage_id)) {
                    $orphanage = Orphanage::find($request->orphanage_id);
                }

                $delivery_fee = 0;

                if ($request->delivery) {
                    // $customer_restaurant_distance = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$request->delivery_location_lat.','.$request->delivery_location_lng.'&destinations='.$restaurant->latitude.','.$restaurant->longitude.'&key='.config('services.map.key'));

                    // if (json_decode($customer_restaurant_distance)->rows[0]->elements[0]->status === 'NOT_FOUND') {
                    //    return response()->json(['message' => 'Please provide a valid location(longitude and latitude)'], 422);
                    // }
                    // if (json_decode($customer_restaurant_distance)->rows[0]->elements[0]->status === 'ZERO_RESULTS') {
                    //    return response()->json(['message' => 'Please provide a valid location(longitude and latitude)'], 422);
                    // }

                    // $distance = json_decode($customer_restaurant_distance)->rows[0]->elements[0]->distance->text;

                    // $distance = explode(' ', $distance)[0];
                    $distance = $this->point2point_distance($request->delivery_location_lat, $request->delivery_location_lng, $restaurant->latitude, $restaurant->longitude, 'K');

                    $delivery_fee = (double) (((double) $distance * (double) config('services.kms_to_miles')) * (double) Setting::where('name', 'Delivery Rate')->first()->variable);
                }

                if ($orphanage) {
                    $customer_restaurant_distance = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$orphanage->location_lat.','.$orphanage->location_long.'&destinations='.$restaurant->latitude.','.$restaurant->longitude.'&key='.config('services.map.key'));

                    if (json_decode($customer_restaurant_distance)->rows[0]->elements[0]->status === 'NOT_FOUND') {
                        return response()->json(['message' => 'Please provide a valid location(longitude and latitude)'], 422);
                    }
                    if (json_decode($customer_restaurant_distance)->rows[0]->elements[0]->status === 'ZERO_RESULTS') {
                        return response()->json(['message' => 'Please provide a valid location(longitude and latitude)'], 422);
                    }

                    $distance = json_decode($customer_restaurant_distance)->rows[0]->elements[0]->distance->text;

                    $distance = explode(' ', $distance)[0];

                    $delivery_fee = (double) (((double) $distance * (double) config('services.kms_to_miles')) * (double) Setting::where('name', 'Delivery Rate')->first()->variable);
                }

                //create Order object
                $order = Order::create([
                    'user_id' => $user->id,
                    'restaurant_id' => $request->restaurant_id,
                    'delivery' => ($request->delivery) ? 1 : 0,
                    'total_amount' => 0,
                    'created_by' => $user->name,
                    'orphanage_id' => $orphanage?->id,
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
                    $promo_code = PromoCode::active()->where('code', $request->promo_code)->first();
                    if ($promo_code) {
                        $order->update([
                            'promo_code_id' => $promo_code->id
                        ]);

                        if ($promo_code->type == 'amount') {
                            $totalSubtotal = $totalSubtotal - $promo_code->value;
                            $discount += $promo_code->value;
                        } else {
                            $discount += ($promo_code->value / 100) * $totalSubtotal;
                            $totalSubtotal = $totalSubtotal - $discount;
                        }
                    }
                }

                $order->update([
                    'discount' => $discount
                ]);

                if ($items_are_groceries) {
                    // Service Charge
                    $service_charge = $restaurant->groceries_service_charge_agreement ? ((int) $restaurant->groceries_service_charge_agreement / 100) * $totalSubtotal : ((double) Setting::where('name', 'Base Groceries Service Charge Rate')->first()->variable / 100) * $totalSubtotal;
                } else {
                    // Service Charge
                    $service_charge = $restaurant->service_charge_agreement ? ((int) $restaurant->service_charge_agreement / 100) * $totalSubtotal : ((double) Setting::where('name', 'Base Service Charge Rate')->first()->variable / 100) * $totalSubtotal;
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
                } else if ($orphanage) {
                    $totalSubtotal = $totalSubtotal + $delivery_fee;
                    $totalSubtotal = $totalSubtotal <= 0 ? 0 : $totalSubtotal;
                    $order->update([
                        'total_amount' => $totalSubtotal,
                        'delivery_fee' => $delivery_fee,
                        'delivery_address' => $orphanage->location,
                        'delivery_location_lat' => $orphanage->location_lat,
                        'delivery_location_lng' => $orphanage->location_long,
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

                return new OrderResource($order->loadMissing(['user', 'restaurant', 'orderItems.menu.images', 'orphanage']));
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }

    function point2point_distance($lat1, $lon1, $lat2, $lon2, $unit='K')
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K")
        {
            return ($miles * 1.609344);
        }
        else if ($unit == "N")
        {
        return ($miles * 0.8684);
        }
        else
        {
        return $miles;
      }
    }

    /**
     * Get an order
     *
     * Returns a single order. For users with role `restaurant` or `restaurant employee` the raw order (with restaurant,
     * rider, items, reservation, tables, payment and orphanage) is returned together with the restaurant's tables and, for
     * delivery orders, a list of available riders with their distance/time from the restaurant. Other users get the order
     * as an order resource. Returns an empty body unless the request accepts JSON.
     *
     * @urlParam order string required The uuid of the order. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     *
     * @response 200 scenario="Customer" {"status":"Request was succesful","message":"","data":{"order":{"id":10,"uuid":"9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d","attributes":{"userId":12,"totalAmount":"25.50","delivery":true,"deliveryFee":"3.20","deliveryAddress":"221B Baker Street, London","deliveryStatus":null,"deliveryLat":51.5237,"deliveryLng":-0.1585,"createdAt":"Mon, Jan 15, 2024 10:00 AM","status":"1","paymentStatus":"Pending","service_charge":"1.20","booking_time":null,"discount":"0","preparation_time":25,"restaurant_name":"Mama's Kitchen","user_name":"Jane Doe"},"relationships":{"user":{"id":12,"uuid":"0f8fad5b-d9cb-469f-a165-70867728950e","name":"Jane Doe","email":"jane@example.com","phone_number":"+447700900123","role":"orderer"},"restaurant":{"id":3,"uuid":"6fa459ea-ee8a-3ca4-894e-db77e160355e","attributes":{"name":"Mama's Kitchen"}},"orderItems":[{"id":1,"uuid":"1b4e28ba-2fa1-11d2-883f-0016d3cca427","attributes":{"orderId":10,"menuId":4,"quantity":2,"subtotal":"20.00","status":"1"},"relationships":{"menu":{"id":4,"title":"Chicken Burger"}}}],"orphanage":null}}}}
     * @response 200 scenario="Restaurant user" {"status":"Request was succesful","message":"","data":{"order":{"id":10,"uuid":"9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d","user_id":12,"restaurant_id":3,"rider_id":null,"total_amount":"25.50","delivery":true,"delivery_fee":"3.20","delivery_address":"221B Baker Street, London","delivery_location_lat":51.5237,"delivery_location_lng":-0.1585,"delivery_status":null,"status":"2","service_charge":"1.20","discount":"0","booking_time":null,"promo_code_id":null,"orphanage_id":null,"created_by":"Jane Doe","updated_by":null,"created_at":"2024-01-15T10:00:00.000000Z","updated_at":"2024-01-15T10:05:00.000000Z","country":"United Kingdom","preparation_time":25,"restaurant":{"id":3,"name":"Mama's Kitchen","latitude":"51.5200","longitude":"-0.1500"},"rider":null,"order_items":[{"id":1,"order_id":10,"menu_id":4,"quantity":2,"subtotal":"20.00","menu":{"id":4,"title":"Chicken Burger","preparation_time":25}}],"reservation":null,"order_tables":[],"payment":null,"orphanage":null},"riders":[{"id":7,"name":"John Rider","latitude":"51.5100","longitude":"-0.1400","distance":"2.3 km","time_away":"8 mins"}],"restaurant_tables":[{"id":1,"restaurant_id":3,"name":"Table 1","number_of_people":4}]}}
     * @response 404 scenario="Order not found" {"message":"No query results for model [App\\Models\\Order]."}
     *
     * @responseField data.order object The order.
     * @responseField data.riders object[] Restaurant users only. Available riders for delivery orders.
     * @responseField data.restaurant_tables object[] Restaurant users only. Tables of the user's restaurants.
     */
    public function show(Order $order)
    {
        $order = $order->load('restaurant', 'rider', 'orderItems.menu', 'reservation', 'orderTables.restaurantTable', 'payment', 'orphanage');
        $order->preparation_time = $order->getTotalPreparationTime();
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
                $riders = User::where('device_token', '!=', NULL)
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
                                    //                     ->where('id', '!=', $order->id)
                                    //                     ->where('delivery', 1)
                                    //                     ->where('rider_id', '!=', NULL)
                                    //                     ->where('restaurant_id', $order->restaurant_id)
                                    //                     ->where('delivery_status', '!=', 'Delivered')
                                    //                     ->where('delivery_status', '!=', 'On Delivery')
                                    //                     ->where('delivery_location_lat', '!=', '')
                                    //                     ->select(
                                    //                         DB::raw("3961 * acos(cos(radians(" . $order->delivery_location_lat . "))
                                    //                         * cos(radians(orders.delivery_location_lat))
                                    //                         * cos(radians(orders.delivery_location_lng)
                                    //                         - radians(" . $order->delivery_location_lng . "))
                                    //                         + sin(radians(" . $order->delivery_location_lat. "))
                                    //                         * sin(radians(orders.delivery_location_lat))) AS distance"))
                                    //                     ->get();

                                    $deliveries = Order::where('id', '!=', $order->id)
                                                        ->where('delivery', 1)
                                                        ->where('rider_id', '!=', NULL)
                                                        ->where('restaurant_id', $order->restaurant_id)
                                                        ->where('delivery_status', '!=', 'Delivered')
                                                        ->where('delivery_status', '!=', 'On Delivery')
                                                        ->distance($order->delivery_location_lat, $order->delivery_location_lng)
                                                        ->get();

                                    // Filter to couriers distances less than 6 MILES
                                    $nearby_deliveries = $deliveries->filter(function($delivery) {
                                        return (int) ($delivery->distance) <= 20;
                                    })->pluck('rider_id')->values()->all();

                                    // Check if rider rejected the delivery request
                                    $rejected_orders = AssignedOrder::where('order_id', $order->id)->where('status', 'rejected')->pluck('user_id');

                                    $query->whereNotIn('id', $rejected_orders)
                                            ->when(count($assigned_riders) > 0 && count($nearby_deliveries) > 0, function ($query) use ($assigned_riders, $nearby_deliveries) {
                                                $query->where(function ($query) use ($assigned_riders, $nearby_deliveries) {
                                                    $query->orWhereIn('id', $assigned_riders)->orWhereIn('id', $nearby_deliveries);
                                                });
                                            });
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
                        'order' => new OrderResource($order->loadMissing(['user', 'restaurant', 'orderItems.menu.images', 'orphanage'])),
                    ], '', 200) : '';
        }
    }

    /**
     * Update order status
     *
     * Restaurant endpoint that sets the order status. A push notification is sent to the customer when the status is
     * `in_progress` or `denied`.
     *
     * @urlParam order string required The uuid of the order. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     *
     * @bodyParam status string required The new order status. Example: in_progress
     *
     * @response 200 scenario="Updated" {"status":"Request was succesful","message":"Order status updated successfully","data":{"id":10,"uuid":"9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d","user_id":12,"restaurant_id":3,"rider_id":null,"total_amount":"25.50","delivery":true,"delivery_fee":"3.20","delivery_address":"221B Baker Street, London","delivery_location_lat":51.5237,"delivery_location_lng":-0.1585,"delivery_status":null,"status":"2","service_charge":"1.20","discount":"0","booking_time":null,"promo_code_id":null,"orphanage_id":null,"created_by":"Jane Doe","updated_by":null,"created_at":"2024-01-15T10:00:00.000000Z","updated_at":"2024-01-15T10:05:00.000000Z","country":"United Kingdom"}}
     * @response 404 scenario="Order not found" {"message":"No query results for model [App\\Models\\Order]."}
     * @response 422 scenario="Validation error" {"message":"The status field is required.","errors":{"status":["The status field is required."]}}
     *
     * @responseField data object The updated order.
     */
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

    /**
     * Update reservation status
     *
     * Restaurant endpoint that updates a dine-in order's reservation status. `completed` also sets the order status to 5
     * and sends a thank-you push notification to the customer.
     *
     * @urlParam order string required The uuid of the order. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     *
     * @bodyParam status string required The reservation status. One of: `completed`, `in progress`, `no show`, `cancelled`. Example: completed
     *
     * @response 200 scenario="Updated" {"status":"Request was succesful","message":null,"data":"Reservation updated successfully"}
     * @response 404 scenario="Order not found" {"message":"No query results for model [App\\Models\\Order]."}
     * @response 422 scenario="Validation error" {"message":"The selected status is invalid.","errors":{"status":["The selected status is invalid."]}}
     */
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

    /**
     * Assign tables to a reservation
     *
     * Restaurant endpoint that assigns one or more restaurant tables to a dine-in order, sets the order status to 2 and
     * notifies the customer. Fails if the booking time has already passed.
     *
     * @urlParam order string required The uuid of the order. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     *
     * @bodyParam restaurant_table_ids string required Comma-separated restaurant table ids. Example: 1,2
     *
     * @response 200 scenario="Assigned" {"status":"Request was succesful","message":null,"data":"Table(s) assigned to order"}
     * @response 403 scenario="Booking time passed" {"status":"Error has occured...","message":"This reserved time for this order has already passed","data":""}
     * @response 404 scenario="Order not found" {"message":"No query results for model [App\\Models\\Order]."}
     */
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

    /**
     * Delete an order
     *
     * Not implemented: the endpoint performs no action and returns an empty response.
     *
     * @urlParam order string required The uuid of the order. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     *
     * @response 200 scenario="No-op" ""
     * @response 404 scenario="Order not found" {"message":"No query results for model [App\\Models\\Order]."}
     */
    public function destroy(Order $order)
    {
        //
    }

    /**
     * Assign an order to a rider
     *
     * Restaurant endpoint that sends a delivery request to a rider: records an assigned order and pushes a notification
     * with pickup/delivery coordinates, customer, order items and restaurant details to the rider.
     *
     * @bodyParam order_id string required The uuid of the order. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @bodyParam rider_id integer required The user id of the rider. Example: 7
     *
     * @response 200 scenario="Request sent" {"status":"Request was succesful","message":"Delivery request sent successfully","data":""}
     * @response 404 scenario="Order not found" {"status":"Error has occured...","message":"Order not found. Please use the uuid of the order.","data":""}
     * @response 422 scenario="Validation error" {"message":"The rider id field is required.","errors":{"rider_id":["The rider id field is required."]}}
     */
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

        // info(json_encode(['pickup_address' => $pickup_address, 'delivery_address' => $delivery_address, 'order_code' => $order->id, 'orderer' => ['id' => $order->user->uuid, 'name' => $order->user->name, 'phone_number' => $order->user->phone_number], 'order_details' => ['id' => $order->uuid, 'order_items' => $order->orderItems->load('menu.menuPrices')], 'restaurant' => ['name' => $order->restaurant->name, 'logo' => $order->restaurant->logo, 'id' => $order->restaurant->uuid]]));
        SendNotification::dispatchAfterResponse($rider, 'You have been assigned to deliver an order', ['pickup_address' => $pickup_address, 'delivery_address' => $delivery_address, 'order_code' => $order->id, 'orderer' => ['id' => $order->user->uuid, 'name' => $order->user->name, 'phone_number' => $order->user->phone_number], 'order_details' => ['id' => $order->uuid, 'order_items' => $order->orderItems->load('menu.menuPrices')], 'restaurant' => ['name' => $order->restaurant->name, 'logo' => $order->restaurant->logo, 'id' => $order->restaurant->uuid]]);

        return $this->success('', 'Delivery request sent successfully', 200);
    }

    /**
     * Count pending delivery orders
     *
     * Returns the number of delivery orders with status 1 (pending) for the restaurant(s) of the authenticated
     * `restaurant` or `restaurant employee` user. Other roles get 0. The response body is a bare integer.
     *
     * @response 200 scenario="Count" 3
     */
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

    /**
     * Count pending dine-in orders
     *
     * Returns the number of dine-in (non-delivery) orders with status 1 (pending) for the restaurant(s) of the authenticated
     * `restaurant` or `restaurant employee` user. Other roles get 0. The response body is a bare integer.
     *
     * @response 200 scenario="Count" 2
     */
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
     *
     * Lets the authenticated customer review the restaurant and the rider of an order. The restaurant's average rating
     * is recalculated. The rider review is only saved when the order has a rider with a rider profile.
     *
     * @group Reviews
     *
     * @bodyParam order_id integer required The id of the order. Example: 10
     * @bodyParam restaurant_rating integer required The rating of the restaurant from 1 - 5. Example: 5
     * @bodyParam rider_rating integer required The rating of the rider from 1 - 5. Example: 4
     * @bodyParam restaurant_review string A review of the restaurant. Example: Great food, quick service.
     * @bodyParam rider_review string A review of the rider. Example: Friendly and on time.
     *
     * @response 200 scenario="Saved" {"status":"Request was succesful","message":"Review successfully saved","data":"Review"}
     * @response 400 scenario="Validation error" {"status":"Error has occured...","message":{"restaurant_rating":["The restaurant rating field is required."]},"data":""}
     * @response 404 scenario="Order not found" {"status":"Error has occured...","message":"Order not found","data":""}
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

                // Update restaurant average rating
                $total_reviews_count = $restaurant->reviews->count();
                if ($total_reviews_count > 0) {
                    $total_reviews = $restaurant->reviews->sum('rating');

                    $restaurant->update([
                        'average_rating' => round($total_reviews / $total_reviews_count, 2)
                    ]);
                }
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

<?php

namespace App\Http\Controllers\Api\V1;

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
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * @group Customer Order Management
 *
 * Customer Order API resource
 */
class OrderController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the resource.
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
                ->with(['orderItems', 'restaurant', 'rider'])
                ->paginate();

                return new OrderCollection($orders);
            }
            if ($role === 'restaurant') {
                $orders = Order::whereIn('restaurant_id', $user->restaurants->pluck('id'))
                                ->where($filterItems)
                                ->with(['orderItems', 'restaurant', 'rider'])
                                ->paginate();

                return new OrderCollection($orders);
            } else {
                $orders = Order::where($filterItems);
                return new OrderCollection($orders->with(['orderItems', 'restaurant', 'rider']));
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        // if (!$request->delivery) {
        //     return $this->error('Order Creation', 'Important Field Missing', 402);
        // }

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
                            ->where('status', 2)
                            ->first();

                if (!$cart) {
                    return $this->error('Order Creation', 'User does not have active cart', 402);
                }
                $cartItems = CartItem::where('cart_id', $cart->id)->get();

                //create Order object
                $order = Order::create([
                    'user_id' => $user->id,
                    'restaurant_id' => $request->restaurant_id,
                    'delivery' => ($request->delivery) ? 1 : 0,
                    'total_amount' => 0,
                    'created_by' => $user->name,
                ]);

                // cart items will translate to order items
                $cartItems->each(function($item) use ($order, $user){
                    $standardMenuPrice = $item->menu->menuPrices->where('description', 'standard')->first();
                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_id' => $item->menu_id,
                        'quantity' => $item->quantity,
                        'subtotal' => ($standardMenuPrice->price * $item->quantity),
                        'created_by' => $user->name,
                    ]);
                });

                // update total amount in Order
                $totalSubtotal = $order->orderItems->sum('subtotal');
                // add delivery fee if customer needs
                if ($request->delivery == true) {
                    $totalSubtotal = $totalSubtotal + $request->delivery_fee;
                    $order->update([
                        'total_amount' => $totalSubtotal,
                        'delivery_fee' => $request->delivery_fee,
                        'delivery_address' => $request->delivery_address,
                    ]);
                } else {
                    $order->update(['total_amount' => $totalSubtotal]);
                }

                DB::commit();
                return new OrderResource($order->loadMissing(['user', 'restaurant', 'orderItems']));
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order = $order->load('restaurant', 'rider', 'orderItems');
        $user = $order->user;
        $restaurant = $order->restaurant;

        if (auth()->user()->hasRole('restaurant')) {
            $riders = User::whereHas('roles', function($query) {
                                $query->where('name', 'rider');
                            })
                            ->where(function($query) use ($restaurant, $order, $user) {
                                $orders = Order::where('rider_id', '!=', NULL)->get()->pluck('rider_id');

                                // Get riders who have been assigned delivery to the restaurant
                                // and are going close to another order from the same restaurant
                                $deliveries = DB::table("orders")
                                                ->where('rider_id', '!=', NULL)
                                                ->where('restaurant_id', $order->restaurant_id)
                                                ->whereIn('status', [1, 2, 3])
                                                ->select("*",
                                                    DB::raw("6371 * acos(cos(radians(" . $user->latitude . "))
                                                    * cos(radians(".$order->user->latitude."))
                                                    * cos(radians(".$order->user->longitude.") - radians(" . $user->longitude . "))
                                                    + sin(radians(" .$user->latitude. "))
                                                    * sin(radians(".$order->user->latitude."))) AS distance"))
                                                ->get();

                                // Filter to riders distances less than 5 Kms
                                $nearby_deliveries = $deliveries->filter(function($delivery) {
                                    return (int) $delivery->distance <= 5;
                                })->pluck('rider_id')->values()->all();

                                $rejected_orders = AssignedOrder::where('order_id', $order->id)->where('status', 'rejected')->get()->pluck('courier_id');

                                $query->whereNotIn('id', $rejected_orders)
                                        ->where(function($query) use ($nearby_deliveries, $orders) {
                                            $query->whereIn('id', $nearby_deliveries)
                                                ->orWhereNotIn('id', $orders);
                                        });
                            })->get()->each(function($rider, $key) use ($restaurant) {
                                if ($rider->latitude != NULL && $rider->longitude != NULL) {
                                    $restaurant_coordinates = explode(',', $restaurant->map_location);
                                    $business_location = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$rider->latitude.','.$rider->longitude.'&destinations='.$restaurant_coordinates[0].','.$restaurant_coordinates[1].'&key='.config('services.map.key'));
                                    info($business_location);
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
                                    fn($a, $b) => (double) explode(' ', $a['distance'])[0] >= (double) explode(' ',$b['distance'])[0],
                                ]);
            return request()->wantsJson() ?
                $this->success([
                    'order' => $order,
                    'riders' => $riders,
                ], '', 200) : '';
        } else {
            return request()->wantsJson() ?
                    $this->success([
                        'order' => $order,
                    ], '', 200) : '';
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
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

        $order = Order::where('id', $request->order_id)->orWhere('uuid', $request->order_id)->first();

        $rider = User::find($request->rider_id);

        AssignedOrder::create([
            'order_id' => $order->id,
            'user_id' => $rider->id
        ]);

        SendNotification::dispatchAfterResponse($rider, 'You have been assigned to deliver an order', ['delivery_location' => [$order->user->latitude, $order->user->longitude]]);

        return $this->success('', 'Delivery request sent successfully', 200);
    }
}

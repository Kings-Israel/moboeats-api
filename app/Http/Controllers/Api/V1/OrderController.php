<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\OrderFilter;
use App\Models\Order;
use App\Http\Requests\V1\StoreOrderRequest;
use App\Http\Requests\V1\UpdateOrderRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderCollection;
use App\Http\Resources\V1\OrderResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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
                ->with(['orderItems', 'restaurant'])
                ->paginate();

                return new OrderCollection($orders);
            } 
            if ($role === 'restaurant') {
                // info($user->restaurants->pluck('id'));
                //get user restaurants and then load orders for all restaurants
                $orders = Order::whereIn('restaurant_id', $user->restaurants->pluck('id'))
                ->where($filterItems)
                ->with(['orderItems', 'restaurant', 'user'])
                ->paginate();

                return new OrderCollection($orders);
            } else {
                $orders = Order::where($filterItems);
                return new OrderCollection($orders->with(['orderItems', 'restaurant']));
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
                // ->where('status', 2)
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
       return new OrderResource($order->loadMissing(['orderItems', 'restaurant', 'user']));
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
}

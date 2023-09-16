<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\CartFilter;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Http\Requests\V1\StoreCartRequest;
use App\Http\Requests\V1\UpdateCartRequest;
use App\Http\Resources\V1\CartCollection;
use App\Http\Resources\V1\CartResource;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @group Customer Cart
 * 
 * Cart API resource
 */
class CartController extends Controller
{
    use HttpResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter =  new CartFilter();
        $filterItems = $filter->transform($request);

        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                $carts = Cart::where('user_id', Auth::user()->id)
                ->where($filterItems)
                ->with(['cartItems', 'user'])
                ->paginate();

                return new CartCollection($carts);
            } else {
                $carts = Cart::where($filterItems);
                return new CartCollection($carts->with(['user', 'cartItems']));
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }


    }

    
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCartRequest $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                if (Cart::where('user_id', $user->id)->exists()) {
                    return $this->error('Cart', 'You have active cart', 402);
                }
                $request->merge([
                    'status' => 2,
                ]);
                $cart = Cart::create($request->all());
                DB::commit();
                return new CartResource($cart->loadMissing(['user', 'cartItems']));
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
    public function show(Cart $cart)
    {
        return new CartResource($cart->loadMissing(['user', 'cartItems']));
    }

    
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCartRequest $request, Cart $cart)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cart $cart)
    {
        try {
            DB::beginTransaction();
            $cart->delete();
            DB::commit();
            return response(null, 204);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }
}

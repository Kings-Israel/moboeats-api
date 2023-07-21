<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\CartItemFilter;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Http\Requests\V1\StoreCartItemRequest;
use App\Http\Requests\V1\UpdateCartItemRequest;
use App\Http\Resources\V1\CartItemCollection;
use App\Http\Resources\V1\CartItemResource;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @group Customer Cart Items Management
 * 
 * Cart Items API resource
 */
class CartItemController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                if (empty($request->all())) {
                    return $this->error('', 'You must pass cartId as query', 401);
                } 
                $filter =  new CartItemFilter();
                $filterItems = $filter->transform($request);

                $cartsItems = CartItem::where($filterItems)
                ->with(['menu', 'cart'])
                ->paginate();
                return new CartItemCollection($cartsItems);
            } else {
                $filter =  new CartItemFilter();
                $filterItems = $filter->transform($request);

                $cartsItems = CartItem::where($filterItems)
                ->with(['menu', 'cart'])
                ->paginate();
                return new CartItemCollection($cartsItems);
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }

    
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCartItemRequest $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                $cartItem = CartItem::create($request->all());
                DB::commit();
                return new CartItemResource($cartItem->with(['menu', 'cart']));
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
    public function show(CartItem $cartItem)
    {
        return new CartItemResource($cartItem->with(['menu', 'cart']));
    }

    
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                $cartItem->update($request->all());
                DB::commit();
                return new CartItemResource($cartItem->with(['menu', 'cart']));
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
     * Remove the specified resource from storage.
     */
    public function destroy(CartItem $cartItem)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                $cartItem->delete();
                DB::commit();
                return response(null, 204);
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        }
    }

   
}

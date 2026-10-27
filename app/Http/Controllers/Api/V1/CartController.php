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
 * @group Cart
 *
 * Customer shopping cart. Each orderer has at most one active cart.
 */
class CartController extends Controller
{
    use HttpResponses;

    /**
     * List carts
     *
     * Users with role `orderer` get their own carts (paginated) with items, menus and menu images.
     * Other roles get all carts matching the filters. Returns 401 if the user does not hold their own `role_id` role.
     *
     * @queryParam uuid[eq] string Filter by cart UUID. Example: 0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f
     * @queryParam status[eq] integer Filter by cart status. Example: 2
     * @queryParam userId[eq] integer Filter by user ID. Example: 5
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Orderer" {
     *   "data": [{"id": 1, "uuid": "0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f", "attributes": {"userId": 5, "status": "2"}, "relationships": {"user": {"id": 5, "uuid": "9b1d3c2e-1a2b-4c5d-8e9f-0a1b2c3d4e5f", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": "orderer", "referral_code": null, "is_guided": true}, "cartItems": [{"id": 3, "uuid": "c7e1f9a2-3b4c-4d5e-9f60-718293a4b5c6", "attributes": {"cartId": 1, "menuId": 10, "quantity": 2, "status": "2"}, "relationships": {"menu": {"id": 10, "uuid": "a1b2c3d4-e5f6-4789-90ab-cdef01234567", "attributes": {"title": "Chicken Burger", "description": "Grilled chicken burger", "status": "2", "preparation_time": "20", "average_rating": 4.5}, "relationships": {"images": [], "menuPrices": []}}, "cart": {"id": 1, "uuid": "0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f", "user_id": 5, "status": 2}}}]}}],
     *   "links": {"first": "https://moboeats.com/api/v1/orderer/cart?page=1", "last": "https://moboeats.com/api/v1/orderer/cart?page=1", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/orderer/cart", "per_page": 15, "to": 1, "total": 1}
     * }
     * @response 401 scenario="Unauthorized role" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @responseField data object[] The carts.
     * @responseField data[].relationships.cartItems object[] Items in the cart.
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
                                ->with(['cartItems.menu.images', 'user'])
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
     * Create a cart
     *
     * Creates a cart (status `2`) for the authenticated user. Only users with role `orderer` may call this.
     * Fails with 402 if the user already has a cart.
     *
     * @bodyParam userId integer required The ID of the user owning the cart. Example: 5
     *
     * @response 201 scenario="Created" {"data": {"id": 1, "uuid": "0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f", "attributes": {"userId": 5, "status": "2"}, "relationships": {"user": {"id": 5, "uuid": "9b1d3c2e-1a2b-4c5d-8e9f-0a1b2c3d4e5f", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": "orderer", "referral_code": null, "is_guided": true}, "cartItems": []}}}
     * @response 401 scenario="Not an orderer" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @response 402 scenario="Cart already exists" {"status": "Error has occured...", "message": "You have active cart", "data": "Cart"}
     * @response 403 scenario="Server error" {"status": "Error has occured...", "message": "SQLSTATE[HY000]: General error", "data": ""}
     * @response 422 scenario="Validation error" {"message": "The user id field is required.", "errors": {"userId": ["The user id field is required."]}}
     * @responseField data.uuid string The cart UUID.
     * @responseField data.attributes.status string Cart status.
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
     * Get a cart
     *
     * Returns a single cart with its user and items.
     *
     * @urlParam cart string required The UUID of the cart. Example: 0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f
     *
     * @response 200 {"data": {"id": 1, "uuid": "0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f", "attributes": {"userId": 5, "status": "2"}, "relationships": {"user": {"id": 5, "uuid": "9b1d3c2e-1a2b-4c5d-8e9f-0a1b2c3d4e5f", "name": "Jane Doe", "email": "jane@example.com", "phone_number": "254712345678", "role": "orderer", "avatar": null, "relationships": {"orderer": null}, "type": "orderer", "referral_code": null, "is_guided": true}, "cartItems": [{"id": 3, "uuid": "c7e1f9a2-3b4c-4d5e-9f60-718293a4b5c6", "attributes": {"cartId": 1, "menuId": 10, "quantity": 2, "status": "2"}, "relationships": {"menu": {"id": 10, "uuid": "a1b2c3d4-e5f6-4789-90ab-cdef01234567", "attributes": {"title": "Chicken Burger", "description": "Grilled chicken burger", "status": "2", "preparation_time": "20", "average_rating": 4.5}, "relationships": {"images": [], "menuPrices": []}}, "cart": {"id": 1, "uuid": "0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f", "user_id": 5, "status": 2}}}]}}}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\Cart] 0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f"}
     * @responseField data.relationships.cartItems object[] Items in the cart.
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
     * Delete a cart
     *
     * Deletes the given cart.
     *
     * @urlParam cart string required The UUID of the cart. Example: 0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f
     *
     * @response 204 scenario="Deleted" ""
     * @response 403 scenario="Server error" {"status": "Error has occured...", "message": "SQLSTATE[HY000]: General error", "data": ""}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\Cart] 0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f"}
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

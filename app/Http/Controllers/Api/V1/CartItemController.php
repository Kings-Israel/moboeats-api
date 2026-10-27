<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\CartItemFilter;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Http\Requests\V1\StoreCartItemRequest;
use App\Http\Requests\V1\UpdateCartItemRequest;
use App\Http\Resources\V1\CartItemCollection;
use App\Http\Resources\V1\CartItemResource;
use App\Models\Menu;
use App\Models\Cart;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * @group Cart
 *
 * Items in the customer's cart.
 */
class CartItemController extends Controller
{
    use HttpResponses;
    /**
     * List cart items
     *
     * Users with role `orderer` get the items in their own cart (10 per page). Other roles get all cart items
     * matching the filters. Returns 401 if the user does not hold their own `role_id` role.
     *
     * @queryParam uuid[eq] string Filter by item UUID (non-orderer roles only). Example: c7e1f9a2-3b4c-4d5e-9f60-718293a4b5c6
     * @queryParam status[eq] integer Filter by status (non-orderer roles only). Example: 2
     * @queryParam cartId[eq] integer Filter by cart ID (non-orderer roles only). Example: 1
     * @queryParam menuId[eq] integer Filter by menu ID (non-orderer roles only). Example: 10
     * @queryParam quantity[eq] integer Filter by quantity; also supports `quantity[gt]` and `quantity[lt]` (non-orderer roles only). Example: 2
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "data": [{"id": 3, "uuid": "c7e1f9a2-3b4c-4d5e-9f60-718293a4b5c6", "attributes": {"cartId": 1, "menuId": 10, "quantity": 2, "status": "2"}, "relationships": {"menu": {"id": 10, "uuid": "a1b2c3d4-e5f6-4789-90ab-cdef01234567", "attributes": {"title": "Chicken Burger", "description": "Grilled chicken burger", "status": "2", "preparation_time": "20", "average_rating": 4.5}, "relationships": {"images": [], "menuPrices": []}}, "cart": {"id": 1, "uuid": "0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f", "user_id": 5, "status": 2}}}],
     *   "links": {"first": "https://moboeats.com/api/v1/orderer/cart-items?page=1", "last": "https://moboeats.com/api/v1/orderer/cart-items?page=1", "prev": null, "next": null},
     *   "meta": {"current_page": 1, "from": 1, "last_page": 1, "links": [], "path": "https://moboeats.com/api/v1/orderer/cart-items", "per_page": 10, "to": 1, "total": 1}
     * }
     * @response 200 scenario="Orderer has no cart" {"status": "Request was succesful", "message": "No cart items were found", "data": ""}
     * @response 401 scenario="Unauthorized role" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @responseField data object[] The cart items.
     * @responseField data[].attributes.quantity integer Quantity of the menu item.
     */
    public function index(Request $request)
    {
        $user = User::where('id',Auth::user()->id)->first();

        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                $cart = Cart::where('user_id', $user->id)->first();
                $filter =  new CartItemFilter();
                $filterItems = $filter->transform($request);

                if ($cart) {
                    $cartsItems = CartItem::where('cart_id', $cart->id)
                                            ->with('menu', 'cart')
                                            ->paginate(10);

                    return new CartItemCollection($cartsItems);
                } else {
                    return $this->success('', 'No cart items were found');
                }
            } else {
                $filter =  new CartItemFilter();
                $filterItems = $filter->transform($request);

                $cartsItems = CartItem::where($filterItems)
                                        ->with('menu', 'cart')
                                        ->paginate(10);

                return new CartItemCollection($cartsItems);
            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }

    /**
     * Add an item to the cart
     *
     * Adds a menu item to the authenticated orderer's active cart, creating the cart if needed.
     * Only users with role `orderer` may call this. Fails if the menu item is unavailable or already in the cart.
     *
     * @bodyParam menuId integer required The ID of the menu item. Example: 10
     * @bodyParam quantity integer required Quantity to add. Example: 2
     * @bodyParam status integer Item status. Example: 2
     *
     * @response 201 scenario="Created" {"data": {"id": 3, "uuid": "c7e1f9a2-3b4c-4d5e-9f60-718293a4b5c6", "attributes": {"cartId": 1, "menuId": 10, "quantity": 2, "status": "2"}, "relationships": {"menu": {"id": 10, "uuid": "a1b2c3d4-e5f6-4789-90ab-cdef01234567", "attributes": {"title": "Chicken Burger", "description": "Grilled chicken burger", "status": "2", "preparation_time": "20", "average_rating": 4.5}, "relationships": {"images": [], "menuPrices": []}}, "cart": {"id": 1, "uuid": "0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f", "user_id": 5, "status": 2}}}}
     * @response 401 scenario="Not an orderer" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @response 403 scenario="Menu item unavailable" {"status": "Error has occured...", "message": "The selected item is not available", "data": "Menu Item"}
     * @response 403 scenario="Already in cart" {"status": "Error has occured...", "message": "Item already exists in the cart", "data": "Cart"}
     * @response 422 scenario="Validation error" {"message": "The menu id field is required.", "errors": {"menuId": ["The menu id field is required."]}}
     * @responseField data.attributes.cartId integer ID of the cart the item was added to.
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
                $cart = Cart::where('user_id', $user->id)->where("status", 2)->first();
                if (!$cart) {
                    $cart = Cart::create(['user_id' => $user->id, 'status' => 2]);
                }

                // Check if menu item is available
                $menu_item = Menu::find($request->menu_id);

                if (!$menu_item || $menu_item->status == 1) {
                    return $this->error('Menu Item', 'The selected item is not available', 403);
                }

                if (CartItem::where('menu_id', $request->menu_id)->where('cart_id', $cart->id)->exists()) {
                    return $this->error('Cart', 'Item already exists in the cart', 403);
                }

                $request->merge([
                    'cart_id' => $cart->id,
                ]);

                $cartItem = CartItem::create($request->all());

                DB::commit();
                return new CartItemResource($cartItem->loadMissing(['menu', 'cart']));
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
     * Get a cart item
     *
     * Returns a single cart item with its menu and cart.
     *
     * @urlParam cart_item string required The UUID of the cart item. Example: c7e1f9a2-3b4c-4d5e-9f60-718293a4b5c6
     *
     * @response 200 {"data": {"id": 3, "uuid": "c7e1f9a2-3b4c-4d5e-9f60-718293a4b5c6", "attributes": {"cartId": 1, "menuId": 10, "quantity": 2, "status": "2"}, "relationships": {"menu": {"id": 10, "uuid": "a1b2c3d4-e5f6-4789-90ab-cdef01234567", "attributes": {"title": "Chicken Burger", "description": "Grilled chicken burger", "status": "2", "preparation_time": "20", "average_rating": 4.5}, "relationships": {"images": [], "menuPrices": []}}, "cart": {"id": 1, "uuid": "0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f", "user_id": 5, "status": 2}}}}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\CartItem] c7e1f9a2-3b4c-4d5e-9f60-718293a4b5c6"}
     * @responseField data.relationships.menu object The menu item.
     */
    public function show(CartItem $cartItem)
    {
        return new CartItemResource($cartItem->loadMissing(['menu', 'cart']));
    }

    /**
     * Update a cart item
     *
     * Updates the quantity/status/menu of a cart item. Only users with role `orderer` may call this.
     * With `PUT`, `menuId` and `quantity` are required; with `PATCH` all fields are optional.
     *
     * @urlParam cart_item string required The UUID of the cart item. Example: c7e1f9a2-3b4c-4d5e-9f60-718293a4b5c6
     * @bodyParam menuId integer The ID of the menu item (required for PUT). Example: 10
     * @bodyParam quantity integer The new quantity (required for PUT). Example: 3
     * @bodyParam status integer Item status. Example: 2
     *
     * @response 200 {"data": {"id": 3, "uuid": "c7e1f9a2-3b4c-4d5e-9f60-718293a4b5c6", "attributes": {"cartId": 1, "menuId": 10, "quantity": 2, "status": "2"}, "relationships": {"menu": {"id": 10, "uuid": "a1b2c3d4-e5f6-4789-90ab-cdef01234567", "attributes": {"title": "Chicken Burger", "description": "Grilled chicken burger", "status": "2", "preparation_time": "20", "average_rating": 4.5}, "relationships": {"images": [], "menuPrices": []}}, "cart": {"id": 1, "uuid": "0f9e8d7c-6b5a-4c3d-8e2f-1a0b9c8d7e6f", "user_id": 5, "status": 2}}}}
     * @response 401 scenario="Not an orderer" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @response 403 scenario="Server error" {"status": "Error has occured...", "message": "SQLSTATE[HY000]: General error", "data": ""}
     * @response 422 scenario="Validation error" {"message": "The quantity field is required.", "errors": {"quantity": ["The quantity field is required."]}}
     * @responseField data.attributes.quantity integer The updated quantity.
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
                return new CartItemResource($cartItem->loadMissing(['menu', 'cart']));
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
     * Remove a cart item
     *
     * Deletes a cart item. Only users with role `orderer` may call this.
     *
     * @urlParam cart_item string required The UUID of the cart item. Example: c7e1f9a2-3b4c-4d5e-9f60-718293a4b5c6
     *
     * @response 204 scenario="Deleted" ""
     * @response 401 scenario="Not an orderer" {"status": "Error has occured...", "message": "Unauthorized", "data": ""}
     * @response 403 scenario="Server error" {"status": "Error has occured...", "message": "SQLSTATE[HY000]: General error", "data": ""}
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

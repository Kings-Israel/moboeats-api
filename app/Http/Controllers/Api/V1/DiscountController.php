<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MenuCollection;
use App\Models\Discount;
use App\Models\Menu;
use App\Models\MenuPrice;
use App\Models\UserRestaurant;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

/**
 * @group Promo Codes & Discounts
 *
 * Menu item discounts managed by restaurant owners/employees (`v1/restaurant/discounts`).
 */
class DiscountController extends Controller
{
    use HttpResponses;

    /**
     * List discounted menu items
     *
     * Returns menu items that have a discount, 6 per page. Users with role `restaurant` see discounted menus of their
     * own restaurants and also receive all their menus (with restaurant) in `menus`; users with role `restaurant employee`
     * see their restaurant's discounted menus; other authenticated users see active, priced menus with images.
     *
     * @queryParam search string Filter discounted menus by title (restaurant owners and employees only). Example: burger
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "discounts": {
     *       "current_page": 1,
     *       "data": [{"id": 10, "uuid": "a1b2c3d4-e5f6-4789-90ab-cdef01234567", "title": "Chicken Burger", "description": "Grilled chicken burger", "restaurant_id": 1, "status": 2, "preparation_time": "20", "created_by": "3", "updated_by": "3", "created_at": "2024-01-10T10:00:00.000000Z", "updated_at": "2024-01-10T10:00:00.000000Z", "discount": {"id": 2, "menu_id": 10, "type": "percentage", "value": 10, "created_at": "2024-01-10T10:00:00.000000Z", "updated_at": "2024-01-10T10:00:00.000000Z"}}],
     *       "first_page_url": "https://moboeats.com/api/v1/restaurant/discounts?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://moboeats.com/api/v1/restaurant/discounts?page=1",
     *       "links": [],
     *       "next_page_url": null,
     *       "path": "https://moboeats.com/api/v1/restaurant/discounts",
     *       "per_page": 6,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     },
     *     "menus": [{"id": 10, "uuid": "a1b2c3d4-e5f6-4789-90ab-cdef01234567", "title": "Chicken Burger", "restaurant_id": 1, "status": 2, "restaurant": {"id": 1, "uuid": "5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9", "name": "Mama Oliech", "status": "Approved"}}]
     *   }
     * }
     * @response 422 scenario="Restaurant owner has no restaurant" {"data": "no_restaurant"}
     * @responseField data.discounts object Paginated menus (Laravel paginator) with their `discount`.
     * @responseField data.menus object[] Restaurant owners only: all menus of the owner's restaurants.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $menus = [];

        if (auth()->check()) {
            if (auth()->user()->hasRole('restaurant')) {
                $menu = Menu::with('discount')
                            ->whereHas('discount')
                            ->whereIn('restaurant_id', auth()->user()->restaurants->pluck('id'))
                            ->when($search && $search != '', function ($query) use ($search) {
                                $query->where('title', 'LIKE', '%'.$search.'%');
                            })
                            ->paginate(6);

                $menus = Menu::with('restaurant')->whereIn('restaurant_id', auth()->user()->restaurants->pluck('id'))->get();
            } else if(auth()->user()->hasRole('restaurant employee')) {
                $restaurant = UserRestaurant::where('user_id', auth()->id())->first();

                $menu = Menu::with('discount')
                            ->whereHas('discount')
                            ->where('restaurant_id', $restaurant->resturant_id)
                            ->when($search && $search != '', function ($query) use ($search) {
                                $query->where('title', 'LIKE', '%'.$search.'%');
                            })
                            ->paginate(6);
            } else {
                $menu = Menu::with('discount')->active()
                            ->whereHas('menuPrices', function ($query) {
                                $query->where('status', '2');
                            })
                            ->whereHas('images')
                            ->paginate(6);
            }

            return $this->success([
                'discounts' => $menu,
                'menus' => $menus
            ]);
        } else {
            $menu = Menu::with('discount')->active()
                            ->whereHas('menuPrices', function ($query) {
                                $query->where('status', '2');
                            })
                            ->whereHas('images');

            return new MenuCollection($menu->with(['restaurant', 'menuPrices', 'categories.food_sub_categories', 'images', 'discount'])->paginate());
        }
    }

    /**
     * Create discounts
     *
     * Creates (or replaces) a discount on each of the given menu items. Any existing discount on a menu is deleted first.
     *
     * @bodyParam menu_ids string required JSON-encoded array of menu IDs. Example: [10,11]
     * @bodyParam type string required Discount type. One of: `amount`, `percentage`. Example: percentage
     * @bodyParam value integer required Discount value (amount or percentage). Example: 10
     *
     * @response 200 scenario="Created" {"status": "Request was succesful", "message": "Discount added successfully", "data": ""}
     * @response 422 scenario="Validation error" {"message": "The selected type is invalid.", "errors": {"type": ["The selected type is invalid."]}}
     */
    public function store(Request $request)
    {
        $request->validate([
            'menu_ids' => ['required'],
            'type' => ['required', 'in:amount,percentage'],
            'value' => ['required', 'integer'],
        ]);

        collect(json_decode($request->menu_ids, true))->each(function ($menu) use ($request) {
            $menu = Menu::find($menu);
            if ($menu->discount()->exists()) {
                $menu->discount()->delete();
            }

            $discount = collect($request)->merge(['menu_id' => $menu->id])->forget('menu_ids');

            Discount::create($discount->all());
        });

        return $this->success('', 'Discount added successfully');
    }

    /**
     * Update a discount
     *
     * Updates the type and value of a discount.
     *
     * @urlParam discount integer required The numeric ID of the discount. Example: 2
     * @bodyParam type string required Discount type. One of: `amount`, `percentage`. Example: amount
     * @bodyParam value integer required Discount value. Example: 50
     *
     * @response 200 scenario="Updated" {"status": "Request was succesful", "message": "Discount updated successfully", "data": ""}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\Discount] 99"}
     * @response 422 scenario="Validation error" {"message": "The value field is required.", "errors": {"value": ["The value field is required."]}}
     */
    public function update(Request $request, Discount $discount)
    {
        $request->validate([
            'type' => ['required', 'in:amount,percentage'],
            'value' => ['required', 'integer'],
        ]);

        $discount->update($request->all());

        return $this->success('', 'Discount updated successfully');
    }

    /**
     * Delete a discount
     *
     * Deletes a discount. Note this is a `GET` route.
     *
     * @urlParam discount integer required The numeric ID of the discount. Example: 2
     *
     * @response 200 scenario="Deleted" {"status": "Request was succesful", "message": "Discount deleted successfully", "data": ""}
     * @response 404 scenario="Not found" {"message": "No query results for model [App\\Models\\Discount] 99"}
     */
    public function destroy(Discount $discount)
    {
        $discount->delete();

        return $this->success('', 'Discount deleted successfully');
    }
}

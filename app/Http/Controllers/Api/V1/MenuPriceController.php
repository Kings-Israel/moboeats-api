<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\MenuPriceFilter;
use App\Http\Controllers\Controller;
use App\Models\MenuPrice;
use App\Http\Requests\V1\StoreMenuPriceRequest;
use App\Http\Requests\V1\UpdateMenuPriceRequest;
use App\Http\Resources\V1\MenuPriceCollection;
use App\Http\Resources\V1\MenuPriceResource;
use App\Models\Menu;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * @group Menus
 * @subgroup Menu prices
 *
 * Prices of restaurant menu items. A menu item can have several prices but only one active (status `2`) price;
 * prices are mirrored to Stripe as Stripe prices of the menu item's Stripe product.
 */
class MenuPriceController extends Controller
{
    use HttpResponses;
    /**
     * List menu prices
     *
     * Returns a paginated list (15 per page) of menu prices, optionally filtered.
     *
     * @queryParam uuid[eq] string Filter by exact price uuid. Example: c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f
     * @queryParam menuId[eq] integer Filter by menu item id. Example: 4
     * @queryParam price[eq] number Filter by exact price. Example: 12.5
     * @queryParam description[eq] string Filter by exact description. Example: standard
     * @queryParam status[eq] integer Filter by status (1 = inactive, 2 = active). Example: 2
     * @queryParam createdBy[eq] string Filter by creator email. No-example
     * @queryParam updatedBy[eq] string Filter by last updater email. No-example
     * @queryParam menu boolean When set, eager loads the related menu item. Example: true
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"data":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"links":{"first":"https://moboeats.com/api/v1/restaurant/menu-prices?page=1","last":"https://moboeats.com/api/v1/restaurant/menu-prices?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/restaurant/menu-prices?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"https://moboeats.com/api/v1/restaurant/menu-prices","per_page":15,"to":1,"total":1}}
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     *
     * @responseField data object[] The menu prices.
     * @responseField data[].attributes.price string The price amount.
     * @responseField data[].attributes.status string Status (1 = inactive, 2 = active).
     * @responseField data[].relationships.menu object The menu item the price belongs to.
     */
    public function index(Request $request)
    {
        $filter =  new MenuPriceFilter();
        $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
        $menu = $request->query('menu');

        $menuPrices = MenuPrice::where($filterItems);
        if ($menu) {
            $menuPrices = $menuPrices->with('menu');
        }

        return new MenuPriceCollection($menuPrices->paginate()->appends($request->query()));
    }

    /**
     * Create a menu price
     *
     * Adds a price to a menu item. Only users with role `restaurant` or the `edit partners` permission may call this.
     * When `status` is `2` all other prices of the menu item are set inactive. Creates the menu item's Stripe product
     * if missing and, when `standardPrice` is sent, a Stripe price. The change is logged in the activity log.
     *
     * @bodyParam menu_id integer required The id of the menu item. Example: 4
     * @bodyParam price number required The price (0 to 9999999999.99). Example: 12.5
     * @bodyParam description string required A label for the price (e.g. size). Example: Large
     * @bodyParam status integer Status: 1 = inactive, 2 = active (makes this the only active price). Example: 2
     * @bodyParam standardPrice integer Amount in the smallest currency unit used to create a Stripe price for this menu price. No-example
     *
     * @response 201 scenario="Created" {"data":{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}}
     * @response 401 scenario="Unauthorized role" {"status":"Error has occured...","message":"Unauthorized","data":""}
     * @response 403 scenario="Error (e.g. Stripe or database error)" {"status":"Error has occured...","message":"No such product: 'prod_PqR123'","data":""}
     * @response 422 scenario="Validation error" {"message":"The price field is required.","errors":{"price":["The price field is required."]}}
     *
     * @responseField data.id integer Menu price id.
     * @responseField data.attributes object Price, description and status.
     */
    public function store(StoreMenuPriceRequest $request)
    {
        if (!auth()->user()->hasRole('restaurant') && !auth()->user()->hasPermission('edit partners')) {
            return $this->error('', 'Unauthorized', 401);
        }

        try {
            DB::beginTransaction();

            if ($request->status == 2) {
                $active_price = MenuPrice::where('menu_id', $request->menu_id)->get();

                if ($active_price->count() > 0) {
                    // Make other prices inactive
                    foreach($active_price as $price) {
                        $price->update([
                            'status' => 1
                        ]);
                    }
                }
            }

            $request->merge([
                'created_by' => auth()->user()->email
            ]);

            $menuPrice = MenuPrice::create($request->all());

            $menu = Menu::with('restaurant')->find($request->menu_id);

            // Make request to stripe to store menu item
            $stripe = new \Stripe\StripeClient(config('services.stripe.SECRET_KEY'));

            if ($menu->stripe_product_id) {
                $product = $stripe->products->retrieve($menu->stripe_product_id);
            } else {
                $product = $stripe->products->create([
                    'name' => $menu->title,
                    'description' => $menu->description,
                    'metadata' => [
                        'restaurant_id' => $menu->restaurant->id,
                        'restaurant_name' => $menu->restaurant->name,
                    ]
                ]);

                $menu->update([
                    'stripe_product_id' => $product->id,
                ]);
            }

            if ($request->has('standardPrice')) {
                $price = $stripe->prices->create([
                    'unit_amount' => $request->standardPrice,
                    'product' => $product['id']
                ]);

                $menuPrice->update([
                    'stripe_price_id' => $price->id
                ]);
            }

            activity()->causedBy(auth()->user())->performedOn($menuPrice->menu)->log('added menu price at '.$request->price);

            DB::commit();

            return new MenuPriceResource($menuPrice);

        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Get a menu price
     *
     * Returns a single menu price, looked up by uuid.
     *
     * @urlParam uuid string required The uuid of the menu price. Example: c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f
     *
     * @response 200 scenario="Success" {"data":{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\MenuPrice] c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f"}
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     *
     * @responseField data.attributes.price string The price amount.
     * @responseField data.relationships.menu object The menu item the price belongs to.
     */
    public function show(MenuPrice $menuPrice)
    {
        return new MenuPriceResource($menuPrice);
    }

    /**
     * Update a menu price
     *
     * Updates a menu price. Available as `PUT/PATCH /restaurant/menu-prices/{uuid}` (by uuid) and
     * `POST /restaurant/menu-prices/{id}/update` (by numeric id). When `status` is `2` the other prices of the menu
     * item are set inactive. If the price has no Stripe price yet, one is created (in GBP). The change is logged.
     *
     * @bodyParam price number The new price (0 to 9999999999.99). Example: 14
     * @bodyParam description string A label for the price. Example: standard
     * @bodyParam status integer Status: 1 = inactive, 2 = active (makes this the only active price). Example: 2
     *
     * @response 200 scenario="Updated" {"data":{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}}
     * @response 403 scenario="Error (e.g. Stripe or database error)" {"status":"Error has occured...","message":"No such product: 'prod_PqR123'","data":""}
     * @response 422 scenario="Validation error" {"message":"The price field must be a number.","errors":{"price":["The price field must be a number."]}}
     *
     * @responseField data object The updated menu price.
     */
    public function update(UpdateMenuPriceRequest $request, MenuPrice $menuPrice = NULL, $id = NULL)
    {
        if ($menuPrice == NULL) {
            $menuPrice = MenuPrice::find($id);
        }

        try {
            DB::beginTransaction();

            if ($request->status == 2) {
                $active = MenuPrice::where('menu_id', $menuPrice->menu->id)
                                    ->where('status', 2)
                                    ->where('id', '!=', $menuPrice->id)
                                    ->get();

                if ($active->count() > 0) {
                    // Make other prices inactive
                    foreach($active as $price) {
                        $price->update([
                            'status' => 1
                        ]);
                    }
                }
            }

            $request->merge([
                'updated_by' => auth()->user()->email
            ]);

            $current_price = $menuPrice->price;

            $menuPrice->update($request->all());

            // Make request to stripe to store menu item
            $stripe = new \Stripe\StripeClient(config('services.stripe.SECRET_KEY'));

            if ($menuPrice->stripe_price_id) {
                // $stripe->prices->update($menuPrice->stripe_price_id, $request->price);
            } else {
                $menu = Menu::with('restaurant')->find($menuPrice->menu->id);

                if ($menu->stripe_product_id) {
                    $product = $stripe->products->retrieve($menu->stripe_product_id);
                } else {
                    $product = $stripe->products->create([
                        'name' => $menu->title,
                        'description' => $menu->description,
                        'metadata' => [
                            'restaurant_id' => $menu->restaurant->id,
                            'restaurant_name' => $menu->restaurant->name,
                        ]
                    ]);

                    $menu->update([
                        'stripe_product_id' => $product->id,
                    ]);
                }

                $price = $stripe->prices->create([
                    'unit_amount' => $request->price,
                    'product' => $product['id'],
                    'currency' => 'gbp'
                ]);

                $menuPrice->update([
                    'stripe_price_id' => $price->id
                ]);
            }


            activity()->causedBy(auth()->user())->performedOn($menuPrice->menu)->log('update menu price from '.$current_price.' to '.$request->price);

            DB::commit();

            return new MenuPriceResource($menuPrice);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Delete a menu price
     *
     * Deletes a menu price. Available as `DELETE /restaurant/menu-prices/{uuid}` (by uuid) and
     * `DELETE /restaurant/menu-prices/{id}/delete` (by numeric id). A menu item must keep at least one price.
     * Returns the deleted price (with its menu item). The deletion is logged.
     *
     * @response 200 scenario="Deleted" {"status":"Request was succesful","message":null,"data":{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","menu_id":4,"description":"standard","price":"12.50","status":2,"created_by":"owner@example.com","updated_by":null,"stripe_price_id":"price_1PqR123","created_at":"2024-05-01T10:00:00.000000Z","updated_at":"2024-05-01T10:00:00.000000Z","menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger","description":"Grilled chicken burger with fries","restaurant_id":3,"status":2,"preparation_time":"20","created_by":"owner@example.com","updated_by":"owner@example.com","stripe_product_id":"prod_PqR123","created_at":"2024-05-01T10:00:00.000000Z","updated_at":"2024-05-01T10:00:00.000000Z"}}}
     * @response 403 scenario="Last price of the menu item" {"status":"Error has occured...","message":"Must have at least one menu price","data":""}
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     *
     * @responseField data object The deleted menu price, including its `menu`.
     */
    public function destroy(MenuPrice $menuPrice = NULL, $id = NULL)
    {
        if (!$menuPrice) {
            $menuPrice = MenuPrice::find($id);
        }

        $menuPrices = MenuPrice::where('menu_id', $menuPrice->menu->id)->count();

        if ($menuPrices > 1) {
            $menuPrice->delete();
        } else {
            return $this->error('', 'Must have at least one menu price', 403);
        }

        $menuPrices = MenuPrice::where('menu_id', $menuPrice->menu->id)->count();

        if ($menuPrices == 1) {
            $menuPrices = MenuPrice::where('menu_id', $menuPrice->menu->id)->first()->update(['status', 2]);
        }

        activity()->causedBy(auth()->user())->performedOn($menuPrice->menu)->log('deleted a menu price '.$menuPrice->price);

        return $this->success($menuPrice);
    }
}

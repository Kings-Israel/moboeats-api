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
 * @group Menu Prices Management
 *
 * Food Menu Prices API resource
 */
class MenuPriceController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuPriceRequest $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'restaurant') {
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
    }

    /**
     * Display the specified resource.
     */
    public function show(MenuPrice $menuPrice)
    {
        return new MenuPriceResource($menuPrice);
    }

    /**
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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

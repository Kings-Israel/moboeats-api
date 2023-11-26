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

class DiscountController extends Controller
{
    use HttpResponses;

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

    public function update(Request $request, Discount $discount)
    {
        $request->validate([
            'type' => ['required', 'in:amount,percentage'],
            'value' => ['required', 'integer'],
        ]);

        $discount->update($request->all());

        return $this->success('', 'Discount updated successfully');
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();

        return $this->success('', 'Discount deleted successfully');
    }
}

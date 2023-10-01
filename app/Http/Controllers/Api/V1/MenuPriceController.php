<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\MenuPriceFilter;
use App\Http\Controllers\Controller;
use App\Models\MenuPrice;
use App\Http\Requests\V1\StoreMenuPriceRequest;
use App\Http\Requests\V1\UpdateMenuPriceRequest;
use App\Http\Resources\V1\MenuPriceCollection;
use App\Http\Resources\V1\MenuPriceResource;
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

                $menuPrice = MenuPrice::create($request->all());
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
    public function update(UpdateMenuPriceRequest $request, MenuPrice $menuPrice)
    {
        try {
            DB::beginTransaction();
            // info($request->all());
            if ($request->status == 2) {
                $active = MenuPrice::where('menu_id', $request->menu_id)
                ->where('status', $request->status)
                ->first();
                if ($active) {
                    return $this->error('', 'Only one price type can be active at a time.', 403);
                }
            }
            $menuPrice->update($request->all());
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
    public function destroy(MenuPrice $menuPrice)
    {
        //
    }
}

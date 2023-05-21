<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\MenuFilter;
use App\Models\Menu;
use App\Http\Requests\V1\StoreMenuRequest;
use App\Http\Requests\V1\UpdateMenuRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MenuCollection;
use App\Http\Resources\V1\MenuResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter =  new MenuFilter();
        $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
        $includeCategories = $request->query('categories');
        $includesubCategories = $request->query('subCategories');

        $menu = Menu::where($filterItems);

        if ($includesubCategories &&  $includeCategories) {
            $menu = $menu->with(['categories', 'subCategories']);
        } else {
            if ($includeCategories) {
                $menu = $menu->with('categories');
            }
            if ($includesubCategories) {
                $menu = $menu->with('subCategories');
            }
        }
       
        return new MenuCollection($menu->paginate()->appends($request->query()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuRequest $request)
    {
        // info($request);
        
        try {
            DB::beginTransaction();
            $menu = Menu::create($request->all());
            foreach ($request->input('categoryIds') as $foodCategoryId) {
                $menu->categories()->attach($foodCategoryId, [
                     // Add more pivot table attributes here
                    'uuid' => Str::uuid(),
                    'created_by' => 'info@moboeats.com',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }

            foreach ($request->input('subcategoryIds') as $subCategoryId) {
                $menu->subCategories()->attach($subCategoryId, [
                     // Add more pivot table attributes here
                    'uuid' => Str::uuid(),
                    'created_by' => 'info@moboeats.com',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }

            DB::commit();
            return new MenuResource($menu);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Menu $menu)
    {
        $includeCategories = request()->query('categories');
        $includesubCategories = request()->query('subCategories');

        if ($includesubCategories &&  $includeCategories) {
            return new MenuResource($menu->loadMissing(['categories', 'subCategories']));
        } else {
            if ($includeCategories) {
                return new MenuResource($menu->loadMissing('categories'));
            }
            if ($includesubCategories) {
                return new MenuResource($menu->loadMissing('subCategories'));
            }
        }
        return new MenuResource($menu);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuRequest $request, Menu $menu)
    {

        try {
            DB::beginTransaction();
       
            $menu->update($request->all());
            $foodCategoryIds = $request->input('categoryIds');
            $subcategoryIds = $request->input('subcategoryIds');

            $syncData = [];
            $syncData2 = [];
            foreach ($foodCategoryIds as $foodCategoryId) {
                $syncData[$foodCategoryId] = [
                    'uuid' => Str::uuid(),
                    'created_by' => 'info@moboeats.com',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ];
            }
            foreach ($subcategoryIds as $categoryId) {
                $syncData2[$categoryId] = [
                    'uuid' => Str::uuid(),
                    'created_by' => 'info@moboeats.com',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ];
            }
            $menu->categories()->sync($syncData);
            $menu->subCategories()->sync($syncData2);
            DB::commit();
            return new MenuResource($menu);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        //
    }
}

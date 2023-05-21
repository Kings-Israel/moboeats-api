<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\FoodCommonCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\FoodCommonCategoryCollection;
use App\Http\Resources\V1\FoodCommonCategoryResource;
use App\Filters\V1\FoodCommonCategoryFilter;
use App\Http\Requests\V1\StoreFoodCommonCategoryRequest;
use App\Http\Requests\V1\UpdateFoodCommonCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FoodCommonCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // return FoodCommonCategory::all();
        $filter =  new FoodCommonCategoryFilter();
        $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
        $includeSubcategories = $request->query('includeSubcategories');

        $categories = FoodCommonCategory::where($filterItems);
        if ($includeSubcategories) {
            $categories = $categories->with('food_sub_categories');
        }
       
        return new FoodCommonCategoryCollection($categories->paginate()->appends($request->query()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFoodCommonCategoryRequest $request)
    {
        try {
            DB::beginTransaction();
            $food_category = FoodCommonCategory::create($request->all());
            DB::commit();

            return new FoodCommonCategoryResource($food_category);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(FoodCommonCategory $food_category)
    {
        return new FoodCommonCategoryResource($food_category);
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFoodCommonCategoryRequest $request, FoodCommonCategory $food_category)
    {
        try {
            DB::beginTransaction();
            $food_category->update($request->all());
            DB::commit();

            return new FoodCommonCategoryResource($food_category);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FoodCommonCategory $food_category)
    {
        //
    }
}

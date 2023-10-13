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
use App\Traits\HttpResponses;
/**
 * @group Food Categories Management
 *
 * Food Category API resource
 */

class FoodCommonCategoryController extends Controller
{
    use HttpResponses;
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
            $request->merge([
                'created_by' => auth()->user()->email,
                'status' => 2
            ]);
            $food_category = FoodCommonCategory::create(collect($request->all())->except('image'));
            if ($request->hasFile('image')) {
                $food_category->update([
                    'image' => pathinfo($request->image->store('images', 'category'), PATHINFO_BASENAME)
                ]);
            }
            DB::commit();

            // return new FoodCommonCategoryResource($food_category);
            $categories = FoodCommonCategory::paginate(7);
            return $this->success($categories);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
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
    public function update(UpdateFoodCommonCategoryRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $food_category = FoodCommonCategory::find($id);
            
            $food_category->update([
                'title' => $request->title,
                'description' => $request->description
            ]);

            if ($request->hasFile('image')) {
                $food_category->update([
                    'image' => pathinfo($request->image->store('images', 'category'), PATHINFO_BASENAME)
                ]);
            }
            DB::commit();

            // return new FoodCommonCategoryResource($food_category);
            $categories = FoodCommonCategory::paginate(7);
            return $this->success($categories);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
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

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
     * Show List of categories
     */
    public function index(Request $request)
    {
        $filter =  new FoodCommonCategoryFilter();
        $filterItems = $filter->transform($request);
        $includeSubcategories = $request->query('includeSubcategories');

        $categories = FoodCommonCategory::whereHas('menus', function ($query) {
            $query->where('status', 2)
                ->whereHas('menuPrices', function ($query) {
                    $query->where('status', 2);
                });
        })->where($filterItems);

        $categories = $categories->with([
            'food_sub_categories',
            'menus' => function ($query) {
                $query->whereHas('restaurant', function ($query) {
                        $query->inOperation()->approved();
                    })
                    ->whereHas('menuPrices', function ($query) {
                        $query->where('status', 2);
                    });
            }
        ]);

        return new FoodCommonCategoryCollection($categories->paginate()->appends($request->query()));
    }

    public function store(StoreFoodCommonCategoryRequest $request)
    {
        try {
            DB::beginTransaction();
            $request->merge([
                'created_by' => auth()->user()->email,
                'status' => 2
            ]);
            $food_category = FoodCommonCategory::create(collect($request->all())->except('image')->toArray());
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
     * Show category details
     */
    public function show(FoodCommonCategory $food_common_category)
    {
        return new FoodCommonCategoryResource(
            $food_common_category->load([
                'food_sub_categories',
                'menus' => function ($query) {
                    $query->whereHas('restaurant', function ($query) {
                        $query->inOperation()->approved();
                    })
                    ->whereHas('menuPrices', function ($query) {
                        $query->where('status', 2);
                    });
                }
        ]));
    }

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

    }
}

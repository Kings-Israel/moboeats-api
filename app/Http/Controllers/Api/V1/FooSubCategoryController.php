<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\FoodSubCategoryFilter;
use App\Models\FooSubCategory;
use App\Http\Requests\V1\StoreFooSubCategoryRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\BulkStoreFooSubCategoryRequest;
use App\Http\Requests\V1\UpdateFooSubCategoryRequest;
use App\Http\Resources\V1\FooSubCategoryCollection;
use App\Http\Resources\V1\FooSubCategoryResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FooSubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // return FoodCommonCategory::all();
        $filter =  new FoodSubCategoryFilter();
        $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
        $includeSubcategories = $request->query('includeSubcategories');

        $categories = FooSubCategory::where($filterItems);
        if ($includeSubcategories) {
            $categories = $categories->with('food_sub_categories');
        }
       
        return new FooSubCategoryCollection($categories->paginate()->appends($request->query()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFooSubCategoryRequest $request)
    {
        try {
            DB::beginTransaction();
            $foodSubCategory = FooSubCategory::create($request->all());

            foreach ($request->input('categoryIds') as $foodCategoryId) {
                $foodSubCategory->foodCategories()->attach($foodCategoryId, [
                     // Add more pivot table attributes here
                    'uuid' => Str::uuid(),
                    'created_by' => 'info@moboeats.com',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }
            DB::commit();
            return new FooSubCategoryResource($foodSubCategory);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
        
    }

    public function bulkStore(BulkStoreFooSubCategoryRequest $request)
    {
        try {
            DB::beginTransaction();

            $bulk = collect($request->all())->map(function($arr, $key) {
                return Arr::except($arr, ['createdBy', 'categoryIds']);
            });
            $subCategoriesData = $bulk->toArray();

            $createdSubCategories = [];
    
            foreach ($subCategoriesData as $subCategoryData) {
                $subCategory = FooSubCategory::create($subCategoryData);
    
                $foodCategoryIds = $subCategoryData['category_ids'];
    
                $subCategory->foodCategories()->attach($foodCategoryIds, [
                    'uuid' => Str::uuid(),
                    'created_by' => 'info@moboeats.com',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
    
                $createdSubCategories[] = $subCategory;
            }
            // $foodSubCategory = FooSubCategory::create($request->all());
            // FooSubCategory::insert($bulk->toArray());
            DB::commit();
            return true;

        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
       
    }

    /**
     * Display the specified resource.
     */
    public function show(FooSubCategory $food_sub_category)
    {
        return new FooSubCategoryResource($food_sub_category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFooSubCategoryRequest $request, FooSubCategory $food_sub_category)
    {
       try {
            DB::beginTransaction();
       
            $food_sub_category->update($request->all());
            $foodCategoryIds = $request->input('categoryIds');

            $syncData = [];

            foreach ($foodCategoryIds as $foodCategoryId) {
                $syncData[$foodCategoryId] = [
                    'uuid' => Str::uuid(),
                    'created_by' => 'info@moboeats.com',
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ];
            }
            $food_sub_category->foodCategories()->sync($syncData);
            DB::commit();
            // return $food_sub_category;
            return new FooSubCategoryResource($food_sub_category);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FooSubCategory $fooSubCategory)
    {
        //
    }
}

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

/**
 * @group Food Categories
 * @subgroup Sub categories
 *
 * Food sub categories (e.g. "Beef Burgers" under "Burgers"). A sub category can belong to several food categories.
 */

class FooSubCategoryController extends Controller
{

    /**
     * List food sub categories
     *
     * Returns a paginated list (15 per page) of sub categories that have at least one active menu item with an
     * active price. Available publicly (`/sub-categories`, `/orderer/orderer-food-sub-categories`) and to restaurant
     * users (`/restaurant/food-sub-categories`).
     *
     * @queryParam uuid[eq] string Filter by exact sub category uuid. Example: 7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d
     * @queryParam title[eq] string Filter by exact title. Example: Beef Burgers
     * @queryParam description[eq] string Filter by exact description. No-example
     * @queryParam status[eq] integer Filter by status (1 = inactive, 2 = active). Example: 2
     * @queryParam createdBy[eq] string Filter by creator. No-example
     * @queryParam updatedBy[eq] string Filter by last updater. No-example
     * @queryParam includeSubcategories boolean Tries to eager load a `food_sub_categories` relation; sub categories have no such relation, so this currently causes a server error. No-example
     * @queryParam page integer Page number (15 items per page). Example: 1
     *
     * @response 200 scenario="Success" {"data":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}],"links":{"first":"https://moboeats.com/api/v1/sub-categories?page=1","last":"https://moboeats.com/api/v1/sub-categories?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/sub-categories?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"https://moboeats.com/api/v1/sub-categories","per_page":15,"to":1,"total":1}}
     * @response 422 scenario="Restaurant user without a restaurant (restaurant route only)" {"data":"no_restaurant"}
     *
     * @responseField data object[] The sub categories.
     * @responseField data[].image string Full URL of the sub category image (a default image when none is set).
     * @responseField meta object Pagination metadata.
     */
    public function index(Request $request)
    {
        $filter =  new FoodSubCategoryFilter();
        $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
        $includeSubcategories = $request->query('includeSubcategories');

        $categories = FooSubCategory::whereHas('menus', function ($query) {
            $query->where('status', 2)
                ->whereHas('menuPrices', function ($query) {
                    $query->where('status', 2);
                });
        })->where($filterItems);

        if ($includeSubcategories) {
            $categories = $categories->with('food_sub_categories');
        }

        return new FooSubCategoryCollection($categories->paginate()->appends($request->query()));
    }

    /**
     * Create a food sub category
     *
     * Creates a sub category, stores the optional image and attaches it to the given food categories.
     *
     * @bodyParam title string required The sub category title. Example: Beef Burgers
     * @bodyParam description string required The sub category description. Example: Beef patty burgers
     * @bodyParam status integer Status (1 = inactive, 2 = active). Example: 2
     * @bodyParam createdBy string required Who created the sub category (usually the user's email). Example: owner@example.com
     * @bodyParam categoryIds integer[] required Ids of the food categories this sub category belongs to. Example: [1, 2]
     * @bodyParam image file An image (png or jpg, max 3000 KB). No-example
     *
     * @response 201 scenario="Created" {"data":{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}}
     * @response 422 scenario="Validation error" {"message":"The title field is required.","errors":{"title":["The title field is required."]}}
     * @response 500 scenario="Server error (error helper is not available in this controller)" {"message":"Call to undefined method App\\Http\\Controllers\\Api\\V1\\FooSubCategoryController::error()"}
     *
     * @responseField data.id integer Sub category id.
     * @responseField data.uuid string Sub category uuid.
     */
    public function store(StoreFooSubCategoryRequest $request)
    {
        try {
            DB::beginTransaction();
            $foodSubCategory = FooSubCategory::create(collect($request->all())->except('image')->toArray());

            if ($request->hasFile('image')) {
                $foodSubCategory->update([
                    'image' => pathinfo($request->image->store('subcategory', 'category'), PATHINFO_BASENAME)
                ]);
            }

            foreach ($request->input('categoryIds') as $foodCategoryId) {
                $foodSubCategory->foodCategories()->attach($foodCategoryId, [
                     // Add more pivot table attributes here
                    'uuid' => Str::uuid(),
                    'created_by' => $request->createdBy,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }
            DB::commit();
            return new FooSubCategoryResource($foodSubCategory);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }

    }

    /**
     * Bulk create food sub categories
     *
     * Creates several sub categories at once. The request body is a JSON array; each item is created and attached
     * to its `categoryIds`. Only users with a restaurant may call this.
     *
     * @bodyParam [] object[] required The sub categories to create.
     * @bodyParam [].title string required The sub category title. Example: Beef Burgers
     * @bodyParam [].description string required The sub category description. Example: Beef patty burgers
     * @bodyParam [].status integer Status (1 = inactive, 2 = active). Example: 2
     * @bodyParam [].createdBy string required Who created the sub category. Example: owner@example.com
     * @bodyParam [].categoryIds integer[] required Ids of the food categories the sub category belongs to. Example: [1]
     *
     * @response 200 scenario="Created" 1
     * @response 422 scenario="Validation error" {"message":"The 0.title field is required.","errors":{"0.title":["The 0.title field is required."]}}
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     * @response 500 scenario="Server error (error helper is not available in this controller)" {"message":"Call to undefined method App\\Http\\Controllers\\Api\\V1\\FooSubCategoryController::error()"}
     */
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
                    'created_by' => $request->createdBy,
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
            return $this->error('', $th->getMessage(), 403);
        }

    }

    /**
     * Get a food sub category
     *
     * Returns a sub category (looked up by uuid) with its active menu items that have an active price. Note: on
     * `/orderer/orderer-food-sub-categories/{id}` the route placeholder does not match the bound argument, so an empty
     * sub category is returned. The path placeholder (`{food_sub_category}` / `{id}`) is the sub category uuid,
     * e.g. `7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d`.
     *
     * @response 200 scenario="Success" {"data":{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"menus":[{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"orderItems":[],"orders_count":0,"total_orders_value":0}}]}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\FooSubCategory] 7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d"}
     *
     * @responseField data.menus object[] Active menu items in the sub category.
     */
    public function show(FooSubCategory $food_sub_category)
    {
        return new FooSubCategoryResource(
            $food_sub_category->load(['menus' => function ($query) {
                $query->where('status', 2)
                    ->whereHas('menuPrices', function ($query) {
                        $query->where('status', 2);
                    });
            }])
        );
    }

    /**
     * Update a food sub category
     *
     * Updates a sub category (looked up by uuid), optionally replaces its image and re-syncs the food categories it
     * belongs to. With `PUT`, `title`, `description` and `categoryIds` are required. If anything fails the change is
     * rolled back and an empty 200 response is returned. Note: only `/restaurant/food-sub-categories/{id}` binds the
     * sub category; on the other routes the placeholder does not match the bound argument.
     *
     * @urlParam id string required The uuid of the sub category. Example: 7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d
     *
     * @bodyParam title string The sub category title. Required for PUT requests. Example: Beef Burgers
     * @bodyParam description string The sub category description. Required for PUT requests. Example: Beef patty burgers
     * @bodyParam status integer Status (1 = inactive, 2 = active). Example: 2
     * @bodyParam updatedBy string required Who updated the sub category (usually the user's email). Example: owner@example.com
     * @bodyParam categoryIds integer[] Ids of the food categories the sub category belongs to. Required for PUT requests and needed by the code in practice. Example: [1, 2]
     * @bodyParam image file A new image (png or jpg, max 3000 KB). No-example
     *
     * @response 200 scenario="Updated" {"data":{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}}
     * @response 200 scenario="Update failed (rolled back)" ""
     * @response 422 scenario="Validation error" {"message":"The updated by field is required.","errors":{"updatedBy":["The updated by field is required."]}}
     *
     * @responseField data object The updated sub category.
     */
    public function update(UpdateFooSubCategoryRequest $request, FooSubCategory $food_sub_category)
    {
       try {
            DB::beginTransaction();

            $food_sub_category->update(collect($request->all())->except('image')->toArray());

            if ($request->hasFile('image')) {
                $food_sub_category->update([
                    'image' => pathinfo($request->image->store('subcategory', 'category'), PATHINFO_BASENAME)
                ]);
            }

            $foodCategoryIds = $request->input('categoryIds');

            $syncData = [];

            foreach ($foodCategoryIds as $foodCategoryId) {
                $syncData[$foodCategoryId] = [
                    'uuid' => Str::uuid(),
                    'created_by' => $request->updatedBy,
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
     * Delete a food sub category (not implemented)
     *
     * This endpoint is routed but not implemented: it performs no action and returns an empty 200 response.
     *
     * @urlParam id string required The uuid of the sub category. Example: 7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d
     *
     * @response 200 scenario="No action taken" ""
     */
    public function destroy(FooSubCategory $fooSubCategory)
    {
        //
    }
}

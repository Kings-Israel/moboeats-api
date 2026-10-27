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
 * @group Food Categories
 *
 * Food (menu) categories such as "Burgers" or "Groceries". Categories group menu items and own a set of
 * sub categories. Global categories have no `restaurant_id`; restaurant specific ones belong to a restaurant.
 */

class FoodCommonCategoryController extends Controller
{
    use HttpResponses;
    /**
     * List food categories
     *
     * Returns a paginated list (15 per page) of food categories that have at least one active menu item with an
     * active price. Each category includes its sub categories and its menu items that belong to an approved,
     * operating restaurant and have an active price. Available publicly (`/categories`, `/orderer/orderer-food-categories`)
     * and to restaurant users (`/restaurant/food-categories`).
     *
     * @queryParam uuid[eq] string Filter by exact category uuid. Example: 0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70
     * @queryParam title[eq] string Filter by exact category title. Example: Burgers
     * @queryParam description[eq] string Filter by exact description. No-example
     * @queryParam status[eq] integer Filter by status (1 = inactive, 2 = active). Example: 2
     * @queryParam createdBy[eq] string Filter by creator email. No-example
     * @queryParam updatedBy[eq] string Filter by last updater email. No-example
     * @queryParam page integer Page number (15 items per page). Example: 1
     * @queryParam includeSubcategories boolean Accepted for backwards compatibility but ignored; sub categories are always included. Example: true
     *
     * @response 200 scenario="Success" {"data":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"image":"https://moboeats.com/storage/category/images/burgers.png","subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}],"menus":[{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"orderItems":[],"orders_count":0,"total_orders_value":0}}]}],"links":{"first":"https://moboeats.com/api/v1/categories?page=1","last":"https://moboeats.com/api/v1/categories?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/categories?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"https://moboeats.com/api/v1/categories","per_page":15,"to":1,"total":1}}
     * @response 422 scenario="Restaurant user without a restaurant (restaurant route only)" {"data":"no_restaurant"}
     *
     * @responseField data object[] The food categories.
     * @responseField data[].subCategories object[] Sub categories of the category.
     * @responseField data[].menus object[] Active menu items in the category.
     * @responseField meta object Pagination metadata.
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

    /**
     * Create a food category
     *
     * Creates a new active food category (status is always set to `2`) and stores the optional image. The creator is
     * taken from the authenticated user, so the call fails with 403 when no user is authenticated (the public
     * `/categories` and `/orderer/orderer-food-categories` routes have no auth middleware). Returns the first page of
     * all categories (7 per page). Also used by admins via `POST /admin/categories/add`.
     *
     * @bodyParam title string required The category title. Example: Burgers
     * @bodyParam description string required The category description. Example: Burgers and sandwiches
     * @bodyParam status integer Ignored; new categories are always created active (2). Example: 2
     * @bodyParam restaurant_id integer The id of the restaurant the category belongs to. Leave empty for a global category. Example: 3
     * @bodyParam image file An image for the category. No-example
     *
     * @response 200 scenario="Created" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"created_by":"admin@moboeats.com","updated_by":null,"image":"https://moboeats.com/storage/category/images/burgers.png","restaurant_id":null,"created_at":"2024-05-01T10:00:00.000000Z","updated_at":"2024-05-01T10:00:00.000000Z"}],"first_page_url":"https://moboeats.com/api/v1/restaurant/food-categories?page=1","from":1,"last_page":1,"last_page_url":"https://moboeats.com/api/v1/restaurant/food-categories?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/restaurant/food-categories?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"https://moboeats.com/api/v1/restaurant/food-categories","per_page":7,"prev_page_url":null,"to":1,"total":1}}
     * @response 403 scenario="Error (e.g. unauthenticated or database error)" {"status":"Error has occured...","message":"Attempt to read property \"email\" on null","data":""}
     * @response 422 scenario="Validation error" {"message":"The title field is required.","errors":{"title":["The title field is required."]}}
     *
     * @responseField data object Laravel paginator (7 per page) of all food categories.
     * @responseField data.data object[] The categories on the current page.
     */
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
     * Get a food category
     *
     * Returns a single food category with its sub categories and its menu items that belong to an approved,
     * operating restaurant and have an active price. On `GET /categories/{food_common_category}` the category is
     * looked up by uuid. Note: on the resource routes (`/orderer/orderer-food-categories/{id}`,
     * `/restaurant/food-categories/{id}`) the route placeholder does not match the bound argument, so an empty category is returned.
     * The path placeholder (`{food_common_category}` / `{id}`) is the category uuid, e.g. `0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70`.
     *
     * @response 200 scenario="Success" {"data":{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"image":"https://moboeats.com/storage/category/images/burgers.png","subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}],"menus":[{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"orderItems":[],"orders_count":0,"total_orders_value":0}}]}}
     * @response 404 scenario="Not found" {"message":"No query results for model [App\\Models\\FoodCommonCategory] 0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70"}
     *
     * @responseField data.subCategories object[] Sub categories of the category.
     * @responseField data.menus object[] Active menu items in the category.
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

    /**
     * Update a food category
     *
     * Updates the title, description and (optionally) the image of a food category, then returns the first page of
     * all categories (7 per page). With `PUT` both `title` and `description` are required; with `PATCH`/`POST` they
     * are optional (omitted values are saved as null). Also used by admins via `POST /admin/categories/update/{id}`.
     *
     * @urlParam id integer required The numeric id of the food category. Example: 1
     *
     * @bodyParam title string The category title. Required for PUT requests. Example: Burgers
     * @bodyParam description string The category description. Required for PUT requests. Example: Burgers and sandwiches
     * @bodyParam image file A new image for the category. No-example
     *
     * @response 200 scenario="Updated" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"created_by":"admin@moboeats.com","updated_by":null,"image":"https://moboeats.com/storage/category/images/burgers.png","restaurant_id":null,"created_at":"2024-05-01T10:00:00.000000Z","updated_at":"2024-05-01T10:00:00.000000Z"}],"first_page_url":"https://moboeats.com/api/v1/restaurant/food-categories?page=1","from":1,"last_page":1,"last_page_url":"https://moboeats.com/api/v1/restaurant/food-categories?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/restaurant/food-categories?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"https://moboeats.com/api/v1/restaurant/food-categories","per_page":7,"prev_page_url":null,"to":1,"total":1}}
     * @response 403 scenario="Category not found or other error" {"status":"Error has occured...","message":"Call to a member function update() on null","data":""}
     * @response 422 scenario="Validation error" {"message":"The title field is required.","errors":{"title":["The title field is required."]}}
     *
     * @responseField data object Laravel paginator (7 per page) of all food categories.
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
     * Delete a food category (not implemented)
     *
     * This endpoint is routed but not implemented: it performs no action and returns an empty 200 response.
     *
     * @urlParam id string required The uuid of the food category. Example: 0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70
     *
     * @response 200 scenario="No action taken" ""
     */
    public function destroy(FoodCommonCategory $food_category)
    {

    }
}

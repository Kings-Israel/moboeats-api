<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\MenuFilter;
use App\Models\Menu;
use App\Http\Requests\V1\StoreMenuRequest;
use App\Http\Requests\V1\UpdateMenuRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\FoodCommonCategoryCollection;
use App\Http\Resources\V1\MenuCollection;
use App\Http\Resources\V1\MenuResource;
use App\Models\CategoryMenu;
use App\Models\FoodCommonCategory;
use App\Models\MenuImage;
use App\Models\MenuPrice;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\UserRestaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\Admin\UploadFileTrait;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MenuExport;
use App\Exports\GroceryExport;
use App\Http\Resources\V1\FoodCommonCategoryResource;
use App\Http\Resources\V1\FooSubCategoryResource;
use App\Http\Resources\V1\ReviewResource;
use App\Imports\MenuItemsImport;
use App\Models\FooSubCategory;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Excel as ExcelExcel;

/**
 * @group Menus
 *
 * Restaurant menu items (dishes and grocery products): browsing for customers, and management (create, update,
 * images, prices, import/export, reviews) for restaurant owners. Grocery items are menu items in the "groceries" category.
 */
class MenuController extends Controller
{
    use UploadFileTrait;
    use HttpResponses;

    public $settings = [
        'model' =>  '\\App\\Models\\Menu',
        'caption' =>  "Menu",
        'storageName' =>  "menus",
    ];

    /**
     * List menu items
     *
     * Returns a paginated list of menu items with their restaurant, prices, categories (with sub categories),
     * images and discount. Guests and customers get active menu items with an active price and at least one image from
     * approved restaurants, limited to restaurants within 1000 km when coordinates are known (query `lat`/`lng`, or the
     * customer's saved location). On the authenticated `/restaurant/menu` route, users with role `restaurant` get all
     * menu items of their own restaurants and `restaurant employee` users get their restaurant's items.
     *
     * @queryParam lat number Latitude used to find nearby restaurants. Example: 51.5072
     * @queryParam lng number Longitude used to find nearby restaurants. Example: -0.1276
     * @queryParam per_page integer Items per page. Defaults to 100. Example: 20
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"data":[{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"categories":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"image":"https://moboeats.com/storage/category/images/burgers.png","subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}]}],"subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}],"images":[{"id":7,"uuid":"5a3e9d1c-2b4f-4e6a-8c7d-1f2e3a4b5c6d","attributes":{"imageUrl":"chicken-burger-65f1a2b3c4d5e.jpg","sequence":1,"status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"discount":null,"orderItems":[],"orders_count":0,"total_orders_value":0}}],"links":{"first":"https://moboeats.com/api/v1/menu?page=1","last":"https://moboeats.com/api/v1/menu?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/menu?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"https://moboeats.com/api/v1/menu","per_page":100,"to":1,"total":1}}
     * @response 422 scenario="Restaurant user without a restaurant (restaurant route only)" {"data":"no_restaurant"}
     *
     * @responseField data object[] The menu items.
     * @responseField data[].attributes object Title, description, status, preparation time and average rating.
     * @responseField data[].relationships.menuPrices object[] Prices of the menu item.
     * @responseField data[].relationships.restaurant object The restaurant selling the item.
     * @responseField meta object Pagination metadata.
     */
    public function index(Request $request)
    {
        $radius = 1000;
        $latitude = $request->query('lat');
        $longitude = $request->query('lng');

        $per_page = $request->query('per_page');

        if (!$per_page || $per_page == '') {
            $per_page = 100;
        }

        if (auth()->check()) {
            $filter =  new MenuFilter();
            $filterItems = $filter->transform($request);

            if (auth()->user()->hasRole('restaurant')) {
                $menu = Menu::whereIn('restaurant_id', auth()->user()->restaurants->pluck('id'));
            } else if(auth()->user()->hasRole('restaurant employee')) {
                $search = $request->query('search');

                $restaurant = UserRestaurant::where('user_id', auth()->id())->first();

                $menu = Menu::where('restaurant_id', $restaurant->resturant_id);
            } else {
                $user_latitude = $latitude ? $latitude : auth()->user()->latitude;
                $user_longitude = $longitude ? $longitude : auth()->user()->longitude;
                $menu = Menu::active()
                            ->hasActivePrices()
                            ->whereHas('restaurant', function ($query) use ($user_latitude, $user_longitude, $radius) {
                                $query->where('status', '2')
                                    ->when($user_latitude && $user_longitude && $user_latitude != '' && $user_longitude != '', function ($query) use ($user_latitude, $user_longitude, $radius) {
                                        $query->select(DB::raw("*,
                                                (6371 * acos(cos(radians($user_latitude))
                                                * cos(radians(latitude))
                                                * cos(radians(longitude)
                                                - radians($user_longitude))
                                                + sin(radians($user_latitude))
                                                * sin(radians(latitude))))
                                                AS distance"))
                                        ->having('distance', '<=', $radius);
                                    });
                            })
                            ->whereHas('images');
            }

            return new MenuCollection($menu->with(['restaurant', 'menuPrices', 'categories.food_sub_categories', 'images', 'discount'])->paginate($per_page)->appends($request->query()));
        } else {
            $menu = Menu::active()
                        ->hasActivePrices()
                        ->whereHas('restaurant', function ($query) use ($latitude, $longitude, $radius) {
                            $query->where('status', '2')
                                ->when($latitude && $longitude && $latitude != '' && $longitude != '', function ($query) use ($latitude, $longitude, $radius) {
                                    $query->select(DB::raw("*,
                                            (6371 * acos(cos(radians($latitude))
                                            * cos(radians(latitude))
                                            * cos(radians(longitude)
                                            - radians($longitude))
                                            + sin(radians($latitude))
                                            * sin(radians(latitude))))
                                            AS distance"))
                                    ->having('distance', '<=', $radius);
                                });
                        })
                        ->whereHas('images');

            return new MenuCollection($menu->with(['restaurant', 'menuPrices', 'categories.food_sub_categories', 'images', 'discount'])->paginate($per_page));
        }
    }

    /**
     * Create a menu item for a restaurant
     *
     * Creates an active menu item for the restaurant in the path placeholder `{id}` (restaurant uuid or numeric id),
     * with an optional standard price and image, attaches categories/sub categories and creates the matching Stripe
     * product (and price). Only users with role `restaurant` or the `edit partners` permission may call this. Used by
     * `POST /restaurant/{id}/menu/add` and `POST /admin/restaurant/{id}/menu/add`. Note: the resource routes
     * `POST /restaurant/menu` and `POST /orderer/orderer-menu` have no `{id}` and therefore fail.
     *
     * @bodyParam title string required The menu item name. Example: Chicken Burger
     * @bodyParam description string required The menu item description. Example: Grilled chicken burger with fries
     * @bodyParam status integer Ignored; new menu items are always active (2). Example: 2
     * @bodyParam preparation_time integer Preparation time in minutes. Example: 20
     * @bodyParam categoryIds integer[] required Ids of the food categories of the item. Example: [1]
     * @bodyParam subcategoryIds integer[] Ids of the food sub categories of the item. Example: [4]
     * @bodyParam standardPrice integer The standard price; also sent to Stripe as `unit_amount` (smallest currency unit, GBP). Example: 1250
     * @bodyParam image file The main image of the menu item. No-example
     *
     * @response 201 scenario="Created" {"data":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"orderItems":[],"orders_count":0,"total_orders_value":0}}}
     * @response 401 scenario="Unauthorized role" {"status":"Error has occured...","message":"Unauthorized","data":""}
     * @response 403 scenario="Error (e.g. Stripe or database error)" {"status":"Error has occured...","message":"No such product: 'prod_PqR123'","data":""}
     * @response 404 scenario="Restaurant not found" {"status":"Error has occured...","message":"Invalid restaurnt","data":""}
     * @response 422 scenario="Validation error" {"message":"The category ids field is required.","errors":{"categoryIds":["The category ids field is required."]}}
     *
     * @responseField data.id integer Menu item id.
     * @responseField data.uuid string Menu item uuid.
     * @responseField data.relationships.menuPrices object[] Prices of the menu item.
     */
    public function store(StoreMenuRequest $request, $id)
    {
        $restaurant = Restaurant::where('uuid', $id)->first();
        if (!$restaurant) {
            $restaurant = Restaurant::where('id', $id)->first();
        }

        if (!$restaurant) {
            return $this->error('', 'Invalid restaurnt', 404);
        }

        if (!auth()->user()->hasRole('restaurant') && !auth()->user()->hasPermission('edit partners')) {
            return $this->error('', 'Unauthorized', 401);
        }

        try {
            DB::beginTransaction();
            if($request->hasFile('image')){
                $fileName = $this->generateFileName2($request->file('image'));
            }

            $menu = Menu::create([
                'title' => $request->title,
                'description' => $request->description,
                'restaurant_id' => $restaurant->id,
                'preparation_time' => $request->preparation_time,
                'status' => 2,
                'created_by' => auth()->user()->email,
                'updated_by' => auth()->user()->email,
            ]);

            if (!$menu) {
                DB::rollBack();
                return $this->error('', 'unable to create menu item', 403);
            }

            $menuPrice = null;

            if ($request->has('standardPrice')) {
                $menuPrice = MenuPrice::create([
                    'menu_id' => $menu->id,
                    'description' => 'standard',
                    'price' => $request->standardPrice,
                    'status' => 2,
                    'created_by' => auth()->user()->email,
                ]);
            }

            if($request->hasFile('image')) {
                $filename = $request->file('image')->storeAs('menus/images', $fileName, 'public');
                if ($filename) {
                    MenuImage::create([
                        'menu_id' => $menu->id,
                        'image_url' => $fileName,
                        'sequence' => 1,
                        'status' => 2,
                        'created_by' => auth()->user()->email,
                    ]);
                }
            }

            foreach ($request->input('categoryIds') as $foodCategoryId) {
                $menu->categories()->attach($foodCategoryId, [
                    // Add more pivot table attributes here
                    'uuid' => Str::uuid(),
                    'created_by' => auth()->user()->email,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            }

            if ($request->has('subcategoryIds') && count($request->subcategoryIds) > 0) {
                foreach ($request->subcategoryIds as $subcategoryId) {
                    $menu->subCategories()->attach($subcategoryId, [
                        'uuid' => Str::uuid(),
                        'created_by' => auth()->user()->email,
                    ]);
                }
            }

            // $restaurant = Restaurant::find($restaurant);

            // Make request to stripe to store menu item
            $stripe = new \Stripe\StripeClient(config('services.stripe.SECRET_KEY'));

            $product = $stripe->products->create([
                'name' => $request->title,
                'description' => $request->description,
                'metadata' => [
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->name,
                ]
            ]);

            $menu->update([
                'stripe_product_id' => $product->id
            ]);

            if ($request->has('standardPrice')) {
                $price = $stripe->prices->create([
                    'unit_amount' => $request->standardPrice,
                    'product' => $product['id'],
                    'currency' => 'gbp'
                ]);

                $menuPrice->update([
                    'stripe_price_id' => $price->id
                ]);
            }

            activity()->causedBy(auth()->user())->performedOn($menu)->log('added new menu item');

            DB::commit();
            return new MenuResource($menu);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);

        }
    }

    /**
     * List my restaurants' menu items
     *
     * Returns the menu items of all restaurants owned by the authenticated restaurant user (10 per page, newest
     * first, with order counts), the food categories available to those restaurants (global ones and their own) and
     * the user's restaurants.
     *
     * @queryParam search string Filter by menu item title or restaurant name. Example: burger
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"menu":{"current_page":1,"data":[{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger","description":"Grilled chicken burger with fries","restaurant_id":3,"status":2,"preparation_time":"20","created_by":"owner@example.com","updated_by":"owner@example.com","stripe_product_id":"prod_PqR123","created_at":"2024-05-01T10:00:00.000000Z","updated_at":"2024-05-01T10:00:00.000000Z","order_items_count":3,"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","name":"Mama's Kitchen","email":"info@mamaskitchen.com","phone_no":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","status":2,"created_at":"2024-04-01T10:00:00.000000Z","updated_at":"2024-04-01T10:00:00.000000Z"},"menu_prices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","menu_id":4,"description":"standard","price":"12.50","status":2,"created_by":"owner@example.com","updated_by":null,"stripe_price_id":"price_1PqR123","created_at":"2024-05-01T10:00:00.000000Z","updated_at":"2024-05-01T10:00:00.000000Z"}],"categories":[],"sub_categories":[],"images":[],"discount":null}],"first_page_url":"https://moboeats.com/api/v1/restaurant/menus?page=1","from":1,"last_page":1,"last_page_url":"https://moboeats.com/api/v1/restaurant/menus?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/restaurant/menus?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"https://moboeats.com/api/v1/restaurant/menus","per_page":10,"prev_page_url":null,"to":1,"total":1},"categories":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"image":"https://moboeats.com/storage/category/images/burgers.png","subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}]}],"restaurants":[{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","name":"Mama's Kitchen","email":"info@mamaskitchen.com","phone_no":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","status":2,"created_at":"2024-04-01T10:00:00.000000Z","updated_at":"2024-04-01T10:00:00.000000Z"}]}}
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     *
     * @responseField data.menu object Laravel paginator of menu items (raw model attributes with `restaurant`, `menu_prices`, `categories`, `sub_categories`, `images`, `discount` and `order_items_count`).
     * @responseField data.categories object[] Food categories (with sub categories) usable by the restaurants.
     * @responseField data.restaurants object[] The user's restaurants.
     */
    public function commonMenus(Request $request)
    {
        $search = $request->query('search');

        $restaurant_ids = auth()->user()->restaurants->pluck('id');

        $menu = Menu::with('restaurant', 'menuPrices', 'categories.food_sub_categories', 'subCategories', 'images', 'discount')
                    ->withCount('orderItems')
                    ->whereIn('restaurant_id', $restaurant_ids)
                    ->when($search && $search != '', function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('title', 'LIKE', '%'.$search.'%')
                                ->orWhereHas('restaurant', function ($query) use ($search) {
                                    $query->where('name', 'LIKE', '%'.$search.'%');
                                });
                        });
                    })
                    ->orderBy('created_at', 'DESC')
                    ->paginate(10);

        $categories = FoodCommonCategory::with('food_sub_categories')->where('restaurant_id', NULL)->orWhereIn('restaurant_id', $restaurant_ids)->get();

        return $this->success([
            'menu' => $menu,
            'categories' => new FoodCommonCategoryCollection($categories),
            'restaurants' => auth()->user()->restaurants,
        ]);
    }

    /**
     * Export menu items
     *
     * Generates an Excel (.xlsx) export of menu items and downloads it.
     *
     * @queryParam search string Filter by menu item title or restaurant name (passed to the export). Example: burger
     *
     * @response 200 scenario="File download" <<binary>> The exported Excel file (menu<id>.xlsx).
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     */
    public function export(Request $request)
    {
        $search = $request->query('search');

        $unique = explode('-', Str::uuid())[0];

        Excel::store(new MenuExport($search), 'menu'.$unique.'.xlsx', 'exports');

        return Storage::disk('exports')->download('menu'.$unique.'.xlsx');
    }

    /**
     * Create a menu item in several restaurants
     *
     * Creates the same menu item (with images, categories, optional standard price and a Stripe product/price) in each
     * of the given restaurants. Send as `multipart/form-data`; the id lists are JSON-encoded strings. Any failure
     * (including missing `images`) rolls everything back and returns 403.
     *
     * @bodyParam restaurant_ids string required JSON-encoded array of restaurant ids. Example: [3,5]
     * @bodyParam title string required The menu item name. Example: Chicken Burger
     * @bodyParam description string required The menu item description. Example: Grilled chicken burger with fries
     * @bodyParam status integer Status: 1 = inactive, 2 = active. Example: 2
     * @bodyParam preparation_time integer Preparation time in minutes. Example: 20
     * @bodyParam categoryIds string required JSON-encoded array of food category ids. Example: [1]
     * @bodyParam subcategoryIds string JSON-encoded array of food sub category ids. Example: [4]
     * @bodyParam standardPrice integer The standard price; also sent to Stripe as `unit_amount` (smallest currency unit, GBP). Example: 1250
     * @bodyParam images file[] required Images of the menu item (needed by the code even though not validated). No-example
     *
     * @response 200 scenario="Created" {"status":"Request was succesful","message":"Menu added successfully","data":""}
     * @response 403 scenario="Error (e.g. missing images, Stripe or database error)" {"status":"Error has occured...","message":"count(): Argument #1 ($value) must be of type Countable|array, null given","data":""}
     * @response 422 scenario="Validation error" {"message":"The restaurant ids field is required.","errors":{"restaurant_ids":["The restaurant ids field is required."]}}
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     */
    public function addMenu(Request $request)
    {
        $request->validate([
            'restaurant_ids' => ['required'],
            'title' => ['required'],
            'description' => ['required'],
            'status' => ['nullable', 'integer'],
            'categoryIds' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $images = [];
            if (count($request->images) > 0) {
                foreach ($request->images as $image) {
                    $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $newFilename = $originalFilename.'-'.uniqid().'.'.$image->guessExtension();
                    $image->move('storage/menus/images/tmp', $newFilename);
                    array_push($images, $newFilename);
                }
            }

            collect(json_decode($request->restaurant_ids, true))->each(function ($restaurant, $index) use ($request, $images) {
                $menu = Menu::create([
                    'title' => $request->title,
                    'description' => $request->description,
                    'restaurant_id' => $restaurant,
                    'status' => $request->status,
                    'preparation_time' => $request->preparation_time,
                    'created_by' => auth()->user()->email,
                    'updated_by' => auth()->user()->email,
                ]);

                if (!$menu) {
                    DB::rollBack();
                    return $this->error('', 'unable to create menu item', 403);
                }

                $menuPrice = null;

                if ($request->has('standardPrice')) {
                    $menuPrice = MenuPrice::create([
                        'menu_id' => $menu->id,
                        'description' => 'standard',
                        'price' => $request->standardPrice,
                        'status' => 2,
                        'created_by' => auth()->user()->email,
                    ]);
                }

                if (count($request->images) > 0) {
                    foreach ($images as $image) {
                        $anotherFileName = explode('.', $image)[0].''.uniqid().'.'.explode('.', $image)[1];
                        Storage::disk('public')->copy('menus/images/tmp/'.$image, 'menus/images/'.$anotherFileName);
                        MenuImage::create([
                            'menu_id' => $menu->id,
                            'image_url' => $anotherFileName,
                            'sequence' => 1,
                            'status' => 2,
                            'created_by' => auth()->user()->email,
                        ]);
                    }
                }

                foreach (json_decode($request->categoryIds, true) as $foodCategoryId) {
                    $menu->categories()->attach($foodCategoryId, [
                        // Add more pivot table attributes here
                        'uuid' => Str::uuid(),
                        'created_by' => auth()->user()->email,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                }

                if ($request->has('subcategoryIds') && count(json_decode($request->subcategoryIds, true)) > 0) {
                    foreach (json_decode($request->subcategoryIds, true) as $subcategoryId) {
                        $menu->subCategories()->attach($subcategoryId, [
                            'uuid' => Str::uuid(),
                            'created_by' => auth()->user()->email,
                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        ]);
                    }
                }

                $restaurant = Restaurant::find($restaurant);

                // Make request to stripe to store menu item
                $stripe = new \Stripe\StripeClient(config('services.stripe.SECRET_KEY'));

                $product = $stripe->products->create([
                    'name' => $request->title,
                    'description' => $request->description,
                    'metadata' => [
                        'restaurant_id' => $restaurant->id,
                        'restaurant_name' => $restaurant->name,
                    ]
                ]);

                $menu->update([
                    'stripe_product_id' => $product->id,
                ]);

                if ($request->has('standardPrice')) {
                    $price = $stripe->prices->create([
                        'unit_amount' => $request->standardPrice,
                        'product' => $product['id'],
                        'currency' => 'gbp'
                    ]);

                    $menuPrice->update([
                        'stripe_price_id' => $price->id
                    ]);
                }
            });

            if (count($images) > 0) {
                foreach ($images as $image) {
                    Storage::disk('public')->delete('menus/images/tmp/'.$image);
                }
            }
            DB::commit();
            return $this->success('', 'Menu added successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Update a menu item (multi-restaurant form)
     *
     * Intended to update a menu item's details, image, standard price and categories for the given restaurants. Only
     * users with role `restaurant` may call this. Note: the current implementation calls `DB::transaction()` without a
     * callback, so after validation it always ends in the 400 error response.
     *
     * @urlParam menu_uuid string required The uuid of the menu item. Example: 9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d
     *
     * @bodyParam restaurant_ids integer[] required Ids of the restaurants. Example: [3]
     * @bodyParam title string required The menu item name. Example: Chicken Burger
     * @bodyParam description string required The menu item description. Example: Grilled chicken burger with fries
     * @bodyParam status integer Status: 1 = inactive, 2 = active. Example: 2
     * @bodyParam categoryIds integer[] required Ids of the food categories. Example: [1]
     * @bodyParam subcategoryIds integer[] Ids of the food sub categories. Example: [4]
     * @bodyParam standardPrice number A new standard price. Example: 12.5
     * @bodyParam image file A new main image. No-example
     *
     * @response 200 scenario="Updated" {"status":"Request was succesful","message":"Menu added successfully","data":""}
     * @response 400 scenario="Error" {"status":"Error has occured...","message":"An error occurred","data":""}
     * @response 401 scenario="Unauthorized role" {"status":"Error has occured...","message":"Unauthorized","data":""}
     * @response 404 scenario="Menu not found" {"message":"No query results for model [App\\Models\\Menu] 9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d"}
     * @response 422 scenario="Validation error" {"message":"The restaurant ids field is required.","errors":{"restaurant_ids":["The restaurant ids field is required."]}}
     */
    public function updateMenu(Request $request, Menu $menu)
    {
        if (!auth()->user()->hasRole('restaurant')) {
            return $this->error('', 'Unauthorized', 401);
        }

        $request->validate([
            'restaurant_ids' => ['required', 'array'],
            'restaurant_ids.*' => ['integer'],
            'title' => ['required'],
            'description' => ['required'],
            'status' => ['nullable', 'integer'],
            'categoryIds' => 'required|array',
            'categoryIds.*' => 'integer',
        ]);

        try {
            DB::transaction();
            if($request->hasFile('image')){
                $fileName = $this->generateFileName2($request->file('image'));
            }

            collect($request->restaurant_ids)->each(function ($restaurant) use ($request, $fileName, $menu) {
                if (!$menu) {
                    return $this->error('', 'Unable to update menu item', 403);
                }

                $menu->update([
                    'title' => $request->title,
                    'description' => $request->description,
                    'restaurant_id' => $restaurant,
                    'status' => $request->status,
                    'updated_by' => auth()->user()->email,
                ]);

                if ($request->has('standarPrice')) {
                    MenuPrice::create([
                        'menu_id' => $menu->id,
                        'description' => 'standard',
                        'price' => $request->standardPrice,
                        'status' => 2,
                        'updated_by' => auth()->user()->email,
                    ]);
                }

                if($request->hasFile('image')) {
                    $image = MenuImage::where('menu_id', $menu->id)->where('sequence', 1)->first();
                    if($image) {
                        $image->delete();
                        //delete file from
                        $prevFile = $image->image_url;
                    }
                    MenuImage::create([
                        'uuid' => Str::uuid(),
                        'menu_id' => $menu->id,
                        'image_url' => $fileName,
                        'sequence' => 1,
                        'status' => 2,
                        'created_by' => auth()->user()->email,
                    ]);

                    $fileData = ['file' => $request->file('image'),'fileName' => $fileName, 'storageName' => $this->settings['storageName'].'\\images','prevFile' => $prevFile];
                    if(!$this->uploadFile($fileData)){
                        DB::rollBack();
                    }
                }

                $foodCategoryIds = $request->input('categoryIds');
                $subcategoryIds = $request->input('subcategoryIds');

                $syncData = [];
                $syncData2 = [];
                foreach ($foodCategoryIds as $foodCategoryId) {
                    $syncData[$foodCategoryId] = [
                        'menu_id' => $menu->id,
                        'uuid' => Str::uuid(),
                        'created_by' => auth()->user()->email,
                        'created_at' => now()->format('Y-m-d H:i:s'),
                    ];
                }

                $menu->categories()->sync($syncData);

                if ($request->subcategoryIds) {
                    foreach ($subcategoryIds as $categoryId) {
                        $syncData2[$categoryId] = [
                            'uuid' => Str::uuid(),
                            'menu_id' => $menu->id,
                            'created_by' => auth()->user()->email,
                        ];
                    }
                    $menu->subCategories()->sync($syncData2);
                }
            });
            DB::commit();
            return $this->success('', 'Menu added successfully');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->error('', 'An error occurred', 400);
        }
    }

    /**
     * Replace menu item images
     *
     * Deletes all current images of the menu item and stores the uploaded ones. Send as `multipart/form-data`.
     *
     * @urlParam id integer required The numeric id of the menu item. Example: 4
     *
     * @bodyParam images file[] required The new images of the menu item. No-example
     *
     * @response 200 scenario="Updated" {"status":"Request was succesful","message":"Menu images updated successfully","data":""}
     * @response 404 scenario="Menu not found" {"status":"Error has occured...","message":"Menu not found","data":""}
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     */
    public function updateImages(Request $request, $id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return $this->error('', 'Menu not found', 404);
        }

        $current_images = MenuImage::where('menu_id', $id)->get();

        if($current_images->count() > 0) {
            foreach ($current_images as $image) {
                Storage::disk('public')->delete('/menus/images/'.$image->image_url);
                $image->delete();
            }
        }

        foreach ($request->images as $image) {
            $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            // this is needed to safely include the file name as part of the URL
            $newFilename = $originalFilename.'-'.uniqid().'.'.$image->guessExtension();
            $image->move('storage/menus/images', $newFilename);

            MenuImage::create([
                'menu_id' => $menu->id,
                'image_url' => $newFilename,
                'sequence' => 1,
                'status' => 2,
                'created_by' => auth()->user()->email,
            ]);
        }

        return $this->success('', 'Menu images updated successfully');
    }

    /**
     * Get a menu item
     *
     * Returns a menu item (looked up by uuid) with prices, categories, sub categories, images, discount, reviews and
     * order items (grouped by order id). The response is not wrapped in `data`. When authenticated as a `restaurant`
     * or `restaurant employee` user (on `/restaurant/menu/{id}`), the food categories available to the restaurant are
     * returned too.
     *
     * @urlParam id string required The uuid of the menu item. Example: 9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d
     *
     * @response 200 scenario="Success" {"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"categories":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"image":"https://moboeats.com/storage/category/images/burgers.png","subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}]}],"subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}],"images":[{"id":7,"uuid":"5a3e9d1c-2b4f-4e6a-8c7d-1f2e3a4b5c6d","attributes":{"imageUrl":"chicken-burger-65f1a2b3c4d5e.jpg","sequence":1,"status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"discount":null,"orderItems":[],"orders_count":0,"total_orders_value":0,"reviews":[]}}}
     * @response 200 scenario="Success (restaurant user)" {"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"categories":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"image":"https://moboeats.com/storage/category/images/burgers.png","subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}]}],"subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}],"images":[{"id":7,"uuid":"5a3e9d1c-2b4f-4e6a-8c7d-1f2e3a4b5c6d","attributes":{"imageUrl":"chicken-burger-65f1a2b3c4d5e.jpg","sequence":1,"status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"discount":null,"orderItems":[],"orders_count":0,"total_orders_value":0,"reviews":[]}},"categories":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"image":"https://moboeats.com/storage/category/images/burgers.png","subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}]}]}
     * @response 404 scenario="Not found" {"message":"No Menu with the given ID was found"}
     *
     * @responseField menu object The menu item.
     * @responseField menu.relationships.reviews object[] Reviews of the menu item.
     * @responseField categories object[] Food categories usable by the restaurant (restaurant users only).
     */
    public function show($menuId)
    {
        $menu = Menu::where('uuid', $menuId)->first();
        if ($menu) {
            if (auth()->check()) {
                if (auth()->user()->hasRole('restaurant')) {
                    $restaurant_ids = auth()->user()->restaurants->pluck('id');
                } elseif (auth()->user()->hasRole('restaurant employee')) {
                    $restaurant_ids = UserRestaurant::where('user_id', auth()->id())->first()->pluck('restaurant_id');
                }

                $categories = FoodCommonCategory::with('food_sub_categories')->where('restaurant_id', NULL)->orWhereIn('restaurant_id', $restaurant_ids)->get();

                return [
                    'menu' => new MenuResource($menu->loadMissing('menuPrices', 'categories.food_sub_categories', 'discount', 'images', 'orderItems.order', 'reviews', 'subCategories')),
                    'categories' => new FoodCommonCategoryCollection($categories),
                ];
            }

            return [
                'menu' => new MenuResource($menu->loadMissing('menuPrices', 'categories.food_sub_categories', 'discount', 'images', 'orderItems.order', 'reviews', 'subCategories')),
            ];
        }

        return response()->json(['message' => 'No Menu with the given ID was found'], 404);
    }

    /**
     * List menu item reviews
     *
     * Returns all reviews of a menu item, newest first. Each item embeds the full review record (with the reviewed menu item).
     *
     * @urlParam menu_uuid string required The uuid of the menu item. Example: 9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d
     *
     * @response 200 scenario="Success" {"data":[{"reviewable":{"id":1,"user_id":12,"order_id":10,"reviewable_type":"App\\Models\\Menu","reviewable_id":4,"rating":5,"review":"Delicious!","created_at":"2024-05-02T18:00:00.000000Z","updated_at":"2024-05-02T18:00:00.000000Z","reviewable":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger","description":"Grilled chicken burger with fries","restaurant_id":3,"status":2,"preparation_time":"20","created_by":"owner@example.com","updated_by":"owner@example.com","stripe_product_id":"prod_PqR123","created_at":"2024-05-01T10:00:00.000000Z","updated_at":"2024-05-01T10:00:00.000000Z"}},"rating":5,"review":"Delicious!"}]}
     * @response 404 scenario="Menu not found" {"status":"Error has occured...","message":"Menu record not found.","data":"Menu not found"}
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     *
     * @responseField data[].rating integer Rating from 1 to 5.
     * @responseField data[].review string The review text.
     * @responseField data[].reviewable object The full review record including the reviewed menu item.
     */
    public function reviews(Menu $menu)
    {
        $review = Review::where('reviewable_type', Menu::class)->where('reviewable_id', $menu->id)->orderBy('created_at', 'DESC')->get();

        return ReviewResource::collection($review);
    }

    /**
     * Update a menu item
     *
     * Updates a menu item's attributes, standard price (first price), main image and categories/sub categories, and
     * creates or updates its Stripe product. Available as `POST /restaurant/menu/{id}/update` and
     * `POST /admin/restaurant/menu/{id}/update` (numeric menu id), `PUT/PATCH /restaurant/menu/{uuid}` (menu uuid) and
     * `PUT/PATCH /orderer/orderer-menu/{id}` (numeric id). Any error rolls back and returns 403.
     *
     * @bodyParam title string The menu item name. Example: Chicken Burger
     * @bodyParam description string The menu item description. Example: Grilled chicken burger with fries
     * @bodyParam status integer Status: 1 = inactive, 2 = active. Example: 2
     * @bodyParam preparation_time integer Preparation time in minutes. Example: 20
     * @bodyParam categoryIds integer[] Ids of the food categories (the categories are re-synced; needed by the code). Example: [1]
     * @bodyParam subcategoryIds integer[] Ids of the food sub categories (re-synced when sent). Example: [4]
     * @bodyParam standardPrice number New amount for the menu item's first price. Example: 12.5
     * @bodyParam image file A new main image. No-example
     *
     * @response 200 scenario="Updated" {"data":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"orderItems":[],"orders_count":0,"total_orders_value":0}}}
     * @response 403 scenario="Error (e.g. menu not found, missing categoryIds, Stripe error)" {"status":"Error has occured...","message":"Call to a member function update() on null","data":""}
     * @response 404 scenario="Menu not found (PUT/PATCH /restaurant/menu/{uuid})" {"status":"Error has occured...","message":"Menu record not found.","data":"Menu not found"}
     * @response 422 scenario="Validation error" {"message":"The category ids field must be an array.","errors":{"categoryIds":["The category ids field must be an array."]}}
     *
     * @responseField data object The updated menu item.
     */
    public function update(UpdateMenuRequest $request, Menu $menu = NULL, $id = NULL)
    {
        if ($menu == NULL) {
            $menu = Menu::find($id);
        }

        try {
            DB::beginTransaction();

            if($request->hasFile('image')){
                $fileName = $this->generateFileName2($request->file('image'));
            }

            $menu->update($request->all());

            if ($request->standardPrice) {
                $standardPrice = MenuPrice::where('menu_id', $menu->id)->first();
                $standardPrice->update(['price' => $request->standardPrice]);
            }

            if($request->hasFile('image')){
                $image = MenuImage::where('menu_id', $menu->id)->where('sequence', 1)->first();
                if($image) {
                    $image->delete();
                    //delete file from
                    $prevFile = $image->image_url;
                }
                MenuImage::create([
                    'uuid' => Str::uuid(),
                    'menu_id' => $menu->id,
                    'image_url' => $fileName,
                    'sequence' => 1,
                    'status' => 2,
                    'created_by' => auth()->user()->email,
                ]);

                $fileData = ['file' => $request->file('image'),'fileName' => $fileName, 'storageName' => $this->settings['storageName'].'\\images','prevFile' => $prevFile];
                if(!$this->uploadFile($fileData)){
                    DB::rollBack();
                }
            }

            $foodCategoryIds = $request->input('categoryIds');
            $subcategoryIds = $request->input('subcategoryIds');

            $syncData = [];
            $syncData2 = [];
            foreach ($foodCategoryIds as $foodCategoryId) {
                $syncData[$foodCategoryId] = [
                    'menu_id' => $menu->id,
                    'uuid' => Str::uuid(),
                    'created_by' => auth()->user()->email,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                ];
            }

            $menu->categories()->sync($syncData);

            if ($request->subcategoryIds) {
                foreach ($subcategoryIds as $categoryId) {
                    $syncData2[$categoryId] = [
                        'uuid' => Str::uuid(),
                        'menu_id' => $menu->id,
                        'created_by' => auth()->user()->email,
                    ];
                }
                $menu->subCategories()->sync($syncData2);
            }

            // Make request to stripe to store menu item
            $stripe = new \Stripe\StripeClient(config('services.stripe.SECRET_KEY'));

            if (!$menu->stripe_product_id) {
                $product = $stripe->products->create([
                    'name' => $request->title,
                    'description' => $request->description,
                    'metadata' => [
                        'restaurant_id' => $menu->restaurant->id,
                        'restaurant_name' => $menu->restaurant->name,
                    ]
                ]);

                $menu->update([
                    'stripe_product_id' => $product->id,
                ]);
            } else {
                $stripe->products->update($menu->stripe_product_id, [
                    'name' => $request->title,
                    'description' => $request->description,
                ]);
            }

            DB::commit();
            return new MenuResource($menu);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * List grocery items
     *
     * Returns menu items in the "groceries" category (10 per page). Guests and customers (`orderer` role) get active
     * items with an active price. On the authenticated `/restaurant/groceries` route, `restaurant` users get the
     * grocery items of their restaurants together with the available categories and their restaurants, and
     * `restaurant employee` users get their restaurant's grocery items. Other roles get an empty response.
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success (guest, customer or employee)" {"data":[{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"categories":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"image":"https://moboeats.com/storage/category/images/burgers.png"}],"subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}],"images":[{"id":7,"uuid":"5a3e9d1c-2b4f-4e6a-8c7d-1f2e3a4b5c6d","attributes":{"imageUrl":"chicken-burger-65f1a2b3c4d5e.jpg","sequence":1,"status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"discount":null,"orderItems":[],"orders_count":0,"total_orders_value":0}}],"links":{"first":"https://moboeats.com/api/v1/groceries?page=1","last":"https://moboeats.com/api/v1/groceries?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/groceries?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"https://moboeats.com/api/v1/groceries","per_page":10,"to":1,"total":1}}
     * @response 200 scenario="Success (restaurant user)" {"status":"Request was succesful","message":null,"data":{"menu":{"current_page":1,"data":[{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger","description":"Grilled chicken burger with fries","restaurant_id":3,"status":2,"preparation_time":"20","created_by":"owner@example.com","updated_by":"owner@example.com","stripe_product_id":"prod_PqR123","created_at":"2024-05-01T10:00:00.000000Z","updated_at":"2024-05-01T10:00:00.000000Z","images":[],"categories":[],"sub_categories":[],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","name":"Mama's Kitchen","email":"info@mamaskitchen.com","phone_no":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","status":2,"created_at":"2024-04-01T10:00:00.000000Z","updated_at":"2024-04-01T10:00:00.000000Z"},"discount":null}],"first_page_url":"https://moboeats.com/api/v1/restaurant/groceries?page=1","from":1,"last_page":1,"last_page_url":"https://moboeats.com/api/v1/restaurant/groceries?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/restaurant/groceries?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"https://moboeats.com/api/v1/restaurant/groceries","per_page":10,"prev_page_url":null,"to":1,"total":1},"categories":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"image":"https://moboeats.com/storage/category/images/burgers.png","subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}]}],"restaurants":[{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","name":"Mama's Kitchen","email":"info@mamaskitchen.com","phone_no":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","status":2,"created_at":"2024-04-01T10:00:00.000000Z","updated_at":"2024-04-01T10:00:00.000000Z"}]}}
     * @response 422 scenario="Restaurant user without a restaurant (restaurant route only)" {"data":"no_restaurant"}
     *
     * @responseField data object Grocery menu items (paginated resource), or for restaurant users an object with `menu` (paginator), `categories` and `restaurants`.
     */
    public function groceries(Request $request)
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole('orderer')) {
                $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

                $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

                $menu = Menu::active()->hasActivePrices()->with('images', 'categories', 'subCategories', 'restaurant', 'discount')->whereIn('id', $category_menus)->paginate(10);

                return MenuResource::collection($menu);
            }

            if (auth()->user()->hasRole('restaurant')) {
                $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

                $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

                $restaurants = auth()->user()->restaurants->pluck('id');

                $menu = Menu::with('images', 'categories', 'subCategories', 'restaurant', 'discount')->whereIn('restaurant_id', $restaurants)->whereIn('id', $category_menus)->paginate(10);

                $categories = FoodCommonCategory::with('food_sub_categories')->where('restaurant_id', NULL)->orWhereIn('restaurant_id', $restaurants)->get();

                return $this->success([
                    'menu' => $menu,
                    'categories' => new FoodCommonCategoryCollection($categories),
                    'restaurants' => auth()->user()->restaurants,
                ]);
            }

            if (auth()->user()->hasRole('restaurant employee')) {
                $search = $request->query('search');
                $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
                $restaurant = Restaurant::where('id', $user_restaurant->restaurant_id)->first();

                $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

                $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

                $menu = Menu::with('images', 'categories', 'subCategories', 'discount')->where('restaurant_id', $restaurant->id)->whereIn('id', $category_menus)->paginate(10);

                return MenuResource::collection($menu);
            }
        } else {
            $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

            $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

            $menu = Menu::active()->hasActivePrices()->with('images', 'categories', 'subCategories', 'restaurant', 'discount')->whereIn('id', $category_menus)->paginate(10);

            return MenuResource::collection($menu);
        }
    }

    /**
     * List grocery sub categories
     *
     * Returns all sub categories of the groceries category, each with its active menu items that have an active
     * price. Served on `GET /groceries/subcategories/{foo_sub_category}` (the placeholder is required by the route
     * but ignored) and `GET /orderer/groceries/categories`. When called by an authenticated `restaurant` user it
     * returns that user's grocery items, categories and restaurants instead.
     *
     * @response 200 scenario="Success" {"data":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"menus":[{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"orderItems":[],"orders_count":0,"total_orders_value":0}}]}]}
     *
     * @responseField data object[] Grocery sub categories.
     * @responseField data[].menus object[] Active grocery items in the sub category.
     */
    public function grocerySubcategories(FooSubCategory $foo_sub_category)
    {
        $grocery = FoodCommonCategory::where('title', 'grocery')->orWhere('title', 'groceries')->orWhere('title', 'Groceries')->first();

        $foo_sub_category = FooSubCategory::whereHas('foodCategories', function ($query) use ($grocery) {
            $query->where('category_id', $grocery->id);
        })
        ->get();

        if (auth()->check()) {
            if (auth()->user()->hasRole('orderer')) {
                return FooSubCategoryResource::collection(
                    $foo_sub_category->load(['menus' => function ($query) {
                        $query->where('status', 2)
                            ->whereHas('menuPrices', function ($query) {
                                $query->where('status', 2);
                            });
                    }])
                );

                // $category = FoodCommonCategory::with([
                //     'food_sub_categories',
                //     'menus' => function ($query) {
                //         $query->whereHas('restaurant', function ($query) {
                //             $query->inOperation()->approved();
                //         })
                //         ->whereHas('menuPrices', function ($query) {
                //             $query->where('status', 2);
                //         });
                //     }])
                //     ->where('title', 'groceries')
                //     ->first();

                // return new FoodCommonCategoryResource(
                //     $category->load([
                //         'food_sub_categories',
                //         'menus' => function ($query) {
                //             $query->whereHas('restaurant', function ($query) {
                //                 $query->inOperation()->approved();
                //             })
                //             ->whereHas('menuPrices', function ($query) {
                //                 $query->where('status', 2);
                //             });
                //         }
                // ]));

                // $sub_categories = $food_category->food_sub_categories->pluck('id');

                // $category_menus = FooSubCategory::where('category_id', $category->id)->get()->pluck('menu_id');

                // $menu = Menu::active()->hasActivePrices()->with('images', 'categories', 'subCategories', 'restaurant', 'discount')->whereIn('id', $category_menus)->paginate(10);

                // return MenuResource::collection($menu);
            }

            if (auth()->user()->hasRole('restaurant')) {
                $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

                $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

                $restaurants = auth()->user()->restaurants->pluck('id');

                $menu = Menu::with('images', 'categories', 'subCategories', 'restaurant', 'discount')->whereIn('restaurant_id', $restaurants)->whereIn('id', $category_menus)->paginate(10);

                $categories = FoodCommonCategory::with('food_sub_categories')->where('restaurant_id', NULL)->orWhereIn('restaurant_id', $restaurants)->get();

                return $this->success([
                    'menu' => $menu,
                    'categories' => new FoodCommonCategoryCollection($categories),
                    'restaurants' => auth()->user()->restaurants,
                ]);
            }
        } else {
            return FooSubCategoryResource::collection(
                $foo_sub_category->load(['menus' => function ($query) {
                    $query->where('status', 2)
                        ->whereHas('menuPrices', function ($query) {
                            $query->where('status', 2);
                        });
                }])
            );

            // $category = FoodCommonCategory::with([
            //     'food_sub_categories',
            //     'menus' => function ($query) {
            //         $query->whereHas('restaurant', function ($query) {
            //             $query->inOperation()->approved();
            //         })
            //         ->whereHas('menuPrices', function ($query) {
            //             $query->where('status', 2);
            //         });
            //     }])
            //     ->where('title', 'groceries')
            //     ->first();

            // return new FoodCommonCategoryResource(
            //     $category->load([
            //         'food_sub_categories',
            //         'menus' => function ($query) {
            //             $query->whereHas('restaurant', function ($query) {
            //                 $query->inOperation()->approved();
            //             })
            //             ->whereHas('menuPrices', function ($query) {
            //                 $query->where('status', 2);
            //             });
            //         }
            // ]));

            // $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

            // $menu = Menu::active()->hasActivePrices()->with('images', 'categories', 'subCategories', 'restaurant', 'discount')->whereIn('id', $category_menus)->paginate(10);

            // return MenuResource::collection($menu);
        }

    }

    /**
     * List a restaurant's menu items
     *
     * Returns non-grocery menu items (10 per page), optionally limited to one restaurant. Guests and customers get
     * active items with an active price. Authenticated `restaurant` users get the items of their restaurants with the
     * available categories and their restaurants; `restaurant employee` users get their restaurant's items.
     *
     * @urlParam restaurant string The uuid of the restaurant. Omit to list menu items of all restaurants. Example: 1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"data":[{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"categories":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"image":"https://moboeats.com/storage/category/images/burgers.png"}],"subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}],"images":[{"id":7,"uuid":"5a3e9d1c-2b4f-4e6a-8c7d-1f2e3a4b5c6d","attributes":{"imageUrl":"chicken-burger-65f1a2b3c4d5e.jpg","sequence":1,"status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"discount":null,"orderItems":[],"orders_count":0,"total_orders_value":0}}],"links":{"first":"https://moboeats.com/api/v1/orderer/orderer-restaurants/1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6/menu?page=1","last":"https://moboeats.com/api/v1/orderer/orderer-restaurants/1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6/menu?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/orderer/orderer-restaurants/1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6/menu?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"https://moboeats.com/api/v1/orderer/orderer-restaurants/1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6/menu","per_page":10,"to":1,"total":1}}
     * @response 404 scenario="Restaurant not found" {"status":"Error has occured...","message":"Restaurant record not found.","data":"Restaurant Not Found"}
     *
     * @responseField data object[] The menu items.
     * @responseField meta object Pagination metadata.
     */
    public function restaurantMenu(Request $request, Restaurant $restaurant = null)
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole('orderer')) {
                $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

                $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

                $menu = Menu::active()
                            ->hasActivePrices()
                            ->with('images', 'categories', 'subCategories', 'restaurant', 'discount')
                            ->whereNotIn('id', $category_menus)
                            ->when($restaurant && $restaurant != NULL, function ($query) use ($restaurant) {
                                $query->where('restaurant_id', $restaurant->id);
                            })
                            ->paginate(10);

                return MenuResource::collection($menu);
            }

            if (auth()->user()->hasRole('restaurant')) {
                $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

                $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

                $restaurants = auth()->user()->restaurants->pluck('id');

                $menu = Menu::with('images', 'categories', 'subCategories', 'restaurant', 'discount')
                                ->whereIn('restaurant_id', $restaurants)
                                ->whereNotIn('id', $category_menus)
                                ->when($restaurant && $restaurant != NULL, function ($query) use ($restaurant) {
                                    $query->where('restaurant_id', $restaurant->id);
                                })
                                ->paginate(10);

                $categories = FoodCommonCategory::with('food_sub_categories')->where('restaurant_id', NULL)->orWhereIn('restaurant_id', $restaurants)->get();

                return $this->success([
                    'menu' => $menu,
                    'categories' => new FoodCommonCategoryCollection($categories),
                    'restaurants' => auth()->user()->restaurants,
                ]);
            }

            if (auth()->user()->hasRole('restaurant employee')) {
                $search = $request->query('search');
                $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
                $restaurant = Restaurant::where('id', $user_restaurant->restaurant_id)->first();

                $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

                $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

                $menu = Menu::with('images', 'categories', 'subCategories', 'discount')
                                ->where('restaurant_id', $restaurant->id)
                                ->whereNotIn('id', $category_menus)
                                ->when($restaurant && $restaurant != NULL, function ($query) use ($restaurant) {
                                    $query->where('restaurant_id', $restaurant->id);
                                })
                                ->paginate(10);

                return MenuResource::collection($menu);
            }
        } else {
            $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

            $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

            $menu = Menu::active()->hasActivePrices()->with('images', 'categories', 'subCategories', 'restaurant', 'discount')
                                ->whereNotIn('id', $category_menus)
                                ->when($restaurant && $restaurant != NULL, function ($query) use ($restaurant) {
                                    $query->where('restaurant_id', $restaurant->id);
                                })
                                ->paginate(10);

            return MenuResource::collection($menu);
        }

    }

    /**
     * Export grocery items
     *
     * Generates an Excel (.xlsx) export of grocery items and downloads it. Note: the code calls an undefined
     * `unique()` helper, so this currently fails with a server error.
     *
     * @queryParam search string Filter by menu item title or restaurant name (passed to the export). Example: burger
     *
     * @response 200 scenario="File download" <<binary>> The exported Excel file (groceries<id>.xlsx).
     * @response 500 scenario="Server error" {"message":"Call to undefined function App\\Http\\Controllers\\Api\\V1\\unique()"}
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     */
    public function exportGroceries(Request $request)
    {
        $search = $request->query('search');

        $unique = unique();

        Excel::store(new GroceryExport($search), 'groceries'.$unique.'.xlsx', 'exports');

        return Storage::disk('exports')->download('groceries'.$unique.'.xlsx');
    }

    /**
     * List a restaurant's grocery items
     *
     * Returns the grocery items (menu items in the "groceries" category) of a restaurant, 10 per page.
     *
     * @urlParam restaurant_uuid string required The uuid of the restaurant. Example: 1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"data":[{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","attributes":{"title":"Chicken Burger","description":"Grilled chicken burger with fries","createdBy":"owner@example.com","updatedBy":"owner@example.com","status":"2","preparation_time":"20","average_rating":4.5},"relationships":{"categories":[{"id":1,"uuid":"0f8e2a3c-5b1d-4e7a-9c3f-2d6b8a1e4f70","title":"Burgers","description":"Burgers and sandwiches","status":2,"createdBy":"admin@moboeats.com","updatedBy":null,"image":"https://moboeats.com/storage/category/images/burgers.png"}],"subCategories":[{"id":4,"uuid":"7c6b5a49-3827-4165-9e0d-1c2b3a4f5e6d","title":"Beef Burgers","image":"https://moboeats.com/storage/category/subcategory/beef.png","description":"Beef patty burgers","status":2,"createdBy":"admin@moboeats.com","updatedBy":null}],"images":[{"id":7,"uuid":"5a3e9d1c-2b4f-4e6a-8c7d-1f2e3a4b5c6d","attributes":{"imageUrl":"chicken-burger-65f1a2b3c4d5e.jpg","sequence":1,"status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"menuPrices":[{"id":6,"uuid":"c4d5e6f7-8a9b-4c0d-9e1f-2a3b4c5d6e7f","attributes":{"price":"12.50","description":"standard","status":"2"},"relationships":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger"}}}],"restaurant":{"id":3,"uuid":"1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6","attributes":{"name":"Mama's Kitchen","email":"info@mamaskitchen.com","phoneNo":"+447700900123","address":"12 High Street","city":"London","latitude":"51.5072","longitude":"-0.1276","logo":"https://moboeats.com/storage/restaurant/logos/mamas.png","status":"2","average_rating":4.2,"is_open":true},"relationships":{}},"orderItems":[],"orders_count":0,"total_orders_value":0}}],"links":{"first":"https://moboeats.com/api/v1/restaurant/1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6/groceries?page=1","last":"https://moboeats.com/api/v1/restaurant/1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6/groceries?page=1","prev":null,"next":null},"meta":{"current_page":1,"from":1,"last_page":1,"links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/restaurant/1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6/groceries?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"path":"https://moboeats.com/api/v1/restaurant/1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6/groceries","per_page":10,"to":1,"total":1}}
     * @response 404 scenario="Restaurant not found" {"message":"No query results for model [App\\Models\\Restaurant] 1e2d3c4b-5a69-4788-9a0b-c1d2e3f4a5b6"}
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     *
     * @responseField data object[] The grocery items.
     * @responseField meta object Pagination metadata.
     */
    public function restaurantGroceries(Restaurant $restaurant)
    {
        $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

        $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

        $menu = Menu::with('images', 'categories', 'subCategories')->where('restaurant_id', $restaurant->id)->whereIn('id', $category_menus)->paginate(10);

        return MenuResource::collection($menu);
    }

    /**
     * List orders containing a menu item
     *
     * Returns orders (statuses 2 to 5) that contain the given menu item, newest first, 10 per page, with their order
     * items and customer.
     *
     * @urlParam menu_uuid string required The uuid of the menu item. Example: 9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d
     *
     * @queryParam search string Accepted but currently ignored. No-example
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {"status":"Request was succesful","message":null,"data":{"current_page":1,"data":[{"id":10,"uuid":"9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d","user_id":12,"restaurant_id":3,"rider_id":7,"total_amount":"25.50","delivery":1,"delivery_fee":"3.20","status":5,"service_charge":"1.20","created_at":"2024-05-02T17:30:00.000000Z","updated_at":"2024-05-02T18:10:00.000000Z","country":"United Kingdom","order_items":[{"id":1,"order_id":10,"menu_id":4,"quantity":2,"subtotal":"20.00"}],"user":{"id":12,"name":"Jane Doe","email":"jane@example.com"}}],"first_page_url":"https://moboeats.com/api/v1/restaurant/menus/9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d/orders?page=1","from":1,"last_page":1,"last_page_url":"https://moboeats.com/api/v1/restaurant/menus/9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d/orders?page=1","links":[{"url":null,"label":"&laquo; Previous","active":false},{"url":"https://moboeats.com/api/v1/restaurant/menus/9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d/orders?page=1","label":"1","active":true},{"url":null,"label":"Next &raquo;","active":false}],"next_page_url":null,"path":"https://moboeats.com/api/v1/restaurant/menus/9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d/orders","per_page":10,"prev_page_url":null,"to":1,"total":1}}
     * @response 404 scenario="Menu not found" {"message":"No query results for model [App\\Models\\Menu] 9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d"}
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     *
     * @responseField data object Laravel paginator of orders.
     * @responseField data.data object[] Orders with `order_items` and `user`.
     */
    public function menuOrders(Request $request, Menu $menu)
    {
        $search = $request->query('search');

        $orders = Order::with('orderItems', 'user')
                            ->whereHas('orderItems', function ($query) use ($menu) {
                                $query->where('menu_id', $menu->id);
                            })
                            ->whereIn('status', [2, 3, 4, 5])
                            ->orderBy('created_at', 'DESC')
                            ->paginate(10);

        return $this->success($orders);
    }

    /**
     * Review a menu item
     *
     * Stores a rating (and optional review text) for a menu item by the authenticated customer, optionally linked to
     * an order. Validation errors are returned with status 403.
     *
     * @group Reviews
     *
     * @bodyParam order_id integer The id of the order the item was part of. Example: 10
     * @bodyParam menu_id integer required The id of the menu item. Example: 4
     * @bodyParam menu_rating integer required The rating of the menu item, from 1 to 5. Example: 5
     * @bodyParam menu_review string A review of the menu item. Example: Delicious and well packed.
     *
     * @response 200 scenario="Saved" {"status":"Request was succesful","message":"Review successfully saved","data":{"menu":{"id":4,"uuid":"9b1c2f4e-3a6d-4c8e-9f21-7d5e8a1b2c3d","title":"Chicken Burger","description":"Grilled chicken burger with fries","restaurant_id":3,"status":2,"preparation_time":"20","created_by":"owner@example.com","updated_by":"owner@example.com","stripe_product_id":"prod_PqR123","created_at":"2024-05-01T10:00:00.000000Z","updated_at":"2024-05-01T10:00:00.000000Z"}}}
     * @response 403 scenario="Validation error" {"status":"Error has occured...","message":{"menu_rating":["The menu rating field is required."]},"data":"Error"}
     * @response 404 scenario="Menu not found" {"status":"Error has occured...","message":"Menu item not found","data":"Error"}
     *
     * @responseField data.menu object The reviewed menu item.
     */
    public function storeReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu_id' => ['required'],
            'menu_rating' => ['required', 'integer', 'max:5'],
            'menu_review' => ['nullable', 'string', 'sometimes']
        ]);

        if ($validator->fails()) {
            return $this->error('Error', $validator->messages(), 403);
        }

        $menu = Menu::find($request->menu_id);

        if (!$menu) {
            return $this->error('Error', 'Menu item not found', 404);
        }

        $menu->reviews()->create([
            'user_id' => auth()->id(),
            'order_id' => $request->has('order_id') && !empty($request->order_id) ? $request->order_id : NULL,
            'rating' => $request->menu_rating,
            'review' => $request->menu_review
        ]);

        return $this->success(['menu' => $menu], 'Review successfully saved');
    }

    /**
     * Download menu import template
     *
     * Downloads the Excel template (menu-template.xlsx) used for bulk uploading menu items.
     *
     * @response 200 scenario="File download" <<binary>> The menu-template.xlsx file.
     * @response 422 scenario="Restaurant user without a restaurant" {"data":"no_restaurant"}
     */
    public function downloadTemplate()
    {
        if (request()->wantsJson()) {
            return response()->download(public_path('Moboeats Menu Template.xlsx'), 'menu-template.xlsx');
        }

        return response()->download(public_path('Moboeats Menu Template.xlsx'), 'menu-template.xlsx');
    }

    /**
     * Bulk upload menu items
     *
     * Imports menu items from an Excel file based on the menu template (columns such as title, description,
     * branches, categories, active). Branches must be restaurants owned by the user; missing categories are created.
     * JSON responses are only returned when the request accepts JSON; otherwise the response is empty.
     *
     * @bodyParam file file required The filled-in menu template (.xlsx). No-example
     *
     * @response 200 scenario="Uploaded" {"message":"Menu items uploaded successfully","uploaded":5,"total_rows":5}
     * @response 400 scenario="Nothing uploaded" {"message":"No Menu Items were uploaded successfully","uploaded":0,"total_rows":5}
     * @response 422 scenario="Validation error" {"file":["The file field is required."]}
     *
     * @responseField uploaded integer Number of menu items created.
     * @responseField total_rows integer Number of data rows in the file.
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'mimes:xlsx']
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $import = new MenuItemsImport();

        Excel::import($import, $request->file('file')->store('public'));

        if ($request->wantsJson()) {
            if ($import->data > 0) {
                return response()->json(
                [
                    'message' => 'Menu items uploaded successfully',
                    'uploaded' => $import->data,
                    'total_rows' => collect($import->total_rows)->first() - 1,
                ],
                200
                );
            }

            return response()->json(
                [
                'message' => 'No Menu Items were uploaded successfully',
                'uploaded' => $import->data,
                'total_rows' => collect($import->total_rows)->first() - 1,
                ],
                400
            );
        }
    }
}

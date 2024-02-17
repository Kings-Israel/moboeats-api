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

/**
 * @group Menu Management
 *
 * Menu API resource
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
     * Display a listing of the resource.
     *
     * @queryParam categories to fetch categories associated with Menu
     * @queryParam subCategories to fetch categories associated with Menu
     * @queryParam restaurant to fetch restaurant that owns with Menu
     */
    public function index(Request $request)
    {
        if (auth()->check()) {
            $filter =  new MenuFilter();
            $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]

            if (auth()->user()->hasRole('restaurant')) {
                $menu = Menu::whereIn('restaurant_id', auth()->user()->restaurants->pluck('id'));
            } else if(auth()->user()->hasRole('restaurant employee')) {
                $search = $request->query('search');

                $restaurant = UserRestaurant::where('user_id', auth()->id())->first();

                $menu = Menu::where('restaurant_id', $restaurant->resturant_id);
            } else {
                $menu = Menu::active()
                            ->whereHas('menuPrices', function ($query) {
                                $query->where('status', '2');
                            })
                            ->whereHas('images');
            }

            return new MenuCollection($menu->with(['restaurant', 'menuPrices', 'categories.food_sub_categories', 'images', 'discount'])->paginate(6)->appends($request->query()));
        } else {
            $menu = Menu::active()
                            ->whereHas('menuPrices', function ($query) {
                                $query->where('status', '2');
                            })
                            ->whereHas('images');

            return new MenuCollection($menu->with(['restaurant', 'menuPrices', 'categories.food_sub_categories', 'images', 'discount'])->paginate());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuRequest $request, $id)
    {
        $restaurant = Restaurant::where('uuid', $id)->first();
        if (!$restaurant) {
            $restaurant = Restaurant::where('id', $id)->first();
        }

        if (!auth()->user()->hasRole('restaurant')) {
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
                'status' => 2,
                'created_by' => auth()->user()->email,
                'updated_by' => auth()->user()->email,
            ]);

            if (!$menu) {
                DB::rollBack();
                return $this->error('', 'unable to create menu item', 403);
            }
            if ($request->has('standarPrice')) {
                MenuPrice::create([
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

            activity()->causedBy(auth()->user())->performedOn($menu)->log('added new menu item');

            DB::commit();
            return new MenuResource($menu);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);

        }
    }

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

    public function export(Request $request)
    {
        $search = $request->query('search');

        $unique = explode('-', Str::uuid())[0];

        Excel::store(new MenuExport($search), 'menu'.$unique.'.xlsx', 'exports');

        return Storage::disk('exports')->download('menu'.$unique.'.xlsx');
    }

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
                    'created_by' => auth()->user()->email,
                    'updated_by' => auth()->user()->email,
                ]);

                if (!$menu) {
                    DB::rollBack();
                    return $this->error('', 'unable to create menu item', 403);
                }

                if ($request->has('standarPrice')) {
                    MenuPrice::create([
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
     * Display the specified resource.
     */
    public function show(Menu $menu)
    {
        $includeCategories = request()->query('categories');
        $includesubCategories = request()->query('subCategories');
        $includesubImages = request()->query('images');

        if ($includesubCategories &&  $includeCategories && $includesubImages) {
            return new MenuResource($menu->loadMissing(['categories', 'subCategories', 'images']));
        } else {
            if ($includeCategories) {
                return new MenuResource($menu->loadMissing('categories'));
            }
            if ($includesubCategories) {
                return new MenuResource($menu->loadMissing('subCategories'));
            }
            if ($includesubImages) {
                return new MenuResource($menu->loadMissing('images'));
            }
        }
        return new MenuResource($menu->loadMissing('menuPrices', 'categories.subCategories', 'discount'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuRequest $request, Menu $menu = NULL, $id = NULL)
    {
        if ($menu == NULL) {
            $menu = Menu::find($id);
        }

        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role != 'restaurant') {
                return $this->error('', 'Unauthorized', 401);
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
                DB::commit();
                return new MenuResource($menu);
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);

            }
        }
    }

    /**
     * Get Groceries
     *
     */
    public function groceries(Request $request)
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole('orderer')) {
                $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

                $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

                $menu = Menu::active()->with('images', 'categories', 'subCategories', 'restaurant', 'discount')->whereIn('id', $category_menus)->paginate(10);

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

            $menu = Menu::active()->with('images', 'categories', 'subCategories', 'restaurant', 'discount')->whereIn('id', $category_menus)->paginate(10);

            return MenuResource::collection($menu);
        }

    }

    /**
     * Get Restaurant Menu
     *
     */
    public function restaurantMenu(Request $request, Restaurant $restaurant = null)
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole('orderer')) {
                $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

                $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

                $menu = Menu::active()
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

            $menu = Menu::active()->with('images', 'categories', 'subCategories', 'restaurant', 'discount')
                                ->whereNotIn('id', $category_menus)
                                ->when($restaurant && $restaurant != NULL, function ($query) use ($restaurant) {
                                    $query->where('restaurant_id', $restaurant->id);
                                })
                                ->paginate(10);

            return MenuResource::collection($menu);
        }

    }

    public function exportGroceries(Request $request)
    {
        $search = $request->query('search');

        $unique = unique();

        Excel::store(new GroceryExport($search), 'groceries'.$unique.'.xlsx', 'exports');

        return Storage::disk('exports')->download('groceries'.$unique.'.xlsx');
    }

    public function restaurantGroceries(Restaurant $restaurant)
    {
        $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

        $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

        $menu = Menu::with('images', 'categories', 'subCategories')->where('restaurant_id', $restaurant->id)->whereIn('id', $category_menus)->paginate(10);

        return MenuResource::collection($menu);
    }
}

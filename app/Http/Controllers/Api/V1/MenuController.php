<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\V1\MenuFilter;
use App\Models\Menu;
use App\Http\Requests\V1\StoreMenuRequest;
use App\Http\Requests\V1\UpdateMenuRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\MenuCollection;
use App\Http\Resources\V1\MenuResource;
use App\Models\CategoryMenu;
use App\Models\FoodCommonCategory;
use App\Models\MenuImage;
use App\Models\MenuPrice;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\Admin\UploadFileTrait;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        $filter =  new MenuFilter();
        $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
        $includeCategories = $request->query('categories');
        $includesubCategories = $request->query('subCategories');
        $includesubImages = $request->query('images');

        if (auth()->user()->hasRole('restaurant')) {
            $menu = Menu::whereHas('menuPrices')->whereIn('restaurant_id', auth()->user()->restaurants->pluck('id'))->where($filterItems);
        } else {
            $menu = Menu::whereHas('menuPrices')->where($filterItems);
        }

        if ($includesubCategories &&  $includeCategories && $includesubImages) {
            // $menu = $menu->with(['restaurant','categories','subCategories']);
            $menu = $menu->with(['categories.food_sub_categories','subCategories', 'images']);
        } else {
            if ($includeCategories) {
                // $menu = $menu->with(['restaurant','categories']);
                $menu = $menu->with('categories');
            }
            if ($includesubCategories) {
                $menu = $menu->with('categories.food_sub_categories');
            }
            if ($includesubImages) {
                $menu = $menu->with('images');
            }
        }

        return new MenuCollection($menu->with(['restaurant','menuPrices', 'categories.food_sub_categories'])->paginate()->appends($request->query()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuRequest $request, $id)
    {
        $restaurant = Restaurant::where('id', $id)->orWhere('uuid', $id)->first();
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

                if($request->hasFile('image')){
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

                DB::commit();
                return new MenuResource($menu);
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);

            }
        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }

    public function updateImages(Request $request, $id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return $this->error('', 'Menu not found', 404);
        }

        $current_images = MenuImage::where('menu_id', $id)->get();

        foreach ($current_images as $image) {
            Storage::disk('public')->delete('/menus/images/'.$image->image_url);
            $image->delete();
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
            // if (is_array($image)) {
            //     foreach($image as $key => $data) {
            //     }
            // }
            // $fileName = $this->generateFileName2($request->file('image'));
            // $filename = $request->file('image')->storeAs('menus/images', $fileName, 'public');
            // if ($filename) {
            // }
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
        return new MenuResource($menu->loadMissing('menuPrices', 'categories.subCategories'));
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

                if ($request->subCategoryIds) {
                    foreach ($subcategoryIds as $categoryId) {
                        $syncData2[$categoryId] = [
                            'uuid' => Str::uuid(),
                            'menu_id' => $menu->id,
                            'created_by' => auth()->user()->email,
                            'created_at' => now()->format('Y-m-d H:i:s'),
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
    public function groceries()
    {
        $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

        $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

        $menu = Menu::with('images', 'categories', 'subCategories', 'restaurant')->whereIn('id', $category_menus)->paginate(1);

        return MenuResource::collection($menu);
    }
}

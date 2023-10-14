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
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\Admin\UploadFileTrait;
use App\Traits\HttpResponses;
use Illuminate\Support\Facades\Auth;

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
            $menu = $menu->with(['categories','subCategories', 'images']);
        } else {
            if ($includeCategories) {
                // $menu = $menu->with(['restaurant','categories']);
                $menu = $menu->with('categories');
            }
            if ($includesubCategories) {
                $menu = $menu->with('subCategories');
            }
            if ($includesubImages) {
                $menu = $menu->with('images');
            }
        }

        return new MenuCollection($menu->with(['restaurant','menuPrices'])->paginate()->appends($request->query()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuRequest $request)
    {
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
                    'restaurant_id' => $request->restaurantId,
                    'status' => 2,
                    'created_by' => auth()->user()->email,
                    'updated_by' => auth()->user()->email,
                ]);

                if (!$menu) {
                    DB::rollBack();
                    return $this->error('', 'unable to create menu item', 403);
                }
                MenuPrice::create([
                    'menu_id' => $menu->id,
                    'description' => 'standard',
                    'price' => $request->standardPrice,
                    'status' => 2,
                    'created_by' => auth()->user()->email,
                ]);
                if($request->hasFile('image')){
                    $image = MenuImage::create([
                        'menu_id' => $menu->id,
                        'image_url' => $fileName,
                        'sequence' => 1,
                        'status' => 2,
                        'created_by' => auth()->user()->email,
                    ]);
                }

                if($request->hasFile('image')){
                    $fileData = ['file' => $request->file('image'),'fileName' => $fileName, 'storageName' => $this->settings['storageName'], 'prevFile' => null];
                    if(!$this->uploadFile($fileData)){
                        DB::rollBack();
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

                // foreach ($request->input('subcategoryIds') as $subCategoryId) {
                //     $menu->subCategories()->attach($subCategoryId, [
                //         // Add more pivot table attributes here
                //         'uuid' => Str::uuid(),
                //         'created_by' => auth()->user()->email,
                //         'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //     ]);
                // }

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
        return new MenuResource($menu->loadMissing('menuPrices'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuRequest $request, Menu $menu)
    {
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
                    $standardPrice = MenuPrice::where('menu_id', $menu->id)
                    ->where('status', 2)
                    ->where('description', 'standard')
                    ->first();
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
                        'created_by' => $request->updatedBy,
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
                        'uuid' => Str::uuid(),
                        'created_by' => $request->updatedBy,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ];
                }
                foreach ($subcategoryIds as $categoryId) {
                    $syncData2[$categoryId] = [
                        'uuid' => Str::uuid(),
                        'created_by' => $request->updatedBy,
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

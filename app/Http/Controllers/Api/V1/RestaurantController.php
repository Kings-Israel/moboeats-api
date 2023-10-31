<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Restaurant;
use App\Http\Requests\V1\UpdateRestaurantRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RestaurantCollection;
use App\Http\Resources\V1\RestaurantResource;
use App\Filters\V1\RestaurantFilter;
use App\Http\Requests\V1\StoreRestaurantRequest;
use App\Http\Resources\V1\FoodCommonCategoryCollection;
use App\Http\Resources\V1\RiderCollection;
use App\Http\Resources\V1\UserCollection;
use App\Http\Resources\V1\UserResource;
use App\Models\FoodCommonCategory;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\UserRestaurant;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\Admin\UploadFileTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewAccount;
use App\Notifications\UpdatedRestaurant;

/**
 * @group Restaurant Management
 *
 * Restaurant API resource
 */

class RestaurantController extends Controller
{
    use HttpResponses;
    use UploadFileTrait;

    public $settings = [
        'model' =>  '\\App\\Models\\Restaurant',
        'caption' =>  "Restaurant",
        'storageName' =>  "companyLogos",
    ];
    /**
     * Display a listing of the resource.
     *
     * @queryParam questionnaire to fetch associated restaurant questionnaire answers
     */
    public function index(Request $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;

            if ($role === 'orderer') {
                $radius = 10;
                $latitude = $request->latitude;
                $longitude = $request->longitude;
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
                $includeQuestionnaire = $request->query('questionnaire');
                $restaurants = Restaurant::InOperation()->Approved()->where($filterItems);

                // $restaurants = Restaurant::select(DB::raw("*,
                //             (6371 * acos(cos(radians($request->latitude))
                //             * cos(radians(latitude))
                //             * cos(radians(longitude)
                //             - radians($request->longitude))
                //             + sin(radians($request->latitude))
                //             * sin(radians(latitude))))
                //             AS distance"))
                //     ->having('distance', '<=', $radius)
                //     ->orderBy('distance');

                // if ($includeQuestionnaire) {
                //     $restaurants = $restaurants->with('questionnaire');
                // }
                return new RestaurantCollection($restaurants->with('questionnaire')->paginate());
            }
            if ($role === 'restaurant') {
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
                $includeQuestionnaire = $request->query('questionnaire');

                $restaurants = Restaurant::withCount('orders', 'menus')->with('orders.payment', 'menus')->where('user_id', Auth::user()->id)->where($filterItems)->orderBy('created_at', 'DESC');

                if ($includeQuestionnaire) {
                    $restaurants = $restaurants->with('questionnaire');
                }

                return new RestaurantCollection($restaurants->paginate(10)->appends($request->query()));
            }

            if ($role === 'restaurant employee') {
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]

                $restaurant = UserRestaurant::where('user_id', auth()->id())->first();

                $restaurants = Restaurant::withCount('orders', 'menus')->with('orders.payment', 'menus')->where('id', $restaurant->restaurant_id)->where($filterItems)->first();

                return new RestaurantCollection($restaurants);
            }

        } else {
            return $this->error('', 'Unauthorized', 401);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRestaurantRequest $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }

            try {
                DB::beginTransaction();
                $request->merge([
                    'user_id' => Auth::user()->id,
                ]);
                $restaurant = Restaurant::create($request->all());
                if($request->hasFile('logo')){
                    $restaurant->update([
                        'logo' => $request->logo->store('companyLogos/logos', 'public')
                    ]);
                }
                DB::commit();
                return new RestaurantResource($restaurant);
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Restaurant $restaurant)
    {
        return $this->isNotAuthorized($restaurant) ?  $this->isNotAuthorized($restaurant) : new RestaurantResource($restaurant->load('operatingHours', 'documents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }

            try {
                DB::beginTransaction();
                if ($this->isNotAuthorized($restaurant)) {
                    return $this->isNotAuthorized($restaurant);
                }
                $restaurant_logo = explode('/', $restaurant->logo);
                $restaurant->update($request->all());
                if($request->hasFile('logo')) {
                    Storage::disk('public')->delete('/companyLogos/logos/'.end($restaurant_logo));
                    $restaurant->update([
                        'logo' => $request->logo->store('companyLogos/logos', 'public')
                    ]);
                }
                if ($restaurant->status == 'Pending' || $restaurant->status == 'Denied') {
                    // Update restaurant status to pending
                    if ($restaurant->status == 'Denied') {
                        $restaurant->update([
                            'status' => '1'
                        ]);
                    }
                    // Notify admin to review the restaurant
                    $admin = User::where('email', 'admin@moboeats.com')->first();
                    $admin->notify(new UpdatedRestaurant($restaurant));
                }
                DB::commit();
                return new RestaurantResource($restaurant);
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Restaurant $restaurant)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                return $this->error('', 'Unauthorized', 401);
            }
            try {
                DB::beginTransaction();
                return $this->isNotAuthorized($restaurant) ?  $this->isNotAuthorized($restaurant) :  $restaurant->delete();
                DB::commit();
                // return response(null, 204);
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        }
    }

    public function isNotAuthorized($restaurant)
    {
        $user = User::where('id',Auth::user()->id)->first();
        if ($user->hasRole(Auth::user()->role_id)) {
            $role = $user->role_id;
            if ($role === 'orderer') {
                return '';
            } else {
                if (Auth::user()->id !== $restaurant->user_id) {
                    return $this->error('', 'You are not authorized to make this request', 401);
                } else {
                    return '';
                }
            }
        }
        return '';
    }

    public function riders($restaurant_id)
    {
        $restaurant = Restaurant::find($restaurant_id);

        // Get Unassigned Couriers and order by closest
        $riders = User::where('device_token', '!=', NULL)
                        ->whereHas('roles', function($query) {
                            $query->where('name', 'rider');
                        })
                        // ->where('status', 'Active')
                        ->where(function($query) {
                            $assigned_riders = Order::where('rider_id', '!=', NULL)->get()->pluck('rider_id');
                            $query->whereNotIn('id', $assigned_riders);
                        })
                        ->get()
                        // Filter by distance to restaurant
                        ->each(function($rider, $key) use ($restaurant) {
                            if ($rider->latitude != NULL && $rider->lognitude != NULL) {
                                $business_location = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$rider->latitude.','.$rider->longitude.'&destinations='.$restaurant->latitude.','.$restaurant->longitude.'&key='.config('services.map.key'));

                                if (json_decode($business_location)->rows[0]->elements[0]->status != "NOT_FOUND" && json_decode($business_location)->rows[0]->elements[0]->status != "ZERO_RESULTS") {
                                    $distance = json_decode($business_location)->rows[0]->elements[0]->distance->text;
                                    $time = json_decode($business_location)->rows[0]->elements[0]->duration->text;
                                    $rider['distance'] = $distance;
                                    $rider['time_away'] = $time;
                                }
                            } else {
                                $rider['distance'] = NULL;
                                $rider['time_away'] = NULL;
                            }
                        })
                        // Order by distance and time
                        ->sortBy([
                            fn($a, $b) => (double) explode(' ', $a['distance'])[0] >= (double) explode(' ',$b['distance'])[0],
                        ]);

        return $this->success(UserResource::collection($riders));
    }

    public function dashboard()
    {
        $months = [];
        // Get past 12 months
        $months = [];
        for ($i = 12; $i >= 0; $i--) {
            $month = Carbon::today()->startOfMonth()->subMonth($i);
            $year = Carbon::today()->startOfMonth()->subMonth($i)->format('Y');
            array_push($months, $month);
        }

        // Format months
        $months_formatted = [];
        foreach ($months as $key => $month) {
            array_push($months_formatted, Carbon::parse($month)->format('m-Y'));
        }

        $restaurants_count = Restaurant::where('user_id', auth()->id())->count();
        $restaurant_ids = auth()->user()->restaurants->pluck('id');
        $orders_count = Order::whereIn('restaurant_id', $restaurant_ids)->count();
        $delivered_orders_count = Order::whereIn('restaurant_id', $restaurant_ids)->where('status', 'Delivered')->count();

        $order_ids = [];

        if (auth()->user()->hasRole('restaurant')) {
            $order_ids = Order::whereIn('restaurant_id', $restaurant_ids)
                            ->get()
                            ->pluck('id');
        }

        if (auth()->user()->hasRole('restaurant employee')) {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');

            $order_ids = Order::whereIn('restaurant_id', $restaurant_ids)
                                ->get()
                                ->pluck('id');
        }

        $revenue = Payment::whereIn('order_id', $order_ids)->where('status', '2')->sum('amount');

        // Top Restaurants
        $top_restaurants = Restaurant::where('user_id', auth()->id())
                                        // ->whereHas('orders')
                                        ->withCount('orders')
                                        ->with('orders.payment')
                                        ->orderBy('orders_count', 'DESC')
                                        ->get()
                                        ->take(3);

        $pending_approval = Restaurant::where('user_id', auth()->id())->where('status', 1)->count();
        $approved_restaurants = Restaurant::where('user_id', auth()->id())->where('status', 2)->count();
        $rejected_restaurants = Restaurant::where('user_id', auth()->id())->where('status', 3)->count();

        $top_restaurants_formatted = [];
        if (auth()->user()->hasRole('restaurant')) {
            foreach ($top_restaurants as $restaurant) {
                $payment['payments']['labels'] = $months_formatted;
                $payment['payments']['amounts'] = [];

                $order_ids = $restaurant->orders->pluck('id');

                foreach ($months as $month) {
                    $sum = Payment::whereIn('order_id', $order_ids)->where('status', 2)->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->sum('amount');
                    array_push($payment['payments']['amounts'], $sum);
                }

                $restaurant = array_merge($restaurant->toArray(), $payment);
                array_push($top_restaurants_formatted, $restaurant);
            }
        }

        // Most Popular Menu Items
        // $popular_menus = OrderItem::with('menu')
        //                         ->whereIn('order_id', $orders_ids)
        //                         ->get()
        //                         ->take(10)
        //                         ->groupBy(function ($item, $key) {
        //                             return Menu::where('id', $item->menu_id)->first()->title;
        //                         });
        $popular_menus = Menu::whereHas('orderItems')
                            ->withCount('orderItems')
                            ->with('restaurant', 'images', 'menuPrices')
                            ->whereIn('restaurant_id', $restaurant_ids)
                            ->orderBy('order_items_count', 'DESC')
                            ->get()
                            ->take(10);

        return $this->success([
            'restaurants_count' => $restaurants_count,
            'orders_count' => $orders_count,
            'delivered_orders_count' => $delivered_orders_count,
            'revenue' => $revenue,
            'popular_menus' => $popular_menus,
            'top_restaurants' => $top_restaurants_formatted,
            'pending_approval' => $pending_approval,
            'approved_restaurants' => $approved_restaurants,
            'rejected_restaurants' => $rejected_restaurants
        ]);
    }

    public function payments(Request $request)
    {
        if (auth()->user()->hasRole('restaurant')) {
            $restaurant_ids = auth()->user()->restaurants->pluck('id');
        } else {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
        }

        $orders_ids = Order::whereIn('restaurant_id', $restaurant_ids)
                        ->get()
                        ->pluck('id');

        $search = $request->query('search');

        $payments = Payment::with('order.user', 'order.restaurant')
                            ->whereIn('order_id', $orders_ids)
                            ->where('status', '2')
                            ->when($search && $search != '', function ($query) use ($search) {
                                $query->where(function($query) use ($search) {
                                    $query->where('transaction_id', 'LIKE', '%' . $search . '%')
                                        ->orWhereHas('order', function ($query) use ($search) {
                                            $query->where('uuid', 'LIKE', '%' . $search . '%')
                                                ->whereHas('restaurant', function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%' . $search . '%')->orWhere('name_short', 'LIKE', '%' . $search . '%');
                                                })
                                                ->orWhereHas('user', function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%' . $search . '%');
                                                });
                                    });
                                });
                            })
                            ->orderBy('created_at', 'DESC')
                            ->paginate();

        return $this->success($payments);
    }

    public function restaurantMenu(Request $request, Restaurant $restaurant)
    {
        $search = $request->query('search');

        $menu = Menu::with('images', 'menuPrices', 'subCategories', 'categories.food_sub_categories')
                    ->withCount('orderItems')
                    ->where('restaurant_id', $restaurant->id)
                    ->when($search && $search != '', function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('title', 'LIKE', '%' . $search . '%')
                                    ->orWhere('description', 'LIKE', '%' . $search . '%');
                        });
                    })
                    ->orderBy('created_at', 'DESC')
                    ->paginate(9);

        $categories = FoodCommonCategory::with('food_sub_categories')->where('restaurant_id', NULL)->orWhere('restaurant_id', $restaurant->id)->get();

        return $this->success(['menu' => $menu, 'categories' => new FoodCommonCategoryCollection($categories)]);
    }

    /**
     * Get Categories added by the restaurant
     *
     * @urlParam id The ID or the UUID of the restaurant
     *
     */
    public function categories($id)
    {
        $restaurant = Restaurant::where('uuid', $id)->orWhere('uuid', $id)->first();

        $categories = FoodCommonCategory::with('food_sub_categories')->where('restaurant_id', $restaurant->id)->paginate(8);

        return $this->success(['categories' => $categories]);
    }

    /**
     * Add a new category for the specified restaurant
     */
    public function addCategory(Request $request, Restaurant $restaurant)
    {
        $request->validate([
            'title' => ['required'],
            'description' => ['required'],
        ]);

        try {
            DB::beginTransaction();
            $request->merge([
                'restaurant_id' => $restaurant->id,
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

            $categories = FoodCommonCategory::paginate(7);
            return $this->success($categories);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Update a category
     * @urlParam uuid The uuid of the restaurant
     * @urlParam id The id of the category
     */
    public function updateCategory(Request $request, $uuid, $id)
    {
        $request->validate([
            'title' => ['required'],
            'description' => ['required'],
        ]);

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

    public function restaurantOrders(Request $request, Restaurant $restaurant)
    {
        $search = $request->query('search');

        $orders = Order::with('user')
                        ->where('restaurant_id', $restaurant->id)
                        ->when($search && $search != '', function ($query) use ($search) {
                            $query->where('uuid', 'LIKE', '%' . $search . '%')
                                ->where(function ($query) use ($search) {
                                    $query->orWhereHas('user', function ($query) use ($search) {
                                        $query->where('name', 'LIKE', '%' . $query . '%');
                                    });
                                });
                        })
                        ->orderBy('created_at', 'DESC')
                        ->paginate(5);

        return $this->success($orders);
    }

    public function restaurantPayments(Request $request, Restaurant $restaurant)
    {
        $search = $request->query('search');

        $payments = Payment::with('order.restaurant', 'order.user')
                            ->where('transaction_id', '!=', NULL)
                            ->whereHas('order', function ($query) use ($restaurant) {
                                $query->whereHas('restaurant', function ($query) use ($restaurant) {
                                    $query->where('id', '=', $restaurant->id);
                                });
                            })
                            ->when($search && $search != '', function ($query) use ($search) {
                                $query->whereHas('order', function ($query) use ($search) {
                                    $query->where(function ($query) use ($search) {
                                        $query->where('uuid', 'LIKE', '%'.$search.'%')
                                            ->orWhereHas('user', function ($query) use ($search) {
                                                $query->where('name', 'LIKE', '%'.$search.'%');
                                            });
                                    });
                                });
                            })
                            ->orderBy('created_at', 'DESC')
                            ->paginate(5);

        return $this->success($payments);
    }

    public function employees(Request $request)
    {
        $search = $request->query('search');

        $user_restaurants = auth()->user()->restaurants->pluck('id');
        $restaurants = UserRestaurant::whereIn('restaurant_id', $user_restaurants)->get()->pluck('user_id');

        $users = User::whereIn('id', $restaurants)
                        ->when($search && $search != '', function ($query) use ($search) {
                            $query->where(function ($query) use ($search) {
                                $query->where('first_name', 'LIKE', '%'.$search.'%')
                                        ->orWhere('email', 'LIKE', '%'.$search.'%')
                                        ->orWhere('phone_number', 'LIKE', '%'.$search.'%');
                            });
                        })
                        ->paginate(10);

        return $this->success($users);
    }

    public function restaurantEmployees(Request $request, Restaurant $restaurant)
    {
        $search = $request->query('search');

        $restaurants = UserRestaurant::where('restaurant_id', $restaurant->id)->get()->pluck('user_id');

        $users = User::whereIn('id', $restaurants)
                        ->when($search && $search != '', function ($query) use ($search) {
                            $query->where(function ($query) use ($search) {
                                $query->where('first_name', 'LIKE', '%'.$search.'%')
                                        ->orWhere('email', 'LIKE', '%'.$search.'%')
                                        ->orWhere('phone_number', 'LIKE', '%'.$search.'%');
                            });
                        })
                        ->paginate(10);

        return $this->success($users);
    }

    public function addEmployee(Restaurant $restaurant, Request $request)
    {
        $request->validate([
            'first_name' => 'required', 'string',
            'last_name' => 'required', 'string',
            'email' => 'required', 'email',
            'phone_number' => 'required',
            'avatar' => ['nullable', 'mimes:jpg,png', 'max:9000'],
        ]);

        $generate_password = rand(1000000000, 99999999999);

        $user = User::create([
            'name' => $request->first_name.' '.$request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'user_type' => 'restaurant employee',
            'password' => bcrypt($generate_password),
            'status' => 2,
            'image' => $request->hasFile('avatar') ? pathinfo($request->avatar->store('avatar', 'user'), PATHINFO_BASENAME) : NULL,
        ]);

        UserRestaurant::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $user->addRole('restaurant employee');

        Mail::to($request->email)->send(new NewAccount($user, $generate_password));

        return $this->success($user, 'User created successfully');
    }

    public function updateEmployee(User $user, Request $request)
    {
        $request->validate([
            'first_name' => 'required', 'string',
            'last_name' => 'required', 'string',
            'email' => 'required', 'email',
            'phone_number' => 'required',
            'avatar' => ['nullable', 'max:9000'],
            'suspend' => ['required', 'in:1,2'],
        ]);

        $generate_password = rand(1000000000, 99999999999);

        $user->update([
            'name' => $request->first_name.' '.$request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'status' => 2,
            'password' => bcrypt($generate_password),
            'status' => $request->suspend,
        ]);

        if ($request->hasFile('avatar')) {
            $image = explode('/', $user->image);
            Storage::disk('user')->delete('/avatar/'.end($image));
            $user->update([
                'image' => pathinfo($request->avatar->store('avatar', 'user'), PATHINFO_BASENAME),
            ]);
        }

        Mail::to($request->email)->send(new NewAccount($user, $generate_password));

        return $this->success($user, 'User update successfully');
    }
}

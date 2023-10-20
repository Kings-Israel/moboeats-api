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
                $restaurants = Restaurant::where($filterItems);

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
        // $restaurant = Restaurant::where('id', $restaurant)->orWhere('uuid', $restaurant)->first();
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
        // Get past 9 months
        $months = [];
        // $days = [0, 29, 59, 89, 119, 149, 179, 209, 239];
        // $days = [239, 209, 179, 149, 119, 89, 59, 29, 0];
        $days = [10, 9, 8, 7, 6, 5, 4, 3, 2, 1, 0];
        foreach($days as $day) {
            array_push($months, now()->subMonths($day));
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

        $orders_ids = Order::whereIn('restaurant_id', $restaurant_ids)
                        ->get()
                        ->pluck('id');

        $revenue = Payment::whereIn('order_id', $orders_ids)->where('status', '2')->sum('amount');

        // Top Restaurants
        $top_restaurants = Restaurant::where('user_id', auth()->id())
                                        // ->whereHas('orders')
                                        ->withCount('orders')
                                        ->with('orders.payment')
                                        ->orderBy('orders_count', 'DESC')
                                        ->get()
                                        ->take(3);

        $top_restaurants_formatted = [];
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
        ]);
    }

    public function payments(Request $request)
    {
        $restaurant_ids = auth()->user()->restaurants->pluck('id');

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

        $menu = Menu::with('images', 'menuPrices', 'categories.food_sub_categories', 'subCategories')
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

        $categories = FoodCommonCategory::all();

        return $this->success(['menu' => $menu, 'categories' => new FoodCommonCategoryCollection($categories)]);
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
}

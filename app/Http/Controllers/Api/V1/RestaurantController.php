<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Restaurant;
use App\Http\Requests\V1\UpdateRestaurantRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RestaurantCollection;
use App\Http\Resources\V1\RestaurantResource;
use App\Http\Resources\V1\ReviewResource;
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
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RestaurantExport;
use App\Exports\PaymentExport;
use App\Models\Payout;
use App\Models\SeatingArea;
use App\Models\RestaurantTable;
use App\Models\Review;
use App\Models\PromoCode;
use App\Models\Discount;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Query\JoinClause;

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

    public function index(Request $request)
    {
        $radius = 100;
        $latitude = $request->query('lat');
        $longitude = $request->query('lng');

        if (auth()->check()) {
            if (auth()->user()->hasRole('orderer')) {
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
                if ($latitude && $longitude) {
                    $restaurants = Restaurant::Approved()->hasMenu()->select(DB::raw("*,
                                (6371 * acos(cos(radians($latitude))
                                * cos(radians(latitude))
                                * cos(radians(longitude)
                                - radians($longitude))
                                + sin(radians($latitude))
                                * sin(radians(latitude))))
                                AS distance"))
                        ->having('distance', '<=', $radius)
                        ->orderBy('distance');
                } else {
                    $restaurants = Restaurant::Approved()->hasMenu()->where($filterItems);
                }


                return new RestaurantCollection($restaurants->with('questionnaire', 'reviews', 'restaurantTables.seatingArea')->paginate());
            }

            if (auth()->user()->hasRole('rider')) {
                $radius = 100;
                $latitude = $request->latitude;
                $longitude = $request->longitude;
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
                $includeQuestionnaire = $request->query('questionnaire');
                $restaurants = Restaurant::Approved()->hasMenu()->where($filterItems);

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

            if (auth()->user()->hasRole('restaurant')) {
                $search = $request->query('search');
                $status = $request->query('status');

                $restaurants = Restaurant::withCount('orders', 'menus')
                                        ->with('orders.payment', 'orders.reservation', 'menus', 'restaurantTables.seatingArea')
                                        ->where('user_id', Auth::user()->id)
                                        ->when($search && $search != '', function ($query) use ($search) {
                                            $query->where(function ($query) use ($search) {
                                                $query->where('name', 'LIKE', '%'.$search.'%')
                                                        ->orWhere('address', 'LIKE', '%'.$search.'%');
                                            });
                                        })
                                        ->when($status && $status != '', function ($query) use ($status) {
                                            $query->where(function ($query) use ($status) {
                                                $query->where('status', $status);
                                            });
                                        })
                                        ->orderBy('created_at', 'DESC')
                                        ->paginate(10);

                return new RestaurantCollection($restaurants);
            }

            if (auth()->user()->hasRole('restaurant employee')) {
                $search = $request->query('search');

                $restaurant = UserRestaurant::where('user_id', auth()->id())->first();

                $restaurants = Restaurant::withCount('orders', 'menus')
                                            ->with('orders.payment', 'orders.reservation', 'menus', 'restaurantTables.seatingArea')
                                            ->where('id', $restaurant->restaurant_id)
                                            ->when($search && $search != '', function ($query) use ($search) {
                                                $query->where(function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%'.$search.'%')
                                                            ->orWhere('address', 'LIKE', '%'.$search.'%');
                                                });
                                            })
                                            ->first();

                return new RestaurantCollection($restaurants);
            }
        } else {
            if ($latitude && $longitude) {
                $restaurants = Restaurant::Approved()->hasMenu()
                    ->select(DB::raw("*,
                            (6371 * acos(cos(radians($latitude))
                            * cos(radians(latitude))
                            * cos(radians(longitude)
                            - radians($longitude))
                            + sin(radians($latitude))
                            * sin(radians(latitude))))
                            AS distance"))
                    ->having('distance', '<=', $radius)
                    ->orderBy('distance');
            } else {
                $restaurants = Restaurant::Approved()->hasMenu();
            }

            return new RestaurantCollection($restaurants->with('questionnaire', 'reviews', 'restaurantTables.seatingArea')->paginate());
        }
    }

    /**
     * Get Rated and Reviewed Restaurants.
     */
    public function rating(Request $request)
    {
        $restaurants = Restaurant::InOperation()->Approved()->hasMenu()->rated();

        return new RestaurantCollection($restaurants->with('questionnaire', 'reviews', 'restaurantTables.seatingArea')->paginate());
    }

    public function seatingAreas()
    {
        $seating_areas = SeatingArea::all();

        return $this->success($seating_areas);
    }

    public function export(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $unique = explode('-', Str::uuid())[0];

        Excel::store(new RestaurantExport($search, $status), 'restaurants'.$unique.'.xlsx', 'exports');

        return Storage::disk('exports')->download('restaurants'.$unique.'.xlsx');
    }

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
                activity()->causedBy(auth()->user())->performedOn($restaurant)->log('registered a new restaurant');
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
     * Show Restaurant Details
     *
     * @urlParam restaurant The ID of the restaurant
     */
    public function show(Restaurant $restaurant)
    {
        if(auth()->check()) {
            return new RestaurantResource($restaurant->load('operatingHours', 'documents', 'reviews', 'restaurantTables.seatingArea', 'menus'));
        } else {
            return new RestaurantResource($restaurant->load('operatingHours', 'reviews', 'restaurantTables.seatingArea', 'menus'));
        }
    }

    /**
     * Get Restaurant Available Seating Areas
     *
     * @urlParam restaurant The ID of the restaurant
     */
    public function restaurantSeatingAreas(Request $request, Restaurant $restaurant)
    {
        if (auth()->user()->hasRole('restaurant')) {
            $restaurant_ids = auth()->user()->restaurants->pluck('id');
        } else {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
        }

        $seating_areas = RestaurantTable::with('seatingArea')
                                        ->whereIn('restaurant_id', $restaurant_ids)
                                        ->get()
                                        ->groupBy('seatingArea');

        return $this->success([
            'seating_areas' => $seating_areas,
        ]);
    }

    /**
     * Get Restaurant Reviews
     */
    public function reviews(Restaurant $restaurant)
    {
        $review = Review::where('reviewable_type', Restaurant::class)->where('reviewable_id', $restaurant->id)->orderBy('created_at', 'DESC')->get();

        return ReviewResource::collection($review);
    }

    /**
     * @authenticated
     * Update the restaurant details.
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
                $restaurant->update(collect($request->all())->except('sitting_capacity')->toArray());
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
                activity()->causedBy(auth()->user())->performedOn($restaurant)->log('update the restaurant');
                DB::commit();
                return new RestaurantResource($restaurant);
            } catch (\Throwable $th) {
                info($th);
                DB::rollBack();
                return $this->error('', $th->getMessage(), 403);
            }
        }

    }

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

    /**
     * @authenticated
     * Get Riders
     */
    public function riders($restaurant_id)
    {
        $restaurant = Restaurant::find($restaurant_id);

        $riders = User::where('device_token', '!=', NULL)
                                ->whereHas('roles', function($query) {
                                    $query->where('name', 'rider');
                                })
                                ->whereHas('rider')
                                ->where('status', 2)
                                ->where(function($query) use ($restaurant_id) {
                                    $assigned_riders = Order::where('rider_id', '!=', NULL)->where('status', 'On Delivery')->get()->pluck('rider_id');

                                    $query->when(count($assigned_riders) > 0, function ($query) use ($assigned_riders) {
                                        $query->whereNotIn('id', $assigned_riders);
                                    });
                                })
                                ->get()
                                ->each(function($rider, $key) use ($restaurant) {
                                    if ($rider->latitude != NULL && $rider->longitude != NULL && $restaurant->latitude != NULL && $restaurant->longitude != NULL) {
                                        $business_location = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$rider->latitude.','.$rider->longitude.'&destinations='.$restaurant->latitude.','.$restaurant->longitude.'&key='.config('services.map.key'));
                                        if (json_decode($business_location)->rows[0]->elements[0]->status != "NOT_FOUND" && json_decode($business_location)->rows[0]->elements[0]->status != "ZERO_RESULTS") {
                                            $distance = json_decode($business_location)->rows[0]->elements[0]->distance->text;
                                            $time = json_decode($business_location)->rows[0]->elements[0]->duration->text;
                                            $rider['distance'] = $distance;
                                            $rider['time_away'] = $time;
                                        } else {
                                            $rider['distance'] = NULL;
                                            $rider['time_away'] = NULL;
                                        }
                                    } else {
                                       $rider['distance'] = NULL;
                                       $rider['time_away'] = NULL;
                                    }
                                    })->sortBy([
                                        fn($a, $b) => (double) explode(' ', $a['distance'])[0] <= (double) explode(' ', $b['distance'])[0],
                                    ]);

        // // Get Unassigned Couriers and order by closest
        // $riders = User::where('device_token', '!=', NULL)
        //                 ->whereHas('roles', function($query) {
        //                     $query->where('name', 'rider');
        //                 })
        //                 ->whereHas('rider')
        //                 // ->where('status', 'Active')
        //                 ->where(function($query) {
        //                     $assigned_riders = Order::where('rider_id', '!=', NULL)->get()->pluck('rider_id');
        //                     $query->whereNotIn('id', $assigned_riders);
        //                 })
        //                 ->get()
        //                 // Filter by distance to restaurant
        //                 ->each(function($rider, $key) use ($restaurant) {
        //                     if ($rider->latitude != NULL && $rider->lognitude != NULL) {
        //                         $business_location = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$rider->latitude.','.$rider->longitude.'&destinations='.$restaurant->latitude.','.$restaurant->longitude.'&key='.config('services.map.key'));

        //                         if (json_decode($business_location)->rows[0]->elements[0]->status != "NOT_FOUND" && json_decode($business_location)->rows[0]->elements[0]->status != "ZERO_RESULTS") {
        //                             $distance = json_decode($business_location)->rows[0]->elements[0]->distance->text;
        //                             $time = json_decode($business_location)->rows[0]->elements[0]->duration->text;
        //                             $rider['distance'] = $distance;
        //                             $rider['time_away'] = $time;
        //                         }
        //                     } else {
        //                         $rider['distance'] = NULL;
        //                         $rider['time_away'] = NULL;
        //                     }
        //                 })
        //                 // Order by distance and time
        //                 ->sortBy([
        //                     fn($a, $b) => (double) explode(' ', $a['distance'])[0] >= (double) explode(' ',$b['distance'])[0],
        //                 ]);

        return $this->success(UserResource::collection($riders));
    }

    /**
     * @authenticated
     * Restaurant Dashboard Data
     */
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

        $orders = [];

        if (auth()->user()->hasRole('restaurant')) {
            $orders = Order::whereIn('restaurant_id', $restaurant_ids)->get();
            $deliveries = Order::whereIn('restaurant_id', $restaurant_ids)->where('delivery', true)->get();
            $dineins = Order::whereIn('restaurant_id', $restaurant_ids)->where('delivery', false)->get();
        }

        if (auth()->user()->hasRole('restaurant employee')) {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');

            $orders = Order::whereIn('restaurant_id', $restaurant_ids)->get();
            $deliveries = Order::whereIn('restaurant_id', $restaurant_ids)->where('delivery', true)->get();
            $dineins = Order::whereIn('restaurant_id', $restaurant_ids)->where('delivery', false)->get();
        }

        $revenue = Payment::where('orderable_type', Order::class)->whereIn('orderable_id', $orders->pluck('id'))->where('status', '2')->sum('amount');
        $service_charges = $orders->where('delivery_status', 'delivered')->sum('service_charge');
        $revenue = (float) $revenue - (float) $service_charges;

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
        $orders_series = [];
        $payments_series = [];
        $delivery_payment_series = [];
        $bookings_payments_series = [];
        if (auth()->user()->hasRole('restaurant')) {
            foreach ($top_restaurants as $restaurant) {
                $payment['payments']['labels'] = $months_formatted;
                $payment['payments']['amounts'] = [];

                $order_ids = $restaurant->orders->pluck('id');

                foreach ($months as $month) {
                    $sum = Payment::where('orderable_type', Order::class)->whereIn('orderable_id', $order_ids)->where('status', 2)->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->sum('amount');
                    $delivery_fees = Order::whereIn('id', $order_ids)->where('delivery_status', 'Delivered')->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->sum('delivery_fee');
                    $amount = $sum - $delivery_fees;
                    array_push($payment['payments']['amounts'], $amount);
                }

                $restaurant = array_merge($restaurant->toArray(), $payment);
                array_push($top_restaurants_formatted, $restaurant);
            }

            $restaurants_ids = auth()->user()->restaurants->pluck('id');

            foreach ($months as $month) {
                $orders = Order::whereIn('restaurant_id', $restaurants_ids)->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->count();
                array_push($orders_series, $orders);
            }

            $orders_ids = Order::whereIn('restaurant_id', $restaurants_ids)->get()->pluck('id');

            foreach ($months as $month) {
                $payments = Payment::where('orderable_type', Order::class)->whereIn('orderable_id', $orders_ids)->where('status', 2)->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->sum('amount');
                array_push($payments_series, $payments);
                $delivery_payments = Payment::where('orderable_type', Order::class)
                                        ->whereIn('orderable_id', $orders_ids)
                                        ->whereHas('orderable', function ($query) {
                                            $query->where('delivery', true);
                                        })
                                        ->where('status', 2)
                                        ->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
                                        ->sum('amount');

                array_push($delivery_payment_series, $delivery_payments);
                $booking_payments = Payment::where('orderable_type', Order::class)
                                            ->whereIn('orderable_id', $orders_ids)
                                            ->whereHas('orderable', function ($query) {
                                                $query->where('delivery', false);
                                            })
                                            ->where('status', 2)
                                            ->whereBetween('updated_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])
                                            ->sum('amount');

                array_push($bookings_payments_series, $booking_payments);
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

        $latest_discounts = PromoCode::withCount('orders')->whereIn('restaurant_id', $restaurant_ids)->orderBy('created_at', 'DESC')->get()->take(5);

        return $this->success([
            'months' => $months_formatted,
            'restaurants_count' => $restaurants_count,
            'orders_count' => $orders_count,
            'delivered_orders_count' => $delivered_orders_count,
            'revenue' => $revenue,
            'popular_menus' => $popular_menus,
            'top_restaurants' => $top_restaurants_formatted,
            'pending_approval' => $pending_approval,
            'approved_restaurants' => $approved_restaurants,
            'rejected_restaurants' => $rejected_restaurants,
            'orders_series' => $orders_series,
            'payments_series' => $payments_series,
            'delivery_payment_series' => $delivery_payment_series,
            'booking_payment_series' => $bookings_payments_series,
            'deliveries' => $deliveries,
            'dineins' => $dineins,
            'latest_discounts' => $latest_discounts,
        ]);
    }

    /**
     * @authenticated
     * Get Restaurant Payments and Earnings
     */
    public function payments(Request $request)
    {
        if (auth()->user()->hasRole('restaurant')) {
            $restaurant_ids = auth()->user()->restaurants->pluck('id');
        } else {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
        }

        $total_amount = 0;
        $paid_amount = 0;
        $unpaid_amount = 0;

        $orders = Order::whereIn('restaurant_id', $restaurant_ids)
                            ->where('delivery_status', 'delivered')
                            ->get();

        $total_amount = $orders->sum('total_amount');
        $delivery_fees = $orders->sum('delivery_fee');
        $service_charges = $orders->sum('service_charge');
        $total_amount = $total_amount - $delivery_fees - $service_charges;

        $paid_amount = Payout::whereIn('payable_id', $restaurant_ids)->where('payable_type', Restaurant::class)->sum('amount');

        $unpaid_amount = (int) $total_amount - (int) $paid_amount;

        $search = $request->query('search');
        $from_created_at = $request->query('from_created_at');
        $to_created_at = $request->query('to_created_at');
        $payments = Payment::with('orderable.user', 'orderable.restaurant')
                            ->where('orderable_type', Order::class)
                            ->whereIn('orderable_id', $orders->pluck('id'))
                            ->where('transaction_id', '!=', NULL)
                            ->when($from_created_at && $from_created_at != '', function ($query) use ($from_created_at) {
                                $query->whereDate('created_at', '>=', Carbon::parse($from_created_at));
                            })
                            ->when($to_created_at && $to_created_at != '', function ($query) use ($to_created_at) {
                                $query->whereDate('created_at', '<=', Carbon::parse($to_created_at));
                            })
                            ->when($search && $search != '', function ($query) use ($search) {
                                $query->where(function($query) use ($search) {
                                    $query->where('transaction_id', 'LIKE', '%' . $search . '%')
                                        ->orWhereHas('orderable', function ($query) use ($search) {
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

        return $this->success([
            'payments' => $payments,
            'total_amount' => $total_amount,
            'paid_amount' => $paid_amount,
            'unpaid_amount' => $unpaid_amount,
            'country' => $orders->count() ? $orders->first()->country : 'United Kingdom',
        ]);
    }

    /**
     * @authenticated
     * Get Restaurant's Tables
     */
    public function tables(Request $request)
    {
        if (auth()->user()->hasRole('restaurant')) {
            $restaurant_ids = auth()->user()->restaurants->pluck('id');
        } else {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
        }

        $tables = RestaurantTable::with('seatingArea')
                                    ->whereIn('restaurant_id', $restaurant_ids)
                                    ->paginate(10);

        return $this->success($tables);
    }

    /**
     * @authenticated
     * Get Restaurant Reserved Tables
     */
    public function reservations(Request $request)
    {
        if (auth()->user()->hasRole('restaurant')) {
            $restaurant_ids = auth()->user()->restaurants->pluck('id');
        } else {
            $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
            $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
        }

        $search = $request->query('search');
        $from_created_at = $request->query('from_created_at');
        $to_created_at = $request->query('to_created_at');

        $orders = Order::whereIn('restaurant_id', $restaurant_ids)
                            ->whereHas('reservation')
                            ->when($from_created_at && $from_created_at != '', function ($query) use ($from_created_at) {
                                $query->whereDate('reservation_date', '>=', Carbon::parse($from_created_at));
                            })
                            ->when($to_created_at && $to_created_at != '', function ($query) use ($to_created_at) {
                                $query->whereDate('reservation_date', '<=', Carbon::parse($to_created_at));
                            })
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
                            ->orderBy('reservation_date')
                            ->get();

        return $this->success([
            'orders' => $orders,
        ]);
    }

    public function exportPayments(Request $request)
    {
        $search = $request->query('search');
        $from_created_at = $request->query('from_created_at');
        $to_created_at = $request->query('to_created_at');

        $unique = explode('-', Str::uuid())[0];

        Excel::store(new PaymentExport($search, $from_created_at, $to_created_at), 'payments'.$unique.'.xlsx', 'exports');

        return Storage::disk('exports')->download('payments'.$unique.'.xlsx');
    }

    /**
     * Restaurant's Menu
     *
     * @urlParam restaurant_id The id of the restaurant
     */
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

        $menu_ids = $restaurant->menus->pluck('id');

        $discounts = Discount::with('menu')->whereIn('menu_id', $menu_ids)->get();

        return $this->success(['categories' => $categories, 'discounts' => $discounts]);
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

    /**
     * Add a new table for the specified restaurant
     */
    public function addTable(Request $request, Restaurant $restaurant)
    {
        $request->validate([
            'name' => ['required'],
            'seating_area_id' => ['required'],
            'seat_number' => ['required'],
        ]);

        try {
            DB::beginTransaction();
            $request->merge([
                'restaurant_id' => $restaurant->id,
            ]);

            RestaurantTable::create($request->all());

            DB::commit();

            $tables = RestaurantTable::paginate(7);
            return $this->success($tables);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Update a table
     * @urlParam uuid The uuid of the restaurant
     * @urlParam id The id of the category
     */
    public function updateTable(Request $request, $uuid, $id)
    {
        $request->validate([
            'name' => ['required'],
            'seating_area_id' => ['required'],
            'seat_number' => ['required'],
        ]);

        try {
            DB::beginTransaction();

            $table = RestaurantTable::find($id);

            $table->update($request->all());

            DB::commit();

            $tables = RestaurantTable::paginate(7);
            return $this->success($tables);
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
                        ->whereDoesntHave('reservation')
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

        $payments = Payment::with('orderable.restaurant', 'orderable.user')
                            ->where('transaction_id', '!=', NULL)
                            ->whereHas('orderable', function ($query) use ($restaurant) {
                                $query->whereHas('restaurant', function ($query) use ($restaurant) {
                                    $query->where('id', '=', $restaurant->id);
                                });
                            })
                            ->when($search && $search != '', function ($query) use ($search) {
                                $query->whereHas('orderable', function ($query) use ($search) {
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

    public function restaurantReservations(Request $request, Restaurant $restaurant)
    {
        $search = $request->query('search');

        $orders = Order::with('user')
                        ->whereHas('reservation')
                        ->where('restaurant_id', $restaurant->id)
                        ->when($search && $search != '', function ($query) use ($search) {
                            $query->where('uuid', 'LIKE', '%' . $search . '%')
                                ->where(function ($query) use ($search) {
                                    $query->orWhereHas('user', function ($query) use ($search) {
                                        $query->where('name', 'LIKE', '%' . $query . '%');
                                    });
                                });
                        })
                        ->orderBy('reservation_date', 'DESC')
                        ->paginate(5);

        return $this->success($orders);
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

    public function restaurantTables(Request $request, Restaurant $restaurant)
    {
        if (!$restaurant) {
            return $this->error('Restaurant Not Found', 'Restaurant Not Found', 404);
        }
        $search = $request->query('search');

        $tables = RestaurantTable::with('seatingArea')->where('restaurant_id', $restaurant->id)->paginate(10);

        return $this->success($tables);
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
            'type' => auth()->user()->type
        ]);

        UserRestaurant::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $user->addRole('restaurant employee');

        Mail::to($request->email)->send(new NewAccount($user, $generate_password, 'restaurant'));

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

    /**
     * Store review for a restaurant
     * @bodyParam restaurant_id integer The id of the restaurant
     * @bodyParam rating integer The rating from 1 - 5
     * @bodyParam review string A review of the restaurant
     */
    public function storeReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => ['required'],
            'rating' => ['required', 'integer', 'max:5', 'min:1'],
            'review' => ['nullable', 'sometimes', 'string']
        ]);

        if ($validator->fails()) {
            return $this->error('', $validator->messages(), 400);
        }

        $restaurant = Restaurant::find($request->restaurant_id);

        if (!$restaurant) {
            return $this->error('', 'No such restaurant', 404);
        }

        $restaurant->reviews()->create([
            'user_id' => auth()->id(),
            'review' => $request->has('review') && !empty($request->review) ? $request->review : NULL,
            'rating' => $request->rating
        ]);

        return $this->success('Review created successfully');
    }

    /**
     * Get Grocery SHops
     */
    public function groceryShops(Request $request)
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole('orderer')) {
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request);

                $restaurants = Restaurant::Approved()->groceryShops()->where($filterItems);

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

                return new RestaurantCollection($restaurants->with('questionnaire', 'reviews', 'restaurantTables.seatingArea')->paginate());
            }

            if (auth()->user()->hasRole('rider')) {
                $radius = 10;
                $latitude = $request->latitude;
                $longitude = $request->longitude;
                $filter =  new RestaurantFilter();
                $filterItems = $filter->transform($request); //[['column, 'operator', 'value']]
                $includeQuestionnaire = $request->query('questionnaire');
                $restaurants = Restaurant::Approved()->hasMenu()->groceryShops()->where($filterItems);

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

            if (auth()->user()->hasRole('restaurant')) {
                $search = $request->query('search');
                $status = $request->query('status');

                $restaurants = Restaurant::withCount('orders', 'menus')
                                        ->groceryShops()
                                        ->with('orders.payment', 'orders.reservation', 'menus', 'restaurantTables.seatingArea')
                                        ->where('user_id', Auth::user()->id)
                                        ->when($search && $search != '', function ($query) use ($search) {
                                            $query->where(function ($query) use ($search) {
                                                $query->where('name', 'LIKE', '%'.$search.'%')
                                                        ->orWhere('address', 'LIKE', '%'.$search.'%');
                                            });
                                        })
                                        ->when($status && $status != '', function ($query) use ($status) {
                                            $query->where(function ($query) use ($status) {
                                                $query->where('status', $status);
                                            });
                                        })
                                        ->orderBy('created_at', 'DESC')
                                        ->paginate(10);

                return new RestaurantCollection($restaurants);
            }

            if (auth()->user()->hasRole('restaurant employee')) {
                $search = $request->query('search');

                $restaurant = UserRestaurant::where('user_id', auth()->id())->first();

                $restaurants = Restaurant::withCount('orders', 'menus')
                                            ->groceryShops()
                                            ->with('orders.payment', 'orders.reservation', 'menus', 'restaurantTables.seatingArea')
                                            ->where('id', $restaurant->restaurant_id)
                                            ->when($search && $search != '', function ($query) use ($search) {
                                                $query->where(function ($query) use ($search) {
                                                    $query->where('name', 'LIKE', '%'.$search.'%')
                                                            ->orWhere('address', 'LIKE', '%'.$search.'%');
                                                });
                                            })
                                            ->first();

                return new RestaurantCollection($restaurants);
            }
        } else {
            $restaurants = Restaurant::Approved()->hasMenu()->groceryShops();

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

            return new RestaurantCollection($restaurants->with('questionnaire', 'reviews', 'restaurantTables.seatingArea')->paginate());
        }
    }
}

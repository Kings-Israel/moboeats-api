<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Models\Order;
use App\Models\Rider;
use App\Models\Payout;
use App\Models\Review;
use App\Models\Payment;
use App\Models\Setting;
use App\Imports\Payouts;
use App\Mail\NewAccount;
use App\Models\DietPlan;
use App\Models\RiderTip;
use Milon\Barcode\DNS2D;
use App\Models\Restaurant;
use App\Models\Supplement;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\AssignedOrder;
use App\Traits\HttpResponses;
use App\Jobs\SendNotification;
use App\Models\FooSubCategory;
use App\Jobs\SendCommunication;
use App\Models\PermissionGroup;
use App\Models\SupplementOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use App\Models\DietSubscription;
use App\Models\FoodCommonCategory;
use App\Models\SupplementSupplier;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\FCategorySubCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\V1\UserResource;
use App\Models\DietSubscriptionPackage;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use App\Http\Resources\V1\RiderResource;
use App\Http\Resources\V1\ReviewResource;
use Illuminate\Support\Facades\Validator;
use App\Notifications\UpdatedRestaurantStatus;
use App\Http\Resources\V1\FoodCommonCategoryCollection;
use App\Models\Scopes\UserCountryScope;
use App\Models\UserCountry;

/**
 * @group Admin
 *
 * Back-office administration endpoints (dashboard, users, restaurants, orders, payments,
 * settings, diet plans, roles and permissions).
 */
class AdminController extends Controller
{
    use HttpResponses;

    /**
     * Admin login
     *
     * Authenticate an admin (or other back-office user) with email and password and receive a Sanctum API token,
     * the user's role and the full list of permissions the user holds. Note: validation failures are caught and
     * returned with status 403 rather than 422.
     *
     * @group Authentication
     *
     * @bodyParam email string required The user's email address. Example: admin@moboeats.co.uk
     * @bodyParam password string required The user's password. Example: secret123
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *       "name": "Admin",
     *       "email": "admin@moboeats.co.uk",
     *       "phone_number": "+447700900000",
     *       "role": null,
     *       "avatar": "https://api.moboeats.co.uk/assets/user/default.png",
     *       "relationships": {
     *         "orderer": null
     *       },
     *       "type": null,
     *       "referral_code": null,
     *       "is_guided": 0
     *     },
     *     "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ0123456789",
     *     "role": "admin",
     *     "permissions": [
     *       "view dashboard",
     *       "manage users"
     *     ]
     *   }
     * }
     * @response 422 scenario="Invalid credentials" {
     *   "status": "Error has occured...",
     *   "message": "Invalid Credentials",
     *   "data": {
     *     "email": "Invalid Credentials"
     *   }
     * }
     * @response 403 scenario="Validation error or unexpected failure" {
     *   "status": "Error has occured...",
     *   "message": "The email field is required.",
     *   "data": ""
     * }
     *
     * @responseField data.user object The authenticated user (UserResource).
     * @responseField data.token string Sanctum bearer token to use in the Authorization header.
     * @responseField data.role string Name of the user's first role.
     * @responseField data.permissions string[] Names of all permissions the user holds.
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            if(!Auth::attempt($request->only(['email', 'password']))){
                return $this->error(['email' => 'Invalid Credentials'], 'Invalid Credentials', 422);
            }

            $user = User::withoutGlobalScope(UserCountryScope::class)->where('email', $request->email)->first();

            $token = $user->createToken($request->email);

            $user = new UserResource($user);

            return $this->success([
                'user' => $user,
                'token' => $token->plainTextToken,
                'role' => $user->roles->first()->name,
                'permissions' => auth()->user()->allPermissions()->pluck('name'),

            ]);
        } catch (\Throwable $th) {
            info($th->getMessage());
            return $this->error('', $th->getMessage(), 403);
        }
    }

    /**
     * Create a back-office user
     *
     * Create a user (or reuse an existing one with the same email) and attach the given role. A random 8-character
     * password is generated and emailed to the user after the response is sent. Optionally restricts the user to a set
     * of countries.
     *
     * @group Admin - Users
     *
     * @bodyParam name string required Full name of the user. Example: Jane Doe
     * @bodyParam email string required Email address (an existing user with this email is reused). Example: jane@example.com
     * @bodyParam phone_number string required Phone number. Example: +447700900123
     * @bodyParam role integer required ID of the role to assign. Example: 5
     * @bodyParam user_countries integer[] IDs of countries the user is allowed to manage. Example: [1, 2]
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "user": {
     *       "id": 12,
     *       "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *       "name": "Jane Doe",
     *       "email": "jane@example.com",
     *       "phone_number": "+447700900123",
     *       "type": null,
     *       "status": 2,
     *       "country": "United Kingdom",
     *       "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *       "created_at": "2024-06-01T10:00:00.000000Z",
     *       "updated_at": "2024-06-01T10:00:00.000000Z"
     *     },
     *     "0": "User added successfully"
     *   }
     * }
     * @response 404 scenario="Role not found" {
     *   "status": "Error has occured...",
     *   "message": "Add User",
     *   "data": "Invalid role"
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The name field is required.",
     *   "errors": {
     *     "name": [
     *       "The name field is required."
     *     ]
     *   }
     * }
     * @response 500 scenario="Failure" {
     *   "status": "Error has occured...",
     *   "message": "Something went wrong",
     *   "data": ""
     * }
     *
     * @responseField data.user object The created (or existing) user record.
     */
    public function addUser(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'email' => ['required'],
            'phone_number' => ['required'],
            'role' => ['required'],
        ]);

        $role = Role::find($request->role);

        if (!$role) {
            return $this->error('Invalid role', 'Add User', 404);
        }

        try {
            $password = Str::random(8);

            DB::beginTransaction();

            $user = User::firstOrCreate([
                'email' => $request->email,
            ],[
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'password' => bcrypt($password)
            ]);

            if ($user && $role) {
                $user->addRole($role->name);
            }

            if ($request->has('user_countries') && !empty($request->user_countries)) {
                foreach ($request->user_countries as $user_country) {
                    UserCountry::updateOrCreate([
                        'user_id' => $user->id,
                        'country_id' => $user_country
                    ]);
                };
            }

            DB::commit();

            if ($user->email) {
                SendCommunication::dispatchAfterResponse('mail', $user->email, 'NewAccount', ['user' => $user, 'password' => $password]);
            }

            return $this->success(['user' => $user, 'User added successfully']);
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
            return $this->error('', 'Something went wrong', 500);
        }
    }

    /**
     * Get dashboard statistics
     *
     * Return headline counts (customers, restaurants, riders, orders), order and payment time series, top restaurants,
     * top menu items, platform settings, supplement counts and per-region earnings. Side effect: default settings
     * (Delivery Rate, Base Service Charge Rate, Base Groceries Service Charge Rate, Registration Fee) are created if missing.
     *
     * @group Admin
     * @subgroup Dashboard
     *
     * @queryParam orders_timeline string Time range for the orders series. One of `past_ten_years`, `past_five_years`, `past_three_years`, `past_six_months`, `past_three_months`; any other value (or none) gives the past 12 months. Example: past_six_months
     * @queryParam payments_timeline string Time range for the payments series. Same values as `orders_timeline`. Example: past_three_months
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "users": 120,
     *     "restaurants": 15,
     *     "riders": 22,
     *     "orders": 340,
     *     "orders_series": {
     *       "labels": [
     *         "Jan",
     *         "Feb"
     *       ],
     *       "name": "Orders",
     *       "data": [
     *         20,
     *         34
     *       ],
     *       "orders_direction": "more",
     *       "orders_difference": 120,
     *       "total_orders": 54
     *     },
     *     "payments_series": {
     *       "labels": [
     *         "Jan",
     *         "Feb"
     *       ],
     *       "name": "Payments",
     *       "data": [
     *         500.5,
     *         820
     *       ],
     *       "payments_direction": "more",
     *       "payments_difference": 110.5,
     *       "total_payments": 1320.5
     *     },
     *     "top_restaurants": [
     *       {
     *         "id": 3,
     *         "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *         "user_id": 12,
     *         "name": "Mama's Kitchen",
     *         "email": "info@mamaskitchen.com",
     *         "phone_no": "+447700900456",
     *         "address": "1 High St, London",
     *         "status": 2,
     *         "service_charge_agreement": 10,
     *         "groceries_service_charge_agreement": 5,
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "orders_count": 40,
     *         "user": {
     *           "id": 12,
     *           "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *           "name": "Jane Doe",
     *           "email": "jane@example.com",
     *           "phone_number": "+447700900123",
     *           "type": null,
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         },
     *         "orders": [
     *           {
     *             "id": 101,
     *             "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *             "user_id": 12,
     *             "restaurant_id": 3,
     *             "rider_id": null,
     *             "total_amount": 25.5,
     *             "delivery_fee": 3.5,
     *             "service_charge": 1.2,
     *             "delivery": 1,
     *             "delivery_status": "Delivered",
     *             "status": 2,
     *             "country": "United Kingdom",
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         ]
     *       }
     *     ],
     *     "top_menu_series": {
     *       "top_menu_items_names": [
     *         "Jollof Rice"
     *       ],
     *       "top_menu_items_series": [
     *         100
     *       ]
     *     },
     *     "settings": [
     *       {
     *         "id": 1,
     *         "name": "Delivery Rate",
     *         "description": null,
     *         "variable": "10",
     *         "type": "percentage",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z"
     *       }
     *     ],
     *     "supplements": {
     *       "suppliers_count": 4,
     *       "supplements_count": 30,
     *       "supplement_orders_count": 12
     *     },
     *     "region_data": {
     *       "United Kingdom": {
     *         "total_amount": 1250.5,
     *         "total_service_charges": 62.5,
     *         "rider_earnings": 140,
     *         "restaurant_earnings": 1048,
     *         "paid_amount": 500,
     *         "restaurant_amount_paid_out": 380,
     *         "rider_amount_paid_out": 120,
     *         "unpaid_amount": 750.5
     *       }
     *     },
     *     "orders_timeline": "past_six_months",
     *     "payments_timeline": null
     *   }
     * }
     *
     * @responseField data.users integer Number of customers (role `orderer`).
     * @responseField data.restaurants integer Number of restaurants.
     * @responseField data.riders integer Number of riders.
     * @responseField data.orders integer Number of active/completed orders.
     * @responseField data.orders_series object Chart series for orders (labels, data, direction, difference, total).
     * @responseField data.payments_series object Chart series for payment amounts.
     * @responseField data.top_restaurants object[] Top 5 restaurants by order count.
     * @responseField data.top_menu_series object Top 5 menu item names and their percentage share.
     * @responseField data.settings object[] All platform settings.
     * @responseField data.region_data object Earnings breakdown keyed by country name.
     */
    public function dashboard(Request $request)
    {
        $orders_timeline = $request->query('orders_timeline');
        $payments_timeline = $request->query('payments_timeline');
        $users = User::whereHasRole('orderer')->count();
        $restaurants = Restaurant::count();
        $riders = User::whereHasRole('rider')->count();
        $orders = Order::whereIn('delivery_status', ['On Delivery', 'Delivered', 'In Progress'])->count();

        $months = [];
        // Get past 12 months
        for ($i = 12; $i >= 0; $i--) {
            $month = Carbon::today()->startOfMonth()->subMonth($i);
            $year = Carbon::today()->startOfMonth()->subMonth($i)->format('Y');
            array_push($months, $month);
        }

        // Format months
        $months_formatted = [];
        foreach ($months as $key => $month) {
            array_push($months_formatted, Carbon::parse($month)->format('M'));
        }
        // Orders
        $total_monthly_orders = [];
        $total_orders = 0;

        $index = 0;
        foreach ($months as $month) {
            $order = Order::whereIn('delivery_status', ['On Delivery', 'Delivered', 'In Progress'])->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->count();
            array_push($total_monthly_orders, $order);
            $total_orders += $order;
            $index++;
        }

        if ($orders_timeline && $orders_timeline != '') {
            switch ($orders_timeline) {
                case 'past_ten_years':
                    $months = [];
                    for ($i = 9; $i >= 0; $i--) {
                    $month = Carbon::today()->startOfYear()->subYear($i);
                    array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                    array_push($months_formatted, Carbon::parse($month)->format('Y'));
                    }

                    $index = 0;
                    $total_monthly_orders = [];
                    $total_orders = 0;
                    foreach ($months as $month) {
                        $order = Order::whereIn('delivery_status', ['On Delivery', 'Delivered', 'In Progress'])->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])->count();
                        array_push($total_monthly_orders, $order);
                        $total_orders += $order;
                        $index++;
                    }
                    break;
                case 'past_five_years':
                    $months = [];
                    for ($i = 4; $i >= 0; $i--) {
                      $month = Carbon::today()->startOfYear()->subYear($i);
                      array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                      array_push($months_formatted, Carbon::parse($month)->format('Y'));
                    }

                    $index = 0;
                    $total_monthly_orders = [];
                    $total_orders = 0;
                    foreach ($months as $month) {
                        $order = Order::whereIn('delivery_status', ['On Delivery', 'Delivered', 'In Progress'])->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])->count();
                        array_push($total_monthly_orders, $order);
                        $total_orders += $order;
                        $index++;
                    }
                    break;
                case 'past_three_years':
                    $months = [];
                    for ($i = 2; $i >= 0; $i--) {
                        $month = Carbon::today()->startOfYear()->subYear($i);
                        array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                        array_push($months_formatted, Carbon::parse($month)->format('Y'));
                    }

                    $index = 0;
                    $total_monthly_orders = [];
                    $total_orders = 0;
                    foreach ($months as $month) {
                        $order = Order::whereIn('delivery_status', ['On Delivery', 'Delivered', 'In Progress'])->whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])->count();
                        array_push($total_monthly_orders, $order);
                        $total_orders += $order;
                        $index++;
                    }
                    break;
                case 'past_six_months':
                    $months = [];
                    for ($i = 5; $i >= 0; $i--) {
                        $month = Carbon::today()->startOfMonth()->subMonth($i);
                        array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                        array_push($months_formatted, Carbon::parse($month)->format('M'));
                    }

                    $index = 0;
                    $total_monthly_orders = [];
                    $total_orders = 0;
                    foreach ($months as $month) {
                        $order = Order::whereIn('delivery_status', ['On Delivery', 'Delivered', 'In Progress'])->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->count();
                        array_push($total_monthly_orders, $order);
                        $total_orders += $order;
                        $index++;
                    }
                    break;
                case 'past_three_months':
                    $months = [];
                    for ($i = 2; $i >= 0; $i--) {
                        $month = Carbon::today()->startOfMonth()->subMonth($i);
                        array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                        array_push($months_formatted, Carbon::parse($month)->format('M'));
                    }

                    $index = 0;
                    $total_monthly_orders = [];
                    $total_orders = 0;
                    foreach ($months as $month) {
                        $order = Order::whereIn('delivery_status', ['On Delivery', 'Delivered', 'In Progress'])->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->count();
                        array_push($total_monthly_orders, $order);
                        $total_orders += $order;
                        $index++;
                    }
                    break;

                default:
                    $months = [];
                    // Get past 12 months
                    for ($i = 12; $i >= 0; $i--) {
                        $month = Carbon::today()->startOfMonth()->subMonth($i);
                        $year = Carbon::today()->startOfMonth()->subMonth($i)->format('Y');
                        array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                        array_push($months_formatted, Carbon::parse($month)->format('M'));
                    }
                    $total_monthly_orders = [];
                    $total_orders = 0;

                    $index = 0;
                    foreach ($months as $month) {
                        $order = Order::whereIn('delivery_status', ['On Delivery', 'Delivered', 'In Progress'])->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->count();
                        array_push($total_monthly_orders, $order);
                        $total_orders += $order;
                        $index++;
                    }
                    break;

            }
        }

        // Get current months earning
        $current_month_orders = Order::where('delivery_status', 'Delivered')->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();

        // Previous months orders
        $prev_month = now()->subMonths(1);
        $prev_month_orders = Order::where('delivery_status', 'Delivered')->whereBetween('created_at', [Carbon::parse($prev_month)->startOfMonth(), Carbon::parse($prev_month)->endOfMonth()])->count();

        // Compare current months orders to previous month
        if ($prev_month_orders != 0) {
            $orders_difference = ceil(($current_month_orders / $prev_month_orders) * 100);
        } else {
            $orders_difference = ceil($current_month_orders / 100);
        }

        $orders_direction = '';

        if ($orders_difference < 0) {
            $orders_direction = 'less';
        } else if ($orders_difference > 0) {
            $orders_direction = 'more';
        }

        $orders_made_monthly = array(
            'labels' => $months_formatted,
            'name' => 'Orders',
            'data' => $total_monthly_orders,
            'orders_direction' => $orders_direction,
            'orders_difference' => $orders_difference,
            'total_orders' => $total_orders,
        );
        // End Orders

        // Payments
        $total_monthly_payments = [];
        $total_payments = 0;

        $index = 0;
        foreach ($months as $month) {
            $payment = Payment::whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->sum('amount');
            array_push($total_monthly_payments, $payment);
            $total_payments += $payment;
            $index++;
        }

        if ($payments_timeline && $payments_timeline != '') {
            switch ($payments_timeline) {
                case 'past_ten_years':
                    $months = [];
                    for ($i = 9; $i >= 0; $i--) {
                    $month = Carbon::today()->startOfYear()->subYear($i);
                    array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                    array_push($months_formatted, Carbon::parse($month)->format('Y'));
                    }

                    $total_monthly_payments = [];
                    $total_payments = 0;
                    $index = 0;
                    foreach ($months as $month) {
                        $payment = Payment::whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])->sum('amount');
                        array_push($total_monthly_payments, $payment);
                        $total_payments += $payment;
                        $index++;
                    }
                    break;
                case 'past_five_years':
                    $months = [];
                    for ($i = 4; $i >= 0; $i--) {
                      $month = Carbon::today()->startOfYear()->subYear($i);
                      array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                      array_push($months_formatted, Carbon::parse($month)->format('Y'));
                    }

                    $total_monthly_payments = [];
                    $total_payments = 0;
                    $index = 0;
                    foreach ($months as $month) {
                        $payment = Payment::whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])->sum('amount');
                        array_push($total_monthly_payments, $payment);
                        $total_payments += $payment;
                        $index++;
                    }
                    break;
                case 'past_three_years':
                    $months = [];
                    for ($i = 2; $i >= 0; $i--) {
                        $month = Carbon::today()->startOfYear()->subYear($i);
                        array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                        array_push($months_formatted, Carbon::parse($month)->format('Y'));
                    }

                    $total_monthly_payments = [];
                    $total_payments = 0;
                    $index = 0;
                    foreach ($months as $month) {
                        $payment = Payment::whereBetween('created_at', [Carbon::parse($month)->startOfYear(), Carbon::parse($month)->endOfYear()])->sum('amount');
                        array_push($total_monthly_payments, $payment);
                        $total_payments += $payment;
                        $index++;
                    }
                    break;
                case 'past_six_months':
                    $months = [];
                    for ($i = 5; $i >= 0; $i--) {
                        $month = Carbon::today()->startOfMonth()->subMonth($i);
                        array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                        array_push($months_formatted, Carbon::parse($month)->format('M'));
                    }

                    $total_monthly_payments = [];
                    $total_payments = 0;
                    $index = 0;
                    foreach ($months as $month) {
                        $payment = Payment::whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->sum('amount');
                        array_push($total_monthly_payments, $payment);
                        $total_payments += $payment;
                        $index++;
                    }
                    break;
                case 'past_three_months':
                    $months = [];
                    for ($i = 2; $i >= 0; $i--) {
                        $month = Carbon::today()->startOfMonth()->subMonth($i);
                        array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                        array_push($months_formatted, Carbon::parse($month)->format('M'));
                    }

                    $total_monthly_payments = [];
                    $total_payments = 0;
                    $index = 0;
                    foreach ($months as $month) {
                        $payment = Payment::whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->sum('amount');
                        array_push($total_monthly_payments, $payment);
                        $total_payments += $payment;
                        $index++;
                    }
                    break;

                default:
                    $months = [];
                    // Get past 12 months
                    for ($i = 12; $i >= 0; $i--) {
                        $month = Carbon::today()->startOfMonth()->subMonth($i);
                        $year = Carbon::today()->startOfMonth()->subMonth($i)->format('Y');
                        array_push($months, $month);
                    }

                    // Format months
                    $months_formatted = [];
                    foreach ($months as $key => $month) {
                        array_push($months_formatted, Carbon::parse($month)->format('M'));
                    }
                    $total_monthly_payments = [];
                    $total_payments = 0;
                    $index = 0;
                    foreach ($months as $month) {
                        $payment = Payment::whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->sum('amount');
                        array_push($total_monthly_payments, $payment);
                        $total_payments += $payment;
                        $index++;
                    }
                    break;

            }
        }

        // Get current months payments
        $current_month_payments = Payment::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();

        // Previous months payments
        $prev_month = now()->subMonths(1);
        $prev_month_payments = Payment::whereBetween('created_at', [Carbon::parse($prev_month)->startOfMonth(), Carbon::parse($prev_month)->endOfMonth()])->count();

        // Compare current months orders to previous month
        if ($prev_month_payments != 0) {
            $payments_difference = ($current_month_payments / $prev_month_payments) * 100;
        } else {
            $payments_difference = $current_month_payments / 100;
        }

        $payments_direction = '';

        if ($payments_difference < 0) {
            $payments_direction = 'less';
        } else if ($payments_difference > 0) {
            $payments_direction = 'more';
        }

        $payments_made_monthly = array(
            'labels' => $months_formatted,
            'name' => 'Payments',
            'data' => $total_monthly_payments,
            'payments_direction' => $payments_direction,
            'payments_difference' => $payments_difference,
            'total_payments' => $total_payments,
        );

        // Top Restaurants
        $top_restaurants = Restaurant::withCount('orders')->with('user', 'orders')->whereHas('orders')->orderBy('orders_count', 'DESC')->get()->take(5);

        // Top Menu Items
        $top_menu_items = Menu::withCount('orderItems')->with('restaurant')->whereHas('orderItems')->orderBy('order_items_count', 'DESC')->get()->take(5);

        $top_menu_items_series = [];
        $top_menu_items_names = [];
        $total_orders_count = 0;
        foreach($top_menu_items as $item) {
            $total_orders_count += $item->order_items_count;
        }

        foreach ($top_menu_items as $key => $item) {
            array_push($top_menu_items_names, $item->title);
            array_push($top_menu_items_series, ceil(($item->order_items_count / $total_orders_count) * 100));
        }

        $top_menu_series = [
            'top_menu_items_names' => $top_menu_items_names,
            'top_menu_items_series' => $top_menu_items_series
        ];

        $settings = [
            [
                'name' => 'Delivery Rate',
                'variable' => 0,
                'type' => 'percentage'
            ],
            [
                'name' => 'Base Service Charge Rate',
                'variable' => 0,
                'type' => 'percentage'
            ],
            [
                'name' => 'Base Groceries Service Charge Rate',
                'variable' => 0,
                'type' => 'percentage'
            ],
            [
                'name' => 'Registration Fee',
                'variable' => 0,
                'type' => 'amount'
            ]
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate([
                'name' => $setting['name']
            ], [
                'variable' => $setting['variable'],
                'type' => $setting['type']
            ]);
        }

        $settings = Setting::all();

        // Supplements and Suppliers
        $suppliers_count = SupplementSupplier::count();
        $supplements_count = Supplement::count();
        $supplement_orders_count = SupplementOrder::count();

        $supplements = [
            'suppliers_count' => $suppliers_count,
            'supplements_count' => $supplements_count,
            'supplement_orders_count' => $supplement_orders_count
        ];

        $region_data = array();

        $regions = Order::where('delivery_status', 'Delivered')->get()->pluck('country');

        $region_data_orders = Order::where('delivery_status', 'Delivered')->get();

        $paid_amount = Payout::with('payable')->get();

        $payment_data = Payment::with('orderable.user')->where('transaction_id', '!=', NULL)->get();

        foreach ($regions as $region) {
            $region_data[$region]['total_amount'] = $payment_data->where('orderable.user.country', $region)->sum('amount');
            $region_data[$region]['total_service_charges'] = $region_data_orders->where('country', $region)->sum('service_charge');
            $region_data[$region]['rider_earnings'] = $region_data_orders->where('delivery', true)->where('country', $region)->sum('delivery_fee');
            $region_data[$region]['restaurant_earnings'] = $region_data[$region]['total_amount'] - $region_data[$region]['total_service_charges'] - $region_data[$region]['rider_earnings'];

            $region_data[$region]['paid_amount'] = $paid_amount->where('payable.country', $region)->sum('amount');
            $region_data[$region]['restaurant_amount_paid_out'] = $paid_amount->where('payable_type', Restaurant::class)->where('payable.country', $region)->sum('amount');
            $region_data[$region]['rider_amount_paid_out'] = $paid_amount->where('payable_type', User::class)->where('payable.country', $region)->sum('amount');

            $region_data[$region]['unpaid_amount'] = (double) $region_data[$region]['total_amount'] - ((double) $region_data[$region]['paid_amount']);
        }

        return $this->success([
            'users' => $users,
            'restaurants' => $restaurants,
            'riders' => $riders,
            'orders' => $orders,
            'orders_series' => $orders_made_monthly,
            'payments_series' => $payments_made_monthly,
            'top_restaurants' => $top_restaurants,
            'top_menu_series' => $top_menu_series,
            'settings' => $settings,
            'supplements' => $supplements,
            'region_data' => $region_data,
            'orders_timeline' => $orders_timeline,
            'payments_timeline' => $payments_timeline,
        ]);
    }

    /**
     * List food categories
     *
     * Paginated list (7 per page) of common food categories with their sub categories, excluding the `groceries` category.
     *
     * @group Admin
     * @subgroup Categories
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 2,
     *         "uuid": "5e6f7081-92a3-b4c5-d6e7-f8a9b0c1d2e3",
     *         "title": "Main Dishes",
     *         "description": null,
     *         "image": "https://api.moboeats.co.uk/storage/category/main.png",
     *         "status": 2,
     *         "restaurant_id": null,
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "food_sub_categories": [
     *           {
     *             "id": 9,
     *             "uuid": "4d5e6f70-8192-a3b4-c5d6-e7f8a9b0c1d2",
     *             "title": "Fresh Produce",
     *             "description": "Fruits and vegetables",
     *             "status": 2,
     *             "image": "abc123.png",
     *             "created_by": "admin@moboeats.co.uk",
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         ]
     *       }
     *     ],
     *     "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/categories?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/categories?page=1",
     *     "links": [
     *       {
     *         "url": null,
     *         "label": "&laquo; Previous",
     *         "active": false
     *       },
     *       {
     *         "url": "https://api.moboeats.co.uk/api/v1/admin/categories?page=1",
     *         "label": "1",
     *         "active": true
     *       },
     *       {
     *         "url": null,
     *         "label": "Next &raquo;",
     *         "active": false
     *       }
     *     ],
     *     "next_page_url": null,
     *     "path": "https://api.moboeats.co.uk/api/v1/admin/categories",
     *     "per_page": 7,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     *
     * @responseField data.data object[] Food categories with `food_sub_categories`.
     */
    public function categories()
    {
        $categories = FoodCommonCategory::with('food_sub_categories')->where('title', '!=', 'groceries')->orderBy('created_at', 'DESC')->paginate(7);

        return $this->success($categories);
    }

    /**
     * List sub categories of a category
     *
     * Paginated list (7 per page) of sub categories that belong to the category whose title matches `search`.
     * If no category matches, the request fails with a server error.
     *
     * @group Admin
     * @subgroup Categories
     *
     * @queryParam search string required Exact title of the parent category. Example: groceries
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 9,
     *         "uuid": "4d5e6f70-8192-a3b4-c5d6-e7f8a9b0c1d2",
     *         "title": "Fresh Produce",
     *         "description": "Fruits and vegetables",
     *         "status": 2,
     *         "image": "abc123.png",
     *         "created_by": "admin@moboeats.co.uk",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z"
     *       }
     *     ],
     *     "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/sub-categories?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/sub-categories?page=1",
     *     "links": [
     *       {
     *         "url": null,
     *         "label": "&laquo; Previous",
     *         "active": false
     *       },
     *       {
     *         "url": "https://api.moboeats.co.uk/api/v1/admin/sub-categories?page=1",
     *         "label": "1",
     *         "active": true
     *       },
     *       {
     *         "url": null,
     *         "label": "Next &raquo;",
     *         "active": false
     *       }
     *     ],
     *     "next_page_url": null,
     *     "path": "https://api.moboeats.co.uk/api/v1/admin/sub-categories",
     *     "per_page": 7,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     * @response 500 scenario="Category not found" {
     *   "message": "Server Error"
     * }
     *
     * @responseField data.data object[] Sub categories.
     */
    public function subCategories(Request $request)
    {
        $search = $request->query('search');

        $category = FoodCommonCategory::where('title', $search)->first();

        $sub_category_ids = FCategorySubCategory::where('category_id', $category->id)->get()->pluck('sub_category_id');

        $sub_categories = FooSubCategory::whereIn('id', $sub_category_ids)
                                        ->orderBy('created_at', 'DESC')
                                        ->paginate(7);

        return $this->success($sub_categories);
    }

    /**
     * Create a grocery sub category
     *
     * Create a sub category under the `groceries` common category and link it to that category. The image is stored on
     * the `category` disk.
     *
     * @group Admin
     * @subgroup Categories
     *
     * @bodyParam title string required Sub category title. Example: Fresh Produce
     * @bodyParam status integer required Status (e.g. 1 = inactive, 2 = active). Example: 2
     * @bodyParam description string Optional description. Example: Fruits and vegetables
     * @bodyParam image file required Sub category image (the request fails with a server error if missing).
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": "Subcategory successfully created",
     *   "data": {
     *     "id": 9,
     *     "uuid": "4d5e6f70-8192-a3b4-c5d6-e7f8a9b0c1d2",
     *     "title": "Fresh Produce",
     *     "description": "Fruits and vegetables",
     *     "status": 2,
     *     "image": "abc123.png",
     *     "created_by": "admin@moboeats.co.uk",
     *     "created_at": "2024-06-01T10:00:00.000000Z",
     *     "updated_at": "2024-06-01T10:00:00.000000Z"
     *   }
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The title field is required.",
     *   "errors": {
     *     "title": [
     *       "The title field is required."
     *     ]
     *   }
     * }
     * @response 500 scenario="Missing image or groceries category" {
     *   "message": "Server Error"
     * }
     *
     * @responseField data object The created sub category.
     */
    public function addSubCategory(Request $request)
    {
        $request->validate([
            // 'category_id' => ['required'],
            'title' => ['required'],
            'status' => ['required', 'integer'],
        ]);

        $groceries = FoodCommonCategory::where('title', 'groceries')->first();

        $subcategory = FooSubCategory::create([
            'category_id' => $groceries->id,
            'title' => $request->title,
            'description' => $request->has('description') && $request->description != '' ? $request->description : NULL,
            'status' => $request->status,
            'image' => pathinfo($request->image->store('subcategory', 'category'), PATHINFO_BASENAME),
            'created_by' => auth()->user()->email,
        ]);

        FCategorySubCategory::create([
            'sub_category_id' => $subcategory->id,
            'category_id' => $groceries->id,
            'created_by' => auth()->user()->email,
        ]);

        return $this->success($subcategory, 'Subcategory successfully created');
    }

    /**
     * Update a sub category
     *
     * Update a sub category's title, description, status and image. A new image must be uploaded on every update.
     *
     * @group Admin
     * @subgroup Categories
     *
     * @urlParam id integer required Numeric ID of the sub category. Example: 9
     *
     * @bodyParam title string required Sub category title. Example: Fresh Produce
     * @bodyParam description string New description; kept unchanged when empty. Example: Fruits and vegetables
     * @bodyParam status integer Status (e.g. 1 = inactive, 2 = active). Example: 2
     * @bodyParam image file required New image (the request fails with a server error if missing).
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": "Subcategory successfully created",
     *   "data": {
     *     "id": 9,
     *     "uuid": "4d5e6f70-8192-a3b4-c5d6-e7f8a9b0c1d2",
     *     "title": "Fresh Produce",
     *     "description": "Fruits and vegetables",
     *     "status": 2,
     *     "image": "abc123.png",
     *     "created_by": "admin@moboeats.co.uk",
     *     "created_at": "2024-06-01T10:00:00.000000Z",
     *     "updated_at": "2024-06-01T10:00:00.000000Z"
     *   }
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The title field is required.",
     *   "errors": {
     *     "title": [
     *       "The title field is required."
     *     ]
     *   }
     * }
     * @response 500 scenario="Sub category not found or image missing" {
     *   "message": "Server Error"
     * }
     *
     * @responseField data object The updated sub category.
     */
    public function updateSubcategory(Request $request, $id)
    {
        $request->validate([
            'title' => ['required'],
        ]);

        $subcategory = FooSubCategory::find($id);

        $subcategory->update([
            'title' => $request->title,
            'description' => $request->has('description') && $request->description != '' ? $request->description : $subcategory->description,
            'status' => $request->status,
            'image' => pathinfo($request->image->store('subcategory', 'category'), PATHINFO_BASENAME),
            'updated_by' => auth()->user()->email,
        ]);

        return $this->success($subcategory, 'Subcategory successfully created');
    }

    /**
     * List users by role
     *
     * Paginated list (10 per page) of users with the given role, newest first, with their orders and restaurants
     * (including restaurant orders, menus and users).
     *
     * @group Admin - Users
     *
     * @urlParam role string required Role name, e.g. `orderer`, `restaurant`, `rider`, `restaurant employee`, `admin`. Example: orderer
     *
     * @queryParam search string Filter by name, email or phone number (partial match). Example: jane
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 12,
     *         "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *         "name": "Jane Doe",
     *         "email": "jane@example.com",
     *         "phone_number": "+447700900123",
     *         "type": null,
     *         "status": 2,
     *         "country": "United Kingdom",
     *         "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "orders": [
     *           {
     *             "id": 101,
     *             "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *             "user_id": 12,
     *             "restaurant_id": 3,
     *             "rider_id": null,
     *             "total_amount": 25.5,
     *             "delivery_fee": 3.5,
     *             "service_charge": 1.2,
     *             "delivery": 1,
     *             "delivery_status": "Delivered",
     *             "status": 2,
     *             "country": "United Kingdom",
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         ],
     *         "restaurants": []
     *       }
     *     ],
     *     "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/users/orderer?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/users/orderer?page=1",
     *     "links": [
     *       {
     *         "url": null,
     *         "label": "&laquo; Previous",
     *         "active": false
     *       },
     *       {
     *         "url": "https://api.moboeats.co.uk/api/v1/admin/users/orderer?page=1",
     *         "label": "1",
     *         "active": true
     *       },
     *       {
     *         "url": null,
     *         "label": "Next &raquo;",
     *         "active": false
     *       }
     *     ],
     *     "next_page_url": null,
     *     "path": "https://api.moboeats.co.uk/api/v1/admin/users/orderer",
     *     "per_page": 10,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     *
     * @responseField data.data object[] Users with `orders` and `restaurants`.
     */
    public function users(Request $request, $role)
    {
        $search = $request->query('search');

        $users = User::with('orders', 'restaurants.orders', 'restaurants.menus', 'restaurants.users')
                        ->whereHas('roles', function ($query) use ($role) { $query->where('name', $role); })
                        ->when($search && $search != '', function($query) use ($search) {
                            $query->where(function ($query) use ($search) {
                                $query->where('name', 'LIKE', '%'.$search.'%')
                                        ->orWhere('email', 'LIKE', '%'.$search.'%')
                                        ->orWhere('phone_number', 'LIKE', '%'.$search.'%');
                            });
                        })
                        ->orderBy('created_at', 'DESC')
                        ->paginate(10);

        return $this->success($users);
    }

    /**
     * Get customer details
     *
     * Return a user (with order/restaurant counts and roles) together with their paginated orders and restaurants
     * (5 per page each).
     *
     * @group Admin - Users
     *
     * @urlParam id integer required Numeric ID of the user. Example: 12
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "user": {
     *       "id": 12,
     *       "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *       "name": "Jane Doe",
     *       "email": "jane@example.com",
     *       "phone_number": "+447700900123",
     *       "type": null,
     *       "status": 2,
     *       "country": "United Kingdom",
     *       "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *       "created_at": "2024-06-01T10:00:00.000000Z",
     *       "updated_at": "2024-06-01T10:00:00.000000Z",
     *       "orders_count": 3,
     *       "restaurants_count": 0,
     *       "roles": [
     *         {
     *           "id": 5,
     *           "name": "orderer",
     *           "display_name": null,
     *           "description": "Finance team",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       ]
     *     },
     *     "orders": {
     *       "current_page": 1,
     *       "data": [
     *         {
     *           "id": 101,
     *           "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *           "user_id": 12,
     *           "restaurant_id": 3,
     *           "rider_id": null,
     *           "total_amount": 25.5,
     *           "delivery_fee": 3.5,
     *           "service_charge": 1.2,
     *           "delivery": 1,
     *           "delivery_status": "Delivered",
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "restaurant": {
     *             "id": 3,
     *             "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *             "user_id": 12,
     *             "name": "Mama's Kitchen",
     *             "email": "info@mamaskitchen.com",
     *             "phone_no": "+447700900456",
     *             "address": "1 High St, London",
     *             "status": 2,
     *             "service_charge_agreement": 10,
     *             "groceries_service_charge_agreement": 5,
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         }
     *       ],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/users/customer/12/details?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/users/customer/12/details?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/users/customer/12/details?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/users/customer/12/details",
     *       "per_page": 5,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     },
     *     "restaurants": {
     *       "current_page": 1,
     *       "data": [],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/users/customer/12/details?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/users/customer/12/details?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/users/customer/12/details?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/users/customer/12/details",
     *       "per_page": 5,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     }
     *   }
     * }
     * @response 500 scenario="User not found" {
     *   "message": "Server Error"
     * }
     *
     * @responseField data.user object The user with `orders_count`, `restaurants_count` and `roles`.
     * @responseField data.orders object Paginated orders with `restaurant`.
     * @responseField data.restaurants object Paginated restaurants with `orders`.
     */
    public function user($id)
    {
        $user = User::withCount('orders', 'restaurants')->with(['roles'])->find($id);

        $restaurants = Restaurant::where('user_id', $user->id)->with('orders')->paginate(5);

        $orders = Order::where('user_id', $user->id)->with('restaurant')->paginate(5);

        return $this->success(['user' => $user, 'orders' => $orders, 'restaurants' => $restaurants]);
    }

    /**
     * Get restaurant admin details
     *
     * Return a restaurant owner (with counts and roles) and their paginated restaurants (5 per page) with orders.
     *
     * @group Admin - Users
     *
     * @urlParam id integer required Numeric ID of the user. Example: 12
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "restaurants": {
     *       "current_page": 1,
     *       "data": [
     *         {
     *           "id": 3,
     *           "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *           "user_id": 12,
     *           "name": "Mama's Kitchen",
     *           "email": "info@mamaskitchen.com",
     *           "phone_no": "+447700900456",
     *           "address": "1 High St, London",
     *           "status": 2,
     *           "service_charge_agreement": 10,
     *           "groceries_service_charge_agreement": 5,
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "orders": [
     *             {
     *               "id": 101,
     *               "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *               "user_id": 12,
     *               "restaurant_id": 3,
     *               "rider_id": null,
     *               "total_amount": 25.5,
     *               "delivery_fee": 3.5,
     *               "service_charge": 1.2,
     *               "delivery": 1,
     *               "delivery_status": "Delivered",
     *               "status": 2,
     *               "country": "United Kingdom",
     *               "created_at": "2024-06-01T10:00:00.000000Z",
     *               "updated_at": "2024-06-01T10:00:00.000000Z"
     *             }
     *           ]
     *         }
     *       ],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/users/restaurant-admin/12/details?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/users/restaurant-admin/12/details?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/users/restaurant-admin/12/details?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/users/restaurant-admin/12/details",
     *       "per_page": 5,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     },
     *     "user": {
     *       "id": 12,
     *       "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *       "name": "Jane Doe",
     *       "email": "jane@example.com",
     *       "phone_number": "+447700900123",
     *       "type": null,
     *       "status": 2,
     *       "country": "United Kingdom",
     *       "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *       "created_at": "2024-06-01T10:00:00.000000Z",
     *       "updated_at": "2024-06-01T10:00:00.000000Z",
     *       "orders_count": 0,
     *       "restaurants_count": 1,
     *       "roles": [
     *         {
     *           "id": 5,
     *           "name": "restaurant",
     *           "display_name": null,
     *           "description": "Finance team",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       ]
     *     }
     *   }
     * }
     * @response 500 scenario="User not found" {
     *   "message": "Server Error"
     * }
     *
     * @responseField data.restaurants object Paginated restaurants with `orders`.
     * @responseField data.user object The user with counts and `roles`.
     */
    public function restaurantAdmin($id)
    {
        $user = User::withCount('orders', 'restaurants')->with(['roles'])->find($id);

        $restaurants = Restaurant::where('user_id', $user->id)->with('orders')->paginate(5);

        return $this->success(['restaurants' => $restaurants, 'user' => $user]);
    }

    /**
     * Update a user
     *
     * Update a user's name, email, phone number and type, and optionally replace their avatar.
     *
     * @group Admin - Users
     *
     * @bodyParam id integer required Numeric ID of the user to update. Example: 12
     * @bodyParam first_name string required First name. Example: Jane
     * @bodyParam last_name string required Last name. Example: Doe
     * @bodyParam email string required Email address. Example: jane@example.com
     * @bodyParam phone_number string required Phone number. Example: +447700900123
     * @bodyParam type string User type. Example: individual
     * @bodyParam avatar file New avatar image (max 9000 KB).
     *
     * @response 200 scenario="Success" {
     *   "message": "User updated successfully"
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The first name field is required.",
     *   "errors": {
     *     "first_name": [
     *       "The first name field is required."
     *     ]
     *   }
     * }
     * @response 500 scenario="User not found" {
     *   "message": "Server Error"
     * }
     *
     * @responseField message string Confirmation message.
     */
    public function updateUser(Request $request)
    {
        $request->validate([
            'id' => ['required'],
            'first_name' => 'required', 'string',
            'last_name' => 'required', 'string',
            'email' => 'required', 'email',
            'phone_number' => 'required',
            'avatar' => ['nullable', 'max:9000'],
        ]);

        $user = User::find($request->id);

        $user->update([
            'name' => $request->first_name.' '.$request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'type' => $request->type
        ]);

        if ($request->hasFile('avatar')) {
            $image = explode('/', $user->image);
            Storage::disk('user')->delete('/avatar/'.end($image));
            $user->update([
                'image' => pathinfo($request->avatar->store('avatar', 'user'), PATHINFO_BASENAME),
            ]);
        }

        return response()->json(['message' => 'User updated successfully'], 200);
    }

    /**
     * Get rider details
     *
     * Return a rider user with deliveries and roles, the rider profile, paginated deliveries (5 per page) and an
     * earnings summary (delivery fees + tips, amount paid out and outstanding amount).
     *
     * @group Admin - Users
     *
     * @urlParam id integer required Numeric ID of the rider's user account. Example: 12
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "user": {
     *       "id": 12,
     *       "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *       "name": "Jane Doe",
     *       "email": "jane@example.com",
     *       "phone_number": "+447700900123",
     *       "type": null,
     *       "status": 2,
     *       "country": "United Kingdom",
     *       "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *       "created_at": "2024-06-01T10:00:00.000000Z",
     *       "updated_at": "2024-06-01T10:00:00.000000Z",
     *       "deliveries": [
     *         {
     *           "id": 101,
     *           "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *           "user_id": 12,
     *           "restaurant_id": 3,
     *           "rider_id": null,
     *           "total_amount": 25.5,
     *           "delivery_fee": 3.5,
     *           "service_charge": 1.2,
     *           "delivery": 1,
     *           "delivery_status": "Delivered",
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       ],
     *       "roles": [
     *         {
     *           "id": 5,
     *           "name": "rider",
     *           "display_name": null,
     *           "description": "Finance team",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       ]
     *     },
     *     "deliveries": {
     *       "current_page": 1,
     *       "data": [
     *         {
     *           "id": 101,
     *           "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *           "user_id": 12,
     *           "restaurant_id": 3,
     *           "rider_id": null,
     *           "total_amount": 25.5,
     *           "delivery_fee": 3.5,
     *           "service_charge": 1.2,
     *           "delivery": 1,
     *           "delivery_status": "Delivered",
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       ],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/users/rider/12/details?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/users/rider/12/details?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/users/rider/12/details?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/users/rider/12/details",
     *       "per_page": 5,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     },
     *     "rider_profile": {
     *       "id": 4,
     *       "uuid": "6f708192-a3b4-c5d6-e7f8-a9b0c1d2e3f4",
     *       "user_id": 12,
     *       "status": 2
     *     },
     *     "earnings_data": {
     *       "total_earnings": 150.5,
     *       "order_earnings": 140,
     *       "tip_earnings": 10.5,
     *       "paid_amount": 120,
     *       "unpaid_amount": 30.5
     *     }
     *   }
     * }
     * @response 500 scenario="User not found" {
     *   "message": "Server Error"
     * }
     *
     * @responseField data.user object The rider user with `deliveries` and `roles`.
     * @responseField data.deliveries object Paginated orders delivered by the rider.
     * @responseField data.rider_profile object Rider profile (RiderResource) or null when the user has no rider profile.
     * @responseField data.earnings_data object Totals: `total_earnings`, `order_earnings`, `tip_earnings`, `paid_amount`, `unpaid_amount`.
     */
    public function rider($id)
    {
        $user = User::with('deliveries.restaurant', 'deliveries.user', 'roles')->find($id);

        $rider_profile = null;
        if ($user->rider) {
            $rider_profile = new RiderResource(Rider::where('user_id', $user->id)->first());
        }

        $deliveries = Order::where('rider_id', $id)->orderBy('created_at', 'DESC')->paginate(5);

        $earnings = Order::where('rider_id', $id)->where('delivery_status', 'delivered')->sum('delivery_fee');

        $tips = 0;
        if ($user->rider) {
            $tips = RiderTip::where('rider_id', $user->rider->id)->where('transaction_id', '!=', NULL)->sum('amount');
        }

        // Add disbursed amount
        $paid_amount = Payout::with('payable')
                            ->where('payable_type', User::class)
                            ->where('payable_id', $user->id)
                            ->sum('amount');

        $pending_payment = $earnings + $tips - $paid_amount;

        return $this->success(['user' => $user, 'deliveries' => $deliveries, 'rider_profile' => $rider_profile, 'earnings_data' => ['total_earnings' => $earnings + $tips, 'order_earnings' => $earnings, 'tip_earnings' => $tips, 'paid_amount' => $paid_amount, 'unpaid_amount' => $pending_payment]]);
    }

    /**
     * Approve or deny a rider
     *
     * Set a rider profile's status to approved (2) or denied (3) and store an optional rejection reason. A push
     * notification is dispatched to the rider if they have a device token.
     *
     * @group Admin - Users
     *
     * @urlParam rider string required UUID of the rider profile. Example: 6f708192-a3b4-c5d6-e7f8-a9b0c1d2e3f4
     *
     * @bodyParam status string required New status. One of `approved`, `denied`. Example: approved
     * @bodyParam rejection_reason string Reason for denial. Example: Driving licence has expired
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "message": "Rider profile updated successfully"
     *   }
     * }
     * @response 404 scenario="Rider not found" {
     *   "message": "No query results for model [App\\Models\\Rider]."
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The status field is required.",
     *   "errors": {
     *     "status": [
     *       "The status field is required."
     *     ]
     *   }
     * }
     */
    public function updateRiderStatus(Request $request, Rider $rider)
    {
        $request->validate([
            'status' => ['required', 'in:approved,denied']
        ]);

        $rider->update([
            'status' => $request->status == 'approved' ? '2' : '3',
            'rejection_reason' => $request->has('rejection_reason') && !empty($request->rejection_reason) ? $request->rejection_reason : NULL,
        ]);

        if ($rider->user->device_token) {
            // Send Notification to user
            SendNotification::dispatchAfterResponse($rider->user->device_token, 'Your profile has been '.$request->status, ['rejection_reason' => $request->has('rejection_reason') && !empty($request->rejection_reason) ? $request->rejection_reason : NULL]);
        }

        return $this->success(['message' => 'Rider profile updated successfully']);
    }

    /**
     * List restaurants
     *
     * Paginated list (10 per page) of restaurants with owner, orders and menus, newest first.
     *
     * @group Admin - Restaurants
     *
     * @queryParam search string Filter by restaurant name or owner name (partial match). Example: mama
     * @queryParam status integer Filter by status (1 = pending, 2 = approved, 3 = denied). Example: 2
     * @queryParam type string Filter by the owner's user type. Example: company
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 3,
     *         "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *         "user_id": 12,
     *         "name": "Mama's Kitchen",
     *         "email": "info@mamaskitchen.com",
     *         "phone_no": "+447700900456",
     *         "address": "1 High St, London",
     *         "status": 2,
     *         "service_charge_agreement": 10,
     *         "groceries_service_charge_agreement": 5,
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "user": {
     *           "id": 12,
     *           "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *           "name": "Jane Doe",
     *           "email": "jane@example.com",
     *           "phone_number": "+447700900123",
     *           "type": null,
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         },
     *         "orders": [
     *           {
     *             "id": 101,
     *             "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *             "user_id": 12,
     *             "restaurant_id": 3,
     *             "rider_id": null,
     *             "total_amount": 25.5,
     *             "delivery_fee": 3.5,
     *             "service_charge": 1.2,
     *             "delivery": 1,
     *             "delivery_status": "Delivered",
     *             "status": 2,
     *             "country": "United Kingdom",
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         ],
     *         "menus": [
     *           {
     *             "id": 7,
     *             "uuid": "3c4d5e6f-7081-92a3-b4c5-d6e7f8a9b0c1",
     *             "restaurant_id": 3,
     *             "title": "Jollof Rice",
     *             "description": "Spicy jollof rice with chicken",
     *             "status": 2,
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         ]
     *       }
     *     ],
     *     "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/restaurants?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/restaurants?page=1",
     *     "links": [
     *       {
     *         "url": null,
     *         "label": "&laquo; Previous",
     *         "active": false
     *       },
     *       {
     *         "url": "https://api.moboeats.co.uk/api/v1/admin/restaurants?page=1",
     *         "label": "1",
     *         "active": true
     *       },
     *       {
     *         "url": null,
     *         "label": "Next &raquo;",
     *         "active": false
     *       }
     *     ],
     *     "next_page_url": null,
     *     "path": "https://api.moboeats.co.uk/api/v1/admin/restaurants",
     *     "per_page": 10,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     *
     * @responseField data.data object[] Restaurants with `user`, `orders` and `menus`.
     */
    public function restaurants(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $type = $request->query('type');

        $restaurants = Restaurant::with('user', 'orders', 'menus')
                                ->when($search && $search != '', function($query) use ($search) {
                                    $query->where('name', 'LIKE', '%'.$search.'%')
                                        ->orWhereHas('user', function ($query) use ($search) {
                                            $query->where('name', 'LIKE', '%'.$search.'%');
                                        });
                                })
                                ->when($status && $status != '', function ($query) use ($status) {
                                    $query->where(function ($query) use ($status) {
                                        $query->where('status', $status);
                                    });
                                })
                                ->when($type && $type != '', function ($query) use ($type) {
                                    $query->whereHas('user', function ($query) use ($type) {
                                        $query->where('type', $type);
                                    });
                                })
                                ->orderBy('created_at', 'DESC')
                                ->paginate(10);

        return $this->success($restaurants);
    }

    /**
     * Get restaurant details
     *
     * Return a restaurant with owner, staff, orders (with payment and customer), menus, operating hours, documents and
     * reviews, its average rating and an earnings summary. Also available as `GET /api/v1/admin/restaurant/{restaurant}`.
     *
     * @group Admin - Restaurants
     *
     * @urlParam id string required UUID of the restaurant. Example: 8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "restaurant": {
     *       "id": 3,
     *       "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *       "user_id": 12,
     *       "name": "Mama's Kitchen",
     *       "email": "info@mamaskitchen.com",
     *       "phone_no": "+447700900456",
     *       "address": "1 High St, London",
     *       "status": 2,
     *       "service_charge_agreement": 10,
     *       "groceries_service_charge_agreement": 5,
     *       "created_at": "2024-06-01T10:00:00.000000Z",
     *       "updated_at": "2024-06-01T10:00:00.000000Z",
     *       "orders_count": 1,
     *       "menus_count": 1,
     *       "user": {
     *         "id": 12,
     *         "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *         "name": "Jane Doe",
     *         "email": "jane@example.com",
     *         "phone_number": "+447700900123",
     *         "type": null,
     *         "status": 2,
     *         "country": "United Kingdom",
     *         "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z"
     *       },
     *       "users": [],
     *       "orders": [
     *         {
     *           "id": 101,
     *           "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *           "user_id": 12,
     *           "restaurant_id": 3,
     *           "rider_id": null,
     *           "total_amount": 25.5,
     *           "delivery_fee": 3.5,
     *           "service_charge": 1.2,
     *           "delivery": 1,
     *           "delivery_status": "Delivered",
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "payment": {
     *             "id": 44,
     *             "uuid": "2b3c4d5e-6f70-8192-a3b4-c5d6e7f8a9b0",
     *             "transaction_id": "pi_3PZx1234567890",
     *             "orderable_type": "App\\Models\\Order",
     *             "orderable_id": 101,
     *             "payment_method": "stripe",
     *             "amount": 25.5,
     *             "status": 2,
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           },
     *           "user": {
     *             "id": 12,
     *             "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *             "name": "Jane Doe",
     *             "email": "jane@example.com",
     *             "phone_number": "+447700900123",
     *             "type": null,
     *             "status": 2,
     *             "country": "United Kingdom",
     *             "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         }
     *       ],
     *       "menus": [
     *         {
     *           "id": 7,
     *           "uuid": "3c4d5e6f-7081-92a3-b4c5-d6e7f8a9b0c1",
     *           "restaurant_id": 3,
     *           "title": "Jollof Rice",
     *           "description": "Spicy jollof rice with chicken",
     *           "status": 2,
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       ],
     *       "operating_hours": [],
     *       "documents": [],
     *       "reviews": []
     *     },
     *     "average_rating": 4.5,
     *     "restaurant_payment_data": {
     *       "total_earnings": 980.5,
     *       "paid_amount": 380,
     *       "unpaid_amount": 600.5,
     *       "service_charges": 62.5
     *     }
     *   }
     * }
     * @response 500 scenario="Restaurant not found" {
     *   "message": "Server Error"
     * }
     *
     * @responseField data.restaurant object The restaurant with its relations.
     * @responseField data.average_rating number Average review rating.
     * @responseField data.restaurant_payment_data object `total_earnings`, `paid_amount`, `unpaid_amount`, `service_charges`.
     */
    public function restaurant($id)
    {
        $restaurant = Restaurant::with('user', 'users', 'orders.payment', 'orders.user', 'menus', 'operatingHours', 'documents', 'reviews')
                            ->withCount('orders', 'menus')
                            ->orWhere('uuid', $id)
                            ->first();

        $orders = Order::where('restaurant_id', $restaurant->id)
                        ->where('delivery_status', 'Delivered')
                        ->get();

        $total_service_charges = $orders->sum('service_charge');
        $rider_earnings = $orders->where('delivery', true)->sum('delivery_fee');

        $paid_amount = Payout::with('payable')
                            ->where('payable_type', Restaurant::class)
                            ->where('payable_id', $restaurant->id)
                            ->sum('amount');

        $payment_data = Payment::with('orderable.restaurant')
                        ->whereHas('orderable', function ($query) use ($orders) {
                            $query->where('orderable_type', Order::class)
                                    ->whereIn('orderable_id', $orders->pluck('id'));
                        })
                        ->where('transaction_id', '!=', NULL)
                        ->sum('amount');

        $restaurant_payment_data = array();

        $restaurant_payment_data['total_earnings'] = $payment_data - $rider_earnings - $total_service_charges;
        $restaurant_payment_data['paid_amount'] = $paid_amount;
        $restaurant_payment_data['unpaid_amount'] = $restaurant_payment_data['total_earnings'] - $paid_amount;
        $restaurant_payment_data['service_charges'] = $total_service_charges;

        return $this->success(['restaurant' => $restaurant, 'average_rating' => $restaurant->averageRating(), 'restaurant_payment_data' => $restaurant_payment_data]);
    }

    /**
     * List restaurant reviews
     *
     * Return all reviews of a restaurant (not paginated).
     *
     * @group Admin - Restaurants
     *
     * @urlParam id string required UUID of the restaurant. Example: 8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "reviewable": {
     *         "id": 1,
     *         "user_id": 12,
     *         "reviewable_type": "App\\Models\\Restaurant",
     *         "reviewable_id": 3,
     *         "rating": 5,
     *         "review": "Great food!",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "reviewable": {
     *           "id": 3,
     *           "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *           "user_id": 12,
     *           "name": "Mama's Kitchen",
     *           "email": "info@mamaskitchen.com",
     *           "phone_no": "+447700900456",
     *           "address": "1 High St, London",
     *           "status": 2,
     *           "service_charge_agreement": 10,
     *           "groceries_service_charge_agreement": 5,
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       },
     *       "rating": 5,
     *       "review": "Great food!"
     *     }
     *   ]
     * }
     *
     * @responseField data object[] Reviews (ReviewResource) with `reviewable`, `rating`, `review`.
     */
    public function restaurantReviews(Restaurant $restaurant)
    {
        $review = Review::where('reviewable_type', Restaurant::class)->where('reviewable_id', $restaurant->id)->get();

        return ReviewResource::collection($review);
    }

    /**
     * List restaurant payments
     *
     * Paginated list (5 per page) of completed payments (with a transaction ID) for orders of the restaurant, newest first.
     *
     * @group Admin - Restaurants
     *
     * @urlParam restaurant string required UUID of the restaurant. Example: 8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d
     *
     * @queryParam search string Filter by order UUID or customer name (partial match). Example: jane
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 44,
     *         "uuid": "2b3c4d5e-6f70-8192-a3b4-c5d6e7f8a9b0",
     *         "transaction_id": "pi_3PZx1234567890",
     *         "orderable_type": "App\\Models\\Order",
     *         "orderable_id": 101,
     *         "payment_method": "stripe",
     *         "amount": 25.5,
     *         "status": 2,
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "orderable": {
     *           "id": 101,
     *           "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *           "user_id": 12,
     *           "restaurant_id": 3,
     *           "rider_id": null,
     *           "total_amount": 25.5,
     *           "delivery_fee": 3.5,
     *           "service_charge": 1.2,
     *           "delivery": 1,
     *           "delivery_status": "Delivered",
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "restaurant": {
     *             "id": 3,
     *             "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *             "user_id": 12,
     *             "name": "Mama's Kitchen",
     *             "email": "info@mamaskitchen.com",
     *             "phone_no": "+447700900456",
     *             "address": "1 High St, London",
     *             "status": 2,
     *             "service_charge_agreement": 10,
     *             "groceries_service_charge_agreement": 5,
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           },
     *           "user": {
     *             "id": 12,
     *             "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *             "name": "Jane Doe",
     *             "email": "jane@example.com",
     *             "phone_number": "+447700900123",
     *             "type": null,
     *             "status": 2,
     *             "country": "United Kingdom",
     *             "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         }
     *       }
     *     ],
     *     "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/payments?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/payments?page=1",
     *     "links": [
     *       {
     *         "url": null,
     *         "label": "&laquo; Previous",
     *         "active": false
     *       },
     *       {
     *         "url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/payments?page=1",
     *         "label": "1",
     *         "active": true
     *       },
     *       {
     *         "url": null,
     *         "label": "Next &raquo;",
     *         "active": false
     *       }
     *     ],
     *     "next_page_url": null,
     *     "path": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/payments",
     *     "per_page": 5,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     * @response 404 scenario="Restaurant not found" {
     *   "message": "No query results for model [App\\Models\\Restaurant]."
     * }
     *
     * @responseField data.data object[] Payments with `orderable` (order with `restaurant` and `user`).
     */
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

    /**
     * Approve or deny a restaurant
     *
     * Set a restaurant's status to approved (2) or denied (3). A reason is required when denying. The restaurant is
     * notified of the status change.
     *
     * @group Admin - Restaurants
     *
     * @urlParam restaurant string required UUID of the restaurant. Example: 8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d
     *
     * @bodyParam status integer required New status. One of `2` (approved), `3` (denied). Example: 2
     * @bodyParam reason string Reason for denial. Required when `status` is 3. Example: Missing food hygiene certificate
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": "Restaurants updated successfully"
     * }
     * @response 404 scenario="Restaurant not found" {
     *   "message": "No query results for model [App\\Models\\Restaurant]."
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The status field is required.",
     *   "errors": {
     *     "status": [
     *       "The status field is required."
     *     ]
     *   }
     * }
     */
    public function updateRestaurantStatus(Request $request, Restaurant $restaurant)
    {
        $request->validate([
            'status' => ['required', 'in:2,3'],
            'reason' => ['nullable', 'string', 'required_if:status,3'],
        ]);

        $restaurant->update([
            'status' => $request->status,
            'denied_reason' => $request->has('reason') && $request->reason != '' && $request->status == '3' ? $request->reason : NULL
        ]);

        $restaurant->notify(new UpdatedRestaurantStatus($restaurant->status, $request->has('reason') && $request->reason != '' ? $request->reason : ''));

        return $this->success('Restaurants updated successfully');
    }

    /**
     * Update a restaurant's service charge agreement
     *
     * Set the restaurant's service charge percentage. When `groceries` is present in the request the groceries
     * service charge agreement is updated instead.
     *
     * @group Admin - Restaurants
     *
     * @urlParam restaurant string required UUID of the restaurant. Example: 8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d
     *
     * @bodyParam service_charge_agreement number The agreed service charge rate. Example: 10
     * @bodyParam groceries boolean If present (any value), the groceries service charge agreement is updated. Example: true
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": "Restaurant updated successfully"
     * }
     * @response 404 scenario="Restaurant not found" {
     *   "message": "No query results for model [App\\Models\\Restaurant]."
     * }
     */
    public function updateServiceChargeAgreement(Restaurant $restaurant, Request $request)
    {
        if ($request->has('groceries')) {
            $restaurant->update([
                'groceries_service_charge_agreement' => $request->service_charge_agreement
            ]);
        } else {
            $restaurant->update([
                'service_charge_agreement' => $request->service_charge_agreement
            ]);
        }

        return $this->success('Restaurant updated successfully');
    }

    /**
     * List restaurant orders
     *
     * Paginated list (5 per page) of the restaurant's orders with customer, newest first.
     *
     * @group Admin - Restaurants
     *
     * @urlParam restaurant string required UUID of the restaurant. Example: 8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d
     *
     * @queryParam search string Filter by order UUID (partial match). Example: 1f2e3d4c
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 101,
     *         "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *         "user_id": 12,
     *         "restaurant_id": 3,
     *         "rider_id": null,
     *         "total_amount": 25.5,
     *         "delivery_fee": 3.5,
     *         "service_charge": 1.2,
     *         "delivery": 1,
     *         "delivery_status": "Delivered",
     *         "status": 2,
     *         "country": "United Kingdom",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "user": {
     *           "id": 12,
     *           "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *           "name": "Jane Doe",
     *           "email": "jane@example.com",
     *           "phone_number": "+447700900123",
     *           "type": null,
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       }
     *     ],
     *     "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/orders?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/orders?page=1",
     *     "links": [
     *       {
     *         "url": null,
     *         "label": "&laquo; Previous",
     *         "active": false
     *       },
     *       {
     *         "url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/orders?page=1",
     *         "label": "1",
     *         "active": true
     *       },
     *       {
     *         "url": null,
     *         "label": "Next &raquo;",
     *         "active": false
     *       }
     *     ],
     *     "next_page_url": null,
     *     "path": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/orders",
     *     "per_page": 5,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     * @response 404 scenario="Restaurant not found" {
     *   "message": "No query results for model [App\\Models\\Restaurant]."
     * }
     *
     * @responseField data.data object[] Orders with `user`.
     */
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

    /**
     * List restaurant menu
     *
     * Paginated list (9 per page) of the restaurant's menu items with images, prices, categories, sub categories,
     * discount and order counts, plus the categories available to the restaurant (global and restaurant-specific).
     *
     * @group Admin - Restaurants
     *
     * @urlParam restaurant string required UUID of the restaurant. Example: 8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d
     *
     * @queryParam search string Filter by menu title or description (partial match). Example: rice
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "menu": {
     *       "current_page": 1,
     *       "data": [
     *         {
     *           "id": 7,
     *           "uuid": "3c4d5e6f-7081-92a3-b4c5-d6e7f8a9b0c1",
     *           "restaurant_id": 3,
     *           "title": "Jollof Rice",
     *           "description": "Spicy jollof rice with chicken",
     *           "status": 2,
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "order_items_count": 12,
     *           "images": [],
     *           "menu_prices": [],
     *           "sub_categories": [],
     *           "categories": [],
     *           "discount": null
     *         }
     *       ],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/menu?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/menu?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/menu?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/menu",
     *       "per_page": 9,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     },
     *     "categories": {
     *       "data": [
     *         {
     *           "id": 2,
     *           "uuid": "5e6f7081-92a3-b4c5-d6e7-f8a9b0c1d2e3",
     *           "title": "Main Dishes",
     *           "description": null,
     *           "image": "https://api.moboeats.co.uk/storage/category/main.png",
     *           "status": 2
     *         }
     *       ]
     *     }
     *   }
     * }
     * @response 404 scenario="Restaurant not found" {
     *   "message": "No query results for model [App\\Models\\Restaurant]."
     * }
     *
     * @responseField data.menu object Paginated menu items.
     * @responseField data.categories object Food categories (FoodCommonCategoryCollection).
     */
    public function restaurantMenu(Request $request, Restaurant $restaurant)
    {
        $search = $request->query('search');

        $menu = Menu::with('images', 'menuPrices', 'subCategories', 'categories.food_sub_categories', 'discount')
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
     * List restaurant-specific categories
     *
     * Paginated list (8 per page) of food categories created by the restaurant, with sub categories.
     *
     * @group Admin - Restaurants
     *
     * @urlParam restaurant string required UUID of the restaurant. Example: 8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d
     *
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "categories": {
     *       "current_page": 1,
     *       "data": [
     *         {
     *           "id": 2,
     *           "uuid": "5e6f7081-92a3-b4c5-d6e7-f8a9b0c1d2e3",
     *           "title": "Main Dishes",
     *           "description": null,
     *           "image": "https://api.moboeats.co.uk/storage/category/main.png",
     *           "status": 2,
     *           "restaurant_id": 3,
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "food_sub_categories": [
     *             {
     *               "id": 9,
     *               "uuid": "4d5e6f70-8192-a3b4-c5d6-e7f8a9b0c1d2",
     *               "title": "Fresh Produce",
     *               "description": "Fruits and vegetables",
     *               "status": 2,
     *               "image": "abc123.png",
     *               "created_by": "admin@moboeats.co.uk",
     *               "created_at": "2024-06-01T10:00:00.000000Z",
     *               "updated_at": "2024-06-01T10:00:00.000000Z"
     *             }
     *           ]
     *         }
     *       ],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/categories?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/categories?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/categories?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/restaurant/8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d/categories",
     *       "per_page": 8,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     }
     *   }
     * }
     * @response 500 scenario="Restaurant not found" {
     *   "message": "Server Error"
     * }
     *
     * @responseField data.categories object Paginated categories with `food_sub_categories`.
     */
    public function restaurantCategories($id)
    {
        $restaurant = Restaurant::where('uuid', $id)->first();

        $categories = FoodCommonCategory::with('food_sub_categories')->where('restaurant_id', $restaurant->id)->paginate(8);

        return $this->success(['categories' => $categories]);
    }

    /**
     * List orders
     *
     * Paginated list (10 per page) of all orders with customer and restaurant, newest first.
     *
     * @group Admin - Orders & Payments
     *
     * @queryParam search string Filter by customer name, restaurant name or restaurant description (partial match). Example: jane
     * @queryParam per_page integer Currently ignored; the page size is fixed at 10. Example: 10
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 101,
     *         "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *         "user_id": 12,
     *         "restaurant_id": 3,
     *         "rider_id": null,
     *         "total_amount": 25.5,
     *         "delivery_fee": 3.5,
     *         "service_charge": 1.2,
     *         "delivery": 1,
     *         "delivery_status": "Delivered",
     *         "status": 2,
     *         "country": "United Kingdom",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "user": {
     *           "id": 12,
     *           "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *           "name": "Jane Doe",
     *           "email": "jane@example.com",
     *           "phone_number": "+447700900123",
     *           "type": null,
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         },
     *         "restaurant": {
     *           "id": 3,
     *           "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *           "user_id": 12,
     *           "name": "Mama's Kitchen",
     *           "email": "info@mamaskitchen.com",
     *           "phone_no": "+447700900456",
     *           "address": "1 High St, London",
     *           "status": 2,
     *           "service_charge_agreement": 10,
     *           "groceries_service_charge_agreement": 5,
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       }
     *     ],
     *     "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/orders?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/orders?page=1",
     *     "links": [
     *       {
     *         "url": null,
     *         "label": "&laquo; Previous",
     *         "active": false
     *       },
     *       {
     *         "url": "https://api.moboeats.co.uk/api/v1/admin/orders?page=1",
     *         "label": "1",
     *         "active": true
     *       },
     *       {
     *         "url": null,
     *         "label": "Next &raquo;",
     *         "active": false
     *       }
     *     ],
     *     "next_page_url": null,
     *     "path": "https://api.moboeats.co.uk/api/v1/admin/orders",
     *     "per_page": 10,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     *
     * @responseField data.data object[] Orders with `user` and `restaurant`.
     */
    public function orders(Request $request)
    {
        $search = $request->query('search');
        $per_page = $request->query('per_page');

        $orders = Order::with('user', 'restaurant')
                        ->when($search && $search != '', function($query) use ($search) {
                            $query->whereHas('user', function ($query) use ($search) {
                                $query->where('name', 'LIKE', '%'.$search.'%');
                            })
                            ->orWhereHas('restaurant', function ($query) use ($search) {
                                $query->where('name', 'LIKE', '%'.$search.'%')
                                        ->orWhere('about', 'LIKE', '%'.$search.'%');
                            });
                        })
                        ->orderBy('created_at', 'DESC')
                        ->paginate(10);

        return $this->success($orders);
    }

    /**
     * Get order details
     *
     * Return an order with restaurant, rider, items (with menu) and customer. For delivery orders without a rider,
     * `riders` lists available riders (approved, with a device token, not having rejected this order) with their
     * distance and travel time to the restaurant (via Google Distance Matrix); otherwise `riders` is empty.
     *
     * @group Admin - Orders & Payments
     *
     * @urlParam order string required UUID of the order. Example: 1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "id": 101,
     *     "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *     "user_id": 12,
     *     "restaurant_id": 3,
     *     "rider_id": null,
     *     "total_amount": 25.5,
     *     "delivery_fee": 3.5,
     *     "service_charge": 1.2,
     *     "delivery": 1,
     *     "delivery_status": "Delivered",
     *     "status": 2,
     *     "country": "United Kingdom",
     *     "created_at": "2024-06-01T10:00:00.000000Z",
     *     "updated_at": "2024-06-01T10:00:00.000000Z",
     *     "restaurant": {
     *       "id": 3,
     *       "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *       "user_id": 12,
     *       "name": "Mama's Kitchen",
     *       "email": "info@mamaskitchen.com",
     *       "phone_no": "+447700900456",
     *       "address": "1 High St, London",
     *       "status": 2,
     *       "service_charge_agreement": 10,
     *       "groceries_service_charge_agreement": 5,
     *       "created_at": "2024-06-01T10:00:00.000000Z",
     *       "updated_at": "2024-06-01T10:00:00.000000Z"
     *     },
     *     "rider": null,
     *     "order_items": [
     *       {
     *         "id": 1,
     *         "uuid": "7081a2b3-c4d5-e6f7-a8b9-c0d1e2f3a4b5",
     *         "order_id": 101,
     *         "menu_id": 7,
     *         "quantity": 2,
     *         "subtotal": 22,
     *         "menu": {
     *           "id": 7,
     *           "uuid": "3c4d5e6f-7081-92a3-b4c5-d6e7f8a9b0c1",
     *           "restaurant_id": 3,
     *           "title": "Jollof Rice",
     *           "description": "Spicy jollof rice with chicken",
     *           "status": 2,
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       }
     *     ],
     *     "user": {
     *       "id": 12,
     *       "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *       "name": "Jane Doe",
     *       "email": "jane@example.com",
     *       "phone_number": "+447700900123",
     *       "type": null,
     *       "status": 2,
     *       "country": "United Kingdom",
     *       "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *       "created_at": "2024-06-01T10:00:00.000000Z",
     *       "updated_at": "2024-06-01T10:00:00.000000Z"
     *     },
     *     "riders": [
     *       {
     *         "id": 20,
     *         "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *         "name": "Rider Ray",
     *         "email": "jane@example.com",
     *         "phone_number": "+447700900123",
     *         "type": null,
     *         "status": 2,
     *         "country": "United Kingdom",
     *         "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "distance": "2.4 km",
     *         "time_away": "7 mins"
     *       }
     *     ]
     *   }
     * }
     * @response 404 scenario="Order not found" {
     *   "message": "No query results for model [App\\Models\\Order]."
     * }
     *
     * @responseField data object The order with relations.
     * @responseField data.riders object[] Candidate riders with `distance` and `time_away` (empty when not applicable).
     */
    public function order(Order $order)
    {
        $order = $order->load('restaurant', 'rider', 'orderItems.menu', 'user');
        if ($order->delivery && !$order->rider) {
            $restaurant = $order->restaurant;
            $riders = User::where('device_token', '!=', NULL)
                            ->whereHas('roles', function($query) {
                                $query->where('name', 'rider');
                            })
                            ->whereHas('rider')
                            ->where('status', 2)
                            ->where(function($query) use ($order) {
                                $assigned_riders = Order::where('rider_id', '!=', NULL)->get()->pluck('rider_id');
                                // Get couriers who have been assigned delivery to the restaurant
                                // and are going close to another order from the same restaurant
                                // $deliveries = DB::table("orders")
                                //                     ->where('id', '!=', $order->id)
                                //                     ->where('delivery', 1)
                                //                     ->where('rider_id', '!=', NULL)
                                //                     ->where('restaurant_id', $order->restaurant_id)
                                //                     ->where('delivery_status', '!=', 'Delivered')
                                //                     ->where('delivery_status', '!=', 'On Delivery')
                                //                     ->where('delivery_location_lat', '!=', '')
                                //                     ->select(
                                //                         DB::raw("3961 * acos(cos(radians(" . $order->delivery_location_lat . "))
                                //                         * cos(radians(orders.delivery_location_lat))
                                //                         * cos(radians(orders.delivery_location_lng)
                                //                         - radians(" . $order->delivery_location_lng . "))
                                //                         + sin(radians(" . $order->delivery_location_lat. "))
                                //                         * sin(radians(orders.delivery_location_lat))) AS distance"))
                                //                     ->get();

                                $deliveries = Order::where('id', '!=', $order->id)
                                                    ->where('delivery', 1)
                                                    ->where('rider_id', '!=', NULL)
                                                    ->where('restaurant_id', $order->restaurant_id)
                                                    ->where('delivery_status', '!=', 'Delivered')
                                                    ->where('delivery_status', '!=', 'On Delivery')
                                                    ->distance($order->delivery_location_lat, $order->delivery_location_lng)
                                                    ->get();

                                // Filter to couriers distances less than 6 MILES
                                $nearby_deliveries = $deliveries->filter(function($delivery) {
                                    return (int) ($delivery->distance) <= 6;
                                })->pluck('rider_id')->values()->all();

                                // Check if rider rejected the delivery request
                                $rejected_orders = AssignedOrder::where('order_id', $order->id)->where('status', 'rejected')->pluck('user_id');

                                $query->whereNotIn('id', $rejected_orders)
                                        ->when(count($assigned_riders) > 0 && count($nearby_deliveries) > 0, function ($query) use ($assigned_riders, $nearby_deliveries) {
                                            $query->where(function ($query) use ($assigned_riders, $nearby_deliveries) {
                                                $query->orWhereIn('id', $assigned_riders)->orWhereIn('id', $nearby_deliveries);
                                            });
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
                                    fn($a, $b) => (double) explode(' ', $a['distance'])[0] <= (double) explode(' ',$b['distance'])[0],
                                ]);

            $order['riders'] = $riders;
        } else {
            $order['riders'] = [];
        }


        return $this->success($order);
    }

    /**
     * List payments and earnings summary
     *
     * Paginated list (10 per page) of completed payments (with a transaction ID), plus platform-wide totals
     * (amounts, service charges, restaurant/rider earnings and payouts) and a per-region breakdown.
     *
     * @group Admin - Orders & Payments
     *
     * @queryParam search string Filter by customer name/email or restaurant description (partial match). Example: jane
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "payments": {
     *       "current_page": 1,
     *       "data": [
     *         {
     *           "id": 44,
     *           "uuid": "2b3c4d5e-6f70-8192-a3b4-c5d6e7f8a9b0",
     *           "transaction_id": "pi_3PZx1234567890",
     *           "orderable_type": "App\\Models\\Order",
     *           "orderable_id": 101,
     *           "payment_method": "stripe",
     *           "amount": 25.5,
     *           "status": 2,
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "orderable": {
     *             "id": 101,
     *             "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *             "user_id": 12,
     *             "restaurant_id": 3,
     *             "rider_id": null,
     *             "total_amount": 25.5,
     *             "delivery_fee": 3.5,
     *             "service_charge": 1.2,
     *             "delivery": 1,
     *             "delivery_status": "Delivered",
     *             "status": 2,
     *             "country": "United Kingdom",
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z",
     *             "user": {
     *               "id": 12,
     *               "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *               "name": "Jane Doe",
     *               "email": "jane@example.com",
     *               "phone_number": "+447700900123",
     *               "type": null,
     *               "status": 2,
     *               "country": "United Kingdom",
     *               "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *               "created_at": "2024-06-01T10:00:00.000000Z",
     *               "updated_at": "2024-06-01T10:00:00.000000Z"
     *             },
     *             "restaurant": {
     *               "id": 3,
     *               "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *               "user_id": 12,
     *               "name": "Mama's Kitchen",
     *               "email": "info@mamaskitchen.com",
     *               "phone_no": "+447700900456",
     *               "address": "1 High St, London",
     *               "status": 2,
     *               "service_charge_agreement": 10,
     *               "groceries_service_charge_agreement": 5,
     *               "created_at": "2024-06-01T10:00:00.000000Z",
     *               "updated_at": "2024-06-01T10:00:00.000000Z"
     *             }
     *           }
     *         }
     *       ],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/payments?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/payments?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/payments?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/payments",
     *       "per_page": 10,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     },
     *     "total_amount": 1188,
     *     "paid_out_amount": 500,
     *     "unpaid_amount": 688,
     *     "restaurant_earnings": 1048,
     *     "rider_earnings": 140,
     *     "total_service_charges": 62.5,
     *     "restaurant_amount_paid_out": 380,
     *     "rider_amount_paid_out": 120,
     *     "region_data": {
     *       "United Kingdom": {
     *         "total_amount": 1250.5,
     *         "total_service_charges": 62.5,
     *         "rider_earnings": 140,
     *         "restaurant_earnings": 1048,
     *         "paid_amount": 500,
     *         "restaurant_amount_paid_out": 380,
     *         "rider_amount_paid_out": 120,
     *         "unpaid_amount": 750.5
     *       }
     *     }
     *   }
     * }
     *
     * @responseField data.payments object Paginated payments with `orderable` (order with `user` and `restaurant`).
     * @responseField data.total_amount number Total paid minus service charges.
     * @responseField data.paid_out_amount number Total amount paid out to restaurants and riders.
     * @responseField data.unpaid_amount integer Outstanding amount not yet paid out.
     * @responseField data.region_data object Earnings breakdown keyed by country name.
     */
    public function payments(Request $request)
    {
        $search = $request->query('search');

        $total_amount = 0;
        $total_service_charges = 0;
        $paid_amount = 0;
        $unpaid_amount = 0;
        $restaurant_earnings = 0;
        $rider_earnings = 0;
        $restaurant_amount_paid_out = 0;
        $rider_amount_paid_out = 0;
        $region_data = array();

        $regions = Order::where('delivery_status', 'Delivered')->get()->pluck('country');

        $orders = Order::where('delivery_status', 'Delivered')->get();

        $paid_amount = Payout::with('payable')->get();

        $payment_data = Payment::with('orderable.user')->where('transaction_id', '!=', NULL)->get();

        foreach ($regions as $region) {
            $region_data[$region]['total_amount'] = $payment_data->where('orderable.user.country', $region)->sum('amount');
            $region_data[$region]['total_service_charges'] = $orders->where('country', $region)->sum('service_charge');
            $region_data[$region]['rider_earnings'] = $orders->where('delivery', true)->where('country', $region)->sum('delivery_fee');
            $region_data[$region]['restaurant_earnings'] = $region_data[$region]['total_amount'] - $region_data[$region]['total_service_charges'] - $region_data[$region]['rider_earnings'];

            $region_data[$region]['paid_amount'] = $paid_amount->where('payable.country', $region)->sum('amount');
            $region_data[$region]['restaurant_amount_paid_out'] = $paid_amount->where('payable_type', Restaurant::class)->where('payable.country', $region)->sum('amount');
            $region_data[$region]['rider_amount_paid_out'] = $paid_amount->where('payable_type', User::class)->where('payable.country', $region)->sum('amount');

            $region_data[$region]['unpaid_amount'] = (double) $region_data[$region]['total_amount'] - ((double) $region_data[$region]['paid_amount']);
        }

        $total_amount = $payment_data->sum('amount');
        $total_service_charges = $orders->sum('service_charge');
        $total_amount = $total_amount - $total_service_charges;
        $restaurant_earnings = $total_amount;
        $rider_earnings = $orders->where('delivery', true)->sum('delivery_fee');
        $restaurant_earnings = $restaurant_earnings - $rider_earnings;

        $paid_amount = $paid_amount->sum('amount');
        $restaurant_amount_paid_out = Payout::where('payable_type', Restaurant::class)->sum('amount');
        $rider_amount_paid_out = Payout::where('payable_type', User::class)->sum('amount');

        $unpaid_amount = (int) $total_amount - (int) $paid_amount;


        $payments = Payment::with('orderable.user', 'orderable.restaurant')
                            ->where('transaction_id', '!=', NULL)
                            ->when($search && $search != null, function ($query) use ($search) {
                                $query->whereHas('orderable', function ($query) use ($search) {
                                    $query->whereHas('user', function ($query) use ($search) {
                                        $query->where('name', 'LIKE', '%'.$search.'%')->orWhere('email', 'LIKE', '%'.$search.'%');
                                    });
                                })
                                ->orWhereHas('orderable', function ($query) use ($search) {
                                    $query->whereHas('restaurant', function ($query) use ($search) {
                                        $query->where('amount', 'LIKE', '%'.$search.'%')->orWhere('about', 'LIKE', '%'.$search.'%');
                                    });
                                });
                            })
                            ->orderBy('created_at', 'DESC')
                            ->paginate(10);

        return $this->success([
            'payments' => $payments,
            'total_amount' => $total_amount,
            'paid_out_amount' => $paid_amount,
            'unpaid_amount' => $unpaid_amount,
            'restaurant_earnings' => $restaurant_earnings,
            'rider_earnings' => $rider_earnings,
            'total_service_charges' => $total_service_charges,
            'restaurant_amount_paid_out' => $restaurant_amount_paid_out,
            'rider_amount_paid_out' => $rider_amount_paid_out,
            'region_data' => $region_data,
        ]);
    }

    /**
     * List activity logs
     *
     * Paginated list (10 per page) of activity log entries with causer and subject.
     *
     * @group Admin
     * @subgroup Activity Logs
     *
     * @queryParam search string Currently ignored. Example: order
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "log_name": "default",
     *         "description": "updated",
     *         "subject_type": "App\\Models\\Order",
     *         "subject_id": 101,
     *         "causer_type": "App\\Models\\User",
     *         "causer_id": 1,
     *         "properties": {},
     *         "event": "updated",
     *         "batch_uuid": null,
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "causer": {
     *           "id": 12,
     *           "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *           "name": "Jane Doe",
     *           "email": "jane@example.com",
     *           "phone_number": "+447700900123",
     *           "type": null,
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         },
     *         "subject": {
     *           "id": 101,
     *           "uuid": "1f2e3d4c-5b6a-7980-a1b2-c3d4e5f6a7b8",
     *           "user_id": 12,
     *           "restaurant_id": 3,
     *           "rider_id": null,
     *           "total_amount": 25.5,
     *           "delivery_fee": 3.5,
     *           "service_charge": 1.2,
     *           "delivery": 1,
     *           "delivery_status": "Delivered",
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       }
     *     ],
     *     "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/logs?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/logs?page=1",
     *     "links": [
     *       {
     *         "url": null,
     *         "label": "&laquo; Previous",
     *         "active": false
     *       },
     *       {
     *         "url": "https://api.moboeats.co.uk/api/v1/admin/logs?page=1",
     *         "label": "1",
     *         "active": true
     *       },
     *       {
     *         "url": null,
     *         "label": "Next &raquo;",
     *         "active": false
     *       }
     *     ],
     *     "next_page_url": null,
     *     "path": "https://api.moboeats.co.uk/api/v1/admin/logs",
     *     "per_page": 10,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     *
     * @responseField data.data object[] Activity entries with `causer` and `subject`.
     */
    public function logs(Request $request)
    {
        $search = $request->query('search');

        $logs = Activity::with('causer', 'subject')
                                ->paginate(10);

        return $this->success($logs);
    }

    /**
     * List discounted menu items
     *
     * Paginated list (6 per page) of menu items that have a discount, with discount and restaurant.
     *
     * @group Admin - Marketing
     *
     * @queryParam search string Filter by menu title (partial match). Example: rice
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "discounts": {
     *       "current_page": 1,
     *       "data": [
     *         {
     *           "id": 7,
     *           "uuid": "3c4d5e6f-7081-92a3-b4c5-d6e7f8a9b0c1",
     *           "restaurant_id": 3,
     *           "title": "Jollof Rice",
     *           "description": "Spicy jollof rice with chicken",
     *           "status": 2,
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "discount": {
     *             "id": 1,
     *             "menu_id": 7,
     *             "percentage": 10,
     *             "start_date": "2024-06-01",
     *             "end_date": "2024-06-30"
     *           },
     *           "restaurant": {
     *             "id": 3,
     *             "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *             "user_id": 12,
     *             "name": "Mama's Kitchen",
     *             "email": "info@mamaskitchen.com",
     *             "phone_no": "+447700900456",
     *             "address": "1 High St, London",
     *             "status": 2,
     *             "service_charge_agreement": 10,
     *             "groceries_service_charge_agreement": 5,
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         }
     *       ],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/discounts?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/discounts?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/discounts?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/discounts",
     *       "per_page": 6,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     }
     *   }
     * }
     *
     * @responseField data.discounts object Paginated menu items with `discount` and `restaurant`.
     */
    public function discounts(Request $request)
    {
        $search = $request->query('search');

        $menu = Menu::with('discount', 'restaurant')
                    ->whereHas('discount')
                    ->when($search && $search != '', function ($query) use ($search) {
                        $query->where('title', 'LIKE', '%'.$search.'%');
                    })
                    ->paginate(6);

        return $this->success([
            'discounts' => $menu,
        ]);
    }

    /**
     * Update delivery rate
     *
     * Set the `Delivery Rate` platform setting (percentage). The setting is created if it does not exist.
     *
     * @group Admin - Settings
     *
     * @bodyParam rate number required New value. Example: 10
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": "Rate updated successfully",
     *   "data": ""
     * }
     * @response 400 scenario="Rate missing" {
     *   "status": "Error has occured...",
     *   "message": "Rate is required",
     *   "data": ""
     * }
     */
    public function updateDeliveryRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rate' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error('', 'Rate is required', 400);
        }

        $setting = Setting::where('name', 'Delivery Rate')->first();

        if (!$setting) {
            $setting = Setting::create([
                'name' => 'Delivery Rate',
                'variable' => $request->rate,
                'type' => 'percentage'
            ]);
        } else {
            $setting->update([
                'variable' => $request->rate
            ]);
        }

        return $this->success('', 'Rate updated successfully');
    }

    /**
     * Update base service charge rate
     *
     * Set the `Base Service Charge Rate` platform setting (percentage). The setting is created if it does not exist.
     *
     * @group Admin - Settings
     *
     * @bodyParam rate number required New value. Example: 10
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": "Rate updated successfully",
     *   "data": ""
     * }
     * @response 400 scenario="Rate missing" {
     *   "status": "Error has occured...",
     *   "message": "Rate is required",
     *   "data": ""
     * }
     */
    public function updateBaseRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rate' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error('', 'Rate is required', 400);
        }

        $setting = Setting::where('name', 'Base Service Charge Rate')->first();

        if (!$setting) {
            $setting = Setting::create([
                'name' => 'Base Service Charge Rate',
                'variable' => $request->rate,
                'type' => 'percentage'
            ]);
        } else {
            $setting->update([
                'variable' => $request->rate
            ]);
        }

        return $this->success('', 'Rate updated successfully');
    }

    /**
     * Update groceries base service charge rate
     *
     * Set the `Base Groceries Service Charge Rate` platform setting (percentage). The setting is created if it does not exist.
     *
     * @group Admin - Settings
     *
     * @bodyParam rate number required New value. Example: 10
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": "Rate updated successfully",
     *   "data": ""
     * }
     * @response 400 scenario="Rate missing" {
     *   "status": "Error has occured...",
     *   "message": "Rate is required",
     *   "data": ""
     * }
     */
    public function updateGroceryBaseRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rate' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error('', 'Rate is required', 400);
        }

        $setting = Setting::where('name', 'Base Groceries Service Charge Rate')->first();

        if (!$setting) {
            $setting = Setting::create([
                'name' => 'Base Groceries Service Charge Rate',
                'variable' => $request->rate,
                'type' => 'percentage'
            ]);
        } else {
            $setting->update([
                'variable' => $request->rate
            ]);
        }

        return $this->success('', 'Rate updated successfully');
    }

    /**
     * Update restaurant registration fee
     *
     * Set the `Registration Fee` platform setting (amount). The setting is created if it does not exist.
     *
     * @group Admin - Settings
     *
     * @bodyParam rate number required New value. Example: 50
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": "Registration Fee updated successfully",
     *   "data": ""
     * }
     * @response 400 scenario="Rate missing" {
     *   "status": "Error has occured...",
     *   "message": "Rate is required",
     *   "data": ""
     * }
     */
    public function updateRegistrationFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rate' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error('', 'Rate is required', 400);
        }

        $setting = Setting::where('name', 'Registration Fee')->first();

        if (!$setting) {
            $setting = Setting::create([
                'name' => 'Registration Fee',
                'variable' => $request->rate,
                'type' => 'amount'
            ]);
        } else {
            $setting->update([
                'variable' => $request->rate
            ]);
        }

        return $this->success('', 'Registration Fee updated successfully');
    }

    /**
     * Generate a QR code
     *
     * Generate a QR code PNG for the given string (defaults to the partner signup URL) and download it.
     *
     * @group Admin - Marketing
     *
     * @bodyParam string string Text or URL to encode. Defaults to `https://partner.moboeats.co.uk/signup`. Example: https://partner.moboeats.co.uk/signup
     *
     * @response 200 scenario="File download" <<binary>> The QR code PNG image (QR.png).
     */
    public function qrCode(Request $request)
    {
        $string = 'https://partner.moboeats.co.uk/signup';
        if ($request->has('string') && $request->string != '') {
            $string = $request->string;
        }

        $barcode = new DNS2D;

        Storage::disk('public')->put('QR.png',base64_decode($barcode->getBarcodePNG($string, "QRCODE", 10, 10)));

        return Storage::disk('public')->download('QR.png');
    }

    /**
     * Create a diet subscription package
     *
     * Create a diet subscription package. Only users with role `admin` may call this.
     *
     * @group Diet Plans
     *
     * @bodyParam name string required Package name. Example: Monthly Plan
     * @bodyParam price number required Package price. Example: 29.99
     * @bodyParam currency string required Currency code. Example: GBP
     * @bodyParam duration string required Billing period. One of `daily`, `weekly`, `monthly`, `quarterly`, `half annually`, `annually`. Example: monthly
     * @bodyParam tag_line string Short tag line. Example: Eat well every day
     * @bodyParam description string Package description. Example: Personalised meal plans for a month
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": "Package created successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Monthly Plan",
     *     "currency": "GBP",
     *     "price": "29.99",
     *     "duration": "monthly",
     *     "tag_line": "Eat well every day",
     *     "description": "Personalised meal plans for a month",
     *     "created_at": "2024-06-01T10:00:00.000000Z",
     *     "updated_at": "2024-06-01T10:00:00.000000Z"
     *   }
     * }
     * @response 400 scenario="Validation error" {
     *   "status": "Error has occured...",
     *   "message": "Invalid data",
     *   "data": {
     *     "name": [
     *       "The name field is required."
     *     ]
     *   }
     * }
     * @response 403 scenario="Not an admin" {
     *   "status": "Error has occured...",
     *   "message": "You are not allowed to perform this action",
     *   "data": ""
     * }
     * @response 500 scenario="Creation failed" {
     *   "status": "Error has occured...",
     *   "message": "Package creation failed",
     *   "data": ""
     * }
     *
     * @responseField data object The created package.
     */
    public function storeDietPackage(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) {
            return $this->error('', 'You are not allowed to perform this action', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'price' => ['required'],
            'currency' => ['required'],
            'duration' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid data', 400);
        }

        $package = DietSubscriptionPackage::create([
            'name' => $request->name,
            'currency' => $request->currency,
            'price' => $request->price,
            'duration' => $request->duration,
            'tag_line' => $request->has('tag_line') && $request->tag_line != '' ? $request->tag_line : NULL,
            'description' => $request->has('description') && $request->description != '' ? $request->description : NULL,
        ]);

        if ($package) {
            return $this->success($package, 'Package created successfully');
        }

        return $this->error('', 'Package creation failed', 500);
    }

    /**
     * List diet plans
     *
     * Paginated list (10 per page) of diet plan meals with their user. Available to admins at
     * `GET /api/v1/admin/diet/plans` and to authenticated users at `GET /api/v1/orderer/diet/plans`.
     *
     * @group Diet Plans
     *
     * @queryParam user integer Filter by numeric user ID. Example: 12
     * @queryParam date string Currently ignored. Example: 2024-06-01
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "user_id": 12,
     *         "date": "2024-06-01",
     *         "type": "Breakfast",
     *         "meal": "Oatmeal with berries",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "user": {
     *           "id": 12,
     *           "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *           "name": "Jane Doe",
     *           "email": "jane@example.com",
     *           "phone_number": "+447700900123",
     *           "type": null,
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       }
     *     ],
     *     "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/diet/plans?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/diet/plans?page=1",
     *     "links": [
     *       {
     *         "url": null,
     *         "label": "&laquo; Previous",
     *         "active": false
     *       },
     *       {
     *         "url": "https://api.moboeats.co.uk/api/v1/admin/diet/plans?page=1",
     *         "label": "1",
     *         "active": true
     *       },
     *       {
     *         "url": null,
     *         "label": "Next &raquo;",
     *         "active": false
     *       }
     *     ],
     *     "next_page_url": null,
     *     "path": "https://api.moboeats.co.uk/api/v1/admin/diet/plans",
     *     "per_page": 10,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     *
     * @responseField data.data object[] Diet plan meals with `user`.
     */
    public function plans(Request $request)
    {
        $date = $request->query('date');
        $user = $request->query('user');

        $plans = DietPlan::with('user')
                        ->when($user && $user != '', function ($query) use ($user) {
                            $query->whereHas('user', function ($query) use ($user) {
                                $query->where('id', $user);
                            });
                        })
                        ->paginate(10);

        return $this->success($plans);
    }

    /**
     * List diet plan subscribers
     *
     * Paginated list (10 per page) of users with at least one diet subscription, with their diet plans and subscriptions.
     *
     * @group Diet Plans
     *
     * @queryParam user string Filter by user name (partial match). Example: jane
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 12,
     *         "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *         "name": "Jane Doe",
     *         "email": "jane@example.com",
     *         "phone_number": "+447700900123",
     *         "type": null,
     *         "status": 2,
     *         "country": "United Kingdom",
     *         "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "diet_plans": [
     *           {
     *             "id": 1,
     *             "user_id": 12,
     *             "date": "2024-06-01",
     *             "type": "Breakfast",
     *             "meal": "Oatmeal with berries",
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         ],
     *         "diet_subscriptions": [
     *           {
     *             "id": 1,
     *             "user_id": 12,
     *             "diet_subscription_package_id": 1,
     *             "start_date": "2024-06-01",
     *             "end_date": "2024-06-30",
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         ]
     *       }
     *     ],
     *     "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/diet/plans/subscribers?page=1",
     *     "from": 1,
     *     "last_page": 1,
     *     "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/diet/plans/subscribers?page=1",
     *     "links": [
     *       {
     *         "url": null,
     *         "label": "&laquo; Previous",
     *         "active": false
     *       },
     *       {
     *         "url": "https://api.moboeats.co.uk/api/v1/admin/diet/plans/subscribers?page=1",
     *         "label": "1",
     *         "active": true
     *       },
     *       {
     *         "url": null,
     *         "label": "Next &raquo;",
     *         "active": false
     *       }
     *     ],
     *     "next_page_url": null,
     *     "path": "https://api.moboeats.co.uk/api/v1/admin/diet/plans/subscribers",
     *     "per_page": 10,
     *     "prev_page_url": null,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     *
     * @responseField data.data object[] Users with `diet_plans` and `diet_subscriptions`.
     */
    public function dietPlanSubscribers(Request $request)
    {
        $user = $request->query('user');

        $subscribers = User::with([
                                'dietPlans',
                                'dietSubscriptions' => function ($query) {
                                    $query->latest()->first();
                                }
                            ])
                            ->whereHas('dietSubscriptions')
                            ->when($user && $user != '', function ($query) use ($user) {
                                $query->where('name', 'LIKE', '%'.$user.'%');
                            })
                            ->paginate(10);

        return $this->success($subscribers);
    }

    /**
     * Get diet plan subscriber
     *
     * Return a subscriber with their diet plans (newest date first) and diet subscriptions.
     *
     * @group Diet Plans
     *
     * @urlParam id string required UUID of the user. Example: 9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "id": 12,
     *     "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "phone_number": "+447700900123",
     *     "type": null,
     *     "status": 2,
     *     "country": "United Kingdom",
     *     "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *     "created_at": "2024-06-01T10:00:00.000000Z",
     *     "updated_at": "2024-06-01T10:00:00.000000Z",
     *     "diet_plans": [
     *       {
     *         "id": 1,
     *         "user_id": 12,
     *         "date": "2024-06-01",
     *         "type": "Breakfast",
     *         "meal": "Oatmeal with berries",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z"
     *       }
     *     ],
     *     "diet_subscriptions": [
     *       {
     *         "id": 1,
     *         "user_id": 12,
     *         "diet_subscription_package_id": 1,
     *         "start_date": "2024-06-01",
     *         "end_date": "2024-06-30",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z"
     *       }
     *     ]
     *   }
     * }
     * @response 500 scenario="User not found" {
     *   "message": "Server Error"
     * }
     *
     * @responseField data object The user with `diet_plans` and `diet_subscriptions`.
     */
    public function dietPlanSubscriber($id)
    {
        $subscriber = User::with([
                'dietPlans' => function ($query) {
                    $query->orderBy('date', 'DESC');
                },
                'dietSubscriptions' => function ($query) {
                    $query->latest()->first();
                }
            ])
            ->where('uuid', $id)
            ->first();

        if ($subscriber->dietPlans) {
            $subscriber->dietPlans = $subscriber->dietPlans->groupBy('date');
        }

        return $this->success($subscriber);
    }

    /**
     * Create a diet plan
     *
     * Create one diet plan meal per entry in `meals` for the given user and date, then send the user a notification.
     *
     * @group Diet Plans
     *
     * @bodyParam user_id integer required Numeric ID of an existing user. Example: 12
     * @bodyParam date string required Plan date. Example: 2024-06-01
     * @bodyParam meals object required Meals keyed by meal type (`Breakfast`, `Lunch`, `Dinner`); each value is the meal description. Example: {"Breakfast": "Oatmeal with berries", "Lunch": "Grilled chicken salad", "Dinner": "Salmon with vegetables"}
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": "Meal plan created successfully"
     * }
     * @response 400 scenario="Validation error" {
     *   "status": "Error has occured...",
     *   "message": "Diet plan validation",
     *   "data": {
     *     "user_id": [
     *       "The user id field is required."
     *     ]
     *   }
     * }
     */
    public function storeDietPlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'exists:users,id'],
            'date' => ['required', 'date'],
            'meals' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Diet plan validation', 400);
        }

        foreach ($request->meals as $key => $meal) {
            DietPlan::create([
                'user_id' => $request->user_id,
                'date' => Carbon::parse($request->date)->format('Y-m-d'),
                'meal' => $meal,
                'type' => $key,
            ]);
        }

        // Notify user of new meal plan
        SendNotification::dispatchAfterResponse(User::find($request->user_id), 'A new meal plan has been created for you.');

        return $this->success('Meal plan created successfully');
    }

    /**
     * List rider payouts
     *
     * Paginated list (10 per page) of payouts made to riders, newest first.
     *
     * @group Admin - Orders & Payments
     *
     * @queryParam search string Currently ignored. Example: jane
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "payouts": {
     *       "current_page": 1,
     *       "data": [
     *         {
     *           "id": 1,
     *           "payable_id": "12",
     *           "payable_type": "App\\Models\\User",
     *           "amount": "120.00",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "payable": {
     *             "id": 12,
     *             "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *             "name": "Jane Doe",
     *             "email": "jane@example.com",
     *             "phone_number": "+447700900123",
     *             "type": null,
     *             "status": 2,
     *             "country": "United Kingdom",
     *             "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         }
     *       ],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/payouts/riders?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/payouts/riders?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/payouts/riders?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/payouts/riders",
     *       "per_page": 10,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     }
     *   }
     * }
     *
     * @responseField data.payouts object Paginated payouts with `payable` (the rider user).
     */
    public function ridersPayouts(Request $request)
    {
        $search = $request->query('search');

        $payouts = Payout::with('payable')->where('payable_type', User::class)->latest()->paginate(10);

        return $this->success(['payouts' => $payouts]);
    }

    /**
     * List partner payouts
     *
     * Paginated list (10 per page) of payouts made to restaurants, newest first.
     *
     * @group Admin - Orders & Payments
     *
     * @queryParam search string Currently ignored. Example: mama
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "payouts": {
     *       "current_page": 1,
     *       "data": [
     *         {
     *           "id": 1,
     *           "payable_id": "3",
     *           "payable_type": "App\\Models\\Restaurant",
     *           "amount": "120.00",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "payable": {
     *             "id": 3,
     *             "uuid": "8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d",
     *             "user_id": 12,
     *             "name": "Mama's Kitchen",
     *             "email": "info@mamaskitchen.com",
     *             "phone_no": "+447700900456",
     *             "address": "1 High St, London",
     *             "status": 2,
     *             "service_charge_agreement": 10,
     *             "groceries_service_charge_agreement": 5,
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         }
     *       ],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/payouts/partners?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/payouts/partners?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/payouts/partners?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/payouts/partners",
     *       "per_page": 10,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     }
     *   }
     * }
     *
     * @responseField data.payouts object Paginated payouts with `payable` (the restaurant).
     */
    public function partnersPayouts(Request $request)
    {
        $search = $request->query('search');

        $payouts = Payout::with('payable')->where('payable_type', Restaurant::class)->latest()->paginate(10);

        return $this->success(['payouts' => $payouts]);
    }

    /**
     * Upload payouts
     *
     * Import payout records from an Excel (.xlsx) file.
     *
     * @group Admin - Orders & Payments
     *
     * @bodyParam file file required Excel file (.xlsx) containing payouts.
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": "Upload successful"
     * }
     * @response 422 scenario="Validation error" {
     *   "status": "Error has occured...",
     *   "message": "Upload error",
     *   "data": {
     *     "file": [
     *       "The file field is required."
     *     ]
     *   }
     * }
     */
    public function uploadPayouts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'mimes:xlsx']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Upload error', 422);
        }

        Excel::import(new Payouts, $request->file('file')->store('public'));

        return $this->success('Upload successful');
    }

    /**
     * List permissions
     *
     * Return all admin permission groups with their permissions.
     *
     * @group Admin - Users
     * @subgroup Roles & Permissions
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Dashboard",
     *       "type": "admin",
     *       "created_at": "2024-06-01T10:00:00.000000Z",
     *       "updated_at": "2024-06-01T10:00:00.000000Z",
     *       "permissions": [
     *         {
     *           "id": 1,
     *           "name": "view dashboard",
     *           "display_name": "View Dashboard",
     *           "description": null,
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z"
     *         }
     *       ]
     *     }
     *   ]
     * }
     *
     * @responseField data object[] Permission groups with `permissions`.
     */
    public function permissions(Request $request)
    {
        $permissions = PermissionGroup::where('type', 'admin')->with('permissions')->get();

        return $this->success($permissions);
    }

    /**
     * List roles
     *
     * Paginated list of custom back-office roles (excluding admin, orderer, restaurant, restaurant employee and rider)
     * with their permissions, plus the admin permission groups filtered to permissions the current user holds.
     *
     * @group Admin - Users
     * @subgroup Roles & Permissions
     *
     * @queryParam per_page integer Number of roles per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "roles": {
     *       "current_page": 1,
     *       "data": [
     *         {
     *           "id": 5,
     *           "name": "finance",
     *           "display_name": null,
     *           "description": "Finance team",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "permissions": [
     *             {
     *               "id": 1,
     *               "name": "view dashboard",
     *               "display_name": "View Dashboard",
     *               "description": null,
     *               "created_at": "2024-06-01T10:00:00.000000Z",
     *               "updated_at": "2024-06-01T10:00:00.000000Z"
     *             }
     *           ]
     *         }
     *       ],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/roles?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/roles?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/roles?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/roles",
     *       "per_page": 15,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     },
     *     "permissions": [
     *       {
     *         "id": 1,
     *         "name": "Dashboard",
     *         "type": "admin",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z",
     *         "permissions": [
     *           {
     *             "id": 1,
     *             "name": "view dashboard",
     *             "display_name": "View Dashboard",
     *             "description": null,
     *             "created_at": "2024-06-01T10:00:00.000000Z",
     *             "updated_at": "2024-06-01T10:00:00.000000Z"
     *           }
     *         ]
     *       }
     *     ]
     *   }
     * }
     *
     * @responseField data.roles object Paginated roles with `permissions`.
     * @responseField data.permissions object[] Permission groups limited to the current user's permissions.
     */
    public function roles(Request $request)
    {
        $roles = Role::with('permissions')->whereNotIn('name', ['admin', 'orderer', 'restaurant', 'restaurant employee', 'rider'])->paginate($request->query('per_page'));

        // Make sure logged in user only sees the permission they have
        $user_permissions = auth()->user()->allPermissions()->pluck('name');

        $permissions = PermissionGroup::where('type', 'admin')
        ->with(['permissions' => function ($query) use ($user_permissions) {
            $query->whereIn('name', $user_permissions);
        }])
        ->get();

        return $this->success(['roles' => $roles, 'permissions' => $permissions]);
    }

    /**
     * Create a role
     *
     * Create a role and sync its permissions.
     *
     * @group Admin - Users
     * @subgroup Roles & Permissions
     *
     * @bodyParam name string required Unique role name. Example: finance
     * @bodyParam permissions string[] required Permission names (or IDs) to attach. Example: ["view dashboard", "view payments"]
     * @bodyParam description string Role description (the string `null` is treated as empty). Example: Finance team
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "id": 5,
     *     "name": "finance",
     *     "display_name": null,
     *     "description": "Finance team",
     *     "created_at": "2024-06-01T10:00:00.000000Z",
     *     "updated_at": "2024-06-01T10:00:00.000000Z"
     *   }
     * }
     * @response 400 scenario="Validation error" {
     *   "status": "Error has occured...",
     *   "message": "Role Creation",
     *   "data": {
     *     "name": [
     *       "The name has already been taken."
     *     ]
     *   }
     * }
     *
     * @responseField data object The created role.
     */
    public function storeRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'unique:roles,name'],
            'permissions' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Role Creation', 400);
        }

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->has('description') && $request->description != 'null' ? $request->description : NULL,
        ]);

        $role->syncPermissions($request->permissions);

        return $this->success($role);
    }

    /**
     * Update a role
     *
     * Update a role's name and description and sync its permissions.
     *
     * @group Admin - Users
     * @subgroup Roles & Permissions
     *
     * @bodyParam role_id integer required ID of the role. Example: 5
     * @bodyParam name string required Unique role name. Example: finance
     * @bodyParam permissions string[] required Permission names (or IDs) to attach. Example: ["view dashboard", "view payments"]
     * @bodyParam description string Role description (the string `null` is treated as empty). Example: Finance team
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "id": 5,
     *     "name": "finance",
     *     "display_name": null,
     *     "description": "Finance team",
     *     "created_at": "2024-06-01T10:00:00.000000Z",
     *     "updated_at": "2024-06-01T10:00:00.000000Z"
     *   }
     * }
     * @response 400 scenario="Validation error" {
     *   "status": "Error has occured...",
     *   "message": "Role Updated",
     *   "data": {
     *     "name": [
     *       "The name has already been taken."
     *     ]
     *   }
     * }
     * @response 500 scenario="Role not found" {
     *   "message": "Server Error"
     * }
     *
     * @responseField data object The updated role.
     */
    public function updateRole(Request $request)
    {
        $role = Role::find($request->role_id);
        if ($role) {
            $rule = Rule::unique('roles')->ignore($role->id, 'id');
        }

        $validator = Validator::make($request->all(), [
            'role_id' => ['required', 'exists:roles,id'],
            'name' => ['required', $rule],
            'permissions' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Role Updated', 400);
        }

        $role->update([
            'name' => $request->name,
            'description' => $request->has('description') && $request->description != 'null' ? $request->description : NULL,
        ]);

        $role->syncPermissions($request->permissions);

        return $this->success($role);
    }

    /**
     * List back-office users
     *
     * Paginated list of back-office users (any role other than orderer, restaurant, restaurant employee or rider,
     * excluding the built-in admin accounts) with their roles and countries, plus all assignable roles.
     *
     * @group Admin - Users
     * @subgroup Roles & Permissions
     *
     * @queryParam per_page integer Number of users per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "users": {
     *       "current_page": 1,
     *       "data": [
     *         {
     *           "id": 12,
     *           "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *           "name": "Jane Doe",
     *           "email": "jane@example.com",
     *           "phone_number": "+447700900123",
     *           "type": null,
     *           "status": 2,
     *           "country": "United Kingdom",
     *           "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *           "created_at": "2024-06-01T10:00:00.000000Z",
     *           "updated_at": "2024-06-01T10:00:00.000000Z",
     *           "countries": [
     *             {
     *               "id": 1,
     *               "name": "United Kingdom"
     *             }
     *           ],
     *           "roles": [
     *             {
     *               "id": 5,
     *               "name": "finance",
     *               "display_name": null,
     *               "description": "Finance team",
     *               "created_at": "2024-06-01T10:00:00.000000Z",
     *               "updated_at": "2024-06-01T10:00:00.000000Z"
     *             }
     *           ]
     *         }
     *       ],
     *       "first_page_url": "https://api.moboeats.co.uk/api/v1/admin/admins?page=1",
     *       "from": 1,
     *       "last_page": 1,
     *       "last_page_url": "https://api.moboeats.co.uk/api/v1/admin/admins?page=1",
     *       "links": [
     *         {
     *           "url": null,
     *           "label": "&laquo; Previous",
     *           "active": false
     *         },
     *         {
     *           "url": "https://api.moboeats.co.uk/api/v1/admin/admins?page=1",
     *           "label": "1",
     *           "active": true
     *         },
     *         {
     *           "url": null,
     *           "label": "Next &raquo;",
     *           "active": false
     *         }
     *       ],
     *       "next_page_url": null,
     *       "path": "https://api.moboeats.co.uk/api/v1/admin/admins",
     *       "per_page": 15,
     *       "prev_page_url": null,
     *       "to": 1,
     *       "total": 1
     *     },
     *     "roles": [
     *       {
     *         "id": 5,
     *         "name": "finance",
     *         "display_name": null,
     *         "description": "Finance team",
     *         "created_at": "2024-06-01T10:00:00.000000Z",
     *         "updated_at": "2024-06-01T10:00:00.000000Z"
     *       }
     *     ]
     *   }
     * }
     *
     * @responseField data.users object Paginated users with `roles` and `countries`.
     * @responseField data.roles object[] Assignable roles.
     */
    public function admins(Request $request)
    {
        $users = User::with('countries')->whereNotIn('email', ['admin@moboeats.com', 'admin@moboeats.co.uk'])
        ->with('roles')->whereHas('roles', function ($query) {
            $query->whereNotIn('name', ['orderer', 'restaurant', 'restaurant employee', 'rider']);
        })
        ->paginate($request->query('per_page'));

        $roles = Role::whereNotIn('name', ['orderer', 'restaurant', 'restaurant employee', 'rider'])->get();

        return $this->success(['users' => $users, 'roles' => $roles]);
    }

    /**
     * Assign a role to a user
     *
     * Replace the user's roles with the given role and, when `user_countries` is provided, replace the countries the user
     * may manage. Also routed as `POST /api/v1/admin/admins/update`.
     *
     * @group Admin - Users
     * @subgroup Roles & Permissions
     *
     * @bodyParam role_id integer required ID of the role. Example: 5
     * @bodyParam user_id integer required Numeric ID of the user. Example: 12
     * @bodyParam user_countries integer[] IDs of countries the user may manage (replaces existing ones). Example: [1, 2]
     *
     * @response 200 scenario="Success" {
     *   "status": "Request was succesful",
     *   "message": null,
     *   "data": {
     *     "id": 12,
     *     "uuid": "9c1f6a2e-3b4d-4e5f-8a9b-0c1d2e3f4a5b",
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "phone_number": "+447700900123",
     *     "type": null,
     *     "status": 2,
     *     "country": "United Kingdom",
     *     "image": "https://api.moboeats.co.uk/assets/user/default.png",
     *     "created_at": "2024-06-01T10:00:00.000000Z",
     *     "updated_at": "2024-06-01T10:00:00.000000Z"
     *   }
     * }
     * @response 400 scenario="Validation error" {
     *   "status": "Error has occured...",
     *   "message": "Assign Role",
     *   "data": {
     *     "role_id": [
     *       "The selected role id is invalid."
     *     ]
     *   }
     * }
     *
     * @responseField data object The updated user.
     */
    public function assignRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => ['required', 'exists:roles,id'],
            'user_id' => ['required', 'exists:users,id']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Assign Role', 400);
        }

        $user = User::find($request->user_id);

        $user->syncRoles([$request->role_id]);

        if ($request->has('user_countries') && !empty($request->user_countries)) {
            // Delete Current User Countries
            UserCountry::where('user_id', $user->id)->delete();

            foreach ($request->user_countries as $user_country) {
                UserCountry::create([
                    'user_id' => $user->id,
                    'country_id' => $user_country
                ]);
            };
        }

        return $this->success($user);
    }
}

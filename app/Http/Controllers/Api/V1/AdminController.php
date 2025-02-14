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

class AdminController extends Controller
{
    use HttpResponses;

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

            $user = User::where('email', $request->email)->first();

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

        $password = Str::random(8);

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

        if ($user->email) {
            SendCommunication::dispatchAfterResponse('mail', $user->email, 'NewAccount', ['user' => $user, 'password' => $password]);
        }

        return $this->success(['user' => $user, 'User added successfully']);
    }

    public function dashboard(Request $request)
    {
        $orders_timeline = $request->query('orders_timeline');
        $payments_timeline = $request->query('payments_timeline');
        $users = User::whereHasRole('orderer')->count();
        $restaurants = User::whereHasRole('restaurant')->count();
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

    public function categories()
    {
        $categories = FoodCommonCategory::with('food_sub_categories')->where('title', '!=', 'groceries')->orderBy('created_at', 'DESC')->paginate(7);

        return $this->success($categories);
    }

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

    public function users(Request $request, $role)
    {
        $search = $request->query('search');

        $users = User::with('orders', 'restaurants.orders', 'restaurants.menus', 'restaurants.users')
                        ->whereHas('roles', function ($query) use ($role) { $query->where('name', $role); })
                        ->when($search && $search != '', function($query) use ($search) {
                            $query->where(function ($query) use ($search) {
                                $query->where('name', 'LIKE', '%'.$search.'%')
                                        ->orWhere('email', 'LIKE', '%'.$search.'%');
                            });
                        })
                        ->orderBy('created_at', 'DESC')
                        ->paginate(10);

        return $this->success($users);
    }

    public function user($id)
    {
        $user = User::withCount('orders', 'restaurants')->with(['roles'])->find($id);

        $restaurants = Restaurant::where('user_id', $user->id)->with('orders')->paginate(5);

        $orders = Order::where('user_id', $user->id)->with('restaurant')->paginate(5);

        return $this->success(['user' => $user, 'orders' => $orders, 'restaurants' => $restaurants]);
    }

    public function restaurantAdmin($id)
    {
        $user = User::withCount('orders', 'restaurants')->with(['roles'])->find($id);

        $restaurants = Restaurant::where('user_id', $user->id)->with('orders')->paginate(5);

        return $this->success(['restaurants' => $restaurants, 'user' => $user]);
    }

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

    public function restaurantReviews(Restaurant $restaurant)
    {
        $review = Review::where('reviewable_type', Restaurant::class)->where('reviewable_id', $restaurant->id)->get();

        return ReviewResource::collection($review);
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

        $categories = FoodCommonCategory::where('restaurant_id', NULL)->orWhere('restaurant_id', $restaurant->id)->get();

        return $this->success(['menu' => $menu, 'categories' => new FoodCommonCategoryCollection($categories)]);
    }

    public function restaurantCategories($id)
    {
        $restaurant = Restaurant::where('uuid', $id)->first();

        $categories = FoodCommonCategory::where('restaurant_id', $restaurant->id)->paginate(8);

        return $this->success(['categories' => $categories]);
    }

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

    public function logs(Request $request)
    {
        $search = $request->query('search');

        $logs = Activity::with('causer', 'subject')
                                ->paginate(10);

        return $this->success($logs);
    }

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

    public function updateDeliveryRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rate' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error('', 'Rate is required', 400);
        }

        $setting = Setting::where('name', 'Delivery Rate')->first();

        $setting->update([
            'variable' => $request->rate
        ]);

        return $this->success('', 'Rate updated successfully');
    }

    public function updateBaseRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rate' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error('', 'Rate is required', 400);
        }

        $setting = Setting::where('name', 'Base Service Charge Rate')->first();

        $setting->update([
            'variable' => $request->rate
        ]);

        return $this->success('', 'Rate updated successfully');
    }

    public function updateGroceryBaseRate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rate' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error('', 'Rate is required', 400);
        }

        $setting = Setting::where('name', 'Base Groceries Service Charge Rate')->first();

        $setting->update([
            'variable' => $request->rate
        ]);

        return $this->success('', 'Rate updated successfully');
    }

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

    public function ridersPayouts(Request $request)
    {
        $search = $request->query('search');

        $payouts = Payout::with('payable')->where('payable_type', User::class)->latest()->paginate(10);

        return $this->success(['payouts' => $payouts]);
    }

    public function partnersPayouts(Request $request)
    {
        $search = $request->query('search');

        $payouts = Payout::with('payable')->where('payable_type', Restaurant::class)->latest()->paginate(10);

        return $this->success(['payouts' => $payouts]);
    }

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

    public function permissions(Request $request)
    {
        $permissions = PermissionGroup::where('type', 'admin')->with('permissions')->get();

        return $this->success($permissions);
    }

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

    public function admins(Request $request)
    {
        $users = User::whereNotIn('email', ['admin@moboeats.com', 'admin@moboeats.co.uk'])
        ->with('roles')->whereHas('roles', function ($query) {
            $query->whereNotIn('name', ['orderer', 'restaurant', 'restaurant employee', 'rider']);
        })
        ->paginate($request->query('per_page'));

        $roles = Role::whereNotIn('name', ['orderer', 'restaurant', 'restaurant employee', 'rider'])->get();

        return $this->success(['users' => $users, 'roles' => $roles]);
    }

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

        return $this->success($user);
    }
}

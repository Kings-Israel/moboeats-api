<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\FoodCommonCategoryCollection;
use App\Http\Resources\V1\RiderResource;
use App\Http\Resources\V1\UserResource;
use App\Models\FoodCommonCategory;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\Rider;
use App\Models\User;
use App\Traits\HttpResponses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

            if (!$user->hasRole('admin')) {
                return $this->error(['email' => 'You do not have permission to login.'], 'You do not have permission to login.', 401);
            }

            $token = $user->createToken($request->email);

            $user = new UserResource($user);

            return $this->success([
                'user' => $user,
                'token' => $token->plainTextToken,

            ]);
        } catch (\Throwable $th) {
            info($th->getMessage());
            return $this->error('', $th->getMessage(), 403);
        }
    }

    public function dashboard()
    {
        $users = User::whereHasRole('orderer')->count();
        $restaurants = User::whereHasRole('restaurant')->count();
        $riders = User::whereHasRole('rider')->count();
        $orders = Order::where('status', 2)->count();

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
            array_push($months_formatted, Carbon::parse($month)->format('M'));
        }
        // Orders
        $total_monthly_orders = [];
        $total_orders = 0;

        $index = 0;
        foreach ($months as $month) {
            $order = Order::where('status', 2)->whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->count();
            array_push($total_monthly_orders, $order);
            $total_orders += $order;
            $index++;
        }

        // Get current months earning
        $current_month_orders = Order::where('status', 2)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();

        // Previous months orders
        $prev_month = now()->subMonths(1);
        $prev_month_orders = Order::where('status', 2)->whereBetween('created_at', [Carbon::parse($prev_month)->startOfMonth(), Carbon::parse($prev_month)->endOfMonth()])->count();

        // Compare current months orders to previous month
        if ($prev_month_orders != 0) {
            $orders_difference = ($current_month_orders / $prev_month_orders) * 100;
        } else {
            $orders_difference = $current_month_orders / 100;
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
            $order = Payment::whereBetween('created_at', [Carbon::parse($month)->startOfMonth(), Carbon::parse($month)->endOfMonth()])->count();
            array_push($total_monthly_payments, $order);
            $total_payments += $order;
            $index++;
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

        // // Top Menu Items
        // $top_menu_items = Menu::withCount('orderItems')->with('restaurant')->orders->orderBy('order_items_count', 'DESC')->get()->take(5);

        // $top_menu_items_series = [];
        // $top_menu_items_names = [];
        // $total_orders_count = 0;
        // foreach($top_menu_items as $item) {
        //     $total_orders_count += $item->order_items_count;
        // }

        // foreach ($top_menu_items as $key => $item) {
        //     array_push($top_menu_items_names, $item->title);
        //     array_push($top_menu_items_series, ceil(($item->order_items_count / $total_orders_count) * 100));
        // }

        return $this->success([
            'users' => $users,
            'restaurants' => $restaurants,
            'riders' => $riders,
            'orders' => $orders,
            'orders_series' => $orders_made_monthly,
            'payments_series' => $payments_made_monthly,
            'top_restaurants' => $top_restaurants,
        ]);
    }

    public function categories()
    {
        $categories = FoodCommonCategory::paginate(7);

        return $this->success($categories);
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

        return $this->success(['user' => $user, 'deliveries' => $deliveries, 'rider_profile' => $rider_profile]);
    }

    public function restaurants(Request $request)
    {
        $search = $request->query('search');

        $restaurants = Restaurant::with('user', 'orders', 'menus')
                                ->when($search && $search != '', function($query) use ($search) {
                                    $query->where('name', 'LIKE', '%'.$search.'%')
                                        ->orWhereHas('user', function ($query) use ($search) {
                                            $query->where('name', 'LIKE', '%'.$search.'%');
                                        });
                                })
                                ->orderBy('created_at', 'DESC')
                                ->paginate(10);

        return $this->success($restaurants);
    }

    public function restaurant($id)
    {
        $restaurant = Restaurant::with('users', 'orders.payments', 'orders.users', 'menus')->where('id', $id);

        return $this->success($restaurant);
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

    public function payments(Request $request)
    {
        $search = $request->query('search');

        $payments = Payment::with('order.user', 'order.restaurant')
                            ->where('transaction_id', '!=', NULL)
                            ->when($search && $search != null, function ($query) use ($search) {
                                $query->whereHas('order', function ($query) use ($search) {
                                    $query->whereHas('user', function ($query) use ($search) {
                                        $query->where('name', 'LIKE', '%'.$search.'%')->orWhere('email', 'LIKE', '%'.$search.'%');
                                    });
                                })
                                ->orWhereHas('order', function ($query) use ($search) {
                                    $query->whereHas('restaurant', function ($query) use ($search) {
                                        $query->where('amount', 'LIKE', '%'.$search.'%')->orWhere('about', 'LIKE', '%'.$search.'%');
                                    });
                                });
                            })
                            ->orderBy('created_at', 'DESC')
                            ->paginate(10);

        return $this->success($payments);
    }
}

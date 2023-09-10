<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\User;
use App\Traits\HttpResponses;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use HttpResponses;

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

        return $this->success([
            'users' => $users,
            'restaurants' => $restaurants,
            'riders' => $riders,
            'orders' => $orders,
            'orders_series' => $orders_made_monthly,
            'payments_series' => $payments_made_monthly,
        ]);
    }

    public function users(Request $request)
    {
        $search = $request->query('search');
        $per_page = $request->query('per_page');

        $orders = User::with('role')
                        ->when($search && $search != '', function($query) use ($search) {
                            $query->where('name', 'LIKE', '%'.$search.'%')
                                    ->orWhere('email', 'LIKE', '%'.$search.'%')
                                    ->orWhere('phone_number', 'LIKE', '%'.$search.'%');
                        })
                        ->paginate($per_page);

        return $this->success($orders);
    }

    public function restaurants(Request $request)
    {
        $search = $request->query('search');
        $per_page = $request->query('per_page');

        $orders = Restaurant::with('user')
                                ->when($search && $search != '', function($query) use ($search) {
                                    $query->whereHas('user', function ($query) use ($search) {
                                        $query->where('name', 'LIKE', '%'.$search.'%');
                                    });
                                })
                                ->paginate($per_page);

        return $this->success($orders);
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
                        ->paginate($per_page);

        return $this->success($orders);
    }

    public function payments(Request $request)
    {
        $search = $request->query('search');
        $per_page = $request->query('per_page');

        $payments = Payment::with('user', 'order.restaurant')
                        ->when($search && $search != '', function($query) use ($search) {
                            $query->whereHas('user', function ($query) use ($search) {
                                $query->where('name', 'LIKE', '%'.$search.'%');
                            });
                        })
                        ->paginate($per_page);

        return $this->success($payments);
    }
}

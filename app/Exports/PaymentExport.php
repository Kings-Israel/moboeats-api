<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\UserRestaurant;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Str;

class PaymentExport implements FromCollection, WithMapping, WithHeadings
{
    public $var;
    public $from;
    public $to;

    public function __construct(string $var = null, string $from = null, string $to = null)
    {
        $this->var = $var;
        $this->from = $from;
        $this->to = $to;
    }

    public function map($payment): array
    {
        return [
            Str::upper(explode('-', $payment->order->uuid)[0]),
            $payment->transaction_id,
            $payment->order->user->name,
            $payment->order->restaurant->name,
            $payment->amount,
            $payment->created_at->format('d M Y')
        ];
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Transaction ID',
            'User',
            'Restaurant',
            'Amount',
            'Paid On'
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        if (auth()->user()->hasRole('restaurant') || auth()->user()->hasRole('restaurant employee')) {
            if (auth()->user()->hasRole('restaurant')) {
                $restaurant_ids = auth()->user()->restaurants->pluck('id');
            } else {
                $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
                $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
            }

            $orders_ids = Order::whereIn('restaurant_id', $restaurant_ids)
                            ->get()
                            ->pluck('id');

            return Payment::with('order.user', 'order.restaurant')
                            ->whereIn('order_id', $orders_ids)
                            ->where('status', '2')
                            ->when($this->from && $this->from != '' && $this->from != null, function ($query) {
                                $query->whereDate('created_at', '>=', Carbon::parse($this->from));
                            })
                            ->when($this->to && $this->to != '' && $this->to != null, function ($query) {
                                $query->whereDate('created_at', '<=', Carbon::parse($this->to));
                            })
                            ->when($this->var && $this->var != '', function ($query) {
                                $query->where(function($query) {
                                    $query->where('transaction_id', 'LIKE', '%' . $this->var . '%')
                                        ->orWhereHas('order', function ($query) {
                                            $query->where('uuid', 'LIKE', '%' . $this->var . '%')
                                                ->whereHas('restaurant', function ($query) {
                                                    $query->where('name', 'LIKE', '%' . $this->var . '%')->orWhere('name_short', 'LIKE', '%' . $this->var . '%');
                                                })
                                                ->orWhereHas('user', function ($query) {
                                                    $query->where('name', 'LIKE', '%' . $this->var . '%');
                                                });
                                    });
                                });
                            })
                            ->orderBy('created_at', 'DESC')
                            ->get();
        } else {
            return Payment::with('order.user', 'order.restaurant')
                            ->where('status', '2')
                            ->when($this->from && $this->from != '' && $this->from != null, function ($query) {
                                $query->whereDate('created_at', '>=', Carbon::parse($this->from));
                            })
                            ->when($this->to && $this->to != '' && $this->to != null, function ($query) {
                                $query->whereDate('created_at', '<=', Carbon::parse($this->to));
                            })
                            ->when($this->var && $this->var != '', function ($query) {
                                $query->where(function($query) {
                                    $query->where('transaction_id', 'LIKE', '%' . $this->var . '%')
                                        ->orWhereHas('order', function ($query) {
                                            $query->where('uuid', 'LIKE', '%' . $this->var . '%')
                                                ->whereHas('restaurant', function ($query) {
                                                    $query->where('name', 'LIKE', '%' . $this->var . '%')->orWhere('name_short', 'LIKE', '%' . $this->var . '%');
                                                })
                                                ->orWhereHas('user', function ($query) {
                                                    $query->where('name', 'LIKE', '%' . $this->var . '%');
                                                });
                                    });
                                });
                            })
                            ->orderBy('created_at', 'DESC')
                            ->get();
        }
    }
}

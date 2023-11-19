<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\UserRestaurant;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Str;

class OrderExport implements FromCollection, WithMapping, WithHeadings
{
    use Exportable;

    public $var;
    public $from;
    public $to;

    public function __construct(string $var = null, string $from = null, string $to = null) {
        $this->var = $var;
        $this->from = $from;
        $this->to = $to;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        if (auth()->user()->hasRole('restaurant') || auth()->user()->hasRole('restaurant employee')) {
            if (auth()->user()->hasRole('restuarant employee')) {
                $restaurant_ids = auth()->user()->restaurants->pluck('id');
            } else {
                $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
                $restaurant_ids = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
            }

            return Order::whereIn('restaurant_id', $restaurant_ids)
                        ->when($this->var && $this->var != '' && $this->var != null, function ($query) {
                            $query->where(function ($query) {
                                $query->orWhere('uuid', 'LIKE', '%' . strtolower($this->var) . '%')
                                        ->orWhereHas('user', function ($query) {
                                            $query->where('name', 'LIKE', '%' . $this->var . '%');
                                        })
                                        ->orWhereHas('restaurant', function ($query) {
                                            $query->where('name', 'LIKE', '%' . $this->var . '%')
                                                    ->orWhere('name_short', 'LIKE', '%' . $this->var . '%');
                                        });
                            });
                        })
                        ->when($this->from && $this->from != '' && $this->from != null, function ($query) {
                            $query->whereDate('created_at', '>=', Carbon::parse($this->from));
                        })
                        ->when($this->to && $this->to != '' && $this->to != null, function ($query) {
                            $query->whereDate('created_at', '<=', Carbon::parse($this->to));
                        })
                        ->get();
        } else {
            return Order::when($this->var && $this->var != '' && $this->var != null, function ($query) {
                            $query->where(function ($query) {
                                $query->orWhere('uuid', 'LIKE', '%' . strtolower($this->var) . '%')
                                        ->orWhereHas('user', function ($query) {
                                            $query->where('name', 'LIKE', '%' . $this->var . '%');
                                        })
                                        ->orWhereHas('restaurant', function ($query) {
                                            $query->where('name', 'LIKE', '%' . $this->var . '%')
                                                    ->orWhere('name_short', 'LIKE', '%' . $this->var . '%');
                                        });
                            });
                        })
                        ->when($this->from && $this->from != '' && $this->from != null, function ($query) {
                            $query->whereDate('created_at', '>=', Carbon::parse($this->from));
                        })
                        ->when($this->to && $this->to != '' && $this->to != null, function ($query) {
                            $query->whereDate('created_at', '<=', Carbon::parse($this->to));
                        })
                        ->get();
        }
    }

    public function map($order): array
    {
        return  [
            Str::upper(explode('-',$order->uuid)[0]),
            $order->delivery ? 'Delivery' : 'Dine In',
            $order->user->name,
            $order->restaurant->name,
            $order->total_amount,
            Str::title($order->status),
            $order->delivery_address,
            Carbon::parse($order->created_at)->format('d M Y H:i a')
        ];
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Type',
            'User',
            'Restaurant',
            'Amount',
            'Status',
            'Delivery Location',
            'Ordererd On'
        ];
    }
}

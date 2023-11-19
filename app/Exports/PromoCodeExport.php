<?php

namespace App\Exports;

use App\Models\PromoCode;
use App\Models\Restaurant;
use App\Models\UserRestaurant;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Str;

class PromoCodeExport implements FromCollection, WithHeadings, WithMapping
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
    public function map($promo_code): array
    {
        return  [
            Str::upper($promo_code->code),
            $promo_code->restaurant->name,
            Carbon::parse($promo_code->start_date)->format('d M Y'),
            Carbon::parse($promo_code->end_date)->format('d M Y'),
            Str::title($promo_code->status),
            $promo_code->value,
            Str::title($promo_code->type),
            Carbon::parse($promo_code->created_at)->format('d M Y')
        ];
    }

    public function headings(): array
    {
        return [
            'Code',
            'Resturant',
            'Starts On',
            'Ends On',
            'Status',
            'Amount',
            'Type',
            'Created On',
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        if (auth()->user()->hasRole('restaurant') || auth()->user()->hasRole('restaurant employee')) {
            if (auth()->user()->hasRole('restaurant')) {
                $restaurants_ids = auth()->user()->restaurants->pluck('id');
            } else {
                $restaurants_ids = UserRestaurant::where('user_id', auth()->id())->first()->pluck('restaurant_id');
            }

            return PromoCode::with('restaurant')
                                ->whereIn('restaurant_id', $restaurants_ids)
                                ->when($this->from && $this->from != '', function ($query) {
                                    $query->where(function ($query) {
                                        $query->whereDate('created_at', '>=', Carbon::parse($this->from))
                                                ->orWhereDate('start_date', '>=', Carbon::parse($this->from))
                                                ->orWhereDate('end_date', '>=', Carbon::parse($this->from));
                                    });
                                })
                                ->when($this->to && $this->to != '', function ($query) {
                                    $query->where(function ($query) {
                                        $query->whereDate('created_at', '<=', Carbon::parse($this->to))
                                                ->orWhereDate('start_date', '<=', Carbon::parse($this->to))
                                                ->orWhereDate('end_date', '<=', Carbon::parse($this->to));
                                    });
                                })
                                ->when($this->var && $this->var != '', function ($query) {
                                    $query->where(function ($query) {
                                        $query->where('code', 'LIKE', '%'.$this->var.'%')
                                            ->orWhereHas('restaurant', function ($query) {
                                                $query->where('name', 'LIKE', '%'.$this->var.'%');
                                            });
                                    });
                                })
                                ->get();
        } else {
            return PromoCode::with('restaurant')
                            ->when($this->from && $this->from != '', function ($query) {
                                $query->where(function ($query) {
                                    $query->whereDate('created_at', '>=', Carbon::parse($this->from))
                                            ->orWhereDate('start_date', '>=', Carbon::parse($this->from))
                                            ->orWhereDate('end_date', '>=', Carbon::parse($this->from));
                                });
                            })
                            ->when($this->to && $this->to != '', function ($query) {
                                $query->where(function ($query) {
                                    $query->whereDate('created_at', '<=', Carbon::parse($this->to))
                                            ->orWhereDate('start_date', '<=', Carbon::parse($this->to))
                                            ->orWhereDate('end_date', '<=', Carbon::parse($this->to));
                                });
                            })
                            ->when($this->var && $this->var != '', function ($query) {
                                $query->where(function ($query) {
                                    $query->where('code', 'LIKE', '%'.$this->var.'%')
                                        ->orWhereHas('restaurant', function ($query) {
                                            $query->where('name', 'LIKE', '%'.$this->var.'%');
                                        });
                                });
                            })
                            ->get();
        }
    }
}

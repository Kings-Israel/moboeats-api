<?php

namespace App\Exports;

use App\Models\Restaurant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RestaurantExport implements FromCollection, WithMapping, WithHeadings
{
    public $var;

    public function __construct(string $var = null)
    {
        $this->var = $var;
    }
    public function map($restaurant): array
    {
        return  [
            $restaurant->name,
            $restaurant->orders->count(),
            $restaurant->menus->count(),
            $restaurant->address,
            $restaurant->created_at->format('d M Y')
        ];
    }

    public function headings(): array
    {
        return [
            "Name",
            "No. of Orders",
            "No. Of Menu Items",
            "Location",
            "Created On"
        ];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        if (auth()->check() && auth()->user()->hasRole('restaurant')) {

            $restaurant_ids = auth()->user()->restaurants->pluck('id');

            return Restaurant::whereIn('restaurant_id', $restaurant_ids)->when($this->var && $this->var != '' && $this->var != null, function ($query) {
                                $query->where(function ($query) {
                                    $query->where('name', 'LIKE', '%'.$this->var.'%')
                                            ->orWhere('address', 'LIKE', '%'.$this->var.'%');
                                });
                            })
                            ->get();
        } else {
            return Restaurant::when($this->var && $this->var != '' && $this->var != null, function ($query) {
                                $query->where(function ($query) {
                                    $query->where('name', 'LIKE', '%'.$this->var.'%')
                                            ->orWhere('address', 'LIKE', '%'.$this->var.'%');
                                });
                            })
                            ->get();
        }
    }
}

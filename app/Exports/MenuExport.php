<?php

namespace App\Exports;

use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\UserRestaurant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Str;

class MenuExport implements FromCollection, WithMapping, WithHeadings
{
    public $var;

    public function __construct(string $var = null)
    {
        $this->var = $var;
    }

    public function map($menu): array
    {
        return [
            $menu->title,
            $menu->restaurant->name,
            $menu->status == 2 ? 'Active' : 'Inactive',
            $menu->orderItems->count(),
            $menu->created_at->format('d M Y')
        ];
    }

    public function headings(): array
    {
        return [
            'Title',
            'Restaurant',
            'Status',
            'No. of Orders',
            'Created On'
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

            return Menu::whereIn('restaurant_id', $restaurant_ids)
                        ->when($this->var && $this->var != '' && $this->var != null, function ($query) {
                            $query->where(function ($query) {
                                $query->where('title', 'LIKE', '%'.$this->var.'%')
                                    ->orWhereHas('restaurant', function ($query) {
                                        $query->where('name', 'LIKE', '%'.$this->var.'%');
                                    });
                            });
                        })
                        ->get();
        } else {
            return Menu::when($this->var && $this->var != '' && $this->var != null, function ($query) {
                            $query->where(function ($query) {
                                $query->where('title', 'LIKE', '%'.$this->var.'%')
                                    ->orWhereHas('restaurant', function ($query) {
                                        $query->where('name', 'LIKE', '%'.$this->var.'%');
                                    });
                            });
                        })
                        ->get();
        }
    }
}

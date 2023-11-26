<?php

namespace App\Exports;

use App\Models\Restaurant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RestaurantExport implements FromCollection, WithMapping, WithHeadings
{
    public $var;
    public $status;

    public function __construct(string $var = null, int $status = null)
    {
        $this->var = $var;
        $this->status = $status;
    }
    public function map($restaurant): array
    {
        if (auth()->check() && auth()->user()->hasRole('restaurant')) {
            return  [
                $restaurant->name,
                $restaurant->orders->count(),
                $restaurant->menus->count(),
                $restaurant->address,
                $restaurant->status,
                $restaurant->created_at->format('d M Y')
            ];
        } else {
            return  [
                $restaurant->name,
                $restaurant->user->name,
                $restaurant->user->email,
                $restaurant->user->phone_number,
                $restaurant->orders->count(),
                $restaurant->menus->count(),
                $restaurant->status,
                $restaurant->address,
                $restaurant->latitude,
                $restaurant->longitude,
                $restaurant->created_at->format('d M Y')
            ];
        }
    }

    public function headings(): array
    {
        if (auth()->check() && auth()->user()->hasRole('restaurant')) {
            return [
                "Name",
                "No. of Orders",
                "No. Of Menu Items",
                "Location",
                "Status",
                "Created On"
            ];
        } else {
            return [
                "Name",
                "Admin Name",
                "Admin Email",
                "Admin Phone Number",
                "No. of Orders",
                "No. Of Menu Items",
                "Status",
                "Location",
                "Location Latitude",
                "Location Longitude",
                "Created On",
            ];
        }
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        if (auth()->check() && auth()->user()->hasRole('restaurant')) {

            $restaurant_ids = auth()->user()->restaurants->pluck('id');

            return Restaurant::whereIn('id', $restaurant_ids)
                            ->when($this->var && $this->var != '' && $this->var != null, function ($query) {
                                $query->where(function ($query) {
                                    $query->where('name', 'LIKE', '%'.$this->var.'%')
                                            ->orWhere('address', 'LIKE', '%'.$this->var.'%');
                                });
                            })
                            ->when($this->status && $this->status != '' && $this->status != null, function ($query) {
                                $query->where(function ($query) {
                                    $query->where('status', $this->status);
                                });
                            })
                            ->get();
        } else {
            return Restaurant::with('user', 'orders', 'menus')
                            ->when($this->var && $this->var != '' && $this->var != null, function ($query) {
                                $query->where(function ($query) {
                                    $query->where('name', 'LIKE', '%'.$this->var.'%')
                                            ->orWhere('address', 'LIKE', '%'.$this->var.'%');
                                });
                            })
                            ->when($this->status && $this->status != '' && $this->status != null, function ($query) {
                                $query->where(function ($query) {
                                    $query->where('status', $this->status);
                                });
                            })
                            ->get();
        }
    }
}

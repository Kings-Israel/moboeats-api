<?php

namespace App\Exports;

use App\Models\CategoryMenu;
use App\Models\FoodCommonCategory;
use App\Models\Menu;
use App\Models\Restaurant;
use App\Models\UserRestaurant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GroceryExport implements FromCollection, WithMapping, WithHeadings
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
                $restaurants = auth()->user()->restaurants->pluck('id');
            } else {
                $user_restaurant = UserRestaurant::where('user_id', auth()->id())->first();
                $restaurants = Restaurant::where('id', $user_restaurant->restaurant_id)->get()->pluck('id');
            }

            $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

            $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

            return Menu::with('images', 'categories', 'subCategories', 'restaurant')->whereIn('restaurant_id', $restaurants)->whereIn('id', $category_menus)->get();
        } else {
            $category = FoodCommonCategory::with('menus')->where('title', 'groceries')->first();

            $category_menus = CategoryMenu::where('category_id', $category->id)->get()->pluck('menu_id');

            return Menu::with('images', 'categories', 'subCategories', 'restaurant')->whereIn('id', $category_menus)->get();
        }
    }
}

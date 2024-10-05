<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuPrice;
use App\Models\Questionnaire;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Str;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Laratrust\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(RolesSeeder::class);
        $this->call(FoodCommonCategorySeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(SeatingAreaSeeder::class);
        $user = User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->addRole(Role::where('name', 'orderer')->first());

        $restaurant = User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Restaurant',
            'email' => 'test@restaurant.com',
        ]);

        $restaurant->addRole(Role::where('name', 'restaurant')->first());

        Auth::login($restaurant);

        $res = Restaurant::factory()
        ->count(10) // Generate 10 restaurants
        ->create()
        ->each(function ($restaurant) {
            // For each restaurant, generate a questionnaire
            $restaurant->questionnaire()->save(Questionnaire::factory()->make());
        });

        $menus = [
            ['title' => 'Chicken wings', 'description' => 'chicken wings'],
            ['title' => 'Chicken wings2', 'description' => 'chicken wings2'],
            ['title' => 'Chicken wings4', 'description' => 'chicken wings4'],
            ['title' => 'Chicken wings6', 'description' => 'Chicken wings6'],
            ['title' => 'Chicken wings7', 'description' => 'Chicken wings7'],
            ['title' => 'Chicken wings8', 'description' => 'Chicken wings8'],
            ['title' => 'Chicken wings9', 'description' => 'Chicken wings9'],
            ['title' => 'Canned Tuna', 'description' => 'Seafood is packed with protein, helping you feel full and satisfied'],
        ];

        $menu_prices = [
            ['standard', 105, 2, 'info@moboeats.com'],
            ['standard', 350, 2, 'info@moboeats.com'],
            ['standard', 500, 2, 'info@moboeats.com'],
        ];

        collect($menus)->each(function($menu) use ($menu_prices) {
            $new_menu = Menu::create([
                'uuid' => Str::uuid(),
                'title' => $menu['title'],
                'description' => $menu['description'],
                'restaurant_id' => Restaurant::first()->id,
                'status' => 2,
                'created_by' => 'info@moboeats.com',
                'updated_by' => 'info@moboeats.com',
            ]);
            $menu_price_index = rand(0, 2);
            MenuPrice::create([
                'uuid' => Str::uuid(),
                'menu_id' => $new_menu->id,
                'description' => $menu_prices[$menu_price_index][0],
                'price' => $menu_prices[$menu_price_index][1],
                'status' => $menu_prices[$menu_price_index][2],
                'created_by' => $menu_prices[$menu_price_index][3],
            ]);
        });

        Auth::logout();

        $rider = User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Rider',
            'email' => 'test@rider.com',
        ]);

        $rider->addRole(Role::where('name', 'rider')->first());
    }
}

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
        $this->call(PermissionGroupSeeder::class);
        $this->call(PermissionGroupingSeeder::class);
        $this->call(FoodCommonCategorySeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(SeatingAreaSeeder::class);
        $user = User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->addRole(Role::where('name', 'orderer')->first());

        $restaurantAdmin = User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Java House',
            'email' => 'admin@javahouse.com',
        ]);

        $restaurantAdmin->addRole(Role::where('name', 'restaurant')->first());

        Auth::login($restaurantAdmin);

        $menus = [
            ['title' => 'Loaded Full Java Breakfast', 'description' => 'Enjoy a hearty breakfast with eggs, sausage, bacon, and toast.'],
            ['title' => 'Java Pancakes', 'description' => 'Fluffy pancakes served with syrup and butter.'],
            ['title' => 'Java Omelette', 'description' => 'A delicious omelette filled with your choice of vegetables and cheese.'],
        ];

        $menu_prices = [
            ['standard', 105, 2, 'info@javahouse.com'],
            ['standard', 350, 2, 'info@javahouse.com'],
            ['standard', 500, 2, 'info@javahouse.com'],
        ];

        Restaurant::factory()
            ->create([
                'name' => 'Java House',
                'status' => 2,
            ])
            ->each(function ($restaurant) use ($menus, $menu_prices) {
                // For each restaurant, generate a questionnaire
                $restaurant->questionnaire()->save(Questionnaire::factory()->make());

                collect($menus)->each(function($menu) use ($menu_prices, $restaurant) {
                    $new_menu = Menu::create([
                        'uuid' => Str::uuid(),
                        'title' => $menu['title'],
                        'description' => $menu['description'],
                        'restaurant_id' => $restaurant->id,
                        'status' => 2,
                        'created_by' => 'info@javahouse.com',
                        'updated_by' => 'info@javahouse.com',
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

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laratrust\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super',
                'display_name' => 'Super User',
                'description' => NULL,
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => NULL,
            ],
            [
                'name' => 'restaurant',
                'display_name' => 'Restaurant',
                'description' => 'restaurant saas user',
            ],
            [
                'name' => 'orderer',
                'display_name' => 'Orderer',
                'description' => 'access restaurants, menus and make orders',
            ],
            [
                'name' => 'rider',
                'display_name' => 'Rider',
                'description' => 'rider access requests for delivery, can be customer too'
            ],
        ];

        collect($roles)->each(function ($role) {
            Role::create($role);
        });

    }
}

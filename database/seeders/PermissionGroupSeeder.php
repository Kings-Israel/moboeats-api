<?php

namespace Database\Seeders;

use App\Models\PermissionGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            [
                'name' => 'menu',
            ],
            [
                'name' => 'categories',
            ],
            [
                'name' => 'groceries categories',
            ],
            [
                'name' => 'rates',
            ],
            [
                'name' => 'customers',
            ],
            [
                'name' => 'partners',
            ],
            [
                'name' => 'riders',
            ],
            [
                'name' => 'orders',
            ],
            [
                'name' => 'payments',
            ],
            [
                'name' => 'payouts',
            ],
            [
                'name' => 'diet and diet planning',
            ],
            [
                'name' => 'supplements',
            ],
            [
                'name' => 'discounts',
            ],
            [
                'name' => 'logs',
            ],
            [
                'name' => 'marketing',
            ],
            [
                'name' => 'frequently asked questions',
            ],
            [
                'name' => 'messages',
            ],
            [
                'name' => 'orphanages',
            ],
        ];

        collect($groups)->each(fn ($group) => PermissionGroup::create($group));
    }
}

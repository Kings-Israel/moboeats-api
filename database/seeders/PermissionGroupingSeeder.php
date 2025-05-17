<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\PermissionGrouping;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionGroupingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'menu' => [
                'view menus'
            ],
            'categories' => [
                'view categories',
                'add/edit categories'
            ],
            'groceries categories' => [
                'view groceries categories',
                'add/edit groceries categories'
            ],
            'rates' => [
                'edit rates'
            ],
            'customers' => [
                'view customers',
                'edit customers'
            ],
            'partners' => [
                'view partners',
                'edit partners'
            ],
            'riders' => [
                'view riders',
                'edit riders'
            ],
            'orders' => [
                'view orders',
                'edit orders'
            ],
            'payments' => [
                'view payments',
            ],
            'payouts' => [
                'view riders payouts',
                'view partners payouts',
                'upload payouts'
            ],
            'diet and diet planning' => [
                'view diet plan subscriptions',
                'view diet plan packages',
                'add/edit diet plan packages'
            ],
            'supplements' => [
                'view supplements',
                'add/edit supplements',
                'view suppliers',
                'add/edit suppliers',
                'view supplements orders',
                'edit supplements orders',
            ],
            'discounts' => [
                'view discounts'
            ],
            'logs' => [
                'view logs',
            ],
            'marketing' => [
                'view marketing posters',
                'add/edit marketing posters'
            ],
            'frequently asked questions' => [
                'view frequently asked questions',
                'add/edit frequently asked questions'
            ],
            'messages' => [
                'view messages',
            ],
            'orphanages' => [
                'create orphanages',
                'view orphanages',
                'edit orphanages',
                'view orphanages orders',
                'edit orphanages orders',
            ],
            'countries' => [
                'create countries',
                'view countries',
                'edit countries',
            ],
        ];

        try {
            DB::beginTransaction();

            foreach ($data as $key => $value) {
                $permission_group = PermissionGroup::where('name', $key)->first()->id;
                foreach ($value as $permission) {
                    $permission = Permission::where('name', $permission)->first()->id;
                    PermissionGrouping::create([
                        'permission_group_id' => $permission_group,
                        'permission_id' => $permission
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            info($th);
            DB::rollBack();
        }
    }
}

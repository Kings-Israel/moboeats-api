<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                "name" => "view menus",
                "display_name" => "View Menus",
            ],
            [
                "name" => "view categories",
                "display_name" => "View Categories",
            ],
            [
                "name" => "add/edit categories",
                "display_name" => "Add/Edit Categories",
            ],
            [
                "name" => "view groceries categories",
                "display_name" => "View Grocieries Categories",
            ],
            [
                "name" => "add/edit groceries categories",
                "display_name" => "Add/Edit Grocieries Categories",
            ],
            [
                "name" => "edit rates",
                "display_name" => "Edit Rates",
            ],
            [
                "name" => "view customers",
                "display_name" => "View Customers",
            ],
            [
                "name" => "edit customers",
                "display_name" => "Edit Customers",
            ],
            [
                "name" => "view riders",
                "display_name" => "View Riders",
            ],
            [
                "name" => "edit riders",
                "display_name" => "Edit Riders",
            ],
            [
                "name" => "view partners",
                "display_name" => "View Partners",
            ],
            [
                "name" => "edit partners",
                "display_name" => "Edit Partners",
            ],
            [
                "name" => "view orders",
                "display_name" => "View Orders",
            ],
            [
                "name" => "edit orders",
                "display_name" => "Edit Orders",
            ],
            [
                "name" => "view payments",
                "display_name" => "View Payments",
            ],
            [
                "name" => "view riders payouts",
                "display_name" => "View Riders Payouts",
            ],
            [
                "name" => "view partners payouts",
                "display_name" => "View Partner Payouts",
            ],
            [
                "name" => "upload payouts",
                "display_name" => "Upload Payouts",
            ],
            [
                "name" => "view diet plan subscriptions",
                "display_name" => "View Diet Plan Subscriptions",
            ],
            [
                "name" => "view diet plan packages",
                "display_name" => "View Diet Plan Packages",
            ],
            [
                "name" => "add/edit diet plan packages",
                "display_name" => "Add/Edit Diet Plan Packages",
            ],
            [
                "name" => "view supplements",
                "display_name" => "View Supplements",
            ],
            [
                "name" => "add/edit supplements",
                "display_name" => "Add/Edit Supplements",
            ],
            [
                "name" => "view suppliers",
                "display_name" => "View Suppliers",
            ],
            [
                "name" => "add/edit suppliers",
                "display_name" => "Add/Edit Suppliers",
            ],
            [
                "name" => "view supplements orders",
                "display_name" => "View Supplements Orders",
            ],
            [
                "name" => "edit supplements orders",
                "display_name" => "Edit Supplements Orders",
            ],
            [
                "name" => "view discounts",
                "display_name" => "View Discounts",
            ],
            [
                "name" => "view logs",
                "display_name" => "View Logs",
            ],
            [
                "name" => "view marketing posters",
                "display_name" => "View Marketing Posters",
            ],
            [
                "name" => "add/edit marketing posters",
                "display_name" => "Add/Edit Marketing Posters",
            ],
            [
                "name" => "view frequently asked questions",
                "display_name" => "View Frequently Asked Questions",
            ],
            [
                "name" => "add/edit frequently asked questions",
                "display_name" => "Add/Edit Frequently Asked Questions",
            ],
            [
                "name" => "view messages",
                "display_name" => "View Messages",
            ],
            [
                'name' => 'create orphanages',
                'display_name' => "Create Orphanages",
            ],
            [
                "name" => 'view orphanages',
                "display_name" => "View Orphanages",
            ],
            [
                "name" => 'edit orphanages',
                "display_name" => "Edit Orphanages",
            ],
            [
                "name" => 'view orphanages orders',
                "display_name" => "View Orphanages Orders",
            ],
            [
                "name" => 'edit orphanages orders',
                "display_name" => "Edit Orphanages Orders"
            ]
        ];

        collect($permissions)->each(function ($permission) {
            Permission::create($permission);
        });
    }
}

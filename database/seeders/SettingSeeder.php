<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::create([
            'name' => 'Delivery Rate',
            'description' => 'Rate of product delivery in gbp per mile',
            'variable' => 3
        ]);

        Setting::create([
            'name' => 'Base Service Charge Rate',
            'description' => 'Base rate charges on partners per order in percentage',
            'variable' => 15,
            'type' => 'percentage',
        ]);

        Setting::create([
            'name' => 'Base Groceries Service Charge Rate',
            'description' => 'Base rate charges on partners per order in percentage',
            'variable' => 12,
            'type' => 'percentage',
        ]);
    }
}

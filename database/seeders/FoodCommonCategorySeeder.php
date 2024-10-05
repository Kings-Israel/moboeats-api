<?php

namespace Database\Seeders;

use App\Models\FoodCommonCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FoodCommonCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $common_categories = [
            [
                'uuid' => (string) Str::uuid(),
                'title' => 'Groceries',
                'description' => 'groceries',
                'status' => 2,
                'created_by' => config('app.company.COMPANY_EMAIL'),
                'updated_by' => config('app.company.COMPANY_EMAIL'),
            ]
        ];

        collect($common_categories)->each(fn ($category) => FoodCommonCategory::create($category));
    }
}

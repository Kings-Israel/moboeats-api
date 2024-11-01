<?php

namespace Database\Seeders;

use App\Models\SupplementCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplementCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Vitamins & Minerals',
            'Herbal',
            'Amino acids',
            'Omega-3',
            'Digestive Enzymes',
            'Joint & Bone Health',
            'Antioxidants',
            'Weight Loss',
            'Energy Boosters',
            'Sexual Health',
            'Immune Support',
            'Mental Health',
        ];

        collect($categories)->each(fn ($category) => SupplementCategory::create(['name' => $category]));
    }
}

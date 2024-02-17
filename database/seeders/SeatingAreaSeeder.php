<?php

namespace Database\Seeders;

use App\Models\SeatingArea;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SeatingAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = ['Indoor', 'Outdoor'];

        collect($areas)->each(fn ($area) => SeatingArea::create(['name' => $area]));
    }
}

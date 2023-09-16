<?php

namespace Database\Seeders;

use App\Models\Questionnaire;
use App\Models\Restaurant;
use Illuminate\Support\Str;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Restaurant::factory()
        ->count(10) // Generate 10 restaurants
        ->create()
        ->each(function ($restaurant) {
            // For each restaurant, generate a questionnaire
            $restaurant->questionnaire()->save(Questionnaire::factory()->make());
        });
    }
}

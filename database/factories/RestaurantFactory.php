<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Restaurant;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'uuid' => Str::uuid(),
            'name' => $this->faker->company,
            'name_short' => $this->faker->word,
            'email' => $this->faker->unique()->safeEmail,
            'user_id' => User::all()->random()->id,
            'about' => $this->faker->paragraphs(3, true),
            'about_short' => $this->faker->sentence,
            'phone_no' => $this->faker->phoneNumber,
            'address' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'postal_code' => $this->faker->postcode,
            'map_location' => $this->faker->latitude . ',' . $this->faker->longitude,
            'url' => $this->faker->url,
            // 'logo' => $this->faker->image('public/storage/images', 640, 480, null, false),
            'logo' => '',
            'status' => $this->faker->numberBetween(1, 3),
            'created_by' => $this->faker->email,
            'updated_by' => $this->faker->optional()->email,
        ];
    }
}

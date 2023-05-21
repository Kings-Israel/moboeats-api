<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Restaurant;
use App\Models\Questionnaire;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Questionnaire>
 */
class QuestionnaireFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'restaurant_id' => Restaurant::factory(),
            'delivery' => $this->faker->boolean,
            'booking' => $this->faker->boolean,
            'status' => $this->faker->numberBetween(1, 3),
            'created_by' => $this->faker->email,
            'updated_by' => $this->faker->optional()->email,
        ];
    }
}

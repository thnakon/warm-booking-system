<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomType>
 */
class RoomTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Small Room', 'Medium Room', 'Large Room']),
            'description' => fake()->paragraph(),
            'capacity' => fake()->numberBetween(1, 4),
            'base_price' => fake()->randomElement([1500, 2500, 3500, 4500]),
        ];
    }
}

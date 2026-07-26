<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guest_id' => \App\Models\Guest::factory(),
            'property_id' => \App\Models\Property::factory(),
            'amenity_id' => \App\Models\Amenity::factory(),
            'status' => fake()->randomElement(['pending', 'fulfilled', 'cancelled']),
            'total' => fake()->randomFloat(2, 100, 5000),
        ];
    }
}

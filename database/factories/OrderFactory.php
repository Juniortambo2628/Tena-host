<?php

namespace Database\Factories;

use App\Models\Amenity;
use App\Models\Guest;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guest_id' => Guest::factory(),
            'property_id' => Property::factory(),
            'amenity_id' => Amenity::factory(),
            'status' => fake()->randomElement(['pending', 'fulfilled', 'cancelled']),
            'total' => fake()->randomFloat(2, 100, 5000),
        ];
    }
}

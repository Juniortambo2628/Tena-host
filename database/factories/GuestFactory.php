<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+254'.fake()->numerify('7########'),
            'last_connected' => fake()->dateTimeBetween('-30 days', 'now'),
            'total_visits' => fake()->numberBetween(1, 10),
            'source' => fake()->randomElement(['WiFi', 'Direct', 'Booking.com', 'Airbnb']),
        ];
    }
}

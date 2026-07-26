<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccessPointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'mac_address' => fake()->macAddress(),
            'name' => fake()->randomElement(['Main Router', 'Extension AP', 'Guest Network', 'Pool Area AP']),
            'status' => fake()->randomElement(['online', 'offline']),
            'last_seen' => fake()->dateTimeBetween('-1 hour', 'now'),
            'connected_clients_count' => fake()->numberBetween(0, 25),
        ];
    }
}

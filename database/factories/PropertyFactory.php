<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->host(),
            'name' => fake()->company().' '.fake()->citySuffix(),
            'address' => fake()->address(),
            'wifi_ssid' => fake()->word().'_WiFi',
            'occupancy_threshold' => fake()->numberBetween(10, 50),
            'pms_integration_type' => fake()->randomElement(['Beds24', 'Cloudbeds', 'Hostaway', null]),
            'pms_connection_status' => fake()->randomElement(['connected', 'disconnected']),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['success', 'error', 'warning', 'info']),
            'category' => fake()->randomElement(['system', 'user', 'registration', 'export']),
            'title' => fake()->sentence(3),
            'message' => fake()->paragraph(),
            'data' => null,
            'is_read' => fake()->boolean(30),
            'is_archived' => false,
            'expires_at' => fake()->optional()->dateTimeBetween('+1 week', '+1 month'),
        ];
    }
}

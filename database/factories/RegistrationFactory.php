<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'property_type' => fake()->randomElement(['vacation_rental', 'hotel', 'b&b', 'other']),
            'property_count' => fake()->numberBetween(1, 50),
            'location' => fake()->city(),
            'phone' => fake()->optional()->phoneNumber(),
            'message' => fake()->optional()->paragraph(),
            'referral_source' => fake()->optional()->randomElement(['Google', 'Facebook', 'Friend', 'Instagram', 'Other']),
            'status' => fake()->randomElement(['active', 'inactive', 'converted']),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->host(),
            'name' => fake()->randomElement([
                'Pre-Arrival Welcome',
                'Post-Stay Review Request',
                'Direct Booking Offer',
                'Seasonal Promotion',
                'Loyalty Discount',
            ]),
            'type' => fake()->randomElement(['email', 'sms']),
            'status' => fake()->randomElement(['draft', 'active', 'paused']),
            'subject' => fake()->sentence(4),
            'content' => fake()->paragraph(),
            'trigger_event' => fake()->randomElement([
                'Guest Connects to WiFi',
                '24 Hours Before Arrival',
                'Day of Checkout',
                'Custom Date',
            ]),
            'trigger_delay' => fake()->randomElement(['Instant', '1 Hour', '24 Hours', '3 Days']),
            'target_audience' => 'all_guests',
            'total_sent' => fake()->numberBetween(0, 500),
            'total_opened' => fake()->numberBetween(0, 200),
            'total_clicked' => fake()->numberBetween(0, 100),
        ];
    }
}

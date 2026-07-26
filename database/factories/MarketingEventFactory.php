<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketingEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'guest_id' => Guest::factory(),
            'event_type' => fake()->randomElement(['sent', 'opened', 'clicked', 'bounced']),
            'metadata' => null,
        ];
    }
}

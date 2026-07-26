<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class AmenityFactory extends Factory
{
    public function definition(): array
    {
        $amenities = [
            ['name' => 'High-Speed WiFi', 'description' => 'wifi'],
            ['name' => 'Smart TV', 'description' => 'monitor'],
            ['name' => 'Coffee Maker', 'description' => 'coffee'],
            ['name' => 'Pool Access', 'description' => 'droplet'],
            ['name' => 'Air Conditioning', 'description' => 'wind'],
            ['name' => 'Kitchen', 'description' => 'utensils'],
            ['name' => 'Parking', 'description' => 'car'],
            ['name' => 'Washer/Dryer', 'description' => 'tshirt'],
        ];

        $amenity = fake()->randomElement($amenities);

        return [
            'property_id' => Property::factory(),
            'name' => $amenity['name'],
            'description' => $amenity['description'],
            'price' => fake()->randomElement([0, 0, 0, 500, 1000, 2000]),
            'is_active' => fake()->boolean(90),
        ];
    }
}

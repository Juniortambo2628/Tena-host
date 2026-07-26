<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenaDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Admin User
        User::updateOrCreate(
            ['email' => 'admin@tena.com'],
            [
                'username' => 'superadmin',
                'first_name' => 'Tena',
                'last_name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // 2. Create Host User (Superhost)
        $host = User::updateOrCreate(
            ['email' => 'host@tena.com'],
            [
                'username' => 'superhost',
                'first_name' => 'John',
                'last_name' => 'Host',
                'password' => Hash::make('password'),
                'role' => 'host',
            ]
        );

        // 3. Create Staff User
        User::updateOrCreate(
            ['email' => 'staff@tena.com'],
            [
                'username' => 'staffuser',
                'first_name' => 'Jane',
                'last_name' => 'Staff',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ]
        );

        // 4. Create Guest User
        User::updateOrCreate(
            ['email' => 'guest@tena.com'],
            [
                'username' => 'guestuser',
                'first_name' => 'Jim',
                'last_name' => 'Guest',
                'password' => Hash::make('password'),
                'role' => 'guest',
            ]
        );

        // 5. Create Sample Properties for Host
        $property = Property::updateOrCreate(
            ['user_id' => $host->id, 'name' => 'Blue Haven Apartments'],
            [
                'address' => 'Muthangari Drive, Nairobi, Kenya',
                'wifi_ssid' => 'BlueHaven_GuestWiFi',
                'occupancy_threshold' => 20,
                'splash_image_path' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80',
                'pms_integration_type' => 'Beds24',
                'pms_connection_status' => 'connected',
            ]
        );

        Property::updateOrCreate(
            ['user_id' => $host->id, 'name' => 'The Shore'],
            [
                'address' => 'Beach Front Road, Mombasa',
                'wifi_ssid' => 'TheShore_WiFi',
                'occupancy_threshold' => 15,
                'pms_connection_status' => 'disconnected',
            ]
        );

        // 6. Create Sample Guests for the Property
        $guests = [
            ['first_name' => 'Alice', 'last_name' => 'Johnson', 'email' => 'alice@example.com'],
            ['first_name' => 'Bob', 'last_name' => 'Smith', 'email' => 'bob@example.com'],
            ['first_name' => 'Charlie', 'last_name' => 'Brown', 'email' => 'charlie@example.com'],
        ];

        foreach ($guests as $guestData) {
            Guest::updateOrCreate(
                ['property_id' => $property->id, 'email' => $guestData['email']],
                [
                    'first_name' => $guestData['first_name'],
                    'last_name' => $guestData['last_name'],
                    'phone' => '+254'.rand(700000000, 799999999),
                    'last_connected' => now()->subHours(rand(1, 48)),
                    'total_visits' => rand(1, 5),
                    'source' => 'WiFi',
                ]
            );
        }
    }
}

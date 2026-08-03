<?php

namespace Database\Seeders;

use App\Models\Registration;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TenaDatabaseSeeder::class,
            LandingContentSeeder::class,
            SettingsSeeder::class,
            NotificationPreferencesSeeder::class,
            PolicyDocumentSeeder::class,
        ]);
        $registrations = [
            ['first_name' => 'Alice', 'last_name' => 'Johnson', 'email' => 'alice@example.com', 'property_type' => 'vacation_rental', 'property_count' => 2, 'location' => 'Malindi', 'status' => 'active'],
            ['first_name' => 'Bob', 'last_name' => 'Smith', 'email' => 'bob@example.com', 'property_type' => 'hotel', 'property_count' => 15, 'location' => 'Nairobi', 'status' => 'active'],
            ['first_name' => 'Charlie', 'last_name' => 'Brown', 'email' => 'charlie@example.com', 'property_type' => 'b&b', 'property_count' => 5, 'location' => 'Diani', 'status' => 'active'],
        ];

        foreach ($registrations as $reg) {
            Registration::firstOrCreate(
                ['email' => $reg['email']],
                $reg
            );
        }
    }
}

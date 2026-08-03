<?php

namespace Database\Seeders;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationPreferencesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['system', 'user', 'registration', 'export'];

        User::each(function ($user) use ($categories) {
            foreach ($categories as $category) {
                NotificationPreference::firstOrCreate(
                    ['user_id' => $user->id, 'category' => $category],
                    ['email_enabled' => true, 'dashboard_enabled' => true]
                );
            }
        });
    }
}

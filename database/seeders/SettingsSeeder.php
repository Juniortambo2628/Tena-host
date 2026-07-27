<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Seed the default application settings.
     */
    public function run(): void
    {
        $defaults = [
            // General
            ['key' => 'site_name', 'value' => 'Tena Host', 'group' => 'general', 'type' => 'string'],
            ['key' => 'support_email', 'value' => 'info@tena.host', 'group' => 'general', 'type' => 'string'],
            ['key' => 'maintenance_mode', 'value' => '0', 'group' => 'general', 'type' => 'boolean'],
            ['key' => 'billing_enabled', 'value' => 'auto', 'group' => 'general', 'type' => 'string'],
            ['key' => 'business_address', 'value' => 'Nairobi, Kenya', 'group' => 'general', 'type' => 'string'],

            // Branding
            ['key' => 'email_primary_color', 'value' => '#1a1a2e', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'email_accent_color', 'value' => '#FFD300', 'group' => 'branding', 'type' => 'string'],
            ['key' => 'logo_url', 'value' => '', 'group' => 'branding', 'type' => 'string'],

            // Email Templates
            ['key' => 'welcome_email_heading', 'value' => 'Welcome to Tena Host!', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'welcome_email_body', 'value' => 'Thank you for joining Tena Host. We are excited to have you on board!', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'receipt_email_heading', 'value' => 'Your Payment Receipt', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'receipt_email_body', 'value' => 'Thank you for your payment. Here is your receipt.', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'forgot_password_email_heading', 'value' => 'Reset Your Password', 'group' => 'email_templates', 'type' => 'string'],
            ['key' => 'forgot_password_email_body', 'value' => 'Click the link below to reset your password.', 'group' => 'email_templates', 'type' => 'string'],
        ];

        foreach ($defaults as $setting) {
            // Only create if the key doesn't exist yet (preserves manual changes)
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                ]
            );
        }
    }
}

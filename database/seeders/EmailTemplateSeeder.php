<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ── Waitlist Confirmation Email ──
            [
                'key' => 'waitlist_confirmation_subject',
                'value' => "You're on the Tena waitlist!",
                'group' => 'email_templates',
                'type' => 'string',
            ],
            [
                'key' => 'waitlist_confirmation_heading',
                'value' => "You're on the list!",
                'group' => 'email_templates',
                'type' => 'string',
            ],
            [
                'key' => 'waitlist_confirmation_body',
                'value' => '',
                'group' => 'email_templates',
                'type' => 'string',
            ],

            // ── Waitlist Welcome / Follow-up Email ──
            [
                'key' => 'waitlist_welcome_subject',
                'value' => 'Welcome to the Tena Family!',
                'group' => 'email_templates',
                'type' => 'string',
            ],
            [
                'key' => 'waitlist_welcome_heading',
                'value' => 'Welcome to the Tena Family!',
                'group' => 'email_templates',
                'type' => 'string',
            ],
            [
                'key' => 'waitlist_welcome_body',
                'value' => '',
                'group' => 'email_templates',
                'type' => 'string',
            ],

            // ── Branding defaults (used by all email templates) ──
            [
                'key' => 'email_primary_color',
                'value' => '#000000',
                'group' => 'branding',
                'type' => 'string',
            ],
            [
                'key' => 'email_accent_color',
                'value' => '#FFD300',
                'group' => 'branding',
                'type' => 'string',
            ],
            [
                'key' => 'site_name',
                'value' => 'Tena',
                'group' => 'branding',
                'type' => 'string',
            ],
            [
                'key' => 'business_address',
                'value' => 'Nairobi, Kenya',
                'group' => 'branding',
                'type' => 'string',
            ],
            [
                'key' => 'logo_url',
                'value' => '/legacy/assets/Tena-logo-square.jpg',
                'group' => 'branding',
                'type' => 'string',
            ],
        ];

        foreach ($templates as $template) {
            Setting::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}

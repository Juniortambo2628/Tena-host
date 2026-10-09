<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::all()->groupBy('group'),
            'logos' => collect(Brand::LOGOS)->map(fn ($logo, $key) => [
                'key' => $key,
                'label' => $logo[0],
                'hint' => $logo[2],
                'url' => Brand::asset($key),
                'custom' => (bool) Setting::getValue($key),
            ])->values(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
        ]);

        $rules = [
            'site_name' => 'sometimes|string|max:255',
            'maintenance_mode' => 'sometimes|in:0,1',
            'support_email' => 'sometimes|email|max:255',
            'email_primary_color' => 'sometimes|string|max:7',
            'email_accent_color' => 'sometimes|string|max:7',
            'business_address' => 'sometimes|string|max:500',
            'welcome_email_heading' => 'sometimes|string|max:255',
            'welcome_email_body' => 'sometimes|string|max:5000',
            'welcome_email_subject' => 'sometimes|string|max:255',
            'receipt_email_heading' => 'sometimes|string|max:255',
            'receipt_email_body' => 'sometimes|string|max:5000',
            'forgot_password_email_heading' => 'sometimes|string|max:255',
            'forgot_password_email_body' => 'sometimes|string|max:5000',
            'waitlist_confirmation_subject' => 'sometimes|string|max:255',
            'waitlist_confirmation_heading' => 'sometimes|string|max:255',
            'waitlist_confirmation_body' => 'sometimes|string|max:5000',
            'waitlist_welcome_subject' => 'sometimes|string|max:255',
            'waitlist_welcome_heading' => 'sometimes|string|max:255',
            'waitlist_welcome_body' => 'sometimes|string|max:5000',
            'contact_enquiry_heading' => 'sometimes|string|max:255',
            'contact_enquiry_body' => 'sometimes|string|max:5000',
            'billing_enabled' => 'sometimes|in:auto,enabled,disabled',
            'signup_alert_emails' => 'sometimes|nullable|string|max:500',
            'signup_alert_webhook_url' => 'sometimes|nullable|url|max:500',
        ];

        $types = [
            'maintenance_mode' => 'boolean',
            'billing_enabled' => 'string',
        ];

        $emailKeys = [
            'welcome_email_heading', 'welcome_email_body',
            'receipt_email_heading', 'receipt_email_body',
            'forgot_password_email_heading', 'forgot_password_email_body',
            'waitlist_confirmation_subject', 'waitlist_confirmation_heading', 'waitlist_confirmation_body',
            'waitlist_welcome_subject', 'waitlist_welcome_heading', 'waitlist_welcome_body',
            'contact_enquiry_heading', 'contact_enquiry_body',
        ];

        foreach ($data['settings'] as $key => $value) {
            if (isset($rules[$key])) {
                $request->validate([$key => $rules[$key]], [], ['key' => $key]);
            }

            $group = $request->input('settings_groups.'.$key)
                ?? (in_array($key, $emailKeys) ? 'email_templates' : 'general');

            $setting = Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'type' => $types[$key] ?? 'string',
                    'group' => $group,
                ]
            );
        }

        Cache::forget('app_settings');

        return back()->with('success', 'Settings updated successfully.');
    }
}

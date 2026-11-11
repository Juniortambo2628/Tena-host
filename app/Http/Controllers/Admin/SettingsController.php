<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Brand;
use App\Support\TrialSlots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
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
            'business_address' => 'sometimes|nullable|string|max:500',
            'welcome_email_heading' => 'sometimes|nullable|string|max:255',
            'welcome_email_body' => 'sometimes|nullable|string|max:5000',
            'welcome_email_subject' => 'sometimes|nullable|string|max:255',
            'receipt_email_heading' => 'sometimes|nullable|string|max:255',
            'receipt_email_body' => 'sometimes|nullable|string|max:5000',
            'forgot_password_email_heading' => 'sometimes|nullable|string|max:255',
            'forgot_password_email_body' => 'sometimes|nullable|string|max:5000',
            'waitlist_confirmation_subject' => 'sometimes|nullable|string|max:255',
            'waitlist_confirmation_heading' => 'sometimes|nullable|string|max:255',
            'waitlist_confirmation_body' => 'sometimes|nullable|string|max:5000',
            'waitlist_welcome_subject' => 'sometimes|nullable|string|max:255',
            'waitlist_welcome_heading' => 'sometimes|nullable|string|max:255',
            'waitlist_welcome_body' => 'sometimes|nullable|string|max:5000',
            'contact_enquiry_heading' => 'sometimes|nullable|string|max:255',
            'contact_enquiry_body' => 'sometimes|nullable|string|max:5000',
            'billing_enabled' => 'sometimes|in:auto,enabled,disabled',
            'signup_alert_emails' => 'sometimes|nullable|string|max:500',
            'signup_alert_webhook_url' => 'sometimes|nullable|url|max:500',
            'trial_slots_total' => 'sometimes|nullable|integer|min:0|max:10000',
            'trial_slots_since' => 'sometimes|nullable|date',
            'callbacks_enabled' => 'sometimes|in:0,1',
            'demo_enabled' => 'sometimes|in:0,1',
            'v2_pages_enabled' => 'sometimes|in:0,1',
            'v2_live' => 'sometimes|in:0,1',
            'pricing_page_enabled' => 'sometimes|in:0,1',
            'event_accounts_enabled' => 'sometimes|in:0,1',
            'teams_enabled' => 'sometimes|in:0,1',
            'staff_wifi_enabled' => 'sometimes|in:0,1',
            'wifi_qr_enabled' => 'sometimes|in:0,1',
            'visitor_log_enabled' => 'sometimes|in:0,1',
            'event_billing_enabled' => 'sometimes|in:0,1',
            'device_financing_enabled' => 'sometimes|in:0,1',
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

        // A key with no rule is not a setting this page manages: reject it
        // rather than save it. Compared as plain array keys, so a key such as
        // "a.b" can't slip past as a nested field.
        $unknown = array_keys(array_diff_key($data['settings'], $rules));

        if ($unknown !== []) {
            throw ValidationException::withMessages(collect($unknown)->mapWithKeys(
                fn ($key) => ['settings.'.$key => 'The "'.$key.'" setting is not recognised.']
            )->all());
        }

        // Each value arrives as settings[<key>], so the rules have to target
        // settings.<key>. Validate everything before saving anything, so one bad
        // value leaves every setting as it was.
        $submitted = array_intersect_key($rules, $data['settings']);

        $request->validate(
            collect($submitted)->mapWithKeys(fn ($rule, $key) => ['settings.'.$key => $rule])->all(),
            [],
            collect($submitted)->mapWithKeys(fn ($rule, $key) => ['settings.'.$key => str_replace('_', ' ', $key)])->all(),
        );

        foreach ($data['settings'] as $key => $value) {
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
        TrialSlots::forget();

        return back()->with('success', 'Settings updated successfully.');
    }
}

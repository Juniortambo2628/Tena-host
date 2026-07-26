<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::all()->groupBy('group'),
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
            'logo_url' => 'sometimes|string|max:500',
            'welcome_email_heading' => 'sometimes|string|max:255',
            'welcome_email_body' => 'sometimes|string|max:5000',
            'receipt_email_heading' => 'sometimes|string|max:255',
            'receipt_email_body' => 'sometimes|string|max:5000',
            'forgot_password_email_heading' => 'sometimes|string|max:255',
            'forgot_password_email_body' => 'sometimes|string|max:5000',
            'billing_enabled' => 'sometimes|in:auto,enabled,disabled',
        ];

        $types = [
            'maintenance_mode' => 'boolean',
            'billing_enabled' => 'string',
        ];

        foreach ($data['settings'] as $key => $value) {
            if (isset($rules[$key])) {
                $request->validate([$key => $rules[$key]], [], ['key' => $key]);
            }

            $setting = Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'type' => $types[$key] ?? 'string',
                ]
            );

            if ($request->has('settings_groups.'.$key)) {
                $setting->update(['group' => $request->input('settings_groups.'.$key)]);
            }
        }

        Cache::forget('app_settings');

        return back()->with('success', 'Settings updated successfully.');
    }
}

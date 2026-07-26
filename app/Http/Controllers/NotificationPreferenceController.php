<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationPreferenceController extends Controller
{
    public function index()
    {
        $categories = ['system', 'user', 'registration', 'export'];

        $preferences = NotificationPreference::where('user_id', Auth::id())
            ->get()
            ->keyBy('category')
            ->toArray();

        foreach ($categories as $category) {
            if (! isset($preferences[$category])) {
                $preferences[$category] = [
                    'category' => $category,
                    'email_enabled' => true,
                    'dashboard_enabled' => true,
                ];
            }
        }

        return Inertia::render('Profile/NotificationPreferences', [
            'preferences' => $preferences,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.category' => 'required|string|in:system,user,registration,export',
            'preferences.*.email_enabled' => 'required|boolean',
            'preferences.*.dashboard_enabled' => 'required|boolean',
        ]);

        foreach ($validated['preferences'] as $pref) {
            NotificationPreference::updateOrCreate(
                ['user_id' => Auth::id(), 'category' => $pref['category']],
                [
                    'email_enabled' => $pref['email_enabled'],
                    'dashboard_enabled' => $pref['dashboard_enabled'],
                ]
            );
        }

        return back()->with('success', 'Notification preferences updated.');
    }
}

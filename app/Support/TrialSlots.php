<?php

namespace App\Support;

use App\Models\Registration;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * "X of 50 free trial slots taken". Taken is the number of real trial
 * bookings: host and business sign-ups (registrations) since the trial
 * opened, not counting ones an admin marked inactive. The total and the
 * start date are set in Admin → Settings. No total, no counter.
 */
class TrialSlots
{
    public const CACHE_KEY = 'trial_slots';

    /** @return array{taken: int, total: int, left: int}|null */
    public static function summary(): ?array
    {
        $total = (int) Setting::getValue('trial_slots_total', 0);
        if ($total <= 0) {
            return null;
        }

        $taken = (int) Cache::remember(self::CACHE_KEY, 300, function () {
            try {
                $since = filled(Setting::getValue('trial_slots_since')) ? Carbon::parse(Setting::getValue('trial_slots_since'))->startOfDay() : null;
            } catch (\Throwable) {
                $since = null; // a typo in Settings must never break the public pages
            }

            return Registration::query()
                ->whereIn('type', Registration::TRIAL_TYPES)
                ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'inactive'))
                ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
                ->count();
        });
        $taken = min($taken, $total);

        return ['taken' => $taken, 'total' => $total, 'left' => $total - $taken];
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Analytics;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

/**
 * First-party funnel counters for the public pages (path cards, JOIN clicks,
 * each sign-up step). Stored as daily totals in `analytics`, e.g.
 * metric_name = "public.join_click:hosts". The browser also pushes the same
 * events to window.dataLayer for GA4/GTM when that is installed.
 */
class TrackController extends Controller
{
    private const EVENT_PATTERN = '/^(path_card_[a-z]+|join_click|signup_step_\d|signup_submit|signup_success)$/';

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'event' => ['required', 'string', 'regex:'.self::EVENT_PATTERN],
            'page' => ['nullable', 'string', 'regex:/^[a-z0-9-]{1,20}$/'],
        ]);

        $key = 'track:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->noContent(429);
        }
        RateLimiter::hit($key, 60);

        $metric = 'public.'.$data['event'].(isset($data['page']) ? ':'.$data['page'] : '');

        // whereDate: the `date` cast stores a full datetime on some drivers.
        $row = Analytics::where('metric_name', $metric)->whereDate('date_recorded', today())->first()
            ?? Analytics::create(['metric_name' => $metric, 'metric_value' => 0, 'date_recorded' => today()]);
        $row->increment('metric_value');

        return response()->noContent();
    }
}

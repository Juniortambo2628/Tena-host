<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSignupRequest;
use App\Models\Registration;
use App\Services\SignupAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

use function Illuminate\Support\defer;

/**
 * Single endpoint for both public sign-up forms (POST /api/signups with
 * type = host | business). Questions that have a dedicated registrations
 * column are stored there; everything else goes into `answers`.
 */
class SignupController extends Controller
{
    /** Column => max length, matching the registrations table. */
    private const COLUMNS = [
        'first_name' => 50,
        'last_name' => 50,
        'email' => 100,
        'phone' => 20,
        'business_name' => 150,
        'location' => 100,
        'property_type' => 50,
        'units' => 20,
        'primary_platform' => 50,
        'biggest_challenge' => 100,
        'referral_source' => 50,
        'message' => 5000,
    ];

    public function store(StoreSignupRequest $request, SignupAlertService $alerts): JsonResponse
    {
        $key = 'signup:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $schema = $request->schema();
        $validated = collect($request->validated())->except(['type', 'consent']);

        $columns = $validated->only(array_keys(self::COLUMNS))
            ->map(fn ($value, $column) => $value === null ? null : Str::limit((string) $value, self::COLUMNS[$column], ''));

        $attributes = $columns->except('email')->all() + [
            'answers' => $validated->except(array_keys(self::COLUMNS))->all(),
            'agree_updates' => true,
            // The wording comes from the CMS, never from the client.
            'consent_text' => $schema->consentText,
            'consented_at' => now(),
            'consent_ip' => $request->ip(),
            'source_page' => $schema->pageSlug,
        ];

        if (isset($attributes['units']) && preg_match('/\d+/', $attributes['units'], $m)) {
            $attributes['property_count'] = (int) $m[0];
        }

        $registration = Registration::firstOrNew([
            'email' => Str::lower($columns['email']),
            'type' => $schema->type,
        ]);
        $isNew = ! $registration->exists;
        $registration->fill($attributes);
        if ($isNew) {
            $registration->status = 'active';
        }
        $registration->save();

        RateLimiter::hit($key, 600);

        if ($isNew) {
            // Request-scoped: runs once after the response is sent.
            defer(fn () => $alerts->notify($registration));
        }

        return response()->json([
            'message' => 'Application received.',
            'id' => $registration->id,
        ], $isNew ? 201 : 200);
    }
}

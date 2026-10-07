<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSignupRequest;
use App\Models\Registration;
use App\Services\Cms\SignupFormSchema;
use App\Services\SignupAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

use function Illuminate\Support\defer;

/**
 * Single endpoint for both public sign-up forms (POST /api/signups with
 * type = host | business), using the payload keys from SIGNUP-FIELDS.md.
 * Answers with a dedicated registrations column are stored there; all the
 * rest (plan, platforms, isp, ...) goes into `answers`.
 */
class SignupController extends Controller
{
    /** Payload key => [registrations column, max length]. */
    private const COLUMNS = [
        'firstName' => ['first_name', 50],
        'lastName' => ['last_name', 50],
        'email' => ['email', 100],
        'phone' => ['phone', 20],
        'businessName' => ['business_name', 150],
        'area' => ['location', 100],
        'units' => ['units', 20],
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
        $validated = collect($request->validated())->except(['type', ...SignupFormSchema::META_KEYS]);

        $attributes = [];
        foreach (self::COLUMNS as $field => [$column, $limit]) {
            if ($validated->has($field)) {
                $value = $validated[$field];
                $attributes[$column] = $value === null ? null : Str::limit((string) $value, $limit, '');
            }
        }
        if (isset($attributes['email'])) {
            $attributes['email'] = Str::lower($attributes['email']);
        }
        if (isset($attributes['units']) && preg_match('/\d+/', $attributes['units'], $m)) {
            $attributes['property_count'] = (int) $m[0];
        }

        $attributes += [
            'answers' => $validated->except(array_keys(self::COLUMNS))->filter(fn ($v) => $v !== null && $v !== [])->all(),
            'agree_updates' => true,
            // The wording comes from the CMS, never from the client.
            'consent_text' => $schema->consentText,
            'consented_at' => now(),
            'consent_ip' => $request->ip(),
            'source_page' => $schema->pageSlug,
        ];

        // The WhatsApp number is the one required contact, so a repeat
        // application from the same number updates the earlier one.
        $registration = Registration::firstOrNew(['phone' => $attributes['phone'], 'type' => $schema->type]);
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

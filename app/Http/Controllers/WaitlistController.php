<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class WaitlistController extends Controller
{
    public function store(Request $request)
    {
        $key = 'waitlist:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'property_type' => ['required', Rule::in(['Entire Place', 'Private Room', 'Shared Room', 'Hotel / Boutique'])],
            'units' => ['required', Rule::in(['1-5', '6-20', '21+'])],
            'primary_platform' => ['required', Rule::in(['Airbnb', 'Booking.com', 'Vrbo', 'Multiple', 'Direct Bookings'])],
            'biggest_challenge' => ['required', Rule::in([
                'Getting more direct bookings',
                'Getting repeat bookings',
                'OTA (Airbnb, Booking.com etc commissions)',
                'Guest communication',
                'Other',
            ])],
            'agree_updates' => ['boolean'],
        ]);

        $unitsMap = [
            '1-5' => 1,
            '6-20' => 6,
            '21+' => 21,
        ];

        Registration::firstOrCreate(
            ['email' => $validated['email']],
            [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'] ?? null,
                'property_type' => $validated['property_type'],
                'property_count' => $unitsMap[$validated['units']],
                'units' => $validated['units'],
                'primary_platform' => $validated['primary_platform'],
                'biggest_challenge' => $validated['biggest_challenge'],
                'agree_updates' => $validated['agree_updates'] ?? false,
                'status' => 'active',
            ]
        );

        RateLimiter::hit($key, 300);

        return response()->json(['message' => "Thanks! You're on the list."], 201);
    }
}

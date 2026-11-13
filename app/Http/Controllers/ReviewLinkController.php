<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Services\ReviewRequestService;
use App\Support\Brand;
use Illuminate\Http\Request;

class ReviewLinkController extends Controller
{
    public function __invoke(string $token, ReviewRequestService $reviews)
    {
        $guest = Guest::with('property')->where('review_token', $token)->firstOrFail();

        abort_unless($guest->property?->review_url, 404);

        return redirect()->away($reviews->click($guest));
    }

    /** The review email's unsubscribe link (signed): "Unsubscribe?" with a button. */
    public function confirmUnsubscribe(Request $request, string $token)
    {
        $guest = Guest::with('property')->where('review_token', $token)->firstOrFail();

        return response()->view('portal.unsubscribe-confirm', [
            'propertyName' => $guest->property?->name ?? Brand::name(),
            'action' => $request->fullUrl(),
        ]);
    }

    /** The button on that page: no more messages from this property. */
    public function unsubscribe(string $token)
    {
        $guest = Guest::with('property')->where('review_token', $token)->firstOrFail();

        $guests = Guest::where('property_id', $guest->property_id)
            ->where(fn ($q) => $q->whereKey($guest->id)->when($guest->email, fn ($q) => $q->orWhere('email', $guest->email)));
        $guests->update(['marketing_opt_in' => false, 'opted_out_at' => now()]);

        return response()->view('portal.unsubscribed', ['propertyName' => $guest->property?->name ?? Brand::name()]);
    }
}

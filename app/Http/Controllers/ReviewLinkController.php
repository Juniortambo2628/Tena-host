<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Services\ReviewRequestService;

class ReviewLinkController extends Controller
{
    public function __invoke(string $token, ReviewRequestService $reviews)
    {
        $guest = Guest::with('property')->where('review_token', $token)->firstOrFail();

        abort_unless($guest->property?->review_url, 404);

        return redirect()->away($reviews->click($guest));
    }
}

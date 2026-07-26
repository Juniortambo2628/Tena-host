<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GuestResource;
use App\Models\Guest;
use Illuminate\Http\Request;

class HostGuestController extends Controller
{
    public function index(Request $request)
    {
        $guests = Guest::whereIn('property_id', $request->user()->propertyIds())
            ->latest()
            ->paginate(50);

        return GuestResource::collection($guests);
    }

    public function show(Request $request, Guest $guest)
    {
        if (! in_array($guest->property_id, $request->user()->propertyIds())) {
            abort(403);
        }

        return new GuestResource($guest->load(['property', 'orders']));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GuestPortalController extends Controller
{
    /**
     * Display the guest portal for an assigned property.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user && $user->isGuest()) {
            $propertyIds = $user->guestRecords()->pluck('property_id')->unique()->values();
            $propertyId = $request->query('p', $propertyIds->first());

            if ($propertyId && ! $propertyIds->contains($propertyId)) {
                abort(403, 'You do not have access to this property.');
            }
        } else {
            // Fallback for public preview or staff/admin
            $propertyId = $request->query('p', Property::first()?->id);
        }

        $property = Property::with(['accessPoints', 'host'])->find($propertyId);

        if (! $property) {
            abort(404);
        }

        return Inertia::render('Guest/Portal', [
            'property' => $property,
            'amenities' => $property->amenities()->where('is_active', true)->get()->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'description' => $a->description,
                'price' => $a->price,
                'icon' => $a->description ? strtolower(explode(' ', $a->description)[0]) : 'star',
            ]),
            'guidebook_link' => route('guest.guidebook', ['p' => $property->id]),
        ]);
    }
}

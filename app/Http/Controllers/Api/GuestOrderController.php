<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AmenityResource;
use App\Http\Resources\PropertyResource;
use App\Models\Amenity;
use App\Models\Order;
use App\Models\Property;
use Illuminate\Http\Request;

class GuestOrderController extends Controller
{
    public function portal(Request $request)
    {
        $user = $request->user();

        if (! $user->isGuest()) {
            abort(403);
        }

        $propertyIds = $user->guestRecords()->pluck('property_id')->unique()->values();
        $propertyId = $request->query('p', $propertyIds->first());

        if ($propertyId && ! $propertyIds->contains($propertyId)) {
            abort(403, 'You do not have access to this property.');
        }

        $property = Property::with(['accessPoints', 'host'])->findOrFail($propertyId);

        return response()->json([
            'property' => new PropertyResource($property),
            'amenities' => AmenityResource::collection($property->amenities()->where('is_active', true)->get()),
            'orders' => $user->guestRecords()
                ->where('property_id', $propertyId)
                ->first()
                ?->orders()
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user->isGuest()) {
            abort(403);
        }

        $validated = $request->validate([
            'amenity_id' => 'required|exists:amenities,id',
        ]);

        $amenity = Amenity::findOrFail($validated['amenity_id']);
        $guestRecord = $user->guestRecords()
            ->where('property_id', $amenity->property_id)
            ->first();

        if (! $guestRecord) {
            abort(403, 'You are not a guest at this property.');
        }

        $order = Order::create([
            'property_id' => $amenity->property_id,
            'guest_id' => $guestRecord->id,
            'amenity_id' => $amenity->id,
            'status' => 'pending',
            'total' => $amenity->price,
        ]);

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => $order->load('amenity'),
        ], 201);
    }
}

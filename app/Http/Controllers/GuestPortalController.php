<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Property;
use App\Services\ExtrasPaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GuestPortalController extends Controller
{
    /**
     * The property a signed-in user may open in the guest portal: guests only
     * where they have stayed, hosts and staff only their own properties (a
     * preview), admins any.
     *
     * @param  list<string>  $with
     */
    public static function accessibleProperty(Request $request, array $with = []): Property
    {
        $user = $request->user();
        $allowed = array_values(array_unique(array_map('intval', match (true) {
            $user->isGuest() => $user->guestRecords()->pluck('property_id')->all(),
            $user->isStaff() => $user->staffProperties()->pluck('properties.id')->all(),
            default => $user->propertyIds(),
        })));

        $propertyId = (int) $request->query('p', $allowed[0] ?? 0);
        abort_unless(in_array($propertyId, $allowed, true), $allowed ? 403 : 404, 'You do not have access to this property.');

        return Property::with($with)->findOrFail($propertyId);
    }

    /**
     * Display the guest portal for an assigned property.
     */
    public function index(Request $request, ExtrasPaymentService $payments)
    {
        $user = $request->user();
        $property = static::accessibleProperty($request, ['accessPoints', 'host']);

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
            'orders' => $user?->isGuest()
                ? Order::with('amenity:id,name')
                    ->whereIn('guest_id', $user->guestRecords()->pluck('id'))
                    ->where('property_id', $property->id)
                    ->latest()->take(5)->get(['id', 'amenity_id', 'total', 'status', 'payment_status', 'created_at'])
                : [],
            'guestPhone' => $user?->isGuest() ? $user->guestRecords()->where('property_id', $property->id)->value('phone') : null,
            'mpesaEnabled' => $payments->enabled(),
        ]);
    }
}

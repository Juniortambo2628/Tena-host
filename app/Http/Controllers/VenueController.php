<?php

namespace App\Http\Controllers;

use App\Models\Property;

/**
 * The business customer homepage: menu, offers and events. Customers land
 * here right after connecting to a business's WiFi. Plain Blade (like the
 * captive portal) so it loads fast in the captive browser.
 */
class VenueController extends Controller
{
    public function show(Property $property)
    {
        abort_unless($property->host?->isBusiness(), 404);

        return view('portal.venue', [
            'property' => $property,
            'logoUrl' => $property->logo_path ? asset('storage/'.ltrim($property->logo_path, '/')) : null,
            'menu' => $property->amenities()->where('is_active', true)->orderBy('name')->get(['name', 'description', 'price']),
            'offers' => self::lines($property->offers),
            'events' => self::lines($property->events),
        ]);
    }

    /**
     * @return list<string>
     */
    public static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text))));
    }
}

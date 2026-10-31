<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Setting;
use App\Support\SplashDesign;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * /demo: the real guest WiFi page, filled with made-up sample places so
 * visitors can try it on their own phone (the homepage "Try it yourself"
 * QR code points here). Submitting shows the "connected" page; nothing is
 * saved, sent or authorised. Off until Admin -> Settings switches it on.
 */
class DemoController extends Controller
{
    /** Made-up places only: never real venues or events. */
    public const PLACES = [
        'rental' => ['label' => 'Rental', 'name' => 'The Loft Nairobi', 'type' => 'apartment', 'template' => 'bento', 'accent' => '#0F4C45'],
        'cafe' => ['label' => 'Café', 'name' => 'Kahawa House', 'type' => 'cafe', 'template' => 'brew', 'accent' => '#6B4A35'],
        'salon' => ['label' => 'Salon', 'name' => 'Glow Studio', 'type' => 'salon', 'template' => 'bloom', 'accent' => '#C77B86'],
        'event' => ['label' => 'Event', 'name' => 'Kona Design Week', 'type' => null, 'template' => 'nocturne', 'accent' => '#7357F0'],
    ];

    public static function enabled(): bool
    {
        return filter_var(Setting::getValue('demo_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public function show(Request $request): Response
    {
        return $this->render('portal.splash', $request);
    }

    /** The form "connects": the connected page, with nothing stored. */
    public function connect(Request $request): Response
    {
        return $this->render('portal.connected', $request);
    }

    private function render(string $view, Request $request): Response
    {
        abort_unless(static::enabled(), 404);

        $key = array_key_exists($request->query('type'), self::PLACES) ? $request->query('type') : 'rental';
        $property = static::place($key);

        return response()->view($view, [
            'params' => ['id' => null, 'ap' => null, 't' => null, 'ssid' => null, 'url' => null],
            'property' => $property,
            'propertyName' => $property->name,
            'logoUrl' => null,
            'splashImageUrl' => null,
            'design' => SplashDesign::forProperty($property),
            'canConnect' => true,
            'deviceMac' => null,
            'returningGuest' => null,
            'consentText' => WifiPortalController::consentText($property->name),
            'isBusiness' => SplashDesign::isBusiness($property),
            'continueUrl' => null,
            'formAction' => route('demo.connect', ['type' => $key]),
            'demo' => ['current' => $key, 'places' => array_map(fn ($p) => $p['label'], self::PLACES)],
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** An unsaved sample property, styled by its onboarding template. */
    public static function place(string $key): Property
    {
        $place = self::PLACES[$key];
        $business = $key !== 'rental';

        $branding = SplashDesign::withDefaults(['template' => $place['template'], 'accent' => $place['accent']], $business);
        $branding = array_replace($branding, match ($key) {
            'rental' => [
                'welcome_note' => "Karibu! The coffee is on the counter and the best sunset is from the rooftop.\n\nWiFi works in every room. Message us any time.",
                'host_name' => 'Amina, your host',
                'house_rules' => ['Check-out by 10am', 'Quiet hours 10pm to 7am', 'No smoking indoors'],
                'nearby' => [['name' => 'Kahawa House', 'category' => 'food', 'note' => 'Great coffee and breakfast', 'distance' => '3 min walk']],
                'offers' => [['title' => 'Late checkout, 2pm', 'detail' => 'Ask us on WhatsApp.', 'when' => 'Example']],
            ],
            'event' => [
                'heading' => 'Welcome to Kona Design Week',
                'message' => 'Free WiFi all day. Join for the schedule and next year’s early-bird tickets.',
                'opt_in' => 'Send me next year’s early-bird tickets',
                'fields' => ['email' => true, 'birthday' => false],
                'tiles' => [
                    ['icon' => 'cal', 'label' => 'Schedule', 'url' => null],
                    ['icon' => 'star', 'label' => 'Speakers', 'url' => null],
                    ['icon' => 'gift', 'label' => 'Sponsor offer', 'url' => null],
                ],
                'offers' => [['title' => 'Sponsor offer: 2-for-1 coffee', 'detail' => 'Show this page at the café stand.', 'when' => 'Today']],
            ],
            default => [
                'welcome_note' => 'Karibu! Ask us about today’s specials.',
                'offers' => [['title' => '10% off your next visit', 'detail' => 'For members who join the WiFi list.', 'when' => 'Example']],
            ],
        });

        return new Property([
            'name' => $place['name'],
            'business_type' => $place['type'] ?? 'bar',
            'address' => 'Nairobi',
            'branding_json' => SplashDesign::clean($branding),
        ]);
    }
}

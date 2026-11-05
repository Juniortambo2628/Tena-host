<?php

namespace App\Support;

use App\Models\Registration;
use App\Models\Setting;
use App\Models\User;

/**
 * What each kind of account is called and how it behaves, in one place:
 * the words its dashboard uses for the people it collects and the places
 * it runs, and whether its WiFi pages start from the rental or the business
 * templates. Pages read this (shared to the dashboard as `auth.account`)
 * instead of checking the account type themselves.
 */
class AccountType
{
    /** Event accounts: what the account mostly does. Defaults and wording only; the data is the same. */
    public const EVENT_PROFILES = ['venue', 'organiser', 'both'];

    private const DEFINITIONS = [
        User::ACCOUNT_HOST => [
            'label' => 'Short-term rentals',
            'person' => 'guest',
            'people' => 'guests',
            'place' => 'property',
            'places' => 'properties',
            'splash' => 'stays',
        ],
        User::ACCOUNT_BUSINESS => [
            'label' => 'Business',
            'person' => 'customer',
            'people' => 'customers',
            'place' => 'location',
            'places' => 'locations',
            'splash' => 'business',
        ],
        User::ACCOUNT_EVENT => [
            'label' => 'Events',
            'person' => 'attendee',
            'people' => 'attendees',
            'place' => 'venue',
            'places' => 'venues',
            'splash' => 'business',
        ],
    ];

    /**
     * @return array{type: string, label: string, person: string, people: string, place: string, places: string, splash: string}
     */
    public static function for(?string $type): array
    {
        $type = isset(self::DEFINITIONS[$type]) ? $type : User::ACCOUNT_HOST;

        return ['type' => $type] + self::DEFINITIONS[$type];
    }

    /** The account a public sign-up becomes. Event accounts only once switched on in Admin → Settings. */
    public static function forRegistration(string $registrationType): string
    {
        return match ($registrationType) {
            Registration::TYPE_BUSINESS => User::ACCOUNT_BUSINESS,
            Registration::TYPE_EVENT => self::eventsEnabled() ? User::ACCOUNT_EVENT : User::ACCOUNT_BUSINESS,
            default => User::ACCOUNT_HOST,
        };
    }

    public static function eventsEnabled(): bool
    {
        return filter_var(Setting::getValue('event_accounts_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }
}

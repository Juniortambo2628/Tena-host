<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The product name and logos, from Admin → Settings → Branding with TenaFi
 * defaults. Shared to every page (Inertia `brand`), the captive portal and
 * emails, so swapping a logo is one upload.
 */
class Brand
{
    public const NAME = 'TenaFi';

    /** The yellow of the official logo; email headers use it so the logo blends in. */
    public const YELLOW = '#F0C138';

    /**
     * Setting key => [label, default file, where it shows].
     */
    public const LOGOS = [
        'logo_url' => ['Official logo', '/brand/tenafi-logo.png', 'Emails and link previews (yellow background)'],
        'logo_header_url' => ['Header logo', '/brand/tenafi-logo-header.png', 'Website header, sign-in pages, dashboards and WiFi pages (transparent, dark text)'],
        'logo_footer_url' => ['Footer logo', '/brand/tenafi-logo-footer.png', 'Website footer and dark backgrounds (transparent, light text)'],
        'favicon_url' => ['Favicon', '/brand/tenafi-favicon-512.png', 'Browser tab and home-screen icon (square)'],
    ];

    public static function name(): string
    {
        return Setting::getValue('site_name') ?: self::NAME;
    }

    public static function asset(string $key): string
    {
        return Setting::getValue($key) ?: self::LOGOS[$key][1];
    }

    /** The official logo, absolute (emails, link previews). */
    public static function emailLogoUrl(): string
    {
        return asset(self::asset('logo_url'));
    }

    /**
     * @return array{name: string, logo: string, header: string, footer: string, favicon: string}
     */
    public static function toArray(): array
    {
        return [
            'name' => self::name(),
            'logo' => self::asset('logo_url'),
            'header' => self::asset('logo_header_url'),
            'footer' => self::asset('logo_footer_url'),
            'favicon' => self::asset('favicon_url'),
        ];
    }
}

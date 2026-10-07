<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The product name and logo, from Admin → Settings (site_name, logo_url)
 * with TenaFi defaults. Shared to every page (Inertia `brand`), the
 * captive portal and emails, so a rename is one setting.
 */
class Brand
{
    public const NAME = 'TenaFi';

    public const LOGO = '/brand/tenafi-logo.svg';

    /** Emails need a raster image: many clients block SVG. */
    public const EMAIL_LOGO = '/brand/tenafi-logo.png';

    public static function name(): string
    {
        return Setting::getValue('site_name') ?: self::NAME;
    }

    public static function logoUrl(): string
    {
        return Setting::getValue('logo_url') ?: self::LOGO;
    }

    public static function emailLogoUrl(): string
    {
        $logo = Setting::getValue('logo_url');

        return asset($logo && ! str_ends_with($logo, '.svg') ? $logo : self::EMAIL_LOGO);
    }

    /**
     * @return array{name: string, logo: string}
     */
    public static function toArray(): array
    {
        return ['name' => self::name(), 'logo' => self::logoUrl()];
    }
}

<?php

namespace App\Services\Pms;

use InvalidArgumentException;

class PmsFactory
{
    public static function driver(string $provider): PmsDriver
    {
        return match (strtolower($provider)) {
            'beds24' => new Beds24Driver,
            'cloudbeds' => new CloudbedsDriver,
            'hostaway' => new HostawayDriver,
            default => throw new InvalidArgumentException("Unsupported PMS provider: {$provider}"),
        };
    }
}

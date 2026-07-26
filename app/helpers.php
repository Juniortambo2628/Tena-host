<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * Get or set a typed application setting.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }
}

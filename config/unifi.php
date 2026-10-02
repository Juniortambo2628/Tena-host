<?php

return [

    /*
    |--------------------------------------------------------------------------
    | UniFi Controller connection
    |--------------------------------------------------------------------------
    |
    | Settings for talking to the Ubiquiti UniFi controller that manages the
    | access points. The external captive portal authorizes a guest device by
    | calling the controller's `cmd/stamgr` -> `authorize-guest` endpoint.
    |
    */

    // Base URL of the controller, including scheme and port.
    //  - Self-hosted / classic controller:   https://controller.example.com:8443
    //  - UniFi OS (UDM, UDM-Pro, CloudKey G2+): https://192.168.1.1   (no port)
    'base_url' => env('UNIFI_BASE_URL'),

    // Local controller admin account used only for authorizing guests.
    // Create a dedicated limited admin for this rather than reusing a person's login.
    'username' => env('UNIFI_USERNAME'),
    'password' => env('UNIFI_PASSWORD'),

    // UniFi site the APs belong to. "default" unless you created extra sites.
    'site' => env('UNIFI_SITE', 'default'),

    // Set true for UniFi OS consoles (UDM/UDM-Pro/CloudKey Gen2+), which proxy
    // the network API under /proxy/network and log in at /api/auth/login.
    // Set false for a classic self-hosted controller on :8443.
    'is_unifi_os' => env('UNIFI_IS_OS', true),

    // Most controllers present a self-signed certificate. Leave false unless
    // the controller has a certificate your server already trusts.
    'verify_ssl' => env('UNIFI_VERIFY_SSL', false),

    // How long (minutes) to authorize a device for after it taps "Connect".
    'auth_minutes' => (int) env('UNIFI_AUTH_MINUTES', 1440),

    // Network timeout (seconds) for controller calls.
    'timeout' => (int) env('UNIFI_TIMEOUT', 10),
];

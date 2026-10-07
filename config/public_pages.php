<?php

return [
    /*
    | Short URLs for published policy documents (Admin -> Policies).
    */
    'policies' => [
        'privacy' => 'privacy-policy',
        'terms' => 'terms-of-service',
    ],

    /*
    | Old URLs permanently redirected after the TenaFi relaunch. The old
    | homepage pitch now lives at /hosts.
    */
    'redirects' => [
        '/home' => '/hosts',
        '/welcome' => '/hosts',
        '/waitlist' => '/hosts#join',
        '/index.html' => '/',
        '/hosts.html' => '/hosts',
        '/business.html' => '/business',
        '/register.php' => '/hosts#join',
        '/legacy/index.html' => '/hosts',
        '/privacy-policy' => '/privacy',
        '/terms-of-service' => '/terms',
    ],
];

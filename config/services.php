<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret' => env('PAYSTACK_SECRET_KEY'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
    ],

    'mpesa' => [
        'key' => env('MPESA_CONSUMER_KEY'),
        'secret' => env('MPESA_CONSUMER_SECRET'),
        'passkey' => env('MPESA_PASSKEY'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'env' => env('MPESA_ENV', 'sandbox'),
        'callback_url' => env('MPESA_CALLBACK_URL'),
    ],

    // Text messaging (App\Services\Messaging\Messenger). Drivers: null,
    // africastalking, whatsapp_cloud, or a class implementing SmsDriverInterface.
    'sms' => [
        'driver' => env('SMS_DRIVER', 'null'),
    ],

    'africastalking' => [
        'username' => env('AFRICASTALKING_USERNAME'), // "sandbox" uses the sandbox API
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'from' => env('AFRICASTALKING_FROM'),          // approved sender ID, optional
    ],

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'null'),
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'template' => env('WHATSAPP_TEMPLATE'),         // approved template with one body variable
        'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'fallback_to_sms' => env('WHATSAPP_FALLBACK_TO_SMS', true),
    ],

    'pms' => [
        'webhook_secret' => env('PMS_WEBHOOK_SECRET'),
    ],

];

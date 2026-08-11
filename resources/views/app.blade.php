<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Primary Meta Tags --}}
        <title inertia>{{ config('app.name', 'Tena') }}</title>
        <meta name="title" content="{{ config('app.name', 'Tena') }} — Smart WiFi Management for Hospitality">
        <meta name="description" content="Tena helps hospitality businesses capture guest data through branded WiFi splash pages, drive direct bookings, and build lasting guest relationships. Built by Superhosts for Superhosts.">
        <meta name="keywords" content="WiFi splash page, guest data collection, hospitality WiFi, direct bookings, guest marketing, WiFi management, hotel WiFi, vacation rental WiFi">
        <meta name="author" content="Tena">
        <meta name="robots" content="index, follow">
        <meta name="theme-color" content="#FFD300">

        {{-- Open Graph / Facebook --}}
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:title" content="{{ config('app.name', 'Tena') }} — Smart WiFi Management for Hospitality">
        <meta property="og:description" content="Capture guest data through branded WiFi splash pages, drive direct bookings, and build lasting guest relationships.">
        <meta property="og:image" content="{{ asset('legacy/assets/Tena-logo-square.jpg') }}">
        <meta property="og:site_name" content="{{ config('app.name', 'Tena') }}">

        {{-- Twitter --}}
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="{{ url('/') }}">
        <meta property="twitter:title" content="{{ config('app.name', 'Tena') }} — Smart WiFi Management for Hospitality">
        <meta property="twitter:description" content="Capture guest data through branded WiFi splash pages, drive direct bookings, and build lasting guest relationships.">
        <meta property="twitter:image" content="{{ asset('legacy/assets/Tena-logo-square.jpg') }}">

        {{-- Canonical --}}
        <link rel="canonical" href="{{ url('/') }}">

        {{-- Favicon --}}
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('legacy/assets/Tena-logo-square.jpg') }}">

        {{-- Fonts --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        {{-- Scripts --}}
        <script src="https://js.paystack.co/v1/inline.js"></script>
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Primary / social meta. Public CMS pages pass `seo` props (see PublicPageController); everything else gets the defaults. --}}
        @php
            $seo = $page['props']['seo'] ?? [];
            $siteName = $seo['site_name'] ?? 'TenaFi';
            $metaTitle = $seo['title'] ?? "TenaFi | Africa's guest relationship platform";
            $metaDescription = $seo['description'] ?? 'TenaFi turns the WiFi you already have into growth: capture every guest, stay in touch, and bring them back.';
            $metaImage = $seo['image'] ?? \App\Support\Brand::emailLogoUrl();
            $metaUrl = $seo['url'] ?? url()->current();
        @endphp
        <title inertia>{{ $metaTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <meta name="author" content="{{ $siteName }}">
        <meta name="robots" content="index, follow">
        <meta name="theme-color" content="#FFD300">

        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $metaUrl }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:image" content="{{ $metaImage }}">
        <meta property="og:site_name" content="{{ $siteName }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ $metaUrl }}">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $metaImage }}">

        @if(! empty($seo))
        <link rel="canonical" href="{{ $metaUrl }}">
        @endif

        {{-- Favicon --}}
        @include('partials.favicon')

        {{-- Fonts --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900|inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        {{-- Font Awesome --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

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

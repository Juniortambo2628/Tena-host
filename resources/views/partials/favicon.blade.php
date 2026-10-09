{{-- Favicon from Admin → Settings → Branding (App\Support\Brand) --}}
@php($favicon = asset(\App\Support\Brand::asset('favicon_url')))
<link rel="icon" href="{{ $favicon }}">
<link rel="apple-touch-icon" href="{{ $favicon }}">

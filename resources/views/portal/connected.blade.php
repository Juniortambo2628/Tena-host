@php
    $title = "You're connected";
    // Society welcomes the guest into the club by its name (the headline).
    if ($design['template'] === 'society' && empty($staffConnected)) {
        $club = trim((string) ($design['heading'] ?: $propertyName));
        $club = preg_replace('/^The /', 'the ', $club);
        $title = stripos($club, 'welcome') === 0 ? $club : 'Welcome to '.$club;
    }
    $page = 'connected';
@endphp
@extends('portal.layout')

@section('title', $propertyName.': connected')
@if($design['template'] === 'society' && empty($staffConnected))
    {{-- Society: a gold ring, the member perk (the first offer) and a calm sign-off. --}}
    @section('hero_top')
        <span class="sp-ring" aria-hidden="true">@include('portal.partials.icon', ['name' => 'check', 'size' => 34, 'stroke' => 2])</span>
    @endsection
    @section('eyebrow', 'You’re connected')
    @section('lede')Enjoy your time at {{ $propertyName }}.@endsection

    @section('connect')
        @php($perk = $design['perk'] ?? null)
        @if($perk)
            <div class="sp-perk">
                <p class="sp-perk__kicker">Your member perk</p>
                <h2 class="sp-perk__title" id="sp-connect-title">{{ $perk['title'] }}</h2>
                @if(!empty($perk['detail']))<p class="sp-perk__detail">{{ $perk['detail'] }}</p>@endif
                @if(!empty($perk['code']))
                    <div class="sp-perk__code">
                        <small>Use code at checkout</small>
                        <strong>{{ $perk['code'] }}</strong>
                    </div>
                @endif
                @if(!empty($perk['when']))<p class="sp-perk__when">{{ $perk['when'] }}</p>@endif
            </div>
            @if(!empty($perk['code']))
                <p class="sp-perk__save">@include('portal.partials.icon', ['name' => 'camera', 'size' => 16]) Screenshot this to save your code</p>
            @endif
        @endif
        <div class="sp-signoff-block">
            <h2 @unless($perk) id="sp-connect-title" @endunless>You’re all set.</h2>
            <p>You can close this page and enjoy your visit.</p>
            @if($design['content']['review'])
                <a class="sp-pill sp-pill--ghost" href="{{ $design['content']['review']['url'] }}" target="_blank" rel="noopener">@include('portal.partials.icon', ['name' => 'star', 'size' => 16]) Review {{ $propertyName }} on Google</a>
            @endif
        </div>
    @endsection
@else
@section('eyebrow', 'Online')
@section('lede')@if(!empty($staffConnected))You're on the staff WiFi at {{ $propertyName }}.@else Enjoy the WiFi at {{ $propertyName }}.@if($design['hasContent']) Everything you need for your stay is right here.@endif @endif @endsection

@section('connect')
    <div class="sp-done">
        <span class="sp-done__icon">@include('portal.partials.icon', ['name' => 'check', 'size' => 30, 'stroke' => 2.4])</span>
        <h2 id="sp-connect-title">You're connected</h2>
        <p>You can close this page and start browsing{{ $design['hasContent'] ? ', or keep it handy for house info and local picks' : '' }}.</p>
        @if(!empty($continueUrl))
            <a class="sp-submit" href="{{ $continueUrl }}">Continue browsing @include('portal.partials.icon', ['name' => 'arrow', 'size' => 18])</a>
        @endif
        @if($design['content']['review'])
            <a class="sp-pill sp-pill--ghost" href="{{ $design['content']['review']['url'] }}" target="_blank" rel="noopener">@include('portal.partials.icon', ['name' => 'star', 'size' => 16]) Review {{ $propertyName }} on Google</a>
        @endif
    </div>
@endsection
@endif

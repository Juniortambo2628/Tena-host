@php
    $title = "You're connected";
    $page = 'connected';
@endphp
@extends('portal.layout')

@section('title', $propertyName.': connected')
@section('eyebrow', 'Online')
@section('lede')Enjoy the WiFi at {{ $propertyName }}.@if($design['hasContent']) Everything you need for your stay is right here.@endif @endsection

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

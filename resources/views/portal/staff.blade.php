@php
    $old = $old ?? [];
    $fieldError = fn ($key) => isset($errors) ? $errors->first($key) : null;
    $domain = ltrim(strtolower((string) ($property?->staff_email_domain ?? '')), '@');
    $title = 'Staff WiFi';
    $page = 'staff';
@endphp
@extends('portal.layout')

@section('title', $propertyName.' staff WiFi')
@section('eyebrow', 'Staff WiFi')
@section('lede')Sign in with your work details to get online at {{ $propertyName }}.@endsection

@section('connect')
    <div class="sp-connect__head">
        <span class="sp-badge-icon">@include('portal.partials.icon', ['name' => 'wifi', 'size' => 22])</span>
        <div>
            <h2 id="sp-connect-title">Staff sign-in</h2>
            <p class="sp-connect__sub">{{ $params['ssid'] ?? $propertyName.' staff WiFi' }}</p>
        </div>
    </div>

    @if(!empty($error))
        <div class="sp-error" role="alert">{{ $error }}</div>
    @endif

    <form method="POST" action="{{ route('portal.connect') }}" novalidate>
        @csrf
        @foreach(['id', 'ap', 't', 'ssid', 'url'] as $param)
            <input type="hidden" name="{{ $param }}" value="{{ $params[$param] }}">
        @endforeach

        <label class="sp-field">
            <span>Your name</span>
            <input type="text" name="staff_name" value="{{ $old['staff_name'] ?? '' }}" autocomplete="name" required @if($fieldError('staff_name')) aria-invalid="true" @endif>
            @if($fieldError('staff_name'))<small class="sp-field-error">{{ $fieldError('staff_name') }}</small>@endif
        </label>

        <label class="sp-field">
            <span>{{ $domain ? 'Work email' : 'Staff ID or work email' }}</span>
            <input type="{{ $domain ? 'email' : 'text' }}" name="staff_id" value="{{ $old['staff_id'] ?? '' }}" @if($domain) placeholder="{{ 'you@'.$domain }}" autocomplete="email" @else autocomplete="off" @endif required @if($fieldError('staff_id')) aria-invalid="true" @endif>
            @if($fieldError('staff_id'))<small class="sp-field-error">{{ $fieldError('staff_id') }}</small>@endif
        </label>

        <p class="sp-staff-note">Used only to give you network access at work. You won't get marketing messages.</p>

        <button class="sp-submit" type="submit" @disabled(!$canConnect)>
            @include('portal.partials.icon', ['name' => 'wifi', 'size' => 18])<span>Connect to staff WiFi</span>
        </button>
    </form>
@endsection

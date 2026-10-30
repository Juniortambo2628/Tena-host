@php
    $old = $old ?? [];
    $fieldError = fn ($key) => isset($errors) ? $errors->first($key) : null;
    // Show the number without its +254 prefix (the prefix is drawn separately).
    $oldPhone = preg_replace('/^\+?254/', '', (string) ($old['phone'] ?? ''));
    $title = $returningGuest ? 'Welcome back, '.$returningGuest->first_name.'!' : ($property ? null : 'Welcome');
    $page = 'splash';
@endphp
@extends('portal.layout')

@section('title', $propertyName.' WiFi')

@section('lede')@if($returningGuest)Tap below to get back online at {{ $propertyName }}.@elseif($property){{ $design['message'] }}@else{{ !empty($params['ssid']) ? "You're connecting to ".$params['ssid'].'.' : '' }} Please ask your host to link this access point to the property.@endif @endsection

@section('connect')
    <div class="sp-connect__head">
        <span class="sp-badge-icon">@include('portal.partials.icon', ['name' => 'wifi', 'size' => 22])</span>
        <div>
            <h2 id="sp-connect-title">{{ $returningGuest ? 'One tap to reconnect' : 'Get online' }}</h2>
            <p class="sp-connect__sub">{{ !empty($params['ssid']) ? $params['ssid'] : $propertyName.' guest WiFi' }}</p>
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

        @if($property && !$returningGuest)
            <label class="sp-field">
                <span>First name</span>
                <input type="text" name="first_name" value="{{ $old['first_name'] ?? '' }}" autocomplete="given-name" required @if($fieldError('first_name')) aria-invalid="true" @endif>
                @if($fieldError('first_name'))<small class="sp-field-error">{{ $fieldError('first_name') }}</small>@endif
            </label>

            <label class="sp-field">
                <span>WhatsApp number</span>
                <span class="sp-tel">
                    <span class="sp-tel-prefix">+254</span>
                    <input type="tel" name="phone" value="{{ $oldPhone }}" inputmode="tel" autocomplete="tel-national" placeholder="7XX XXX XXX" required @if($fieldError('phone')) aria-invalid="true" @endif>
                </span>
                @if($fieldError('phone'))<small class="sp-field-error">{{ $fieldError('phone') }}</small>@endif
            </label>

            @if($design['fields']['email'])
                <label class="sp-field">
                    <span>Email address <em>(optional)</em></span>
                    <input type="email" name="email" value="{{ $old['email'] ?? '' }}" autocomplete="email" @if($fieldError('email')) aria-invalid="true" @endif>
                    @if($fieldError('email'))<small class="sp-field-error">{{ $fieldError('email') }}</small>@endif
                </label>
            @endif

            @if($design['fields']['birthday'])
                <div class="sp-field">
                    <span>Birthday <em>(optional, for a treat on your day)</em></span>
                    <span class="sp-birthday">
                        <select name="birthday_day" aria-label="Day">
                            <option value="">Day</option>
                            @for($d = 1; $d <= 31; $d++)
                                <option value="{{ $d }}" @selected((int) ($old['birthday_day'] ?? 0) === $d)>{{ $d }}</option>
                            @endfor
                        </select>
                        <select name="birthday_month" aria-label="Month">
                            <option value="">Month</option>
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" @selected((int) ($old['birthday_month'] ?? 0) === $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                            @endforeach
                        </select>
                    </span>
                    @if($fieldError('birthday_day') ?: $fieldError('birthday_month'))<small class="sp-field-error">Pick both a day and a month.</small>@endif
                </div>
            @endif

            <label class="sp-check">
                <input type="checkbox" name="consent" value="1" required @checked(!empty($old['consent']))>
                <span>{{ $consentText }} <a href="{{ url('/privacy') }}" target="_blank" rel="noopener">Read it</a>.</span>
            </label>
            @if($fieldError('consent'))<small class="sp-field-error">{{ $fieldError('consent') }}</small>@endif

            <label class="sp-check">
                <input type="checkbox" name="marketing_opt_in" value="1" @checked(!empty($old['marketing_opt_in']))>
                <span>{{ rtrim($design['optIn'], '.') }}.</span>
            </label>
        @endif

        @if(!$canConnect && empty($preview) && $property)
            <p class="sp-error">Join the guest WiFi network to open this page with your device's connection details.</p>
        @endif
        <button class="sp-submit" type="submit" @disabled(!$canConnect) onclick="var b=this;setTimeout(function(){b.disabled=true;b.querySelector('span').textContent='Connecting…';},40);">
            @include('portal.partials.icon', ['name' => 'wifi', 'size' => 18])<span>{{ !empty($preview) ? 'Preview only' : ($design['button'] ?: 'Connect to WiFi') }}</span>
        </button>
    </form>
@endsection

@php
    $old = $old ?? [];
    $fieldError = fn ($key) => isset($errors) ? $errors->first($key) : null;
    // Show the number without its +254 prefix (the prefix is drawn separately).
    $oldPhone = preg_replace('/^\+?254/', '', (string) ($old['phone'] ?? ''));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $propertyName }} WiFi</title>
    @include('portal.partials.styles')
</head>
<body>
    <main class="card">
        @if($logoUrl)
            <img class="logo-img" src="{{ $logoUrl }}" alt="{{ $propertyName }}">
        @else
            <div class="logo" aria-hidden="true">{{ mb_substr($propertyName, 0, 1) }}</div>
        @endif

        @if(!empty($error))
            <div class="error" role="alert">{{ $error }}</div>
        @endif

        <form method="POST" action="{{ route('portal.connect') }}" novalidate>
            @csrf
            @foreach(['id', 'ap', 't', 'ssid', 'url'] as $param)
                <input type="hidden" name="{{ $param }}" value="{{ $params[$param] }}">
            @endforeach

            @if($returningGuest)
                {{-- Known device on this property: one tap. --}}
                <h1>Welcome back, {{ $returningGuest->first_name }}!</h1>
                <p class="sub">Tap below to get back online at {{ $propertyName }}.</p>
            @elseif($property)
                <h1>Welcome to {{ $propertyName }}</h1>
                <p class="sub">Connect to the WiFi and stay in touch.</p>

                <label class="field">
                    <span>First name</span>
                    <input type="text" name="first_name" value="{{ $old['first_name'] ?? '' }}" autocomplete="given-name" required @if($fieldError('first_name')) aria-invalid="true" @endif>
                    @if($fieldError('first_name'))<small class="field-error">{{ $fieldError('first_name') }}</small>@endif
                </label>

                <label class="field">
                    <span>WhatsApp number</span>
                    <span class="tel">
                        <span class="tel-prefix">+254</span>
                        <input type="tel" name="phone" value="{{ $oldPhone }}" inputmode="tel" autocomplete="tel-national" placeholder="7XX XXX XXX" required @if($fieldError('phone')) aria-invalid="true" @endif>
                    </span>
                    @if($fieldError('phone'))<small class="field-error">{{ $fieldError('phone') }}</small>@endif
                </label>

                <label class="field">
                    <span>Email address <em>(optional)</em></span>
                    <input type="email" name="email" value="{{ $old['email'] ?? '' }}" autocomplete="email" @if($fieldError('email')) aria-invalid="true" @endif>
                    @if($fieldError('email'))<small class="field-error">{{ $fieldError('email') }}</small>@endif
                </label>

                <label class="check">
                    <input type="checkbox" name="consent" value="1" required>
                    <span>{{ $consentText }} <a href="{{ url('/privacy') }}" target="_blank" rel="noopener">Read it</a>.</span>
                </label>
                @if($fieldError('consent'))<small class="field-error">{{ $fieldError('consent') }}</small>@endif

                <label class="check">
                    <input type="checkbox" name="marketing_opt_in" value="1" @checked(!empty($old['marketing_opt_in']))>
                    <span>Send me offers and early access to direct deals.</span>
                </label>
            @else
                <h1>Welcome</h1>
                <p class="sub">
                    @if(!empty($params['ssid']))
                        You're connecting to <strong>{{ $params['ssid'] }}</strong>.
                    @endif
                    Tap below to get online.
                </p>
            @endif

            <button type="submit" onclick="var b=this;setTimeout(function(){b.disabled=true;b.innerText='Connecting…';},40);">
                Connect to WiFi
            </button>
        </form>

        <p class="fine">Powered by {{ \App\Support\Brand::name() }}</p>
    </main>
</body>
</html>

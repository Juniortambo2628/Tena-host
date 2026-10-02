<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $propertyName }} — WiFi</title>
    <style>
        :root { --brand: #1f6feb; --brand-dark: #1a5fd0; --ink: #0f172a; --muted: #64748b; --bg: #f1f5f9; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            width: 100%;
            max-width: 420px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.12);
            padding: 32px 28px 28px;
            text-align: center;
        }
        .logo {
            width: 64px; height: 64px;
            margin: 0 auto 18px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--brand), #5b9bff);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 30px;
        }
        h1 { font-size: 22px; margin: 0 0 6px; }
        .sub { color: var(--muted); font-size: 15px; margin: 0 0 24px; }
        .ssid { font-weight: 600; color: var(--ink); }
        button {
            width: 100%;
            border: 0;
            border-radius: 12px;
            background: var(--brand);
            color: #fff;
            font-size: 17px;
            font-weight: 600;
            padding: 15px 20px;
            cursor: pointer;
            transition: background .15s ease;
            -webkit-appearance: none;
        }
        button:hover { background: var(--brand-dark); }
        button:disabled { opacity: .6; cursor: default; }
        .error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 14px;
            margin: 0 0 18px;
            text-align: left;
        }
        .fine { color: var(--muted); font-size: 12px; margin-top: 18px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">&#128246;</div>
        <h1>Welcome to {{ $propertyName }}</h1>
        <p class="sub">
            @if(!empty($params['ssid']))
                You're connecting to <span class="ssid">{{ $params['ssid'] }}</span>.
            @endif
            Tap below to get online.
        </p>

        @if(!empty($error))
            <div class="error">{{ $error }}</div>
        @endif

        <form method="POST" action="{{ route('portal.connect') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $params['id'] }}">
            <input type="hidden" name="ap" value="{{ $params['ap'] }}">
            <input type="hidden" name="t" value="{{ $params['t'] }}">
            <input type="hidden" name="ssid" value="{{ $params['ssid'] }}">
            <input type="hidden" name="url" value="{{ $params['url'] }}">
            <button type="submit" onclick="var b=this;setTimeout(function(){b.disabled=true;b.innerText='Connecting…';},40);">
                Connect to WiFi
            </button>
        </form>

        <p class="fine">By connecting you agree to our fair-use terms. Enjoy your stay!</p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $propertyName }} — Connected</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff; width: 100%; max-width: 420px;
            border-radius: 20px; box-shadow: 0 10px 40px rgba(15,23,42,.12);
            padding: 36px 28px; text-align: center;
        }
        .check {
            width: 72px; height: 72px; margin: 0 auto 18px; border-radius: 50%;
            background: #dcfce7; color: #16a34a;
            display: flex; align-items: center; justify-content: center; font-size: 38px;
        }
        h1 { font-size: 22px; margin: 0 0 8px; }
        p { color: #64748b; font-size: 15px; margin: 0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="check">&#10003;</div>
        <h1>You're connected</h1>
        <p>Enjoy the WiFi at {{ $propertyName }}. You can close this page and start browsing.</p>
    </div>
</body>
</html>

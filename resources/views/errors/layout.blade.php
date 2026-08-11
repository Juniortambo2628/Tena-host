<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $status ?? 'Error' }} — Tena</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Figtree', system-ui, sans-serif; background: #FDFCF7; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .error-card { text-align: center; max-width: 28rem; width: 100%; }
        .error-badge { width: 5rem; height: 5rem; border-radius: 1.5rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; background: rgba(255,211,0,0.15); }
        .error-code { font-size: 2.5rem; font-weight: 800; color: #FFD300; }
        .error-title { font-size: 1.5rem; font-weight: 700; color: #1b1b1b; margin-bottom: 0.5rem; letter-spacing: -0.5px; }
        .error-msg { color: #666; font-size: 0.95rem; line-height: 1.6; margin-bottom: 2rem; }
        .error-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .btn-home { background: #1b1b1b; color: #fff; padding: 0.75rem 2rem; border-radius: 0.75rem; font-weight: 700; font-size: 0.875rem; text-decoration: none; transition: all 0.2s; border: none; cursor: pointer; }
        .btn-home:hover { background: #333; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn-back { color: #666; padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 600; font-size: 0.875rem; text-decoration: none; transition: color 0.2s; background: none; border: none; cursor: pointer; }
        .btn-back:hover { color: #1b1b1b; }
        .error-footer { margin-top: 3rem; padding-top: 1.5rem; border-top: 1px solid rgba(0,0,0,0.05); }
        .error-footer p { font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-badge">
            <span class="error-code">{{ $status ?? '!' }}</span>
        </div>
        <h1 class="error-title">{{ $title ?? 'Something went wrong' }}</h1>
        <p class="error-msg">{{ $message ?? 'An unexpected error occurred.' }}</p>
        <div class="error-actions">
            <a href="/" class="btn-home">Back to Home</a>
            <button onclick="window.location.reload()" class="btn-back">Try Again</button>
        </div>
        <div class="error-footer">
            <p>Tena — Built by Superhosts for Superhosts</p>
        </div>
    </div>
</body>
</html>

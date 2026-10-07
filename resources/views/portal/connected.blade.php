<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $propertyName }}: connected</title>
    @include('portal.partials.styles')
</head>
<body>
    <main class="card">
        <div class="done" aria-hidden="true">&#10003;</div>
        <h1>You're connected</h1>
        <p class="sub">Enjoy the WiFi at {{ $propertyName }}. You can close this page and start browsing.</p>
        <p class="fine">Powered by TenaFi</p>
    </main>
</body>
</html>

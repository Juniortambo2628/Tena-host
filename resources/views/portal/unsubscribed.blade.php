<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Unsubscribed</title>
    @include('partials.favicon')
    @include('portal.partials.styles')
</head>
<body>
    <main class="card">
        <div class="done" aria-hidden="true">&#10003;</div>
        <h1>You're unsubscribed</h1>
        <p class="sub">You won't receive offers from {{ $propertyName }} by email any more. Messages about a stay you book still reach you.</p>
        <p class="fine">Powered by {{ \App\Support\Brand::name() }}</p>
    </main>
</body>
</html>

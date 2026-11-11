{{-- "It wasn't me": cancel a property transfer from the owner's email, without signing in (PropertyTransferController@cancelLink). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $done ? 'Transfer cancelled' : 'Cancel this transfer?' }}</title>
    @include('partials.favicon')
    @include('portal.partials.styles')
</head>
<body>
    <main class="card">
        @if($done)
            <div class="done" aria-hidden="true">&#10003;</div>
            <h1>Transfer cancelled</h1>
            <p class="sub">{{ $propertyName }} stays in your account and nothing has moved. If you didn't offer this transfer, change your TenaFi password now.</p>
        @elseif(! $open)
            <h1>This transfer can't be cancelled any more</h1>
            <p class="sub">It has already been {{ $statusText }}. If something is wrong, contact TenaFi support.</p>
        @else
            <h1>Cancel this transfer?</h1>
            <p class="sub">{{ $propertyName }} was offered to {{ $toEmail }}. Cancelling keeps it in your account and nothing moves.</p>
            {{-- A button, not the link itself: email security scanners open links, and mustn't cancel by doing so. --}}
            <form method="POST" action="{{ $action }}">
                @csrf
                <button type="submit">Cancel the transfer</button>
            </form>
        @endif
        <p class="fine">@include('portal.partials.powered')</p>
    </main>
</body>
</html>

{{--
    "Unsubscribe?" with one button. The link in an email only opens this page;
    the guest is unsubscribed when they press the button. Email security
    scanners that open every link can't unsubscribe anyone by accident.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Unsubscribe</title>
    @include('partials.favicon')
    @include('portal.partials.styles')
</head>
<body>
    <main class="card">
        <h1>Unsubscribe?</h1>
        <p class="sub">You won't receive offers or review requests from {{ $propertyName }} by email any more.</p>
        <form method="POST" action="{{ $action }}">
            @csrf
            <button type="submit">Unsubscribe</button>
        </form>
        <p class="fine">@include('portal.partials.powered')</p>
    </main>
</body>
</html>

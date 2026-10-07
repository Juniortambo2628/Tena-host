<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $property->name }}</title>
    @include('portal.partials.styles')
    <style>
        .card { text-align: left; }
        .venue-head { text-align: center; }
        h2 { font-size: 15px; margin: 24px 0 8px; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
        .list { list-style: none; margin: 0; padding: 0; }
        .list li { padding: 12px 0; border-bottom: 1px solid #EEE; display: flex; justify-content: space-between; gap: 12px; }
        .list li:last-child { border-bottom: 0; }
        .list small { display: block; color: var(--muted); font-size: 13px; margin-top: 2px; }
        .price { font-weight: 700; white-space: nowrap; }
        .offer { background: #FFF8D6; border-radius: 12px; padding: 12px 14px; margin-bottom: 8px; font-weight: 600; }
    </style>
</head>
<body>
    <main class="card">
        <div class="venue-head">
            @if($logoUrl)
                <img class="logo-img" src="{{ $logoUrl }}" alt="{{ $property->name }}">
            @else
                <div class="logo" aria-hidden="true">{{ mb_substr($property->name, 0, 1) }}</div>
            @endif
            <h1>{{ $property->name }}</h1>
            <p class="sub">You're connected. Here's what's on today.</p>
        </div>

        @if($offers)
            <h2>Offers</h2>
            @foreach($offers as $offer)
                <div class="offer">{{ $offer }}</div>
            @endforeach
        @endif

        @if($menu->isNotEmpty())
            <h2>Menu</h2>
            <ul class="list">
                @foreach($menu as $item)
                    <li>
                        <span>{{ $item->name }}@if($item->description)<small>{{ $item->description }}</small>@endif</span>
                        @if($item->price > 0)<span class="price">KES {{ number_format($item->price) }}</span>@endif
                    </li>
                @endforeach
            </ul>
        @endif

        @if($events)
            <h2>Events</h2>
            <ul class="list">
                @foreach($events as $event)
                    <li>{{ $event }}</li>
                @endforeach
            </ul>
        @endif

        @if(! $offers && $menu->isEmpty() && ! $events)
            <p class="sub">Enjoy your visit!</p>
        @endif

        <p class="fine">Powered by {{ \App\Support\Brand::name() }}</p>
    </main>
</body>
</html>

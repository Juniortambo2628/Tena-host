@php
    /** @var \App\Models\Event $event */
    $sponsor = $event->sponsor['name'] ?? 'Sponsor';
    $dates = $event->starts_on->equalTo($event->ends_on)
        ? $event->starts_on->format('j M Y')
        : $event->starts_on->format('j M').' – '.$event->ends_on->format('j M Y');
    $max = max(1, collect($report['topAnswers'])->max('count') ?? 1);
    $rate = fn ($part, $whole) => $whole > 0 ? round($part / $whole * 100).'%' : '—';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $sponsor }} · {{ $event->name }} · Sponsor report</title>
    <style>
        :root { --ink: #1b1b1b; --muted: #6b6b6b; --line: #ecebe6; --brand: #FFD300; --bg: #fdfcf7; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: var(--ink); background: var(--bg); }
        main { max-width: 760px; margin: 0 auto; padding: 32px 16px 48px; }
        .eyebrow { font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); }
        h1 { font-size: 30px; line-height: 1.15; margin: 8px 0 6px; }
        .meta { color: var(--muted); font-size: 15px; margin: 0 0 24px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        @media (min-width: 640px) { .grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 20px; padding: 18px; }
        .card b { display: block; font-size: 28px; line-height: 1; }
        .card span { display: block; font-size: 13px; font-weight: 700; margin-top: 8px; }
        .card small { display: block; font-size: 12px; color: var(--muted); margin-top: 4px; }
        h2 { font-size: 18px; margin: 32px 0 12px; }
        .answers { background: #fff; border: 1px solid var(--line); border-radius: 20px; padding: 18px; }
        .answer { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 4px 12px; margin-bottom: 12px; font-size: 14px; }
        .answer:last-child { margin-bottom: 0; }
        .bar { grid-column: 1 / -1; height: 8px; border-radius: 999px; background: var(--line); overflow: hidden; }
        .bar i { display: block; height: 100%; background: var(--brand); }
        .empty { color: var(--muted); font-size: 14px; margin: 0; }
        footer { margin-top: 32px; font-size: 12px; color: var(--muted); }
    </style>
</head>
<body>
<main>
    <div class="eyebrow">Sponsor report</div>
    <h1>{{ $sponsor }} at {{ $event->name }}</h1>
    <p class="meta">{{ $dates }} · {{ $event->property?->name }}</p>

    <div class="grid">
        <div class="card"><b>{{ number_format($report['views']) }}</b><span>Saw the sponsor screen</span><small>WiFi page views while the event ran</small></div>
        <div class="card"><b>{{ number_format($report['attendees']) }}</b><span>Attendees signed in</span><small>One per person, all days</small></div>
        <div class="card"><b>{{ number_format($report['answered']) }}</b><span>Answered the question</span><small>{{ $rate($report['answered'], $report['attendees']) }} of attendees</small></div>
        <div class="card"><b>{{ number_format($report['clicks']) }}</b><span>Tapped the offer</span><small>{{ $rate($report['clicks'], $report['views']) }} of views</small></div>
    </div>

    @if($event->question)
        <h2>{{ $event->question }}</h2>
        <div class="answers">
            @forelse($report['topAnswers'] as $row)
                <div class="answer">
                    <span>{{ $row['answer'] }}</span><b>{{ $row['count'] }}</b>
                    <div class="bar"><i style="width: {{ round($row['count'] / $max * 100) }}%"></i></div>
                </div>
            @empty
                <p class="empty">No answers yet.</p>
            @endforelse
        </div>
    @endif

    <footer>Counted by {{ \App\Support\Brand::name() }} from the event's WiFi sign-in. Updated {{ now()->format('j M Y, H:i') }}.</footer>
</main>
</body>
</html>

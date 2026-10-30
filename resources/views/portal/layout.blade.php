@php
    /** @var array $design */
    $c = $design['content'];
    $initial = mb_strtoupper(mb_substr($propertyName, 0, 1));
    $tabs = array_filter([
        'welcome' => ($c['welcome'] || $c['review']) ? 'Welcome' : null,
        'house' => ($c['rules'] || $c['emergency']) ? 'House' : null,
        'nearby' => $c['nearby'] ? 'Nearby' : null,
        'offers' => $c['offers'] ? 'Offers' : null,
        'stays' => $c['stays'] ? 'Stays' : null,
    ]);
    $paneTitles = [
        'welcome' => ['A note for you', 'Welcome'],
        'house' => ['Good to know', 'House info'],
        'nearby' => ['Our local picks', 'Nearby'],
        'offers' => ['Coming up', 'Offers for you'],
        'stays' => ['Stay with us again', 'More from '.$propertyName],
    ];
    // Highlight the property name inside the heading, the way the templates accent one phrase.
    $highlight = function (string $text) use ($propertyName) {
        $safe = e($text);
        $name = e($propertyName);

        return $name !== '' && str_contains($safe, $name) ? str_replace($name, '<span class="sp-hl">'.$name.'</span>', $safe) : $safe;
    };
    $heading = $title ?? ($design['heading'] ?: 'Welcome to '.$propertyName);
    // Business templates draw their own header band (colour, decoration, font) from the catalogue.
    $look = $design['look'] ?? [];
    $band = ! in_array($design['template'], \App\Support\SplashDesign::ORIGINAL, true);
    $bodyClass = implode(' ', array_filter([
        'sp', 'splash--'.$design['template'], $tabs ? '' : 'sp--solo',
        $band ? 'sp--band sp--'.($look['theme'] ?? 'light').' sp-font--'.($look['font'] ?? 'sans') : '',
    ]));
    $bodyStyle = '--accent: '.$design['accent'].'; --accent-ink: '.$design['ink'].';'
        .($band ? ' --band: '.($look['header'] ?? '#222').'; --band-ink: '.($look['headerInk'] ?? '#fff').';'.(isset($look['page']) ? ' --page: '.$look['page'].';' : '') : '');
    $tiles = $design['tiles'] ?? [];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="{{ $design['accent'] }}">
    <title>@yield('title')</title>
    @include('partials.favicon')
    @include('portal.partials.splash-styles')
</head>
<body class="{{ $bodyClass }}" style="{{ $bodyStyle }}">
    @if(!empty($preview) && empty($embedded))
        <p class="sp-preview-notice">Design preview · This page does not connect a device.</p>
    @endif

    <div class="sp-app">
        <div class="sp-mark" aria-hidden="true">{{ $initial }}</div>

        <div class="sp-side" data-pane="wifi" id="pane-wifi" role="tabpanel" aria-label="Connect to WiFi">
            <div class="sp-photo {{ $splashImageUrl ? '' : 'sp-photo--empty' }}" data-sp-cover @if($splashImageUrl) style="background-image: url('{{ $splashImageUrl }}')" @endif>
                @if($band && !empty($look['decor']))<i class="sp-decor sp-decor--{{ $look['decor'] }}" aria-hidden="true"></i>@endif
            </div>

            <div class="sp-brand" data-sp-logo>
                @if($logoUrl)
                    <img class="sp-brand__logo" src="{{ $logoUrl }}" alt="{{ $propertyName }}">
                @else
                    <span class="sp-brand__mono" aria-hidden="true">{{ $initial }}</span>
                @endif
                <span class="sp-brand__name">{{ $propertyName }}</span>
            </div>

            <header class="sp-hero">
                <p class="sp-eyebrow">@yield('eyebrow', 'Guest WiFi'){{ !empty($params['ssid']) ? ' · '.$params['ssid'] : '' }}</p>
                <h1 class="sp-title">{!! $highlight($heading) !!}</h1>
                <p class="sp-lede">@yield('lede', $design['message'])</p>
            </header>

            <section class="sp-connect" aria-labelledby="sp-connect-title">
                {{-- Business pages: three quick links above the sign-up form. --}}
                @if($tiles && ($page ?? null) === 'splash')
                    <nav class="sp-tiles" aria-label="Quick links">
                        @foreach($tiles as $tile)
                            @if($tile['url'])
                                <a class="sp-tile" href="{{ $tile['url'] }}" target="_blank" rel="noopener">@include('portal.partials.icon', ['name' => 'tile-'.$tile['icon'], 'size' => 22])<span>{{ $tile['label'] }}</span></a>
                            @elseif($tile['goto'])
                                <a class="sp-tile" href="#pane-{{ $tile['goto'] }}" data-goto="{{ $tile['goto'] }}">@include('portal.partials.icon', ['name' => 'tile-'.$tile['icon'], 'size' => 22])<span>{{ $tile['label'] }}</span></a>
                            @else
                                <span class="sp-tile">@include('portal.partials.icon', ['name' => 'tile-'.$tile['icon'], 'size' => 22])<span>{{ $tile['label'] }}</span></span>
                            @endif
                        @endforeach
                    </nav>
                @endif
                @yield('connect')
            </section>
        </div>

        @if($tabs)
            <div class="sp-board">
                <nav class="sp-tabs" role="tablist" aria-label="Guest information">
                    <button type="button" class="sp-tab sp-tab--wifi" role="tab" data-tab="wifi" aria-controls="pane-wifi" aria-selected="true">
                        @include('portal.partials.icon', ['name' => 'wifi'])<span>WiFi</span>
                    </button>
                    @foreach($tabs as $key => $label)
                        <button type="button" class="sp-tab" role="tab" data-tab="{{ $key }}" aria-controls="pane-{{ $key }}" aria-selected="false">
                            @include('portal.partials.icon', ['name' => $key])<span>{{ $label }}</span>
                        </button>
                    @endforeach
                </nav>

                <div class="sp-panes">
                    @foreach($tabs as $key => $label)
                        <section class="sp-pane sp-pane--{{ $key }}" data-pane="{{ $key }}" id="pane-{{ $key }}" role="tabpanel" aria-labelledby="pane-{{ $key }}-title">
                            <div class="sp-pane__head">
                                <div>
                                    <p class="sp-pane__kicker">{{ $paneTitles[$key][0] }}</p>
                                    <h2 class="sp-pane__title" id="pane-{{ $key }}-title">{{ $paneTitles[$key][1] }}</h2>
                                </div>
                                @if(in_array($key, ['nearby', 'offers', 'stays'], true))
                                    <div class="sp-rail-ctl">
                                        <button type="button" data-rail-prev aria-controls="rail-{{ $key }}" aria-label="Previous">@include('portal.partials.icon', ['name' => 'prev'])</button>
                                        <button type="button" data-rail-next aria-controls="rail-{{ $key }}" aria-label="Next">@include('portal.partials.icon', ['name' => 'next'])</button>
                                    </div>
                                @endif
                            </div>
                            @include('portal.partials.pane-'.$key, ['c' => $c])
                        </section>
                    @endforeach
                </div>
            </div>
        @endif

        <p class="sp-powered">Powered by {{ \App\Support\Brand::name() }}</p>
    </div>

    @if($tabs)
        <script>
            (function () {
                var d = document, tabs = [].slice.call(d.querySelectorAll('[data-tab]'));
                var panes = [].slice.call(d.querySelectorAll('[data-pane]'));
                var wide = window.matchMedia('(min-width: 1024px)');
                var first = tabs[1] ? tabs[1].getAttribute('data-tab') : 'wifi';
                var current = 'wifi';
                d.documentElement.className += ' sp-js';

                function show(name, scroll) {
                    current = name;
                    // Wide screens keep the connect card beside the content, so "WiFi" means the first tab.
                    var active = wide.matches && name === 'wifi' ? first : name;
                    tabs.forEach(function (t) {
                        var on = t.getAttribute('data-tab') === active;
                        t.setAttribute('aria-selected', on ? 'true' : 'false');
                        t.tabIndex = on ? 0 : -1;
                    });
                    panes.forEach(function (p) {
                        var key = p.getAttribute('data-pane');
                        p.classList.toggle('is-active', key === active || (wide.matches && key === 'wifi'));
                    });
                    d.body.setAttribute('data-tab', active);
                    if (scroll && !wide.matches) window.scrollTo(0, 0);
                }

                tabs.forEach(function (t, i) {
                    t.addEventListener('click', function () { show(t.getAttribute('data-tab'), true); });
                    t.addEventListener('keydown', function (e) {
                        var step = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
                        if (!step) return;
                        var visible = tabs.filter(function (b) { return b.offsetParent !== null; });
                        var next = visible[(visible.indexOf(t) + step + visible.length) % visible.length];
                        next.focus();
                        next.click();
                    });
                });
                d.addEventListener('click', function (e) {
                    var link = e.target.closest && e.target.closest('[data-goto]');
                    if (link) { e.preventDefault(); show(link.getAttribute('data-goto'), true); }
                });
                [].forEach.call(d.querySelectorAll('[data-rail-prev],[data-rail-next]'), function (b) {
                    b.addEventListener('click', function () {
                        var rail = d.getElementById(b.getAttribute('aria-controls'));
                        if (rail) rail.scrollBy({ left: (b.hasAttribute('data-rail-next') ? 1 : -1) * rail.clientWidth * 0.85, behavior: 'smooth' });
                    });
                });
                if (wide.addEventListener) wide.addEventListener('change', function () { show(current); });
                else if (wide.addListener) wide.addListener(function () { show(current); });
                show('wifi');
            })();
        </script>
    @endif
</body>
</html>

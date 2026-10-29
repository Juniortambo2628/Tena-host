<div class="sp-rail" id="rail-offers" tabindex="0" aria-label="Offers">
    @foreach($c['offers'] as $offer)
        <article class="sp-card sp-card--offer">
            <span class="sp-card__icon">@include('portal.partials.icon', ['name' => 'offers'])</span>
            @if(!empty($offer['when']))<span class="sp-chip">{{ $offer['when'] }}</span>@endif
            <h3>{{ $offer['title'] }}</h3>
            @if(!empty($offer['detail']))<p>{{ $offer['detail'] }}</p>@endif
        </article>
    @endforeach
    @if(($page ?? 'splash') === 'splash')
        <a class="sp-card sp-card--optin" href="#pane-wifi" data-goto="wifi">
            <strong>Be first to hear about these</strong>
            <span>Tick “Send me offers” when you connect and we'll message you when they go live.</span>
            <span class="sp-pill">Connect &amp; opt in @include('portal.partials.icon', ['name' => 'next', 'size' => 16])</span>
        </a>
    @endif
</div>

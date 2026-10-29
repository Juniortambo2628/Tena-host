<div class="sp-grid sp-grid--welcome {{ $c['welcome'] && $c['review'] ? '' : 'sp-grid--one' }}">
    @if($c['welcome'])
        <article class="sp-card sp-card--note">
            <p class="sp-card__kicker">From your host</p>
            <div class="sp-note">{!! nl2br(e($c['welcome']['note'])) !!}</div>
            <footer class="sp-signoff">
                <span class="sp-signoff__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($c['welcome']['host'] ?: $propertyName, 0, 1)) }}</span>
                <span>
                    <strong>{{ $c['welcome']['host'] ?: 'The '.$propertyName.' team' }}</strong>
                    <small>{{ $propertyName }}</small>
                </span>
            </footer>
        </article>
    @endif

    @if($c['review'])
        <article class="sp-card sp-card--review">
            <div class="sp-stars" aria-label="Five stars">
                @for($i = 0; $i < 5; $i++)@include('portal.partials.icon', ['name' => 'star', 'fill' => true, 'size' => 22, 'stroke' => 1])@endfor
            </div>
            <h3>{{ $c['review']['prompt'] }}</h3>
            <a class="sp-pill" href="{{ $c['review']['url'] }}" target="_blank" rel="noopener">Leave a Google review @include('portal.partials.icon', ['name' => 'arrow', 'size' => 16])</a>
            <small>Opens once you're online.</small>
        </article>
    @endif

    @if($c['offers'])
        <a class="sp-card sp-card--teaser" href="#pane-offers" data-goto="offers">
            <span class="sp-card__icon">@include('portal.partials.icon', ['name' => 'offers'])</span>
            <span><small>Offer</small><strong>{{ $c['offers'][0]['title'] }}</strong></span>
            @include('portal.partials.icon', ['name' => 'next', 'class' => 'sp-teaser__go'])
        </a>
    @endif
</div>

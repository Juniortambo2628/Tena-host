@php
    $categoryNames = ['food' => 'Eat', 'drink' => 'Drink', 'sights' => 'See', 'shop' => 'Shop', 'nature' => 'Outdoors', 'transport' => 'Getting around', 'health' => 'Health', 'other' => 'Local tip'];
@endphp
<div class="sp-rail" id="rail-nearby" tabindex="0" aria-label="Nearby recommendations">
    @foreach($c['nearby'] as $place)
        <article class="sp-card sp-card--place sp-cat--{{ $place['category'] }}">
            <div class="sp-place__art">
                @include('portal.partials.icon', ['name' => $place['category'], 'size' => 34, 'stroke' => 1.5])
                <span class="sp-chip">{{ $categoryNames[$place['category']] }}</span>
            </div>
            <div class="sp-place__body">
                <h3>{{ $place['name'] }}</h3>
                @if($place['note'])<p>{{ $place['note'] }}</p>@endif
                <div class="sp-place__foot">
                    @if($place['distance'])<span class="sp-meta">@include('portal.partials.icon', ['name' => 'pin', 'size' => 14]) {{ $place['distance'] }}</span>@endif
                    <a href="{{ $place['url'] }}" target="_blank" rel="noopener">Directions @include('portal.partials.icon', ['name' => 'arrow', 'size' => 14])</a>
                </div>
            </div>
        </article>
    @endforeach
</div>

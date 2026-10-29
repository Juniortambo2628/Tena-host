<div class="sp-rail" id="rail-stays" tabindex="0" aria-label="More places to stay">
    @foreach($c['stays'] as $stay)
        @php $tag = $stay['url'] ? 'a' : 'article'; @endphp
        <{{ $tag }} class="sp-card sp-card--stay {{ $stay['image'] ? '' : 'sp-card--stay-plain' }}" @if($stay['url']) href="{{ $stay['url'] }}" target="_blank" rel="noopener" @endif @if($stay['image']) style="background-image: url('{{ $stay['image'] }}')" @endif>
            @if($stay['url'])<span class="sp-stay__go">@include('portal.partials.icon', ['name' => 'arrow', 'size' => 18])</span>@endif
            <span class="sp-stay__body">
                @if($stay['price'])<span class="sp-chip">{{ $stay['price'] }}</span>@endif
                <strong>{{ $stay['title'] }}</strong>
                @if($stay['detail'])<small>{{ $stay['detail'] }}</small>@endif
            </span>
        </{{ $tag }}>
    @endforeach
</div>

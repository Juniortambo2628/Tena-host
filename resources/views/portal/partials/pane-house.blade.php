<div class="sp-grid sp-grid--house {{ $c['rules'] && $c['emergency'] ? '' : 'sp-grid--one' }}">
    @if($c['rules'])
        <article class="sp-card sp-card--rules">
            <h3 class="sp-card__title">@include('portal.partials.icon', ['name' => 'rules']) House rules</h3>
            <ol class="sp-rules">
                @foreach($c['rules'] as $rule)
                    <li>{{ $rule }}</li>
                @endforeach
            </ol>
        </article>
    @endif

    @if($c['emergency'])
        <article class="sp-card sp-card--emergency">
            <h3 class="sp-card__title">@include('portal.partials.icon', ['name' => 'emergency']) Emergency &amp; help</h3>
            <ul class="sp-calls">
                @foreach($c['emergency'] as $contact)
                    <li>
                        <span><strong>{{ $contact['label'] }}</strong><small>{{ $contact['phone'] }}</small></span>
                        <a class="sp-call" href="tel:{{ $contact['tel'] }}" aria-label="Call {{ $contact['label'] }}">@include('portal.partials.icon', ['name' => 'phone', 'size' => 18])</a>
                    </li>
                @endforeach
            </ul>
        </article>
    @endif
</div>

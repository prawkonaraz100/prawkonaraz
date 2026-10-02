<section class="question-detail__sign" aria-labelledby="question-sign-heading">
    <h2 id="question-sign-heading">Znak drogowy widoczny w pytaniu</h2>
    <div class="question-detail__sign-body">
        @if (! empty($referenceSign['code']))
            <x-public.sign-badge :sign="$referenceSign" variant="detail" />
        @elseif (! empty($referenceSign['image_url']))
            <img src="{{ $referenceSign['image_url'] }}" alt="{{ $referenceSign['alt_text'] ?? 'Znak drogowy' }}" width="80" height="80" class="question-detail__sign-image">
        @endif
        <div>
            <p class="question-detail__sign-title">{{ $referenceSign['title'] }}</p>
            @if (! empty($referenceSign['intro']))
                <p class="question-detail__sign-intro">{{ $referenceSign['intro'] }}</p>
            @endif
            @if (! empty($referenceSign['url']))
                <a href="{{ $referenceSign['url'] }}">{{ $referenceSign['link_label'] ?? 'Zobacz opis znaku' }} <span aria-hidden="true">→</span></a>
            @endif
        </div>
    </div>
</section>

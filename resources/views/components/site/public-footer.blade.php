@php
    $footer = app(\App\Support\PublicFooter::class)->data(auth()->user());
@endphp

<footer class="site-footer">
    <div class="site-footer__shell">
        <div class="site-footer__desktop-compact">
            <div class="site-footer__row site-footer__row--primary">
                <a href="{{ $footer['home_href'] }}" class="site-footer__compact-brand" aria-label="{{ $footer['brand']['aria_label'] }}">
                    <span>Prawko</span><strong>NaRaz</strong>
                </a>
                <span class="site-footer__compact-copy">{{ $footer['copyright'] }}</span>
                <nav class="site-footer__compact-nav" aria-label="Informacje i dokumenty">
                    @foreach ($footer['legal_links'] as $link)
                        <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="site-footer__row site-footer__row--secondary">
                <nav class="site-footer__compact-nav" aria-label="Najważniejsze sekcje serwisu">
                    @foreach ($footer['service_links'] as $link)
                        <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </nav>
                <nav class="site-footer__socials" aria-label="Media społecznościowe">
                    @foreach ($footer['social_links'] as $link)
                        <a href="{{ $link['href'] }}" target="_blank" rel="noopener noreferrer">{{ $link['label'] }}</a>
                    @endforeach
                </nav>
                <span class="site-footer__language">
                    {{ $footer['language'] }}
                    <svg viewBox="0 0 12 8" aria-hidden="true"><path d="m1 1.5 5 5 5-5" /></svg>
                </span>
            </div>
        </div>
    </div>
</footer>

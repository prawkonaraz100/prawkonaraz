@php
    $footer = app(\App\Support\PublicFooter::class)->data(auth()->user());
@endphp

<footer class="site-footer">
    <div class="site-footer__shell">
        <div class="site-footer__main">
            <section class="site-footer__brand" aria-labelledby="site-footer-brand">
                <a
                    id="site-footer-brand"
                    href="{{ $footer['home_href'] }}"
                    class="site-footer__brand-name"
                    aria-label="{{ $footer['brand']['aria_label'] }}"
                >
                    PrawkoNaRaz.pl
                </a>
                <p class="site-footer__description">{{ $footer['description'] }}</p>
                <p class="site-footer__email">
                    Adres e-mail:
                    <a href="mailto:{{ $footer['email'] }}">{{ $footer['email'] }}</a>
                </p>
            </section>
            @foreach ($footer['groups'] as $group)
                <nav class="site-footer__group" aria-labelledby="site-footer-group-{{ $loop->index }}">
                    <h2 id="site-footer-group-{{ $loop->index }}">{{ $group['title'] }}</h2>
                    <ul>
                        @foreach ($group['links'] as $link)
                            <li><a href="{{ $link['href'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach
        </div>
    </div>

    <div class="site-footer__legal-row">
        <div class="site-footer__shell site-footer__bottom-bar">
            <span>{{ $footer['copyright'] }}</span>
            <nav aria-label="Dokumenty prawne">
                @foreach ($footer['legal_links'] as $link)
                    <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                @endforeach
            </nav>
        </div>
    </div>
</footer>

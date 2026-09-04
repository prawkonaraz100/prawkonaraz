@php
    $footer = app(\App\Support\PublicFooter::class)->data(auth()->user());
@endphp

<footer class="site-footer">
    <div class="site-footer__shell">
        <div class="site-footer__desktop-compact">
            <a href="{{ $footer['home_href'] }}" class="site-footer__compact-brand" aria-label="{{ $footer['brand']['aria_label'] }}">
                <img
                    src="/images/site-header-logo-20260728.png"
                    alt=""
                    class="site-footer__compact-logo"
                    width="1150"
                    height="310"
                >
            </a>

            <p class="site-footer__compact-copy">
                {{ $footer['copyright'] }}<br>
                Wszelkie prawa zastrzeżone.
            </p>

            <nav class="site-footer__compact-nav" aria-label="Stopka">
                <a href="{{ route('about.organization', absolute: false) }}">O nas</a>
                <a href="{{ route('legal.terms', absolute: false) }}">Regulamin</a>
                <a href="{{ route('legal.privacy', absolute: false) }}">Polityka prywatności</a>
                <a href="{{ route('about.contact', absolute: false) }}">Kontakt</a>
            </nav>

            <div class="site-footer__socials" aria-label="Media społecznościowe">
                <a href="https://www.facebook.com/PrawkoNaRaz/" target="_blank" rel="noopener noreferrer" aria-label="PrawkoNaRaz na Facebooku">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M14.2 8.5H16V5.8h-2.2c-2.7 0-4.1 1.7-4.1 4.1v2H7.8v2.8h1.9V21h3v-6.3h2.4l.4-2.8h-2.8v-1.8c0-1 .5-1.6 1.5-1.6Z" />
                    </svg>
                </a>
                <a href="https://www.instagram.com/prawkonaraz.pl/" target="_blank" rel="noopener noreferrer" aria-label="PrawkoNaRaz na Instagramie">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="5.3" y="5.3" width="13.4" height="13.4" rx="4" fill="none" stroke="currentColor" stroke-width="2" />
                        <circle cx="12" cy="12" r="3.1" fill="none" stroke="currentColor" stroke-width="2" />
                        <circle cx="16.4" cy="7.7" r="1.05" />
                    </svg>
                </a>
                <a href="https://www.tiktok.com/@prawkonaraz" target="_blank" rel="noopener noreferrer" aria-label="PrawkoNaRaz na TikToku">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12.5 2h3.2c.2 1.6.8 2.9 1.8 3.9 1 1 2.3 1.6 4 1.8v3.2c-1.6-.1-3.1-.6-4.5-1.5v6.9c0 3.4-2.7 6.1-6.1 6.1s-6.1-2.7-6.1-6.1 2.7-6.1 6.1-6.1c.4 0 .9.1 1.3.1v3.3a3.1 3.1 0 1 0 1.4 2.6V2Z" />
                    </svg>
                </a>
                <a href="https://www.youtube.com/channel/UCSrCDt_Aj1yslMY8sfXFBkg" target="_blank" rel="noopener noreferrer" aria-label="PrawkoNaRaz na YouTube">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.2 8.1c-.2-.8-.8-1.4-1.6-1.6C17.2 6.1 12 6.1 12 6.1s-5.2 0-6.6.4c-.8.2-1.4.8-1.6 1.6-.4 1.5-.4 3.9-.4 3.9s0 2.4.4 3.9c.2.8.8 1.4 1.6 1.6 1.4.4 6.6.4 6.6.4s5.2 0 6.6-.4c.8-.2 1.4-.8 1.6-1.6.4-1.5.4-3.9.4-3.9s0-2.4-.4-3.9Z" />
                        <path d="M10.1 15.6V8.4l6 3.6-6 3.6Z" fill="#050608" />
                    </svg>
                </a>
            </div>
        </div>

    </div>
</footer>

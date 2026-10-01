<div class="home-site-header__mega" data-home-header-mega>
    <div class="home-site-header__mega-tabs" aria-label="Grupy menu">
        @foreach ($groups as $group)
            <button type="button" class="home-site-header__mega-tab {{ $loop->first ? 'is-active' : '' }}" data-mega-tab="{{ $loop->index }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                <svg aria-hidden="true" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                    @switch($group['icon'])
                        @case('car')
                            <path d="M5 21h22l-2-8a3 3 0 0 0-3-2H10a3 3 0 0 0-3 2l-2 8Z"/><path d="M9 11l2-4h10l2 4M5 21v3h4v-3m14 0v3h4v-3M9 17h3m8 0h3"/>
                            @break
                        @case('motorcycle')
                            <circle cx="8" cy="23" r="4"/><circle cx="25" cy="23" r="4"/><path d="m8 23 6-9h6l5 9m-16 0h10l-5-9-3-2h-3m12 2 3-4h3"/>
                            @break
                        @case('study')
                            <path d="M5 7h9a4 4 0 0 1 4 4v15a4 4 0 0 0-4-3H5V7Zm22 0h-5a4 4 0 0 0-4 4v15a4 4 0 0 1 4-3h5V7Z"/>
                            @break
                        @case('vertical-signs')
                            <path d="M16 5 5 12v8l11 7 11-7v-8L16 5Z"/><path d="M16 11v8m0 4h.01"/>
                            @break
                        @case('road-signs')
                            <path d="M11 4 5 28m16-24 6 24M16 6v5m0 5v5m0 4v3"/>
                            @break
                        @case('signals')
                            <rect x="11" y="3" width="10" height="26" rx="3"/><circle cx="16" cy="9" r="2"/><circle cx="16" cy="16" r="2"/><circle cx="16" cy="23" r="2"/>
                            @break
                        @default
                            <path d="M7 5h18v22H7zM11 11h10m-10 5h10m-10 5h6"/><path d="m21 21 2 2 4-4"/>
                    @endswitch
                </svg>
                <span>{{ $group['label'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="home-site-header__mega-content-wrap">
        @foreach ($groups as $group)
            <section class="home-site-header__mega-content" data-mega-content="{{ $loop->index }}" @unless ($loop->first) hidden @endunless>
                <h2>{{ $group['heading'] }}</h2>
                <nav aria-label="{{ $group['heading'] }}">
                    @foreach ($group['links'] as $item)
                        <a href="{{ $item['href'] }}">{{ $item['label'] }}<span aria-hidden="true">→</span></a>
                    @endforeach
                </nav>
            </section>
        @endforeach
    </div>

    <div class="home-site-header__mega-promo-wrap">
        @foreach ($groups as $group)
            <aside class="home-site-header__mega-promo" data-mega-promo="{{ $loop->index }}" @unless ($loop->first) hidden @endunless>
                <strong>{{ $group['promo_title'] }}</strong>
                <p>{{ $group['promo_text'] }}</p>
                <a href="{{ $group['cta_href'] }}">{{ $group['cta_label'] }}</a>
            </aside>
        @endforeach
    </div>
</div>

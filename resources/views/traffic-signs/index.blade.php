@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'traffic-sign-guide__breadcrumb-shell')

@section('content')
<section class="rankomat-guide traffic-sign-guide">
    <main class="traffic-sign-guide__shell">
        <header class="traffic-sign-guide__hero-layout">
            <div class="traffic-sign-guide__hero-copy">
                <h1>Poznaj znaki drogowe i ich znaczenie.</h1>
                <p class="rankomat-guide__lead">
                    Wybierz kategorię, aby poznać znaczenie znaków i zobaczyć praktyczne przykłady.
                </p>
            </div>
        </header>

            <section id="traffic-sign-categories" class="rankomat-guide__section traffic-sign-guide__tiles-section">
                <h2>Kategorie znaków</h2>
                @if ($categoryColumns->isNotEmpty())
                    <div class="traffic-sign-guide__tiles">
                        @foreach ($categoryColumns as $categoryColumn)
                            @foreach ($categoryColumn as $category)
                                <a href="{{ $category['url'] }}" class="traffic-sign-guide__tile">
                                    <span class="traffic-sign-guide__tile-icon">
                                        @if ($category['icon_url'])
                                            <img src="{{ $category['icon_url'] }}" alt="" loading="lazy">
                                        @else
                                            <span aria-hidden="true">{{ mb_substr($category['name'], 0, 1) }}</span>
                                        @endif
                                    </span>
                                    <strong>{{ $category['name'] }}</strong>
                                    <small>{{ number_format($category['count'], 0, ',', ' ') }} znaków</small>
                                </a>
                            @endforeach
                        @endforeach
                    </div>
                @else
                    <p>Nie ma jeszcze opublikowanych kategorii znaków.</p>
                @endif
            </section>

            <section id="traffic-sign-recent" class="rankomat-guide__section">
                <div class="traffic-sign-guide__section-heading">
                    <div>
                        <h2>Ostatnio aktualizowane</h2>
                        <p>Nowe i zaktualizowane opisy znaków.</p>
                    </div>
                    <a class="rankomat-guide__inline-link" href="#traffic-sign-categories">Zobacz wszystkie →</a>
                </div>
                <div class="traffic-sign-guide__recent-list">
                    @forelse ($featuredSigns as $sign)
                        <article class="traffic-sign-guide__recent-item">
                            <a href="{{ $sign['url'] }}" class="traffic-sign-guide__recent-image" aria-label="{{ $sign['title'] }}">
                                @if ($sign['image_url'])
                                    <img src="{{ $sign['image_url'] }}" alt="{{ $sign['image_alt'] }}" loading="lazy">
                                @else
                                    <span>{{ $sign['code'] }}</span>
                                @endif
                            </a>
                            <div>
                                <h3><a href="{{ $sign['url'] }}">{{ $sign['title'] }}</a></h3>
                                @if ($sign['intro'])
                                    <p>{{ $sign['intro'] }}</p>
                                @endif
                                @if ($sign['author_url'])
                                    <a href="{{ $sign['author_url'] }}" class="sr-only">Opracowanie: {{ $sign['author_name'] }}</a>
                                @endif
                                <div class="traffic-sign-guide__recent-meta">
                                    @if ($sign['category_url'])
                                        <a href="{{ $sign['category_url'] }}">{{ $sign['category_name'] }}</a>
                                    @elseif ($sign['category_name'])
                                        <span>{{ $sign['category_name'] }}</span>
                                    @endif
                                    @if ($sign['updated_at'])
                                        <span>{{ $sign['updated_at'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <a class="traffic-sign-guide__recent-arrow" href="{{ $sign['url'] }}" aria-label="Otwórz: {{ $sign['title'] }}">→</a>
                        </article>
                    @empty
                        <p>Nie ma jeszcze opublikowanych opisów znaków.</p>
                    @endforelse
                </div>
            </section>

            <section id="traffic-sign-learning" class="rankomat-guide__section rankomat-guide__final">
                <h2>Chcesz uczyć się skuteczniej?</h2>
                <p>Ćwicz znaki w testach i utrwalaj wiedzę w praktyce.</p>
                <a href="{{ route('public.tests', absolute: false) }}" class="rankomat-guide__main-cta">Przejdź do testów →</a>
            </section>
    </main>
</section>
@endsection

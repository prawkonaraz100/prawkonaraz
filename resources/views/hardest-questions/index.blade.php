@extends('layouts.public-content')

@php
    $questionTextFormatter = app(\App\Support\QuestionTextFormatter::class);
    $basePath = $selected_category
        ? '/najtrudniejsze-pytania-na-prawo-jazdy/kategoria/'.$selected_category['slug']
        : '/najtrudniejsze-pytania-na-prawo-jazdy';
    $rankingHref = static fn (string $key): string => $key === 'overall'
        ? $basePath
        : $basePath.'?ranking='.$key;
    $categoryHref = static fn (array $category): string => $ranking['key'] === 'overall'
        ? $category['path']
        : $category['path'].'?ranking='.$ranking['key'];
@endphp

@section('breadcrumb_shell_class', 'rankomat-guide__breadcrumb-shell')

@section('content')
<section class="rankomat-guide hardest-guide">
    <div class="rankomat-guide__shell">
        @include('components.site.test-online-sidebar')

        <main class="rankomat-guide__main">
            <div class="rankomat-guide__category">RANKING PYTAŃ</div>
            <h1>{{ $page['title'] }}</h1>
            <div class="rankomat-guide__meta">
                <div class="rankomat-guide__author">
                    <img src="/images/site-brand-mark-shield-v2-optimized.webp" alt="" width="42" height="42">
                    <div><strong>PrawkoNaRaz.pl</strong><span>Dane z nauki kursantów</span></div>
                </div>
                <div class="rankomat-guide__meta-divider" aria-hidden="true"></div>
                <div class="rankomat-guide__updated"><strong>Okres analizy</strong><span>{{ $summary['window_label'] }}</span></div>
                <div class="rankomat-guide__trust">✓ Aktualne dane</div>
            </div>
            <p class="rankomat-guide__lead">{{ $page['description'] }}</p>
            @if ($selected_category)
                <p class="hardest-guide__selected">Kategoria {{ $selected_category['short_name'] ?: $selected_category['code'] }}</p>
            @endif
            <a href="{{ route('public.tests', absolute: false) }}" class="rankomat-guide__mobile-cta">Przejdź do testów online</a>

            <section class="rankomat-guide__important hardest-guide__summary" aria-labelledby="hardest-summary-title">
                <h2 id="hardest-summary-title">Ranking w liczbach</h2>
                <dl class="hardest-guide__stats">
                    <div><dt>Pytania z danymi</dt><dd>{{ $summary['questions_analyzed'] }}</dd><small>z {{ $summary['questions_total'] }} aktywnych pytań</small></div>
                    <div><dt>Odpowiedzi kursantów</dt><dd>{{ $summary['answers_count'] }}</dd><small>{{ $summary['window_label'] }}</small></div>
                    <div><dt>Kursanci w próbie</dt><dd>{{ $summary['users_count'] }}</dd><small>uwzględnieni w rankingu</small></div>
                    <div><dt>Aktywne kategorie</dt><dd>{{ $summary['active_categories_count'] }}</dd><small>z aktualnymi danymi</small></div>
                </dl>
                <p class="hardest-guide__summary-note">Ranking pokazuje bieżący obraz najtrudniejszych pytań. Uwzględnia pierwsze pomyłki, powracające błędy, czas odpowiedzi i drogę do utrwalenia.</p>
            </section>

            <nav class="rankomat-guide__toc" aria-label="Spis treści">
                <p>Przejdź do sekcji:</p>
                <ol>
                    <li><a href="#kategorie-pytan">Wybierz kategorię</a></li>
                    <li><a href="#typ-rankingu">Wybierz typ rankingu</a></li>
                    <li><a href="#ranking-pytan">Najtrudniejsze pytania teraz</a></li>
                    <li><a href="#trudne-dzialy">Najtrudniejsze działy</a></li>
                    <li><a href="#metodologia-rankingu">Jak liczymy trudność</a></li>
                    <li><a href="#faq-rankingu">Najczęstsze pytania</a></li>
                </ol>
            </nav>

            <section id="kategorie-pytan" class="rankomat-guide__section hardest-guide__section">
                <h2>Najtrudniejsze pytania według kategorii</h2>
                <p>Wybierz kategorię, aby zobaczyć trudności w pytaniach właściwych dla Twojego kursu.</p>
                <div class="hardest-guide__categories">
                    <a href="{{ $ranking['key'] === 'overall' ? route('public.hardest-questions.index') : route('public.hardest-questions.index', ['ranking' => $ranking['key']]) }}" class="{{ $selected_category ? '' : 'is-active' }}">
                        <strong>Wszystkie kategorie</strong><span>Pełny przekrój rankingu</span>
                    </a>
                    @foreach ($categories as $category)
                        <a href="{{ $categoryHref($category) }}" class="{{ ($selected_category['slug'] ?? null) === $category['slug'] ? 'is-active' : '' }}">
                            <strong>Kat. {{ $category['code'] }}</strong><span>{{ $category['questions_analyzed'] }} pytań z danymi</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section id="typ-rankingu" class="rankomat-guide__section hardest-guide__section">
                <h2>Jakie trudności chcesz sprawdzić?</h2>
                <div class="hardest-guide__modes">
                    @foreach ($ranking_options as $option)
                        <a href="{{ $rankingHref($option['key']) }}" class="{{ $ranking['key'] === $option['key'] ? 'is-active' : '' }}">
                            <strong>{{ $option['label'] }}</strong><span>{{ $option['description'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section id="ranking-pytan" class="rankomat-guide__section hardest-guide__section">
                <p class="hardest-guide__eyebrow">Najtrudniejsze teraz · {{ $summary['window_label'] }}</p>
                <h2>{{ $ranking['label'] }}</h2>
                <p>{{ $ranking['description'] }}</p>
                @if (count($top_questions) > 0)
                    <ol class="hardest-guide__questions">
                        @foreach ($top_questions as $index => $question)
                            <li>
                                <article>
                                    <div class="hardest-guide__question-heading"><span class="hardest-guide__number">{{ $index + 1 }}</span><span>Kat. {{ $question['category']['code'] }} · {{ $question['topic']['name'] }}</span></div>
                                    <h3>{!! $questionTextFormatter->inlineHtml($question['prompt']) !!}</h3>
                                    <div class="hardest-guide__question-bottom">
                                        <div class="hardest-guide__metrics">
                                            <span><strong>{{ $question['first_try_error_pct'] }}%</strong> błędów przy pierwszej próbie</span>
                                            <span>{{ $question['answers_count'] }} odpowiedzi</span>
                                            <span>{{ $question['users_count'] }} kursantów</span>
                                            <span>Trudność: {{ $question['difficulty_score'] }}</span>
                                        </div>
                                        <a href="{{ $question['practice_url'] }}" class="hardest-guide__practice">Przećwicz pytanie →</a>
                                    </div>
                                </article>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="hardest-guide__empty">Dla tego widoku nie mamy jeszcze wystarczającej aktywności. Gdy kursanci rozwiążą więcej pytań, ranking zacznie się tu wypełniać.</p>
                @endif
            </section>

            <section id="trudne-dzialy" class="rankomat-guide__section hardest-guide__section">
                <h2>Najtrudniejsze działy</h2>
                <div class="hardest-guide__topic-list">
                    @forelse ($top_topics as $topic)
                        <div><div><strong>{{ $topic['name'] }}</strong><span>{{ $topic['questions_count'] }} pytań · {{ $topic['answers_count'] }} odpowiedzi</span></div><b>{{ $topic['avg_difficulty_score'] }}</b></div>
                    @empty
                        <p>Brak wystarczających danych do rankingu działów.</p>
                    @endforelse
                </div>
            </section>

            <section id="metodologia-rankingu" class="rankomat-guide__section hardest-guide__section">
                <h2>Jak liczymy trudność?</h2>
                <p>Ranking powstaje na podstawie rzeczywistych odpowiedzi kursantów, nie subiektywnej oceny pytania.</p>
                <div class="hardest-guide__methodology">
                    @foreach ($methodology as $item)
                        <div><h3>{{ $item['title'] }}</h3><p>{{ $item['description'] }}</p></div>
                    @endforeach
                </div>
            </section>

            <section id="faq-rankingu" class="rankomat-guide__section rankomat-guide__faq">
                <h2>Najczęstsze pytania o ranking</h2>
                <details><summary>Skąd biorą się te statystyki?<span aria-hidden="true">+</span></summary><p>Liczymy je na podstawie realnych odpowiedzi kursantów w bieżącym oknie {{ strtolower($summary['window_label']) }}.</p></details>
                <details><summary>Czy to jest ranking oficjalnych pytań?<span aria-hidden="true">+</span></summary><p>Ranking dotyczy aktywnych pytań w bazie, dla których mamy dane z nauki kursantów.</p></details>
                <details><summary>Dlaczego niektóre kategorie mają mało danych?<span aria-hidden="true">+</span></summary><p>Mniej popularne kategorie potrzebują większej liczby odpowiedzi, aby zbudować mocniejszą próbę.</p></details>
            </section>
        </main>
    </div>
</section>
@endsection

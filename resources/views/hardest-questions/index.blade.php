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

@section('content')
    <section class="content-band">
        <div class="content-shell py-9 md:py-12">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Publiczna analityka trudności</p>
            <h1 class="mt-3 max-w-4xl text-4xl font-bold tracking-tight text-slate-950 md:text-5xl">
                {{ $page['title'] }}
            </h1>
            <p class="content-muted mt-5 max-w-3xl text-base leading-8 md:text-lg">
                {{ $page['description'] }}
            </p>

            @if ($selected_category)
                <p class="mt-5 inline-flex rounded-full border border-slate-300 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-900">
                    Kategoria {{ $selected_category['short_name'] ?: $selected_category['code'] }}
                </p>
            @endif

            <dl class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <dt class="text-sm text-slate-600">Pytania z danymi</dt>
                    <dd class="mt-2 text-3xl font-bold text-slate-950">{{ $summary['questions_analyzed'] }}</dd>
                    <p class="mt-1 text-sm text-slate-500">z {{ $summary['questions_total'] }} aktywnych pytań</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <dt class="text-sm text-slate-600">Odpowiedzi kursantów</dt>
                    <dd class="mt-2 text-3xl font-bold text-slate-950">{{ $summary['answers_count'] }}</dd>
                    <p class="mt-1 text-sm text-slate-500">{{ $summary['window_label'] }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <dt class="text-sm text-slate-600">Kursanci w próbie</dt>
                    <dd class="mt-2 text-3xl font-bold text-slate-950">{{ $summary['users_count'] }}</dd>
                    <p class="mt-1 text-sm text-slate-500">uwzględnieni w bieżącym rankingu</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <dt class="text-sm text-slate-600">Aktywne kategorie</dt>
                    <dd class="mt-2 text-3xl font-bold text-slate-950">{{ $summary['active_categories_count'] }}</dd>
                    <p class="mt-1 text-sm text-slate-500">z aktualnymi danymi trudności</p>
                </div>
            </dl>

            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                <h2 class="font-semibold text-slate-950">Jak czytać ten ranking</h2>
                <p class="content-muted mt-2 text-sm leading-7">
                    Ranking pokazuje bieżący obraz najtrudniejszych pytań w oknie {{ $summary['window_label'] }}.
                    Uwzględnia pierwsze pomyłki, powracające błędy, czas odpowiedzi i drogę do utrwalenia.
                </p>
            </div>
        </div>
    </section>

    <section class="content-band border-t border-slate-100">
        <div class="content-shell py-8 md:py-10">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Kategorie</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <a
                        href="{{ $ranking['key'] === 'overall' ? route('public.hardest-questions.index') : route('public.hardest-questions.index', ['ranking' => $ranking['key']]) }}"
                        class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-[#d01921]"
                    >
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Wszystkie</p>
                        <p class="mt-2 text-2xl font-bold text-slate-950">Kategorie</p>
                        <p class="content-muted mt-2 text-sm leading-6">Zobacz przekrój całego rankingu bez filtrowania.</p>
                    </a>

                    @foreach ($categories as $category)
                        <a
                            href="{{ $categoryHref($category) }}"
                            class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-[#d01921]"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Kategoria</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-950">{{ $category['code'] }}</p>
                                </div>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold">
                                    {{ $category['questions_analyzed'] }} pytań
                                </span>
                            </div>
                            <p class="content-muted mt-3 text-sm leading-6">{{ $category['short_name'] ?: $category['name'] }}</p>
                            <p class="mt-3 text-xs font-semibold text-slate-600">
                                Próba: {{ $category['users_count'] }} kursantów · Odp.: {{ $category['answers_count'] }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="mt-8">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Typ rankingu</p>
                <div class="mt-3 grid gap-3 lg:grid-cols-4">
                    @foreach ($ranking_options as $option)
                        <a
                            href="{{ $rankingHref($option['key']) }}"
                            class="rounded-xl border p-4 transition {{ $ranking['key'] === $option['key'] ? 'border-[#d01921] bg-red-50/40' : 'border-slate-200 bg-white hover:border-slate-400' }}"
                        >
                            <p class="font-semibold text-slate-950">{{ $option['label'] }}</p>
                            <p class="content-muted mt-2 text-sm leading-6">{{ $option['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="content-band border-t border-slate-100">
        <div class="content-shell py-8 md:py-10">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Najtrudniejsze teraz</p>
                    <h2 class="mt-2 text-3xl font-bold text-slate-950">{{ $ranking['label'] }}</h2>
                    <p class="content-muted mt-2 max-w-3xl text-sm leading-7">{{ $ranking['description'] }}</p>
                </div>
                <p class="text-sm font-semibold text-slate-600">{{ $summary['window_label'] }}</p>
            </div>

            @if (count($top_questions) > 0)
                <ol class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
                    @foreach ($top_questions as $index => $question)
                        <li class="py-5">
                            <article class="grid gap-4 lg:grid-cols-[64px_minmax(0,1fr)_180px] lg:items-start">
                                <div class="text-2xl font-bold text-slate-400">#{{ $index + 1 }}</div>
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                                        {{ $question['category']['code'] }} · {{ $question['topic']['name'] }}
                                    </p>
                                    <h3 class="mt-2 text-lg font-semibold leading-7 text-slate-950">
                                        {!! $questionTextFormatter->inlineHtml($question['prompt']) !!}
                                    </h3>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                                        <span class="rounded-full border border-slate-200 px-3 py-1">Odp.: {{ $question['answers_count'] }}</span>
                                        <span class="rounded-full border border-slate-200 px-3 py-1">Kursanci: {{ $question['users_count'] }}</span>
                                        <span class="rounded-full border border-slate-200 px-3 py-1">Trudność: {{ $question['difficulty_score'] }}</span>
                                    </div>
                                </div>
                                <div class="lg:text-right">
                                    <p class="text-sm font-semibold text-slate-600">Błędy przy pierwszej próbie</p>
                                    <p class="mt-1 text-2xl font-bold text-slate-950">{{ $question['first_try_error_pct'] }}%</p>
                                    <a
                                        href="{{ $question['practice_url'] }}"
                                        class="mt-3 inline-flex rounded-[4px] bg-[#d01921] px-4 py-2 text-sm font-bold text-white hover:bg-[#b9151c]"
                                    >
                                        Przećwicz
                                    </a>
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="content-muted mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm leading-7">
                    Dla tego widoku nie mamy jeszcze wystarczającej aktywności. Gdy kursanci rozwiążą więcej pytań, ranking zacznie się tu wypełniać.
                </p>
            @endif
        </div>
    </section>

    <section class="content-band border-t border-slate-100">
        <div class="content-shell grid gap-8 py-8 md:py-10 lg:grid-cols-2">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Najtrudniejsze działy</p>
                <div class="mt-4 space-y-3">
                    @forelse ($top_topics as $topic)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $topic['name'] }}</p>
                                    <p class="content-muted mt-1 text-sm">{{ $topic['questions_count'] }} pytań · {{ $topic['answers_count'] }} odpowiedzi</p>
                                </div>
                                <p class="text-xl font-bold text-slate-950">{{ $topic['avg_difficulty_score'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="content-muted text-sm">Brak wystarczających danych do rankingu działów.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Jak liczymy trudność</p>
                <div class="mt-4 space-y-3">
                    @foreach ($methodology as $item)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="font-semibold text-slate-950">{{ $item['title'] }}</p>
                            <p class="content-muted mt-2 text-sm leading-6">{{ $item['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="content-band border-t border-slate-100">
        <div class="content-shell py-8 md:py-10">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">FAQ</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-950">O co najczęściej pytają kursanci</h2>
            <div class="mt-5 grid gap-3 lg:grid-cols-3">
                <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h3 class="font-semibold text-slate-950">Skąd biorą się te statystyki?</h3>
                    <p class="content-muted mt-2 text-sm leading-6">Liczymy je na podstawie realnych odpowiedzi kursantów w bieżącym oknie {{ strtolower($summary['window_label']) }}.</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h3 class="font-semibold text-slate-950">Czy to jest ranking oficjalnych pytań?</h3>
                    <p class="content-muted mt-2 text-sm leading-6">Ranking dotyczy aktywnych pytań w bazie, dla których mamy dane z nauki kursantów.</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h3 class="font-semibold text-slate-950">Dlaczego niektóre kategorie mają mało danych?</h3>
                    <p class="content-muted mt-2 text-sm leading-6">Mniej popularne kategorie potrzebują większej liczby odpowiedzi, aby zbudować mocniejszą próbę.</p>
                </article>
            </div>
        </div>
    </section>
@endsection

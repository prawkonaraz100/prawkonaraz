@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'traffic-sign-guide__breadcrumb-shell')

@php
    $lastPage = max(1, (int) $questions->lastPage());
    $currentPage = max(1, (int) $questions->currentPage());
    $paginationPages = $lastPage <= 9
        ? range(1, $lastPage)
        : collect([1, 2, max(1, $currentPage - 1), $currentPage, min($lastPage, $currentPage + 1), $lastPage - 1, $lastPage])->unique()->sort()->values()->all();
    $searchValue = trim((string) (($filters['q'] ?? '') !== '' ? $filters['q'] : ($filters['gov_id'] ?? '')));
    $hasActiveSearch = $searchValue !== '';
    $categoryIntro = match (\Illuminate\Support\Str::upper((string) $category->code)) {
        'A' => 'Pytania egzaminacyjne na motocykl bez ograniczenia mocy.',
        'A1' => 'Pytania egzaminacyjne na motocykl do 125 cm3.',
        'A2' => 'Pytania egzaminacyjne na motocykl o mocy do 35 kW.',
        'AM' => 'Pytania egzaminacyjne na motorower i lekki czterokołowiec.',
        'B' => 'Pytania egzaminacyjne na samochód osobowy.',
        'B1' => 'Pytania egzaminacyjne na czterokołowiec.',
        'C' => 'Pytania egzaminacyjne na samochód ciężarowy.',
        'C1' => 'Pytania egzaminacyjne na samochód ciężarowy do 7,5 tony.',
        'D' => 'Pytania egzaminacyjne na autobus.',
        'D1' => 'Pytania egzaminacyjne na mniejszy autobus.',
        'T' => 'Pytania egzaminacyjne na ciągnik rolniczy.',
        default => $summary,
    };
@endphp

@section('content')
    <section class="rankomat-guide question-database-guide question-category-guide">
        <div class="traffic-sign-guide__shell">
            <header class="question-database-guide__hero">
                <div class="traffic-sign-guide__hero-copy">
                    <p class="question-category-guide__eyebrow">Kategoria {{ $category->code }} · {{ $vehicleLabel }}</p>
                    <h1>Pytania na prawo jazdy kat. {{ $category->code }} — oficjalna baza</h1>
                    <p class="rankomat-guide__lead">{{ $categoryIntro }} Sprawdzaj poprawne odpowiedzi i korzystaj z dostępnych wyjaśnień, aby zrozumieć sytuacje z egzaminu.</p>
                </div>
                <a href="{{ route('public.tests') }}" class="rankomat-guide__main-cta">Przejdź do testów online <span aria-hidden="true">→</span></a>
            </header>

            <dl class="question-category-guide__stats" aria-label="Zawartość bazy kategorii {{ $category->code }}">
                <div><dt>Pytania w kategorii</dt><dd>{{ number_format($categoryContent['total'], 0, ',', ' ') }}</dd></div>
                @if ($categoryContent['boolean'] > 0)
                    <div><dt>Pytania TAK / NIE</dt><dd>{{ number_format($categoryContent['boolean'], 0, ',', ' ') }}</dd></div>
                @endif
                @if ($categoryContent['single_choice'] > 0)
                    <div><dt>Pytania A / B / C</dt><dd>{{ number_format($categoryContent['single_choice'], 0, ',', ' ') }}</dd></div>
                @endif
                @if ($categoryContent['topic_count'] > 0)
                    <div><dt>Zagadnienia w bazie</dt><dd>{{ $categoryContent['topic_count'] }}</dd></div>
                @endif
            </dl>
            @if (! $hasActiveSearch && $currentPage === 1 && $categoryContent['topics'] !== [])
                <nav class="question-category-guide__section-nav" aria-label="Na tej stronie">
                    <a href="#pytania">Przeglądaj pytania</a>
                    <a href="#zagadnienia">Zagadnienia i przykłady</a>
                    <a href="#jak-sie-uczyc">Jak pracować z pytaniami?</a>
                    <a href="#pytania-i-odpowiedzi">Najczęstsze pytania</a>
                </nav>
            @endif

            <h2 id="pytania" class="question-category-guide__list-heading">{{ $hasActiveSearch ? 'Wyniki wyszukiwania' : 'Pytania egzaminacyjne kategorii '.$category->code }}{{ $currentPage > 1 ? ' — strona '.$currentPage : '' }}</h2>

            <form method="GET" action="{{ route('public.questions.search') }}" class="mt-7">
                <label for="category-question-search" class="sr-only">Szukaj pytań w kategorii {{ $category->code }}</label>
                <input type="hidden" name="category" value="{{ $category->slug }}">
                <div class="flex min-h-[64px] items-center rounded-[8px] border border-[#dce3eb] bg-white px-5 transition focus-within:border-[#b8c4d2] focus-within:shadow-[0_14px_36px_rgba(15,23,42,0.07)] md:px-7">
                    <button
                        type="submit"
                        class="grid h-11 w-11 shrink-0 place-items-center rounded-[6px] text-[#111827] transition hover:bg-[#f8fafc] hover:text-[#1769c2] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1769c2]/25"
                        aria-label="Szukaj pytań w kategorii {{ $category->code }}"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.4-3.4" stroke-linecap="round" />
                        </svg>
                    </button>
                    <input
                        id="category-question-search"
                        name="q"
                        type="search"
                        value="{{ $searchValue }}"
                        autocomplete="off"
                        enterkeyhint="search"
                        placeholder="Szukaj pytań..."
                        class="question-search-input min-w-0 flex-1 border-0 bg-transparent px-3 text-[1rem] text-[#111827] placeholder:text-[#6b7890] focus:bg-transparent focus:ring-0 md:text-[1.08rem]"
                    >
                </div>
            </form>

            @if ($hasActiveSearch)
                <div class="mt-4 flex flex-wrap items-center gap-3 text-[0.92rem] text-[#64748b]">
                    <span>Wyniki dla: <strong class="font-semibold text-[#111827]">{{ $searchValue }}</strong></span>
                    <a href="{{ route('public.questions.category', $category->slug) }}" class="font-semibold text-[#1769c2] transition hover:text-[#07358c]">
                        Wyczyść
                    </a>
                </div>
            @endif

            @if ($questions->count() > 0)
                <div class="mt-6 overflow-hidden rounded-[8px] border border-[#dce3eb] bg-white">
                    @foreach ($questions as $question)
                        <a href="{{ $question['url'] }}" class="group grid gap-4 border-b border-[#e6ebf1] px-4 py-4 transition last:border-b-0 hover:bg-[#fbfcfe] md:grid-cols-[214px_minmax(0,1fr)_44px] md:items-center md:px-4 md:py-3.5 lg:grid-cols-[240px_minmax(0,1fr)_56px]">
                            <div class="h-[118px] overflow-hidden rounded-[6px] bg-[#eef3f8] md:h-[112px] lg:h-[118px]">
                                @if (! empty($question['thumbnail_url']))
                                    <img src="{{ $question['thumbnail_url'] }}" alt="{{ $question['thumbnail_alt'] }}" loading="lazy" decoding="async" width="240" height="118" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-[#94a3b8]">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path d="M4 5h16v14H4z" />
                                            <path d="m4 15 4-4 4 4 3-3 5 5" />
                                        </svg>
                                    </span>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-4">
                                    <span class="text-[0.92rem] font-semibold text-[#58677f]">
                                        #{{ $question['display_external_id'] ?? $question['external_id'] }}
                                    </span>
                                    <span class="rounded-[6px] bg-[#e8f2ff] px-3 py-1 text-[0.82rem] font-bold text-[#1769c2]">
                                        Kat. {{ $question['category_code'] ?? $category->code }}
                                    </span>
                                </div>

                                <h3 class="question-category-guide__prompt mt-3 max-w-4xl text-[1.05rem] font-bold leading-7 text-[#111827] transition group-hover:text-[#1769c2] md:text-[1.08rem]">
                                    {{ $question['prompt_plain'] }}
                                </h3>

                                <div class="mt-4 flex flex-wrap items-center gap-x-7 gap-y-2 text-[0.95rem] text-[#5f6b81]">
                                    <span class="inline-flex items-center gap-2">
                                        <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9" />
                                            <path d="M9.4 9a2.7 2.7 0 0 1 5.2.9c0 1.8-2.6 2-2.6 3.9" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M12 17h.01" stroke-linecap="round" />
                                        </svg>
                                        {{ $question['type_label'] }}
                                    </span>
                                    <span class="inline-flex items-center gap-2">
                                        <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                            <path d="M8 3v4M16 3v4M4.5 9h15M6 5h12a1.5 1.5 0 0 1 1.5 1.5V19A1.5 1.5 0 0 1 18 20.5H6A1.5 1.5 0 0 1 4.5 19V6.5A1.5 1.5 0 0 1 6 5Z" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        {{ $question['date_label'] }}
                                    </span>
                                </div>
                            </div>

                            <span class="hidden h-11 w-11 items-center justify-center justify-self-end text-[2rem] leading-none text-[#111827] transition group-hover:translate-x-1 group-hover:text-[#1769c2] md:flex" aria-hidden="true">
                                →
                            </span>
                        </a>
                    @endforeach
                </div>

                <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.92rem] text-[#64748b]">
                        Wyświetlono {{ number_format($questions->firstItem() ?? 0, 0, ',', ' ') }}-{{ number_format($questions->lastItem() ?? 0, 0, ',', ' ') }} z {{ number_format($questions->total(), 0, ',', ' ') }} pytań
                    </p>

                    @if ($questions->hasPages())
                        <nav class="flex flex-wrap items-center gap-2" aria-label="Paginacja pytań kategorii {{ $category->code }}">
                            @if ($currentPage > 1)
                                <a href="{{ $questions->previousPageUrl() }}" class="question-category-guide__page-link" aria-label="Poprzednia strona">‹</a>
                            @endif
                            @foreach ($paginationPages as $pageNumber)
                                @if ($loop->index > 0 && $pageNumber > $paginationPages[$loop->index - 1] + 1)
                                    <span class="px-2 text-sm text-[#64748b]" aria-hidden="true">…</span>
                                @endif
                                @if ($pageNumber === $currentPage)
                                    <span aria-current="page" class="flex h-10 min-w-10 items-center justify-center rounded-[4px] bg-[#e8f2ff] px-3 text-sm font-bold text-[#1769c2]">{{ $pageNumber }}</span>
                                @else
                                    <a href="{{ $questions->url($pageNumber) }}" class="flex h-10 min-w-10 items-center justify-center rounded-[4px] border border-[#dce3eb] px-3 text-sm font-semibold text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]">{{ $pageNumber }}</a>
                                @endif
                            @endforeach

                            @if ($questions->hasMorePages())
                                <a href="{{ $questions->nextPageUrl() }}" class="flex h-10 min-w-10 items-center justify-center rounded-[4px] border border-[#dce3eb] px-3 text-sm font-semibold text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]" aria-label="Następna strona">›</a>
                            @endif
                        </nav>
                    @endif
                </div>
            @else
                <div class="mt-6 rounded-[8px] border border-dashed border-[#dce3eb] bg-[#f8fafc] px-5 py-10 text-center text-[0.95rem] text-[#64748b]">
                    {{ $hasActiveSearch ? 'Nie znaleziono pytań dla tego wyszukiwania.' : 'W tej kategorii nie ma obecnie opublikowanych pytań.' }}
                    @if ($hasActiveSearch)
                        <a href="{{ route('public.questions.category', $category->slug) }}" class="ml-2 font-semibold text-[#1769c2] transition hover:text-[#07358c]">Wyczyść</a>
                    @endif
                </div>
            @endif
            @if (! $hasActiveSearch && $currentPage === 1 && $categoryContent['total'] > 0)
                @include('questions-database.partials.category-guide')
            @elseif (! $hasActiveSearch && $currentPage > 1)
                <p class="question-category-guide__return"><a href="{{ route('public.questions.category', $category->slug) }}">Wróć do początku bazy kategorii {{ $category->code }} i zobacz zagadnienia oraz wskazówki do nauki.</a></p>
            @endif
        </div>
    </section>
@endsection

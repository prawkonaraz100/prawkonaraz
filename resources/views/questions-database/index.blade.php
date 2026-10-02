@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'traffic-sign-guide__breadcrumb-shell')

@php
    $categoriesByCode = $categories->keyBy(fn (array $category): string => \Illuminate\Support\Str::upper((string) $category['code']));
    $categorySet = fn (array $codes) => collect($codes)
        ->map(fn (string $code) => $categoriesByCode->get(\Illuminate\Support\Str::upper($code)))
        ->filter()
        ->values();

    $popularCategories = $categorySet(['B', 'A', 'C']);
    if ($popularCategories->isEmpty()) {
        $popularCategories = $categories->take(3)->values();
    }

    $categorySections = [
        [
            'title' => 'Samochody',
            'codes' => ['B1', 'C1', 'D', 'D1'],
        ],
        [
            'title' => 'Motocykle',
            'codes' => ['AM', 'A1', 'A2', 'A'],
        ],
        [
            'title' => 'Pozostałe',
            'codes' => ['T'],
        ],
    ];

    $shortLabels = [
        'A' => 'Motocykle bez ograniczeń mocy',
        'A1' => 'Motocykle do 125 cm³',
        'A2' => 'Motocykle do 35 kW',
        'AM' => 'Motorowery',
        'B' => 'Samochody osobowe',
        'B1' => 'Lekkie czterokołowce',
        'C' => 'Samochody ciężarowe',
        'C1' => 'Samochody ciężarowe do 7,5 tony',
        'D' => 'Autobusy',
        'D1' => 'Minibusy',
        'T' => 'Ciągniki rolnicze',
    ];

    $labelFor = fn (array $category): string => $shortLabels[\Illuminate\Support\Str::upper((string) $category['code'])] ?? (string) $category['vehicle_label'];
    $currentYear = now()->year;
    $hasActiveSearch = (bool) ($hasActiveSearch ?? false);
    $searchValue = trim((string) ($searchQuery ?? ''));
    $searchPaginator = $searchResults ?? null;
    $searchLastPage = $searchPaginator ? max(1, (int) $searchPaginator->lastPage()) : 1;
    $searchCurrentPage = $searchPaginator ? max(1, (int) $searchPaginator->currentPage()) : 1;
    $searchEarlyPages = $searchLastPage >= 1 ? range(1, min(6, $searchLastPage)) : [];
    $searchTailPages = $searchLastPage > 8 ? range(max(7, $searchLastPage - 1), $searchLastPage) : [];
@endphp

@section('content')
    <section class="rankomat-guide question-database-guide">
        <div class="traffic-sign-guide__shell">
            <header class="question-database-guide__hero">
                <div class="traffic-sign-guide__hero-copy">
                    <h1>Oficjalna baza pytań na prawo jazdy {{ $currentYear }}</h1>
                    <p class="rankomat-guide__lead">
                        Wybierz swoją kategorię, sprawdzaj poprawne odpowiedzi i ucz się, dlaczego są właściwe.
                    </p>
                    <p class="question-database-guide__meta">
                        <span>{{ number_format($canonicalQuestionsCount, 0, ',', ' ') }} oficjalnych pytań</span>
                        <span>{{ $categories->count() }} kategorii prawa jazdy</span>
                    </p>
                </div>
                <a href="{{ route('public.tests', absolute: false) }}" class="rankomat-guide__main-cta">Przejdź do testów online →</a>
            </header>

            <div class="question-database-guide__content">
            @if ($hasActiveSearch)
                <section class="question-database-guide__search" aria-labelledby="question-search-results-heading">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 id="question-search-results-heading" class="text-[1.7rem] font-bold leading-tight text-[#06122b]">
                                Wyniki wyszukiwania
                            </h2>
                            <p class="mt-2 text-[1rem] font-medium text-[#06122b]">
                                Wyniki dla: <strong class="font-bold">{{ $searchValue }}</strong>
                            </p>
                        </div>
                        <a href="{{ route('public.questions.hub') }}" class="inline-flex min-h-10 items-center justify-center rounded-[5px] border border-[#dce3eb] px-4 text-[0.9rem] font-bold text-[#06122b] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]">
                            Wyczyść
                        </a>
                    </div>

                    @if ($searchPaginator && $searchPaginator->count() > 0)
                        <div class="mt-6 overflow-hidden rounded-[8px] border border-[#dce3eb] bg-white">
                            @foreach ($searchPaginator as $question)
                                @php($categoryCodes = collect($question['category_codes'] ?? [])->filter()->values())
                                <a href="{{ $question['url'] }}" class="group grid gap-4 border-b border-[#e6ebf1] px-4 py-4 transition last:border-b-0 hover:bg-[#fbfcfe] md:grid-cols-[214px_minmax(0,1fr)_44px] md:items-center md:px-4 md:py-3.5 lg:grid-cols-[240px_minmax(0,1fr)_56px]">
                                    <div class="h-[118px] overflow-hidden rounded-[6px] bg-[#eef3f8] md:h-[112px] lg:h-[118px]">
                                        @if (! empty($question['thumbnail_url']))
                                            <img src="{{ $question['thumbnail_url'] }}" alt="{{ $question['thumbnail_alt'] }}" class="h-full w-full object-cover">
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
                                        <div class="flex flex-wrap items-center gap-3">
                                            <span class="text-[0.92rem] font-semibold text-[#58677f]">
                                                #{{ $question['display_external_id'] ?? $question['external_id'] }}
                                            </span>
                                            @forelse ($categoryCodes as $categoryCode)
                                                <span class="rounded-[6px] bg-[#e8f2ff] px-3 py-1 text-[0.82rem] font-bold text-[#1769c2]">
                                                    Kat. {{ $categoryCode }}
                                                </span>
                                            @empty
                                                <span class="rounded-[6px] bg-[#e8f2ff] px-3 py-1 text-[0.82rem] font-bold text-[#1769c2]">
                                                    Kat. {{ $question['category_code'] ?? '' }}
                                                </span>
                                            @endforelse
                                        </div>

                                        <h3 class="mt-3 max-w-4xl text-[1.05rem] font-bold leading-7 text-[#111827] transition group-hover:text-[#07358c] md:text-[1.08rem]">
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

                                    <span class="hidden h-11 w-11 items-center justify-center justify-self-end text-[2rem] leading-none text-[#111827] transition group-hover:translate-x-1 group-hover:text-[#07358c] md:flex" aria-hidden="true">
                                        →
                                    </span>
                                </a>
                            @endforeach
                        </div>

                        <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-[0.92rem] text-[#64748b]">
                                Wyświetlono {{ number_format($searchPaginator->firstItem() ?? 0, 0, ',', ' ') }}-{{ number_format($searchPaginator->lastItem() ?? 0, 0, ',', ' ') }} z {{ number_format($searchPaginator->total(), 0, ',', ' ') }} pytań
                            </p>

                            @if ($searchPaginator->hasPages())
                                <nav class="flex flex-wrap items-center gap-2" aria-label="Paginacja wyników wyszukiwania">
                                    @foreach ($searchEarlyPages as $pageNumber)
                                        @if ($pageNumber === $searchCurrentPage)
                                            <span class="flex h-10 min-w-10 items-center justify-center rounded-[4px] bg-[#e8f2ff] px-3 text-sm font-bold text-[#07358c]">{{ $pageNumber }}</span>
                                        @else
                                            <a href="{{ $searchPaginator->url($pageNumber) }}" class="flex h-10 min-w-10 items-center justify-center rounded-[4px] border border-[#dce3eb] px-3 text-sm font-semibold text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]">{{ $pageNumber }}</a>
                                        @endif
                                    @endforeach

                                    @if ($searchLastPage > 8)
                                        <span class="px-2 text-sm text-[#64748b]">...</span>
                                        @foreach ($searchTailPages as $pageNumber)
                                            @if ($pageNumber === $searchCurrentPage)
                                                <span class="flex h-10 min-w-10 items-center justify-center rounded-[4px] bg-[#e8f2ff] px-3 text-sm font-bold text-[#07358c]">{{ $pageNumber }}</span>
                                            @else
                                                <a href="{{ $searchPaginator->url($pageNumber) }}" class="flex h-10 min-w-10 items-center justify-center rounded-[4px] border border-[#dce3eb] px-3 text-sm font-semibold text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]">{{ $pageNumber }}</a>
                                            @endif
                                        @endforeach
                                    @endif

                                    @if ($searchPaginator->hasMorePages())
                                        <a href="{{ $searchPaginator->nextPageUrl() }}" class="flex h-10 min-w-10 items-center justify-center rounded-[4px] border border-[#dce3eb] px-3 text-sm font-semibold text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]" aria-label="Następna strona">›</a>
                                    @endif
                                </nav>
                            @endif
                        </div>
                    @else
                        <div class="mt-6 rounded-[8px] border border-dashed border-[#dce3eb] bg-[#f8fafc] px-5 py-10 text-center text-[0.95rem] text-[#64748b]">
                            Nie znaleziono pytań dla tego wyszukiwania.
                            <a href="{{ route('public.questions.hub') }}" class="ml-2 font-semibold text-[#07358c] transition hover:text-[#052a71]">Wyczyść</a>
                        </div>
                    @endif
                </section>
            @endif

            <div class="rankomat-guide__section question-database-guide__categories-heading">
                <h2>
                    Kategorie prawa jazdy
                </h2>
                <p>
                    Wybierz kategorię i przejdź do oficjalnych pytań
                </p>
            </div>

            @if ($popularCategories->isNotEmpty())
                <section class="question-database-guide__group" aria-labelledby="question-popular-categories">
                    <h3 id="question-popular-categories">Najczęściej wybierane</h3>
                    <div class="question-database-guide__tiles question-database-guide__tiles--popular">
                        @foreach ($popularCategories as $category)
                            <x-public.question-category-tile :category="$category" :description="$labelFor($category)" />
                        @endforeach
                    </div>
                </section>
            @endif

            @foreach ($categorySections as $section)
                @php($sectionCategories = $categorySet($section['codes']))
                @if ($sectionCategories->isNotEmpty())
                    <section class="question-database-guide__group" aria-labelledby="question-category-section-{{ \Illuminate\Support\Str::slug($section['title']) }}">
                        <h3 id="question-category-section-{{ \Illuminate\Support\Str::slug($section['title']) }}">{{ $section['title'] }}</h3>
                        <div class="question-database-guide__tiles {{ $sectionCategories->count() === 1 ? 'question-database-guide__tiles--single' : '' }}">
                            @foreach ($sectionCategories as $category)
                                <x-public.question-category-tile :category="$category" :description="$labelFor($category)" />
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach

            @include('questions-database.partials.hub-guide')
            </div>
        </div>
    </section>
@endsection

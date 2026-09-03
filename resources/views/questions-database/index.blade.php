@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'site-shell')

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
            'accent' => '#0b56bf',
            'icon' => 'car',
        ],
        [
            'title' => 'Motocykle',
            'codes' => ['AM', 'A1', 'A2', 'A'],
            'accent' => '#14843a',
            'icon' => 'bike',
        ],
        [
            'title' => 'Pozostałe',
            'codes' => ['T'],
            'accent' => '#f08a00',
            'icon' => 'tractor',
        ],
    ];

    $categoryThemes = [
        'A' => ['accent' => '#16813a', 'soft' => '#e7f5ec'],
        'A1' => ['accent' => '#16813a', 'soft' => '#e7f5ec'],
        'A2' => ['accent' => '#16813a', 'soft' => '#e7f5ec'],
        'AM' => ['accent' => '#16813a', 'soft' => '#e7f5ec'],
        'B' => ['accent' => '#0b56bf', 'soft' => '#e8f1ff'],
        'B1' => ['accent' => '#0b56bf', 'soft' => '#e8f1ff'],
        'C' => ['accent' => '#f0a000', 'soft' => '#fff3d8'],
        'C1' => ['accent' => '#0b56bf', 'soft' => '#e8f1ff'],
        'D' => ['accent' => '#0b56bf', 'soft' => '#e8f1ff'],
        'D1' => ['accent' => '#0b56bf', 'soft' => '#e8f1ff'],
        'T' => ['accent' => '#f08a00', 'soft' => '#fff1dc'],
    ];

    $featuredIllustrations = [
        'A' => 'resources/images/questions/featured-category-icons/a.png',
        'B' => 'resources/images/questions/featured-category-icons/b.png',
        'C' => 'resources/images/questions/featured-category-icons/c.png',
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

    $themeFor = fn (array $category): array => $categoryThemes[\Illuminate\Support\Str::upper((string) $category['code'])] ?? ['accent' => '#0b56bf', 'soft' => '#e8f1ff'];
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
    <section class="content-band overflow-hidden">
        <div class="content-shell grid gap-7 pb-8 pt-7 md:pb-10 md:pt-9 lg:grid-cols-[minmax(0,0.88fr)_minmax(440px,0.96fr)] lg:items-start lg:gap-10 lg:py-0">
            <div class="relative z-10 max-w-2xl py-2 lg:pb-16 lg:pt-16 xl:pb-20 xl:pt-[4.5rem]">
                <h1 class="max-w-2xl text-[2.45rem] font-bold leading-[1.08] text-[#06122b] sm:text-[3rem] lg:text-[3.35rem]">
                    Oficjalna baza pytań
                    <span class="block text-[#d01921]">na prawo jazdy {{ $currentYear }}</span>
                </h1>

                <p class="mt-7 max-w-[37rem] text-[1.05rem] font-medium leading-8 text-[#06122b]">
                    Ćwicz z oficjalnymi pytaniami egzaminacyjnymi i zdaj egzamin za pierwszym razem.
                </p>
            </div>

            <div class="-mr-4 overflow-hidden sm:-mr-6 lg:-mr-6 xl:-mr-8">
                <img
                    src="{{ asset('images/questions/question-database-hero.png') }}"
                    alt="Samochód nauki jazdy i ekran z przykładowym pytaniem egzaminacyjnym"
                    width="576"
                    height="383"
                    class="block h-auto w-full select-none"
                    fetchpriority="high"
                    decoding="async"
                >
            </div>
        </div>
    </section>

    <section class="bg-white pb-12 pt-8 md:pt-10 lg:pt-12">
        <div class="site-shell">
            @if ($hasActiveSearch)
                <section class="mx-auto max-w-[1120px]" aria-labelledby="question-search-results-heading">
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

                                        <h3 class="mt-3 max-w-4xl text-[1.05rem] font-bold leading-7 text-[#111827] transition group-hover:text-[#d01921] md:text-[1.08rem]">
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

                                    <span class="hidden h-11 w-11 items-center justify-center justify-self-end text-[2rem] leading-none text-[#111827] transition group-hover:translate-x-1 group-hover:text-[#d01921] md:flex" aria-hidden="true">
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
                                            <span class="flex h-10 min-w-10 items-center justify-center rounded-[4px] bg-[#ffe6e8] px-3 text-sm font-bold text-[#d01921]">{{ $pageNumber }}</span>
                                        @else
                                            <a href="{{ $searchPaginator->url($pageNumber) }}" class="flex h-10 min-w-10 items-center justify-center rounded-[4px] border border-[#dce3eb] px-3 text-sm font-semibold text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]">{{ $pageNumber }}</a>
                                        @endif
                                    @endforeach

                                    @if ($searchLastPage > 8)
                                        <span class="px-2 text-sm text-[#64748b]">...</span>
                                        @foreach ($searchTailPages as $pageNumber)
                                            @if ($pageNumber === $searchCurrentPage)
                                                <span class="flex h-10 min-w-10 items-center justify-center rounded-[4px] bg-[#ffe6e8] px-3 text-sm font-bold text-[#d01921]">{{ $pageNumber }}</span>
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
                            <a href="{{ route('public.questions.hub') }}" class="ml-2 font-semibold text-[#d01921] transition hover:text-[#a91118]">Wyczyść</a>
                        </div>
                    @endif
                </section>
            @endif

            <div class="mx-auto max-w-4xl text-center">
                <h2 class="text-[1.7rem] font-bold leading-tight text-[#06122b]">
                    Kategorie prawa jazdy
                </h2>
                <p class="mt-2 text-[1rem] font-medium text-[#06122b]">
                    Wybierz kategorię i przejdź do oficjalnych pytań
                </p>
            </div>

            @if ($popularCategories->isNotEmpty())
                <div class="mx-auto mt-10 max-w-[1120px]">
                    <div class="flex items-center gap-3 text-[#d01921]">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12.6 2.3c.2 2.2-.7 3.9-2 5.2-1.2 1.2-2.8 2.2-4 3.8-1.1 1.4-1.7 3.1-1.4 5.1.4 3.1 3 5.3 6.7 5.3 4.2 0 7-2.7 7-6.6 0-2.8-1.6-5.1-3.2-6.9-.3 1.7-1.1 2.8-2.2 3.5.4-3.7-1.2-6.7-4.9-9.4Z" />
                        </svg>
                        <h3 class="text-[1.04rem] font-bold">Najczęściej wybierane</h3>
                    </div>

                    <div class="mt-5 grid gap-5 lg:grid-cols-3">
                        @foreach ($popularCategories as $category)
                            @php($theme = $themeFor($category))
                            <a
                                href="{{ $category['url'] }}"
                                class="group flex min-h-[250px] flex-col justify-between rounded-[8px] border border-[#dce3eb] bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition hover:-translate-y-0.5 hover:border-[#c4cfdb] hover:shadow-[0_14px_28px_rgba(15,23,42,0.08)]"
                                aria-label="Przejdź do pytań kategorii {{ $category['code'] }}"
                            >
                                <div class="grid grid-cols-[8.25rem_minmax(0,1fr)] items-start gap-3">
                                    @php($featuredIllustration = $featuredIllustrations[\Illuminate\Support\Str::upper((string) $category['code'])] ?? null)
                                    @if ($featuredIllustration)
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Vite::asset($featuredIllustration) }}"
                                            alt=""
                                            aria-hidden="true"
                                            loading="lazy"
                                            class="h-32 w-40 max-w-none object-contain object-left"
                                        >
                                    @else
                                        <div class="flex h-28 w-28 items-center justify-center rounded-full" style="background-color: {{ $theme['soft'] }}">
                                            <x-questions.vehicle-icon :code="$category['code']" class="h-[6.4rem] w-[6.4rem]" />
                                        </div>
                                    @endif
                                    <div class="min-w-0 pt-1">
                                        <span class="block text-[3.5rem] font-bold leading-none" style="color: {{ $theme['accent'] }}">
                                            {{ $category['code'] }}
                                        </span>
                                        <h4 class="mt-2 text-[1.05rem] font-bold leading-6 text-[#06122b]">{{ $category['title'] }}</h4>
                                        <p class="mt-1 text-[0.95rem] leading-6 text-[#06122b]">{{ $labelFor($category) }}</p>
                                    </div>
                                </div>

                                <div class="mt-5 flex items-center justify-between gap-4">
                                    <span class="inline-flex items-center gap-3 text-[0.95rem] font-semibold text-[#06122b]">
                                        <svg class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: {{ $theme['accent'] }}" aria-hidden="true">
                                            <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M12 17h.01" stroke-linecap="round" />
                                        </svg>
                                        {{ number_format($category['questions_count'], 0, ',', ' ') }} pytań
                                    </span>

                                    <span class="inline-flex min-h-10 items-center justify-center rounded-[5px] bg-[#0b56bf] px-5 text-[0.86rem] font-bold text-white transition group-hover:bg-[#084899]">
                                        Sprawdź <span class="ml-2" aria-hidden="true">→</span>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mx-auto mt-10 max-w-[1120px] space-y-8">
                @foreach ($categorySections as $section)
                    @php($sectionCategories = $categorySet($section['codes']))
                    @if ($sectionCategories->isNotEmpty())
                        <section aria-labelledby="question-category-section-{{ \Illuminate\Support\Str::slug($section['title']) }}">
                            <div class="flex items-center gap-3" style="color: {{ $section['accent'] }}">
                                @if ($section['icon'] === 'car')
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M5.4 7.2 7 4h10l1.6 3.2A3.2 3.2 0 0 1 21 10.3V17h-2a2.5 2.5 0 0 1-5 0H10a2.5 2.5 0 0 1-5 0H3v-6.7a3.2 3.2 0 0 1 2.4-3.1ZM8.2 6 7.1 8.2h9.8L15.8 6H8.2ZM7.5 18a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm9 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" />
                                    </svg>
                                @elseif ($section['icon'] === 'bike')
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M6 18a4 4 0 1 1 2.6-7.1L10 8H8V6h4.8l2.1 3.8H17a4 4 0 1 1-3.8 5.2H10a4 4 0 0 1-4 3Zm0-2a2 2 0 0 0 1.8-1.1H6.2l1.5-2.5A2 2 0 1 0 6 16Zm6.2-3h1.1l-1.1-2-1 2H12.2Zm5.8 3a2 2 0 0 0 0-4h-1.9l1 1.9-1.8.9A2 2 0 0 0 18 16Z" />
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M4 7h5l2 4h4l1.6-3H20v5h-1.1A3.5 3.5 0 1 1 12 14H9.9A3.5 3.5 0 1 1 3 14H2v-3h2V7Zm2.5 9A1.5 1.5 0 1 0 5 14.5 1.5 1.5 0 0 0 6.5 16Zm9 0A1.5 1.5 0 1 0 14 14.5a1.5 1.5 0 0 0 1.5 1.5Z" />
                                    </svg>
                                @endif
                                <h3 id="question-category-section-{{ \Illuminate\Support\Str::slug($section['title']) }}" class="text-[1.04rem] font-bold">
                                    {{ $section['title'] }}
                                </h3>
                            </div>

                            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ($sectionCategories as $category)
                                    @php($theme = $themeFor($category))
                                    <a
                                        href="{{ $category['url'] }}"
                                        class="group flex min-h-[215px] flex-col justify-between rounded-[8px] border border-[#dce3eb] bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.03)] transition hover:-translate-y-0.5 hover:border-[#c4cfdb] hover:shadow-[0_12px_24px_rgba(15,23,42,0.07)]"
                                        aria-label="Przejdź do pytań kategorii {{ $category['code'] }}"
                                    >
                                        <div class="grid min-h-16 grid-cols-[minmax(0,1fr)_96px] items-center gap-3">
                                            <span class="flex h-16 items-center text-[2.65rem] font-bold leading-none" style="color: {{ $theme['accent'] }}">
                                                {{ $category['code'] }}
                                            </span>
                                            <x-questions.vehicle-icon :code="$category['code']" class="h-16 w-full object-contain object-right" />
                                        </div>

                                        <div class="mt-4 min-w-0">
                                            <h4 class="text-[0.96rem] font-bold leading-6 text-[#06122b]">{{ $category['title'] }}</h4>
                                            <p class="mt-1 text-[0.92rem] leading-6 text-[#06122b]">{{ $labelFor($category) }}</p>
                                        </div>

                                        <div class="mt-5 flex items-center justify-between gap-3">
                                            <span class="inline-flex items-center gap-2 text-[0.88rem] text-[#06122b]">
                                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: {{ $theme['accent'] }}" aria-hidden="true">
                                                    <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M12 17h.01" stroke-linecap="round" />
                                                </svg>
                                                {{ number_format($category['questions_count'], 0, ',', ' ') }} pytań
                                            </span>
                                            <span class="text-[1.4rem] font-semibold leading-none transition group-hover:translate-x-1" style="color: {{ $theme['accent'] }}" aria-hidden="true">
                                                →
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endsection

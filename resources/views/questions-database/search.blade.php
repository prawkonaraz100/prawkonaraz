@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'site-shell')

@php
    $searchValue = trim((string) ($searchQuery ?? ''));
    $govValue = trim((string) ($govId ?? ''));
    $displaySearchValue = $searchValue !== '' ? $searchValue : $govValue;
    $hasActiveSearch = (bool) ($hasActiveSearch ?? false);
    $category = $category ?? null;
    $lastPage = max(1, (int) $questions->lastPage());
    $currentPage = max(1, (int) $questions->currentPage());
    $earlyPages = $lastPage >= 1 ? range(1, min(6, $lastPage)) : [];
    $tailPages = $lastPage > 8 ? range(max(7, $lastPage - 1), $lastPage) : [];
    $searchWithoutCategoryUrl = route('public.questions.search', array_filter([
        'q' => $searchValue !== '' ? $searchValue : null,
        'gov_id' => $searchValue === '' && $govValue !== '' ? $govValue : null,
    ], fn ($value) => $value !== null && $value !== ''));
@endphp

@section('content')
    <section class="bg-white">
        <div class="site-shell py-8 md:py-10 lg:py-12">
            <header class="mx-auto max-w-[980px]">
                <p class="content-kicker">Baza pytań</p>
                <h1 class="mt-3 text-[2rem] font-bold leading-tight text-[#06122b] md:text-[2.45rem]">
                    Wyniki wyszukiwania
                </h1>
            </header>

            <form method="GET" action="{{ route('public.questions.search') }}" class="mx-auto mt-6 max-w-[980px]">
                @if ($category instanceof \App\Models\LicenseCategory)
                    <input type="hidden" name="category" value="{{ $category->slug }}">
                @endif
                <label for="question-search-page-input" class="sr-only">Szukaj pytań</label>
                <div class="flex min-h-[64px] items-center gap-2 rounded-[10px] border border-[#d7dee8] bg-[#f8fafc] p-2 shadow-[0_12px_30px_rgba(15,23,42,0.06)] transition focus-within:border-[#9fb0c4] focus-within:bg-white focus-within:shadow-[0_16px_38px_rgba(15,23,42,0.09)]">
                    <button
                        type="submit"
                        class="grid h-11 w-11 shrink-0 place-items-center rounded-[7px] text-[#172033] transition hover:bg-white hover:text-[#d01921] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#d01921]/25"
                        aria-label="Szukaj wpisanej frazy"
                    >
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.4-3.4" stroke-linecap="round" />
                        </svg>
                    </button>
                    <input
                        id="question-search-page-input"
                        name="q"
                        type="search"
                        value="{{ $displaySearchValue }}"
                        autocomplete="off"
                        enterkeyhint="search"
                        placeholder="Numer albo treść pytania"
                        class="question-search-input min-w-0 flex-1 border-0 bg-transparent px-2 text-[1rem] font-medium text-[#111827] placeholder:text-[#748196] focus:bg-transparent focus:ring-0 md:text-[1.08rem]"
                        autofocus
                    >
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-[7px] bg-[#d01921] px-5 text-[0.9rem] font-bold text-white shadow-[0_10px_22px_rgba(208,25,33,0.22)] transition hover:bg-[#b9151c] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#d01921]/30 sm:px-6">
                        Szukaj
                    </button>
                </div>
            </form>

            <div class="mx-auto mt-5 flex max-w-[980px] flex-wrap items-center gap-3 text-[0.92rem] text-[#64748b]">
                @if ($hasActiveSearch)
                    <span>Wyniki dla: <strong class="font-semibold text-[#111827]">{{ $displaySearchValue }}</strong></span>
                @endif

                @if ($category instanceof \App\Models\LicenseCategory)
                    <span class="rounded-[6px] bg-[#e8f2ff] px-3 py-1 text-[0.82rem] font-bold text-[#1769c2]">
                        Kat. {{ $category->code }}
                    </span>
                    <a href="{{ $searchWithoutCategoryUrl }}" class="font-semibold text-[#d01921] transition hover:text-[#a91118]">
                        Cała baza
                    </a>
                @endif

                @if ($hasActiveSearch)
                    <a href="{{ route('public.questions.search') }}" class="font-semibold text-[#d01921] transition hover:text-[#a91118]">
                        Wyczyść
                    </a>
                @endif
            </div>

            <div class="mx-auto mt-7 max-w-[980px]">
                @if (! $hasActiveSearch)
                    <div class="rounded-[8px] border border-dashed border-[#dce3eb] bg-[#f8fafc] px-5 py-10 text-center text-[0.95rem] text-[#64748b]">
                        Wpisz numer albo treść pytania.
                    </div>
                @elseif ($questions->count() > 0)
                    <div class="overflow-hidden rounded-[8px] border border-[#dce3eb] bg-white">
                        @foreach ($questions as $question)
                            @php($categoryCodes = collect($question['category_codes'] ?? [])->filter()->values())
                            @php($visibleCategoryCodes = $categoryCodes->take(5))
                            @php($hiddenCategoryCount = max(0, $categoryCodes->count() - $visibleCategoryCodes->count()))
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
                                    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                                        <span class="text-[0.9rem] font-semibold text-[#43536a]">
                                            #{{ $question['display_external_id'] ?? $question['external_id'] }}
                                        </span>

                                        @if ($categoryCodes->isNotEmpty())
                                            <span class="text-[0.78rem] font-semibold text-[#758196]">
                                                {{ $categoryCodes->count() === 1 ? '1 kategoria' : $categoryCodes->count().' kategorii' }}
                                            </span>
                                        @endif

                                        @forelse ($visibleCategoryCodes as $categoryCode)
                                            <span class="inline-flex min-h-6 items-center rounded-full bg-[#eef4fb] px-2 text-[0.72rem] font-bold leading-none text-[#255d9f]" aria-label="Kategoria {{ $categoryCode }}">
                                                {{ $categoryCode }}
                                            </span>
                                        @empty
                                            <span class="inline-flex min-h-6 items-center rounded-full bg-[#eef4fb] px-2 text-[0.72rem] font-bold leading-none text-[#255d9f]" aria-label="Kategoria {{ $question['category_code'] ?? '' }}">
                                                {{ $question['category_code'] ?? '' }}
                                            </span>
                                        @endforelse

                                        @if ($hiddenCategoryCount > 0)
                                            <span class="inline-flex min-h-6 items-center rounded-full bg-[#f3f6fa] px-2 text-[0.72rem] font-bold leading-none text-[#64748b]" aria-label="Jeszcze {{ $hiddenCategoryCount }} kategorii">
                                                +{{ $hiddenCategoryCount }}
                                            </span>
                                        @endif
                                    </div>

                                    <h2 class="mt-3 max-w-4xl text-[1.05rem] font-bold leading-7 text-[#111827] transition group-hover:text-[#d01921] md:text-[1.08rem]">
                                        {{ $question['prompt_plain'] }}
                                    </h2>

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
                            Wyświetlono {{ number_format($questions->firstItem() ?? 0, 0, ',', ' ') }}-{{ number_format($questions->lastItem() ?? 0, 0, ',', ' ') }} z {{ number_format($questions->total(), 0, ',', ' ') }} pytań
                        </p>

                        @if ($questions->hasPages())
                            <nav class="flex flex-wrap items-center gap-2" aria-label="Paginacja wyników wyszukiwania">
                                @foreach ($earlyPages as $pageNumber)
                                    @if ($pageNumber === $currentPage)
                                        <span class="flex h-10 min-w-10 items-center justify-center rounded-[4px] bg-[#ffe6e8] px-3 text-sm font-bold text-[#d01921]">{{ $pageNumber }}</span>
                                    @else
                                        <a href="{{ $questions->url($pageNumber) }}" class="flex h-10 min-w-10 items-center justify-center rounded-[4px] border border-[#dce3eb] px-3 text-sm font-semibold text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]">{{ $pageNumber }}</a>
                                    @endif
                                @endforeach

                                @if ($lastPage > 8)
                                    <span class="px-2 text-sm text-[#64748b]">...</span>
                                    @foreach ($tailPages as $pageNumber)
                                        @if ($pageNumber === $currentPage)
                                            <span class="flex h-10 min-w-10 items-center justify-center rounded-[4px] bg-[#ffe6e8] px-3 text-sm font-bold text-[#d01921]">{{ $pageNumber }}</span>
                                        @else
                                            <a href="{{ $questions->url($pageNumber) }}" class="flex h-10 min-w-10 items-center justify-center rounded-[4px] border border-[#dce3eb] px-3 text-sm font-semibold text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]">{{ $pageNumber }}</a>
                                        @endif
                                    @endforeach
                                @endif

                                @if ($questions->hasMorePages())
                                    <a href="{{ $questions->nextPageUrl() }}" class="flex h-10 min-w-10 items-center justify-center rounded-[4px] border border-[#dce3eb] px-3 text-sm font-semibold text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]" aria-label="Następna strona">›</a>
                                @endif
                            </nav>
                        @endif
                    </div>
                @else
                    <div class="rounded-[8px] border border-dashed border-[#dce3eb] bg-[#f8fafc] px-5 py-10 text-center text-[0.95rem] text-[#64748b]">
                        Nie znaleziono pytań dla tego wyszukiwania.
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection

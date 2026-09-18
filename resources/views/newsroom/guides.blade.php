@extends('layouts.public-content')

@php
    $currentPage = $guides->currentPage();
    $lastPage = $guides->lastPage();
    $pageStart = max(1, $currentPage - 2);
    $pageEnd = min($lastPage, $currentPage + 2);
    $baseUrl = route('public.guides');
    $pageUrl = static fn (int $page): string => $page <= 1
        ? $baseUrl
        : $baseUrl.'?page='.$page;
    $items = collect($guides->items());
    $leadGuide = $items->first();
    $remainingGuides = $items->skip(1)->values();
@endphp

@section('content')
    <style>
        .newsroom-guides-title,
        .newsroom-guides-card-title {
            overflow-wrap: anywhere;
        }

        .newsroom-guides-image {
            aspect-ratio: 16 / 9;
        }
    </style>

    <section class="border-b border-slate-200 bg-[#fffdf3]">
        <div class="content-shell py-9 md:py-12">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Materiały praktyczne</p>
            <h1 class="newsroom-guides-title mt-2 max-w-4xl text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">
                Poradniki
            </h1>
            <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">
                Praktyczne materiały, które pomagają przejść przez formalności, naukę i egzamin bez zbędnego komplikowania.
            </p>
        </div>
    </section>

    <section class="content-band" aria-labelledby="guides-list-heading">
        <div class="content-shell py-9 md:py-12">
            @if ($guides->isEmpty())
                <div class="py-10">
                    <p class="text-lg font-semibold text-slate-950">Nie ma jeszcze opublikowanych poradników</p>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        Ta sekcja będzie zawierać wyłącznie publicznie dostępne, praktyczne materiały.
                    </p>
                    <a href="{{ route('public.news') }}" class="mt-5 inline-flex min-h-11 items-center justify-center border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-900 hover:border-slate-500 hover:underline">
                        Przejdź do aktualności
                    </a>
                </div>
            @else
                <div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Evergreen</p>
                        <h2 id="guides-list-heading" class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">
                            Praktyczne poradniki
                        </h2>
                    </div>
                    <p class="text-sm text-slate-500">Liczba poradników: {{ $guides->total() }}</p>
                </div>

                @if ($leadGuide)
                    <article
                        class="grid gap-6 border-b border-slate-200 py-7 md:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.65fr)] md:items-center"
                        data-article-id="{{ $leadGuide['id'] }}"
                        data-article-url="{{ $leadGuide['url'] }}"
                        data-article-type="{{ $leadGuide['type'] }}"
                        data-category-slug="{{ $leadGuide['category']['slug'] ?? '' }}"
                        data-newsroom-analytics-module="guides"
                        data-newsroom-analytics-position="lead"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-[0.1em]">
                                <span class="text-slate-500">Poradnik</span>
                                @if (! empty($leadGuide['category']))
                                    <span class="text-blue-800">{{ $leadGuide['category']['name'] }}</span>
                                @endif
                            </div>
                            <h3 class="newsroom-guides-card-title mt-2 text-2xl font-semibold leading-8 tracking-tight text-slate-950 md:text-3xl md:leading-10">
                                <a href="{{ $leadGuide['url'] }}" class="hover:underline">{{ $leadGuide['title'] }}</a>
                            </h3>
                            @if ($leadGuide['lead'])
                                <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">{{ $leadGuide['lead'] }}</p>
                            @endif
                            @if (! empty($leadGuide['author']))
                                <p class="mt-4 text-sm text-slate-500">
                                    <a href="{{ $leadGuide['author']['url'] }}" class="font-semibold text-slate-700 hover:underline">
                                        {{ $leadGuide['author']['name'] }}
                                    </a>
                                </p>
                            @endif
                        </div>

                        @if (! empty($leadGuide['hero']))
                            <a href="{{ $leadGuide['url'] }}" class="order-first block overflow-hidden bg-slate-100 md:order-none">
                                <img
                                    src="{{ $leadGuide['hero']['url'] }}"
                                    alt="{{ $leadGuide['hero']['alt'] }}"
                                    @if ($leadGuide['hero']['width']) width="{{ $leadGuide['hero']['width'] }}" @endif
                                    @if ($leadGuide['hero']['height']) height="{{ $leadGuide['hero']['height'] }}" @endif
                                    class="newsroom-guides-image block w-full object-cover"
                                    @if ($leadGuide['hero']['object_position']) style="object-position: {{ $leadGuide['hero']['object_position'] }}" @endif
                                    decoding="async"
                                    fetchpriority="high"
                                >
                            </a>
                        @endif
                    </article>
                @endif

                @if ($remainingGuides->isNotEmpty())
                    <div class="grid gap-x-7 gap-y-8 py-8 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($remainingGuides as $guide)
                            <article
                                data-article-id="{{ $guide['id'] }}"
                                data-article-url="{{ $guide['url'] }}"
                                data-article-type="{{ $guide['type'] }}"
                                data-category-slug="{{ $guide['category']['slug'] ?? '' }}"
                                data-newsroom-analytics-module="guides"
                                data-newsroom-analytics-position="{{ $loop->iteration }}"
                            >
                                @if (! empty($guide['hero']))
                                    <a href="{{ $guide['url'] }}" class="mb-4 block overflow-hidden bg-slate-100">
                                        <img
                                            src="{{ $guide['hero']['url'] }}"
                                            alt="{{ $guide['hero']['alt'] }}"
                                            @if ($guide['hero']['width']) width="{{ $guide['hero']['width'] }}" @endif
                                            @if ($guide['hero']['height']) height="{{ $guide['hero']['height'] }}" @endif
                                            class="newsroom-guides-image block w-full object-cover"
                                            @if ($guide['hero']['object_position']) style="object-position: {{ $guide['hero']['object_position'] }}" @endif
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    </a>
                                @endif
                                <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-[0.1em]">
                                    <span class="text-slate-500">Poradnik</span>
                                    @if (! empty($guide['category']))
                                        <span class="text-blue-800">{{ $guide['category']['name'] }}</span>
                                    @endif
                                </div>
                                <h3 class="newsroom-guides-card-title mt-2 text-lg font-semibold leading-7 text-slate-950">
                                    <a href="{{ $guide['url'] }}" class="hover:underline">{{ $guide['title'] }}</a>
                                </h3>
                                @if ($guide['lead'])
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $guide['lead'] }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif

                @if ($lastPage > 1)
                    <nav class="border-t border-slate-200 pt-6" aria-label="Paginacja poradników" data-analytics-module="pagination">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($currentPage > 1)
                                <a href="{{ $pageUrl($currentPage - 1) }}" rel="prev" class="inline-flex min-h-11 items-center justify-center border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-900 hover:border-slate-500 hover:underline">
                                    Poprzednia
                                </a>
                            @endif

                            @for ($page = $pageStart; $page <= $pageEnd; $page++)
                                @if ($page === $currentPage)
                                    <span aria-current="page" class="inline-flex min-h-11 min-w-11 items-center justify-center bg-slate-950 px-3 py-2 text-sm font-semibold text-white">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $pageUrl($page) }}" class="inline-flex min-h-11 min-w-11 items-center justify-center border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-900 hover:border-slate-500 hover:underline">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endfor

                            @if ($currentPage < $lastPage)
                                <a href="{{ $pageUrl($currentPage + 1) }}" rel="next" class="inline-flex min-h-11 items-center justify-center border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-900 hover:border-slate-500 hover:underline">
                                    Następna
                                </a>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </div>
    </section>
@endsection

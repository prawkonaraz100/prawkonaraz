@extends('layouts.public-content')

@php
    $tz = (string) config('app.timezone', 'Europe/Warsaw');

    $formatPublishedAt = static function (?string $value) use ($tz): ?string {
        if (! $value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->timezone($tz)->format('d.m.Y, H:i');
        } catch (\Throwable) {
            return null;
        }
    };

    $typeLabel = static function (?string $type): ?string {
        return match ($type) {
            'explainer' => 'Wyjaśnienie',
            'analysis' => 'Analiza',
            'report' => 'Raport',
            default => null,
        };
    };

    $currentPage = $articles->currentPage();
    $lastPage = $articles->lastPage();
    $pageStart = max(1, $currentPage - 2);
    $pageEnd = min($lastPage, $currentPage + 2);
    $categoryBaseUrl = route('public.news.categories.show', ['categorySlug' => $category['slug']]);
    $pageUrl = static fn (int $page): string => $page <= 1
        ? $categoryBaseUrl
        : $categoryBaseUrl.'?page='.$page;
@endphp

@section('content')
    <style>
        .newsroom-category-title,
        .newsroom-category-card-title {
            overflow-wrap: anywhere;
        }

        .newsroom-category-image {
            aspect-ratio: 16 / 9;
        }
    </style>

    <section class="content-band border-b border-slate-200">
        <div class="content-shell py-8 md:py-11">
            <a href="{{ route('public.news') }}" class="text-xs font-bold uppercase tracking-[0.14em] text-blue-800 hover:underline">
                Aktualności
            </a>
            <h1 class="newsroom-category-title mt-2 max-w-4xl text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">
                {{ $category['name'] }}
            </h1>
            @if ($category['description'])
                <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">
                    {{ $category['description'] }}
                </p>
            @endif
        </div>
    </section>

    <section class="content-band" aria-labelledby="newsroom-category-list-heading">
        <div class="content-shell py-8 md:py-12">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Materiały</p>
                    <h2 id="newsroom-category-list-heading" class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">
                        Najnowsze
                    </h2>
                </div>
                @if ($articles->total() > 0)
                    <p class="text-sm text-slate-500">
                        Liczba materiałów: {{ $articles->total() }}
                    </p>
                @endif
            </div>

            @if ($articles->isEmpty())
                <div class="py-12">
                    <p class="text-lg font-semibold text-slate-950">Nie ma jeszcze opublikowanych materiałów</p>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        W tej kategorii nie ma obecnie materiałów aktywnie dystrybuowanych.
                    </p>
                    <a href="{{ route('public.news') }}" class="mt-5 inline-flex min-h-11 items-center justify-center border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-900 hover:border-slate-500 hover:underline">
                        Wróć do aktualności
                    </a>
                </div>
            @else
                <div class="divide-y divide-slate-200">
                    @foreach ($articles as $article)
                        <article
                            class="grid gap-4 py-6 sm:grid-cols-[minmax(0,1fr)_180px] sm:items-start md:gap-7"
                            data-article-id="{{ $article['id'] }}"
                            data-article-url="{{ $article['url'] }}"
                            data-article-type="{{ $article['type'] }}"
                            data-category-slug="{{ $category['slug'] }}"
                            data-newsroom-analytics-module="category_{{ $category['slug'] }}"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                    @if ($label = $typeLabel($article['type']))
                                        <span class="font-bold uppercase tracking-[0.1em] text-blue-800">{{ $label }}</span>
                                    @endif
                                    @if ($published = $formatPublishedAt($article['first_published_at']))
                                        <time class="text-slate-500" datetime="{{ $article['first_published_at'] }}">{{ $published }}</time>
                                    @endif
                                </div>

                                <h3 class="newsroom-category-card-title mt-2 text-xl font-semibold leading-7 text-slate-950 md:text-2xl md:leading-8">
                                    <a href="{{ $article['url'] }}" class="hover:underline">{{ $article['title'] }}</a>
                                </h3>

                                @if ($article['lead'])
                                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 md:text-base">
                                        {{ $article['lead'] }}
                                    </p>
                                @endif

                                @if (! empty($article['author']))
                                    <p class="mt-3 text-sm text-slate-500">
                                        <a href="{{ $article['author']['url'] }}" class="font-semibold text-slate-700 hover:underline">
                                            {{ $article['author']['name'] }}
                                        </a>
                                    </p>
                                @endif
                            </div>

                            @if (! empty($article['hero']))
                                <a href="{{ $article['url'] }}" class="order-first block overflow-hidden bg-slate-100 sm:order-none">
                                    <img
                                        src="{{ $article['hero']['url'] }}"
                                        alt="{{ $article['hero']['alt'] }}"
                                        @if ($article['hero']['width']) width="{{ $article['hero']['width'] }}" @endif
                                        @if ($article['hero']['height']) height="{{ $article['hero']['height'] }}" @endif
                                        class="newsroom-category-image block w-full object-cover"
                                        @if ($article['hero']['object_position']) style="object-position: {{ $article['hero']['object_position'] }}" @endif
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>

                @if ($lastPage > 1)
                    <nav class="mt-8 border-t border-slate-200 pt-6" aria-label="Paginacja kategorii" data-analytics-module="pagination">
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

    @if ($relatedCategories !== [])
        <section class="border-t border-slate-200 bg-slate-50" aria-labelledby="newsroom-related-categories-heading">
            <div class="content-shell py-8 md:py-10">
                <h2 id="newsroom-related-categories-heading" class="text-lg font-semibold text-slate-950">Inne kategorie</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($relatedCategories as $relatedCategory)
                        <a href="{{ $relatedCategory['url'] }}" class="inline-flex min-h-11 items-center rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:border-slate-500 hover:underline">
                            {{ $relatedCategory['name'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

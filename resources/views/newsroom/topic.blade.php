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

    $typeLabel = static function (?string $type): string {
        return match ($type) {
            'guide' => 'Poradnik',
            'explainer' => 'Wyjaśnienie',
            'analysis' => 'Analiza',
            'report' => 'Raport',
            default => 'Aktualność',
        };
    };

    $currentPage = $articles->currentPage();
    $lastPage = $articles->lastPage();
    $pageStart = max(1, $currentPage - 2);
    $pageEnd = min($lastPage, $currentPage + 2);
    $topicBaseUrl = route('public.news.topics.show', ['topicSlug' => $topic['slug']]);
    $pageUrl = static fn (int $page): string => $page <= 1
        ? $topicBaseUrl
        : $topicBaseUrl.'?page='.$page;
@endphp

@section('content')
    <style>
        .newsroom-topic-title,
        .newsroom-topic-card-title {
            overflow-wrap: anywhere;
        }

        .newsroom-topic-image {
            aspect-ratio: 16 / 9;
        }
    </style>

    <section class="border-b border-slate-200 bg-[#fffdf3]">
        <div class="content-shell py-9 md:py-12">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Temat</p>
            <h1 class="newsroom-topic-title mt-2 max-w-4xl text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">
                {{ $topic['title'] }}
            </h1>
            <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">
                {{ $topic['description'] }}
            </p>
        </div>
    </section>

    @if ($featured)
        <section class="content-band border-b border-slate-200" aria-labelledby="newsroom-topic-featured-heading">
            <div class="content-shell py-8 md:py-10">
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-blue-800">Wyróżniony materiał</p>
                <article class="mt-4 grid gap-6 md:grid-cols-[minmax(0,1.3fr)_minmax(280px,0.7fr)] md:items-center" data-featured-article-id="{{ $featured['id'] }}">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                            <span class="font-bold uppercase tracking-[0.1em] text-slate-500">{{ $typeLabel($featured['type']) }}</span>
                            @if (! empty($featured['category']))
                                <span class="font-bold uppercase tracking-[0.1em] text-blue-800">{{ $featured['category']['name'] }}</span>
                            @endif
                            @if ($published = $formatPublishedAt($featured['first_published_at']))
                                <time class="text-slate-500" datetime="{{ $featured['first_published_at'] }}">{{ $published }}</time>
                            @endif
                        </div>

                        <h2 id="newsroom-topic-featured-heading" class="newsroom-topic-card-title mt-2 text-2xl font-semibold leading-8 tracking-tight text-slate-950 md:text-3xl md:leading-10">
                            <a href="{{ $featured['url'] }}" class="hover:underline">{{ $featured['title'] }}</a>
                        </h2>

                        @if ($featured['lead'])
                            <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">{{ $featured['lead'] }}</p>
                        @endif

                        @if (! empty($featured['author']))
                            <p class="mt-4 text-sm text-slate-500">
                                <a href="{{ $featured['author']['url'] }}" class="font-semibold text-slate-700 hover:underline">
                                    {{ $featured['author']['name'] }}
                                </a>
                            </p>
                        @endif
                    </div>

                    @if (! empty($featured['hero']))
                        <a href="{{ $featured['url'] }}" class="order-first block overflow-hidden bg-slate-100 md:order-none">
                            <img
                                src="{{ $featured['hero']['url'] }}"
                                alt="{{ $featured['hero']['alt'] }}"
                                @if ($featured['hero']['width']) width="{{ $featured['hero']['width'] }}" @endif
                                @if ($featured['hero']['height']) height="{{ $featured['hero']['height'] }}" @endif
                                class="newsroom-topic-image block w-full object-cover"
                                @if ($featured['hero']['object_position']) style="object-position: {{ $featured['hero']['object_position'] }}" @endif
                                decoding="async"
                                fetchpriority="high"
                            >
                        </a>
                    @endif
                </article>
            </div>
        </section>
    @endif

    <section class="content-band" aria-labelledby="newsroom-topic-list-heading">
        <div class="content-shell py-8 md:py-12">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Dossier</p>
                    <h2 id="newsroom-topic-list-heading" class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">
                        Materiały w temacie
                    </h2>
                </div>
                @if ($articles->total() > 0)
                    <p class="text-sm text-slate-500">
                        Pozostałe materiały: {{ $articles->total() }}
                    </p>
                @endif
            </div>

            @if ($articles->isEmpty())
                @if (! $featured)
                    <div class="py-12">
                        <p class="text-lg font-semibold text-slate-950">Brak aktywnie dystrybuowanych materiałów</p>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                            Ten opublikowany temat chwilowo nie ma materiałów spełniających warunki aktywnej dystrybucji.
                        </p>
                        <a href="{{ route('public.news') }}" class="mt-5 inline-flex min-h-11 items-center justify-center border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-900 hover:border-slate-500 hover:underline">
                            Wróć do aktualności
                        </a>
                    </div>
                @endif
            @else
                <div class="divide-y divide-slate-200">
                    @foreach ($articles as $article)
                        <article class="grid gap-4 py-6 sm:grid-cols-[minmax(0,1fr)_180px] sm:items-start md:gap-7" data-article-id="{{ $article['id'] }}">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                    <span class="font-bold uppercase tracking-[0.1em] text-slate-500">{{ $typeLabel($article['type']) }}</span>
                                    @if (! empty($article['category']))
                                        <span class="font-bold uppercase tracking-[0.1em] text-blue-800">{{ $article['category']['name'] }}</span>
                                    @endif
                                    @if ($published = $formatPublishedAt($article['first_published_at']))
                                        <time class="text-slate-500" datetime="{{ $article['first_published_at'] }}">{{ $published }}</time>
                                    @endif
                                </div>

                                <h3 class="newsroom-topic-card-title mt-2 text-xl font-semibold leading-7 text-slate-950 md:text-2xl md:leading-8">
                                    <a href="{{ $article['url'] }}" class="hover:underline">{{ $article['title'] }}</a>
                                </h3>

                                @if ($article['lead'])
                                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 md:text-base">{{ $article['lead'] }}</p>
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
                                        class="newsroom-topic-image block w-full object-cover"
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
                    <nav class="mt-8 border-t border-slate-200 pt-6" aria-label="Paginacja tematu" data-analytics-module="pagination">
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

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
            'guide' => 'Poradnik',
            'analysis' => 'Analiza',
            'report' => 'Raport',
            default => null,
        };
    };

    $hasLeadZone = ! empty($home['lead']) || ! empty($home['secondary']);
    $hasGuides = ! empty($home['guides']['lead']) || ! empty($home['guides']['items']);
@endphp

@section('content')
    <style>
        .newsroom-home-title,
        .newsroom-home-card-title {
            overflow-wrap: anywhere;
        }

        .newsroom-home-scroll {
            scrollbar-width: thin;
        }

        .newsroom-home-hero-image {
            aspect-ratio: 16 / 9;
        }

        @media (min-width: 1024px) {
            .newsroom-home-lead-grid {
                grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
            }
        }
    </style>

    <section class="content-band border-b border-slate-200">
        <div class="content-shell py-7 md:py-9">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Serwis informacyjny</p>
            <h1 class="newsroom-home-title mt-2 text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">Aktualności</h1>
            <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">
                Najważniejsze informacje o prawie jazdy, egzaminach, przepisach, WORD i bezpieczeństwie ruchu.
            </p>
        </div>
    </section>

    @if (! empty($home['latest']) || ! empty($home['categories']) || $hasGuides)
        <nav class="border-b border-slate-200 bg-white" aria-label="Sekcje aktualności">
            <div class="content-shell newsroom-home-scroll overflow-x-auto">
                <div class="flex min-w-max items-center gap-6 py-3 text-sm font-semibold text-slate-700">
                    @if (! empty($home['latest']))
                        <a href="#najnowsze" class="whitespace-nowrap hover:text-slate-950 hover:underline">Najnowsze</a>
                    @endif
                    @foreach ($home['categories'] as $block)
                        <a href="#kategoria-{{ $block['category']['slug'] }}" class="whitespace-nowrap hover:text-slate-950 hover:underline">
                            {{ $block['category']['name'] }}
                        </a>
                    @endforeach
                    @if ($hasGuides)
                        <a href="#poradniki" class="whitespace-nowrap hover:text-slate-950 hover:underline">Poradniki</a>
                    @endif
                </div>
            </div>
        </nav>
    @endif

    @if (! empty($home['important_now']))
        <section class="border-b border-slate-200 bg-slate-50" aria-label="Ważne teraz" data-analytics-module="important-now">
            <div class="content-shell flex items-center gap-4 py-3">
                <span class="shrink-0 text-xs font-bold uppercase tracking-[0.12em] text-slate-950">Ważne teraz</span>
                <div class="newsroom-home-scroll flex min-w-0 gap-5 overflow-x-auto text-sm text-slate-700">
                    @foreach ($home['important_now'] as $article)
                        <a href="{{ $article['url'] }}" class="shrink-0 whitespace-nowrap font-medium hover:text-slate-950 hover:underline">
                            {{ $article['title'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (! empty($home['breaking']))
        <section class="border-b border-[#e7cf73] bg-[#fff9dc]" aria-label="Ważna wiadomość" data-analytics-module="breaking">
            <div class="content-shell flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:gap-4">
                <span class="text-xs font-bold uppercase tracking-[0.14em] text-slate-950">Ważne</span>
                <a href="{{ $home['breaking']['url'] }}" class="newsroom-home-card-title text-sm font-semibold text-slate-950 hover:underline">
                    {{ $home['breaking']['title'] }}
                </a>
                @if ($published = $formatPublishedAt($home['breaking']['first_published_at']))
                    <time class="text-xs text-slate-500" datetime="{{ $home['breaking']['first_published_at'] }}">{{ $published }}</time>
                @endif
            </div>
        </section>
    @endif

    @if ($hasLeadZone)
        <section class="content-band" data-analytics-module="lead-zone">
            <div class="content-shell py-8 md:py-10">
                <div class="newsroom-home-lead-grid grid gap-8 lg:gap-10">
                    @if (! empty($home['lead']))
                        @php($lead = $home['lead'])
                        <article data-article-id="{{ $lead['id'] }}">
                            @if (! empty($lead['hero']))
                                <a href="{{ $lead['url'] }}" class="block overflow-hidden bg-slate-100">
                                    <img
                                        src="{{ $lead['hero']['url'] }}"
                                        alt="{{ $lead['hero']['alt'] }}"
                                        @if ($lead['hero']['width']) width="{{ $lead['hero']['width'] }}" @endif
                                        @if ($lead['hero']['height']) height="{{ $lead['hero']['height'] }}" @endif
                                        class="newsroom-home-hero-image block w-full object-cover"
                                        @if ($lead['hero']['object_position']) style="object-position: {{ $lead['hero']['object_position'] }}" @endif
                                        decoding="async"
                                        fetchpriority="high"
                                    >
                                </a>
                            @endif

                            <div class="{{ ! empty($lead['hero']) ? 'mt-5' : '' }}">
                                <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-[0.1em]">
                                    @if (! empty($lead['category']))
                                        <span class="text-blue-800">{{ $lead['category']['name'] }}</span>
                                    @endif
                                    @if ($label = $typeLabel($lead['type']))
                                        <span class="text-slate-500">{{ $label }}</span>
                                    @endif
                                </div>
                                <h2 class="newsroom-home-title mt-2 text-[2rem] font-semibold leading-[1.12] tracking-tight text-slate-950 sm:text-[2.45rem] lg:text-[3rem]">
                                    <a href="{{ $lead['url'] }}" class="hover:underline">{{ $lead['title'] }}</a>
                                </h2>
                                @if ($lead['lead'])
                                    <p class="mt-4 max-w-3xl text-[17px] leading-7 text-slate-700 sm:text-lg">{{ $lead['lead'] }}</p>
                                @endif
                                <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                                    @if (! empty($lead['author']))
                                        <a href="{{ $lead['author']['url'] }}" class="font-semibold text-slate-700 hover:underline">{{ $lead['author']['name'] }}</a>
                                    @endif
                                    @if ($published = $formatPublishedAt($lead['first_published_at']))
                                        <time datetime="{{ $lead['first_published_at'] }}">{{ $published }}</time>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endif

                    @if (! empty($home['secondary']))
                        <div class="divide-y divide-slate-200 border-y border-slate-200 lg:border-t-0" aria-label="Pozostałe najważniejsze materiały">
                            @foreach ($home['secondary'] as $article)
                                <article class="py-5 first:pt-0 lg:first:pt-5" data-article-id="{{ $article['id'] }}">
                                    <div class="flex gap-4">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-[0.1em]">
                                                @if (! empty($article['category']))
                                                    <span class="text-blue-800">{{ $article['category']['name'] }}</span>
                                                @endif
                                                @if ($label = $typeLabel($article['type']))
                                                    <span class="text-slate-500">{{ $label }}</span>
                                                @endif
                                            </div>
                                            <h2 class="newsroom-home-card-title mt-2 text-lg font-semibold leading-6 text-slate-950">
                                                <a href="{{ $article['url'] }}" class="hover:underline">{{ $article['title'] }}</a>
                                            </h2>
                                            @if ($published = $formatPublishedAt($article['first_published_at']))
                                                <time class="mt-2 block text-xs text-slate-500" datetime="{{ $article['first_published_at'] }}">{{ $published }}</time>
                                            @endif
                                        </div>
                                        @if (! empty($article['hero']))
                                            <a href="{{ $article['url'] }}" class="block h-20 w-28 shrink-0 overflow-hidden bg-slate-100">
                                                <img
                                                    src="{{ $article['hero']['url'] }}"
                                                    alt="{{ $article['hero']['alt'] }}"
                                                    class="h-full w-full object-cover"
                                                    @if ($article['hero']['object_position']) style="object-position: {{ $article['hero']['object_position'] }}" @endif
                                                    loading="lazy"
                                                    decoding="async"
                                                >
                                            </a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if (! empty($home['latest']))
        <section id="najnowsze" class="content-band border-t border-slate-200" aria-labelledby="newsroom-latest-heading" data-analytics-module="latest">
            <div class="content-shell py-9 md:py-12">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Na bieżąco</p>
                        <h2 id="newsroom-latest-heading" class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Najnowsze</h2>
                    </div>
                </div>

                <div class="mt-5 divide-y divide-slate-200 border-y border-slate-200">
                    @foreach ($home['latest'] as $article)
                        <article class="grid gap-2 py-4 sm:grid-cols-[130px_minmax(0,1fr)_auto] sm:items-center sm:gap-5" data-article-id="{{ $article['id'] }}">
                            <div class="text-xs text-slate-500">
                                @if ($published = $formatPublishedAt($article['first_published_at']))
                                    <time datetime="{{ $article['first_published_at'] }}">{{ $published }}</time>
                                @endif
                            </div>
                            <div class="min-w-0">
                                @if (! empty($article['category']))
                                    <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-blue-800">{{ $article['category']['name'] }}</p>
                                @endif
                                <h3 class="newsroom-home-card-title mt-1 text-base font-semibold leading-6 text-slate-950 sm:text-lg">
                                    <a href="{{ $article['url'] }}" class="hover:underline">{{ $article['title'] }}</a>
                                </h3>
                            </div>
                            @if (! empty($article['hero']))
                                <a href="{{ $article['url'] }}" class="hidden h-16 w-24 overflow-hidden bg-slate-100 sm:block">
                                    <img
                                        src="{{ $article['hero']['url'] }}"
                                        alt="{{ $article['hero']['alt'] }}"
                                        class="h-full w-full object-cover"
                                        @if ($article['hero']['object_position']) style="object-position: {{ $article['hero']['object_position'] }}" @endif
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @foreach ($home['categories'] as $block)
        <section id="kategoria-{{ $block['category']['slug'] }}" class="content-band border-t border-slate-200" aria-labelledby="category-heading-{{ $block['category']['id'] }}" data-analytics-module="category">
            <div class="content-shell py-9 md:py-12">
                <div class="max-w-3xl">
                    <h2 id="category-heading-{{ $block['category']['id'] }}" class="text-2xl font-semibold tracking-tight text-slate-950">{{ $block['category']['name'] }}</h2>
                    @if ($block['category']['description'])
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $block['category']['description'] }}</p>
                    @endif
                </div>

                <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    @if (! empty($block['lead']))
                        @php($article = $block['lead'])
                        <article class="md:col-span-2" data-article-id="{{ $article['id'] }}">
                            @if (! empty($article['hero']))
                                <a href="{{ $article['url'] }}" class="block overflow-hidden bg-slate-100">
                                    <img
                                        src="{{ $article['hero']['url'] }}"
                                        alt="{{ $article['hero']['alt'] }}"
                                        class="newsroom-home-hero-image block w-full object-cover"
                                        @if ($article['hero']['object_position']) style="object-position: {{ $article['hero']['object_position'] }}" @endif
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </a>
                            @endif
                            <h3 class="newsroom-home-card-title mt-3 text-xl font-semibold leading-7 text-slate-950">
                                <a href="{{ $article['url'] }}" class="hover:underline">{{ $article['title'] }}</a>
                            </h3>
                            @if ($article['lead'])
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $article['lead'] }}</p>
                            @endif
                        </article>
                    @endif

                    @foreach ($block['items'] as $article)
                        <article class="border-t border-slate-200 pt-4 md:border-t-0 md:pt-0" data-article-id="{{ $article['id'] }}">
                            @if (! empty($article['hero']))
                                <a href="{{ $article['url'] }}" class="mb-3 block overflow-hidden bg-slate-100">
                                    <img
                                        src="{{ $article['hero']['url'] }}"
                                        alt="{{ $article['hero']['alt'] }}"
                                        class="newsroom-home-hero-image block w-full object-cover"
                                        @if ($article['hero']['object_position']) style="object-position: {{ $article['hero']['object_position'] }}" @endif
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </a>
                            @endif
                            <h3 class="newsroom-home-card-title text-base font-semibold leading-6 text-slate-950">
                                <a href="{{ $article['url'] }}" class="hover:underline">{{ $article['title'] }}</a>
                            </h3>
                            @if ($published = $formatPublishedAt($article['first_published_at']))
                                <time class="mt-2 block text-xs text-slate-500" datetime="{{ $article['first_published_at'] }}">{{ $published }}</time>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

    @if ($hasGuides)
        <section id="poradniki" class="border-t border-slate-200 bg-slate-50" aria-labelledby="newsroom-guides-heading" data-analytics-module="guides">
            <div class="content-shell py-9 md:py-12">
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Materiały praktyczne</p>
                <h2 id="newsroom-guides-heading" class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Poradniki</h2>

                <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    @if (! empty($home['guides']['lead']))
                        @php($article = $home['guides']['lead'])
                        <article class="border-l-4 border-[#efc54f] bg-white px-5 py-5 md:col-span-2" data-article-id="{{ $article['id'] }}">
                            <p class="text-xs font-bold uppercase tracking-[0.1em] text-slate-500">Poradnik</p>
                            <h3 class="newsroom-home-card-title mt-2 text-xl font-semibold leading-7 text-slate-950">
                                <a href="{{ $article['url'] }}" class="hover:underline">{{ $article['title'] }}</a>
                            </h3>
                            @if ($article['lead'])
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $article['lead'] }}</p>
                            @endif
                        </article>
                    @endif

                    @foreach ($home['guides']['items'] as $article)
                        <article class="bg-white px-5 py-5" data-article-id="{{ $article['id'] }}">
                            <p class="text-xs font-bold uppercase tracking-[0.1em] text-slate-500">Poradnik</p>
                            <h3 class="newsroom-home-card-title mt-2 text-base font-semibold leading-6 text-slate-950">
                                <a href="{{ $article['url'] }}" class="hover:underline">{{ $article['title'] }}</a>
                            </h3>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="border-y border-slate-200 bg-[#fffdf3]" aria-labelledby="newsroom-product-bridge-heading" data-analytics-module="product-bridge">
        <div class="content-shell py-9 md:py-11">
            <div class="grid gap-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Sprawdź się</p>
                    <h2 id="newsroom-product-bridge-heading" class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Czy zdałbyś teorię dzisiaj?</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Rozwiąż bezpłatny test 20 pytań z oficjalnej bazy — bez logowania.</p>
                </div>
                <a href="{{ route('public.tests') }}" class="inline-flex min-h-11 items-center justify-center bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                    Rozpocznij bezpłatny test
                </a>
            </div>
        </div>
    </section>
@endsection

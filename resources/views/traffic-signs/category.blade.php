@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'site-shell')

@php
    $visibleSigns = $signs->take(20);
    $hiddenSigns = $signs->slice(20);
    $categoryHeroImageUrl = $categoryHeroSign['hero_image_url'] ?? $categoryHeroSign['image_url'] ?? null;
@endphp

@section('content')
    <section class="traffic-sign-category bg-white">
        <div class="site-shell traffic-sign-category__hero relative grid gap-8 pb-8 pt-7 lg:items-center">
            <div class="traffic-sign-category__hero-copy max-w-2xl">
                <p class="traffic-sign-category__eyebrow text-[13px] font-bold uppercase tracking-normal">Kategoria znaków</p>
                <h1 class="mt-4 text-[44px] font-semibold leading-[1.08] text-slate-950">{{ $category->name }}</h1>
                @if ($category->intro_body || $category->description)
                    <p class="mt-6 max-w-[40rem] text-[17px] leading-8 text-slate-700">{{ $category->intro_body ?: $category->description }}</p>
                @endif
            </div>

            @if ($categoryHeroSign && $categoryHeroImageUrl)
                <div class="traffic-sign-category__hero-art flex items-center justify-center">
                    <img
                        src="{{ $categoryHeroImageUrl }}"
                        alt="{{ $categoryHeroSign['image_alt'] }}"
                        class="w-auto object-contain"
                        loading="eager"
                    >
                </div>
            @endif
        </div>
    </section>

    <section class="traffic-sign-category bg-white">
        <div class="site-shell traffic-sign-category__content grid gap-4 pb-10">
            <div class="traffic-sign-category__list overflow-hidden rounded-[6px] border border-slate-200 bg-white">
                <div class="traffic-sign-category__list-heading flex flex-col gap-4 border-b border-slate-200 px-7 py-7 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[13px] font-semibold uppercase tracking-normal text-slate-500">Znaki w kategorii</p>
                        <h2 class="mt-2 text-[26px] font-semibold leading-tight text-slate-950">Opublikowane strony</h2>
                    </div>
                    <p class="text-[15px] font-semibold text-slate-950">{{ $signs->count() }} znaków</p>
                </div>

                <div class="traffic-sign-category__table-head hidden grid-cols-[110px_minmax(0,1fr)_180px_28px] border-b border-slate-200 px-7 py-4 text-[12px] font-semibold uppercase text-slate-500 lg:grid">
                    <span>Znak</span>
                    <span>Opis</span>
                    <span>Aktualizacja</span>
                    <span></span>
                </div>

                <div class="divide-y divide-slate-200">
                    @forelse ($visibleSigns as $sign)
                        @php
                            $signImageUrl = $sign['cutout_image_url'] ?? $sign['image_url'];
                        @endphp
                        <a href="{{ $sign['url'] }}" class="traffic-sign-category__sign-row grid gap-4 px-6 py-4 transition hover:bg-slate-50 lg:grid-cols-[110px_minmax(0,1fr)_180px_28px] lg:items-center lg:px-7">
                            <span class="flex h-[68px] items-center justify-start overflow-visible lg:justify-center">
                                @if ($signImageUrl)
                                    <img src="{{ $signImageUrl }}" alt="{{ $sign['image_alt'] }}" class="max-h-[76px] max-w-[98px] object-contain" loading="lazy">
                                @else
                                    <span class="text-sm font-semibold text-slate-500">{{ $sign['code'] }}</span>
                                @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[16px] font-semibold leading-6 text-slate-950">{{ $sign['title'] }}</span>
                                @if ($sign['intro'])
                                    <span class="mt-1 block max-w-4xl text-[14px] leading-6 text-slate-700">{{ $sign['intro'] }}</span>
                                @endif
                            </span>
                            <span class="text-[14px] leading-6 text-slate-700">
                                @if ($sign['author_url'])
                                    <span class="block">{{ $sign['author_name'] }}</span>
                                @endif
                                @if ($sign['updated_at'])
                                    <span class="block text-slate-600">{{ $sign['updated_at'] }}</span>
                                @endif
                            </span>
                            <span class="hidden justify-self-end text-slate-950 lg:block" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                                    <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </a>
                    @empty
                        <p class="px-7 py-6 text-sm text-slate-500">Ta kategoria nie ma jeszcze opublikowanych znaków.</p>
                    @endforelse

                    @if ($hiddenSigns->isNotEmpty())
                        <details class="group">
                            <summary class="traffic-sign-category__more flex cursor-pointer list-none items-center justify-center gap-3 px-6 py-5 text-[15px] font-semibold text-slate-950 transition hover:bg-slate-50">
                                Pokaż więcej znaków
                                <svg class="h-5 w-5 transition group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </summary>
                            <div class="divide-y divide-slate-200 border-t border-slate-200">
                                @foreach ($hiddenSigns as $sign)
                                    @php
                                        $signImageUrl = $sign['cutout_image_url'] ?? $sign['image_url'];
                                    @endphp
                                    <a href="{{ $sign['url'] }}" class="traffic-sign-category__sign-row grid gap-4 px-6 py-4 transition hover:bg-slate-50 lg:grid-cols-[110px_minmax(0,1fr)_180px_28px] lg:items-center lg:px-7">
                                        <span class="flex h-[68px] items-center justify-start overflow-visible lg:justify-center">
                                            @if ($signImageUrl)
                                                <img src="{{ $signImageUrl }}" alt="{{ $sign['image_alt'] }}" class="max-h-[76px] max-w-[98px] object-contain" loading="lazy">
                                            @else
                                                <span class="text-sm font-semibold text-slate-500">{{ $sign['code'] }}</span>
                                            @endif
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-[16px] font-semibold leading-6 text-slate-950">{{ $sign['title'] }}</span>
                                            @if ($sign['intro'])
                                                <span class="mt-1 block max-w-4xl text-[14px] leading-6 text-slate-700">{{ $sign['intro'] }}</span>
                                            @endif
                                        </span>
                                        <span class="text-[14px] leading-6 text-slate-700">
                                            @if ($sign['author_url'])
                                                <span class="block">{{ $sign['author_name'] }}</span>
                                            @endif
                                            @if ($sign['updated_at'])
                                                <span class="block text-slate-600">{{ $sign['updated_at'] }}</span>
                                            @endif
                                        </span>
                                        <span class="hidden justify-self-end text-slate-950 lg:block" aria-hidden="true">
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                                                <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            </div>
            @include('traffic-signs.partials.category-aside')
        </div>
    </section>
@endsection

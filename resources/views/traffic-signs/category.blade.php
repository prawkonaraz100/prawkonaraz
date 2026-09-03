@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'site-shell')

@php
    $visibleSigns = $signs->take(20);
    $hiddenSigns = $signs->slice(20);
    $categoryHeroImageUrl = $categoryHeroSign['hero_image_url'] ?? $categoryHeroSign['image_url'] ?? null;
@endphp

@section('content')
    <section class="bg-white">
        <div class="site-shell relative grid gap-8 pb-8 pt-7 lg:min-h-[270px] lg:grid-cols-[minmax(0,1fr)_520px] lg:items-center lg:pb-10 lg:pt-9">
            <div class="max-w-2xl">
                <p class="text-[13px] font-bold uppercase tracking-normal text-[#d50000]">Kategoria znaków</p>
                <h1 class="mt-4 text-[44px] font-semibold leading-[1.08] text-slate-950">{{ $category->name }}</h1>
                @if ($category->intro_body || $category->description)
                    <p class="mt-6 max-w-[40rem] text-[17px] leading-8 text-slate-700">{{ $category->intro_body ?: $category->description }}</p>
                @endif
            </div>

            @if ($categoryHeroSign && $categoryHeroImageUrl)
                <div class="flex min-h-[200px] items-center justify-center lg:absolute lg:right-12 lg:top-2 lg:h-[250px] lg:w-[480px] lg:items-start lg:justify-end">
                    <img
                        src="{{ $categoryHeroImageUrl }}"
                        alt="{{ $categoryHeroSign['image_alt'] }}"
                        class="max-h-[230px] w-auto max-w-[310px] object-contain lg:max-h-[240px] lg:max-w-[340px]"
                        loading="eager"
                    >
                </div>
            @endif
        </div>
    </section>

    <section class="bg-white">
        <div class="site-shell grid gap-4 pb-10 lg:grid-cols-[390px_minmax(0,1fr)]">
            <aside class="space-y-8">
                @if ($supportingPages->isNotEmpty())
                    <div class="overflow-hidden rounded-[6px] border border-slate-200 bg-white">
                        <div class="border-b border-slate-200 px-6 py-6">
                            <h2 class="text-[15px] font-bold uppercase text-slate-950">Powiązane porównania</h2>
                            <p class="mt-2 text-[15px] leading-6 text-slate-700">Najmocniejsze materiały w tej kategorii</p>
                            <p class="mt-3 text-[15px] leading-6 text-slate-700">{{ $supportingPages->count() }} stron wspierających</p>
                        </div>

                        <div class="divide-y divide-slate-200">
                            @foreach ($supportingPages as $supportingPage)
                                @php
                                    $thumbCount = count($supportingPage['thumbs']);
                                @endphp
                                <a href="{{ $supportingPage['url'] }}" class="grid min-h-[104px] grid-cols-[74px_minmax(0,1fr)_20px] items-center gap-4 px-5 py-4 transition hover:bg-slate-50">
                                    <span class="flex h-[66px] w-[66px] items-center justify-center overflow-hidden rounded-[4px] border border-slate-200 bg-white">
                                        @if ($thumbCount > 0)
                                            <span class="{{ $thumbCount >= 3 ? 'grid grid-cols-2 place-items-center gap-0.5' : 'flex items-center justify-center gap-1.5' }}">
                                                @foreach ($supportingPage['thumbs'] as $thumb)
                                                    @if ($thumb['image_url'])
                                                        <img
                                                            src="{{ $thumb['image_url'] }}"
                                                            alt="{{ $thumb['image_alt'] }}"
                                                            class="{{ $thumbCount === 1 ? 'max-h-[52px] max-w-[52px]' : ($thumbCount === 2 ? 'max-h-[35px] max-w-[29px]' : 'max-h-[27px] max-w-[27px]'.($loop->iteration === 3 ? ' col-span-2 justify-self-center' : '')) }} object-contain"
                                                            loading="lazy"
                                                        >
                                                    @endif
                                                @endforeach
                                            </span>
                                        @else
                                            <span class="text-xs font-semibold text-slate-400">BRD</span>
                                        @endif
                                    </span>
                                    <span class="text-[15px] font-semibold leading-6 text-slate-950">{{ $supportingPage['title'] }}</span>
                                    <span class="text-slate-950" aria-hidden="true">
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                                            <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="relative overflow-hidden rounded-[6px] border border-slate-200 bg-white px-8 py-9">
                    <div class="relative z-10 max-w-[17rem]">
                        <h2 class="text-[22px] font-semibold leading-tight text-slate-950">Ucz się skuteczniej</h2>
                        <p class="mt-4 text-[16px] leading-7 text-slate-700">Rozwiązuj pytania i utrwalaj znaki w praktyce.</p>
                        <a
                            href="{{ route('public.tests', absolute: false) }}"
                            class="mt-8 inline-flex min-h-14 items-center justify-center gap-4 rounded-[4px] bg-[#d50000] px-9 text-[16px] font-semibold text-white transition hover:bg-red-700"
                        >
                            Przejdź do testów
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M4 10h10.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                <path d="M10.5 5.5 15 10l-4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </div>
                    <svg class="absolute bottom-5 right-5 h-48 w-48 text-slate-100" viewBox="0 0 160 160" fill="none" aria-hidden="true">
                        <path d="M47 28h45l20 20v78H47V28Z" stroke="currentColor" stroke-width="4" />
                        <path d="M92 28v22h20" stroke="currentColor" stroke-width="4" />
                        <path d="M59 65h30M59 82h38M59 99h28" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                        <path d="m45 129 50-18 18 18-50 18-22 4 4-22Z" fill="currentColor" opacity=".55" />
                        <path d="m115 88 16 9 16-9-16-9-16 9Z" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
                    </svg>
                </div>
            </aside>

            <div class="overflow-hidden rounded-[6px] border border-slate-200 bg-white">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-7 py-7 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[13px] font-semibold uppercase tracking-normal text-slate-500">Znaki w kategorii</p>
                        <h2 class="mt-2 text-[26px] font-semibold leading-tight text-slate-950">Opublikowane strony</h2>
                    </div>
                    <p class="text-[15px] font-semibold text-slate-950">{{ $signs->count() }} znaków</p>
                </div>

                <div class="hidden grid-cols-[110px_minmax(0,1fr)_180px_28px] border-b border-slate-200 px-7 py-4 text-[12px] font-semibold uppercase text-slate-500 lg:grid">
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
                        <a href="{{ $sign['url'] }}" class="grid gap-4 px-6 py-4 transition hover:bg-slate-50 lg:grid-cols-[110px_minmax(0,1fr)_180px_28px] lg:items-center lg:px-7">
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
                            <summary class="flex cursor-pointer list-none items-center justify-center gap-3 px-6 py-5 text-[15px] font-semibold text-slate-950 transition hover:bg-slate-50">
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
                                    <a href="{{ $sign['url'] }}" class="grid gap-4 px-6 py-4 transition hover:bg-slate-50 lg:grid-cols-[110px_minmax(0,1fr)_180px_28px] lg:items-center lg:px-7">
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
        </div>
    </section>
@endsection

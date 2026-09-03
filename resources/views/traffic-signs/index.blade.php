@extends('layouts.public-content')

@section('content')
    <section class="content-band overflow-hidden">
        <div class="content-shell grid gap-7 pb-9 pt-7 md:pb-11 md:pt-9 lg:grid-cols-[minmax(0,0.9fr)_minmax(420px,0.92fr)] lg:items-start lg:gap-10 lg:py-0">
            <div class="relative z-10 max-w-2xl py-2 lg:pb-14 lg:pt-8 xl:pb-16 xl:pt-9">
                <p class="text-sm font-bold text-[#d01921]">Znaki drogowe</p>
                <h1 class="mt-3 max-w-2xl text-[2.3rem] font-bold leading-[1.1] text-slate-950 md:text-[2.4rem]">
                    Poznaj znaki drogowe <span class="lg:block">i ich znaczenie.</span>
                </h1>
                <p class="content-muted mt-5 max-w-[40rem] text-base leading-7 md:text-lg">
                    Kompleksowa baza znaków drogowych zgodna z obowiązującymi przepisami. Przejrzyste kategorie, dokładne opisy i praktyczne przykłady pomogą Ci szybko zrozumieć ich znaczenie i zastosowanie w praktyce.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="#traffic-sign-categories" class="inline-flex items-center justify-center gap-2 rounded-[4px] bg-[#d01921] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#b9151c] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d01921]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4.75 5.75A2.75 2.75 0 0 1 7.5 3h3.75A2.75 2.75 0 0 1 14 5.75V21a3.5 3.5 0 0 0-2.75-1.35H7.5A2.75 2.75 0 0 0 4.75 22V5.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <path d="M14 5.75A2.75 2.75 0 0 1 16.75 3h3.75a2.75 2.75 0 0 1 2.75 2.75V22a2.75 2.75 0 0 0-2.75-2.35h-3.75A3.5 3.5 0 0 0 14 21V5.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                        </svg>
                        Przeglądaj znaki
                    </a>
                    <a href="{{ route('about.how-it-works') }}" class="inline-flex items-center justify-center gap-2 rounded-[4px] border border-slate-400 bg-white px-5 py-3 text-sm font-bold text-slate-950 transition hover:border-[#d01921] hover:text-[#d01921] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d01921]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3.5 19.25 6v5.6c0 4.42-2.82 7.52-7.25 8.9-4.43-1.38-7.25-4.48-7.25-8.9V6L12 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <path d="m8.75 12.1 2.1 2.1 4.55-4.65" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Jak to działa?
                    </a>
                </div>
            </div>

            <div class="relative -mr-4 overflow-hidden sm:-mr-6 lg:-mr-6 xl:-mr-8">
                <img
                    src="{{ asset('images/traffic-signs/traffic-signs-hero-clean.png') }}"
                    alt="Znaki STOP, przejścia dla pieszych i ograniczenia prędkości przy ulicy"
                    width="484"
                    height="353"
                    class="block h-auto w-full select-none"
                    fetchpriority="high"
                    decoding="async"
                >
                <div class="pointer-events-none absolute inset-y-0 left-0 w-12 bg-gradient-to-r from-white via-white/90 to-transparent sm:w-14 lg:w-20" aria-hidden="true"></div>
            </div>
        </div>
    </section>

    <section class="bg-[#fafafb]">
        <div class="site-shell pb-3">
            <div id="traffic-sign-categories" class="rounded-[6px] border border-slate-200 bg-white">
                <div class="px-6 pb-4 pt-6">
                    <h2 class="text-[26px] font-semibold leading-tight text-slate-950">Kategorie znaków</h2>
                    <p class="mt-3 text-[17px] text-slate-700">Wybierz grupę znaków:</p>
                </div>

                @if ($categoryColumns->isNotEmpty())
                    <div class="grid gap-5 px-5 pb-6 lg:grid-cols-2">
                        @foreach ($categoryColumns as $categoryColumn)
                            <div class="overflow-hidden rounded-[6px] border border-slate-200 bg-white">
                                @foreach ($categoryColumn as $category)
                                    <a
                                        href="{{ $category['url'] }}"
                                        class="grid min-h-[76px] grid-cols-[54px_minmax(0,1fr)_auto_24px] items-center gap-4 border-b border-slate-200 px-4 py-3 text-slate-950 transition hover:bg-slate-50 last:border-b-0"
                                    >
                                        <span class="flex h-12 w-12 items-center justify-center overflow-visible rounded-[4px] bg-white">
                                            @if ($category['icon_url'])
                                                <img src="{{ $category['icon_url'] }}" alt="{{ $category['icon_alt'] }}" class="max-h-12 max-w-12 object-contain" loading="lazy">
                                            @else
                                                <span class="flex h-10 w-10 items-center justify-center rounded-[4px] border border-slate-200 text-sm font-semibold text-slate-500">
                                                    {{ mb_substr($category['name'], 0, 1) }}
                                                </span>
                                            @endif
                                        </span>
                                        <span class="min-w-0 font-semibold leading-6">{{ $category['name'] }}</span>
                                        <span class="min-w-10 text-center font-semibold tabular-nums text-slate-950">
                                            {{ number_format($category['count'], 0, ',', ' ') }}
                                        </span>
                                        <span class="flex justify-end text-slate-950" aria-hidden="true">
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                                                <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="px-6 pb-6 text-sm text-slate-500">Nie ma jeszcze opublikowanych kategorii znaków.</p>
                @endif
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="site-shell py-4">
            <div class="rounded-[6px] border border-slate-200 bg-white">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-[22px] font-semibold leading-tight text-slate-950">Ostatnio aktualizowane</h2>
                        <p class="mt-2 text-[15px] text-slate-700">Nowe i zaktualizowane opisy znaków.</p>
                    </div>
                    <a href="#traffic-sign-categories" class="inline-flex items-center gap-2 text-sm font-semibold text-[#d50000] transition hover:text-red-800">
                        Zobacz wszystkie
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </div>

                <div class="divide-y divide-slate-200">
                    @forelse ($featuredSigns as $sign)
                        <div class="grid gap-4 px-5 py-4 lg:grid-cols-[190px_minmax(0,1fr)_260px_28px] lg:items-center lg:px-6">
                            <a href="{{ $sign['url'] }}" class="flex h-[96px] items-center justify-center overflow-hidden rounded-[4px] bg-white">
                                @if ($sign['image_url'])
                                    <img src="{{ $sign['image_url'] }}" alt="{{ $sign['image_alt'] }}" class="max-h-[96px] max-w-[184px] scale-[1.22] object-contain" loading="lazy">
                                @else
                                    <span class="text-sm font-semibold text-slate-500">{{ $sign['code'] }}</span>
                                @endif
                            </a>

                            <div class="min-w-0">
                                <a href="{{ $sign['url'] }}" class="text-[17px] font-semibold leading-6 text-slate-950 transition hover:text-[#d50000]">
                                    {{ $sign['title'] }}
                                </a>
                                @if ($sign['intro'])
                                    <p class="mt-2 max-w-3xl text-[15px] leading-6 text-slate-700">{{ $sign['intro'] }}</p>
                                @endif
                                @if ($sign['author_url'])
                                    <a href="{{ $sign['author_url'] }}" class="sr-only">Opracowanie: {{ $sign['author_name'] }}</a>
                                @endif
                            </div>

                            <div class="text-[15px] leading-6 text-slate-700">
                                @if ($sign['category_url'])
                                    <a href="{{ $sign['category_url'] }}" class="font-medium text-slate-800 transition hover:text-[#d50000]">{{ $sign['category_name'] }}</a>
                                @elseif ($sign['category_name'])
                                    <span class="font-medium text-slate-800">{{ $sign['category_name'] }}</span>
                                @endif
                                @if ($sign['updated_at'])
                                    <p class="text-slate-500">{{ $sign['updated_at'] }}</p>
                                @endif
                            </div>

                            <a href="{{ $sign['url'] }}" class="hidden text-slate-950 transition hover:text-[#d50000] lg:flex" aria-label="{{ $sign['title'] }}">
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                        </div>
                    @empty
                        <p class="px-6 py-6 text-sm text-slate-500">Nie ma jeszcze opublikowanych opisów znaków.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="site-shell pb-10 pt-2">
            <div class="grid gap-5 rounded-[6px] border border-slate-200 bg-white px-6 py-6 sm:grid-cols-[72px_minmax(0,1fr)_auto] sm:items-center lg:px-10">
                <div class="flex h-16 w-16 items-center justify-center rounded-full border border-slate-200 text-slate-700">
                    <svg class="h-9 w-9" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                        <path d="m5 12 11-6 11 6-11 6-11-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                        <path d="M10 15v6c0 2 12 2 12 0v-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path d="M27 12v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-[24px] font-semibold leading-tight text-slate-950">Chcesz uczyć się skuteczniej?</h2>
                    <p class="mt-2 text-[17px] leading-7 text-slate-700">Ćwicz znaki w testach i utrwalaj wiedzę w praktyce.</p>
                </div>
                <a
                    href="{{ route('public.tests', absolute: false) }}"
                    class="inline-flex min-h-14 items-center justify-center gap-3 rounded-[4px] bg-[#d50000] px-10 text-[16px] font-semibold text-white transition hover:bg-red-700"
                >
                    Przejdź do testów
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M4 10h10.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        <path d="M10.5 5.5 15 10l-4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </div>
        </div>
    </section>
@endsection

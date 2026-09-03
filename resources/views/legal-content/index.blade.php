@extends('layouts.public-content')

@section('content')
    <section class="content-band overflow-hidden">
        <div class="content-shell grid gap-7 pb-9 pt-7 md:pb-11 md:pt-9 lg:grid-cols-[minmax(0,0.9fr)_minmax(420px,0.92fr)] lg:items-start lg:gap-10 lg:py-0">
            <div class="relative z-10 max-w-2xl py-2 lg:pb-14 lg:pt-8 xl:pb-16 xl:pt-9">
                <p class="text-sm font-bold text-[#d01921]">Prawo drogowe</p>
                <h1 class="mt-3 max-w-2xl text-[2.3rem] font-bold leading-[1.1] text-slate-950 md:text-[2.4rem]">
                    Poznaj przepisy drogowe <span class="lg:block">bez prawniczego języka</span>
                </h1>
                <p class="content-muted mt-5 max-w-[40rem] text-base leading-7 md:text-lg">
                    Najważniejsze zasady, artykuły i podstawy prawne wyjaśnione prostym językiem. Sprawdź, z których przepisów wynikają pytania egzaminacyjne, znaki drogowe i obowiązki kierowcy.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="#najwazniejsze-tematy" class="inline-flex items-center justify-center gap-2 rounded-[4px] bg-[#d01921] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#b9151c] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d01921]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4.75 5.75A2.75 2.75 0 0 1 7.5 3h3.75A2.75 2.75 0 0 1 14 5.75V21a3.5 3.5 0 0 0-2.75-1.35H7.5A2.75 2.75 0 0 0 4.75 22V5.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <path d="M14 5.75A2.75 2.75 0 0 1 16.75 3h3.75a2.75 2.75 0 0 1 2.75 2.75V22a2.75 2.75 0 0 0-2.75-2.35h-3.75A3.5 3.5 0 0 0 14 21V5.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                        </svg>
                        Przeglądaj przepisy
                    </a>
                    <a href="{{ route('public.regulations.methodology') }}" class="inline-flex items-center justify-center gap-2 rounded-[4px] border border-slate-400 bg-white px-5 py-3 text-sm font-bold text-slate-950 transition hover:border-[#d01921] hover:text-[#d01921] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d01921]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3.5 19.25 6v5.6c0 4.42-2.82 7.52-7.25 8.9-4.43-1.38-7.25-4.48-7.25-8.9V6L12 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                            <path d="m8.75 12.1 2.1 2.1 4.55-4.65" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Jak weryfikujemy treści?
                    </a>
                </div>
            </div>

            <div class="-mr-4 overflow-hidden sm:-mr-6 lg:-mr-6 xl:-mr-8">
                <img
                    src="{{ asset('images/legal/regulations-hero.png') }}"
                    alt="Kodeks drogowy, otwarta ustawa i znaki drogowe na tle ulicy"
                    width="524"
                    height="361"
                    class="block h-auto w-full select-none"
                    fetchpriority="high"
                    decoding="async"
                >
            </div>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell py-8 md:py-10">
            <div id="najwazniejsze-tematy" class="scroll-mt-24 border-b border-slate-200 pb-5">
                <h2 class="text-2xl font-semibold text-slate-950">Najważniejsze tematy</h2>
                <p class="content-muted mt-3 max-w-3xl text-sm leading-6">
                    Zebraliśmy najważniejsze zagadnienia, które najczęściej pojawiają się w pytaniach egzaminacyjnych. Każdy temat prowadzi do prostego omówienia, oficjalnej podstawy prawnej i powiązanych przykładów z bazy pytań.
                </p>
            </div>

            @if ($pages->isNotEmpty())
                <div class="divide-y divide-slate-200">
                    @foreach ($pages as $page)
                        <article class="grid gap-5 py-6 lg:grid-cols-[minmax(0,1fr)_220px_28px] lg:items-center">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.04em] text-[#d01921]">
                                    {{ $page->topic?->title ?? 'Przepis' }}
                                </p>
                                <h3 class="mt-2 text-2xl font-semibold leading-tight text-slate-950">
                                    <a href="{{ route('public.regulations.show', $page->slug) }}" class="hover:text-[#d01921]">
                                        {{ $page->title }}
                                    </a>
                                </h3>
                                @if ($page->intro)
                                    <p class="content-muted mt-3 max-w-3xl text-[15px] leading-7">{{ $page->intro }}</p>
                                @endif
                            </div>

                            <dl class="grid grid-cols-2 gap-4 text-sm lg:block lg:space-y-3">
                                <div>
                                    <dt class="text-slate-500">Weryfikacja</dt>
                                    <dd class="mt-1 font-semibold text-slate-950">{{ $page->last_reviewed_at?->format('d.m.Y') ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-slate-500">Źródła</dt>
                                    <dd class="mt-1 font-semibold text-slate-950">{{ $page->legalUnits->count() }}</dd>
                                </div>
                            </dl>

                            <a href="{{ route('public.regulations.show', $page->slug) }}" class="hidden text-slate-950 transition hover:text-[#d01921] lg:block" aria-label="{{ $page->title }}">
                                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                    <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="py-6 text-sm text-slate-500">Pierwsze opracowania są przygotowywane do publikacji.</p>
            @endif
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell pb-12 pt-2">
            <div class="overflow-x-auto pb-1">
                <div class="relative mx-auto w-full min-w-[760px] max-w-[910px]">
                    <img
                        src="{{ asset('images/legal/official-sources.png') }}"
                        alt="Korzystamy z oficjalnych źródeł: ISAP, ELI, Dziennik Ustaw i gov.pl"
                        class="block h-auto w-full select-none"
                        loading="lazy"
                        decoding="async"
                    >

                    <a
                        href="https://isap.sejm.gov.pl/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="absolute left-[28.4%] top-[17%] h-[66%] w-[12.5%] rounded-sm transition hover:bg-slate-900/[0.035] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d01921]"
                        aria-label="Otwórz ISAP - Internetowy System Aktów Prawnych"
                    ></a>

                    <a
                        href="https://op.europa.eu/en/web/eu-vocabularies/eli"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="absolute left-[42.2%] top-[17%] h-[66%] w-[12.8%] rounded-sm transition hover:bg-slate-900/[0.035] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d01921]"
                        aria-label="Otwórz ELI - European Legislation Identifier"
                    ></a>

                    <a
                        href="https://dziennikustaw.gov.pl/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="absolute left-[57.2%] top-[17%] h-[66%] w-[18.4%] rounded-sm transition hover:bg-slate-900/[0.035] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d01921]"
                        aria-label="Otwórz Dziennik Ustaw Rzeczypospolitej Polskiej"
                    ></a>

                    <a
                        href="https://www.gov.pl/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="absolute left-[77.5%] top-[17%] h-[66%] w-[16.7%] rounded-sm transition hover:bg-slate-900/[0.035] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#d01921]"
                        aria-label="Otwórz gov.pl - Serwis Rzeczypospolitej Polskiej"
                    ></a>
                </div>
            </div>
        </div>
    </section>
@endsection

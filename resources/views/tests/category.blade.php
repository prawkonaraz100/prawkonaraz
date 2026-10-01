@extends('layouts.public-content')

@section('breadcrumb_shell_class', in_array($content['code'], ['A', 'A1', 'AM', 'B', 'C', 'D', 'T'], true) ? 'rankomat-guide__breadcrumb-shell' : 'site-shell')

@section('content')
@if ($content['code'] === 'A')
    @include('tests.partials.category-a-rankomat')
@elseif ($content['code'] === 'A1')
    @include('tests.partials.category-a1-rankomat')
@elseif ($content['code'] === 'AM')
    @include('tests.partials.category-am-rankomat')
@elseif ($content['code'] === 'B')
    @include('tests.partials.category-b-rankomat')
@elseif ($content['code'] === 'C')
    @include('tests.partials.category-c-rankomat')
@elseif ($content['code'] === 'D')
    @include('tests.partials.category-d-rankomat')
@elseif ($content['code'] === 'T')
    @include('tests.partials.category-t-rankomat')
@else
<section class="border-b border-slate-200 bg-white">
    <div class="site-shell py-10 md:py-14">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-center">
            <div class="max-w-4xl">
                <p class="text-[13px] font-bold uppercase tracking-[0.08em] text-[#d01921]">Egzamin teoretyczny WORD</p>
                <h1 class="mt-4 text-[2.3rem] font-semibold leading-[1.08] tracking-[-0.035em] text-slate-950 md:text-[3.35rem]">
                    Testy na prawo jazdy kat. {{ $content['code'] }} <span class="text-[#1b66c8]">{{ $year }}</span>
                </h1>
                <p class="mt-6 max-w-3xl text-[17px] leading-8 text-slate-600 md:text-[18px]">{{ $content['lead'] }}</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('public.tests', absolute: false) }}" class="inline-flex min-h-12 items-center justify-center rounded-[8px] bg-[#1b66c8] px-6 text-[15px] font-semibold text-white transition hover:bg-[#1556aa]">
                        Przejdź do testów
                    </a>
                    <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}" class="inline-flex min-h-12 items-center justify-center rounded-[8px] border border-slate-300 bg-white px-6 text-[15px] font-semibold text-slate-800 transition hover:border-slate-400 hover:bg-slate-50">
                        Zobacz pytania kat. {{ $content['code'] }}
                    </a>
                </div>
            </div>

            <div class="rounded-[18px] border border-slate-200 bg-[#f8fafc] p-7">
                <div class="flex items-center gap-5">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white">
                        <x-questions.vehicle-icon :code="$content['code']" class="h-14 w-14" />
                    </div>
                    <div>
                        <p class="text-[13px] font-semibold uppercase tracking-[0.06em] text-slate-500">Kategoria</p>
                        <p class="mt-1 text-[28px] font-semibold leading-none text-slate-950">{{ $content['code'] }}</p>
                        <p class="mt-2 text-[14px] leading-6 text-slate-600">{{ $content['vehicle_label'] }}</p>
                    </div>
                </div>
                <div class="mt-6 border-t border-slate-200 pt-5">
                    <p class="text-[13px] font-semibold uppercase tracking-[0.05em] text-slate-500">Minimalny wiek</p>
                    <p class="mt-2 text-[16px] font-semibold leading-6 text-slate-900">{{ $content['minimum_age'] }}</p>
                    <p class="mt-2 text-[13px] leading-5 text-slate-500">{{ $content['age_note'] }}</p>
                </div>
            </div>
        </div>

        <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-[12px] border border-slate-200 px-5 py-4"><strong class="block text-[24px] font-semibold text-slate-950">32</strong><span class="mt-1 block text-[13px] text-slate-500">pytania na egzaminie</span></div>
            <div class="rounded-[12px] border border-slate-200 px-5 py-4"><strong class="block text-[24px] font-semibold text-slate-950">25 min</strong><span class="mt-1 block text-[13px] text-slate-500">czas części teoretycznej</span></div>
            <div class="rounded-[12px] border border-slate-200 px-5 py-4"><strong class="block text-[24px] font-semibold text-slate-950">68 / 74</strong><span class="mt-1 block text-[13px] text-slate-500">próg zaliczenia</span></div>
            <div class="rounded-[12px] border border-slate-200 px-5 py-4"><strong class="block text-[24px] font-semibold text-slate-950">{{ number_format((int) ($categoryCard['questions_count'] ?? 0), 0, ',', ' ') }}</strong><span class="mt-1 block text-[13px] text-slate-500">pytań kat. {{ $content['code'] }} w naszej bazie</span></div>
        </div>
    </div>
</section>

<section class="bg-white">
    <div class="site-shell py-8">
        <nav class="flex flex-wrap gap-2" aria-label="Kategorie testów na prawo jazdy">
            @foreach ($categoryMenu as $item)
                <a href="{{ $item['href'] }}" class="{{ $item['code'] === $content['code'] ? 'border-[#1b66c8] bg-[#eef5ff] text-[#1556aa]' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' }} inline-flex min-h-10 items-center rounded-full border px-4 text-[14px] font-semibold transition">
                    Kat. {{ $item['code'] }}
                </a>
            @endforeach
        </nav>
    </div>
</section>

<section class="bg-[#f8fafc]">
    <div class="site-shell grid gap-8 py-10 lg:grid-cols-[minmax(0,1fr)_340px] lg:py-14">
        <div class="space-y-8">
            <article class="rounded-[16px] border border-slate-200 bg-white p-6 md:p-8">
                <p class="text-[12px] font-bold uppercase tracking-[0.08em] text-[#d01921]">Uprawnienia</p>
                <h2 class="mt-3 text-[28px] font-semibold tracking-[-0.025em] text-slate-950">Co daje prawo jazdy kategorii {{ $content['code'] }}?</h2>
                <ul class="mt-6 grid gap-3">
                    @foreach ($content['permissions'] as $permission)
                        <li class="flex gap-3 text-[16px] leading-7 text-slate-700"><span class="mt-[9px] h-2 w-2 shrink-0 rounded-full bg-[#1b66c8]" aria-hidden="true"></span><span>{{ $permission }}</span></li>
                    @endforeach
                </ul>
            </article>

            <article class="rounded-[16px] border border-slate-200 bg-white p-6 md:p-8">
                <p class="text-[12px] font-bold uppercase tracking-[0.08em] text-[#d01921]">Teoria w WORD</p>
                <h2 class="mt-3 text-[28px] font-semibold tracking-[-0.025em] text-slate-950">Jak wygląda egzamin teoretyczny na kat. {{ $content['code'] }}?</h2>
                <div class="mt-6 space-y-4 text-[16px] leading-8 text-slate-700">
                    <p>Część teoretyczna trwa 25 minut i składa się z 32 pytań: 20 pytań z wiedzy podstawowej oraz 12 pytań specjalistycznych dla wybranej kategorii.</p>
                    <p>Za pytania można otrzymać 1, 2 albo 3 punkty. Maksymalny wynik to 74 punkty, a do zaliczenia potrzebujesz co najmniej 68 punktów. Każde pytanie ma jedną prawidłową odpowiedź.</p>
                    <p>W PrawkoNaRaz możesz najpierw przejrzeć pełną bazę pytań dla kategorii {{ $content['code'] }}, a potem wracać do trudniejszych zagadnień i ćwiczyć pracę pod presją czasu.</p>
                </div>
            </article>

            <article class="rounded-[16px] border border-slate-200 bg-white p-6 md:p-8">
                <p class="text-[12px] font-bold uppercase tracking-[0.08em] text-[#d01921]">Zakres nauki</p>
                <h2 class="mt-3 text-[28px] font-semibold tracking-[-0.025em] text-slate-950">Na czym skupić naukę do kat. {{ $content['code'] }}?</h2>
                <div class="mt-6 grid gap-3 md:grid-cols-2">
                    @foreach ($content['focus'] as $focus)
                        <div class="rounded-[12px] border border-slate-200 bg-[#fbfcfe] p-5 text-[15px] font-medium leading-6 text-slate-700">{{ $focus }}</div>
                    @endforeach
                </div>
                <p class="mt-6 text-[16px] leading-8 text-slate-700">{{ $content['seo_body'] }}</p>
            </article>

            @if ($sampleQuestions->isNotEmpty())
                <section class="rounded-[16px] border border-slate-200 bg-white p-6 md:p-8" aria-labelledby="sample-questions-title">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-[12px] font-bold uppercase tracking-[0.08em] text-[#d01921]">Oficjalna baza pytań</p>
                            <h2 id="sample-questions-title" class="mt-3 text-[28px] font-semibold tracking-[-0.025em] text-slate-950">Przykładowe pytania kat. {{ $content['code'] }}</h2>
                        </div>
                        <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}" class="text-[14px] font-semibold text-[#1b66c8] hover:underline">Zobacz wszystkie →</a>
                    </div>
                    <div class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
                        @foreach ($sampleQuestions as $question)
                            <a href="{{ $question['url'] }}" class="group flex items-start justify-between gap-5 py-5">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-3 text-[12px] font-semibold text-slate-500">
                                        <span>#{{ $question['display_external_id'] ?? $question['external_id'] }}</span>
                                        <span class="rounded bg-[#eef5ff] px-2 py-1 text-[#1769c2]">Kat. {{ $content['code'] }}</span>
                                    </div>
                                    <h3 class="mt-3 text-[15px] font-semibold leading-6 text-slate-900 transition group-hover:text-[#1b66c8]">{{ $question['prompt_plain'] }}</h3>
                                </div>
                                <span class="mt-4 shrink-0 text-[20px] text-slate-400 transition group-hover:translate-x-1 group-hover:text-[#1b66c8]" aria-hidden="true">→</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-[16px] border border-slate-200 bg-white p-6 md:p-8" aria-labelledby="category-faq-title">
                <p class="text-[12px] font-bold uppercase tracking-[0.08em] text-[#d01921]">FAQ</p>
                <h2 id="category-faq-title" class="mt-3 text-[28px] font-semibold tracking-[-0.025em] text-slate-950">Najczęstsze pytania — kat. {{ $content['code'] }}</h2>
                <div class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
                    @foreach ($faq as $item)
                        <details class="group py-5">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-5 text-[16px] font-semibold leading-6 text-slate-900"><span>{{ $item['question'] }}</span><span class="text-[20px] font-normal text-slate-400 transition group-open:rotate-45" aria-hidden="true">+</span></summary>
                            <p class="mt-4 max-w-3xl pr-8 text-[15px] leading-7 text-slate-600">{{ $item['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-[16px] border border-slate-200 bg-white p-6">
                <h2 class="text-[18px] font-semibold text-slate-950">Przygotuj się krok po kroku</h2>
                <ol class="mt-5 grid gap-4 text-[14px] leading-6 text-slate-600">
                    <li class="flex gap-3"><span class="font-bold text-[#1b66c8]">1.</span><span>Przejrzyj pytania kategorii {{ $content['code'] }}.</span></li>
                    <li class="flex gap-3"><span class="font-bold text-[#1b66c8]">2.</span><span>Wracaj do błędów i czytaj wyjaśnienia.</span></li>
                    <li class="flex gap-3"><span class="font-bold text-[#1b66c8]">3.</span><span>Rozwiązuj pełne testy w limicie 25 minut.</span></li>
                </ol>
                <a href="{{ route('public.tests', absolute: false) }}" class="mt-6 inline-flex w-full min-h-11 items-center justify-center rounded-[8px] bg-[#1b66c8] px-4 text-[14px] font-semibold text-white hover:bg-[#1556aa]">Przejdź do testów</a>
            </div>

            <div class="rounded-[16px] border border-slate-200 bg-white p-6">
                <h2 class="text-[16px] font-semibold text-slate-950">Powiązane materiały</h2>
                <nav class="mt-4 grid gap-1 text-[14px]">
                    <a href="{{ route('public.questions.category', ['categorySlug' => $category->slug], absolute: false) }}" class="rounded-[8px] px-3 py-2.5 text-slate-700 hover:bg-slate-50 hover:text-[#1b66c8]">Pytania kat. {{ $content['code'] }}</a>
                    <a href="{{ route('public.hardest-questions.index', absolute: false) }}" class="rounded-[8px] px-3 py-2.5 text-slate-700 hover:bg-slate-50 hover:text-[#1b66c8]">Najtrudniejsze pytania</a>
                    <a href="{{ route('traffic-signs.index', absolute: false) }}" class="rounded-[8px] px-3 py-2.5 text-slate-700 hover:bg-slate-50 hover:text-[#1b66c8]">Znaki drogowe</a>
                    <a href="{{ route('public.regulations', absolute: false) }}" class="rounded-[8px] px-3 py-2.5 text-slate-700 hover:bg-slate-50 hover:text-[#1b66c8]">Przepisy ruchu drogowego</a>
                </nav>
            </div>

            <div class="rounded-[16px] border border-slate-200 bg-white p-6 text-[12px] leading-5 text-slate-500">
                Informacje o zakresie uprawnień i minimalnym wieku oparto na aktualnych materiałach Ministerstwa Infrastruktury. Parametry egzaminu teoretycznego odpowiadają obowiązującym zasadom egzaminu państwowego.
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2">
                    <a href="https://www.gov.pl/web/infrastruktura/kategorie-prawa-jazdy" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-700 hover:text-[#1b66c8]">Kategorie prawa jazdy ↗</a>
                    <a href="https://www.gov.pl/web/infrastruktura/prawo-jazdy" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-700 hover:text-[#1b66c8]">Egzamin teoretyczny ↗</a>
                </div>
            </div>
        </aside>
    </div>
</section>
@endif
@endsection

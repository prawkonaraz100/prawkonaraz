@extends('layouts.public-content')

@section('content')
    <section class="content-band">
        <div class="content-shell content-hero-section">
            <p class="content-kicker">Teoria na prawo jazdy</p>
            <h1 class="mt-3 max-w-4xl text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">
                Teoria na prawo jazdy: ucz się pytań ze zrozumieniem.
            </h1>
            <p class="content-muted mt-5 max-w-3xl text-base leading-7 md:text-lg">
                PrawkoNaRaz pomaga uczyć się teorii i pytań na prawo jazdy tak, aby po każdym zadaniu było jasne, co widzisz, który szczegół ma znaczenie i jak zachować się w podobnej sytuacji na drodze.
            </p>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell grid gap-10 py-10 md:py-12 lg:grid-cols-[minmax(0,1.35fr)_minmax(290px,0.8fr)] lg:gap-16">
            <section>
                <h2 class="text-2xl font-semibold text-slate-950">Jak uczymy teorii i pytań na prawo jazdy?</h2>
                <p class="content-muted mt-3 max-w-2xl text-base leading-7">
                    Prowadzimy Cię od pytania na prawo jazdy do zrozumienia sytuacji - krok po kroku i bez zbędnego żargonu.
                </p>
                <div class="mt-7">
                    @foreach ($learningSteps as $step)
                        <article class="grid gap-3 border-t border-slate-200 py-6 first:border-t-0 first:pt-0 md:grid-cols-[3rem_minmax(0,1fr)] md:gap-5">
                            <p class="text-sm font-semibold text-[#d01921]">{{ $step['number'] }}</p>
                            <div>
                            <h3 class="text-lg font-semibold text-slate-950">{{ $step['title'] }}</h3>
                            <p class="content-muted mt-3 text-sm leading-6">{{ $step['body'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <aside class="border-t border-slate-200 pt-7 lg:border-l lg:border-t-0 lg:pl-8 lg:pt-0">
                <section>
                    <h2 class="text-xl font-semibold text-slate-950">Na czym możesz polegać?</h2>
                    <ul class="mt-5 space-y-5">
                        @foreach ($learningPrinciples as $principle)
                            <li class="border-l-2 border-[#d01921] pl-4">
                                <h3 class="text-base font-semibold text-slate-950">{{ $principle['title'] }}</h3>
                                <p class="content-muted mt-1.5 text-sm leading-6">{{ $principle['body'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="mt-10 border-t border-slate-200 pt-7">
                    <h2 class="text-xl font-semibold text-slate-950">Zacznij od tego, czego potrzebujesz</h2>
                    <div class="mt-4 flex flex-col gap-3 text-sm font-semibold">
                        <a href="{{ route('public.questions.hub') }}" class="text-[#d01921] transition hover:text-[#a91118]">Oficjalna baza pytań</a>
                        <a href="{{ route('traffic-signs.index') }}" class="text-slate-700 transition hover:text-slate-950">Znaki drogowe</a>
                        <a href="{{ route('about.how-it-works') }}" class="text-slate-700 transition hover:text-slate-950">Sprawdź, jak działa nauka</a>
                    </div>
                </section>
            </aside>
        </div>
    </section>
@endsection

@extends('layouts.public-content')

@section('content')
    <section class="content-band">
        <div class="content-shell py-12 md:py-16">
            <p class="content-kicker">Partnerzy i współpraca</p>
            <h1 class="mt-4 max-w-5xl text-4xl font-semibold tracking-tight text-slate-950 md:text-6xl md:leading-[1.04]">
                Wspólnie wspieramy lepsze przygotowanie do prawa jazdy.
            </h1>
            <p class="mt-6 max-w-4xl text-lg leading-8 text-slate-700">
                PrawkoNaRaz.pl współpracuje z instytucjami i ośrodkami związanymi z edukacją kandydatów na kierowców. Łączymy oficjalny standard egzaminu, aktualne przepisy i praktyczne wyjaśnienia, żeby nauka teorii była bardziej zrozumiała.
            </p>

            <nav class="mt-10 grid gap-3 border-y border-slate-200 py-5 text-sm font-semibold text-slate-900 md:grid-cols-3" aria-label="Sekcje strony partnerów">
                <a href="#ministerstwo-infrastruktury" class="transition hover:text-red-700">
                    1. Ministerstwo Infrastruktury
                </a>
                <a href="#word" class="transition hover:text-red-700">
                    2. Ośrodki egzaminacyjne WORD
                </a>
                <a href="#osk" class="transition hover:text-red-700">
                    3. Ośrodki szkolenia kierowców OSK
                </a>
            </nav>
        </div>
    </section>

    <section id="ministerstwo-infrastruktury" class="content-band scroll-mt-28">
        <div class="content-shell grid gap-10 py-10 md:py-14 lg:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)]">
            <div>
                <p class="content-kicker">01</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">
                    Współpraca z Ministerstwem Infrastruktury
                </h2>
            </div>

            <div class="space-y-8 text-base leading-7 text-slate-700">
                <div class="space-y-5">
                    <p>
                        PrawkoNaRaz.pl współpracuje z Ministerstwem Infrastruktury w obszarze edukacji kandydatów na kierowców, testów na prawo jazdy oraz materiałów wspierających przygotowanie do egzaminu teoretycznego.
                    </p>
                    <p>
                        Nasza platforma powstaje w oparciu o oficjalny standard egzaminu państwowego, aktualne przepisy oraz kierunek rozwoju systemu egzaminowania kandydatów na kierowców w Polsce.
                    </p>
                    <p>
                        Celem współpracy jest wspieranie rzetelnej edukacji, poprawa jakości materiałów szkoleniowych oraz pomoc przyszłym kierowcom w lepszym zrozumieniu zasad ruchu drogowego.
                    </p>
                </div>

                <div class="border-t border-slate-200 pt-7">
                    <h3 class="text-xl font-semibold text-slate-950">Zakres współpracy</h3>
                    <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>aktualność materiałów edukacyjnych,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>zgodność treści z obowiązującymi przepisami,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>wsparcie nauki do egzaminu teoretycznego,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>promowanie bezpiecznych zachowań na drodze,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>lepsze tłumaczenie oficjalnych pytań egzaminacyjnych,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>rozwój nowoczesnych narzędzi edukacyjnych dla kandydatów na kierowców.</span></li>
                    </ul>
                </div>

                <div class="border-t border-slate-200 pt-7">
                    <h3 class="text-xl font-semibold text-slate-950">Dlaczego to ważne?</h3>
                    <div class="mt-4 space-y-5">
                        <p>
                            Egzamin teoretyczny nie powinien polegać wyłącznie na zapamiętywaniu odpowiedzi. Kandydat na kierowcę musi rozumieć sytuację drogową, znać zasady pierwszeństwa, rozpoznawać zagrożenia i podejmować prawidłowe decyzje.
                        </p>
                        <p>
                            Dlatego PrawkoNaRaz.pl łączy oficjalną bazę pytań z praktycznymi wyjaśnieniami, analizą sytuacji drogowych i prostym językiem nauki.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="word" class="content-band scroll-mt-28">
        <div class="content-shell grid gap-10 border-t border-slate-200 py-10 md:py-14 lg:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)]">
            <div>
                <p class="content-kicker">02</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">
                    Współpraca z ośrodkami egzaminacyjnymi WORD
                </h2>
            </div>

            <div class="space-y-8 text-base leading-7 text-slate-700">
                <div class="space-y-5">
                    <p>
                        PrawkoNaRaz.pl współpracuje z wojewódzkimi ośrodkami ruchu drogowego, aby lepiej rozumieć standard egzaminu państwowego, potrzeby kandydatów oraz najczęstsze problemy pojawiające się podczas przygotowania do części teoretycznej.
                    </p>
                    <p>
                        WORD-y odpowiadają za przeprowadzanie egzaminów państwowych, a nasza platforma wspiera kandydatów przed egzaminem — na etapie nauki, powtórek i utrwalania wiedzy.
                    </p>
                    <p>
                        Nie zastępujemy egzaminu państwowego. Pomagamy kandydatom przygotować się do niego w sposób uporządkowany, zgodny z aktualnymi wymaganiami i praktyczny.
                    </p>
                </div>

                <div class="border-t border-slate-200 pt-7">
                    <h3 class="text-xl font-semibold text-slate-950">Obszary współpracy</h3>
                    <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>analiza trudnych zagadnień egzaminacyjnych,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>identyfikacja pytań, które sprawiają kandydatom największe problemy,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>tworzenie lepszych wyjaśnień do sytuacji drogowych,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>wspieranie edukacji z zakresu bezpieczeństwa ruchu drogowego,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>promowanie świadomego przygotowania do egzaminu,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>budowanie materiałów zgodnych ze standardem egzaminu państwowego.</span></li>
                    </ul>
                </div>

                <div class="border-t border-slate-200 pt-7">
                    <h3 class="text-xl font-semibold text-slate-950">Nasza rola</h3>
                    <div class="mt-4 space-y-5">
                        <p>
                            PrawkoNaRaz.pl pomaga kandydatom lepiej zrozumieć pytania, przepisy i sytuacje drogowe, zanim przystąpią do egzaminu w WORD.
                        </p>
                        <p>
                            Dzięki temu nauka nie kończy się na klikaniu odpowiedzi. Kursant wie, dlaczego dana odpowiedź jest prawidłowa, jaki przepis ma zastosowanie i jak podobna sytuacja może wyglądać na drodze.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="osk" class="content-band scroll-mt-28">
        <div class="content-shell grid gap-10 border-t border-slate-200 py-10 md:py-14 lg:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)]">
            <div>
                <p class="content-kicker">03</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl">
                    Współpraca z ośrodkami szkolenia kierowców OSK
                </h2>
            </div>

            <div class="space-y-8 text-base leading-7 text-slate-700">
                <div class="space-y-5">
                    <p>
                        PrawkoNaRaz.pl współpracuje z ośrodkami szkolenia kierowców w całej Polsce, dostarczając narzędzia, które pomagają kursantom skuteczniej przygotować się do egzaminu teoretycznego.
                    </p>
                    <p>
                        Nasza platforma może wspierać szkoły jazdy jako dodatkowe źródło nauki, powtórek, wyjaśnień oraz pracy własnej kursanta poza salą wykładową.
                    </p>
                    <p>
                        Dla OSK to sposób na podniesienie jakości szkolenia, lepsze przygotowanie kursantów i wyróżnienie szkoły jako nowoczesnego partnera edukacyjnego.
                    </p>
                </div>

                <div class="border-t border-slate-200 pt-7">
                    <h3 class="text-xl font-semibold text-slate-950">Co zyskują OSK?</h3>
                    <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>dostęp do uporządkowanych pytań egzaminacyjnych,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>szczegółowe wyjaśnienia odpowiedzi,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>analizę znaków, sytuacji drogowych i podstaw prawnych,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>materiały pomocne podczas zajęć teoretycznych,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>wsparcie kursanta w nauce poza OSK,</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>możliwość kierowania kursantów do konkretnych zagadnień,</span></li>
                        <li class="flex gap-3 sm:col-span-2"><span class="mt-2 h-2 w-2 shrink-0 bg-red-600" aria-hidden="true"></span><span>nowoczesne narzędzie wspierające skuteczność szkolenia.</span></li>
                    </ul>
                </div>

                <div class="border-t border-slate-200 pt-7">
                    <h3 class="text-xl font-semibold text-slate-950">Dla instruktorów i wykładowców</h3>
                    <p class="mt-4">
                        PrawkoNaRaz.pl pomaga tłumaczyć pytania prostym językiem. Instruktor może wykorzystać nasze materiały jako wsparcie podczas omawiania błędów, znaków, pierwszeństwa, skrzyżowań, przejść dla pieszych, sytuacji z tramwajem, autostrad, dróg ekspresowych i innych zagadnień egzaminacyjnych.
                    </p>
                </div>

                <div class="border-t border-slate-200 pt-7">
                    <h3 class="text-xl font-semibold text-slate-950">Dla kursantów</h3>
                    <p class="mt-4">
                        Kursant otrzymuje nie tylko pytanie i odpowiedź, ale również wyjaśnienie, haczyk egzaminacyjny, podstawę prawną i najczęstsze błędy. Dzięki temu uczy się świadomie, szybciej rozumie materiał i lepiej przygotowuje się do egzaminu.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection

<script setup lang="ts">
import SiteFooter from '@/Components/SiteFooter.vue';
import PublicTopNavigation from '@/Components/PublicTopNavigation.vue';
import { Head, Link } from '@inertiajs/vue3';

const paths = [
    {
        name: 'Kwalifikacja wstępna',
        hours: '280 godzin',
        audience: 'Młodsza droga do zawodu: kat. C i C+E od 18. roku życia, a kat. D i D+E od 21. roku życia.',
        includes: 'Część podstawowa to minimum 195 godzin teorii. Część specjalistyczna dokłada minimum 65 godzin teorii i ćwiczeń, 16 godzin jazdy w ruchu oraz 4 godziny w warunkach specjalnych.',
    },
    {
        name: 'Kwalifikacja wstępna przyspieszona',
        hours: '140 godzin',
        audience: 'Najczęstszy kurs po osiągnięciu wieku dla danej kategorii: C1 od 18 lat, C i C+E od 21 lat, D i D+E zwykle od 23 lat.',
        includes: 'Krótsza teoria i praktyka oraz test kwalifikacyjny w WORD. Dla autobusu na liniach do 50 km próg wieku bywa niższy.',
    },
    {
        name: 'Kwalifikacja wstępna uzupełniająca',
        hours: '70 godzin',
        audience: 'Dla kierowcy, który ma już jeden blok — rzeczy albo osoby — i dokłada drugi.',
        includes: 'Tylko część specjalistyczna: minimum 65 godzin teorii i ćwiczeń oraz 5 godzin jazdy w ruchu drogowym.',
    },
    {
        name: 'Kwalifikacja wstępna uzupełniająca przyspieszona',
        hours: '35 godzin',
        audience: 'Skrócone rozszerzenie uprawnień, gdy spełniony jest wiek dla ścieżki przyspieszonej.',
        includes: 'Minimum 32,5 godziny teorii i ćwiczeń oraz 2,5 godziny jazdy w ruchu.',
    },
    {
        name: 'Szkolenie okresowe',
        hours: '35 godzin',
        audience: 'Przedłużenie kodu 95 co 5 lat. Także pierwsza ścieżka, jeśli kat. C pochodzi sprzed 10 września 2009 r., a kat. D sprzed 10 września 2008 r.',
        includes: 'Same zajęcia teoretyczne, zwykle pięć dni po 7 godzin. Bez jazd i bez egzaminu państwowego.',
    },
];

const steps = [
    {
        title: 'Kategoria prawa jazdy',
        text: 'Do przewozu rzeczy potrzebna jest kategoria C, C1, C+E albo C1+E. Do przewozu osób — D, D1, D+E albo D1+E. Część ośrodków zapisuje na kurs jeszcze w trakcie szkolenia na kategorię.',
    },
    {
        title: 'Badania lekarskie i psychologiczne',
        text: 'Orzeczenia musi wydać lekarz uprawniony do badań kierowców oraz uprawniony psycholog. Zwykłe badanie na prawo jazdy kategorii B tego nie zastępuje.',
    },
    {
        title: 'Profil Kierowcy Zawodowego',
        text: 'PKZ zakłada wydział komunikacji. Numer jest potrzebny do szkolenia, a przy kwalifikacji wstępnej także do testu. Samo założenie profilu jest bezpłatne.',
    },
    {
        title: 'Kurs albo szkolenie okresowe',
        text: 'Rodzaj wynika z wieku, daty prawa jazdy i tego, czy dokładasz drugi blok. Ośrodek wydaje zaświadczenie o ukończeniu zajęć.',
    },
    {
        title: 'Test w WORD',
        text: 'Kwalifikacja wstępna kończy się testem. Szkolenie okresowe nie. Po pozytywnym wyniku wojewoda wydaje świadectwo kwalifikacji zawodowej.',
    },
    {
        title: 'Wpis w prawie jazdy',
        text: 'Starosta wymienia dokument. Na odwrocie, przy kategorii, pojawia się kod 95 z datą ważności. Opłata za wydanie prawa jazdy to 100 zł.',
    },
];

const topics = [
    {
        title: 'Racjonalna jazda',
        text: 'Charakterystyka silnika, hamowanie, systemy wspomagania, jazda ekonomiczna i przewidywanie zagrożeń.',
    },
    {
        title: 'Przepisy i tachograf',
        text: 'Czas jazdy, przerwy, odpoczynki, karta kierowcy, dokumenty przewozu oraz odpowiedzialność w kontroli.',
    },
    {
        title: 'Ładunek i pojazd',
        text: 'Masa, naciski osi, rozmieszczenie ładunku, mocowanie, plandeka i kontrola przed wyjazdem.',
    },
    {
        title: 'Zdrowie i sytuacje awaryjne',
        text: 'Zmęczenie, ergonomia, pierwsza pomoc, pożar, bezpieczeństwo ładunku i zachowanie na miejscu zdarzenia.',
    },
    {
        title: 'Przewóz osób',
        text: 'Osobny blok dla autobusu: komfort i bezpieczeństwo pasażerów, bagaż, odpowiedzialność kierowcy i specyfika linii.',
    },
    {
        title: 'Rynek przewozów',
        text: 'Umowy, listy przewozowe, wizerunek firmy transportowej i zasady kontaktu z klientem oraz służbami.',
    },
];

const faqs = [
    {
        question: 'Czy kod 95 to osobna kategoria prawa jazdy?',
        answer: 'Nie. To wpis przy posiadanej kategorii, zwykle w postaci 95.DD.MM.RRRR. Data pokazuje, do kiedy wolno wykonywać przewóz drogowy. Samo prawo jazdy może pozostać ważne dłużej.',
    },
    {
        question: 'Kto musi go mieć?',
        answer: 'Kierowca, który zarobkowo przewozi rzeczy albo osoby pojazdem wymagającym kategorii C1, C, C+E, C1+E, D1, D, D+E lub D1+E. Przy zagranicznym prawie jazdy potwierdzeniem bywa Karta Kwalifikacji Kierowcy.',
    },
    {
        question: 'Kiedy kod 95 nie jest potrzebny?',
        answer: 'Ustawa o transporcie drogowym wyłącza część przejazdów, między innymi niektóre pojazdy służb, pojazdy do nauki jazdy i egzaminów oraz pojazdy o prędkości konstrukcyjnej do 45 km/h. Wyjątki są wąskie i nie obejmują zwykłej pracy kierowcy.',
    },
    {
        question: 'Jak wygląda test kwalifikacyjny?',
        answer: 'Przy kwalifikacji wstępnej test w WORD trwa 45 minut: 20 pytań z części podstawowej i 10 ze specjalistycznej. Zdaje się od 16 poprawnych odpowiedzi w pierwszej części i 5 w drugiej jednocześnie. Przy kwalifikacji uzupełniającej zostaje sam blok specjalistyczny.',
    },
    {
        question: 'Ile to kosztuje?',
        answer: 'PrawkoNaRaz nie prowadzi stacjonarnego kursu i nie podaje własnej ceny szkolenia. Ośrodki zwykle wyceniają kwalifikację wstępną na kilka tysięcy złotych, przyspieszoną niżej, a szkolenie okresowe najczęściej w okolicach 700–950 zł. Do tego dochodzą badania i 100 zł za wydanie prawa jazdy.',
    },
];
</script>

<template>
    <Head title="Kod 95 — kierowca zawodowy">
        <meta
            head-key="description"
            name="description"
            content="Kod 95 w prawie jazdy: dla kogo jest wpis, czym różni się kwalifikacja wstępna od szkolenia okresowego i jak wygląda test w WORD."
        />
    </Head>

    <div class="flex min-h-screen flex-col bg-white text-[#171717]">
        <PublicTopNavigation />

        <main class="flex-1 bg-[#f7f8fa]">
            <section class="mx-auto max-w-[84rem] px-4 py-14 sm:px-6 lg:px-8">
                <div class="border border-[#e4e8ec] bg-white px-6 py-10 shadow-[0_18px_42px_rgba(15,23,42,0.06)] sm:px-10">
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.22em] text-[#717b86]">
                        Kierowca zawodowy
                    </p>
                    <h1 class="mt-4 max-w-3xl text-4xl font-semibold tracking-tight text-[#171717] sm:text-5xl">
                        Kod 95 — kurs kierowcy zawodowego
                    </h1>
                    <p class="mt-5 max-w-3xl text-base leading-8 text-[#5c6570]">
                        Kod 95 to wpis w prawie jazdy, który potwierdza kwalifikację do zarobkowego przewozu rzeczy albo osób.
                        Samo prawo jazdy kategorii C lub D nie wystarcza, żeby legalnie pracować za kierownicą ciężarówki albo autobusu.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Link
                            href="/testy-na-prawo-jazdy"
                            class="inline-flex items-center justify-center bg-[#171717] px-5 py-3 text-sm font-semibold text-white"
                        >
                            Ćwicz testy online
                        </Link>
                        <Link
                            href="/kontakt"
                            class="inline-flex items-center justify-center border border-[#dbe2e8] px-5 py-3 text-sm font-semibold text-[#171717]"
                        >
                            Zapytaj o naukę
                        </Link>
                    </div>
                </div>

                <section class="mt-8 grid gap-4 md:grid-cols-3">
                    <article class="border border-[#e4e8ec] bg-white px-5 py-6">
                        <h2 class="text-lg font-semibold">Gdzie stoi wpis</h2>
                        <p class="mt-3 text-sm leading-7 text-[#5c6570]">
                            Na odwrocie prawa jazdy, przy kategorii, w kolumnie ograniczeń. Zapis ma postać
                            <span class="font-medium text-[#171717]">95.DD.MM.RRRR</span>
                            i pokazuje termin, do którego wolno wykonywać przewóz.
                        </p>
                    </article>
                    <article class="border border-[#e4e8ec] bg-white px-5 py-6">
                        <h2 class="text-lg font-semibold">Jak długo działa</h2>
                        <p class="mt-3 text-sm leading-7 text-[#5c6570]">
                            Co do zasady 5 lat. Potem potrzebne jest szkolenie okresowe, aktualne badania i wymiana dokumentu.
                            Warto zacząć formalności około dwóch miesięcy przed końcem daty.
                        </p>
                    </article>
                    <article class="border border-[#e4e8ec] bg-white px-5 py-6">
                        <h2 class="text-lg font-semibold">Dwie daty graniczne</h2>
                        <p class="mt-3 text-sm leading-7 text-[#5c6570]">
                            Prawo jazdy C wydane po 10 września 2009 r. albo D po 10 września 2008 r. prowadzi przez kwalifikację wstępną.
                            Starsze uprawnienia zwykle wchodzą od szkolenia okresowego.
                        </p>
                    </article>
                </section>

                <section class="mt-10">
                    <h2 class="text-2xl font-semibold tracking-tight">Który kurs wybrać</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-[#5c6570]">
                        Ośrodki rozdzielają ofertę na pięć ścieżek. Różnią się czasem, wiekiem i tym, czy na końcu jest test państwowy.
                    </p>
                    <div class="mt-6 grid gap-4 lg:grid-cols-2">
                        <article
                            v-for="path in paths"
                            :key="path.name"
                            class="border border-[#e4e8ec] bg-white px-5 py-6"
                        >
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#717b86]">
                                {{ path.hours }}
                            </p>
                            <h3 class="mt-2 text-xl font-semibold">{{ path.name }}</h3>
                            <p class="mt-3 text-sm leading-7 text-[#5c6570]">{{ path.audience }}</p>
                            <p class="mt-2 text-sm leading-7 text-[#5c6570]">{{ path.includes }}</p>
                        </article>
                    </div>
                </section>

                <section class="mt-10 border border-[#e4e8ec] bg-white px-6 py-8 sm:px-10">
                    <h2 class="text-2xl font-semibold tracking-tight">Od badań do wpisu w dokumencie</h2>
                    <ol class="mt-6 grid gap-5 md:grid-cols-2">
                        <li v-for="(step, index) in steps" :key="step.title" class="flex gap-4">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center bg-[#171717] text-sm font-semibold text-white">
                                {{ index + 1 }}
                            </span>
                            <div>
                                <h3 class="font-semibold">{{ step.title }}</h3>
                                <p class="mt-1 text-sm leading-7 text-[#5c6570]">{{ step.text }}</p>
                            </div>
                        </li>
                    </ol>
                </section>

                <section class="mt-10">
                    <h2 class="text-2xl font-semibold tracking-tight">Czego uczy program</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-[#5c6570]">
                        Niezależnie od cennika, program kursów opiera się na tych samych blokach: racjonalna jazda, przepisy oraz bezpieczeństwo, zdrowie i logistyka.
                    </p>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="topic in topics"
                            :key="topic.title"
                            class="border border-[#e4e8ec] bg-white px-5 py-6"
                        >
                            <h3 class="font-semibold">{{ topic.title }}</h3>
                            <p class="mt-2 text-sm leading-7 text-[#5c6570]">{{ topic.text }}</p>
                        </article>
                    </div>
                </section>

                <section class="mt-10 border border-[#e4e8ec] bg-white px-6 py-8 sm:px-10">
                    <h2 class="text-2xl font-semibold tracking-tight">Pytania, które wracają na stronach ośrodków</h2>
                    <div class="mt-6 divide-y divide-[#e4e8ec]">
                        <article v-for="item in faqs" :key="item.question" class="py-5">
                            <h3 class="font-semibold">{{ item.question }}</h3>
                            <p class="mt-2 text-sm leading-7 text-[#5c6570]">{{ item.answer }}</p>
                        </article>
                    </div>
                </section>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>

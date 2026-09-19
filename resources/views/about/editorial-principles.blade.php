@extends('layouts.public-content')

@section('content')
    <section class="content-band">
        <div class="content-shell content-hero-section">
            <p class="content-kicker">Transparentność publikacji</p>
            <h1 class="mt-3 max-w-4xl text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">
                Zasady redakcyjne PrawkoNaRaz
            </h1>
            <p class="content-muted mt-5 max-w-3xl text-base leading-7 md:text-lg">
                Opisujemy tu, kto odpowiada za materiały, jak pracujemy ze źródłami, jak oznaczamy aktualizacje i korekty oraz jakie zasady obowiązują przy potencjalnych konfliktach interesów.
            </p>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell grid gap-10 py-10 md:py-12 lg:grid-cols-[minmax(0,1.35fr)_minmax(290px,0.8fr)] lg:gap-16">
            <div class="space-y-10">
                <section>
                    <h2 class="text-2xl font-semibold text-slate-950">Kto publikuje</h2>
                    <div class="content-muted mt-3 space-y-3 text-base leading-7">
                        <p>
                            Wydawcą materiałów jest PrawkoNaRaz. Informacje o serwisie i zespole są dostępne na stronie
                            <a href="{{ route('about.organization') }}" class="font-semibold text-slate-950 underline underline-offset-4">O nas</a>,
                            a dane kontaktowe na stronie
                            <a href="{{ route('about.contact') }}" class="font-semibold text-slate-950 underline underline-offset-4">Kontakt</a>.
                        </p>
                        <p>
                            Publiczny materiał pokazuje autora i prowadzi do jego profilu. Autorstwo treści jest oddzielone od technicznego konta administratora wykonującego operacje w CMS.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-slate-950">Autorstwo i odpowiedzialność</h2>
                    <div class="content-muted mt-3 space-y-3 text-base leading-7">
                        <p>Artykuł powinien mieć jednoznacznego autora oraz widoczną datę publikacji. Gdy materiał jest merytorycznie aktualizowany, pokazujemy także datę aktualizacji.</p>
                        <p>Materiały wymagające ponownej weryfikacji zachowują jawny status redakcyjny. Nie ukrywamy tego stanu przed czytelnikiem.</p>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-slate-950">Źródła i weryfikacja</h2>
                    <div class="content-muted mt-3 space-y-3 text-base leading-7">
                        <p>Przy informacjach o przepisach i zmianach regulacyjnych preferujemy źródła oficjalne i publiczne. Publiczne źródła wykorzystane w materiale pokazujemy w sekcji „Źródła”, jeśli są przeznaczone do cytowania.</p>
                        <p>Wewnętrzne dowody i notatki redakcyjne nie są publikowane jako źródła dla czytelnika.</p>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-slate-950">Korekty i aktualizacje</h2>
                    <div class="content-muted mt-3 space-y-3 text-base leading-7">
                        <p>Drobne poprawki językowe lub techniczne nie muszą otrzymywać osobnej noty korekcyjnej.</p>
                        <p>Jeśli korekta zmienia sens lub istotną informację, materiał powinien otrzymać publiczną notę „Korekta” oraz zaktualizowaną datę merytoryczną. Treść, której nie można bezpiecznie pozostawić publicznie, może zostać wycofana zamiast czekać na pełny cykl redakcyjny.</p>
                        <p>Uwagi merytoryczne można zgłaszać przez <a href="{{ route('about.contact') }}" class="font-semibold text-slate-950 underline underline-offset-4">formularz kontaktowy</a>.</p>
                    </div>
                </section>

                <section>
                    <h2 class="text-2xl font-semibold text-slate-950">Materiały sponsorowane i konflikty interesów</h2>
                    <div class="content-muted mt-3 space-y-3 text-base leading-7">
                        <p>Materiały sponsorowane nie są częścią newsroomu v1.</p>
                        <p>Jeśli taki format zostanie w przyszłości wprowadzony, sponsorowany charakter materiału musi być oznaczony przed treścią, nie może ukrywać reklamowego charakteru publikacji ani udawać niezależnego reportu.</p>
                        <p>Potencjalny konflikt interesów powinien być ujawniony wewnętrznie, a gdy ma znaczenie dla odbiorcy — również publicznie.</p>
                    </div>
                </section>
            </div>

            <aside class="border-t border-slate-200 pt-7 lg:border-l lg:border-t-0 lg:pl-8 lg:pt-0">
                <h2 class="text-xl font-semibold text-slate-950">Powiązane informacje</h2>
                <div class="mt-4 flex flex-col gap-3 text-sm font-semibold">
                    <a href="{{ route('about.organization') }}" class="text-slate-700 transition hover:text-slate-950">O nas i redakcja</a>
                    <a href="{{ route('about.contact') }}" class="text-slate-700 transition hover:text-slate-950">Kontakt i zgłoszenie korekty</a>
                    <a href="{{ route('about.methodology') }}" class="text-slate-700 transition hover:text-slate-950">Metodologia nauki</a>
                </div>
            </aside>
        </div>
    </section>
@endsection

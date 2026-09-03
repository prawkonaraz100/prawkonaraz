@extends('layouts.public-content')

@section('content')
    <section class="content-band">
        <div class="content-shell content-hero-section">
            <p class="content-kicker">Metodologia</p>
            <h1 class="mt-3 max-w-4xl text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">
                Jak weryfikujemy przepisy i podstawy prawne
            </h1>
            <p class="content-muted mt-5 max-w-3xl text-base leading-7 md:text-lg">
                Materiały prawne w prawkonaraz.pl mają charakter edukacyjny i pomagają zrozumieć pytania egzaminacyjne. Nie stanowią indywidualnej porady prawnej.
            </p>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell grid gap-8 py-10 md:py-12 lg:grid-cols-[minmax(0,1.35fr)_minmax(300px,0.8fr)]">
            <section>
                <h2 class="text-2xl font-semibold text-slate-950">Nasz proces</h2>
                <div class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
                    @foreach ([
                        ['title' => 'Korzystamy z oficjalnych źródeł', 'body' => 'Podstawy prawne opieramy na źródłach pierwotnych: ISAP, ELI, Dzienniku Ustaw, gov.pl oraz oficjalnych tekstach ustaw i rozporządzeń.'],
                        ['title' => 'Oddzielamy przepis od wyjaśnienia', 'body' => 'Na stronie pokazujemy edukacyjne opracowanie prostym językiem, a obok podajemy link do oficjalnego aktu prawnego. Nie udajemy, że krótkie wyjaśnienie jest pełnym komentarzem prawnym.'],
                        ['title' => 'Publikujemy tylko zweryfikowane powiązania', 'body' => 'Blok podstawy prawnej przy pytaniu pojawia się tylko wtedy, gdy rekord ma status verified, datę weryfikacji i oficjalne źródło.'],
                    ] as $step)
                        <article class="py-6">
                            <h3 class="text-xl font-semibold text-slate-950">{{ $step['title'] }}</h3>
                            <p class="content-muted mt-3 text-[15px] leading-7">{{ $step['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <aside class="space-y-6">
                <section class="border border-slate-200 bg-slate-50 p-6">
                    <h2 class="text-xl font-semibold text-slate-950">Źródła priorytetowe</h2>
                    <ul class="content-muted mt-4 space-y-3 text-sm leading-6">
                        <li>ISAP i Internetowy System Aktów Prawnych.</li>
                        <li>ELI oraz Dziennik Ustaw.</li>
                        <li>gov.pl i oficjalne materiały administracji publicznej.</li>
                        <li>Oficjalne teksty ustaw i rozporządzeń.</li>
                    </ul>
                </section>

            </aside>
        </div>
    </section>
@endsection

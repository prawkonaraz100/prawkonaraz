@extends('layouts.public-content')

@section('content')
    <section class="content-band">
        <div class="content-shell content-hero-section">
            <p class="content-kicker">Kontakt</p>
            <h1 class="mt-3 max-w-4xl text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">
                Skontaktuj się z zespołem serwisu
            </h1>
            <p class="content-muted mt-5 max-w-3xl text-base leading-7 md:text-lg">
                Jeśli chcesz zgłosić korektę merytoryczną, dopytać o treść strony znaku albo wrócić do nas w sprawie współpracy, tu jest właściwe miejsce.
            </p>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell grid gap-8 py-10 md:py-12 lg:grid-cols-[minmax(0,1.3fr)_minmax(280px,0.9fr)]">
            <section>
                <h2 class="text-2xl font-semibold text-slate-950">Kiedy warto napisać</h2>
                <div class="content-prose content-muted mt-4 text-base leading-7">
                    <p>Najbardziej zależy nam na zgłoszeniach, które pomagają utrzymać wysoką jakość treści: zmianach w oznakowaniu, nieścisłościach w opisie zachowania kierowcy albo sygnałach, że któraś karta powinna wrócić do review.</p>
                    <p>Kontakt jest też dobrym miejscem do pytań o współpracę redakcyjną, materiały edukacyjne i wykorzystanie treści w szerszych projektach szkoleniowych.</p>
                </div>
            </section>

            <aside class="border border-slate-200 bg-slate-50 p-6">
                <h2 class="text-xl font-semibold text-slate-950">Dane kontaktowe</h2>
                <dl class="mt-4 space-y-4 text-sm text-slate-700">
                    <div>
                        <dt class="font-semibold text-slate-950">Email</dt>
                        <dd class="mt-1">
                            <a href="mailto:{{ $organization['email'] }}" class="hover:text-slate-950">{{ $organization['email'] }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-950">Serwis</dt>
                        <dd class="mt-1">{{ $organization['public_url'] ?? url('/') }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-950">Powiązane strony</dt>
                        <dd class="mt-2 flex flex-col gap-2">
                            <a href="{{ route('about.organization') }}" class="hover:text-slate-950">O nas</a>
                            <a href="{{ route('about.methodology') }}" class="hover:text-slate-950">Metodologia treści</a>
                        </dd>
                    </div>
                </dl>
            </aside>
        </div>
    </section>
@endsection

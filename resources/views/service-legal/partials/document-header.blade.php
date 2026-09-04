<section class="content-band border-b border-slate-200 bg-slate-50/70">
    <div class="content-shell content-hero-section">
        <p class="content-kicker">Dokumenty serwisu</p>
        <h1 class="mt-3 max-w-4xl text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">
            {{ $title }}
        </h1>
        <p class="content-muted mt-5 max-w-3xl text-base leading-7 md:text-lg">
            {{ $lead }}
        </p>
        <p class="mt-4 text-sm text-slate-500">
            Obowiązuje od {{ $legalDocuments['effective_date'] }} · wersja {{ $version }}
        </p>

        @unless ($operatorComplete)
            <div class="mt-6 max-w-3xl rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950" role="note">
                <strong>Projekt lokalny:</strong> przed publikacją trzeba uzupełnić pełną nazwę i adres usługodawcy w konfiguracji środowiska.
            </div>
        @endunless
    </div>
</section>

<x-filament::section
    heading="Import gov.pl"
    description="Import uruchamiasz z konsoli. Ten blok zbiera krótki, operatorski flow i komendy potrzebne po nowym wsadzie."
>
    <div class="space-y-6">
        <div class="grid gap-3 lg:grid-cols-3">
            <div class="border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Krok 1</p>
                <h3 class="mt-2 text-sm font-semibold text-slate-900">Przygotuj staging</h3>
                <p class="mt-2 text-sm leading-6 text-slate-700">
                    Zbuduj serię chunków z oficjalnego XLSX i ZIP-ów mediów. To przygotowuje staging do dalszej walidacji i pełnego importu.
                </p>
            </div>

            <div class="border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Krok 2</p>
                <h3 class="mt-2 text-sm font-semibold text-slate-900">Dry-run i import</h3>
                <p class="mt-2 text-sm leading-6 text-slate-700">
                    Najpierw walidacja serii bez zapisu, dopiero potem pełny import do bazy i mediów.
                </p>
            </div>

            <div class="border border-slate-200 bg-slate-50 px-4 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Krok 3</p>
                <h3 class="mt-2 text-sm font-semibold text-slate-900">Domknij quality gate</h3>
                <p class="mt-2 text-sm leading-6 text-slate-700">
                    Po imporcie przelicz grupy, odpal audyt integralności i sprawdź gotowość publikacji.
                </p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(260px,0.9fr)]">
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-3">
                    <p class="text-sm font-semibold text-slate-900">Pełny flow roboczy</p>
                    <p class="text-xs text-slate-500">Kopiuj i uruchamiaj po kolei</p>
                </div>

                <div class="overflow-hidden border border-slate-200 bg-slate-50">
                    <pre class="overflow-x-auto px-4 py-4 text-[12px] leading-6 text-slate-900"><code>php artisan catalog:prepare-gov-batch-series &lt;ścieżka-do-baza-pytan.xlsx&gt; &lt;katalog-stagingu&gt; &lt;media-zip-1-lub-katalog&gt; &lt;media-zip-2-lub-katalog&gt; --include-verification --materialize-media --allow-missing-media
php artisan catalog:import-manifest-series &lt;katalog-stagingu&gt; --dry-run
php artisan catalog:import-manifest-series &lt;katalog-stagingu&gt;
php artisan content:classify-question-topics --refresh
php artisan content:audit-question-integrity
php artisan content:audit-delivery-readiness --deactivate-missing-primary-media</code></pre>
                </div>
            </div>

            <div class="space-y-4">
                <div class="border border-slate-200 bg-white px-4 py-4">
                    <p class="text-sm font-semibold text-slate-900">Wariant B-first</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">
                        Jeśli staging ma objąć tylko kategorię B, dodaj do pierwszej komendy:
                    </p>
                    <div class="mt-3 border border-slate-200 bg-slate-100 px-3 py-3 text-[12px] font-medium text-slate-900">
                        --categories=B
                    </div>
                </div>

                <div class="border border-slate-200 bg-white px-4 py-4">
                    <p class="text-sm font-semibold text-slate-900">Po imporcie sprawdź</p>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-700">
                        <li>- status ostatniego przebiegu w sekcji <span class="font-medium">Importy</span></li>
                        <li>- wskaźnik <span class="font-medium">Integralność pytań</span></li>
                        <li>- czy dashboard nie pokazuje zmian krytycznych</li>
                    </ul>
                </div>

                <div class="border-l-4 border-rose-400 bg-rose-50 px-4 py-4">
                    <p class="text-sm font-semibold text-rose-900">Ważne</p>
                    <p class="mt-2 text-sm leading-6 text-rose-900/90">
                        Jeśli audyt integralności pokazuje czerwony status, nie traktujemy wsadu jako gotowego do publikacji bez ręcznego review zmian krytycznych.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament::section>

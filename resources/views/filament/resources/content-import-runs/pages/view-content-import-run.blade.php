@php
    $record = $this->getRecord();
    $status = $this->getStatusMeta();
    $integrity = $this->getIntegrityStatusMeta();
    $topMetrics = $this->getTopMetrics();
    $operationalRows = $this->getOperationalRows();
    $pathRows = $this->getPathRows();
    $integrityMetrics = $this->getIntegrityMetrics();
    $warnings = $this->getWarnings();
    $errors = $this->getErrors();
    $chunks = $this->getChunks();
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
            <section class="border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="{{ $this->badgeClasses($status['color']) }} inline-flex items-center px-2.5 py-1 text-xs font-semibold">
                                    {{ $status['label'] }}
                                </span>
                                <span class="{{ $this->badgeClasses($integrity['color']) }} inline-flex items-center px-2.5 py-1 text-xs font-semibold">
                                    Audyt: {{ $integrity['label'] }}
                                </span>
                            </div>
                            <h2 class="text-lg font-semibold text-slate-950">
                                {{ filled($record->identifier) ? $record->identifier : 'Bez identyfikatora przebiegu' }}
                            </h2>
                            <p class="text-sm leading-6 text-slate-600">
                                {{ $status['description'] }}
                            </p>
                        </div>

                        <div class="text-sm text-slate-500">
                            Utworzono {{ $record->created_at?->format('d.m.Y H:i') ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-px bg-slate-200 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($topMetrics as $metric)
                        <div class="bg-white px-5 py-4">
                            <p class="text-xs font-medium uppercase tracking-[0.18em] text-slate-500">{{ $metric['label'] }}</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $metric['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">Przebieg operacyjny</h2>
                    <p class="mt-1 text-sm text-slate-600">Najważniejszy kontekst tego importu w jednym miejscu.</p>
                </div>

                <dl class="grid gap-x-5 gap-y-4 px-5 py-4 sm:grid-cols-2">
                    @foreach ($operationalRows as $row)
                        <div class="space-y-1">
                            <dt class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">{{ $row['label'] }}</dt>
                            <dd class="text-sm text-slate-900">{{ $row['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
            <section class="border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">Audyt integralności</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $integrity['description'] }}</p>
                </div>

                @if ($integrityMetrics !== [])
                    <div class="grid gap-px bg-slate-200 sm:grid-cols-2">
                        @foreach ($integrityMetrics as $metric)
                            <div class="bg-white px-5 py-4">
                                <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">{{ $metric['label'] }}</p>
                                <p class="mt-2 text-xl font-semibold text-slate-950">{{ $metric['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-5 py-5 text-sm text-slate-600">
                        Ten przebieg nie ma jeszcze zapisanego podsumowania audytu integralności.
                    </div>
                @endif
            </section>

            <section class="border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">Ścieżki i raporty</h2>
                    <p class="mt-1 text-sm text-slate-600">Szybki podgląd, gdzie leżą źródła, wynik importu i raport audytu.</p>
                </div>

                @if ($pathRows !== [])
                    <dl class="space-y-4 px-5 py-4">
                        @foreach ($pathRows as $row)
                            <div class="space-y-1">
                                <dt class="text-xs font-medium uppercase tracking-[0.16em] text-slate-500">{{ $row['label'] }}</dt>
                                <dd class="break-all text-sm text-slate-900">{{ $row['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <div class="px-5 py-5 text-sm text-slate-600">
                        Ten przebieg nie zapisał jeszcze żadnych ścieżek pomocniczych.
                    </div>
                @endif
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">Ostrzeżenia</h2>
                    <p class="mt-1 text-sm text-slate-600">Warningi nie blokują importu, ale warto je przejrzeć przed kolejnym przebiegiem.</p>
                </div>

                @if ($warnings !== [])
                    <ul class="space-y-3 px-5 py-4">
                        @foreach ($warnings as $warning)
                            <li class="border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">{{ $warning }}</li>
                        @endforeach
                    </ul>
                @else
                    <div class="px-5 py-5 text-sm text-slate-600">
                        Ten import nie zapisał żadnych ostrzeżeń.
                    </div>
                @endif
            </section>

            <section class="border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">Błędy</h2>
                    <p class="mt-1 text-sm text-slate-600">To lista błędów zapisanych razem z przebiegiem importu.</p>
                </div>

                @if ($errors !== [])
                    <ul class="space-y-3 px-5 py-4">
                        @foreach ($errors as $error)
                            <li class="border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $error }}</li>
                        @endforeach
                    </ul>
                @else
                    <div class="px-5 py-5 text-sm text-slate-600">
                        Ten import nie zapisał żadnych błędów.
                    </div>
                @endif
            </section>
        </div>

        <section class="border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Chunki i etapy</h2>
                <p class="mt-1 text-sm text-slate-600">Jeśli import był dzielony na części, tutaj widać przebieg każdej z nich.</p>
            </div>

            @if ($chunks !== [])
                <div class="grid gap-px bg-slate-200 md:grid-cols-2">
                    @foreach ($chunks as $chunk)
                        <div class="bg-white px-5 py-4">
                            <p class="text-sm font-semibold text-slate-950">{{ $chunk['title'] }}</p>
                            <p class="mt-2 text-sm text-slate-600">{{ $chunk['details'] !== '' ? $chunk['details'] : 'Brak dodatkowych danych o tym chunku.' }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-5 py-5 text-sm text-slate-600">
                    Ten przebieg nie ma chunków albo nie zapisano ich do podsumowania.
                </div>
            @endif
        </section>

        <section class="border border-slate-200 bg-white">
            <details>
                <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-slate-900">
                    Surowe podsumowanie importu
                </summary>
                <div class="border-t border-slate-200 px-5 py-4">
                    <pre class="overflow-x-auto whitespace-pre-wrap break-words bg-slate-50 p-4 text-xs text-slate-800">{{ $this->getSummaryJson() }}</pre>
                </div>
            </details>
        </section>
    </div>
</x-filament-panels::page>

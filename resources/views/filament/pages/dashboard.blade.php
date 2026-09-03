@php
    $completedDays = max((int) config('study.completed_session_retention_days', 365), 1);
    $abandonedDays = max((int) config('study.abandoned_session_retention_days', 7), 1);
    $adminDays = max((int) config('study.admin_activity_window_days', 90), 1);
    $analyticsDays = max((int) config('study.question_analytics_window_days', 365), 1);
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            heading="Szybkie wejścia"
            description="Najkrótsza droga do miejsc, do których faktycznie wracasz w pracy z panelem."
        >
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.9fr)]">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($dashboard['quickLinks'] as $link)
                        <a
                            href="{{ $link['url'] }}"
                            class="block border border-slate-200 bg-white px-4 py-4 transition hover:bg-slate-50"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $link['label'] }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $link['description'] }}</p>
                                </div>
                                <span class="text-base text-slate-400">→</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="border border-slate-200 bg-slate-50 px-5 py-5">
                    <div class="space-y-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Stan bieżący</p>
                        <h3 class="text-xl font-semibold text-slate-950">{{ $dashboard['snapshot']['headline'] }}</h3>
                        <p class="text-sm leading-6 text-slate-700">{{ $dashboard['snapshot']['summary'] }}</p>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ($dashboard['snapshot']['metrics'] as $metric)
                            <div class="border border-slate-200 bg-white px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $metric['label'] }}</p>
                                <p class="mt-2 text-lg font-semibold text-slate-950">{{ $metric['value'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <p class="mt-4 text-xs text-slate-500">
                        Łącznie w bazie: <span class="font-medium text-slate-700">{{ $dashboard['snapshot']['questions_total'] }}</span> pytań.
                    </p>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
            <x-filament::section
                heading="Wymaga uwagi"
                description="Rzeczy, od których najlepiej zacząć dzień albo sprawdzenie po imporcie."
            >
                <div class="space-y-3">
                    @foreach ($dashboard['attentionItems'] as $item)
                        <a
                            href="{{ $item['url'] }}"
                            class="block border border-slate-200 bg-white px-4 py-4 transition hover:bg-slate-50"
                        >
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-slate-900">{{ $item['label'] }}</p>
                                    <p class="text-sm leading-6 text-slate-600">{{ $item['description'] }}</p>
                                </div>

                                <div class="shrink-0 text-left md:text-right">
                                    <p class="text-base font-semibold text-slate-950">{{ $item['value'] }}</p>
                                    <p class="mt-1 text-xs font-medium text-slate-500">{{ $item['url_label'] }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </x-filament::section>

            <div class="space-y-6">
                <x-filament::section
                    heading="Ostatni import"
                    description="Szybki skrót ostatniego przebiegu bez wchodzenia od razu w szczegóły."
                >
                    <a
                        href="{{ $dashboard['latestImport']['url'] }}"
                        class="block border border-slate-200 bg-white px-4 py-4 transition hover:bg-slate-50"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900">{{ $dashboard['latestImport']['label'] }}</p>
                            <span class="text-xs font-medium text-slate-500">{{ $dashboard['latestImport']['meta'] }}</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-slate-700">{{ $dashboard['latestImport']['description'] }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $dashboard['latestImport']['details'] }}</p>
                    </a>
                </x-filament::section>

                <x-filament::section
                    heading="Stan systemu"
                    description="Krótki przegląd rzeczy, które warto mieć pod ręką przy pracy operatorskiej."
                >
                    <div class="space-y-3">
                        @foreach ($dashboard['systemChecks'] as $check)
                            <div class="border border-slate-200 bg-white px-4 py-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-900">{{ $check['label'] }}</p>
                                    <p class="text-sm font-medium text-slate-700">{{ $check['value'] }}</p>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $check['details'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(320px,0.95fr)]">
            <x-filament::section
                heading="Ostatnie importy"
                description="Najświeższe przebiegi z podsumowaniem, bez przebijania się przez pełną tabelę."
            >
                <div class="space-y-3">
                    @forelse ($dashboard['recentImports'] as $import)
                        <a
                            href="{{ $import['url'] }}"
                            class="block border border-slate-200 bg-white px-4 py-4 transition hover:bg-slate-50"
                        >
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900">{{ $import['identifier'] }}</p>
                                        <span class="text-xs font-medium text-slate-500">{{ $import['kind'] }}</span>
                                    </div>
                                    <p class="text-sm leading-6 text-slate-600">{{ $import['summary'] }}</p>
                                    <p class="text-sm leading-6 text-slate-500">{{ $import['details'] }}</p>
                                </div>

                                <div class="shrink-0 text-left md:text-right">
                                    <p class="text-sm font-semibold text-slate-900">{{ $import['status_label'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $import['created_at'] }}</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="border border-dashed border-slate-300 bg-white px-5 py-8 text-sm text-slate-500">
                            Nie ma jeszcze żadnych przebiegów importu.
                        </div>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section
                heading="Ostatnie działania"
                description="Co działo się ostatnio w panelu i kto wykonał daną akcję."
            >
                <div class="space-y-3">
                    @forelse ($dashboard['recentAuditLogs'] as $entry)
                        <a
                            href="{{ $entry['url'] }}"
                            class="block border border-slate-200 bg-white px-4 py-4 transition hover:bg-slate-50"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $entry['action'] }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $entry['entity'] }}</p>
                                </div>
                                <p class="text-xs text-slate-500">{{ $entry['created_at'] }}</p>
                            </div>
                            <p class="mt-3 text-sm text-slate-500">{{ $entry['actor'] }}</p>
                        </a>
                    @empty
                        <div class="border border-dashed border-slate-300 bg-white px-5 py-8 text-sm text-slate-500">
                            Nie ma jeszcze wpisów audytu.
                        </div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section
            heading="Retencja danych i znaczenie statystyk"
            description="Stała nota operatorska dla panelu, żeby sposób liczenia aktywności był czytelny także po czasie."
        >
            <div class="space-y-3 text-sm leading-6 text-slate-700">
                <p>
                    Surowe zakończone sesje nauki są utrzymywane przez
                    <span class="font-semibold text-slate-950">{{ $completedDays }} dni</span>,
                    a porzucone sesje <span class="font-semibold text-slate-950">in_progress</span> są czyszczone po
                    <span class="font-semibold text-slate-950">{{ $abandonedDays }} dniach</span>.
                </p>

                <p>
                    Dzienne agregaty pytań trzymają pełny detal przez
                    <span class="font-semibold text-slate-950">{{ $analyticsDays }} dni</span>.
                    Starsze dni nie znikają całkiem: są zwijane do
                    <span class="font-semibold text-slate-950">archiwum miesięcznego</span>, żeby zachować trend bez trzymania całego detalu.
                </p>

                <p>
                    Panel administracyjny pokazuje operacyjne statystyki bieżącej aktywności i jakości danych z okna
                    <span class="font-semibold text-slate-950">{{ $adminDays }} dni</span>.
                    To liczby do codziennej pracy, a nie pełne archiwum od początku działania systemu.
                </p>

                <p>
                    Surowa historia odpowiedzi jest utrzymywana przez
                    <span class="font-semibold text-slate-950">{{ $completedDays }} dni</span>,
                    a ranking <span class="font-semibold text-slate-950">Najtrudniejsze pytania</span> czyta okno
                    <span class="font-semibold text-slate-950">{{ $analyticsDays }} dni</span>.
                    Dzięki temu serwer pozostaje lekki, a statystyki pytań nie tracą zbyt szybko kontekstu.
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

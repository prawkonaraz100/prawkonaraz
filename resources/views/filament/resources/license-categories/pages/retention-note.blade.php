@php
    $completedDays = max((int) config('study.completed_session_retention_days', 365), 1);
    $abandonedDays = max((int) config('study.abandoned_session_retention_days', 7), 1);
    $adminDays = max((int) config('study.admin_activity_window_days', 90), 1);
    $analyticsDays = max((int) config('study.question_analytics_window_days', 365), 1);
@endphp

<x-filament::section
    heading="Retencja danych i znaczenie statystyk"
    description="Ta nota przypomina, jak czytać liczby w tabeli kategorii i jaki zakres danych jest utrzymywany operacyjnie."
>
    <div class="space-y-3 text-sm leading-6 text-slate-700">
        <p>
            Surowe zakończone sesje nauki są przechowywane przez
            <span class="font-semibold text-slate-950">{{ $completedDays }} dni</span>.
            Porzucone sesje <span class="font-semibold text-slate-950">in_progress</span> są usuwane po
            <span class="font-semibold text-slate-950">{{ $abandonedDays }} dniach</span>.
        </p>

        <p>
            Tabela kategorii pokazuje ruch tylko z operacyjnego okna
            <span class="font-semibold text-slate-950">{{ $adminDays }} dni</span>,
            żeby panel pozostawał lekki. To nie jest suma historyczna od początku działania systemu.
        </p>

        <p>
            Dzienne agregaty pytań trzymają detal przez
            <span class="font-semibold text-slate-950">{{ $analyticsDays }} dni</span>.
            Starsze dni przechodzą do
            <span class="font-semibold text-slate-950">archiwum miesięcznego</span>,
            więc trend historyczny zostaje, ale baza nie puchnie od pełnego detalu sprzed roku.
        </p>

        <p>
            Liczniki <span class="font-semibold text-slate-950">pytań</span> i
            <span class="font-semibold text-slate-950">profili kursantów</span> nie są obcinane przez tę retencję.
            Ranking <span class="font-semibold text-slate-950">Najtrudniejsze pytania</span> korzysta z osobnego okna
            <span class="font-semibold text-slate-950">{{ $analyticsDays }} dni</span>, żeby zachować pełniejszy obraz trudności pytań.
        </p>
    </div>
</x-filament::section>

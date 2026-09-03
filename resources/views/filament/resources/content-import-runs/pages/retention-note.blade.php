@php
    $completedDays = max((int) config('study.completed_session_retention_days', 365), 1);
    $abandonedDays = max((int) config('study.abandoned_session_retention_days', 7), 1);
    $adminDays = max((int) config('study.admin_activity_window_days', 90), 1);
    $analyticsDays = max((int) config('study.question_analytics_window_days', 365), 1);
@endphp

<x-filament::section
    heading="Retencja danych i wpływ na statystyki"
    description="Krótka nota operatorska, żeby rozdzielić historię importów od retencji surowych sesji nauki."
>
    <div class="space-y-3 text-sm leading-6 text-slate-700">
        <p>
            Historia importów i audytów w tym module nie jest czyszczona przez retencję sesji nauki.
            Te rekordy pozostają operacyjną historią działań administracyjnych.
        </p>

        <p>
            Retencja dotyczy surowych sesji kursantów:
            zakończone sesje nauki są utrzymywane przez
            <span class="font-semibold text-slate-950">{{ $completedDays }} dni</span>,
            a porzucone sesje <span class="font-semibold text-slate-950">in_progress</span> są usuwane po
            <span class="font-semibold text-slate-950">{{ $abandonedDays }} dniach</span>.
        </p>

        <p>
            Operacyjne liczniki w panelu czytamy z okna
            <span class="font-semibold text-slate-950">{{ $adminDays }} dni</span>,
            a publiczny ranking <span class="font-semibold text-slate-950">Najtrudniejsze pytania</span> z okna
            <span class="font-semibold text-slate-950">{{ $analyticsDays }} dni</span>.
        </p>

        <p>
            Dzienne agregaty pytań zachowują pełny detal przez
            <span class="font-semibold text-slate-950">{{ $analyticsDays }} dni</span>.
            Starsze dane są redukowane do
            <span class="font-semibold text-slate-950">archiwum miesięcznego</span>,
            więc monitoring trendów zostaje, ale baza nie jest obciążana pełnym rocznym detalem bez końca.
        </p>
    </div>
</x-filament::section>

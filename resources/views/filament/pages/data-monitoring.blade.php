<x-filament-panels::page>
    @if ($monitoringError)
        <div class="adm-monitor">
            <section class="adm-monitor__panel adm-monitor__hero">
                <div class="adm-monitor__hero-main">
                    <div class="adm-monitor__eyebrow">
                        <span class="adm-monitor__status-dot adm-monitor__status-dot--danger"></span>
                        <span>Stan monitoringu</span>
                    </div>
                    <div class="adm-monitor__hero-header">
                        <div>
                            <h2 class="adm-monitor__hero-title">{{ $monitoringError['headline'] }}</h2>
                            <p class="adm-monitor__hero-copy">{{ $monitoringError['message'] }}</p>
                        </div>
                        <div class="adm-monitor__status-badge adm-monitor__status-badge--danger">Awaria odczytu</div>
                    </div>
                </div>

                <div class="adm-monitor__stack">
                    <article class="adm-monitor__risk adm-monitor__risk--danger">
                        <div class="adm-monitor__risk-head">
                            <h4>Co sprawdzić teraz</h4>
                            <span class="adm-monitor__alert-chip adm-monitor__alert-chip--danger">pilne</span>
                        </div>
                        <p>Zweryfikuj połączenie z bazą, snapshoty monitoringu, storage backupów i ostatnie joby utrzymaniowe. Sam panel nie powinien już zwracać błędu 500, ale ten ekran wymaga wtedy reakcji.</p>
                        <span class="adm-monitor__risk-recommendation">Szczegóły techniczne: {{ $monitoringError['details'] ?: 'brak komunikatu wyjątku' }}</span>
                    </article>
                </div>
            </section>
        </div>
    @else
    @php
        $systemTone = $monitoring['job_health']['danger_count'] > 0
            ? 'danger'
            : ($monitoring['job_health']['warning_count'] > 0 ? 'warning' : 'success');

        $systemLabel = match ($systemTone) {
            'danger' => 'Wymaga interwencji',
            'warning' => 'Do sprawdzenia',
            default => 'Stabilny',
        };
    @endphp

    <div class="adm-monitor">
        <section class="adm-monitor__panel adm-monitor__hero">
            <div class="adm-monitor__hero-main">
                <div class="adm-monitor__eyebrow">
                    <span class="adm-monitor__status-dot adm-monitor__status-dot--{{ $systemTone }}"></span>
                    <span>Stan monitoringu</span>
                </div>
                <div class="adm-monitor__hero-header">
                    <div>
                        <h2 class="adm-monitor__hero-title">{{ $monitoring['job_health']['headline'] }}</h2>
                        <p class="adm-monitor__hero-copy">{{ $monitoring['job_health']['summary'] }}</p>
                    </div>
                    <div class="adm-monitor__status-badge adm-monitor__status-badge--{{ $systemTone }}">{{ $systemLabel }}</div>
                </div>
            </div>

            <div class="adm-monitor__meta-grid">
                <div class="adm-monitor__meta-item">
                    <span class="adm-monitor__meta-label">Driver</span>
                    <span class="adm-monitor__meta-value">{{ $monitoring['database']['driver'] }}</span>
                </div>
                <div class="adm-monitor__meta-item">
                    <span class="adm-monitor__meta-label">Rozmiar bazy</span>
                    <span class="adm-monitor__meta-value">{{ $monitoring['database']['size'] }}</span>
                </div>
                <div class="adm-monitor__meta-item">
                    <span class="adm-monitor__meta-label">Wolne miejsce</span>
                    <span class="adm-monitor__meta-value">{{ $monitoring['database']['volume_free'] }}</span>
                </div>
                <div class="adm-monitor__meta-item">
                    <span class="adm-monitor__meta-label">Presja wolumenu</span>
                    <span class="adm-monitor__meta-value">{{ $monitoring['database']['volume_used_ratio'] }}</span>
                </div>
                <div class="adm-monitor__meta-item">
                    <span class="adm-monitor__meta-label">Okno admina</span>
                    <span class="adm-monitor__meta-value">{{ $monitoring['retention']['admin_window_days'] }} dni</span>
                </div>
                <div class="adm-monitor__meta-item">
                    <span class="adm-monitor__meta-label">Analityka pytań</span>
                    <span class="adm-monitor__meta-value">{{ $monitoring['retention']['question_analytics_window_days'] }} dni</span>
                </div>
            </div>

            <div class="adm-monitor__kpi-grid">
                @foreach ($monitoring['summary'] as $item)
                    <article class="adm-monitor__kpi">
                        <span class="adm-monitor__kpi-label">{{ $item['label'] }}</span>
                        <span class="adm-monitor__kpi-value">{{ $item['value'] }}</span>
                        <div class="adm-monitor__kpi-deltas">
                            @foreach ($item['deltas'] as $delta)
                                <span class="adm-monitor__delta-chip adm-monitor__delta-chip--{{ $delta['tone'] }}">
                                    <span>{{ $delta['label'] }}</span>
                                    <strong>{{ $delta['value'] }}</strong>
                                </span>
                            @endforeach
                        </div>
                        <p class="adm-monitor__kpi-copy">{{ $item['details'] }}</p>
                        <p class="adm-monitor__kpi-foot">{{ $item['velocity'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="adm-monitor__analysis-grid">
            <section class="adm-monitor__panel">
                <div class="adm-monitor__section-header">
                    <div>
                        <h3 class="adm-monitor__section-title">Presja infrastruktury</h3>
                        <p class="adm-monitor__section-copy">Realny odczyt wolumenu, latencji bazy i presji infrastruktury dla aktywnego drivera danych.</p>
                    </div>
                    <span class="adm-monitor__job-chip adm-monitor__job-chip--{{ $monitoring['infrastructure']['tone'] }}">{{ $monitoring['infrastructure']['headline'] }}</span>
                </div>

                <p class="adm-monitor__section-copy">{{ $monitoring['infrastructure']['summary'] }}</p>

                <div class="adm-monitor__stack">
                    @foreach ($monitoring['infrastructure']['items'] as $item)
                        <article class="adm-monitor__quality-card">
                            <div class="adm-monitor__quality-head">
                                <div>
                                    <h4>{{ $item['label'] }}</h4>
                                    <p>{{ $item['details'] }}</p>
                                </div>
                                <span class="adm-monitor__job-chip adm-monitor__job-chip--{{ $item['tone'] }}">{{ $item['value'] }}</span>
                            </div>
                            <span class="adm-monitor__risk-recommendation">{{ $item['foot'] }}</span>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="adm-monitor__panel">
                <div class="adm-monitor__section-header">
                    <div>
                        <h3 class="adm-monitor__section-title">Health score</h3>
                        <p class="adm-monitor__section-copy">Syntetyczny wynik pokazujący, czy monitoring jest zdrowy, czy już zaczyna się rozjeżdżać.</p>
                    </div>
                </div>

                <div class="adm-monitor__score-card">
                    <div class="adm-monitor__score-main">
                        <span class="adm-monitor__score-value adm-monitor__score-value--{{ $monitoring['health_score']['tone'] }}">{{ $monitoring['health_score']['value'] }}</span>
                        <div>
                            <h4>{{ $monitoring['health_score']['label'] }}</h4>
                            <p>{{ $monitoring['health_score']['details'] }}</p>
                        </div>
                    </div>

                    <div class="adm-monitor__score-breakdown">
                        @foreach ($monitoring['health_score']['breakdown'] as $item)
                            <div class="adm-monitor__score-row">
                                <span>{{ $item['label'] }}</span>
                                <strong class="adm-monitor__delta adm-monitor__delta--{{ $item['tone'] }}">{{ $item['impact'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="adm-monitor__panel">
                <div class="adm-monitor__section-header">
                    <div>
                        <h3 class="adm-monitor__section-title">Jakość danych</h3>
                        <p class="adm-monitor__section-copy">Czy snapshoty, dzienne agregaty i archiwum dają nadal wiarygodny obraz systemu.</p>
                    </div>
                </div>

                <div class="adm-monitor__stack">
                    @foreach ($monitoring['data_quality'] as $label => $item)
                        <article class="adm-monitor__quality-card">
                            <div class="adm-monitor__quality-head">
                                <div>
                                    <h4>{{ str($label)->replace('_', ' ')->title() }}</h4>
                                    <p>{{ $item['details'] }}</p>
                                </div>
                                <span class="adm-monitor__job-chip adm-monitor__job-chip--{{ $item['tone'] }}">{{ $item['value'] }}</span>
                            </div>
                            <span class="adm-monitor__risk-recommendation">{{ $item['foot'] }}</span>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="adm-monitor__panel">
                <div class="adm-monitor__section-header">
                    <div>
                        <h3 class="adm-monitor__section-title">Wpływ retencji</h3>
                        <p class="adm-monitor__section-copy">Czy cleanup i rollup realnie zdejmują presję z bazy, czy tylko spowalniają wzrost.</p>
                    </div>
                </div>

                <div class="adm-monitor__stack adm-monitor__stack--tight">
                    @foreach ($monitoring['retention_impact'] as $item)
                        <div class="adm-monitor__metric-row">
                            <div>
                                <strong>{{ $item['label'] }}</strong>
                                <span>{{ $item['details'] }}</span>
                            </div>
                            <strong>{{ $item['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="adm-monitor__insights-grid">
            <section class="adm-monitor__panel">
                <div class="adm-monitor__section-header">
                    <div>
                        <h3 class="adm-monitor__section-title">Ryzyka i rekomendacje</h3>
                        <p class="adm-monitor__section-copy">Sygnały, które pomagają przewidywać problem zanim zrobi się incydent.</p>
                    </div>
                    <span class="adm-monitor__section-chip">Etap 2</span>
                </div>

                <div class="adm-monitor__stack">
                    @foreach ($monitoring['risks'] as $risk)
                        <article class="adm-monitor__risk adm-monitor__risk--{{ $risk['tone'] }}">
                            <div class="adm-monitor__risk-head">
                                <h4>{{ $risk['title'] }}</h4>
                                <span class="adm-monitor__alert-chip adm-monitor__alert-chip--{{ $risk['tone'] }}">{{ $risk['tone'] === 'success' ? 'ok' : ($risk['tone'] === 'danger' ? 'pilne' : ($risk['tone'] === 'warning' ? 'uwaga' : 'obserwuj')) }}</span>
                            </div>
                            <p>{{ $risk['details'] }}</p>
                            <span class="adm-monitor__risk-recommendation">{{ $risk['recommendation'] }}</span>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="adm-monitor__panel">
                <div class="adm-monitor__section-header">
                    <div>
                        <h3 class="adm-monitor__section-title">Prognoza 30 dni</h3>
                        <p class="adm-monitor__section-copy">Forecast liniowy z ostatnich 30 dni. Ma pomóc ocenić kierunek, nie zastępować pełnego BI.</p>
                    </div>
                    <span class="adm-monitor__section-chip">30 dni</span>
                </div>

                <div class="adm-monitor__stack">
                    @foreach ($monitoring['forecast']['items'] as $item)
                        <article class="adm-monitor__forecast">
                            <div class="adm-monitor__forecast-head">
                                <div>
                                    <h4>{{ $item['label'] }}</h4>
                                    <p>{{ $item['details'] }}</p>
                                </div>
                                <span class="adm-monitor__job-chip adm-monitor__job-chip--{{ $item['tone'] }}">{{ $item['tone_label'] }}</span>
                            </div>
                            <div class="adm-monitor__forecast-metrics">
                                <div>
                                    <span class="adm-monitor__meta-label">Teraz</span>
                                    <strong>{{ $item['current'] }}</strong>
                                </div>
                                <div>
                                    <span class="adm-monitor__meta-label">Tempo</span>
                                    <strong>{{ $item['daily_velocity'] }}</strong>
                                </div>
                                <div>
                                    <span class="adm-monitor__meta-label">Za 30 dni</span>
                                    <strong>{{ $item['forecast'] }}</strong>
                                </div>
                                <div>
                                    <span class="adm-monitor__meta-label">Zmiana</span>
                                    <strong>{{ $item['delta'] }}</strong>
                                </div>
                            </div>
                            <span class="adm-monitor__risk-recommendation">Dojrzałość forecastu: {{ $item['readiness'] }}. {{ $item['readiness_note'] }}</span>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="adm-monitor__operations-grid">
            <section class="adm-monitor__panel">
                <div class="adm-monitor__section-header">
                    <div>
                        <h3 class="adm-monitor__section-title">Co zrobić teraz</h3>
                        <p class="adm-monitor__section-copy">Kolejka działań operatorskich wynikająca z aktualnego health score, jakości danych i forecastu.</p>
                    </div>
                </div>

                <div class="adm-monitor__stack">
                    @foreach ($monitoring['recommended_actions'] as $action)
                        <article class="adm-monitor__action-card">
                            <div class="adm-monitor__action-head">
                                <div>
                                    <h4>{{ $action['title'] }}</h4>
                                    <p>{{ $action['details'] }}</p>
                                </div>
                                <span class="adm-monitor__priority-chip adm-monitor__priority-chip--{{ $action['priority'] === 'P1' ? 'danger' : ($action['priority'] === 'P2' ? 'warning' : ($action['priority'] === 'P3' ? 'neutral' : 'success')) }}">{{ $action['priority'] }}</span>
                            </div>
                            <span class="adm-monitor__risk-recommendation">Owner: {{ $action['owner'] }}</span>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="adm-monitor__panel">
                <div class="adm-monitor__section-header">
                    <div>
                        <h3 class="adm-monitor__section-title">Progi alarmowe</h3>
                        <p class="adm-monitor__section-copy">Jawne granice, od których panel podnosi warning albo danger.</p>
                    </div>
                </div>

                <div class="adm-monitor__table-wrap">
                    <table class="adm-monitor__table adm-monitor__table--compact">
                        <thead>
                            <tr>
                                <th>Obszar</th>
                                <th>Warning</th>
                                <th>Danger</th>
                                <th>Znaczenie</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monitoring['threshold_rows'] as $row)
                                <tr>
                                    <td class="is-strong">{{ $row['label'] }}</td>
                                    <td class="is-numeric">{{ $row['warning'] }}</td>
                                    <td class="is-numeric">{{ $row['danger'] }}</td>
                                    <td>{{ $row['details'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="adm-monitor__main-grid">
            <section class="adm-monitor__panel adm-monitor__charts-panel">
                <div class="adm-monitor__section-header">
                    <div>
                        <h3 class="adm-monitor__section-title">Wykresy wzrostu</h3>
                        <p class="adm-monitor__section-copy">Dwa główne trendy: warstwa surowa i warstwa analityczna.</p>
                    </div>
                    <span class="adm-monitor__section-chip">Widok 30 dni</span>
                </div>

                <div class="adm-monitor__charts-grid">
                    @foreach ($monitoring['charts'] as $chart)
                        <article class="adm-monitor__chart-card">
                            <div class="adm-monitor__chart-header">
                                <div>
                                    <h4 class="adm-monitor__chart-title">{{ $chart['title'] }}</h4>
                                    <p class="adm-monitor__chart-copy">{{ $chart['description'] }}</p>
                                </div>
                                @if (! $chart['empty'])
                                    <span class="adm-monitor__chart-range">{{ $chart['points_count'] }} dni</span>
                                @endif
                            </div>

                            @if ($chart['empty'])
                                <div class="adm-monitor__chart-empty">
                                    Snapshoty dopiero zaczynają się zbierać. Po pierwszych zapisanych dniach wykres zacznie pokazywać przyrosty.
                                </div>
                            @else
                                <div class="adm-monitor__legend">
                                    <table class="adm-monitor__table adm-monitor__table--compact">
                                        <thead>
                                            <tr>
                                                <th>Seria</th>
                                                <th>Start</th>
                                                <th>Teraz</th>
                                                <th>Zmiana</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($chart['series'] as $series)
                                                <tr>
                                                    <td>
                                                        <div class="adm-monitor__series">
                                                            <span class="adm-monitor__series-dot" style="background-color: {{ $series['color'] }}"></span>
                                                            <span>{{ $series['label'] }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="is-numeric">{{ $series['start_value'] }}</td>
                                                    <td class="is-numeric is-strong">{{ $series['value'] }}</td>
                                                    <td class="is-numeric adm-monitor__delta adm-monitor__delta--{{ $series['delta_tone'] }}">{{ $series['delta'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="adm-monitor__sparkline">
                                    <svg viewBox="0 0 100 44" preserveAspectRatio="none">
                                        @foreach ([8, 16, 24, 32, 40] as $gridY)
                                            <line x1="0" y1="{{ $gridY }}" x2="100" y2="{{ $gridY }}" />
                                        @endforeach
                                        @foreach ($chart['series'] as $series)
                                            <polyline points="{{ $series['points'] }}" stroke="{{ $series['color'] }}" />
                                        @endforeach
                                    </svg>

                                    <div class="adm-monitor__sparkline-axis">
                                        <span>{{ $chart['start_label'] }}</span>
                                        <span>maks {{ $chart['max_label'] }}</span>
                                        <span>{{ $chart['end_label'] }}</span>
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            <aside class="adm-monitor__sidebar">
                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Anomalie i trendy</h3>
                            <p class="adm-monitor__section-copy">Najważniejsze odchylenia tydzień do tygodnia i sygnały o utracie jakości danych.</p>
                        </div>
                    </div>

                    <div class="adm-monitor__stack">
                        @foreach ($monitoring['anomalies'] as $item)
                            <article class="adm-monitor__alert adm-monitor__alert--{{ $item['tone'] }}">
                                <div class="adm-monitor__alert-head">
                                    <h4>{{ $item['label'] }}</h4>
                                    <span class="adm-monitor__alert-chip adm-monitor__alert-chip--{{ $item['tone'] }}">{{ $item['headline'] }}</span>
                                </div>
                                <p>{{ $item['details'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Najszybszy wzrost</h3>
                            <p class="adm-monitor__section-copy">Warstwy danych posortowane po zmianie z ostatnich 30 dni.</p>
                        </div>
                    </div>

                    <div class="adm-monitor__stack adm-monitor__stack--tight">
                        @foreach ($monitoring['growth_ranking'] as $item)
                            <div class="adm-monitor__growth-row">
                                <div>
                                    <strong>{{ $item['label'] }}</strong>
                                    <span>{{ $item['velocity'] }}</span>
                                </div>
                                <div class="adm-monitor__growth-values">
                                    <span>{{ $item['current'] }}</span>
                                    <strong class="adm-monitor__delta adm-monitor__delta--{{ $item['tone'] }}">{{ $item['delta'] }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Trend tygodniowy vs miesięczny</h3>
                            <p class="adm-monitor__section-copy">Czy ostatni tydzień przyspiesza względem tego, co pokazywał cały miesiąc.</p>
                        </div>
                    </div>

                    <div class="adm-monitor__table-wrap">
                        <table class="adm-monitor__table adm-monitor__table--compact">
                            <thead>
                                <tr>
                                    <th>Warstwa</th>
                                    <th>7 dni</th>
                                    <th>30 dni</th>
                                    <th>Sygnał</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($monitoring['trend_comparisons'] as $trend)
                                    <tr>
                                        <td class="is-strong">{{ $trend['label'] }}</td>
                                        <td class="is-numeric">{{ $trend['week'] }}</td>
                                        <td class="is-numeric">{{ $trend['month'] }}</td>
                                        <td class="is-numeric adm-monitor__delta adm-monitor__delta--{{ $trend['tone'] }}">{{ $trend['signal'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </aside>
        </div>

        <div class="adm-monitor__bottom-grid">
            <section class="adm-monitor__panel">
                <div class="adm-monitor__section-header">
                    <div>
                        <h3 class="adm-monitor__section-title">Aktywność w oknach czasu</h3>
                        <p class="adm-monitor__section-copy">Przekrój przez ruch, agregaty dzienne i archiwum miesięczne.</p>
                    </div>
                </div>

                <div class="adm-monitor__table-wrap">
                    <table class="adm-monitor__table">
                        <thead>
                            <tr>
                                <th>Okno</th>
                                <th>Sesje</th>
                                <th>Odpowiedzi</th>
                                <th>Dziennie</th>
                                <th>Miesięcznie</th>
                                <th>Aktywne pytania</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monitoring['activity_windows'] as $window)
                                <tr>
                                    <td class="is-strong">{{ $window['label'] }}</td>
                                    <td class="is-numeric">{{ $window['sessions'] }}</td>
                                    <td class="is-numeric">{{ $window['answers'] }}</td>
                                    <td class="is-numeric">{{ $window['daily_stats'] }}</td>
                                    <td class="is-numeric">{{ $window['monthly_stats'] }}</td>
                                    <td class="is-numeric">{{ $window['active_questions'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="adm-monitor__side-grid">
                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Ostatnie zdarzenia operatorskie</h3>
                            <p class="adm-monitor__section-copy">Krótka historia najważniejszych przebiegów i punktów kontrolnych.</p>
                        </div>
                    </div>

                    <div class="adm-monitor__stack">
                        @foreach ($monitoring['event_timeline'] as $event)
                            <article class="adm-monitor__timeline-card">
                                <div class="adm-monitor__timeline-head">
                                    <div>
                                        <h4>{{ $event['title'] }}</h4>
                                        <p>{{ $event['details'] }}</p>
                                    </div>
                                    <span class="adm-monitor__job-chip adm-monitor__job-chip--{{ $event['tone'] }}">{{ $event['time'] }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Retencja i archiwizacja</h3>
                            <p class="adm-monitor__section-copy">Co jest operacyjne, a co już trendem historycznym.</p>
                        </div>
                    </div>

                    <div class="adm-monitor__stack">
                        @foreach ($monitoring['retention_rows'] as $row)
                            <article class="adm-monitor__retention-row">
                                <div class="adm-monitor__retention-head">
                                    <h4>{{ $row['label'] }}</h4>
                                    <span class="adm-monitor__retention-chip">{{ $row['window'] }}</span>
                                </div>
                                <p>{{ $row['details'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Wolumen danych</h3>
                            <p class="adm-monitor__section-copy">Najcięższe warstwy danych i ścieżka bazy.</p>
                        </div>
                    </div>

                    <div class="adm-monitor__database">
                        <div>
                            <span class="adm-monitor__meta-label">Baza danych</span>
                            <p class="adm-monitor__database-path">{{ $monitoring['database']['path'] }}</p>
                        </div>
                        <div class="adm-monitor__database-meta">
                            <span class="adm-monitor__meta-label">{{ $monitoring['database']['driver'] }}</span>
                            <strong>{{ $monitoring['database']['size'] }}</strong>
                        </div>
                    </div>

                    <div class="adm-monitor__stack adm-monitor__stack--tight">
                        @foreach ($monitoring['table_counts'] as $item)
                            <div class="adm-monitor__metric-row">
                                <span>{{ $item['label'] }}</span>
                                <strong>{{ $item['value'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
    @endif
</x-filament-panels::page>

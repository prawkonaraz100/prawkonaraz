@once
    <style>
        .pjm-admin {
            --pjm-ink: #0f172a;
            --pjm-muted: #64748b;
            --pjm-line: #d8e0eb;
            --pjm-soft: #f5f8fc;
            --pjm-blue: #174ea6;
            --pjm-blue-dark: #0c2f66;
            --pjm-green: #047857;
            --pjm-amber: #b45309;
            --pjm-red: #b91c1c;
            display: grid;
            gap: 1rem;
            color: var(--pjm-ink);
        }

        .pjm-admin * {
            box-sizing: border-box;
        }

        .pjm-hero,
        .pjm-panel,
        .pjm-kpi,
        .pjm-lookup,
        .pjm-empty {
            border: 1px solid var(--pjm-line);
            background: #fff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .06);
        }

        .pjm-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(17rem, 22rem);
            gap: 1rem;
            overflow: hidden;
            border-radius: 1.35rem;
            background:
                radial-gradient(circle at 12% 10%, rgba(96, 165, 250, .30), transparent 28rem),
                linear-gradient(135deg, #06142c 0%, #0d2d61 46%, #123f84 100%);
            color: #fff;
            padding: 1.15rem;
        }

        .pjm-eyebrow {
            color: #9cc5ff;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .pjm-hero h2,
        .pjm-panel h3,
        .pjm-lookup h3,
        .pjm-empty h3 {
            margin: 0;
            font-weight: 800;
            letter-spacing: -.035em;
        }

        .pjm-hero h2 {
            max-width: 38rem;
            margin-top: .55rem;
            font-size: clamp(1.65rem, 3vw, 2.55rem);
            line-height: 1.03;
        }

        .pjm-hero p {
            max-width: 48rem;
            margin: .75rem 0 0;
            color: rgba(255, 255, 255, .78);
            font-size: .98rem;
            line-height: 1.65;
        }

        .pjm-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-top: 1rem;
        }

        .pjm-chip,
        .pjm-status-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            min-height: 2rem;
            border-radius: 999px;
            padding: .35rem .72rem;
            font-size: .78rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .pjm-chip {
            border: 1px solid rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .10);
            color: rgba(255, 255, 255, .88);
        }

        .pjm-status-card {
            align-self: stretch;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 1rem;
            background: rgba(255, 255, 255, .10);
            padding: 1rem;
            backdrop-filter: blur(10px);
        }

        .pjm-status-card span {
            display: block;
            color: rgba(255, 255, 255, .62);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .pjm-status-card strong {
            display: block;
            margin-top: .5rem;
            font-size: 1.35rem;
            line-height: 1.1;
        }

        .pjm-status-card p {
            margin-top: .65rem;
            font-size: .85rem;
            line-height: 1.5;
        }

        .pjm-status-pill.is-good {
            background: #dcfce7;
            color: #166534;
        }

        .pjm-status-pill.is-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .pjm-status-pill.is-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .pjm-status-pill.is-neutral {
            background: #e2e8f0;
            color: #334155;
        }

        .pjm-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-top: .85rem;
        }

        .pjm-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.35rem;
            border-radius: .72rem;
            border: 1px solid rgba(255, 255, 255, .18);
            padding: .55rem .85rem;
            background: rgba(255, 255, 255, .12);
            color: #fff;
            font-size: .84rem;
            font-weight: 800;
            text-decoration: none;
            transition: transform .16s ease, background .16s ease, border-color .16s ease;
        }

        .pjm-button:hover {
            transform: translateY(-1px);
            border-color: rgba(255, 255, 255, .38);
            background: rgba(255, 255, 255, .18);
        }

        .pjm-kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .75rem;
        }

        .pjm-kpi {
            min-height: 7.2rem;
            border-radius: 1rem;
            padding: 1rem;
        }

        .pjm-kpi span,
        .pjm-panel-label {
            color: var(--pjm-muted);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .pjm-kpi strong {
            display: block;
            margin-top: .65rem;
            font-size: clamp(1.6rem, 2.8vw, 2.3rem);
            font-weight: 850;
            letter-spacing: -.05em;
            line-height: 1;
        }

        .pjm-kpi p {
            margin: .45rem 0 0;
            color: var(--pjm-muted);
            font-size: .82rem;
            line-height: 1.45;
        }

        .pjm-kpi.is-blue strong {
            color: var(--pjm-blue);
        }

        .pjm-kpi.is-green strong {
            color: var(--pjm-green);
        }

        .pjm-kpi.is-amber strong {
            color: var(--pjm-amber);
        }

        .pjm-kpi.is-red strong {
            color: var(--pjm-red);
        }

        .pjm-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(20rem, 25rem);
            gap: 1rem;
        }

        .pjm-panel,
        .pjm-lookup,
        .pjm-empty {
            overflow: hidden;
            border-radius: 1.1rem;
        }

        .pjm-panel-header,
        .pjm-panel-body {
            padding: 1rem;
        }

        .pjm-panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid var(--pjm-line);
            background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
        }

        .pjm-panel h3,
        .pjm-lookup h3,
        .pjm-empty h3 {
            margin-top: .3rem;
            font-size: 1.15rem;
        }

        .pjm-panel-header p,
        .pjm-panel-body > p,
        .pjm-empty p {
            margin: .35rem 0 0;
            color: var(--pjm-muted);
            font-size: .85rem;
            line-height: 1.55;
        }

        .pjm-muted-count {
            color: var(--pjm-muted);
            font-size: .8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .pjm-table-wrap {
            overflow-x: auto;
        }

        .pjm-table {
            width: 100%;
            min-width: 52rem;
            border-collapse: collapse;
            font-size: .86rem;
        }

        .pjm-table th {
            padding: .72rem 1rem;
            background: #f8fafc;
            color: var(--pjm-muted);
            font-size: .68rem;
            font-weight: 850;
            letter-spacing: .11em;
            text-align: left;
            text-transform: uppercase;
        }

        .pjm-table td {
            border-top: 1px solid #edf2f7;
            padding: .85rem 1rem;
            vertical-align: middle;
        }

        .pjm-table tr.is-focus {
            background: #eff6ff;
        }

        .pjm-table strong {
            display: block;
            font-size: .95rem;
        }

        .pjm-number {
            font-variant-numeric: tabular-nums;
            text-align: right;
            white-space: nowrap;
        }

        .pjm-progress {
            display: grid;
            gap: .35rem;
            min-width: 10rem;
        }

        .pjm-progress-track {
            overflow: hidden;
            height: .55rem;
            border-radius: 999px;
            background: #e2e8f0;
        }

        .pjm-progress-fill {
            display: block;
            width: var(--value);
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #1d4ed8, #38bdf8);
        }

        .pjm-progress small {
            color: var(--pjm-muted);
            font-size: .76rem;
            font-variant-numeric: tabular-nums;
            text-align: right;
        }

        .pjm-side-stack {
            display: grid;
            gap: 1rem;
            align-content: start;
        }

        .pjm-work-list {
            display: grid;
            gap: .65rem;
        }

        .pjm-work-item {
            display: grid;
            gap: .45rem;
            border: 1px solid #e2e8f0;
            border-radius: .9rem;
            padding: .8rem;
            background: #fff;
        }

        .pjm-work-top {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: .75rem;
        }

        .pjm-work-top strong {
            font-size: .9rem;
        }

        .pjm-work-top span {
            font-size: 1.25rem;
            font-weight: 850;
            font-variant-numeric: tabular-nums;
            letter-spacing: -.04em;
        }

        .pjm-work-item p {
            margin: 0;
            color: var(--pjm-muted);
            font-size: .8rem;
            line-height: 1.45;
        }

        .pjm-form {
            display: grid;
            gap: .65rem;
        }

        .pjm-form-row {
            display: flex;
            gap: .55rem;
        }

        .pjm-input {
            width: 100%;
            min-height: 2.55rem;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            padding: .55rem .75rem;
            background: #fff;
            color: var(--pjm-ink);
            font-size: .9rem;
            outline: none;
        }

        .pjm-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .14);
        }

        .pjm-submit,
        .pjm-link-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.55rem;
            border: 0;
            border-radius: .75rem;
            padding: .55rem .85rem;
            background: var(--pjm-blue);
            color: #fff;
            font-size: .85rem;
            font-weight: 850;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .pjm-link-button.is-light {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: var(--pjm-blue-dark);
        }

        .pjm-mini-list {
            display: grid;
            gap: .55rem;
        }

        .pjm-mini-row,
        .pjm-sample-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            border-radius: .75rem;
            background: var(--pjm-soft);
            padding: .65rem .75rem;
            color: var(--pjm-ink);
            font-size: .84rem;
        }

        .pjm-mini-row span,
        .pjm-sample-row span {
            color: var(--pjm-muted);
            font-size: .78rem;
        }

        .pjm-samples {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .pjm-sample-card {
            border: 1px solid var(--pjm-line);
            border-radius: 1rem;
            background: #fff;
            padding: 1rem;
        }

        .pjm-sample-card h4 {
            margin: 0;
            font-size: .95rem;
            font-weight: 850;
        }

        .pjm-sample-card p {
            margin: .35rem 0 .8rem;
            color: var(--pjm-muted);
            font-size: .82rem;
            line-height: 1.45;
        }

        .pjm-empty {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
            background: #fff7ed;
            border-color: #fed7aa;
        }

        .pjm-empty h3 {
            color: #9a3412;
        }

        .pjm-lookup {
            display: grid;
            gap: 0;
        }

        .pjm-lookup-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 1rem;
            padding: 1rem;
        }

        .pjm-record {
            border: 1px solid #e2e8f0;
            border-radius: .95rem;
            padding: .85rem;
            background: #fff;
        }

        .pjm-record + .pjm-record {
            margin-top: .65rem;
        }

        .pjm-record-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .45rem;
        }

        .pjm-record p {
            margin: .4rem 0 0;
            color: var(--pjm-muted);
            font-size: .82rem;
            line-height: 1.5;
        }

        .pjm-path {
            overflow-wrap: anywhere;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .74rem;
        }

        .pjm-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .22rem .55rem;
            background: #e2e8f0;
            color: #334155;
            font-size: .72rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .pjm-badge.is-good {
            background: #dcfce7;
            color: #166534;
        }

        .pjm-badge.is-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .pjm-badge.is-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .pjm-text-green {
            color: var(--pjm-green);
        }

        .pjm-text-amber {
            color: var(--pjm-amber);
        }

        .pjm-text-red {
            color: var(--pjm-red);
        }

        @media (max-width: 1180px) {
            .pjm-kpi-grid,
            .pjm-samples {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pjm-grid,
            .pjm-hero,
            .pjm-lookup-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .pjm-hero {
                padding: 1rem;
                border-radius: 1rem;
            }

            .pjm-kpi-grid,
            .pjm-samples,
            .pjm-empty {
                grid-template-columns: 1fr;
            }

            .pjm-form-row {
                flex-direction: column;
            }
        }
    </style>
@endonce

<x-filament-panels::page>
    @if ($reportError)
        <section class="pjm-admin">
            <div class="pjm-empty">
                <div>
                    <span class="pjm-panel-label">PJM</span>
                    <h3>{{ $reportError['headline'] }}</h3>
                    <p>{{ $reportError['message'] }}</p>
                    <p class="pjm-path">{{ $reportError['details'] ?: 'Brak szczegółów technicznych.' }}</p>
                </div>
            </div>
        </section>
    @else
        @php
            $summary = $report['summary'];
            $lookup = $report['external_id_lookup'];
            $number = fn (int|float|null $value): string => number_format((float) ($value ?? 0), 0, ',', ' ');
            $percent = fn (int|float|null $value, int $decimals = 1): string => number_format((float) ($value ?? 0), $decimals, ',', ' ').'%';
            $coverage = (float) $summary['coverage_percent'];
            $hasAssets = (int) $summary['total_assets'] > 0;
            $hasHardProblems = (int) $summary['processing_problem_assets'] > 0 || (int) $summary['orphaned_assets'] > 0;
            $readyForPaidModule = $coverage >= 100.0 && ! $hasHardProblems;

            if (! $hasAssets) {
                $healthTone = 'neutral';
                $healthLabel = 'Brak importu';
                $healthText = 'Tabela jest gotowa, ale nie ma jeszcze zaimportowanych filmów PJM.';
            } elseif ($readyForPaidModule) {
                $healthTone = 'good';
                $healthLabel = 'Gotowe do publikacji';
                $healthText = 'Aktywna baza ma komplet filmów PJM i nie widać blokujących problemów.';
            } elseif ($hasHardProblems) {
                $healthTone = 'danger';
                $healthLabel = 'Wymaga naprawy';
                $healthText = 'Są assety bez pytania albo problemy processingu. To trzeba wyczyścić przed publikacją.';
            } else {
                $healthTone = 'warning';
                $healthLabel = 'W trakcie uzupełniania';
                $healthText = 'Moduł działa jako darmowy zakres PJM, ale nie jest jeszcze pełnym płatnym produktem.';
            }

            $topMissingCategories = collect($report['categories'])
                ->sortByDesc('missing_questions')
                ->take(5)
                ->values();

            $questionEditUrl = function (int|string $id): string {
                try {
                    return \App\Filament\Resources\Questions\QuestionResource::getUrl('edit', ['record' => $id], panel: 'admin');
                } catch (\Throwable) {
                    return url('/admin/questions/'.$id.'/edit');
                }
            };

            $statusToneClass = fn (string $tone): string => match ($tone) {
                'success' => 'is-good',
                'warning' => 'is-warning',
                default => 'is-danger',
            };

            $workItems = [
                [
                    'label' => 'Do ręcznej kontroli',
                    'count' => (int) $summary['review_required_assets'],
                    'tone' => (int) $summary['review_required_assets'] > 0 ? 'pjm-text-amber' : 'pjm-text-green',
                    'description' => 'Filmy oznaczone jako review_required. Admin powinien je obejrzeć przed pełnym udostępnieniem.',
                ],
                [
                    'label' => 'Problemy processingu',
                    'count' => (int) $summary['processing_problem_assets'],
                    'tone' => (int) $summary['processing_problem_assets'] > 0 ? 'pjm-text-red' : 'pjm-text-green',
                    'description' => 'Wyłączone lub nietypowe statusy. To są elementy blokujące jakość danych.',
                ],
                [
                    'label' => 'Osierocone assety',
                    'count' => (int) $summary['orphaned_assets'],
                    'tone' => (int) $summary['orphaned_assets'] > 0 ? 'pjm-text-red' : 'pjm-text-green',
                    'description' => 'Pliki PJM, których external_id nie pasuje do aktywnego pytania w bazie.',
                ],
            ];
        @endphp

        <div class="pjm-admin">
            <section class="pjm-hero">
                <div>
                    <div class="pjm-eyebrow">PJM / panel operacyjny</div>
                    <h2>Pokrycie filmów PJM w aktywnej bazie pytań</h2>
                    <p>
                        Ten ekran ma dać szybką odpowiedź: czy możemy bezpiecznie pokazać moduł PJM,
                        gdzie są największe braki i które pliki wymagają ręcznej decyzji.
                    </p>

                    <div class="pjm-hero-meta">
                        <span class="pjm-chip">Wygenerowano: {{ \Illuminate\Support\Carbon::parse($report['generated_at'])->format('d.m.Y H:i') }}</span>
                        <span class="pjm-chip">{{ $number($summary['active_assets']) }} aktywnych assetów</span>
                        <span class="pjm-chip">{{ $summary['total_bytes_human'] }} storage</span>
                    </div>

                </div>

                <aside class="pjm-status-card">
                    <span>Gotowość modułu</span>
                    <strong>{{ $healthLabel }}</strong>
                    <p>{{ $healthText }}</p>
                    <div class="pjm-actions">
                        <span class="pjm-status-pill is-{{ $healthTone }}">{{ $percent($coverage, 2) }} coverage</span>
                    </div>
                </aside>
            </section>

            @if (! $hasAssets)
                <section class="pjm-empty">
                    <div>
                        <span class="pjm-panel-label">Następny krok</span>
                        <h3>Nie ma jeszcze zaimportowanych filmów PJM</h3>
                        <p>
                            Struktura raportu działa, ale baza assetów jest pusta. Po imporcie ten ekran pokaże realne pokrycie,
                            kolejkę review i brakujące pytania.
                        </p>
                    </div>
                    <span class="pjm-status-pill is-neutral">0 assetów</span>
                </section>
            @endif

            <section class="pjm-kpi-grid">
                <article class="pjm-kpi is-blue">
                    <span>Coverage</span>
                    <strong>{{ $percent($summary['coverage_percent'], 2) }}</strong>
                    <p>Realne pokrycie pytań aktywnych filmem w roli pytania.</p>
                </article>
                <article class="pjm-kpi is-green">
                    <span>Pytania z PJM</span>
                    <strong>{{ $number($summary['pjm_questions']) }}</strong>
                    <p>z {{ $number($summary['total_questions']) }} pytań w aktywnej bazie.</p>
                </article>
                <article class="pjm-kpi is-amber">
                    <span>Braki</span>
                    <strong>{{ $number($summary['missing_questions']) }}</strong>
                    <p>Pytania, które nadal nie mają filmu PJM.</p>
                </article>
                <article class="pjm-kpi {{ $summary['review_required_assets'] > 0 ? 'is-amber' : 'is-green' }}">
                    <span>Review</span>
                    <strong>{{ $number($summary['review_required_assets']) }}</strong>
                    <p>Filmy wymagające ręcznej kontroli jakości.</p>
                </article>
                <article class="pjm-kpi {{ $hasHardProblems ? 'is-red' : 'is-green' }}">
                    <span>Blokery danych</span>
                    <strong>{{ $number($summary['processing_problem_assets'] + $summary['orphaned_assets']) }}</strong>
                    <p>Problemy processingu plus assety bez pasującego pytania.</p>
                </article>
            </section>

            <section class="pjm-grid">
                <div class="pjm-panel">
                    <div class="pjm-panel-header">
                        <div>
                            <span class="pjm-panel-label">Kategorie</span>
                            <h3>Pokrycie według kategorii prawa jazdy</h3>
                            <p>Najpierw patrzymy na realne braki. Kategoria B jest wyróżniona, bo to główny start dla PJM.</p>
                        </div>
                        <span class="pjm-muted-count">{{ count($report['categories']) }} kategorii</span>
                    </div>

                    <div class="pjm-table-wrap">
                        <table class="pjm-table">
                            <thead>
                                <tr>
                                    <th>Kategoria</th>
                                    <th class="pjm-number">Pytania</th>
                                    <th class="pjm-number">PJM</th>
                                    <th class="pjm-number">Braki</th>
                                    <th>Coverage</th>
                                    <th class="pjm-number">Assety</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['categories'] as $category)
                                    @php
                                        $categoryCoverage = max(0, min(100, (float) $category['coverage_percent']));
                                        $coverageStyle = '--value: '.$categoryCoverage.'%;';
                                    @endphp
                                    <tr @class(['is-focus' => $category['code'] === 'B'])>
                                        <td>
                                            <strong>{{ $category['code'] }}</strong>
                                            <span>{{ $category['name'] }}</span>
                                        </td>
                                        <td class="pjm-number">{{ $number($category['total_questions']) }}</td>
                                        <td class="pjm-number">{{ $number($category['pjm_questions']) }}</td>
                                        <td class="pjm-number {{ $category['missing_questions'] > 0 ? 'pjm-text-amber' : 'pjm-text-green' }}">
                                            {{ $number($category['missing_questions']) }}
                                        </td>
                                        <td>
                                            <div class="pjm-progress">
                                                <div class="pjm-progress-track">
                                                    <span class="pjm-progress-fill" style="{{ $coverageStyle }}"></span>
                                                </div>
                                                <small>{{ $percent($category['coverage_percent'], 2) }}</small>
                                            </div>
                                        </td>
                                        <td class="pjm-number">
                                            {{ $number($category['question_assets']) }} pyt. / {{ $number($category['answer_assets']) }} odp.
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="pjm-side-stack">
                    <section class="pjm-panel">
                        <div class="pjm-panel-header">
                            <div>
                                <span class="pjm-panel-label">Kolejka pracy</span>
                                <h3>Co admin powinien sprawdzić</h3>
                            </div>
                        </div>
                        <div class="pjm-panel-body">
                            <div class="pjm-work-list">
                                @foreach ($workItems as $item)
                                    <article class="pjm-work-item">
                                        <div class="pjm-work-top">
                                            <strong>{{ $item['label'] }}</strong>
                                            <span class="{{ $item['tone'] }}">{{ $number($item['count']) }}</span>
                                        </div>
                                        <p>{{ $item['description'] }}</p>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section class="pjm-panel">
                        <div class="pjm-panel-header">
                            <div>
                                <span class="pjm-panel-label">Inspektor</span>
                                <h3>Sprawdź external_id</h3>
                                <p>Najkrótsza droga do sprawdzenia, czy konkretny numer ma pytanie i pliki PJM.</p>
                            </div>
                        </div>
                        <div class="pjm-panel-body">
                            <form method="GET" class="pjm-form">
                                <label for="external_id" class="pjm-panel-label">Numer pytania</label>
                                <div class="pjm-form-row">
                                    <input
                                        id="external_id"
                                        name="external_id"
                                        value="{{ $externalId }}"
                                        placeholder="np. 941"
                                        class="pjm-input"
                                    />
                                    <button type="submit" class="pjm-submit">Sprawdź</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="pjm-panel">
                        <div class="pjm-panel-header">
                            <div>
                                <span class="pjm-panel-label">Import</span>
                                <h3>Statusy i role</h3>
                            </div>
                        </div>
                        <div class="pjm-panel-body">
                            <div class="pjm-mini-list">
                                @forelse ($report['processing_statuses'] as $status)
                                    <div class="pjm-mini-row">
                                        <strong>{{ $status['label'] }}</strong>
                                        <span class="pjm-badge {{ $statusToneClass($status['tone']) }}">{{ $number($status['count']) }}</span>
                                    </div>
                                @empty
                                    <div class="pjm-mini-row">
                                        <strong>Brak statusów</strong>
                                        <span>import jeszcze nie ruszył</span>
                                    </div>
                                @endforelse

                                @foreach ($report['asset_roles'] as $role)
                                    <div class="pjm-mini-row">
                                        <strong>{{ $role['label'] }}</strong>
                                        <span>{{ $number($role['count']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                </aside>
            </section>

            @if ($lookup)
                <section class="pjm-lookup">
                    <div class="pjm-panel-header">
                        <div>
                            <span class="pjm-panel-label">Inspektor external_id</span>
                            <h3>{{ $lookup['external_id'] }}</h3>
                            <p>
                                {{ $lookup['question_count'] }} pytań w bazie,
                                {{ $lookup['asset_count'] }} assetów PJM,
                                status filmu pytania:
                                <strong>{{ $lookup['has_question_asset'] ? 'jest' : 'brak' }}</strong>.
                            </p>
                        </div>
                        <span class="pjm-status-pill {{ $lookup['has_question_asset'] ? 'is-good' : 'is-warning' }}">
                            {{ $lookup['has_question_asset'] ? 'ma film pytania' : 'brak filmu pytania' }}
                        </span>
                    </div>

                    <div class="pjm-lookup-grid">
                        <div>
                            <span class="pjm-panel-label">Pytania</span>
                            <div style="margin-top: .65rem;">
                                @forelse ($lookup['questions'] as $question)
                                    <article class="pjm-record">
                                        <div class="pjm-record-head">
                                            <strong>Kat. {{ $question['category_code'] ?: '-' }}</strong>
                                            <span class="pjm-badge {{ $question['is_active'] ? 'is-good' : 'is-danger' }}">
                                                {{ $question['is_active'] ? 'aktywne' : 'nieaktywne' }}
                                            </span>
                                        </div>
                                        <p>{{ $question['prompt'] }}</p>
                                        <div class="pjm-actions">
                                            <a class="pjm-link-button is-light" href="{{ $questionEditUrl($question['id']) }}">
                                                Otwórz pytanie
                                            </a>
                                        </div>
                                    </article>
                                @empty
                                    <article class="pjm-record">
                                        <strong>Nie znaleziono pytania</strong>
                                        <p>Ten external_id nie pasuje do żadnego pytania w bazie.</p>
                                    </article>
                                @endforelse
                            </div>
                        </div>

                        <div>
                            <span class="pjm-panel-label">Assety PJM</span>
                            <div style="margin-top: .65rem;">
                                @forelse ($lookup['assets'] as $asset)
                                    <article class="pjm-record">
                                        <div class="pjm-record-head">
                                            <strong>{{ $asset['role'] }}</strong>
                                            <span class="pjm-badge {{ $asset['processing_status'] === 'ready' ? 'is-good' : ($asset['processing_status'] === 'review_required' ? 'is-warning' : 'is-danger') }}">
                                                {{ $asset['processing_status'] }}
                                            </span>
                                        </div>
                                        <p class="pjm-path">{{ $asset['disk'] }}:{{ $asset['path'] }}</p>
                                        <p>
                                            {{ $asset['bytes_human'] }} ·
                                            {{ $asset['review_required'] ? 'review_required' : 'bez review' }} ·
                                            {{ $asset['is_active'] ? 'aktywny' : 'wyłączony' }}
                                        </p>
                                        @if ($asset['url'])
                                            <div class="pjm-actions">
                                                <a href="{{ $asset['url'] }}" target="_blank" rel="noopener noreferrer" class="pjm-link-button is-light">
                                                    Otwórz plik
                                                </a>
                                            </div>
                                        @endif
                                    </article>
                                @empty
                                    <article class="pjm-record">
                                        <strong>Brak assetów</strong>
                                        <p>Dla tego external_id nie ma jeszcze żadnego pliku PJM.</p>
                                    </article>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <section class="pjm-samples">
                <article class="pjm-sample-card">
                    <h4>Największe braki</h4>
                    <p>Kategorie, w których trzeba uzupełnić najwięcej filmów.</p>
                    <div class="pjm-mini-list">
                        @foreach ($topMissingCategories as $category)
                            <div class="pjm-sample-row">
                                <strong>{{ $category['code'] }}</strong>
                                <span>{{ $number($category['missing_questions']) }} braków</span>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="pjm-sample-card">
                    <h4>Pierwsze pytania bez PJM</h4>
                    <p>Próbka do kontroli importu albo planowania kolejnej paczki.</p>
                    <div class="pjm-mini-list">
                        @forelse ($report['missing_question_sample'] as $question)
                            <a class="pjm-sample-row" href="{{ $questionEditUrl($question['question_id']) }}">
                                <strong>{{ $question['external_id'] }}</strong>
                                <span>kat. {{ $question['category_code'] }}</span>
                            </a>
                        @empty
                            <div class="pjm-sample-row">
                                <strong>Brak braków</strong>
                                <span>coverage kompletne</span>
                            </div>
                        @endforelse
                    </div>
                </article>

                <article class="pjm-sample-card">
                    <h4>Assety do wyjaśnienia</h4>
                    <p>Review, problemy processingu i orphaned assety w jednej próbce.</p>
                    <div class="pjm-mini-list">
                        @forelse (collect($report['review_required_sample'])->merge($report['processing_problem_sample'])->merge($report['orphaned_asset_sample'])->take(8) as $asset)
                            <div class="pjm-sample-row">
                                <strong>{{ $asset['external_id'] }}</strong>
                                <span>{{ $asset['role'] }} / {{ $asset['processing_status'] }}</span>
                            </div>
                        @empty
                            <div class="pjm-sample-row">
                                <strong>Brak problemów</strong>
                                <span>czysto</span>
                            </div>
                        @endforelse
                    </div>
                </article>
            </section>
        </div>
    @endif
</x-filament-panels::page>

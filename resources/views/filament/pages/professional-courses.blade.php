<x-filament-panels::page>
    @php
        $courseCount = $collections->count();
        $availableCount = $collections->where('is_available_to_learners', true)->count();
        $moduleCount = $collections->sum(fn ($collection) => $collection->modules->count());
        $questionCount = $collections->sum(fn ($collection) => $collection->modules->sum('questions_count'));
    @endphp

    @once
        <style>
            .professional-courses {
                --courses-line: #d7dee8;
                --courses-soft: #f8fafc;
                --courses-ink: #111827;
                --courses-muted: #4b5563;
                --courses-blue: #0369a1;
                --courses-green: #047857;
                display: grid;
                gap: 1rem;
            }

            .professional-courses * {
                box-sizing: border-box;
            }

            .professional-courses__notice {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr);
                gap: .75rem;
                align-items: start;
                border-left: 3px solid var(--courses-blue);
                background: #f0f7ff;
                padding: .85rem 1rem;
            }

            .professional-courses__notice-mark {
                display: grid;
                width: 1.65rem;
                height: 1.65rem;
                place-items: center;
                border: 1px solid #b9d9f2;
                border-radius: 50%;
                color: var(--courses-blue);
                font-size: .82rem;
                font-weight: 800;
            }

            .professional-courses__notice strong,
            .professional-courses__notice p {
                display: block;
            }

            .professional-courses__notice strong {
                color: var(--courses-ink);
                font-size: .875rem;
                font-weight: 700;
            }

            .professional-courses__notice p {
                margin: .18rem 0 0;
                color: var(--courses-muted);
                font-size: .8125rem;
                line-height: 1.5;
            }

            .professional-courses__summary {
                display: flex;
                flex-wrap: wrap;
                border: 1px solid var(--courses-line);
                background: #fff;
            }

            .professional-courses__metric {
                display: grid;
                gap: .15rem;
                min-width: 8.5rem;
                padding: .75rem 1rem;
                border-right: 1px solid var(--courses-line);
            }

            .professional-courses__metric:last-child {
                border-right: 0;
            }

            .professional-courses__metric-label {
                color: #6b7280;
                font-size: .6875rem;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .professional-courses__metric-value {
                color: var(--courses-ink);
                font-size: 1.125rem;
                font-weight: 700;
                line-height: 1.15;
            }

            .professional-courses__table {
                overflow: hidden;
                border: 1px solid var(--courses-line);
                background: #fff;
            }

            .professional-courses__table-head,
            .professional-courses__course-row {
                display: grid;
                grid-template-columns: minmax(18rem, 2.4fr) minmax(7rem, .8fr) minmax(9rem, .9fr) minmax(13rem, 1.25fr);
                gap: 1rem;
                align-items: center;
            }

            .professional-courses__table-head {
                border-bottom: 1px solid var(--courses-line);
                background: var(--courses-soft);
                padding: .68rem 1rem;
                color: #6b7280;
                font-size: .6875rem;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .professional-courses__course-row {
                padding: 1rem;
            }

            .professional-courses__course-main {
                min-width: 0;
            }

            .professional-courses__course-name {
                color: var(--courses-ink);
                font-size: .9375rem;
                font-weight: 700;
                line-height: 1.35;
            }

            .professional-courses__course-description,
            .professional-courses__course-code,
            .professional-courses__helper {
                color: var(--courses-muted);
                font-size: .8125rem;
                line-height: 1.45;
            }

            .professional-courses__course-description {
                max-width: 44rem;
                margin: .28rem 0 0;
            }

            .professional-courses__course-code {
                margin-top: .34rem;
                color: #6b7280;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: .6875rem;
            }

            .professional-courses__number {
                color: var(--courses-ink);
                font-size: .9375rem;
                font-weight: 700;
            }

            .professional-courses__number small {
                display: block;
                margin-top: .18rem;
                color: #6b7280;
                font-size: .75rem;
                font-weight: 500;
            }

            .professional-courses__access {
                display: grid;
                gap: .55rem;
                justify-items: start;
            }

            .professional-courses__state {
                display: inline-flex;
                align-items: center;
                gap: .42rem;
                color: var(--courses-muted);
                font-size: .8125rem;
                font-weight: 600;
                line-height: 1.2;
            }

            .professional-courses__state::before {
                width: .5rem;
                height: .5rem;
                border-radius: 50%;
                background: #94a3b8;
                content: '';
            }

            .professional-courses__state--available {
                color: var(--courses-green);
            }

            .professional-courses__state--available::before {
                background: var(--courses-green);
            }

            .professional-courses__modules {
                border-top: 1px solid var(--courses-line);
                background: #fbfcfe;
            }

            .professional-courses__modules summary {
                display: flex;
                cursor: pointer;
                align-items: center;
                justify-content: space-between;
                padding: .78rem 1rem;
                color: #334155;
                font-size: .8125rem;
                font-weight: 700;
                list-style: none;
            }

            .professional-courses__modules summary::-webkit-details-marker {
                display: none;
            }

            .professional-courses__modules summary::after {
                color: #64748b;
                content: '+';
                font-size: 1rem;
                font-weight: 500;
            }

            .professional-courses__modules[open] summary::after {
                content: '-';
            }

            .professional-courses__modules-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0;
                border-top: 1px solid var(--courses-line);
            }

            .professional-courses__module {
                display: grid;
                grid-template-columns: auto minmax(0, 1fr) auto;
                gap: .75rem;
                align-items: center;
                min-width: 0;
                padding: .78rem 1rem;
                border-bottom: 1px solid #e5eaf0;
            }

            .professional-courses__module:nth-child(odd) {
                border-right: 1px solid #e5eaf0;
            }

            .professional-courses__module-code {
                color: var(--courses-blue);
                font-size: .75rem;
                font-weight: 800;
            }

            .professional-courses__module-name {
                overflow: hidden;
                color: var(--courses-ink);
                font-size: .8125rem;
                font-weight: 600;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .professional-courses__module-meta {
                color: #6b7280;
                font-size: .75rem;
                white-space: nowrap;
            }

            .professional-courses__module-action {
                grid-column: 1 / -1;
                padding-top: .1rem;
            }

            .professional-courses__empty {
                border: 1px dashed var(--courses-line);
                background: #fff;
                padding: 2.5rem 1.5rem;
                text-align: center;
            }

            .professional-courses__empty h2 {
                margin: 0;
                color: var(--courses-ink);
                font-size: 1rem;
                font-weight: 700;
            }

            .professional-courses__empty p {
                max-width: 30rem;
                margin: .4rem auto 0;
                color: var(--courses-muted);
                font-size: .875rem;
                line-height: 1.5;
            }

            @media (max-width: 72rem) {
                .professional-courses__table-head {
                    display: none;
                }

                .professional-courses__course-row {
                    grid-template-columns: minmax(0, 1fr) minmax(12rem, .9fr);
                }

                .professional-courses__course-main {
                    grid-column: 1 / -1;
                }
            }

            @media (max-width: 48rem) {
                .professional-courses__metric {
                    min-width: 50%;
                    border-bottom: 1px solid var(--courses-line);
                }

                .professional-courses__metric:nth-child(even) {
                    border-right: 0;
                }

                .professional-courses__course-row {
                    grid-template-columns: 1fr;
                    gap: .85rem;
                }

                .professional-courses__modules-grid {
                    grid-template-columns: 1fr;
                }

                .professional-courses__module:nth-child(odd) {
                    border-right: 0;
                }
            }
        </style>
    @endonce

    <div class="professional-courses">
        <section class="professional-courses__notice" aria-label="Zasada dostępu do kursów zawodowych">
            <span class="professional-courses__notice-mark" aria-hidden="true">i</span>
            <div>
                <strong>Globalny dostęp dla kursantów</strong>
                <p>Udostępnienie kursu działa od razu dla osób z aktywnym dostępem. Wyłączenie ukrywa go z nauki, bez usuwania sesji ani postępu.</p>
            </div>
        </section>

        <section class="professional-courses__summary" aria-label="Podsumowanie kursów zawodowych">
            <div class="professional-courses__metric">
                <span class="professional-courses__metric-label">Kursy</span>
                <strong class="professional-courses__metric-value">{{ $courseCount }}</strong>
            </div>
            <div class="professional-courses__metric">
                <span class="professional-courses__metric-label">Dostępne</span>
                <strong class="professional-courses__metric-value">{{ $availableCount }}</strong>
            </div>
            <div class="professional-courses__metric">
                <span class="professional-courses__metric-label">Moduły</span>
                <strong class="professional-courses__metric-value">{{ $moduleCount }}</strong>
            </div>
            <div class="professional-courses__metric">
                <span class="professional-courses__metric-label">Pytania</span>
                <strong class="professional-courses__metric-value">{{ number_format($questionCount, 0, ',', ' ') }}</strong>
            </div>
        </section>

        @forelse ($collections as $collection)
            <section class="professional-courses__table" aria-labelledby="professional-course-{{ $collection->getKey() }}">
                <div class="professional-courses__table-head" aria-hidden="true">
                    <span>Kurs</span>
                    <span>Kategoria</span>
                    <span>Zakres</span>
                    <span>Dostęp kursantów</span>
                </div>

                <div class="professional-courses__course-row">
                    <div class="professional-courses__course-main">
                        <h2 id="professional-course-{{ $collection->getKey() }}" class="professional-courses__course-name">{{ $collection->name }}</h2>
                        @if ($collection->description)
                            <p class="professional-courses__course-description">{{ $collection->description }}</p>
                        @endif
                        <p class="professional-courses__course-code">{{ $collection->code }}</p>
                    </div>

                    <div class="professional-courses__number">
                        {{ $collection->licenseCategory?->code ?? '-' }}
                        <small>{{ $collection->licenseCategory?->name ?? 'Brak kategorii' }}</small>
                    </div>

                    <div class="professional-courses__number">
                        {{ $collection->modules->count() }} modułów
                        <small>{{ number_format($collection->modules->sum('questions_count'), 0, ',', ' ') }} pytań</small>
                    </div>

                    <div class="professional-courses__access">
                        <span class="professional-courses__state {{ $collection->is_available_to_learners ? 'professional-courses__state--available' : '' }}">
                            {{ $collection->is_available_to_learners ? 'Dostępny' : 'Ukryty' }}
                        </span>
                        <form method="POST" action="{{ route('admin.question-collections.availability.update', $collection) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_available_to_learners" value="{{ $collection->is_available_to_learners ? '0' : '1' }}">
                            <x-filament::button type="submit" color="{{ $collection->is_available_to_learners ? 'gray' : 'success' }}" size="sm">
                                {{ $collection->is_available_to_learners ? 'Wyłącz dostęp' : 'Włącz dostęp' }}
                            </x-filament::button>
                        </form>
                    </div>
                </div>

                <details class="professional-courses__modules">
                    <summary>
                        <span>Moduły kursu ({{ $collection->modules->count() }})</span>
                        <span class="professional-courses__helper">Podgląd pojedynczego modułu</span>
                    </summary>
                    <div class="professional-courses__modules-grid">
                        @forelse ($collection->modules as $module)
                            <div class="professional-courses__module">
                                <span class="professional-courses__module-code">{{ $module->code }}</span>
                                <span class="professional-courses__module-name" title="{{ $module->name }}">{{ $module->name }}</span>
                                <span class="professional-courses__module-meta">{{ $module->questions_count }} pytań</span>
                                <form method="POST" action="{{ route('admin.question-collections.modules.start', [
                                    'questionCollection' => $collection->slug,
                                    'module' => $module->slug,
                                ]) }}" class="professional-courses__module-action">
                                    @csrf
                                    <x-filament::link color="gray" size="sm" tag="button" type="submit">
                                        Otwórz podgląd nauki
                                    </x-filament::link>
                                </form>
                            </div>
                        @empty
                            <p class="p-5 text-sm text-gray-500 dark:text-gray-400">Ten kurs nie ma jeszcze modułów.</p>
                        @endforelse
                    </div>
                </details>
            </section>
        @empty
            <section class="professional-courses__empty">
                <h2>Brak kursów zawodowych</h2>
                <p>Po imporcie kurs pojawi się tutaj wraz z kontrolą dostępu dla kursantów.</p>
            </section>
        @endforelse
    </div>
</x-filament-panels::page>

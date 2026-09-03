<x-filament-panels::page>
    <style>
        .qlo {
            display: grid;
            gap: 1.25rem;
            color: #0f172a;
        }

        .qlo-panel {
            border: 1px solid #dbe3ee;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .qlo-panel-pad {
            padding: 1.25rem;
        }

        .qlo-toolbar {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) minmax(320px, 1.4fr) 180px auto;
            gap: 1rem;
            align-items: end;
        }

        .qlo-field {
            display: grid;
            gap: 0.45rem;
        }

        .qlo-label {
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .qlo-control {
            width: 100%;
            min-height: 2.65rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            font-size: 0.92rem;
            padding: 0.55rem 0.75rem;
            outline: none;
        }

        .qlo-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .qlo-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 2.5rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            cursor: pointer;
            font-size: 0.88rem;
            font-weight: 800;
            padding: 0.55rem 0.85rem;
            text-decoration: none;
            transition: background 0.14s ease, border-color 0.14s ease, color 0.14s ease;
            white-space: nowrap;
        }

        .qlo-btn:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .qlo-btn-primary {
            border-color: #0f172a;
            background: #0f172a;
            color: #fff;
        }

        .qlo-btn-primary:hover {
            background: #1e293b;
            border-color: #1e293b;
        }

        .qlo-btn-warn {
            border-color: #f59e0b;
            background: #fffbeb;
            color: #92400e;
        }

        .qlo-btn-danger {
            border-color: #fca5a5;
            background: #fff1f2;
            color: #be123c;
        }

        .qlo-btn-mini {
            min-height: 2rem;
            border-radius: 8px;
            font-size: 0.78rem;
            padding: 0.35rem 0.55rem;
        }

        .qlo-btn:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .qlo-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .qlo-kpi {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            padding: 0.95rem;
        }

        .qlo-kpi span {
            display: block;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .qlo-kpi strong {
            display: block;
            margin-top: 0.35rem;
            color: #0f172a;
            font-size: 1.5rem;
            line-height: 1;
        }

        .qlo-kpi .qlo-good {
            color: #059669;
        }

        .qlo-kpi .qlo-warn {
            color: #d97706;
        }

        .qlo-kpi .qlo-danger {
            color: #e11d48;
        }

        .qlo-empty {
            display: grid;
            gap: 1rem;
            justify-items: start;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            background: linear-gradient(180deg, #fff, #f8fafc);
            padding: 1.4rem;
        }

        .qlo-empty h2,
        .qlo-section-title {
            margin: 0;
            color: #0f172a;
            font-size: 1.08rem;
            font-weight: 900;
        }

        .qlo-copy {
            max-width: 68rem;
            margin: 0.35rem 0 0;
            color: #475569;
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .qlo-drafts {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            margin-top: 0.9rem;
        }

        .qlo-draft-row {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border: 1px solid #dbe3ee;
            border-radius: 12px;
            background: #f8fafc;
            padding: 0.25rem;
        }

        .qlo-draft-active {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .qlo-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.25rem;
        }

        .qlo-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            justify-content: flex-end;
        }

        .qlo-subbar {
            display: grid;
            grid-template-columns: minmax(220px, 420px) 1fr;
            gap: 1rem;
            align-items: center;
            border-bottom: 1px solid #edf2f7;
            background: #f8fafc;
            padding: 0.85rem 1.25rem;
        }

        .qlo-bulkbar {
            display: grid;
            grid-template-columns: auto auto auto minmax(240px, 1fr) auto auto auto auto;
            gap: 0.55rem;
            align-items: center;
            border-bottom: 1px solid #edf2f7;
            background: #fff;
            padding: 0.85rem 1.25rem;
        }

        .qlo-bulk-count {
            color: #334155;
            font-size: 0.84rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .qlo-anchor {
            min-height: 2.5rem;
            border-radius: 10px;
            font-size: 0.84rem;
        }

        .qlo-search {
            min-height: 2.45rem;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #fff;
            padding: 0.5rem 0.9rem;
            font-size: 0.88rem;
            outline: none;
        }

        .qlo-search:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .qlo-hint {
            margin: 0;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .qlo-table-wrap {
            max-height: 68vh;
            overflow: auto;
        }

        .qlo-table {
            width: 100%;
            min-width: 1340px;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        .qlo-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            padding: 0.75rem;
            text-align: left;
            text-transform: uppercase;
        }

        .qlo-table td {
            border-bottom: 1px solid #edf2f7;
            padding: 0.8rem 0.75rem;
            vertical-align: top;
        }

        .qlo-table tr:hover td {
            background: #f8fafc;
        }

        .qlo-row-stale td {
            background: #fff1f2;
        }

        .qlo-position {
            width: 5.25rem;
            min-height: 2.15rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0.35rem 0.5rem;
            text-align: center;
        }

        .qlo-check {
            width: 1.05rem;
            height: 1.05rem;
            accent-color: #2563eb;
        }

        .qlo-move {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.3rem;
        }

        .qlo-question-cell {
            display: grid;
            grid-template-columns: 12rem minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .qlo-media {
            position: relative;
            display: flex;
            min-height: 7.2rem;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            background: #eef3f9;
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 900;
            text-align: center;
            text-decoration: none;
        }

        .qlo-media img {
            display: block;
            width: 100%;
            height: 7.2rem;
            object-fit: contain;
        }

        .qlo-media:hover {
            border-color: #94a3b8;
        }

        .qlo-media-badge {
            position: absolute;
            right: 0.35rem;
            bottom: 0.35rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.82);
            color: #fff;
            font-size: 0.62rem;
            font-weight: 900;
            padding: 0.16rem 0.45rem;
            text-transform: uppercase;
        }

        .qlo-question-id {
            color: #0f172a;
            font-weight: 900;
        }

        .qlo-question-text {
            max-width: 58rem;
            margin: 0.25rem 0 0;
            color: #334155;
            line-height: 1.5;
        }

        .qlo-muted {
            margin-top: 0.28rem;
            color: #94a3b8;
            font-size: 0.76rem;
        }

        .qlo-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 900;
            padding: 0.22rem 0.55rem;
        }

        .qlo-badge-ok {
            background: #dcfce7;
            color: #047857;
        }

        .qlo-badge-stale {
            background: #ffe4e6;
            color: #be123c;
        }

        .qlo-missing {
            border-color: #fbbf24;
            background: #fffbeb;
        }

        .qlo-missing-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .qlo-missing-card {
            border: 1px solid #fde68a;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.75);
            padding: 0.8rem;
        }

        .qlo-missing-card .qlo-question-cell {
            grid-template-columns: 9.5rem minmax(0, 1fr);
        }

        .qlo-missing-card .qlo-media,
        .qlo-missing-card .qlo-media img {
            min-height: 5.8rem;
            height: 5.8rem;
        }

        @media (max-width: 1100px) {
            .qlo-toolbar,
            .qlo-subbar,
            .qlo-bulkbar {
                grid-template-columns: 1fr;
            }

            .qlo-kpis,
            .qlo-missing-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 720px) {
            .qlo-kpis,
            .qlo-missing-grid {
                grid-template-columns: 1fr;
            }

            .qlo-head {
                align-items: stretch;
                flex-direction: column;
            }

            .qlo-actions {
                justify-content: flex-start;
            }

            .qlo-question-cell,
            .qlo-missing-card .qlo-question-cell {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="qlo">
        <section class="qlo-panel qlo-panel-pad">
            <div class="qlo-toolbar">
                <label class="qlo-field">
                    <span class="qlo-label">Kategoria</span>
                    <select wire:model.live="categoryId" class="qlo-control">
                        @foreach ($overview['categories'] as $category)
                            <option value="{{ $category['id'] }}">{{ $category['code'] }} - {{ $category['name'] }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="qlo-field">
                    <span class="qlo-label">Dział</span>
                    <select wire:model.live="topicId" class="qlo-control">
                        @forelse ($overview['topics'] as $topic)
                            <option value="{{ $topic['id'] }}">
                                {{ $topic['name'] }}
                                @if (($topic['technical_name'] ?? $topic['name']) !== $topic['name'])
                                    · technicznie: {{ $topic['technical_name'] }}
                                @endif
                                ({{ $topic['questions_count'] }})
                            </option>
                        @empty
                            <option value="">Brak działów</option>
                        @endforelse
                    </select>
                </label>

                <label class="qlo-field">
                    <span class="qlo-label">Zakres</span>
                    <select wire:model.live="questionScope" class="qlo-control">
                        @foreach ($scopeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <button type="button" wire:click="createDraft" class="qlo-btn qlo-btn-primary" @disabled(! $overview['selected_topic'])>
                    Utwórz draft
                </button>
            </div>

            <div class="qlo-kpis">
                <div class="qlo-kpi">
                    <span>Pula działu</span>
                    <strong>{{ $overview['current_pool_count'] }}</strong>
                </div>
                <div class="qlo-kpi">
                    <span>Aktywny set</span>
                    <strong>{{ $overview['active_set'] ? 'v'.$overview['active_set']['version'] : 'Brak' }}</strong>
                </div>
                <div class="qlo-kpi">
                    <span>Brakujące</span>
                    <strong class="{{ $overview['missing_count'] > 0 ? 'qlo-warn' : 'qlo-good' }}">{{ $overview['missing_count'] }}</strong>
                </div>
                <div class="qlo-kpi">
                    <span>Stare pozycje</span>
                    <strong class="{{ $overview['stale_count'] > 0 ? 'qlo-danger' : 'qlo-good' }}">{{ $overview['stale_count'] }}</strong>
                </div>
            </div>
        </section>

        @if ($overview['draft_sets'])
            <section class="qlo-panel qlo-panel-pad">
                <h2 class="qlo-section-title">Drafty</h2>
                <p class="qlo-copy">Wybierz draft do pracy. Opublikowanie draftu automatycznie archiwizuje poprzedni aktywny układ dla tego działu i zakresu.</p>

                <div class="qlo-drafts">
                    @foreach ($overview['draft_sets'] as $draft)
                        <div class="qlo-draft-row" wire:key="qlo-draft-{{ $draft['id'] }}">
                            <button
                                type="button"
                                wire:click="selectEditableSet({{ $draft['id'] }})"
                                class="qlo-btn {{ ($overview['editable_set']['id'] ?? null) === $draft['id'] ? 'qlo-draft-active' : '' }}"
                            >
                                Draft v{{ $draft['version'] }} · {{ $draft['items_count'] }} pytań
                            </button>
                            <button
                                type="button"
                                wire:click="deleteDraft({{ $draft['id'] }})"
                                wire:confirm="Usunąć ten draft? Opublikowane układy nie zostaną zmienione."
                                class="qlo-btn qlo-btn-danger qlo-btn-mini"
                            >
                                Usuń
                            </button>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($overview['editable_set'])
            @php
                $editableSet = $overview['editable_set'];
                $isDraft = $editableSet['status'] === 'draft';
            @endphp

            <section class="qlo-panel">
                @php
                    $visibleItemIds = collect($overview['rows'])->pluck('item_id')->filter()->values()->all();
                    $selectedCount = collect($selectedItems ?? [])->filter()->count();
                @endphp

                <div class="qlo-head">
                    <div>
                        <span class="qlo-label">{{ $isDraft ? 'Edytowany draft' : 'Aktywny układ' }}</span>
                        <h2 class="qlo-section-title">Wersja {{ $editableSet['version'] }} · {{ $editableSet['items_count'] }} pytań</h2>
                        <p class="qlo-copy">Możesz używać przycisków przesuwania, wpisać pozycje ręcznie i zapisać normalizację co 10.</p>
                    </div>

                    @if ($isDraft)
                        <div class="qlo-actions">
                            <button type="button" wire:click="appendMissing({{ $editableSet['id'] }})" class="qlo-btn qlo-btn-warn">Dopnij brakujące</button>
                            <button type="button" wire:click="removeStale({{ $editableSet['id'] }})" class="qlo-btn qlo-btn-danger">Usuń stare</button>
                            <button type="button" wire:click="savePositions({{ $editableSet['id'] }})" class="qlo-btn">Zapisz pozycje</button>
                            <button type="button" wire:click="deleteDraft({{ $editableSet['id'] }})" wire:confirm="Usunąć ten draft? Opublikowane układy nie zostaną zmienione." class="qlo-btn qlo-btn-danger">Usuń draft</button>
                            <button type="button" wire:click="publish({{ $editableSet['id'] }})" class="qlo-btn qlo-btn-primary">Opublikuj</button>
                        </div>
                    @endif
                </div>

                <div class="qlo-subbar">
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        class="qlo-search"
                        placeholder="Szukaj po ID, numerze źródłowym albo treści pytania..."
                    >
                    <p class="qlo-hint">Wyszukiwarka filtruje widok, ale zapis pozycji dalej obejmuje cały draft. Przy dużych działach najwygodniej znaleźć pytanie, dać mu numer pozycji albo przesunąć je strzałkami.</p>
                </div>

                @if ($isDraft)
                    <div class="qlo-bulkbar">
                        <span class="qlo-bulk-count">Zaznaczono: {{ $selectedCount }}</span>
                        <button type="button" wire:click="selectVisibleItems(@js($visibleItemIds))" class="qlo-btn qlo-btn-mini" @disabled($visibleItemIds === [])>Zaznacz widoczne</button>
                        <button type="button" wire:click="clearSelectedItems" class="qlo-btn qlo-btn-mini" @disabled($selectedCount < 1)>Wyczyść</button>
                        <select wire:model.live="groupAnchorItemId" class="qlo-control qlo-anchor">
                            <option value="">Wstaw obok pytania...</option>
                            @foreach ($overview['rows'] as $row)
                                @if (empty(($selectedItems ?? [])[$row['item_id']]))
                                    <option value="{{ $row['item_id'] }}">
                                        {{ $row['position'] }} · {{ $row['external_id'] ? 'ID '.$row['external_id'].' · ' : '' }}{{ \Illuminate\Support\Str::limit($row['prompt'], 78) }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <button type="button" wire:click="moveSelectedItems({{ $editableSet['id'] }}, 'before')" class="qlo-btn qlo-btn-mini" @disabled($selectedCount < 1 || blank($groupAnchorItemId))>Przed</button>
                        <button type="button" wire:click="moveSelectedItems({{ $editableSet['id'] }}, 'after')" class="qlo-btn qlo-btn-mini" @disabled($selectedCount < 1 || blank($groupAnchorItemId))>Za</button>
                        <button type="button" wire:click="moveSelectedItems({{ $editableSet['id'] }}, 'top')" class="qlo-btn qlo-btn-mini" @disabled($selectedCount < 1)>Na początek</button>
                        <button type="button" wire:click="moveSelectedItems({{ $editableSet['id'] }}, 'bottom')" class="qlo-btn qlo-btn-mini" @disabled($selectedCount < 1)>Na koniec</button>
                    </div>
                @endif

                <div class="qlo-table-wrap">
                    <table class="qlo-table">
                        <thead>
                            <tr>
                                @if ($isDraft)
                                    <th>Zaznacz</th>
                                @endif
                                <th>Pozycja</th>
                                <th>Przesuń</th>
                                <th>Pytanie</th>
                                <th>Zakres</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($overview['rows'] as $row)
                                <tr wire:key="qlo-row-{{ $editableSet['id'] }}-{{ $row['item_id'] }}" class="{{ $row['state'] === 'stale' ? 'qlo-row-stale' : '' }}">
                                    @if ($isDraft)
                                        <td>
                                            <input
                                                type="checkbox"
                                                wire:model.live="selectedItems.{{ $row['item_id'] }}"
                                                class="qlo-check"
                                                aria-label="Zaznacz pytanie #{{ $row['question_id'] }}"
                                            >
                                        </td>
                                    @endif
                                    <td>
                                        @if ($isDraft)
                                            <input
                                                type="number"
                                                min="1"
                                                wire:key="qlo-position-{{ $editableSet['id'] }}-{{ $row['item_id'] }}"
                                                wire:model="positions.{{ $row['item_id'] }}"
                                                value="{{ $positions[$row['item_id']] ?? $row['position'] }}"
                                                class="qlo-position"
                                            >
                                        @else
                                            <strong>{{ $row['position'] }}</strong>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($isDraft)
                                            <div class="qlo-move">
                                                <button type="button" wire:click="moveItem({{ $editableSet['id'] }}, {{ $row['item_id'] }}, 'top')" class="qlo-btn qlo-btn-mini" title="Przenieś na górę">⇤</button>
                                                <button type="button" wire:click="moveItem({{ $editableSet['id'] }}, {{ $row['item_id'] }}, 'up')" class="qlo-btn qlo-btn-mini" title="Przesuń wyżej">↑</button>
                                                <button type="button" wire:click="moveItem({{ $editableSet['id'] }}, {{ $row['item_id'] }}, 'down')" class="qlo-btn qlo-btn-mini" title="Przesuń niżej">↓</button>
                                                <button type="button" wire:click="moveItem({{ $editableSet['id'] }}, {{ $row['item_id'] }}, 'bottom')" class="qlo-btn qlo-btn-mini" title="Przenieś na dół">⇥</button>
                                            </div>
                                        @else
                                            <span class="qlo-hint">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="qlo-question-cell">
                                            @php($mediaPreview = $row['media_preview'] ?? null)

                                            @if ($mediaPreview && $mediaPreview['thumbnail_url'])
                                                <a href="{{ $mediaPreview['full_url'] ?? $mediaPreview['thumbnail_url'] }}" target="_blank" rel="noopener" class="qlo-media" title="Otwórz podgląd">
                                                    <img src="{{ $mediaPreview['thumbnail_url'] }}" alt="Podgląd pytania #{{ $row['question_id'] }}" loading="lazy">
                                                    @if (($mediaPreview['kind'] ?? null) === 'video')
                                                        <span class="qlo-media-badge">wideo</span>
                                                    @endif
                                                </a>
                                            @else
                                                <div class="qlo-media">Brak grafiki</div>
                                            @endif

                                            <div>
                                                <div class="qlo-question-id">
                                                    {{ $row['external_id'] ? 'ID '.$row['external_id'].' · ' : '' }}#{{ $row['question_id'] }}
                                                </div>
                                                <p class="qlo-question-text">{{ $row['prompt'] }}</p>
                                                <div class="qlo-muted">Trudność: {{ $row['difficulty'] ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $row['scope'] }}</td>
                                    <td>
                                        <span class="qlo-badge {{ $row['state'] === 'stale' ? 'qlo-badge-stale' : 'qlo-badge-ok' }}">
                                            {{ $row['state'] === 'stale' ? 'Stare' : 'OK' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isDraft ? 6 : 5 }}">
                                        <p class="qlo-hint">Brak pytań pasujących do aktualnego wyszukiwania.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            <section class="qlo-empty">
                <div>
                    <h2>Brak draftu lub aktywnej kolejności</h2>
                    <p class="qlo-copy">Dla wybranego działu nie ma jeszcze układu do edycji. Utwórz draft z aktualnej technicznej kolejności, a panel pokaże pełną listę pytań do pracy.</p>
                </div>
                <button type="button" wire:click="createDraft" class="qlo-btn qlo-btn-primary" @disabled(! $overview['selected_topic'])>
                    Utwórz draft z {{ $overview['current_pool_count'] }} pytań
                </button>
            </section>
        @endif

        @if ($overview['editable_set'] && $overview['missing_questions'])
            <section class="qlo-panel qlo-panel-pad qlo-missing">
                <h2 class="qlo-section-title">Pytania bez pozycji</h2>
                <p class="qlo-copy">
                    @if (($overview['editable_set']['status'] ?? null) === 'draft')
                        Te pytania są w aktualnej puli działu, ale nie ma ich w edytowanym układzie. Kliknij “Dopnij brakujące”, żeby trafiły na koniec draftu.
                    @else
                        Te pytania są w aktualnej puli działu, ale nie ma ich w opublikowanym układzie. Utwórz draft, żeby bezpiecznie dopiąć je do kolejności.
                    @endif
                </p>

                <div class="qlo-missing-grid">
                    @foreach (array_slice($overview['missing_questions'], 0, 12) as $question)
                        <div class="qlo-missing-card">
                            <div class="qlo-question-cell">
                                @php($mediaPreview = $question['media_preview'] ?? null)

                                @if ($mediaPreview && $mediaPreview['thumbnail_url'])
                                    <a href="{{ $mediaPreview['full_url'] ?? $mediaPreview['thumbnail_url'] }}" target="_blank" rel="noopener" class="qlo-media" title="Otwórz podgląd">
                                        <img src="{{ $mediaPreview['thumbnail_url'] }}" alt="Podgląd pytania #{{ $question['question_id'] }}" loading="lazy">
                                        @if (($mediaPreview['kind'] ?? null) === 'video')
                                            <span class="qlo-media-badge">wideo</span>
                                        @endif
                                    </a>
                                @else
                                    <div class="qlo-media">Brak grafiki</div>
                                @endif

                                <div>
                                    <div class="qlo-question-id">{{ $question['external_id'] ? 'ID '.$question['external_id'].' · ' : '' }}#{{ $question['question_id'] }}</div>
                                    <p class="qlo-question-text">{{ $question['prompt'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (count($overview['missing_questions']) > 12)
                    <p class="qlo-hint">Pokazano 12 z {{ count($overview['missing_questions']) }} brakujących pytań.</p>
                @endif
            </section>
        @endif
    </div>
</x-filament-panels::page>

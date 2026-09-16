<x-filament-panels::page>
    <style>
        .nhc { display: grid; gap: 1.25rem; color: #0f172a; }
        .nhc-toolbar, .nhc-card { border: 1px solid #dbe3ee; background: #fff; border-radius: 14px; box-shadow: 0 14px 34px rgba(15, 23, 42, .05); }
        .nhc-toolbar { padding: 1rem; display: flex; gap: 1rem; align-items: end; flex-wrap: wrap; }
        .nhc-field { display: grid; gap: .4rem; min-width: 220px; }
        .nhc-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; color: #64748b; }
        .nhc-control { min-height: 2.55rem; border: 1px solid #cbd5e1; border-radius: 10px; padding: .55rem .7rem; background: #fff; color: #0f172a; }
        .nhc-btn { display: inline-flex; min-height: 2.45rem; align-items: center; justify-content: center; border: 1px solid #cbd5e1; border-radius: 10px; padding: .5rem .8rem; font-weight: 800; font-size: .86rem; background: #fff; cursor: pointer; text-decoration: none; color: #0f172a; }
        .nhc-btn:hover { background: #f8fafc; }
        .nhc-btn-primary { background: #0f172a; color: #fff; border-color: #0f172a; }
        .nhc-btn-danger { color: #991b1b; border-color: #fecaca; background: #fffafa; }
        .nhc-btn[disabled] { opacity: .45; cursor: not-allowed; }
        .nhc-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); }
        .nhc-card { overflow: hidden; }
        .nhc-card-head { padding: 1rem 1.1rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; gap: 1rem; align-items: start; }
        .nhc-card-body { padding: 1rem 1.1rem; display: grid; gap: .9rem; }
        .nhc-section { color: #64748b; font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; }
        .nhc-title { margin-top: .2rem; font-weight: 800; font-size: 1rem; }
        .nhc-badge { border: 1px solid #dbe3ee; border-radius: 999px; padding: .25rem .55rem; font-size: .72rem; font-weight: 800; color: #475569; white-space: nowrap; }
        .nhc-summary { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; padding: .7rem .8rem; display: grid; gap: .2rem; }
        .nhc-summary strong { font-size: .9rem; }
        .nhc-summary span { font-size: .78rem; color: #64748b; }
        .nhc-fallback { border-left: 3px solid #efc54f; background: #fffdf3; padding: .7rem .8rem; border-radius: 8px; }
        .nhc-warning { border: 1px solid #f5d98a; background: #fff8dd; color: #713f12; border-radius: 10px; padding: .65rem .75rem; font-size: .82rem; font-weight: 700; }
        .nhc-danger { border-color: #fecaca; background: #fff1f2; color: #991b1b; }
        .nhc-search-results { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
        .nhc-search-result { width: 100%; text-align: left; display: block; padding: .65rem .75rem; border: 0; border-bottom: 1px solid #e2e8f0; background: #fff; cursor: pointer; }
        .nhc-search-result:last-child { border-bottom: 0; }
        .nhc-search-result:hover { background: #f8fafc; }
        .nhc-search-result strong { display: block; font-size: .85rem; }
        .nhc-search-result span { display: block; margin-top: .15rem; font-size: .75rem; color: #64748b; }
        .nhc-dates { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
        .nhc-actions { display: flex; flex-wrap: wrap; gap: .55rem; padding-top: .1rem; }
        .nhc-help { font-size: .8rem; line-height: 1.5; color: #64748b; }
        @media (max-width: 760px) {
            .nhc-grid { grid-template-columns: 1fr; }
            .nhc-dates { grid-template-columns: 1fr; }
        }
    </style>

    <div class="nhc">
        <section class="nhc-toolbar">
            <label class="nhc-field">
                <span class="nhc-label">Stan strony w czasie</span>
                <input type="datetime-local" wire:model.blur="previewAt" class="nhc-control">
            </label>

            <div>
                <div class="nhc-label">Strefa czasu</div>
                <div style="margin-top:.6rem;font-weight:800">{{ config('app.timezone', 'Europe/Warsaw') }}</div>
            </div>

            <button type="button" wire:click="refreshComposer" class="nhc-btn">Odśwież dane</button>

            <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer" class="nhc-btn nhc-btn-primary">
                Podgląd /aktualnosci
            </a>

            <p class="nhc-help" style="flex-basis:100%;margin:0">
                Widok edytuje wyłącznie stałe sloty zdefiniowane w kodzie. Podgląd dotyczy {{ $previewAtLabel }}.
                Fallback jest liczony tym samym composition service, ale bez ręcznych placementów.
            </p>
        </section>

        <div class="nhc-grid">
            @foreach ($rows as $row)
                <section class="nhc-card" wire:key="home-slot-{{ $row['id'] }}">
                    <header class="nhc-card-head">
                        <div>
                            <div class="nhc-section">{{ $row['section'] }}</div>
                            <div class="nhc-title">{{ $row['label'] }}</div>
                        </div>
                        <span class="nhc-badge">{{ $row['slot_key'] }} · {{ $row['position'] }}</span>
                    </header>

                    <div class="nhc-card-body">
                        @if ($row['selected_article'])
                            <div class="nhc-summary">
                                <strong>Ręcznie: {{ $row['selected_article']->title }}</strong>
                                <span>
                                    #{{ $row['selected_article']->id }}
                                    · {{ $row['selected_article']->workflow_status?->value ?? $row['selected_article']->workflow_status }}
                                    @if ($row['selected_article']->category)
                                        · {{ $row['selected_article']->category->name }}
                                    @endif
                                </span>
                            </div>
                        @else
                            <div class="nhc-summary">
                                <strong>Brak ręcznego przypisania</strong>
                                <span>Ten slot korzysta z fallbacku.</span>
                            </div>
                        @endif

                        <div class="nhc-fallback">
                            <div class="nhc-label">Fallback bez manual placementu</div>
                            @if ($row['fallback_article'])
                                <div style="margin-top:.35rem;font-weight:800">{{ $row['fallback_article']->title }}</div>
                                <div class="nhc-help">#{{ $row['fallback_article']->id }} · {{ $row['fallback_article']->workflow_status?->value ?? $row['fallback_article']->workflow_status }}</div>
                            @else
                                <div class="nhc-help" style="margin-top:.35rem">Brak kwalifikowanego fallbacku dla tego slotu i czasu.</div>
                            @endif
                        </div>

                        @if ($row['eligible'] === false)
                            <div class="nhc-warning nhc-danger">
                                Wybrany artykuł nie jest kwalifikowany do dystrybucji w ustawionym czasie. Zapis jest zablokowany.
                            </div>
                        @endif

                        @if ($row['duplicate'])
                            <div class="nhc-warning">
                                Ten artykuł jest wybrany także w innym card slocie. Finalny composition service go zdeduplikuje, ale układ wymaga świadomego sprawdzenia.
                            </div>
                        @endif

                        <label class="nhc-field">
                            <span class="nhc-label">Wyszukaj artykuł</span>
                            <input
                                type="search"
                                wire:model.live.debounce.350ms="searches.{{ $row['id'] }}"
                                class="nhc-control"
                                placeholder="Tytuł lub slug…"
                                autocomplete="off"
                            >
                        </label>

                        @if ($row['search_results'])
                            <div class="nhc-search-results">
                                @foreach ($row['search_results'] as $result)
                                    <button
                                        type="button"
                                        class="nhc-search-result"
                                        wire:click="chooseArticle('{{ $row['id'] }}', {{ $result['id'] }})"
                                    >
                                        <strong>{{ $result['title'] }}</strong>
                                        <span>
                                            #{{ $result['id'] }} · {{ $result['status'] }}
                                            @if ($result['category']) · {{ $result['category'] }} @endif
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="nhc-dates">
                            <label class="nhc-field">
                                <span class="nhc-label">Aktywny od</span>
                                <input type="datetime-local" wire:model="slots.{{ $row['id'] }}.starts_at" class="nhc-control">
                            </label>
                            <label class="nhc-field">
                                <span class="nhc-label">Aktywny do</span>
                                <input type="datetime-local" wire:model="slots.{{ $row['id'] }}.ends_at" class="nhc-control">
                            </label>
                        </div>

                        <div class="nhc-actions">
                            <button
                                type="button"
                                wire:click="saveSlot('{{ $row['id'] }}')"
                                class="nhc-btn nhc-btn-primary"
                                @disabled(! $row['selected_article'] || $row['eligible'] === false)
                            >
                                {{ $row['state']['placement_id'] ? 'Zapisz placement' : 'Przypisz do slotu' }}
                            </button>

                            @if ($row['selected_article'])
                                <button type="button" wire:click="clearSelection('{{ $row['id'] }}')" class="nhc-btn">
                                    Wyczyść wybór
                                </button>
                            @endif

                            @if ($row['state']['placement_id'])
                                <button
                                    type="button"
                                    wire:click="removePlacement('{{ $row['id'] }}')"
                                    wire:confirm="Usunąć ręczne przypisanie i wrócić do fallbacku?"
                                    class="nhc-btn nhc-btn-danger"
                                >
                                    Usuń ręczne przypisanie
                                </button>
                            @endif
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>

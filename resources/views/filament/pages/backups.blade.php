<x-filament-panels::page>
    @php
        $dayLabels = [
            0 => 'Niedziela',
            1 => 'Poniedziałek',
            2 => 'Wtorek',
            3 => 'Środa',
            4 => 'Czwartek',
            5 => 'Piątek',
            6 => 'Sobota',
        ];

        $settings = $settingsPanel['settings'] ?? $settingsData ?? [];
        $diskLabel = ($settings['disk'] ?? 'backup_local') === 'r2' ? 'Cloudflare R2' : 'Lokalny dysk';
        $frequencyLabel = ($settings['schedule']['frequency'] ?? 'weekly') === 'daily' ? 'Codziennie' : 'Co tydzień';
        $dayLabel = $dayLabels[(int) ($settings['schedule']['day_of_week'] ?? 0)] ?? 'Niedziela';
        $scheduleLabel = ($settings['schedule']['frequency'] ?? 'weekly') === 'daily'
            ? (($settings['schedule']['at'] ?? '02:15').' każdego dnia')
            : ($dayLabel.' · '.($settings['schedule']['at'] ?? '02:15'));
        $retentionLabel = sprintf(
            'D:%s · T:%s · M:%s',
            $settings['retention']['daily'] ?? 7,
            $settings['retention']['weekly'] ?? 4,
            $settings['retention']['monthly'] ?? 3,
        );
        $secretsReady = (bool) ($settingsPanel['secrets_ready'] ?? false);
    @endphp

    <div class="adm-monitor">
        <section class="adm-monitor__panel adm-backup-config">
            <div class="adm-monitor__section-header">
                <div>
                    <h3 class="adm-monitor__section-title">Konfiguracja backupów</h3>
                    <p class="adm-monitor__section-copy">Ten blok steruje miejscem zapisu kopii, harmonogramem i retencją. Panel trzyma ustawienia operacyjne, a sekrety R2 nadal zostają poza nim w `.env`.</p>
                </div>
                <span class="adm-monitor__status-badge adm-monitor__status-badge--{{ ($settingsPanel['has_overrides'] ?? false) ? 'success' : 'warning' }}">
                    {{ $settingsPanel['source_label'] ?? 'Konfiguracja' }}
                </span>
            </div>

            <div class="adm-backup-config__summary">
                <article class="adm-backup-config__summary-card">
                    <span class="adm-backup-config__summary-label">Tryb aktywny</span>
                    <strong class="adm-backup-config__summary-value">{{ $diskLabel }}</strong>
                    <p class="adm-backup-config__summary-copy">{{ ($settings['disk'] ?? 'backup_local') === 'r2' ? 'Kopie trafiają do zewnętrznego storage zgodnego z S3.' : 'Kopie zostają na lokalnym dysku serwera lub środowiska dev.' }}</p>
                </article>
                <article class="adm-backup-config__summary-card">
                    <span class="adm-backup-config__summary-label">Harmonogram</span>
                    <strong class="adm-backup-config__summary-value">{{ $frequencyLabel }}</strong>
                    <p class="adm-backup-config__summary-copy">{{ $scheduleLabel }}</p>
                </article>
                <article class="adm-backup-config__summary-card">
                    <span class="adm-backup-config__summary-label">Retencja</span>
                    <strong class="adm-backup-config__summary-value">{{ $retentionLabel }}</strong>
                    <p class="adm-backup-config__summary-copy">Liczba checkpointów daily, weekly i monthly utrzymywanych przez system.</p>
                </article>
                <article class="adm-backup-config__summary-card">
                    <span class="adm-backup-config__summary-label">Sekrety R2</span>
                    <strong class="adm-backup-config__summary-value">{{ $secretsReady ? 'Gotowe' : 'Poza panelem' }}</strong>
                    <p class="adm-backup-config__summary-copy">{{ $secretsReady ? 'Klucze środowiskowe są dostępne i panel może testować połączenie z R2.' : 'Klucze nadal wchodzą z `.env`, więc panel nie pokazuje ich jawnie.' }}</p>
                </article>
            </div>

            <div class="adm-backup-config__grid">
                <div class="adm-backup-config__main">
                    <form wire:submit="saveSettings" class="adm-backup-config__form">
                        <section class="adm-backup-config__group">
                            <div class="adm-backup-config__group-head">
                                <div>
                                    <h4>Storage backupów</h4>
                                    <p>Wybierz, czy kopie mają zostać lokalnie, czy wychodzić do Cloudflare R2.</p>
                                </div>
                            </div>

                            <div class="adm-backup-config__fields adm-backup-config__fields--2">
                                <label class="adm-backup-config__field">
                                    <span class="adm-backup-config__field-label">Tryb backupu</span>
                                    <select wire:model.live="settingsData.disk" class="adm-backup-config__control">
                                        <option value="backup_local">Lokalny dysk</option>
                                        <option value="r2">Cloudflare R2</option>
                                    </select>
                                    <small class="adm-backup-config__field-help">Lokalny tryb jest dobry do developmentu. R2 to tryb docelowy dla produkcji.</small>
                                    @error('settingsData.disk')
                                        <span class="adm-backup-config__error">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="adm-backup-config__field">
                                    <span class="adm-backup-config__field-label">Częstotliwość</span>
                                    <select wire:model.live="settingsData.schedule.frequency" class="adm-backup-config__control">
                                        <option value="weekly">Co tydzień</option>
                                        <option value="daily">Codziennie</option>
                                    </select>
                                    <small class="adm-backup-config__field-help">To jest rytm automatycznego backupu. Ręczne kopie nadal możesz wymusić z górnego przycisku.</small>
                                    @error('settingsData.schedule.frequency')
                                        <span class="adm-backup-config__error">{{ $message }}</span>
                                    @enderror
                                </label>
                            </div>
                        </section>

                        @if (($settingsData['disk'] ?? 'backup_local') === 'r2')
                            <section class="adm-backup-config__group">
                                <div class="adm-backup-config__group-head">
                                    <div>
                                        <h4>Cloudflare R2</h4>
                                        <p>Tu podajesz bucket i endpoint. Same sekrety dalej zostają po stronie środowiska.</p>
                                    </div>
                                    <span class="adm-monitor__alert-chip adm-monitor__alert-chip--{{ $secretsReady ? 'success' : 'warning' }}">
                                        {{ $secretsReady ? 'sekrety gotowe' : 'sprawdź .env' }}
                                    </span>
                                </div>

                                <div class="adm-backup-config__fields adm-backup-config__fields--2">
                                    <label class="adm-backup-config__field">
                                        <span class="adm-backup-config__field-label">Bucket R2</span>
                                        <input type="text" wire:model.defer="settingsData.r2.bucket" class="adm-backup-config__control" placeholder="prawkobit-db-backups">
                                        @error('settingsData.r2.bucket')
                                            <span class="adm-backup-config__error">{{ $message }}</span>
                                        @enderror
                                    </label>

                                    <label class="adm-backup-config__field">
                                        <span class="adm-backup-config__field-label">Endpoint R2</span>
                                        <input type="text" wire:model.defer="settingsData.r2.endpoint" class="adm-backup-config__control" placeholder="https://&lt;accountid&gt;.r2.cloudflarestorage.com">
                                        @error('settingsData.r2.endpoint')
                                            <span class="adm-backup-config__error">{{ $message }}</span>
                                        @enderror
                                    </label>

                                    <label class="adm-backup-config__field">
                                        <span class="adm-backup-config__field-label">Region</span>
                                        <input type="text" wire:model.defer="settingsData.r2.region" class="adm-backup-config__control" placeholder="auto">
                                        @error('settingsData.r2.region')
                                            <span class="adm-backup-config__error">{{ $message }}</span>
                                        @enderror
                                    </label>

                                    <label class="adm-backup-config__field">
                                        <span class="adm-backup-config__field-label">Public URL</span>
                                        <input type="text" wire:model.defer="settingsData.r2.url" class="adm-backup-config__control" placeholder="opcjonalnie">
                                        <small class="adm-backup-config__field-help">Opcjonalny URL, jeśli chcesz wystawiać backupy przez własną domenę lub CDN.</small>
                                        @error('settingsData.r2.url')
                                            <span class="adm-backup-config__error">{{ $message }}</span>
                                        @enderror
                                    </label>
                                </div>

                                <label class="adm-backup-config__checkbox">
                                    <input type="checkbox" wire:model.defer="settingsData.r2.use_path_style_endpoint" class="mt-0.5 rounded border-slate-300 text-sky-600 shadow-sm">
                                    <span>
                                        <strong>Path-style endpoint</strong>
                                        <small>Dla Cloudflare R2 zwykle najlepiej zostawić to ustawienie włączone.</small>
                                    </span>
                                </label>
                            </section>
                        @endif

                        <section class="adm-backup-config__group">
                            <div class="adm-backup-config__group-head">
                                <div>
                                    <h4>Harmonogram wykonania</h4>
                                    <p>To jest dokładny termin, o której scheduler ma uruchamiać backup.</p>
                                </div>
                            </div>

                            <div class="adm-backup-config__fields adm-backup-config__fields--3">
                                <label class="adm-backup-config__field">
                                    <span class="adm-backup-config__field-label">Godzina</span>
                                    <input type="text" wire:model.defer="settingsData.schedule.at" class="adm-backup-config__control" placeholder="02:15">
                                    @error('settingsData.schedule.at')
                                        <span class="adm-backup-config__error">{{ $message }}</span>
                                    @enderror
                                </label>

                                @if (($settingsData['schedule']['frequency'] ?? 'weekly') === 'weekly')
                                    <label class="adm-backup-config__field">
                                        <span class="adm-backup-config__field-label">Dzień tygodnia</span>
                                        <select wire:model.defer="settingsData.schedule.day_of_week" class="adm-backup-config__control">
                                            @foreach ($dayLabels as $dayValue => $dayLabel)
                                                <option value="{{ $dayValue }}">{{ $dayLabel }}</option>
                                            @endforeach
                                        </select>
                                        @error('settingsData.schedule.day_of_week')
                                            <span class="adm-backup-config__error">{{ $message }}</span>
                                        @enderror
                                    </label>
                                @else
                                    <div class="adm-backup-config__callout">
                                        <strong>Tryb daily</strong>
                                        <p>Backup wykona się codziennie o ustawionej godzinie, bez dodatkowego wyboru dnia tygodnia.</p>
                                    </div>
                                @endif
                            </div>
                        </section>

                        <section class="adm-backup-config__group">
                            <div class="adm-backup-config__group-head">
                                <div>
                                    <h4>Retencja kopii</h4>
                                    <p>Ustal, ile backupów z poszczególnych okien czasowych system ma utrzymywać na dysku.</p>
                                </div>
                            </div>

                            <div class="adm-backup-config__fields adm-backup-config__fields--3">
                                <label class="adm-backup-config__field">
                                    <span class="adm-backup-config__field-label">Retencja daily</span>
                                    <input type="number" min="0" wire:model.defer="settingsData.retention.daily" class="adm-backup-config__control">
                                    <small class="adm-backup-config__field-help">Świeże kopie dzienne.</small>
                                    @error('settingsData.retention.daily')
                                        <span class="adm-backup-config__error">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="adm-backup-config__field">
                                    <span class="adm-backup-config__field-label">Retencja weekly</span>
                                    <input type="number" min="0" wire:model.defer="settingsData.retention.weekly" class="adm-backup-config__control">
                                    <small class="adm-backup-config__field-help">Checkpointy tygodniowe.</small>
                                    @error('settingsData.retention.weekly')
                                        <span class="adm-backup-config__error">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="adm-backup-config__field">
                                    <span class="adm-backup-config__field-label">Retencja monthly</span>
                                    <input type="number" min="0" wire:model.defer="settingsData.retention.monthly" class="adm-backup-config__control">
                                    <small class="adm-backup-config__field-help">Rzadsze checkpointy długiego okresu.</small>
                                    @error('settingsData.retention.monthly')
                                        <span class="adm-backup-config__error">{{ $message }}</span>
                                    @enderror
                                </label>
                            </div>
                        </section>

                        <div class="adm-backup-config__footer">
                            <div class="adm-backup-config__footer-copy">
                                <strong>Zmiany działają od razu</strong>
                                <p>Po zapisie ten sam tryb backupów zaczyna sterować panelem, schedulerem i monitoringiem. Sekrety R2 nadal zostają na poziomie środowiska.</p>
                            </div>
                            <div class="adm-backup-config__actions">
                                <x-filament::button type="submit" icon="heroicon-o-check">
                                    Zapisz ustawienia
                                </x-filament::button>

                                <x-filament::button type="button" color="gray" icon="heroicon-o-bolt" wire:click="testStorageConnection">
                                    Testuj połączenie
                                </x-filament::button>

                                <x-filament::button type="button" color="danger" icon="heroicon-o-arrow-uturn-left" wire:click="resetSettings" onclick="return confirm('Przywrócić domyślną konfigurację backupów?')">
                                    Przywróć domyślne
                                </x-filament::button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="adm-backup-config__aside">
                    <div class="adm-backup-config__aside-stack">
                        <article class="adm-monitor__risk adm-monitor__risk--{{ ($settingsPanel['has_overrides'] ?? false) ? 'success' : 'warning' }}">
                            <div class="adm-monitor__risk-head">
                                <h4>{{ $settingsPanel['source_label'] ?? 'Konfiguracja' }}</h4>
                                <span class="adm-monitor__alert-chip adm-monitor__alert-chip--{{ ($settingsPanel['has_overrides'] ?? false) ? 'success' : 'warning' }}">
                                    {{ ($settingsPanel['has_overrides'] ?? false) ? 'aktywne' : 'domyślne' }}
                                </span>
                            </div>
                            <p>{{ $settingsPanel['source_copy'] ?? '' }}</p>
                        </article>

                        @foreach (($settingsPanel['secrets'] ?? []) as $secret)
                            <article class="adm-monitor__quality-card">
                                <div class="adm-monitor__quality-head">
                                    <div>
                                        <h4>{{ $secret['label'] }}</h4>
                                        <p>{{ $secret['value'] }}</p>
                                    </div>
                                    <span class="adm-monitor__alert-chip adm-monitor__alert-chip--{{ $secret['configured'] ? 'success' : 'danger' }}">
                                        {{ $secret['configured'] ? 'gotowe' : 'brak' }}
                                    </span>
                                </div>
                            </article>
                        @endforeach

                        @if ($connectionProbe)
                            <article class="adm-monitor__risk adm-monitor__risk--{{ $connectionProbe['tone'] }}">
                                <div class="adm-monitor__risk-head">
                                    <h4>{{ $connectionProbe['headline'] }}</h4>
                                    <span class="adm-monitor__alert-chip adm-monitor__alert-chip--{{ $connectionProbe['tone'] }}">
                                        {{ $connectionProbe['tone'] === 'success' ? 'ok' : 'błąd' }}
                                    </span>
                                </div>
                                <p>{{ $connectionProbe['message'] }}</p>
                            </article>
                        @endif

                        @foreach (($settingsPanel['notes'] ?? []) as $note)
                            <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                                {{ $note }}
                            </div>
                        @endforeach
                    </div>
                </aside>
            </div>
        </section>

        @if ($backupError)
            <section class="adm-monitor__panel adm-monitor__hero">
                <div class="adm-monitor__hero-main">
                    <div class="adm-monitor__eyebrow">
                        <span class="adm-monitor__status-dot adm-monitor__status-dot--danger"></span>
                        <span>Stan backupów</span>
                    </div>
                    <div class="adm-monitor__hero-header">
                        <div>
                            <h2 class="adm-monitor__hero-title">{{ $backupError['headline'] }}</h2>
                            <p class="adm-monitor__hero-copy">{{ $backupError['message'] }}</p>
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
                        <p>Zweryfikuj dysk backupów, konfigurację retencji, manifesty i komendę `ops:list-db-backups`. Ta zakładka nie powinna zwracać błędu 500, ale backupy wymagają reakcji.</p>
                        <span class="adm-monitor__risk-recommendation">Szczegóły techniczne: {{ $backupError['details'] ?: 'brak komunikatu wyjątku' }}</span>
                    </article>
                </div>
            </section>
        @else
        @php
            $statusTone = $backups['status']['tone'];
            $statusLabel = match ($statusTone) {
                'danger' => 'Wymaga interwencji',
                'warning' => 'Do sprawdzenia',
                'success' => 'Stabilny',
                default => 'Neutralny',
            };
        @endphp
            <section class="adm-monitor__panel adm-monitor__hero">
                <div class="adm-monitor__hero-main">
                    <div class="adm-monitor__eyebrow">
                        <span class="adm-monitor__status-dot adm-monitor__status-dot--{{ $statusTone }}"></span>
                        <span>Stan backupów</span>
                    </div>
                    <div class="adm-monitor__hero-header">
                        <div>
                            <h2 class="adm-monitor__hero-title">{{ $backups['status']['headline'] }}</h2>
                            <p class="adm-monitor__hero-copy">{{ $backups['status']['summary'] }}</p>
                        </div>
                        <div class="adm-monitor__status-badge adm-monitor__status-badge--{{ $statusTone }}">{{ $statusLabel }}</div>
                    </div>
                </div>

                <div class="adm-monitor__meta-grid">
                    @foreach ($backups['meta'] as $item)
                        <div class="adm-monitor__meta-item">
                            <span class="adm-monitor__meta-label">{{ $item['label'] }}</span>
                            <span class="adm-monitor__meta-value">{{ $item['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="adm-monitor__panel">
                <div class="adm-monitor__section-header">
                    <div>
                        <h3 class="adm-monitor__section-title">Tryb backupów</h3>
                        <p class="adm-monitor__section-copy">{{ $backups['mode']['summary'] }}</p>
                    </div>
                    <span class="adm-monitor__status-badge adm-monitor__status-badge--{{ $backups['mode']['tone'] }}">{{ $backups['mode']['label'] }}</span>
                </div>

                <div class="adm-monitor__stack adm-monitor__stack--tight">
                    <article class="adm-monitor__risk adm-monitor__risk--{{ $backups['mode']['tone'] }}">
                        <div class="adm-monitor__risk-head">
                            <h4>{{ $backups['mode']['headline'] }}</h4>
                            <span class="adm-monitor__alert-chip adm-monitor__alert-chip--{{ $backups['mode']['tone'] }}">{{ $backups['mode']['label'] }}</span>
                        </div>
                    </article>
                </div>

                <div class="adm-monitor__meta-grid">
                    @foreach ($backups['mode']['details'] as $item)
                        <div class="adm-monitor__meta-item">
                            <span class="adm-monitor__meta-label">{{ $item['label'] }}</span>
                            <span class="adm-monitor__meta-value">{{ $item['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="adm-monitor__analysis-grid">
                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Ostatnia kopia</h3>
                            <p class="adm-monitor__section-copy">Najważniejszy rekord odzyskiwania: kiedy powstał, ile waży i gdzie leży.</p>
                        </div>
                        <span class="adm-monitor__job-chip adm-monitor__job-chip--{{ $backups['latest']['tone'] }}">{{ $backups['latest']['age'] }}</span>
                    </div>

                    <div class="adm-monitor__stack adm-monitor__stack--tight">
                        <div class="adm-monitor__metric-row">
                            <div>
                                <strong>Utworzono</strong>
                                <span>Data ostatniej kopii widocznej dla panelu.</span>
                            </div>
                            <strong>{{ $backups['latest']['created_at'] }}</strong>
                        </div>
                        <div class="adm-monitor__metric-row">
                            <div>
                                <strong>Rozmiar</strong>
                                <span>Waga skompresowanego artefaktu backupu.</span>
                            </div>
                            <strong>{{ $backups['latest']['bytes'] }}</strong>
                        </div>
                        <div class="adm-monitor__metric-row">
                            <div>
                                <strong>Połączenie / driver</strong>
                                <span>Baza źródłowa snapshotu.</span>
                            </div>
                            <strong>{{ $backups['latest']['connection'] }} · {{ $backups['latest']['driver'] }}</strong>
                        </div>
                        <div class="adm-monitor__metric-row">
                            <div>
                                <strong>Etykieta</strong>
                                <span>Dodatkowy opis nadany przy tworzeniu kopii.</span>
                            </div>
                            <strong>{{ $backups['latest']['label'] }}</strong>
                        </div>
                    </div>

                    <div class="adm-monitor__database">
                        <div>
                            <span class="adm-monitor__meta-label">Ścieżka backupu</span>
                            <p class="adm-monitor__database-path">{{ $backups['latest']['path'] }}</p>
                        </div>
                        <div class="adm-monitor__database-meta">
                            <span class="adm-monitor__meta-label">Manifest</span>
                            <strong>{{ $backups['latest']['manifest'] }}</strong>
                        </div>
                    </div>

                    @if (filled($backups['latest']['reference'] ?? null))
                        @php
                            $latestDeleteAction = "deleteBackup('".str_replace("'", "\\'", $backups['latest']['reference'])."')";
                        @endphp
                        <div class="mt-4 flex flex-wrap gap-3">
                            <x-filament::button
                                tag="a"
                                size="sm"
                                :href="route('admin.backups.download', ['reference' => $backups['latest']['reference']])"
                                icon="heroicon-o-arrow-down-tray"
                            >
                                Pobierz kopię
                            </x-filament::button>

                            <button
                                type="button"
                                class="fi-color fi-color-danger fi-bg-color-600 hover:fi-bg-color-500 dark:fi-bg-color-600 dark:hover:fi-bg-color-500 fi-text-color-0 hover:fi-text-color-0 dark:fi-text-color-0 dark:hover:fi-text-color-0 fi-btn fi-size-sm"
                                wire:click="{{ $latestDeleteAction }}"
                                onclick="return confirm('Usunąć tę kopię backupu?')"
                            >
                                <svg class="fi-icon fi-size-sm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                <span>Usuń kopię</span>
                            </button>
                        </div>
                    @endif
                </section>

                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Ryzyka i uwagi</h3>
                            <p class="adm-monitor__section-copy">To, co warto sprawdzić zanim backup stanie się problemem odzyskiwania.</p>
                        </div>
                    </div>

                    <div class="adm-monitor__stack">
                        @foreach ($backups['issues'] as $item)
                            <article class="adm-monitor__risk adm-monitor__risk--{{ $item['tone'] }}">
                                <div class="adm-monitor__risk-head">
                                    <h4>{{ $item['title'] }}</h4>
                                    <span class="adm-monitor__alert-chip adm-monitor__alert-chip--{{ $item['tone'] }}">{{ $item['tone'] === 'success' ? 'ok' : ($item['tone'] === 'danger' ? 'pilne' : ($item['tone'] === 'warning' ? 'uwaga' : 'info')) }}</span>
                                </div>
                                <p>{{ $item['details'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="adm-monitor__operations-grid">
                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Retencja i harmonogram</h3>
                            <p class="adm-monitor__section-copy">Tygodniowy model backupów i sposób, w jaki stare kopie są zwijane do rzadszych checkpointów.</p>
                        </div>
                    </div>

                    <div class="adm-monitor__stack">
                        @foreach ($backups['retention'] as $item)
                            <article class="adm-monitor__retention-row">
                                <div class="adm-monitor__retention-head">
                                    <div>
                                        <h4>{{ $item['label'] }}</h4>
                                        <p>{{ $item['details'] }}</p>
                                    </div>
                                    <span class="adm-monitor__retention-chip">{{ $item['window'] }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Komendy operatorskie</h3>
                            <p class="adm-monitor__section-copy">Najważniejsze komendy do ręcznego backupu, listowania, weryfikacji i restore.</p>
                        </div>
                    </div>

                    <div class="adm-monitor__stack">
                        @foreach ($backups['commands'] as $item)
                            <article class="adm-monitor__job">
                                <div class="adm-monitor__job-head">
                                    <div>
                                        <h4>{{ $item['label'] }}</h4>
                                        <p>{{ $item['details'] }}</p>
                                    </div>
                                </div>
                                <p class="adm-monitor__database-path">{{ $item['command'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="adm-monitor__bottom-grid">
                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Ostatnie backupy</h3>
                            <p class="adm-monitor__section-copy">Lista ostatnich artefaktów z retencji. To jest najważniejszy stół operacyjny tej zakładki.</p>
                        </div>
                        <span class="adm-monitor__section-chip">{{ count($backups['recent']) }} wpisów</span>
                    </div>

                    <div class="adm-monitor__table-wrap">
                        <table class="adm-monitor__table adm-monitor__table--compact">
                            <thead>
                                <tr>
                                    <th>Utworzono</th>
                                    <th>Wiek</th>
                                    <th>Rozmiar</th>
                                    <th>Połączenie</th>
                                    <th>Driver</th>
                                    <th>Etykieta</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($backups['recent'] as $item)
                                    @php
                                        $deleteAction = filled($item['reference'] ?? null)
                                            ? "deleteBackup('".str_replace("'", "\\'", $item['reference'])."')"
                                            : null;
                                    @endphp
                                    <tr>
                                        <td class="is-strong">{{ $item['created_at'] }}</td>
                                        <td class="is-numeric">{{ $item['age'] }}</td>
                                        <td class="is-numeric">{{ $item['bytes'] }}</td>
                                        <td>{{ $item['connection'] }}</td>
                                        <td>{{ $item['driver'] }}</td>
                                        <td>{{ $item['label'] }}</td>
                                        <td class="whitespace-nowrap">
                                            @if (filled($item['reference']))
                                                <div class="flex flex-wrap gap-2">
                                                    <x-filament::button
                                                        tag="a"
                                                        size="xs"
                                                        :href="route('admin.backups.download', ['reference' => $item['reference']])"
                                                        icon="heroicon-o-arrow-down-tray"
                                                    >
                                                        Pobierz
                                                    </x-filament::button>

                                                    <button
                                                        type="button"
                                                        class="fi-color fi-color-danger fi-bg-color-600 hover:fi-bg-color-500 dark:fi-bg-color-600 dark:hover:fi-bg-color-500 fi-text-color-0 hover:fi-text-color-0 dark:fi-text-color-0 dark:hover:fi-text-color-0 fi-btn fi-size-xs"
                                                        wire:click="{{ $deleteAction }}"
                                                        onclick="return confirm('Usunąć ten backup?')"
                                                    >
                                                        <svg class="fi-icon fi-size-sm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                        <span>Usuń</span>
                                                    </button>
                                                </div>
                                            @else
                                                <span class="text-slate-500">brak akcji</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="7">
                                            <div class="adm-monitor__database">
                                                <div>
                                                    <span class="adm-monitor__meta-label">Backup</span>
                                                    <p class="adm-monitor__database-path">{{ $item['path'] }}</p>
                                                </div>
                                                <div class="adm-monitor__database-meta">
                                                    <span class="adm-monitor__meta-label">Manifest</span>
                                                    <strong>{{ $item['manifest'] }}</strong>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">Brak zapisanych backupów na skonfigurowanym dysku.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="adm-monitor__panel">
                    <div class="adm-monitor__section-header">
                        <div>
                            <h3 class="adm-monitor__section-title">Konfiguracja storage</h3>
                            <p class="adm-monitor__section-copy">Gdzie fizycznie trafiają backupy i manifesty oraz skąd bierze się lokalny staging pliku.</p>
                        </div>
                    </div>

                    <div class="adm-monitor__stack">
                        @foreach ($backups['storage'] as $item)
                            <article class="adm-monitor__quality-card">
                                <div class="adm-monitor__quality-head">
                                    <div>
                                        <h4>{{ $item['label'] }}</h4>
                                        <p>{{ $item['details'] }}</p>
                                    </div>
                                </div>
                                <span class="adm-monitor__risk-recommendation">{{ $item['value'] }}</span>
                            </article>
                        @endforeach
                    </div>
                </section>
            </div>
    @endif

    </div>
</x-filament-panels::page>

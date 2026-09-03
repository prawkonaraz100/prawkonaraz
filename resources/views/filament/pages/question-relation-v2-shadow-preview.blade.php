<x-filament-panels::page>
    @php
        $monitorStatus = (string) data_get($monitoring, 'status', 'warning');
        $monitorTone = match ($monitorStatus) {
            'ok' => 'success',
            'failed' => 'danger',
            default => 'warning',
        };
        $monitorStatusLabel = match ($monitorStatus) {
            'ok' => 'Stabilny',
            'failed' => 'Wymaga interwencji',
            default => 'Do sprawdzenia',
        };
        $previewState = (string) data_get($preview, 'state', '');
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 via-white to-indigo-50 shadow-sm">
            <div class="flex flex-col gap-5 p-6 xl:flex-row xl:items-center xl:justify-between">
                <div class="max-w-3xl">
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-800">
                        <span class="h-2 w-2 rounded-full bg-sky-600"></span>
                        Tylko panel administratora
                    </div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-950">V2 jest liczone obok V1, ale nie jest publikowane</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Ten ekran używa snapshotu <code>mode=shadow</code> z ekspozycją 0%. Nie zmienia wyniku strony pytania, jej SSR, canonicali ani linków widocznych dla użytkownika i robota.
                    </p>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                        <span class="block text-slate-500">V2</span>
                        <strong class="mt-1 block text-slate-900">{{ data_get($monitoring, 'runtime.global_v2_enabled') ? 'ON' : 'OFF' }}</strong>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                        <span class="block text-slate-500">Shadow</span>
                        <strong class="mt-1 block text-slate-900">{{ data_get($monitoring, 'runtime.runtime_shadow_enabled') ? 'ON' : 'OFF' }}</strong>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                        <span class="block text-slate-500">Publiczny output</span>
                        <strong class="mt-1 block text-slate-900">V1</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Monitoring quality gate</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h3 class="text-lg font-bold text-slate-950">Stan aktywnych rolloutów shadow</h3>
                        <span @class([
                            'rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-emerald-100 text-emerald-800' => $monitorTone === 'success',
                            'bg-amber-100 text-amber-800' => $monitorTone === 'warning',
                            'bg-rose-100 text-rose-800' => $monitorTone === 'danger',
                        ])>{{ $monitorStatusLabel }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">Audyt porównuje aktualny selektor V1 ze snapshotem V2 i mierzy czas jego pełnego przebiegu.</p>
                </div>
                <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <dt class="text-xs text-slate-500">Rollouty</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-900">{{ data_get($monitoring, 'summary.active_shadow_rollouts', 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Błędy</dt>
                        <dd class="mt-1 text-lg font-bold text-rose-700">{{ data_get($monitoring, 'summary.errors', 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Ostrzeżenia</dt>
                        <dd class="mt-1 text-lg font-bold text-amber-700">{{ data_get($monitoring, 'summary.warnings', 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Pełny audyt</dt>
                        <dd class="mt-1 text-lg font-bold text-slate-900">{{ number_format((float) data_get($monitoring, 'summary.duration_ms', 0), 2, ',', ' ') }} ms</dd>
                    </div>
                </dl>
            </div>

            @if ($monitoringError)
                <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                    <strong>{{ $monitoringError['headline'] }}</strong>
                    <p class="mt-1">{{ $monitoringError['message'] }}</p>
                    <p class="mt-2 break-all text-xs">{{ $monitoringError['details'] }}</p>
                </div>
            @elseif (filled(data_get($monitoring, 'topics')))
                <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Topic / run</th>
                                <th class="px-4 py-3 font-semibold">Rekomendacje</th>
                                <th class="px-4 py-3 font-semibold">V1 / V2 / wspólne</th>
                                <th class="px-4 py-3 font-semibold">Nierozwiązywalne</th>
                                <th class="px-4 py-3 font-semibold">Czas audytu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach (data_get($monitoring, 'topics', []) as $topicReport)
                                @php
                                    $audit = $topicReport['audit'];
                                @endphp
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        <strong class="block text-slate-900">{{ $topicReport['topic']['label'] }}</strong>
                                        <span class="text-xs text-slate-500">{{ $topicReport['topic']['key'] }} · run #{{ $topicReport['run']['id'] }} · {{ $topicReport['rollout']['mode'] }} {{ $topicReport['rollout']['exposure_percentage'] }}%</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $audit['recommendations']['selected'] }} / {{ $audit['recommendations']['sources'] }} źródeł
                                        <span class="block text-xs text-slate-500">min {{ $audit['recommendations']['minimum_per_source'] }}, max {{ $audit['recommendations']['maximum_per_source'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $audit['comparison']['v1_links_total'] }} / {{ $audit['comparison']['v2_links_total'] }} / {{ $audit['comparison']['shared_links_total'] }}
                                        <span class="block text-xs text-slate-500">{{ $audit['comparison']['sources_compared'] }} porównanych źródeł</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $audit['recommendations']['unresolved_sources'] }} źródeł · {{ $audit['recommendations']['unresolved_targets'] }} targetów
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ number_format((float) $audit['duration_ms'], 2, ',', ' ') }} ms</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if (filled(data_get($monitoring, 'issues.warnings')) || filled(data_get($monitoring, 'issues.errors')))
                <div class="mt-5 grid gap-3 lg:grid-cols-2">
                    @foreach (array_merge(data_get($monitoring, 'issues.errors', []), data_get($monitoring, 'issues.warnings', [])) as $issue)
                        <article @class([
                            'rounded-xl border p-4 text-sm',
                            'border-rose-200 bg-rose-50 text-rose-900' => in_array($issue['type'], ['rollout_context_not_resolvable', 'shadow_audit_failed'], true),
                            'border-amber-200 bg-amber-50 text-amber-900' => ! in_array($issue['type'], ['rollout_context_not_resolvable', 'shadow_audit_failed'], true),
                        ])>
                            <strong class="block">{{ str($issue['type'])->replace('_', ' ')->headline() }}</strong>
                            <span class="mt-1 block">{{ $issue['message'] }}</span>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kontrolowane porównanie jednego pytania</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-950">Wybierz pytanie z aktywnego shadow</h3>
                </div>
                <form wire:submit="showQuestion" class="flex w-full max-w-xl gap-2">
                    <label class="sr-only" for="shadow-external-id">ID pytania</label>
                    <input
                        id="shadow-external-id"
                        type="text"
                        wire:model="externalId"
                        placeholder="np. 352"
                        class="block min-w-0 flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    />
                    <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">
                        Porównaj
                    </x-filament::button>
                </form>
            </div>

            @if ($previewError)
                <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                    <strong>{{ $previewError['headline'] }}</strong>
                    <p class="mt-1">{{ $previewError['message'] }}</p>
                    <p class="mt-2 break-all text-xs">{{ $previewError['details'] }}</p>
                </div>
            @elseif ($previewState === 'ready')
                <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Źródło · pytanie {{ data_get($preview, 'source.display_external_id') }}</span>
                            <h4 class="mt-1 text-base font-bold text-slate-950">{{ data_get($preview, 'source.prompt_plain') }}</h4>
                            <a href="{{ data_get($preview, 'source.url') }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-medium text-primary-600 hover:text-primary-700">Otwórz publiczną stronę V1 ↗</a>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="rounded-lg bg-white px-3 py-2 ring-1 ring-slate-200">
                                <span class="block text-slate-500">V1</span>
                                <strong class="mt-1 block text-slate-900">{{ data_get($preview, 'v1.total', 0) }}</strong>
                            </div>
                            <div class="rounded-lg bg-white px-3 py-2 ring-1 ring-slate-200">
                                <span class="block text-slate-500">V2</span>
                                <strong class="mt-1 block text-slate-900">{{ data_get($preview, 'v2.total', 0) }}</strong>
                            </div>
                            <div class="rounded-lg bg-white px-3 py-2 ring-1 ring-slate-200">
                                <span class="block text-slate-500">Wspólne</span>
                                <strong class="mt-1 block text-slate-900">{{ data_get($preview, 'comparison.shared_total', 0) }}</strong>
                            </div>
                        </div>
                    </div>
                    <dl class="mt-4 grid gap-3 border-t border-slate-200 pt-4 text-sm sm:grid-cols-2 xl:grid-cols-5">
                        <div><dt class="text-slate-500">Run</dt><dd class="mt-1 font-semibold text-slate-900">#{{ data_get($preview, 'v2.run.id') }} · {{ data_get($preview, 'v2.run.status') }}</dd></div>
                        <div><dt class="text-slate-500">Topic</dt><dd class="mt-1 font-semibold text-slate-900">{{ data_get($preview, 'v2.topic.label') }}</dd></div>
                        <div><dt class="text-slate-500">V1</dt><dd class="mt-1 font-semibold text-slate-900">{{ number_format((float) data_get($preview, 'performance.v1_duration_ms', 0), 2, ',', ' ') }} ms</dd></div>
                        <div><dt class="text-slate-500">V2</dt><dd class="mt-1 font-semibold text-slate-900">{{ number_format((float) data_get($preview, 'performance.v2_duration_ms', 0), 2, ',', ' ') }} ms</dd></div>
                        <div><dt class="text-slate-500">Łącznie</dt><dd class="mt-1 font-semibold text-slate-900">{{ number_format((float) data_get($preview, 'performance.total_duration_ms', 0), 2, ',', ' ') }} ms</dd></div>
                    </dl>
                </div>

                <div class="mt-6 grid gap-6 xl:grid-cols-2">
                    @foreach (['v1' => ['label' => 'V1 — aktualny renderer publiczny', 'tone' => 'slate'], 'v2' => ['label' => 'V2 — snapshot shadow, tylko panel', 'tone' => 'sky']] as $version => $meta)
                        <section @class([
                            'overflow-hidden rounded-xl border',
                            'border-slate-200' => $meta['tone'] === 'slate',
                            'border-sky-200' => $meta['tone'] === 'sky',
                        ])>
                            <div @class([
                                'border-b px-4 py-3',
                                'border-slate-200 bg-slate-50' => $meta['tone'] === 'slate',
                                'border-sky-200 bg-sky-50' => $meta['tone'] === 'sky',
                            ])>
                                <h4 class="font-bold text-slate-950">{{ $meta['label'] }}</h4>
                                <p class="mt-1 text-xs text-slate-600">{{ data_get($preview, $version.'.total', 0) }} linków · żadna kolumna nie zmienia publicznego HTML</p>
                            </div>
                            <div class="divide-y divide-slate-100">
                                @forelse (data_get($preview, $version.'.groups', []) as $group)
                                    <div class="p-4">
                                        <h5 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $group['label'] }}</h5>
                                        <ol class="mt-3 space-y-3">
                                            @foreach ($group['items'] as $item)
                                                <li class="flex gap-3">
                                                    <span class="flex h-6 min-w-6 items-center justify-center rounded-full bg-slate-100 px-1 text-xs font-bold text-slate-600">{{ $item['shadow_rank'] ?? '•' }}</span>
                                                    <div class="min-w-0">
                                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="text-sm font-semibold text-primary-700 hover:text-primary-800">
                                                            Pytanie {{ $item['display_external_id'] }}
                                                        </a>
                                                        <p class="mt-1 text-sm leading-5 text-slate-700">{{ $item['prompt_plain'] }}</p>
                                                        <p class="mt-1 text-xs text-slate-500">
                                                            @if (filled($item['category_code'])) {{ $item['category_code'] }} @endif
                                                            @if (filled($item['relation_scope'])) · {{ $item['relation_scope'] }} @endif
                                                            @if ($item['relation_score'] !== null) · score {{ number_format((float) $item['relation_score'], 2, ',', ' ') }} @endif
                                                        </p>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>
                                @empty
                                    <p class="p-4 text-sm text-slate-500">Brak linków w tym wyniku.</p>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            @elseif ($preview !== null)
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <strong>{{ data_get($preview, 'message') }}</strong>
                    <p class="mt-1">Stan: {{ $previewState ?: 'brak danych' }}. Możesz podać inne ID pytania albo sprawdzić rollout i primary membership.</p>
                </div>
            @else
                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    Wpisz ID pytania, aby wyświetlić bezpieczne porównanie.
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>

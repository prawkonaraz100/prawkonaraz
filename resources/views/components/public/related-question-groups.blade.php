@props([
    'groups',
    'total',
    'categoryUrl',
    'topic' => null,
    'preview' => false,
])

@php
    $mobileVisible = max(1, (int) config('question_relations.mobile_visible_links', 8));
    $desktopVisible = max($mobileVisible, (int) config('question_relations.desktop_visible_links', 15));
    $entries = collect($groups)->flatMap(function (array $group): array {
        return $group['items']->map(fn (array $item): array => [
            'group_key' => $group['key'],
            'group_label' => $group['label'],
            'item' => $item,
        ])->all();
    })->values();
    $prepareSegment = static function ($segment, ?string $previousKey = null) {
        return $segment->map(function (array $entry) use (&$previousKey): array {
            $entry['starts_group'] = $entry['group_key'] !== $previousKey;
            $previousKey = $entry['group_key'];

            return $entry;
        })->values();
    };
    $initialEntries = $prepareSegment($entries->take($mobileVisible));
    $desktopEntries = $prepareSegment(
        $entries->slice($mobileVisible, $desktopVisible - $mobileVisible),
        $initialEntries->last()['group_key'] ?? null,
    );
    $remainingEntries = $prepareSegment(
        $entries->slice($desktopVisible),
        $desktopEntries->last()['group_key'] ?? $initialEntries->last()['group_key'] ?? null,
    );
    $topicUrl = $topic?->is_indexable ? route('public.questions.topics.show', $topic->slug) : null;
    $count = max(0, (int) $total);
    $countModuloHundred = $count % 100;
    $countModuloTen = $count % 10;
    $countLabel = $count === 1
        ? 'pytanie do dalszej nauki'
        : ($countModuloTen >= 2 && $countModuloTen <= 4 && ($countModuloHundred < 12 || $countModuloHundred > 14)
            ? 'pytania do dalszej nauki'
            : 'pytań do dalszej nauki');
@endphp

<section class="mt-7 grid overflow-hidden border border-[#dce3eb] bg-white min-[1080px]:grid-cols-[340px_minmax(0,1fr)]" aria-labelledby="related-questions-heading">
    <div class="border-b border-[#e9edf2] p-6 min-[1080px]:border-b-0 min-[1080px]:border-r min-[1080px]:p-7">
        <p class="text-[0.7rem] font-bold uppercase tracking-[0.08em] text-[#d01921]">Dalsza nauka</p>
        <h2 class="mt-2 text-[1.2rem] font-bold leading-tight text-[#111827]">Chcesz przerobić całą bazę?</h2>
        <p class="mt-3 max-w-md text-[0.92rem] leading-6 text-[#4b5563]">
            Przechodź między pytaniami, wracaj do błędów i ucz się według powiązanych tematów.
        </p>
        <a href="{{ route('register') }}" class="mt-5 inline-flex min-h-10 items-center justify-center rounded-[4px] bg-[#d01921] px-6 text-[0.86rem] font-bold text-white transition hover:bg-[#b9151c]">
            Załóż darmowe konto
        </a>
    </div>

    <div class="min-w-0">
        <div class="flex flex-col gap-4 border-b border-[#e9edf2] px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="min-w-0">
                @if ($preview)
                    <p class="mb-2 text-[0.68rem] font-bold uppercase tracking-[0.08em] text-[#b45309]">Podgląd V2 · link tymczasowy, nieindeksowany</p>
                @endif
                <h2 id="related-questions-heading" class="text-[1rem] font-bold uppercase tracking-[0.02em] text-[#111827]">
                    Powiązane pytania
                </h2>
                <p class="mt-1 text-[0.78rem] leading-5 text-[#64748b]">
                    Sprawdź podobne sytuacje i utrwal wiedzę przed egzaminem.
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-x-5 gap-y-2">
                <span class="text-[0.78rem] font-semibold tabular-nums text-[#64748b]">
                    {{ number_format($count, 0, ',', ' ') }} {{ $countLabel }}
                </span>
                <a href="{{ $topicUrl ?: $categoryUrl }}" class="group/topic-link inline-flex items-center gap-2 text-[0.84rem] font-semibold text-[#334155] transition hover:text-[#d01921]">
                    {{ $topicUrl ? 'Zobacz cały temat' : 'Zobacz całą kategorię' }}
                    <span class="transition-transform duration-150 group-hover/topic-link:translate-x-0.5" aria-hidden="true">›</span>
                </a>
            </div>
        </div>

        @if ($entries->isNotEmpty())
            <x-public.related-question-list :entries="$initialEntries" />

            @if ($desktopEntries->isNotEmpty())
                <details open class="group border-t border-[#e9edf2] md:border-t-0" data-related-mobile-more>
                    <summary class="cursor-pointer list-none px-5 py-4 text-[0.84rem] font-bold text-[#334155] transition hover:text-[#d01921] md:hidden">
                        <span class="inline-flex items-center gap-2">
                            Pokaż kolejne {{ $desktopEntries->count() }} pytań
                            <span class="transition group-open:rotate-90" aria-hidden="true">›</span>
                        </span>
                    </summary>
                    <div class="hidden group-open:block md:block">
                        <x-public.related-question-list :entries="$desktopEntries" />
                    </div>
                </details>
            @endif

            @if ($remainingEntries->isNotEmpty())
                <details class="group border-t border-[#e9edf2]">
                    <summary class="cursor-pointer list-none px-5 py-4 text-[0.84rem] font-bold text-[#334155] transition hover:text-[#d01921] sm:px-6">
                        <span class="inline-flex items-center gap-2">
                            Pokaż wszystkie pozostałe ({{ $remainingEntries->count() }})
                            <span class="transition group-open:rotate-90" aria-hidden="true">›</span>
                        </span>
                    </summary>
                    <div class="hidden group-open:block">
                        <x-public.related-question-list :entries="$remainingEntries" />
                    </div>
                </details>
            @endif
        @else
            <p class="px-6 py-5 text-[0.9rem] text-[#64748b]">Nie znaleźliśmy jeszcze powiązanych pytań dla tej pozycji.</p>
        @endif
    </div>
</section>

@once
    @push('scripts')
        <script>
            (() => {
                const desktopQuery = window.matchMedia('(min-width: 768px)');
                const detailsElements = document.querySelectorAll('[data-related-mobile-more]');
                const syncDetails = () => {
                    detailsElements.forEach((details) => {
                        details.open = desktopQuery.matches;
                    });
                };

                syncDetails();

                if (typeof desktopQuery.addEventListener === 'function') {
                    desktopQuery.addEventListener('change', syncDetails);
                } else if (typeof desktopQuery.addListener === 'function') {
                    desktopQuery.addListener(syncDetails);
                }
            })();
        </script>
    @endpush
@endonce

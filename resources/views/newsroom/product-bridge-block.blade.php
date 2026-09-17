@if ($block['type'] === 'question_group' && ! empty($block['questions']))
    <section class="border-y border-slate-200 py-6" aria-labelledby="newsroom-question-group-{{ $loop->index ?? 0 }}">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Pytania egzaminacyjne</p>
                <h2 id="newsroom-question-group-{{ $loop->index ?? 0 }}" class="mt-1 text-xl font-semibold text-slate-950">Sprawdź, czy to umiesz</h2>
            </div>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            @foreach ($block['questions'] as $question)
                <a href="{{ $question['url'] }}" class="group flex min-w-0 gap-4 border border-slate-200 bg-white p-4 hover:border-slate-400">
                    @if ($question['thumbnail_url'])
                        <img src="{{ $question['thumbnail_url'] }}" alt="{{ $question['thumbnail_alt'] ?: '' }}" width="112" height="84" class="h-[84px] w-28 shrink-0 object-cover" loading="lazy" decoding="async">
                    @endif
                    <span class="min-w-0">
                        <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            {{ $question['display_external_id'] }}@if ($question['category_code']) · kat. {{ $question['category_code'] }}@endif
                        </span>
                        <span class="mt-1 block text-sm font-semibold leading-6 text-slate-950 group-hover:underline">{{ $question['prompt'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@if ($block['type'] === 'legal_reference' && ! empty($block['legal_reference']))
    <aside class="border-l-4 border-[#efc54f] bg-[#fffdf3] px-5 py-5 sm:px-6" aria-label="Podstawa prawna">
        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Podstawa prawna</p>
        <a href="{{ $block['legal_reference']['url'] }}" class="mt-2 block text-lg font-semibold leading-7 text-slate-950 hover:underline">
            {{ $block['legal_reference']['reference'] }}
        </a>
        @if ($block['legal_reference']['title'] && $block['legal_reference']['title'] !== $block['legal_reference']['reference'])
            <p class="mt-1 text-sm font-semibold leading-6 text-slate-800">{{ $block['legal_reference']['title'] }}</p>
        @endif
        @if ($block['legal_reference']['summary'])
            <p class="mt-2 text-sm leading-6 text-slate-700">{{ $block['legal_reference']['summary'] }}</p>
        @endif
    </aside>
@endif

@if ($block['type'] === 'traffic_sign_group' && ! empty($block['traffic_signs']))
    <section class="border-y border-slate-200 py-6" aria-labelledby="newsroom-sign-group-{{ $loop->index ?? 0 }}">
        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Znaki drogowe</p>
        <h2 id="newsroom-sign-group-{{ $loop->index ?? 0 }}" class="mt-1 text-xl font-semibold text-slate-950">Powiązane znaki</h2>
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            @foreach ($block['traffic_signs'] as $sign)
                <a href="{{ $sign['url'] }}" class="group flex min-w-0 items-center gap-4 border border-slate-200 bg-white p-4 hover:border-slate-400">
                    @if ($sign['image_url'])
                        <img src="{{ $sign['image_url'] }}" alt="{{ $sign['image_alt'] }}" width="80" height="80" class="h-20 w-20 shrink-0 object-contain" loading="lazy" decoding="async">
                    @endif
                    <span class="min-w-0">
                        <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $sign['code'] }}</span>
                        <span class="mt-1 block text-sm font-semibold leading-6 text-slate-950 group-hover:underline">{{ $sign['title'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif

@if ($block['type'] === 'product_cta' && ! empty($block['product_cta']))
    <aside class="border border-slate-200 bg-slate-50 px-5 py-5 sm:flex sm:items-center sm:justify-between sm:gap-6 sm:px-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">PrawkoNaRaz</p>
            <p class="mt-1 text-sm leading-6 text-slate-700">{{ $block['product_cta']['description'] }}</p>
        </div>
        <a href="{{ $block['product_cta']['url'] }}" class="mt-4 inline-flex min-h-11 shrink-0 items-center justify-center bg-[#efc54f] px-5 py-2.5 text-sm font-bold text-slate-950 hover:brightness-95 sm:mt-0">
            {{ $block['product_cta']['label'] }}
        </a>
    </aside>
@endif

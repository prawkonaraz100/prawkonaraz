@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'site-shell')

@section('content')
    <section class="bg-white">
        <div class="site-shell py-8 md:py-10">
            <header class="max-w-4xl border-b border-[#e9edf2] pb-7">
                <p class="text-[0.72rem] font-bold uppercase tracking-[0.08em] text-[#d01921]">Temat pytań egzaminacyjnych</p>
                <h1 class="mt-3 text-[1.9rem] font-bold leading-tight text-[#111827] md:text-[2.2rem]">{{ $topic->label }}</h1>
                <p class="mt-3 text-[1rem] leading-7 text-[#5f6b81]">{{ $topic->description }}</p>
                <p class="mt-4 text-[0.88rem] font-semibold text-[#334155]">
                    {{ number_format($questions->total(), 0, ',', ' ') }} pytań w tym klastrze tematycznym
                </p>
            </header>

            @if ($questions->count() > 0)
                <div class="mt-7 overflow-hidden border border-[#dce3eb] bg-white">
                    @foreach ($questions as $question)
                        @continue(! is_array($question))
                        <a href="{{ $question['canonical_url'] }}" class="group grid gap-2 border-b border-[#e9edf2] px-5 py-4 transition last:border-b-0 hover:bg-[#fbfcfe] sm:grid-cols-[106px_minmax(0,1fr)_80px_20px] sm:items-center sm:gap-4 sm:px-6">
                            <span class="text-[0.8rem] font-semibold text-[#64748b]">#{{ $question['display_external_id'] }}</span>
                            <span class="text-[0.95rem] font-semibold leading-6 text-[#111827] transition group-hover:text-[#d01921]">{{ $question['prompt_plain'] }}</span>
                            <span class="text-[0.8rem] font-semibold text-[#64748b] sm:text-right">
                                @if (! empty($question['category_code']))
                                    Kat. {{ $question['category_code'] }}
                                @endif
                            </span>
                            <span class="hidden text-[#64748b] transition group-hover:translate-x-0.5 group-hover:text-[#d01921] sm:block" aria-hidden="true">›</span>
                        </a>
                    @endforeach
                </div>

                @if ($questions->hasPages())
                    <nav class="mt-6 flex items-center justify-between gap-4" aria-label="Paginacja pytań tematu {{ $topic->label }}">
                        @if ($questions->onFirstPage())
                            <span class="text-[0.88rem] text-[#94a3b8]">← Poprzednia</span>
                        @else
                            <a href="{{ $questions->previousPageUrl() }}" class="text-[0.88rem] font-bold text-[#334155] hover:text-[#d01921]">← Poprzednia</a>
                        @endif
                        <span class="text-[0.85rem] text-[#64748b]">Strona {{ $questions->currentPage() }} z {{ $questions->lastPage() }}</span>
                        @if ($questions->hasMorePages())
                            <a href="{{ $questions->nextPageUrl() }}" class="text-[0.88rem] font-bold text-[#334155] hover:text-[#d01921]">Następna →</a>
                        @else
                            <span class="text-[0.88rem] text-[#94a3b8]">Następna →</span>
                        @endif
                    </nav>
                @endif
            @endif

            @if ($topic->adjacentTopics->isNotEmpty())
                <aside class="mt-9 border-t border-[#e9edf2] pt-6" aria-labelledby="adjacent-topics-heading">
                    <h2 id="adjacent-topics-heading" class="text-[1rem] font-bold uppercase tracking-[0.02em] text-[#111827]">Powiązane tematy</h2>
                    <div class="mt-4 flex flex-wrap gap-3">
                        @foreach ($topic->adjacentTopics as $adjacentTopic)
                            <a href="{{ route('public.questions.topics.show', $adjacentTopic->slug) }}" class="border border-[#dce3eb] px-4 py-2 text-[0.86rem] font-semibold text-[#334155] transition hover:border-[#c4cfdb] hover:text-[#d01921]">
                                {{ $adjacentTopic->label }}
                            </a>
                        @endforeach
                    </div>
                </aside>
            @endif
        </div>
    </section>
@endsection

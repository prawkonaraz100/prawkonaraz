@extends('layouts.public-content')

@section('breadcrumb_nav_class', 'mx-auto grid max-w-[1180px] lg:grid-cols-[minmax(0,820px)_minmax(240px,280px)] lg:justify-center lg:gap-12')
@section('breadcrumb_list_class', 'lg:col-start-1')

@php
    $canManageQuestionReferences = (bool) auth()->user()?->isAdministrator();
    $bodyParagraphs = filled($page->body)
        ? (preg_split('/\R{2,}/u', trim((string) $page->body)) ?: [])
        : [];
    $authorPhotoUrl = $authorPhotoUrl ?? null;
    $reviewerPhotoUrl = $reviewerPhotoUrl ?? null;
    $articleImage = is_array($articleImage ?? null) ? $articleImage : null;
    $authorInitials = collect(preg_split('/\s+/', $page->author?->name ?? 'Zespół prawkonaraz.pl', -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $reviewerInitials = collect(preg_split('/\s+/', $page->reviewer?->name ?? 'Redakcja', -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

@section('content')
    <section class="content-band">
        <div class="content-shell content-hero-section">
            <div class="mx-auto grid max-w-[1180px] lg:grid-cols-[minmax(0,820px)_minmax(240px,280px)] lg:justify-center lg:gap-12">
                <div class="min-w-0 lg:col-start-1">
                <p class="content-kicker">{{ $page->topic?->title ?? 'Przepisy' }}</p>
                <h1 class="mt-3 text-[2.45rem] font-semibold leading-[1.08] tracking-tight text-slate-950 md:text-[3.15rem]">
                    {{ $page->title }}
                </h1>
                @if ($page->intro)
                    <p class="content-muted mt-5 max-w-[52rem] text-base leading-7 md:text-lg">{{ $page->intro }}</p>
                @endif

                <dl class="mt-7 flex flex-wrap gap-x-8 gap-y-4 border-y border-slate-200 py-4 text-sm">
                    <div class="flex min-w-[210px] items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                            @if ($authorPhotoUrl)
                                <img src="{{ $authorPhotoUrl }}" alt="" class="h-full w-full object-cover" width="44" height="44">
                            @else
                                {{ $authorInitials }}
                            @endif
                        </span>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.04em] text-slate-500">Autor</dt>
                            <dd class="mt-1.5 font-bold text-slate-950">
                                @if ($page->author)
                                    <a href="{{ route('content-authors.show', $page->author->slug) }}" class="hover:text-[#d01921]">{{ $page->author->name }}</a>
                                @else
                                    Zespół prawkonaraz.pl
                                @endif
                            </dd>
                        </div>
                    </div>
                    <div class="flex min-w-[210px] items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                            @if ($reviewerPhotoUrl)
                                <img src="{{ $reviewerPhotoUrl }}" alt="" class="h-full w-full object-cover" width="44" height="44">
                            @else
                                {{ $reviewerInitials }}
                            @endif
                        </span>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.04em] text-slate-500">Weryfikacja</dt>
                            <dd class="mt-1.5 font-bold text-slate-950">
                                @if ($page->reviewer)
                                    <a href="{{ route('content-authors.show', $page->reviewer->slug) }}" class="hover:text-[#d01921]">{{ $page->reviewer->name }}</a>
                                @else
                                    Redakcja
                                @endif
                            </dd>
                        </div>
                    </div>
                    <div class="min-w-[130px]">
                        <dt class="text-xs font-semibold uppercase tracking-[0.04em] text-slate-500">Publikacja</dt>
                        <dd class="mt-1.5 font-bold text-slate-950">{{ $page->published_at?->format('d.m.Y') ?? '-' }}</dd>
                    </div>
                    <div class="min-w-[150px]">
                        <dt class="whitespace-nowrap text-xs font-semibold uppercase tracking-[0.04em] text-slate-500">Stan prawny na dzień</dt>
                        <dd class="mt-1.5 font-bold text-slate-950">
                            @if ($page->last_reviewed_at)
                                <time datetime="{{ $page->last_reviewed_at->toDateString() }}">{{ $page->last_reviewed_at->format('d.m.Y') }}</time>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                </dl>
                </div>
            </div>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell py-8 md:py-10">
            <div class="mx-auto grid max-w-[1180px] gap-10 lg:grid-cols-[minmax(0,820px)_minmax(240px,280px)] lg:items-start lg:justify-center lg:gap-12">
            <article class="min-w-0">
                @if ($page->summary)
                    <section aria-label="Wprowadzenie">
                        <p class="text-[18px] font-semibold leading-8 text-slate-900">{{ $page->summary }}</p>
                    </section>
                @endif

                @if (filled($articleImage['url'] ?? null))
                    <figure class="mt-8 overflow-hidden rounded-[6px] bg-slate-100">
                        <img
                            src="{{ $articleImage['url'] }}"
                            alt="{{ $articleImage['alt'] ?? $page->title }}"
                            width="{{ $articleImage['width'] ?? 1400 }}"
                            height="{{ $articleImage['height'] ?? 788 }}"
                            class="block h-auto w-full object-cover"
                            fetchpriority="high"
                            decoding="async"
                        >
                    </figure>
                @endif

                @if (! empty($page->key_points))
                    <section id="najwazniejsze-informacje" class="mt-8 scroll-mt-24 border-l-4 border-[#d01921] bg-slate-50 px-6 py-6 sm:px-7">
                        <h2 class="text-2xl font-semibold text-slate-950">Najważniejsze informacje</h2>
                        <ul class="mt-5 space-y-3 text-[15px] leading-7 text-slate-800">
                            @foreach ($page->key_points as $point)
                                <li class="flex gap-3">
                                    <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-[#d01921]" aria-hidden="true"></span>
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <nav class="mt-8 border-y border-slate-200 py-6" aria-label="Spis treści artykułu">
                    <p class="font-semibold text-slate-950">Przejdź do sekcji:</p>
                    <ul class="mt-3 space-y-2 text-[15px] font-semibold">
                        @if ($page->exam_context)
                            <li>
                                <a href="#w-praktyce-na-egzaminie" class="inline-flex items-center gap-2 text-[#0d47a1] transition hover:text-[#d01921]">
                                    <span aria-hidden="true">›</span>
                                    W praktyce na egzaminie
                                </a>
                            </li>
                        @endif
                        @if ($page->body)
                            <li>
                                <a href="#szczegolowe-omowienie" class="inline-flex items-center gap-2 text-[#0d47a1] transition hover:text-[#d01921]">
                                    <span aria-hidden="true">›</span>
                                    Szczegółowe omówienie
                                </a>
                            </li>
                        @endif
                        @if ($page->legalUnits->isNotEmpty())
                            <li>
                                <a href="#podstawa-prawna" class="inline-flex items-center gap-2 text-[#0d47a1] transition hover:text-[#d01921]">
                                    <span aria-hidden="true">›</span>
                                    Podstawa prawna
                                </a>
                            </li>
                        @endif
                        <li>
                            <a href="#powiazane-pytania" class="inline-flex items-center gap-2 text-[#0d47a1] transition hover:text-[#d01921]">
                                <span aria-hidden="true">›</span>
                                Powiązane pytania egzaminacyjne
                            </a>
                        </li>
                    </ul>
                </nav>

                @if ($page->exam_context)
                    <section id="w-praktyce-na-egzaminie" class="scroll-mt-24 pt-10">
                        <h2 class="text-[1.75rem] font-semibold leading-tight text-slate-950">W praktyce na egzaminie</h2>
                        <p class="mt-4 text-[16px] leading-8 text-slate-800">{{ $page->exam_context }}</p>
                    </section>
                @endif

                @if ($bodyParagraphs !== [])
                    <section id="szczegolowe-omowienie" class="content-prose scroll-mt-24 pt-10">
                        <h2 class="text-[1.75rem] font-semibold leading-tight text-slate-950">Szczegółowe omówienie</h2>
                        <div class="mt-5 space-y-5 text-[16px] leading-8 text-slate-800">
                            @foreach ($bodyParagraphs as $paragraph)
                                <p>{!! nl2br(e($paragraph)) !!}</p>
                            @endforeach
                        </div>
                    </section>
                @endif
            </article>

            <aside class="space-y-8 lg:sticky lg:top-24">
                <section id="podstawa-prawna" class="scroll-mt-24 border-t-4 border-[#d01921] bg-slate-50 px-5 pb-6 pt-5">
                    <h2 class="text-xl font-semibold text-slate-950">Podstawa prawna</h2>
                    <div class="mt-4 divide-y divide-slate-200">
                        @foreach ($page->legalUnits as $unit)
                            <div class="py-4 first:pt-0 last:pb-0">
                                <p class="text-sm font-bold text-slate-950">{{ $unit->legalAct->short_title ?: $unit->legalAct->title }}</p>
                                <p class="mt-1 text-sm text-slate-700">{{ $unit->label }} - {{ $unit->title }}</p>
                                @if ($unit->summary)
                                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ $unit->summary }}</p>
                                @endif
                                <a href="{{ $unit->source_url }}" class="mt-3 inline-flex text-sm font-semibold text-[#d01921] hover:text-[#a91118]" rel="nofollow noopener" target="_blank">
                                    Oficjalne źródło
                                </a>
                            </div>
                        @endforeach
                    </div>
                    @if ($page->source_note)
                        <p class="mt-5 border-t border-slate-200 pt-4 text-xs leading-5 text-slate-600">{{ $page->source_note }}</p>
                    @endif
                </section>

                <section class="border-t border-slate-200 pt-5">
                    <h2 class="text-xl font-semibold text-slate-950">Charakter materiału</h2>
                    <p class="content-muted mt-3 text-sm leading-6">
                        To opracowanie edukacyjne do nauki teorii. Nie jest indywidualną poradą prawną ani pełnym komentarzem do aktu prawnego.
                    </p>
                    <a href="{{ route('public.regulations.methodology') }}" class="mt-4 inline-flex text-sm font-semibold text-[#d01921] hover:text-[#a91118]">
                        Zobacz metodologię
                    </a>
                </section>
            </aside>
            </div>
        </div>
    </section>

    <section id="powiazane-pytania" class="content-band scroll-mt-24">
        <div class="content-shell pb-12">
            <div class="mx-auto max-w-[1180px] border-t border-slate-200 pt-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold text-slate-950">Powiązane pytania egzaminacyjne</h2>
                        <p class="content-muted mt-3 max-w-3xl text-sm leading-6">
                            Poniższe pytania mają zweryfikowane powiązanie z tym zagadnieniem prawnym.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($canManageQuestionReferences)
                            <button
                                type="button"
                                class="inline-flex min-h-10 items-center justify-center rounded-[4px] bg-[#d01921] px-4 text-sm font-bold text-white transition hover:bg-[#b9151c]"
                                data-legal-question-manager-open
                            >
                                Dodaj pytania
                            </button>
                        @endif
                        <a href="{{ route('public.questions.hub') }}" class="text-sm font-semibold text-[#d01921] hover:text-[#a91118]">Baza pytań</a>
                    </div>
                </div>

                @if ($questionCards->isNotEmpty())
                    <div class="mt-5 divide-y divide-slate-200">
                        @foreach ($questionCards as $question)
                            <div class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center">
                                <a href="{{ $question['url'] }}" class="grid min-w-0 flex-1 gap-3 text-[0.9rem] transition hover:bg-slate-50 sm:grid-cols-[120px_minmax(0,1fr)_130px_24px] sm:items-center">
                                    <span class="font-semibold text-slate-600">Pytanie {{ $question['display_external_id'] ?? $question['external_id'] }}</span>
                                    <span class="font-semibold leading-6 text-slate-950">{{ $question['prompt_plain'] }}</span>
                                    <span class="font-semibold text-slate-600">
                                        @if (! empty($question['category_code']))
                                            Kat. {{ $question['category_code'] }}
                                        @endif
                                    </span>
                                    <span class="text-right text-[1.15rem] text-slate-950" aria-hidden="true">›</span>
                                </a>
                                @if ($canManageQuestionReferences && ! empty($question['legal_reference_id']))
                                    <button
                                        type="button"
                                        class="shrink-0 self-start border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-[#d01921] hover:text-[#d01921] sm:self-auto"
                                        data-legal-question-remove
                                        data-delete-url="{{ route('api.v1.admin.legal-content-pages.question-references.destroy', [
                                            'legalContentPage' => $page,
                                            'questionLegalReference' => $question['legal_reference_id'],
                                        ]) }}"
                                        data-question-label="Pytanie {{ $question['display_external_id'] ?? $question['external_id'] }}"
                                    >
                                        Usuń powiązanie
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="py-5 text-sm text-slate-500">Powiązane pytania są w trakcie weryfikacji redakcyjnej.</p>
                @endif
            </div>
        </div>
    </section>

    @if ($canManageQuestionReferences)
        <div
            class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/55 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="legal-question-manager-title"
            data-legal-question-manager
            data-search-url="{{ route('api.v1.admin.legal-content-pages.question-references.index', $page) }}"
            data-store-url="{{ route('api.v1.admin.legal-content-pages.question-references.store', $page) }}"
        >
            <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-5 border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.1em] text-[#0d47a1]">Zarządzanie artykułem</p>
                        <h2 id="legal-question-manager-title" class="mt-1 text-xl font-bold text-slate-950">
                            Dodaj pytania do: {{ $page->title }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center border border-slate-300 text-xl text-slate-700 transition hover:bg-slate-50"
                        aria-label="Zamknij"
                        data-legal-question-manager-close
                    >
                        ×
                    </button>
                </div>

                <div class="overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="grid gap-5 md:grid-cols-[minmax(0,1.4fr)_minmax(250px,0.6fr)]">
                        <div>
                            <label for="legal-question-search" class="text-sm font-bold text-slate-950">Wyszukaj pytanie</label>
                            <input
                                id="legal-question-search"
                                type="search"
                                class="mt-2 block min-h-11 w-full border border-slate-300 px-3 text-sm text-slate-950 focus:border-[#0d47a1] focus:ring-[#0d47a1]"
                                placeholder="Wpisz ID albo fragment treści pytania"
                                autocomplete="off"
                                data-legal-question-search
                            >
                            <p class="mt-2 text-xs leading-5 text-slate-500">Wpisz co najmniej 2 znaki. Warianty tego samego pytania zostaną połączone automatycznie.</p>
                        </div>

                        <div>
                            <label for="legal-question-unit" class="text-sm font-bold text-slate-950">Dokładna podstawa prawna</label>
                            <select
                                id="legal-question-unit"
                                class="mt-2 block min-h-11 w-full border border-slate-300 px-3 text-sm text-slate-950 focus:border-[#0d47a1] focus:ring-[#0d47a1]"
                                data-legal-question-unit
                            >
                                <option value="">Wybierz przepis</option>
                                @foreach ($questionAssignmentLegalUnits as $unit)
                                    <option value="{{ $unit->getKey() }}">{{ $unit->label }} - {{ $unit->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <p class="mt-4 min-h-5 text-sm font-semibold text-slate-600" data-legal-question-status></p>

                    <div class="mt-3 grid gap-5 lg:grid-cols-[minmax(0,1.25fr)_minmax(300px,0.75fr)]">
                        <section class="border border-slate-200">
                            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                                <h3 class="text-sm font-bold text-slate-950">Wyniki wyszukiwania</h3>
                            </div>
                            <div class="max-h-[330px] divide-y divide-slate-200 overflow-y-auto" data-legal-question-results>
                                <p class="px-4 py-6 text-sm text-slate-500">Wyszukaj pytania, które chcesz powiązać z artykułem.</p>
                            </div>
                        </section>

                        <section class="border border-slate-200">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                                <h3 class="text-sm font-bold text-slate-950">Wybrane pytania</h3>
                                <span class="text-xs font-bold text-slate-500" data-legal-question-selected-count>0</span>
                            </div>
                            <div class="max-h-[330px] divide-y divide-slate-200 overflow-y-auto" data-legal-question-selected>
                                <p class="px-4 py-6 text-sm text-slate-500">Nie wybrano jeszcze żadnego pytania.</p>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    <button
                        type="button"
                        class="inline-flex min-h-11 items-center justify-center border border-slate-300 bg-white px-5 text-sm font-bold text-slate-800 transition hover:bg-slate-100"
                        data-legal-question-manager-close
                    >
                        Anuluj
                    </button>
                    <button
                        type="button"
                        class="inline-flex min-h-11 items-center justify-center rounded-[4px] bg-[#d01921] px-6 text-sm font-bold text-white transition hover:bg-[#b9151c] disabled:cursor-not-allowed disabled:opacity-50"
                        data-legal-question-save
                    >
                        Dodaj wybrane pytania
                    </button>
                </div>
            </div>
        </div>
    @endif
@endsection

@if ($canManageQuestionReferences)
    @push('scripts')
        <script>
            (() => {
                const manager = document.querySelector('[data-legal-question-manager]');

                if (!manager) {
                    return;
                }

                const openButton = document.querySelector('[data-legal-question-manager-open]');
                const closeButtons = manager.querySelectorAll('[data-legal-question-manager-close]');
                const searchInput = manager.querySelector('[data-legal-question-search]');
                const legalUnit = manager.querySelector('[data-legal-question-unit]');
                const results = manager.querySelector('[data-legal-question-results]');
                const selectedList = manager.querySelector('[data-legal-question-selected]');
                const selectedCount = manager.querySelector('[data-legal-question-selected-count]');
                const saveButton = manager.querySelector('[data-legal-question-save]');
                const status = manager.querySelector('[data-legal-question-status]');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const selected = new Map();
                let searchTimeout = null;
                let searchSequence = 0;
                let busy = false;

                const setStatus = (message, tone = 'neutral') => {
                    status.textContent = message;
                    status.classList.toggle('text-[#d01921]', tone === 'error');
                    status.classList.toggle('text-[#1f7a3a]', tone === 'success');
                    status.classList.toggle('text-slate-600', tone === 'neutral');
                };

                const setOpen = (open) => {
                    manager.classList.toggle('hidden', !open);
                    manager.classList.toggle('flex', open);
                    document.body.style.overflow = open ? 'hidden' : '';

                    if (open) {
                        window.setTimeout(() => searchInput?.focus(), 0);
                    }
                };

                const renderSelected = () => {
                    selectedList.innerHTML = '';
                    selectedCount.textContent = String(selected.size);

                    if (selected.size === 0) {
                        const empty = document.createElement('p');
                        empty.className = 'px-4 py-6 text-sm text-slate-500';
                        empty.textContent = 'Nie wybrano jeszcze żadnego pytania.';
                        selectedList.appendChild(empty);

                        return;
                    }

                    selected.forEach((question, externalId) => {
                        const item = document.createElement('div');
                        item.className = 'px-4 py-3';

                        const heading = document.createElement('div');
                        heading.className = 'flex items-start justify-between gap-3';

                        const text = document.createElement('div');
                        text.className = 'min-w-0';

                        const id = document.createElement('p');
                        id.className = 'text-xs font-bold text-[#0d47a1]';
                        id.textContent = `Pytanie ${question.display_external_id || externalId}`;

                        const prompt = document.createElement('p');
                        prompt.className = 'mt-1 text-sm font-semibold leading-5 text-slate-950';
                        prompt.textContent = question.prompt || '';

                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'shrink-0 text-xs font-bold text-[#d01921] hover:text-[#a91118]';
                        remove.textContent = 'Usuń';
                        remove.addEventListener('click', () => {
                            selected.delete(externalId);
                            renderSelected();
                            renderResults(window.currentLegalQuestionResults || []);
                        });

                        const note = document.createElement('textarea');
                        note.className = 'mt-3 block min-h-20 w-full border border-slate-300 px-3 py-2 text-sm text-slate-950 focus:border-[#0d47a1] focus:ring-[#0d47a1]';
                        note.placeholder = 'Publiczne uzasadnienie prawne (opcjonalne)';
                        note.value = question.public_note || '';
                        note.addEventListener('input', () => {
                            question.public_note = note.value;
                        });

                        text.appendChild(id);
                        text.appendChild(prompt);
                        heading.appendChild(text);
                        heading.appendChild(remove);
                        item.appendChild(heading);
                        item.appendChild(note);
                        selectedList.appendChild(item);
                    });
                };

                const renderResults = (questions) => {
                    window.currentLegalQuestionResults = questions;
                    results.innerHTML = '';

                    if (!Array.isArray(questions) || questions.length === 0) {
                        const empty = document.createElement('p');
                        empty.className = 'px-4 py-6 text-sm text-slate-500';
                        empty.textContent = 'Brak pasujących pytań.';
                        results.appendChild(empty);

                        return;
                    }

                    questions.forEach((question) => {
                        const externalId = String(question.external_id || '');
                        const isSelected = selected.has(externalId);
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.disabled = Boolean(question.already_attached);
                        button.className = [
                            'block w-full px-4 py-3 text-left transition',
                            question.already_attached
                                ? 'cursor-not-allowed bg-slate-50 opacity-65'
                                : isSelected
                                    ? 'bg-blue-50'
                                    : 'hover:bg-slate-50',
                        ].join(' ');

                        const top = document.createElement('span');
                        top.className = 'flex items-center justify-between gap-3';

                        const id = document.createElement('span');
                        id.className = 'text-xs font-bold text-[#0d47a1]';
                        id.textContent = `Pytanie ${question.display_external_id || externalId}`;

                        const state = document.createElement('span');
                        state.className = question.already_attached
                            ? 'text-xs font-bold text-[#1f7a3a]'
                            : 'text-xs font-bold text-slate-500';
                        state.textContent = question.already_attached
                            ? 'Już w artykule'
                            : isSelected
                                ? 'Wybrane'
                                : 'Wybierz';

                        const prompt = document.createElement('span');
                        prompt.className = 'mt-1 block text-sm font-semibold leading-5 text-slate-950';
                        prompt.textContent = question.prompt || '';

                        const meta = document.createElement('span');
                        meta.className = 'mt-2 block text-xs text-slate-500';
                        meta.textContent = [question.category_code ? `Kat. ${question.category_code}` : '', question.topic_name || '']
                            .filter(Boolean)
                            .join(' · ');

                        top.appendChild(id);
                        top.appendChild(state);
                        button.appendChild(top);
                        button.appendChild(prompt);

                        if (meta.textContent) {
                            button.appendChild(meta);
                        }

                        button.addEventListener('click', () => {
                            if (question.already_attached) {
                                return;
                            }

                            if (selected.has(externalId)) {
                                selected.delete(externalId);
                            } else {
                                selected.set(externalId, { ...question, public_note: '' });
                            }

                            renderSelected();
                            renderResults(questions);
                        });

                        results.appendChild(button);
                    });
                };

                const search = async () => {
                    const query = searchInput?.value.trim() || '';

                    if (query.length < 2) {
                        results.innerHTML = '<p class="px-4 py-6 text-sm text-slate-500">Wpisz co najmniej 2 znaki.</p>';
                        setStatus('');

                        return;
                    }

                    const sequence = ++searchSequence;
                    const url = new URL(manager.getAttribute('data-search-url'), window.location.origin);
                    url.searchParams.set('q', query);
                    setStatus('Szukam pytań...');

                    try {
                        const response = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(payload.message || 'Nie udało się wyszukać pytań.');
                        }

                        if (sequence !== searchSequence) {
                            return;
                        }

                        renderResults(payload.data?.questions || []);
                        setStatus('');
                    } catch (error) {
                        setStatus(error instanceof Error ? error.message : 'Nie udało się wyszukać pytań.', 'error');
                    }
                };

                openButton?.addEventListener('click', () => setOpen(true));
                closeButtons.forEach((button) => button.addEventListener('click', () => {
                    if (!busy) {
                        setOpen(false);
                    }
                }));
                manager.addEventListener('click', (event) => {
                    if (event.target === manager && !busy) {
                        setOpen(false);
                    }
                });
                searchInput?.addEventListener('input', () => {
                    window.clearTimeout(searchTimeout);
                    searchTimeout = window.setTimeout(search, 250);
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !manager.classList.contains('hidden') && !busy) {
                        setOpen(false);
                    }
                });

                saveButton?.addEventListener('click', async () => {
                    if (selected.size === 0) {
                        setStatus('Wybierz co najmniej jedno pytanie.', 'error');

                        return;
                    }

                    if (!legalUnit?.value) {
                        setStatus('Wybierz dokładną podstawę prawną.', 'error');
                        legalUnit?.focus();

                        return;
                    }

                    const publicNotes = {};

                    selected.forEach((question, externalId) => {
                        publicNotes[externalId] = question.public_note?.trim() || null;
                    });

                    busy = true;
                    saveButton.disabled = true;
                    setStatus('Zapisuję powiązania...');

                    try {
                        const response = await fetch(manager.getAttribute('data-store-url'), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                external_ids: Array.from(selected.keys()),
                                legal_unit_id: legalUnit.value,
                                public_notes: publicNotes,
                            }),
                        });
                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            const validationMessage = Object.values(payload.errors || {}).flat()[0];
                            throw new Error(validationMessage || payload.message || 'Nie udało się zapisać powiązań.');
                        }

                        setStatus('Pytania zostały dodane. Odświeżam stronę...', 'success');
                        window.setTimeout(() => window.location.reload(), 300);
                    } catch (error) {
                        setStatus(error instanceof Error ? error.message : 'Nie udało się zapisać powiązań.', 'error');
                        busy = false;
                        saveButton.disabled = false;
                    }
                });

                document.querySelectorAll('[data-legal-question-remove]').forEach((button) => {
                    button.addEventListener('click', async () => {
                        const label = button.getAttribute('data-question-label') || 'To pytanie';

                        if (!window.confirm(`${label}: usunąć powiązanie z tym artykułem?`)) {
                            return;
                        }

                        button.disabled = true;
                        button.textContent = 'Usuwam...';

                        try {
                            const response = await fetch(button.getAttribute('data-delete-url'), {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                            });
                            const payload = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                throw new Error(payload.message || 'Nie udało się usunąć powiązania.');
                            }

                            window.location.reload();
                        } catch (error) {
                            window.alert(error instanceof Error ? error.message : 'Nie udało się usunąć powiązania.');
                            button.disabled = false;
                            button.textContent = 'Usuń powiązanie';
                        }
                    });
                });
            })();
        </script>
    @endpush
@endif

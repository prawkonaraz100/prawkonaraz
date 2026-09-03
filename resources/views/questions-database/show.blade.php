@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'site-shell')

@php
    $displayExternalId = (string) ($questionMeta['display_external_id'] ?? $questionMeta['external_id']);
    $primaryCategoryCode = $primaryCategory?->code;
    $answerLabel = (string) ($questionMeta['correct_option_label'] ?? '');
    $points = filled($questionMeta['points'] ?? null) ? (int) $questionMeta['points'] : null;
    $updatedLabel = $questionMeta['updated_at']?->format('d.m.Y') ?? '-';
    $sourceLabel = (string) ($questionMeta['source_label'] ?? 'gov.pl');
    $categoryUrl = $primaryCategory
        ? route('public.questions.category', $primaryCategory->slug)
        : route('public.questions.hub');
    $previousQuestion = $questionNavigation['previous'] ?? null;
    $nextQuestion = $questionNavigation['next'] ?? null;
    $hasPreviousQuestion = is_array($previousQuestion);
    $hasNextQuestion = is_array($nextQuestion);
    $currentPosition = $questionNavigation['position'] ?? null;
    $navigationTotal = (int) ($questionNavigation['total'] ?? 0);
    $nextQuestionUrl = $hasNextQuestion ? $nextQuestion['url'] : $categoryUrl;
    $previousQuestionUrl = $hasPreviousQuestion ? $previousQuestion['url'] : $categoryUrl;
    $prompt = $question['prompt_plain'] !== '' ? $question['prompt_plain'] : 'Pytanie '.$displayExternalId;
    $questionAudioPayload = is_array($questionAudio ?? null) ? $questionAudio : [];
    $questionPromptAudio = is_array($questionAudioPayload['question'] ?? null) ? $questionAudioPayload['question'] : null;
    $formatQuestionAudioTime = static function (mixed $value): string {
        if (! is_numeric($value) || (float) $value < 0) {
            return '0:00';
        }

        $totalSeconds = (int) floor((float) $value);
        $minutes = intdiv($totalSeconds, 60);
        $seconds = str_pad((string) ($totalSeconds % 60), 2, '0', STR_PAD_LEFT);

        return $minutes.':'.$seconds;
    };
    $questionPromptAudioDuration = is_array($questionPromptAudio)
        && is_numeric($questionPromptAudio['duration_seconds'] ?? null)
        && (float) $questionPromptAudio['duration_seconds'] > 0
            ? (float) $questionPromptAudio['duration_seconds']
            : null;
    $questionPromptAudioTimeLabel = '0:00 / '.($questionPromptAudioDuration !== null
        ? $formatQuestionAudioTime($questionPromptAudioDuration)
        : '--:--');
    $mediaAlt = (string) ($media['alt_text'] ?? 'Pytanie egzaminacyjne '.$displayExternalId);
    $canEditPublicExplanation = (bool) ($canEditPublicExplanation ?? false);
    $questionEditUrl = (string) ($questionEditUrl ?? '');
    $publicExplanationUpdateUrl = (string) ($publicExplanationUpdateUrl ?? '');
    $answerExplanationBodyRaw = (string) ($answerExplanation['body_raw'] ?? $answerExplanation['body_plain'] ?? '');
    $answerExplanationBodyHtml = (string) ($answerExplanation['body_html'] ?? '');
    $dontConfuseWithRaw = (string) ($answerExplanation['dont_confuse_with_raw'] ?? $answerExplanation['dont_confuse_with_plain'] ?? '');
    $dontConfuseWithHtml = (string) ($answerExplanation['dont_confuse_with_html'] ?? '');
    $examTrapRaw = (string) ($answerExplanation['exam_trap_raw'] ?? $answerExplanation['exam_trap_plain'] ?? '');
    $examTrapHtml = (string) ($answerExplanation['exam_trap_html'] ?? '');
    $answerExplanationIsPublic = ($answerExplanation['source'] ?? null) === 'public';
    $answerExplanationReviewedAt = $answerExplanation['last_reviewed_at'] ?? null;
    $answerExplanationAuthor = is_array($answerExplanation['author'] ?? null) ? $answerExplanation['author'] : null;
    $answerExplanationAuthorName = (string) ($answerExplanationAuthor['name'] ?? '');
    $answerExplanationAuthorSlug = (string) ($answerExplanationAuthor['slug'] ?? '');
    $answerExplanationAuthorIsPublic = (bool) ($answerExplanationAuthor['is_public'] ?? false);
    $commonMistakes = $answerExplanation['common_mistakes'] ?? [];
    $dontConfuseWithSignCards = is_array($answerExplanation['dont_confuse_with_signs'] ?? null)
        ? $answerExplanation['dont_confuse_with_signs']
        : [];
    $legalReferences = $legalReferences ?? collect();
    $canEditLegalReferences = (bool) ($canEditLegalReferences ?? false);
    $legalReferenceUpdateUrl = (string) ($legalReferenceUpdateUrl ?? '');
    $legalUnitSearchUrl = (string) ($legalUnitSearchUrl ?? '');
    $legalReferenceEditorOptions = $legalReferenceEditorOptions ?? [
        'legal_unit_current' => null,
        'legal_units' => [],
        'legal_topics' => [],
        'legal_content_pages' => [],
    ];
    $legalReferenceForEditor = $legalReferences->first();
    $legalReferenceDefaultVerifier = $legalReferenceDefaultVerifier ?? null;
    $legalReferenceId = (string) ($legalReferenceForEditor?->getKey() ?? '');
    $legalReferencePublicNote = (string) ($legalReferenceForEditor?->public_note ?? '');
    $legalReferenceLegalUnitId = (string) ($legalReferenceForEditor?->legal_unit_id ?? '');
    $legalReferenceLegalTopicId = (string) ($legalReferenceForEditor?->legal_topic_id ?? '');
    $legalReferenceContentPageId = (string) ($legalReferenceForEditor?->legal_content_page_id ?? '');
    $legalReferenceCurrentUnit = $legalReferenceEditorOptions['legal_unit_current'] ?? null;
    $legalReferenceCurrentUnitLabel = (string) ($legalReferenceCurrentUnit['label'] ?? '');
    $legalReferenceCurrentUnitStatus = (string) ($legalReferenceCurrentUnit['status_label'] ?? '');
    $questionAnswerStats = is_array($questionAnswerStats ?? null) ? $questionAnswerStats : [];
    $hasQuestionAnswerStats = (bool) ($questionAnswerStats['available'] ?? false);
    $questionAnswerStatsItems = is_array($questionAnswerStats['items'] ?? null)
        ? $questionAnswerStats['items']
        : [];
    $questionAnswerStatsTone = (string) ($questionAnswerStats['difficulty_tone'] ?? 'low');
    $questionAnswerStatsToneColor = match ($questionAnswerStatsTone) {
        'high' => '#b91c1c',
        'medium' => '#a16207',
        default => '#166534',
    };
    $hasReadableAnswerAudio = $answerExplanationBodyHtml !== ''
        || $dontConfuseWithHtml !== ''
        || $examTrapHtml !== ''
        || $commonMistakes !== [];
@endphp

@section('content')
    <section class="bg-white">
        <div class="site-shell pb-7 pt-5">
            <div class="border-b border-[#e9edf2] pb-5 md:pb-6">
                <div class="min-w-0">
                    <p class="text-[0.72rem] font-bold uppercase tracking-[0.02em] text-[#d01921]">
                        @if ($primaryCategoryCode)
                            Kategoria {{ $primaryCategoryCode }}
                        @else
                            Oficjalne pytanie egzaminacyjne
                        @endif
                    </p>
                    <h1 class="mt-3 max-w-6xl text-[1.75rem] font-bold leading-tight text-[#111827] md:text-[2rem]">
                        {{ $prompt }}
                    </h1>

                    <div class="mt-4 flex flex-wrap items-center gap-y-1 text-[0.92rem] leading-6 text-[#64748b]">
                        <span>
                            Numer <strong class="font-bold text-[#111827]">{{ $displayExternalId }}</strong>
                        </span>
                        <span aria-hidden="true" class="text-[#94a3b8]">&nbsp;•&nbsp;</span>
                        <span>
                            Typ: <strong class="font-bold text-[#111827]">{{ $questionMeta['type_label'] }}</strong>
                        </span>
                        <span aria-hidden="true" class="text-[#94a3b8]">&nbsp;•&nbsp;</span>
                        <span>
                            Źródło: <strong class="font-bold text-[#111827]">{{ $sourceLabel }}</strong>
                        </span>
                        <span aria-hidden="true" class="text-[#94a3b8]">&nbsp;•&nbsp;</span>
                        <span>
                            Aktualizacja pytania: <strong class="font-semibold text-[#64748b]">{{ $updatedLabel }}</strong>
                        </span>
                    </div>

                    @if (is_array($questionPromptAudio) && filled($questionPromptAudio['url'] ?? null))
                        <section
                            class="mt-5 max-w-3xl border border-[#dce3eb] bg-white px-4 py-3"
                            style="box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);"
                            data-question-audio
                            data-audio-url="{{ $questionPromptAudio['url'] }}"
                            data-audio-type="{{ $questionPromptAudio['encoding_format'] ?? 'audio/mpeg' }}"
                            @if ($questionPromptAudioDuration !== null)
                                data-audio-duration="{{ number_format($questionPromptAudioDuration, 3, '.', '') }}"
                            @endif
                            oncontextmenu="return false"
                        >
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[4px] bg-[#111827] text-white transition hover:bg-[#0f172a]"
                                    style="outline: none;"
                                    data-question-audio-toggle
                                    aria-label="Odtwórz treść pytania {{ $displayExternalId }}"
                                    aria-pressed="false"
                                >
                                    <svg class="h-5 w-5 translate-x-[1px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-question-audio-play-icon>
                                        <path d="M8 5.14v13.72c0 .78.86 1.25 1.52.83l10.78-6.86a.98.98 0 0 0 0-1.66L9.52 4.31A.99.99 0 0 0 8 5.14Z" />
                                    </svg>
                                    <svg class="hidden h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" data-question-audio-pause-icon>
                                        <path d="M7 5.5A1.5 1.5 0 0 1 8.5 4h1A1.5 1.5 0 0 1 11 5.5v13A1.5 1.5 0 0 1 9.5 20h-1A1.5 1.5 0 0 1 7 18.5v-13Zm6 0A1.5 1.5 0 0 1 14.5 4h1A1.5 1.5 0 0 1 17 5.5v13a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5v-13Z" />
                                    </svg>
                                </button>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-[0.72rem] font-bold uppercase tracking-[0.02em] text-[#d01921]">Audio pytania</p>
                                        <span class="shrink-0 text-[0.78rem] font-semibold tabular-nums text-[#64748b]" data-question-audio-time>{{ $questionPromptAudioTimeLabel }}</span>
                                    </div>
                                    <p class="mt-0.5 truncate text-[0.92rem] font-semibold leading-5 text-[#111827]" data-question-audio-state>
                                        Odsłuchaj treść pytania {{ $displayExternalId }}
                                    </p>
                                </div>
                            </div>
                            <audio
                                class="hidden"
                                data-question-audio-player
                                controlslist="nodownload noremoteplayback"
                                disableremoteplayback
                                oncontextmenu="return false"
                                preload="metadata"
                                aria-label="Odsłuchaj treść pytania {{ $displayExternalId }}"
                            ></audio>
                            <div class="mt-3" style="padding-left: 52px;" data-question-audio-controls>
                                <div class="relative h-4" data-question-audio-track>
                                    <div class="absolute left-0 top-1/2 h-1 w-full -translate-y-1/2 rounded-full bg-[#e2e8f0]" aria-hidden="true">
                                        <div
                                            class="h-full rounded-full bg-[#d01921]"
                                            style="width: 0%; transition: width 150ms ease-out;"
                                            data-question-audio-progress
                                        ></div>
                                    </div>
                                    <span
                                        class="pointer-events-none absolute left-0 top-1/2 h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full border border-white bg-[#d01921]"
                                        style="left: 0%; opacity: 0; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.18); transition: left 150ms ease-out, opacity 150ms ease-out;"
                                        data-question-audio-knob
                                        aria-hidden="true"
                                    ></span>
                                    <input
                                        type="range"
                                        min="0"
                                        max="1000"
                                        step="1"
                                        value="0"
                                        class="absolute inset-x-[-2px] top-0 h-4 w-[calc(100%+4px)] cursor-pointer opacity-0"
                                        data-question-audio-seek
                                        aria-label="Postęp odtwarzania audio pytania {{ $displayExternalId }}"
                                    >
                                    <span
                                        class="pointer-events-none absolute inset-x-[-2px] top-1/2 h-4 -translate-y-1/2 rounded-full border border-[#d01921] opacity-0"
                                        style="transition: opacity 150ms ease-out;"
                                        data-question-audio-focus-ring
                                        aria-hidden="true"
                                    ></span>
                                </div>
                            </div>
                        </section>
                    @endif

                </div>
            </div>

            <div class="mt-6 grid gap-6 min-[1180px]:grid-cols-[minmax(0,1.08fr)_minmax(420px,0.92fr)]">
                <section aria-label="Materiał z pytania">
                    @if ($canEditPublicExplanation && $questionEditUrl !== '')
                        <div class="mb-3 flex justify-end">
                            <a
                                href="{{ $questionEditUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                data-question-graphic-edit
                                class="inline-flex min-h-9 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white px-4 text-[0.82rem] font-bold text-[#111827] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]"
                            >
                                Edytuj grafikę
                            </a>
                        </div>
                    @endif
                    <div class="overflow-hidden border border-[#dce3eb] bg-[#f8fafc]">
                        @if (($media['kind'] ?? 'none') === 'image' && ! empty($media['url']))
                            <img
                                src="{{ $media['url'] }}"
                                alt="{{ $mediaAlt }}"
                                class="aspect-[16/8.25] w-full bg-white object-cover"
                                @if (! empty($media['width'])) width="{{ $media['width'] }}" @endif
                                @if (! empty($media['height'])) height="{{ $media['height'] }}" @endif
                                fetchpriority="high"
                            >
                        @elseif (($media['kind'] ?? 'none') === 'video' && ! empty($media['url']))
                            <video
                                controls
                                preload="metadata"
                                playsinline
                                poster="{{ $media['poster_url'] ?? '' }}"
                                aria-label="{{ $mediaAlt }}"
                                @if (! empty($media['width'])) width="{{ $media['width'] }}" @endif
                                @if (! empty($media['height'])) height="{{ $media['height'] }}" @endif
                                class="aspect-video w-full bg-[#111827]"
                            >
                                <source src="{{ $media['url'] }}" type="{{ $media['mime_type'] ?? 'video/mp4' }}">
                            </video>
                        @else
                            <div
                                class="relative flex aspect-[16/8.25] min-h-[260px] items-center justify-center overflow-hidden border border-[#dce3eb] bg-white px-8 py-10 text-center"
                                aria-label="Pytanie tekstowe bez wymaganego obrazu lub filmu"
                            >
                                <div class="absolute inset-x-0 top-0 h-1 bg-[#d01921]" aria-hidden="true"></div>
                                <div class="absolute inset-x-8 top-10 h-px bg-[#eef2f7]" aria-hidden="true"></div>
                                <div class="absolute inset-x-8 bottom-10 h-px bg-[#eef2f7]" aria-hidden="true"></div>

                                <div class="relative mx-auto max-w-[440px]">
                                    <img
                                        src="{{ asset('images/site-header-logo-20260728.png') }}"
                                        alt=""
                                        class="mx-auto h-14 w-auto opacity-95"
                                        loading="lazy"
                                        aria-hidden="true"
                                    >
                                    <p class="mt-6 text-xs font-semibold uppercase tracking-widest text-[#d01921]">
                                        Pytanie tekstowe
                                    </p>
                                    <p class="mt-2 text-xl font-semibold leading-tight text-[#111827]">
                                        Materiał jest kompletny
                                    </p>
                                    <p class="mt-3 text-sm leading-6 text-[#64748b]">
                                        To pytanie w oficjalnej bazie ma formę tekstową. Obraz lub film nie jest wymagany.
                                    </p>
                                    <p class="mt-1 text-sm leading-6 text-[#64748b]">
                                        Odpowiedz na podstawie treści pytania i dostępnych odpowiedzi.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($hasQuestionAnswerStats)
                        <section
                            id="answer-statistics"
                            class="mt-4 border border-[#dce3eb] bg-white p-5"
                            style="box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);"
                            aria-label="Statystyki odpowiedzi kursantów"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#f8fafc] text-[#d01921]">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M4 19V5" />
                                            <path d="M4 19h16" />
                                            <path d="M8 16v-5" />
                                            <path d="M12 16V8" />
                                            <path d="M16 16v-3" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <h2 class="text-[1rem] font-bold leading-6 text-[#111827]">Jak odpowiadali kursanci?</h2>
                                        <p class="mt-1 text-[0.86rem] leading-5 text-[#64748b]">
                                            Na podstawie {{ number_format((int) ($questionAnswerStats['sample_count'] ?? 0), 0, ',', ' ') }} odpowiedzi. {{ $questionAnswerStats['window_label'] ?? 'Ostatnie dane' }}.
                                        </p>
                                    </div>
                                </div>

                                <p class="shrink-0 text-[0.78rem] font-semibold leading-5 text-[#64748b]">
                                    {{ $questionAnswerStats['updated_label'] ?? '' }}
                                </p>
                            </div>

                            <div class="mt-5 space-y-4">
                                @foreach ($questionAnswerStatsItems as $statsItem)
                                    @php
                                        $statsPercent = max(0, min(100, (int) ($statsItem['percent'] ?? 0)));
                                        $statsIsCorrect = (bool) ($statsItem['is_correct'] ?? false);
                                    @endphp
                                    <div>
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex min-w-0 items-center gap-2">
                                                <span class="text-[0.88rem] font-bold uppercase text-[#111827]">{{ $statsItem['label'] ?? '' }}</span>
                                                @if ($statsIsCorrect)
                                                    <span class="rounded-full px-2 py-0.5 text-[0.68rem] font-semibold" style="background-color: #eaf7ee; color: #1f7a3b;">poprawna odpowiedź</span>
                                                @endif
                                            </div>
                                            <span class="text-[1rem] font-bold tabular-nums text-[#111827]">{{ $statsPercent }}%</span>
                                        </div>
                                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-[#edf1f5]">
                                            <div
                                                class="h-full rounded-full"
                                                style="width: {{ $statsPercent }}%; background-color: {{ $statsIsCorrect ? '#1f9d45' : '#d01921' }};"
                                            ></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-5 border-t border-[#e9edf2] pt-4">
                                <p class="text-[0.86rem] leading-5 text-[#64748b]">
                                    Poziom trudności:
                                    <strong class="font-bold" style="color: {{ $questionAnswerStatsToneColor }};">{{ $questionAnswerStats['difficulty_label'] ?? 'Niski' }}</strong>
                                </p>
                            </div>
                        </section>
                    @endif
                </section>

                <aside class="space-y-4">
                    <section class="border border-[#dce3eb] bg-white p-6">
                        <div class="flex items-start justify-between gap-4">
                            <h2 class="text-[0.9rem] font-bold uppercase tracking-[0.02em] text-[#111827]">Poprawna odpowiedź</h2>
                            @if ($points !== null)
                                <p class="text-right text-[0.86rem] font-semibold leading-5 text-[#111827]">
                                    Wartość na egzaminie: <span class="text-[#d01921]">{{ $points }} pkt</span>
                                </p>
                            @endif
                        </div>

                        <div class="mt-6 flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1f9d45] text-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6" />
                                </svg>
                            </span>
                            @if ($answerLabel !== '')
                                <p class="text-[1.85rem] font-bold uppercase leading-none text-[#1f9d45]">{{ $answerLabel }}</p>
                            @endif
                        </div>
                    </section>

                    <section
                        id="answer"
                        class="border border-[#dce3eb] bg-white p-6"
                        @if ($hasReadableAnswerAudio)
                            data-lesson-audio-root
                        @endif
                        @if ($canEditPublicExplanation)
                            data-public-explanation-editor
                            data-update-url="{{ $publicExplanationUpdateUrl }}"
                        @endif
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="text-[1rem] font-bold uppercase tracking-[0.02em] text-[#111827]">Wyjaśnienie</h2>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($hasReadableAnswerAudio)
                                    <button
                                        type="button"
                                        data-lesson-audio-play-all
                                        data-play-label="Odtwórz"
                                        data-stop-label="Stop"
                                        data-play-aria-label="Odtwórz całe wyjaśnienie pytania"
                                        data-stop-aria-label="Zatrzymaj odtwarzanie wyjaśnienia pytania"
                                        aria-label="Odtwórz całe wyjaśnienie pytania"
                                        title="Odtwórz całe wyjaśnienie pytania"
                                        aria-pressed="false"
                                        class="inline-flex h-8 min-h-8 items-center justify-center gap-1.5 rounded-[4px] bg-[#111827] px-3 text-[0.76rem] font-bold text-white transition hover:bg-[#0f172a] disabled:cursor-not-allowed disabled:bg-[#9ca3af]"
                                    >
                                        <svg class="h-3.5 w-3.5 translate-x-[1px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M8 5.14v13.72c0 .78.86 1.25 1.52.83l10.78-6.86a.98.98 0 0 0 0-1.66L9.52 4.31A.99.99 0 0 0 8 5.14Z" />
                                        </svg>
                                        <span data-lesson-audio-label>Odtwórz</span>
                                    </button>
                                @endif
                                @if ($canEditPublicExplanation)
                                    <button
                                        type="button"
                                        data-public-explanation-edit
                                        class="inline-flex min-h-9 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white px-4 text-[0.82rem] font-bold text-[#111827] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]"
                                    >
                                        Edytuj
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if ($hasReadableAnswerAudio)
                            <p data-lesson-audio-status class="mt-3 min-h-5 text-[0.82rem] font-semibold text-[#64748b]" role="status" aria-live="polite"></p>
                        @endif

                        @if ($answerExplanationBodyHtml !== '' || $canEditPublicExplanation)
                            <div
                                @if ($answerExplanationBodyHtml !== '')
                                    data-lesson-audio-section
                                    data-lesson-audio-title="Wyjaśnienie"
                                @endif
                                class="@if ($answerExplanationBodyHtml !== '') mt-4 @endif"
                            >
                                @if ($answerExplanationBodyHtml !== '')
                                    <div class="mb-3 flex justify-end">
                                        <button
                                            type="button"
                                            data-lesson-audio-section-button
                                            data-play-label="Przeczytaj tę sekcję"
                                            data-stop-label="Zatrzymaj czytanie"
                                            data-play-aria-label="Przeczytaj sekcję Wyjaśnienie"
                                            data-stop-aria-label="Zatrzymaj czytanie sekcji Wyjaśnienie"
                                            aria-label="Przeczytaj sekcję Wyjaśnienie"
                                            title="Przeczytaj sekcję Wyjaśnienie"
                                            aria-pressed="false"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc] hover:text-[#111827] disabled:cursor-not-allowed disabled:text-[#94a3b8]"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M11 5 6 9H3v6h3l5 4V5Z" />
                                                <path d="M15.5 8.5a5 5 0 0 1 0 7" />
                                                <path d="M18.5 5.5a9 9 0 0 1 0 13" />
                                            </svg>
                                            <span class="sr-only" data-lesson-audio-label>Przeczytaj tę sekcję</span>
                                        </button>
                                    </div>
                                @endif
                                <div
                                    data-public-explanation-display
                                    @if ($answerExplanationBodyHtml !== '')
                                        data-lesson-audio-content
                                    @endif
                                    class="content-prose text-[0.95rem] leading-7 text-[#111827] @if ($answerExplanationBodyHtml === '') hidden @endif"
                                >
                                    {!! $answerExplanationBodyHtml !!}
                                </div>
                            </div>
                        @endif

                        @if ($answerExplanationBodyHtml === '')
                            <p
                                data-public-explanation-empty
                                class="mt-4 text-[0.95rem] leading-7 text-[#111827]"
                            >
                                To pytanie nie ma jeszcze osobnego rozwinięcia. Poprawna odpowiedź została zaznaczona wyżej, a pełne wyjaśnienia sukcesywnie rozbudowujemy w całej bazie.
                            </p>
                        @endif

                        <p
                            data-public-explanation-reviewed
                            class="mt-3 text-[0.78rem] leading-5 text-[#64748b] @if (! ($answerExplanationIsPublic && $answerExplanationReviewedAt)) hidden @endif"
                        >
                            @if ($answerExplanationIsPublic && $answerExplanationReviewedAt)
                                <span data-public-explanation-reviewed-text>Aktualizacja wyjaśnienia: {{ $answerExplanationReviewedAt->format('d.m.Y') }}</span>
                                @if ($answerExplanationAuthorName !== '')
                                    <span data-public-explanation-author>
                                        <span aria-hidden="true"> · </span>
                                        @if ($answerExplanationAuthorIsPublic && $answerExplanationAuthorSlug !== '')
                                            <a
                                                href="{{ route('content-authors.show', $answerExplanationAuthorSlug) }}"
                                                class="font-semibold text-[#4b5563] underline decoration-[#cbd5e1] underline-offset-2 transition hover:text-[#d01921] hover:decoration-[#d01921]"
                                            >{{ $answerExplanationAuthorName }}</a>
                                        @else
                                            <span>{{ $answerExplanationAuthorName }}</span>
                                        @endif
                                    </span>
                                @endif
                            @endif
                        </p>

                        @if ($dontConfuseWithHtml !== '' || $canEditPublicExplanation)
                            <section
                                data-public-explanation-dont-confuse
                                @if ($dontConfuseWithHtml !== '')
                                    data-lesson-audio-section
                                    data-lesson-audio-title="Nie pomyl z"
                                @endif
                                class="mt-5 border border-[#dce3eb] bg-[#f8fafc] p-4 @if ($dontConfuseWithHtml === '') hidden @endif"
                                aria-labelledby="dont-confuse-heading"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <h3 id="dont-confuse-heading" class="text-[0.92rem] font-bold uppercase tracking-[0.02em] text-[#111827]">
                                        Nie pomyl z
                                    </h3>
                                    @if ($dontConfuseWithHtml !== '')
                                        <button
                                            type="button"
                                            data-lesson-audio-section-button
                                            data-play-label="Przeczytaj tę sekcję"
                                            data-stop-label="Zatrzymaj czytanie"
                                            data-play-aria-label="Przeczytaj sekcję Nie pomyl z"
                                            data-stop-aria-label="Zatrzymaj czytanie sekcji Nie pomyl z"
                                            aria-label="Przeczytaj sekcję Nie pomyl z"
                                            title="Przeczytaj sekcję Nie pomyl z"
                                            aria-pressed="false"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white text-[#334155] transition hover:border-[#c4cfdb] hover:bg-white hover:text-[#111827] disabled:cursor-not-allowed disabled:text-[#94a3b8]"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M11 5 6 9H3v6h3l5 4V5Z" />
                                                <path d="M15.5 8.5a5 5 0 0 1 0 7" />
                                                <path d="M18.5 5.5a9 9 0 0 1 0 13" />
                                            </svg>
                                            <span class="sr-only" data-lesson-audio-label>Przeczytaj tę sekcję</span>
                                        </button>
                                    @endif
                                </div>
                                @if ($dontConfuseWithSignCards !== [])
                                    <div class="mt-3 grid gap-3 @if (count($dontConfuseWithSignCards) > 1) sm:grid-cols-2 @endif">
                                        @foreach ($dontConfuseWithSignCards as $dontConfuseWithSign)
                                            <x-public.sign-badge :sign="$dontConfuseWithSign" variant="comparison" />
                                        @endforeach
                                    </div>
                                @endif
                                <div
                                    data-public-explanation-dont-confuse-display
                                    @if ($dontConfuseWithHtml !== '')
                                        data-lesson-audio-content
                                    @endif
                                    class="content-prose mt-3 text-[0.92rem] leading-7 text-[#111827]"
                                >
                                    {!! $dontConfuseWithHtml !!}
                                </div>
                            </section>
                        @endif

                        @if ($examTrapHtml !== '' || $canEditPublicExplanation)
                            <div
                                data-public-explanation-trap
                                @if ($examTrapHtml !== '')
                                    data-lesson-audio-section
                                    data-lesson-audio-title="Haczyk egzaminacyjny"
                                @endif
                                class="mt-5 border-l-4 border-[#d01921] bg-[#fff7f7] px-4 py-3 @if ($examTrapHtml === '') hidden @endif"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-[0.82rem] font-bold uppercase tracking-[0.02em] text-[#d01921]">
                                        Haczyk egzaminacyjny
                                    </p>
                                    @if ($examTrapHtml !== '')
                                        <button
                                            type="button"
                                            data-lesson-audio-section-button
                                            data-play-label="Przeczytaj tę sekcję"
                                            data-stop-label="Zatrzymaj czytanie"
                                            data-play-aria-label="Przeczytaj sekcję Haczyk egzaminacyjny"
                                            data-stop-aria-label="Zatrzymaj czytanie sekcji Haczyk egzaminacyjny"
                                            aria-label="Przeczytaj sekcję Haczyk egzaminacyjny"
                                            title="Przeczytaj sekcję Haczyk egzaminacyjny"
                                            aria-pressed="false"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-[4px] border border-[#f0b6ba] bg-white text-[#a91118] transition hover:border-[#d01921] hover:bg-[#fffafa] disabled:cursor-not-allowed disabled:text-[#cbd5e1]"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M11 5 6 9H3v6h3l5 4V5Z" />
                                                <path d="M15.5 8.5a5 5 0 0 1 0 7" />
                                                <path d="M18.5 5.5a9 9 0 0 1 0 13" />
                                            </svg>
                                            <span class="sr-only" data-lesson-audio-label>Przeczytaj tę sekcję</span>
                                        </button>
                                    @endif
                                </div>
                                <div
                                    data-public-explanation-trap-display
                                    @if ($examTrapHtml !== '')
                                        data-lesson-audio-content
                                    @endif
                                    class="content-prose mt-2 text-[0.92rem] leading-7 text-[#111827]"
                                >
                                    {!! $examTrapHtml !!}
                                </div>
                            </div>
                        @endif

                        @if ($canEditPublicExplanation)
                            <p data-public-explanation-status class="mt-3 min-h-5 text-[0.82rem] font-semibold text-[#64748b]" role="status"></p>

                            <form data-public-explanation-form class="mt-5 hidden border-t border-[#e9edf2] pt-5">
                                <label for="public-explanation-body" class="block text-[0.86rem] font-bold uppercase tracking-[0.02em] text-[#111827]">
                                    Publiczne wyjaśnienie SEO
                                </label>
                                <textarea
                                    id="public-explanation-body"
                                    data-public-explanation-body
                                    rows="8"
                                    class="mt-3 min-h-[220px] w-full resize-y border border-[#cfd8e3] bg-white px-4 py-3 text-[0.95rem] leading-7 text-[#111827] outline-none transition focus:border-[#d01921] focus:ring-2 focus:ring-[#d01921]/15"
                                >{{ $answerExplanationBodyRaw }}</textarea>

                                <label for="public-explanation-dont-confuse-with" class="mt-5 block text-[0.86rem] font-bold uppercase tracking-[0.02em] text-[#111827]">
                                    Nie pomyl z
                                </label>
                                <textarea
                                    id="public-explanation-dont-confuse-with"
                                    data-public-explanation-dont-confuse-with
                                    rows="4"
                                    class="mt-3 min-h-[120px] w-full resize-y border border-[#cfd8e3] bg-white px-4 py-3 text-[0.95rem] leading-7 text-[#111827] outline-none transition focus:border-[#d01921] focus:ring-2 focus:ring-[#d01921]/15"
                                >{{ $dontConfuseWithRaw }}</textarea>

                                <label for="public-explanation-exam-trap" class="mt-5 block text-[0.86rem] font-bold uppercase tracking-[0.02em] text-[#111827]">
                                    Haczyk egzaminacyjny
                                </label>
                                <textarea
                                    id="public-explanation-exam-trap"
                                    data-public-explanation-exam-trap
                                    rows="4"
                                    class="mt-3 min-h-[120px] w-full resize-y border border-[#cfd8e3] bg-white px-4 py-3 text-[0.95rem] leading-7 text-[#111827] outline-none transition focus:border-[#d01921] focus:ring-2 focus:ring-[#d01921]/15"
                                >{{ $examTrapRaw }}</textarea>

                                <div class="mt-5" data-public-explanation-mistakes-editor>
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-[0.86rem] font-bold uppercase tracking-[0.02em] text-[#111827]">
                                            Najczęstsze błędy
                                        </p>
                                        <button
                                            type="button"
                                            data-public-explanation-mistake-add
                                            class="inline-flex min-h-9 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white px-4 text-[0.82rem] font-bold text-[#111827] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]"
                                        >
                                            Dodaj błąd
                                        </button>
                                    </div>

                                    <div class="mt-3 space-y-4" data-public-explanation-mistakes-list>
                                        @foreach ($commonMistakes as $commonMistake)
                                            <div class="border border-[#e2e8f0] bg-[#fbfcfe] p-4" data-public-explanation-mistake>
                                                <div class="flex items-start justify-between gap-3">
                                                    <p class="text-[0.82rem] font-bold text-[#64748b]" data-public-explanation-mistake-label>Błąd {{ $loop->iteration }}</p>
                                                    <button
                                                        type="button"
                                                        data-public-explanation-mistake-remove
                                                        class="inline-flex min-h-8 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white px-3 text-[0.78rem] font-bold text-[#64748b] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc] hover:text-[#111827]"
                                                    >
                                                        Usuń
                                                    </button>
                                                </div>
                                                <label class="mt-3 block text-[0.78rem] font-bold uppercase tracking-[0.02em] text-[#64748b]">
                                                    Treść błędu
                                                </label>
                                                <input
                                                    type="text"
                                                    data-public-explanation-mistake-title
                                                    value="{{ $commonMistake['title'] }}"
                                                    class="mt-2 min-h-10 w-full border border-[#cfd8e3] bg-white px-3 text-[0.9rem] text-[#111827] outline-none transition focus:border-[#d01921] focus:ring-2 focus:ring-[#d01921]/15"
                                                >
                                                <label class="mt-3 block text-[0.78rem] font-bold uppercase tracking-[0.02em] text-[#64748b]">
                                                    Wyjaśnienie błędu
                                                </label>
                                                <textarea
                                                    data-public-explanation-mistake-explanation
                                                    rows="3"
                                                    class="mt-2 min-h-[92px] w-full resize-y border border-[#cfd8e3] bg-white px-3 py-2 text-[0.9rem] leading-6 text-[#111827] outline-none transition focus:border-[#d01921] focus:ring-2 focus:ring-[#d01921]/15"
                                                >{{ $commonMistake['explanation'] }}</textarea>
                                            </div>
                                        @endforeach
                                    </div>

                                    <template data-public-explanation-mistake-template>
                                        <div class="border border-[#e2e8f0] bg-[#fbfcfe] p-4" data-public-explanation-mistake>
                                            <div class="flex items-start justify-between gap-3">
                                                <p class="text-[0.82rem] font-bold text-[#64748b]" data-public-explanation-mistake-label>Błąd</p>
                                                <button
                                                    type="button"
                                                    data-public-explanation-mistake-remove
                                                    class="inline-flex min-h-8 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white px-3 text-[0.78rem] font-bold text-[#64748b] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc] hover:text-[#111827]"
                                                >
                                                    Usuń
                                                </button>
                                            </div>
                                            <label class="mt-3 block text-[0.78rem] font-bold uppercase tracking-[0.02em] text-[#64748b]">
                                                Treść błędu
                                            </label>
                                            <input
                                                type="text"
                                                data-public-explanation-mistake-title
                                                class="mt-2 min-h-10 w-full border border-[#cfd8e3] bg-white px-3 text-[0.9rem] text-[#111827] outline-none transition focus:border-[#d01921] focus:ring-2 focus:ring-[#d01921]/15"
                                            >
                                            <label class="mt-3 block text-[0.78rem] font-bold uppercase tracking-[0.02em] text-[#64748b]">
                                                Wyjaśnienie błędu
                                            </label>
                                            <textarea
                                                data-public-explanation-mistake-explanation
                                                rows="3"
                                                class="mt-2 min-h-[92px] w-full resize-y border border-[#cfd8e3] bg-white px-3 py-2 text-[0.9rem] leading-6 text-[#111827] outline-none transition focus:border-[#d01921] focus:ring-2 focus:ring-[#d01921]/15"
                                            ></textarea>
                                        </div>
                                    </template>
                                </div>
                                <div class="mt-4 flex flex-wrap justify-end gap-3">
                                    <button
                                        type="button"
                                        data-public-explanation-cancel
                                        class="inline-flex min-h-10 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white px-5 text-[0.86rem] font-bold text-[#111827] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]"
                                    >
                                        Anuluj
                                    </button>
                                    <button
                                        type="submit"
                                        data-public-explanation-save
                                        class="inline-flex min-h-10 items-center justify-center rounded-[4px] bg-[#d01921] px-5 text-[0.86rem] font-bold text-white transition hover:bg-[#b9151c] disabled:cursor-not-allowed disabled:bg-[#d99a9e]"
                                    >
                                        Zapisz
                                    </button>
                                </div>
                            </form>
                        @endif

                        @if ($commonMistakes !== [])
                            <section
                                class="mt-7 border-t border-[#e9edf2] pt-6"
                                aria-labelledby="common-mistakes-heading"
                                data-lesson-audio-section
                                data-lesson-audio-title="Najczęstsze błędy"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <h3 id="common-mistakes-heading" class="text-[1rem] font-bold uppercase tracking-[0.02em] text-[#111827]">
                                        Najczęstsze błędy
                                    </h3>
                                    <button
                                        type="button"
                                        data-lesson-audio-section-button
                                        data-play-label="Przeczytaj tę sekcję"
                                        data-stop-label="Zatrzymaj czytanie"
                                        data-play-aria-label="Przeczytaj sekcję Najczęstsze błędy"
                                        data-stop-aria-label="Zatrzymaj czytanie sekcji Najczęstsze błędy"
                                        aria-label="Przeczytaj sekcję Najczęstsze błędy"
                                        title="Przeczytaj sekcję Najczęstsze błędy"
                                        aria-pressed="false"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white text-[#334155] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc] hover:text-[#111827] disabled:cursor-not-allowed disabled:text-[#94a3b8]"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M11 5 6 9H3v6h3l5 4V5Z" />
                                            <path d="M15.5 8.5a5 5 0 0 1 0 7" />
                                            <path d="M18.5 5.5a9 9 0 0 1 0 13" />
                                        </svg>
                                        <span class="sr-only" data-lesson-audio-label>Przeczytaj tę sekcję</span>
                                    </button>
                                </div>

                                <ol class="mt-4 divide-y divide-[#e9edf2] border-y border-[#e9edf2]" data-lesson-audio-content>
                                    @foreach ($commonMistakes as $commonMistake)
                                        <li class="flex items-start gap-3 py-4">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#fff1f2] text-[0.8rem] font-bold text-[#d01921]" aria-hidden="true">
                                                {{ $loop->iteration }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-[0.92rem] font-bold leading-6 text-[#111827]">
                                                    {!! $commonMistake['title_html'] ?? e($commonMistake['title'] ?? '') !!}
                                                </h4>
                                                <p class="mt-1 text-[0.9rem] leading-6 text-[#4b5563]">
                                                    {!! $commonMistake['explanation_html'] ?? e($commonMistake['explanation'] ?? '') !!}
                                                </p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </section>
                        @endif

                        @if ($legalReferences->isNotEmpty() || $canEditLegalReferences)
                            <div
                                class="mt-7 border-t border-[#e9edf2] pt-6"
                                @if ($canEditLegalReferences)
                                    data-legal-reference-editor
                                    data-update-url="{{ $legalReferenceUpdateUrl }}"
                                @endif
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <h3 class="text-[1rem] font-bold uppercase tracking-[0.02em] text-[#111827]">Uzasadnienie prawne</h3>
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($canEditLegalReferences)
                                            <button
                                                type="button"
                                                data-legal-reference-edit
                                                class="inline-flex min-h-9 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white px-4 text-[0.82rem] font-bold text-[#111827] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]"
                                            >
                                                {{ $legalReferenceForEditor ? 'Edytuj' : 'Dodaj' }}
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                @if ($legalReferences->isNotEmpty())
                                    <div class="mt-4 space-y-5">
                                        @foreach ($legalReferences as $legalReference)
                                            <div>
                                                @if ($legalReference->public_note)
                                                    <p class="text-[0.95rem] leading-7 text-[#111827]">{{ $legalReference->public_note }}</p>
                                                @endif
                                                <div class="mt-3 border-l-4 border-[#d01921] bg-[#f8fafc] px-4 py-3">
                                                    <p class="text-[0.9rem] font-bold text-[#111827]">
                                                        {{ $legalReference->legalUnit->legalAct->short_title ?: $legalReference->legalUnit->legalAct->title }}
                                                        {{ $legalReference->legalUnit->label }}
                                                    </p>
                                                    <p class="mt-1 text-[0.88rem] leading-6 text-[#4b5563]">{{ $legalReference->legalUnit->title }}</p>
                                                    @if ($legalReference->legalUnit->official_excerpt)
                                                        <div class="mt-3 border-t border-[#e2e8f0] pt-3">
                                                            <p class="text-[0.76rem] font-bold uppercase tracking-[0.02em] text-[#64748b]">Treść przepisu</p>
                                                            <p class="mt-2 text-[0.88rem] leading-6 text-[#111827]">{{ $legalReference->legalUnit->official_excerpt }}</p>
                                                        </div>
                                                    @endif
                                                    @if ($legalReference->contentPage?->isPubliclyVisible())
                                                        <a href="{{ route('public.regulations.show', $legalReference->contentPage->slug) }}" class="mt-3 inline-flex items-center gap-2 text-[0.88rem] font-bold text-[#d01921] transition hover:text-[#a91118]">
                                                            Zobacz opracowanie przepisu <span aria-hidden="true">→</span>
                                                        </a>
                                                    @endif
                                                </div>
                                                @if ($legalReference->verified_at)
                                                    @php
                                                        $legalReferenceVerifier = $legalReference->verifier ?: $legalReferenceDefaultVerifier;
                                                        $legalReferenceVerifierName = (string) ($legalReferenceVerifier?->name ?: 'Jakub Wiśniewski');
                                                    @endphp
                                                    <p class="mt-2 text-[0.78rem] leading-5 text-[#64748b]">
                                                        Weryfikacja podstawy prawnej: {{ $legalReference->verified_at->format('d.m.Y') }} · @if ($legalReferenceVerifier?->isPubliclyVisible())<a
                                                                href="{{ route('content-authors.show', $legalReferenceVerifier->slug) }}"
                                                                class="font-semibold text-[#4b5563] underline decoration-[#cbd5e1] underline-offset-2 transition hover:text-[#d01921] hover:decoration-[#d01921]"
                                                            >{{ $legalReferenceVerifierName }}</a>@else{{ $legalReferenceVerifierName }}@endif
                                                    </p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif ($canEditLegalReferences)
                                    <p class="mt-4 text-[0.95rem] leading-7 text-[#64748b]">Brak uzasadnienia prawnego.</p>
                                @endif

                                @if ($canEditLegalReferences)
                                    <p data-legal-reference-status class="mt-3 min-h-5 text-[0.82rem] font-semibold text-[#64748b]" role="status"></p>

                                    <form data-legal-reference-form class="mt-5 hidden border-t border-[#e9edf2] pt-5">
                                        <input type="hidden" data-legal-reference-id value="{{ $legalReferenceId }}">

                                        <label for="legal-reference-public-note" class="block text-[0.86rem] font-bold uppercase tracking-[0.02em] text-[#111827]">
                                            Opis prawny <span class="normal-case text-[#64748b]">(opcjonalnie)</span>
                                        </label>
                                        <textarea
                                            id="legal-reference-public-note"
                                            data-legal-reference-public-note
                                            rows="4"
                                            class="mt-3 min-h-[150px] w-full resize-y border border-[#cfd8e3] bg-white px-4 py-3 text-[0.95rem] leading-7 text-[#111827] outline-none transition focus:border-[#d01921] focus:ring-2 focus:ring-[#d01921]/15"
                                        >{{ $legalReferencePublicNote }}</textarea>

                                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                                            <label class="block text-[0.82rem] font-bold uppercase tracking-[0.02em] text-[#111827]">
                                                Przepis
                                                <input type="hidden" data-legal-reference-unit value="{{ $legalReferenceLegalUnitId }}">
                                                <input
                                                    id="legal-reference-unit-search"
                                                    type="search"
                                                    data-legal-reference-unit-search
                                                    data-search-url="{{ $legalUnitSearchUrl }}"
                                                    value="{{ $legalReferenceCurrentUnitLabel }}"
                                                    autocomplete="off"
                                                    placeholder="Wpisz np. art. 26 ust. 6"
                                                    class="mt-2 min-h-11 w-full border border-[#cfd8e3] bg-white px-3 text-[0.92rem] font-semibold normal-case text-[#111827] outline-none transition focus:border-[#d01921] focus:ring-2 focus:ring-[#d01921]/15"
                                                >
                                                <p data-legal-reference-unit-selected class="mt-2 min-h-5 text-[0.78rem] font-semibold normal-case leading-5 text-[#64748b]">
                                                    @if ($legalReferenceCurrentUnitLabel !== '')
                                                        Wybrano: {{ $legalReferenceCurrentUnitLabel }}{{ $legalReferenceCurrentUnitStatus !== '' ? ' · '.$legalReferenceCurrentUnitStatus : '' }}
                                                    @else
                                                        Wpisz minimum 2 znaki i wybierz zweryfikowany przepis.
                                                    @endif
                                                </p>
                                                <div data-legal-reference-unit-results class="mt-2 hidden max-h-72 overflow-y-auto border border-[#dce3eb] bg-white shadow-sm"></div>
                                            </label>

                                            <label class="block text-[0.82rem] font-bold uppercase tracking-[0.02em] text-[#111827]">
                                                Temat
                                                <select data-legal-reference-topic class="mt-2 min-h-11 w-full border border-[#cfd8e3] bg-white px-3 text-[0.92rem] font-semibold normal-case text-[#111827] outline-none transition focus:border-[#d01921] focus:ring-2 focus:ring-[#d01921]/15">
                                                    <option value="">Bez tematu</option>
                                                    @foreach (($legalReferenceEditorOptions['legal_topics'] ?? []) as $option)
                                                        <option value="{{ $option['id'] }}" @selected((string) $option['id'] === $legalReferenceLegalTopicId)>
                                                            {{ $option['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        </div>

                                        <label class="mt-4 block text-[0.82rem] font-bold uppercase tracking-[0.02em] text-[#111827]">
                                            Artykuł
                                            <select data-legal-reference-page class="mt-2 min-h-11 w-full border border-[#cfd8e3] bg-white px-3 text-[0.92rem] font-semibold normal-case text-[#111827] outline-none transition focus:border-[#d01921] focus:ring-2 focus:ring-[#d01921]/15">
                                                <option value="">Bez linku do artykułu</option>
                                                @foreach (($legalReferenceEditorOptions['legal_content_pages'] ?? []) as $option)
                                                    <option value="{{ $option['id'] }}" @selected((string) $option['id'] === $legalReferenceContentPageId)>
                                                        {{ $option['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <div class="mt-4 flex flex-wrap justify-end gap-3">
                                            <button
                                                type="button"
                                                data-legal-reference-cancel
                                                class="inline-flex min-h-10 items-center justify-center rounded-[4px] border border-[#dce3eb] bg-white px-5 text-[0.86rem] font-bold text-[#111827] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]"
                                            >
                                                Anuluj
                                            </button>
                                            <button
                                                type="submit"
                                                data-legal-reference-save
                                                class="inline-flex min-h-10 items-center justify-center rounded-[4px] bg-[#d01921] px-5 text-[0.86rem] font-bold text-white transition hover:bg-[#b9151c] disabled:cursor-not-allowed disabled:bg-[#d99a9e]"
                                            >
                                                Zapisz
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @if (! empty($referenceSign))
                            <div class="mt-7 border-t border-[#e9edf2] pt-6">
                                <h3 class="text-[1rem] font-bold uppercase tracking-[0.02em] text-[#111827]">Znak drogowy widoczny w pytaniu</h3>
                                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
                                    @if (! empty($referenceSign['code']))
                                        <x-public.sign-badge :sign="$referenceSign" variant="detail" />
                                    @elseif (! empty($referenceSign['image_url']))
                                        <div class="flex h-24 w-24 shrink-0 items-center justify-center">
                                            <img
                                                src="{{ $referenceSign['image_url'] }}"
                                                alt="{{ $referenceSign['alt_text'] ?? 'Znak drogowy' }}"
                                                class="h-full w-full object-contain"
                                            >
                                        </div>
                                    @else
                                        <div class="flex h-24 w-24 shrink-0 items-center justify-center border border-dashed border-[#dce3eb] text-center">
                                            <span class="text-center text-[0.72rem] text-[#64748b]">Brak podglądu znaku.</span>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-[1rem] font-bold leading-6 text-[#111827]">{{ $referenceSign['title'] }}</p>
                                        @if (! empty($referenceSign['intro']))
                                            <p class="mt-2 text-[0.9rem] leading-6 text-[#4b5563]">{{ $referenceSign['intro'] }}</p>
                                        @endif
                                        @if (! empty($referenceSign['url']))
                                            <a href="{{ $referenceSign['url'] }}" class="mt-3 inline-flex items-center gap-2 text-[0.88rem] font-bold text-[#d01921] transition hover:text-[#a91118]">
                                                {{ $referenceSign['link_label'] ?? 'Zobacz opis znaku' }} <span aria-hidden="true">→</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($categories->isNotEmpty())
                            <div class="mt-7">
                                <h3 class="text-[1rem] font-bold uppercase tracking-[0.02em] text-[#111827]">Zakres kategorii</h3>
                                <p class="mt-4 text-[0.95rem] leading-7 text-[#111827]">
                                    @if ($categories->count() === 1)
                                        To pytanie występuje wyłącznie w kategorii {{ $categories->first()->code }}. Zakres wynika z przypisania w oficjalnej bazie pytań egzaminacyjnych.
                                    @else
                                        To pytanie występuje w {{ $categories->count() }} kategoriach egzaminacyjnych, ponieważ dotyczy ogólnych zasad ruchu drogowego, które obowiązują niezależnie od typu pojazdu.
                                    @endif
                                </p>
                                <div class="mt-4 flex flex-wrap gap-3">
                                    @foreach ($categories as $category)
                                        <a href="{{ route('public.questions.category', $category->slug) }}" class="inline-flex min-h-9 min-w-11 items-center justify-center border border-[#dce3eb] bg-white px-3 text-[0.92rem] font-semibold text-[#111827] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]">
                                            {{ $category->code }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </section>
                </aside>
            </div>

            <x-public.related-question-groups
                :groups="$relatedQuestionGroups"
                :total="$relatedQuestionTotal"
                :category-url="$categoryUrl"
                :topic="$relatedQuestionTopic"
                :preview="$relatedQuestionPreview"
            />

            <nav
                class="mt-5 grid gap-4 pb-7 sm:grid-cols-[220px_minmax(0,1fr)_280px] sm:items-center"
                aria-label="Nawigacja po pytaniach"
                data-public-question-keyboard-navigation
                data-previous-url="{{ $hasPreviousQuestion ? $previousQuestionUrl : '' }}"
                data-next-url="{{ $hasNextQuestion ? $nextQuestionUrl : '' }}"
            >
                <a href="{{ $previousQuestionUrl }}" class="inline-flex min-h-12 items-center justify-center gap-3 border border-[#dce3eb] bg-white px-6 text-[0.95rem] font-bold text-[#111827] transition hover:border-[#c4cfdb] hover:bg-[#f8fafc]">
                    <span aria-hidden="true">←</span>
                    Wróć do bazy
                </a>

                <div class="text-center text-[1.05rem] font-bold text-[#111827]">
                    @if ($currentPosition !== null && $navigationTotal > 0)
                        {{ number_format($currentPosition, 0, ',', ' ') }} / {{ number_format($navigationTotal, 0, ',', ' ') }}
                    @endif
                </div>

                <a href="{{ $nextQuestionUrl }}" class="inline-flex min-h-12 items-center justify-center gap-4 rounded-[4px] bg-[#d01921] px-7 text-[0.95rem] font-bold text-white transition hover:bg-[#b9151c]">
                    Następne pytanie
                    <span aria-hidden="true">→</span>
                </a>
            </nav>
        </div>
    </section>

    <x-public.sign-preview-modal />
@endsection

@push('scripts')
    <script>
        (() => {
            const navigation = document.querySelector('[data-public-question-keyboard-navigation]');

            if (!navigation) {
                return;
            }

            const interactiveSelector = [
                'a',
                'button',
                'input',
                'textarea',
                'select',
                'summary',
                '[contenteditable="true"]',
                '[contenteditable=""]',
                '[role="button"]',
                '[role="slider"]',
                '[data-question-audio]',
            ].join(',');

            const shouldIgnoreKeyboardNavigation = (event) => {
                if (
                    event.defaultPrevented
                    || event.altKey
                    || event.ctrlKey
                    || event.metaKey
                    || event.shiftKey
                ) {
                    return true;
                }

                return event.target instanceof Element
                    && event.target.closest(interactiveSelector) !== null;
            };

            document.addEventListener('keydown', (event) => {
                if (shouldIgnoreKeyboardNavigation(event)) {
                    return;
                }

                const targetUrl = event.key === 'ArrowRight'
                    ? navigation.dataset.nextUrl
                    : event.key === 'ArrowLeft'
                        ? navigation.dataset.previousUrl
                        : '';

                if (!targetUrl) {
                    return;
                }

                event.preventDefault();
                window.location.assign(targetUrl);
            });
        })();
    </script>
@endpush

@if (is_array($questionPromptAudio) && filled($questionPromptAudio['url'] ?? null))
    @push('scripts')
        <style>
            [data-question-audio-toggle]:focus,
            [data-question-audio-seek]:focus {
                outline: none;
            }

            [data-question-audio-toggle]:focus-visible {
                box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px rgba(208, 25, 33, 0.55);
            }

            [data-question-audio-seek]:focus-visible + [data-question-audio-focus-ring],
            [data-question-audio-track]:focus-within [data-question-audio-focus-ring] {
                opacity: 1;
            }
        </style>
        <script>
            (() => {
                const roots = Array.from(document.querySelectorAll('[data-question-audio]'));

                if (roots.length === 0) {
                    return;
                }

                const formatTime = (value) => {
                    if (!Number.isFinite(value) || value < 0) {
                        return '0:00';
                    }

                    const totalSeconds = Math.floor(value);
                    const minutes = Math.floor(totalSeconds / 60);
                    const seconds = String(totalSeconds % 60).padStart(2, '0');

                    return `${minutes}:${seconds}`;
                };

                const pauseOthers = (currentAudio) => {
                    document.querySelectorAll('[data-question-audio-player]').forEach((audio) => {
                        if (audio !== currentAudio && !audio.paused) {
                            audio.pause();
                        }
                    });
                };

                roots.forEach((root) => {
                    const audio = root.querySelector('[data-question-audio-player]');
                    const toggle = root.querySelector('[data-question-audio-toggle]');
                    const seek = root.querySelector('[data-question-audio-seek]');
                    const state = root.querySelector('[data-question-audio-state]');
                    const time = root.querySelector('[data-question-audio-time]');
                    const progress = root.querySelector('[data-question-audio-progress]');
                    const knob = root.querySelector('[data-question-audio-knob]');
                    const playIcon = root.querySelector('[data-question-audio-play-icon]');
                    const pauseIcon = root.querySelector('[data-question-audio-pause-icon]');
                    const audioUrl = root.getAttribute('data-audio-url') || '';
                    const audioType = root.getAttribute('data-audio-type') || 'audio/mpeg';
                    const knownDuration = Number(root.getAttribute('data-audio-duration') || '0');
                    const fallbackDuration = Number.isFinite(knownDuration) && knownDuration > 0
                        ? knownDuration
                        : 0;
                    const playLabel = toggle?.getAttribute('aria-label') || 'Odtwórz audio';
                    const pauseLabel = playLabel.replace('Odtwórz', 'Zatrzymaj');

                    if (!audio || !toggle || !seek || audioUrl === '') {
                        return;
                    }

                    audio.controls = false;
                    audio.preload = 'metadata';
                    audio.setAttribute('controlslist', 'nodownload noremoteplayback');
                    audio.setAttribute('disableremoteplayback', '');
                    audio.setAttribute('type', audioType);

                    const preventMediaMenu = (event) => {
                        event.preventDefault();
                    };

                    root.addEventListener('contextmenu', preventMediaMenu);
                    root.addEventListener('dragstart', preventMediaMenu);
                    audio.addEventListener('contextmenu', preventMediaMenu);
                    audio.addEventListener('dragstart', preventMediaMenu);

                    const ensureSource = () => {
                        if (audio.dataset.sourceReady === '1') {
                            return;
                        }

                        audio.src = audioUrl;
                        audio.dataset.sourceReady = '1';
                        audio.load();
                    };

                    const setPlaying = (isPlaying) => {
                        toggle.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
                        toggle.setAttribute('aria-label', isPlaying ? pauseLabel : playLabel);
                        playIcon?.classList.toggle('hidden', isPlaying);
                        pauseIcon?.classList.toggle('hidden', !isPlaying);
                    };

                    const updateProgress = () => {
                        const metadataDuration = Number.isFinite(audio.duration) ? audio.duration : 0;
                        const duration = metadataDuration > 0 ? metadataDuration : fallbackDuration;
                        const current = Number.isFinite(audio.currentTime) ? audio.currentTime : 0;
                        const percent = duration > 0
                            ? Math.min(100, Math.max(0, (current / duration) * 100))
                            : 0;

                        progress?.style.setProperty('width', `${percent}%`);
                        knob?.style.setProperty('left', `${percent}%`);
                        knob?.style.setProperty('opacity', duration > 0 ? '1' : '0');

                        if (duration > 0) {
                            seek.value = String(Math.round((current / duration) * 1000));
                            if (time) {
                                time.textContent = `${formatTime(current)} / ${formatTime(duration)}`;
                            }

                            return;
                        }

                        seek.value = '0';
                        if (time) {
                            time.textContent = `${formatTime(current)} / --:--`;
                        }
                    };

                    toggle.addEventListener('click', async () => {
                        if (!audio.paused && !audio.ended) {
                            audio.pause();

                            return;
                        }

                        pauseOthers(audio);
                        ensureSource();

                        if (state) {
                            state.textContent = 'Ładowanie...';
                        }

                        try {
                            await audio.play();
                        } catch (error) {
                            setPlaying(false);
                            if (state) {
                                state.textContent = 'Nie udało się odtworzyć audio.';
                            }
                        }
                    });

                    seek.addEventListener('input', () => {
                        const duration = Number.isFinite(audio.duration) ? audio.duration : 0;

                        if (duration <= 0) {
                            return;
                        }

                        audio.currentTime = (Number(seek.value) / 1000) * duration;
                        updateProgress();
                    });

                    audio.addEventListener('play', () => {
                        setPlaying(true);
                        if (state) {
                            state.textContent = 'Odtwarzanie';
                        }
                    });

                    audio.addEventListener('pause', () => {
                        setPlaying(false);
                        if (state && !audio.ended) {
                            state.textContent = 'Pauza';
                        }
                    });

                    audio.addEventListener('ended', () => {
                        setPlaying(false);
                        if (state) {
                            state.textContent = 'Odtwórz ponownie';
                        }
                        updateProgress();
                    });

                    audio.addEventListener('loadedmetadata', updateProgress);
                    audio.addEventListener('durationchange', updateProgress);
                    audio.addEventListener('timeupdate', updateProgress);
                    audio.addEventListener('error', () => {
                        setPlaying(false);
                        if (state) {
                            state.textContent = 'Nie udało się odtworzyć audio.';
                        }
                    });

                    ensureSource();
                    updateProgress();
                });
            })();
        </script>
    @endpush
@endif

@if ($canEditPublicExplanation)
    @push('scripts')
        <script>
            (() => {
                const editor = document.querySelector('[data-public-explanation-editor]');

                if (!editor) {
                    return;
                }

                const editButton = editor.querySelector('[data-public-explanation-edit]');
                const form = editor.querySelector('[data-public-explanation-form]');
                const textarea = editor.querySelector('[data-public-explanation-body]');
                const dontConfuseWithTextarea = editor.querySelector('[data-public-explanation-dont-confuse-with]');
                const examTrapTextarea = editor.querySelector('[data-public-explanation-exam-trap]');
                const addMistakeButton = editor.querySelector('[data-public-explanation-mistake-add]');
                const mistakeList = editor.querySelector('[data-public-explanation-mistakes-list]');
                const mistakeTemplate = editor.querySelector('[data-public-explanation-mistake-template]');
                const cancelButton = editor.querySelector('[data-public-explanation-cancel]');
                const saveButton = editor.querySelector('[data-public-explanation-save]');
                const display = editor.querySelector('[data-public-explanation-display]');
                const dontConfuseWithBox = editor.querySelector('[data-public-explanation-dont-confuse]');
                const dontConfuseWithDisplay = editor.querySelector('[data-public-explanation-dont-confuse-display]');
                const examTrapBox = editor.querySelector('[data-public-explanation-trap]');
                const examTrapDisplay = editor.querySelector('[data-public-explanation-trap-display]');
                const emptyState = editor.querySelector('[data-public-explanation-empty]');
                const reviewedLabel = editor.querySelector('[data-public-explanation-reviewed]');
                const status = editor.querySelector('[data-public-explanation-status]');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                let lastSavedBody = textarea?.value || '';
                let lastSavedDontConfuseWith = dontConfuseWithTextarea?.value || '';
                let lastSavedExamTrap = examTrapTextarea?.value || '';

                const renumberMistakes = () => {
                    if (!mistakeList) {
                        return;
                    }

                    mistakeList.querySelectorAll('[data-public-explanation-mistake]').forEach((row, index) => {
                        const label = row.querySelector('[data-public-explanation-mistake-label]') || row.querySelector('p');

                        if (label) {
                            label.textContent = `Błąd ${index + 1}`;
                        }
                    });
                };

                const readMistakes = () => {
                    if (!mistakeList) {
                        return [];
                    }

                    return Array.from(mistakeList.querySelectorAll('[data-public-explanation-mistake]'))
                        .map((row) => ({
                            title: row.querySelector('[data-public-explanation-mistake-title]')?.value.trim() || '',
                            explanation: row.querySelector('[data-public-explanation-mistake-explanation]')?.value.trim() || '',
                        }))
                        .filter((mistake) => mistake.title !== '' || mistake.explanation !== '');
                };

                const createMistakeRow = (mistake = {}) => {
                    const row = mistakeTemplate?.content
                        ?.cloneNode(true)
                        ?.querySelector('[data-public-explanation-mistake]');

                    if (!row) {
                        return null;
                    }

                    const titleInput = row.querySelector('[data-public-explanation-mistake-title]');
                    const explanationTextarea = row.querySelector('[data-public-explanation-mistake-explanation]');

                    if (titleInput) {
                        titleInput.value = mistake.title || '';
                    }

                    if (explanationTextarea) {
                        explanationTextarea.value = mistake.explanation || '';
                    }

                    return row;
                };

                const renderMistakes = (mistakes) => {
                    if (!mistakeList) {
                        return;
                    }

                    mistakeList.innerHTML = '';
                    mistakes.forEach((mistake) => {
                        const row = createMistakeRow(mistake);

                        if (row) {
                            mistakeList.appendChild(row);
                        }
                    });
                    renumberMistakes();
                };

                const setStatus = (message, tone = 'neutral') => {
                    if (!status) {
                        return;
                    }

                    status.textContent = message;
                    status.classList.toggle('text-[#d01921]', tone === 'error');
                    status.classList.toggle('text-[#1f7a3a]', tone === 'success');
                    status.classList.toggle('text-[#64748b]', tone === 'neutral');
                };

                const setReviewedLabel = (lastReviewedLabel, author = null) => {
                    if (!reviewedLabel || !lastReviewedLabel) {
                        return;
                    }

                    reviewedLabel.textContent = '';

                    const reviewedText = document.createElement('span');
                    reviewedText.setAttribute('data-public-explanation-reviewed-text', '');
                    reviewedText.textContent = `Aktualizacja wyjaśnienia: ${lastReviewedLabel}`;
                    reviewedLabel.appendChild(reviewedText);

                    if (author?.name) {
                        const authorWrapper = document.createElement('span');
                        authorWrapper.setAttribute('data-public-explanation-author', '');

                        const separator = document.createElement('span');
                        separator.setAttribute('aria-hidden', 'true');
                        separator.textContent = ' · ';
                        authorWrapper.appendChild(separator);

                        if (author.is_public && author.url) {
                            const authorLink = document.createElement('a');
                            authorLink.href = author.url;
                            authorLink.className = 'font-semibold text-[#4b5563] underline decoration-[#cbd5e1] underline-offset-2 transition hover:text-[#d01921] hover:decoration-[#d01921]';
                            authorLink.textContent = author.name;
                            authorWrapper.appendChild(authorLink);
                        } else {
                            const authorName = document.createElement('span');
                            authorName.textContent = author.name;
                            authorWrapper.appendChild(authorName);
                        }

                        reviewedLabel.appendChild(authorWrapper);
                    }

                    reviewedLabel.classList.remove('hidden');
                };

                let lastSavedCommonMistakes = readMistakes();
                renumberMistakes();

                const showForm = () => {
                    form?.classList.remove('hidden');
                    editButton?.classList.add('hidden');
                    setStatus('');
                    textarea?.focus();
                };

                const hideForm = () => {
                    form?.classList.add('hidden');
                    editButton?.classList.remove('hidden');
                    setStatus('');
                };

                editButton?.addEventListener('click', showForm);
                cancelButton?.addEventListener('click', () => {
                    if (textarea) {
                        textarea.value = lastSavedBody;
                    }

                    if (dontConfuseWithTextarea) {
                        dontConfuseWithTextarea.value = lastSavedDontConfuseWith;
                    }

                    if (examTrapTextarea) {
                        examTrapTextarea.value = lastSavedExamTrap;
                    }

                    renderMistakes(lastSavedCommonMistakes);
                    hideForm();
                });

                addMistakeButton?.addEventListener('click', () => {
                    const row = createMistakeRow();

                    if (!row || !mistakeList) {
                        return;
                    }

                    mistakeList.appendChild(row);
                    renumberMistakes();
                    row.querySelector('[data-public-explanation-mistake-title]')?.focus();
                });

                mistakeList?.addEventListener('click', (event) => {
                    const target = event.target instanceof Element ? event.target : null;
                    const removeButton = target?.closest('[data-public-explanation-mistake-remove]');

                    if (!removeButton) {
                        return;
                    }

                    removeButton.closest('[data-public-explanation-mistake]')?.remove();
                    renumberMistakes();
                });

                form?.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const body = textarea?.value.trim() || '';
                    const dontConfuseWith = dontConfuseWithTextarea?.value.trim() || '';
                    const examTrap = examTrapTextarea?.value.trim() || '';
                    const commonMistakes = readMistakes();

                    if (body === '') {
                        setStatus('Wpisz publiczne wyjaśnienie przed zapisem.', 'error');

                        return;
                    }

                    if (commonMistakes.some((mistake) => mistake.title === '' || mistake.explanation === '')) {
                        setStatus('Uzupełnij treść i wyjaśnienie każdego błędu albo usuń pustą pozycję.', 'error');

                        return;
                    }

                    if (commonMistakes.length > 8) {
                        setStatus('Zostaw maksymalnie 8 najczęstszych błędów.', 'error');

                        return;
                    }

                    saveButton?.setAttribute('disabled', 'disabled');
                    setStatus('Zapisuję...');

                    try {
                        const response = await fetch(editor.getAttribute('data-update-url'), {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                body,
                                dont_confuse_with: dontConfuseWith,
                                exam_trap: examTrap,
                                common_mistakes: commonMistakes,
                            }),
                        });
                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(payload.message || 'Nie udało się zapisać wyjaśnienia.');
                        }

                        const explanation = payload.data?.explanation || {};
                        lastSavedBody = explanation.body || body;
                        lastSavedDontConfuseWith = explanation.dont_confuse_with || dontConfuseWith;
                        lastSavedExamTrap = explanation.exam_trap || examTrap;
                        lastSavedCommonMistakes = Array.isArray(explanation.common_mistakes)
                            ? explanation.common_mistakes
                            : commonMistakes;

                        if (textarea) {
                            textarea.value = lastSavedBody;
                        }

                        if (dontConfuseWithTextarea) {
                            dontConfuseWithTextarea.value = lastSavedDontConfuseWith;
                        }

                        if (examTrapTextarea) {
                            examTrapTextarea.value = lastSavedExamTrap;
                        }

                        renderMistakes(lastSavedCommonMistakes);

                        if (display) {
                            display.innerHTML = explanation.body_html || '';
                            display.classList.toggle('hidden', !explanation.body_html);
                        }

                        if (dontConfuseWithBox && dontConfuseWithDisplay) {
                            dontConfuseWithDisplay.innerHTML = explanation.dont_confuse_with_html || '';
                            dontConfuseWithBox.classList.toggle('hidden', !explanation.dont_confuse_with_html);
                        }

                        if (examTrapBox && examTrapDisplay) {
                            examTrapDisplay.innerHTML = explanation.exam_trap_html || '';
                            examTrapBox.classList.toggle('hidden', !explanation.exam_trap_html);
                        }

                        emptyState?.classList.add('hidden');

                        setReviewedLabel(explanation.last_reviewed_label, explanation.author);

                        hideForm();
                        setStatus('Zapisano publiczne wyjaśnienie. Odświeżam stronę...', 'success');
                        window.setTimeout(() => window.location.reload(), 250);
                    } catch (error) {
                        setStatus(error instanceof Error ? error.message : 'Nie udało się zapisać wyjaśnienia.', 'error');
                    } finally {
                        saveButton?.removeAttribute('disabled');
                    }
                });
            })();
        </script>
    @endpush
@endif

@if ($canEditLegalReferences)
    @push('scripts')
        <script>
            (() => {
                const editor = document.querySelector('[data-legal-reference-editor]');

                if (!editor) {
                    return;
                }

                const editButton = editor.querySelector('[data-legal-reference-edit]');
                const form = editor.querySelector('[data-legal-reference-form]');
                const referenceId = editor.querySelector('[data-legal-reference-id]');
                const publicNote = editor.querySelector('[data-legal-reference-public-note]');
                const legalUnit = editor.querySelector('[data-legal-reference-unit]');
                const legalUnitSearch = editor.querySelector('[data-legal-reference-unit-search]');
                const legalUnitSelected = editor.querySelector('[data-legal-reference-unit-selected]');
                const legalUnitResults = editor.querySelector('[data-legal-reference-unit-results]');
                const legalTopic = editor.querySelector('[data-legal-reference-topic]');
                const legalPage = editor.querySelector('[data-legal-reference-page]');
                const cancelButton = editor.querySelector('[data-legal-reference-cancel]');
                const saveButton = editor.querySelector('[data-legal-reference-save]');
                const status = editor.querySelector('[data-legal-reference-status]');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                let selectedLegalUnitLabel = legalUnitSearch?.value || '';
                let lastSavedState = {
                    publicNote: publicNote?.value || '',
                    legalUnit: legalUnit?.value || '',
                    legalUnitLabel: legalUnitSearch?.value || '',
                    legalTopic: legalTopic?.value || '',
                    legalPage: legalPage?.value || '',
                };
                let legalUnitSearchTimeout = null;
                let legalUnitSearchSequence = 0;

                const setStatus = (message, tone = 'neutral') => {
                    if (!status) {
                        return;
                    }

                    status.textContent = message;
                    status.classList.toggle('text-[#d01921]', tone === 'error');
                    status.classList.toggle('text-[#1f7a3a]', tone === 'success');
                    status.classList.toggle('text-[#64748b]', tone === 'neutral');
                };

                const showForm = () => {
                    form?.classList.remove('hidden');
                    editButton?.classList.add('hidden');
                    setStatus('');
                    publicNote?.focus();
                };

                const hideForm = () => {
                    form?.classList.add('hidden');
                    editButton?.classList.remove('hidden');
                    setStatus('');
                };

                const setSelectedLegalUnitText = (message) => {
                    if (!legalUnitSelected) {
                        return;
                    }

                    legalUnitSelected.textContent = message;
                };

                const hideLegalUnitResults = () => {
                    legalUnitResults?.classList.add('hidden');

                    if (legalUnitResults) {
                        legalUnitResults.innerHTML = '';
                    }
                };

                const selectLegalUnit = (unit) => {
                    if (!unit?.can_select) {
                        setStatus('Ten przepis jest w katalogu, ale ma status do review. Najpierw trzeba go zweryfikować.', 'error');

                        return;
                    }

                    if (legalUnit) {
                        legalUnit.value = String(unit.id || '');
                    }

                    selectedLegalUnitLabel = unit.label || '';

                    if (legalUnitSearch) {
                        legalUnitSearch.value = selectedLegalUnitLabel;
                    }

                    setSelectedLegalUnitText(`Wybrano: ${selectedLegalUnitLabel} · ${unit.status_label || unit.status || 'zweryfikowany'}`);
                    hideLegalUnitResults();
                    setStatus('');
                };

                const renderLegalUnitResults = (items) => {
                    if (!legalUnitResults) {
                        return;
                    }

                    legalUnitResults.innerHTML = '';
                    legalUnitResults.classList.remove('hidden');

                    if (!Array.isArray(items) || items.length === 0) {
                        const empty = document.createElement('p');
                        empty.className = 'px-3 py-3 text-[0.84rem] font-semibold text-[#64748b]';
                        empty.textContent = 'Brak pasujących przepisów.';
                        legalUnitResults.appendChild(empty);

                        return;
                    }

                    items.forEach((unit) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = [
                            'block w-full border-b border-[#edf1f5] px-3 py-3 text-left transition last:border-b-0',
                            unit.can_select ? 'hover:bg-[#f8fafc]' : 'cursor-not-allowed bg-[#f8fafc] opacity-70',
                        ].join(' ');
                        button.disabled = !unit.can_select;

                        const title = document.createElement('span');
                        title.className = 'block text-[0.88rem] font-bold text-[#111827]';
                        title.textContent = unit.label || 'Bez etykiety';

                        const meta = document.createElement('span');
                        meta.className = unit.can_select
                            ? 'mt-1 block text-[0.76rem] font-bold uppercase tracking-[0.02em] text-[#1f7a3a]'
                            : 'mt-1 block text-[0.76rem] font-bold uppercase tracking-[0.02em] text-[#d01921]';
                        meta.textContent = unit.can_select ? 'zweryfikowany - można wybrać' : 'do review - widoczne w katalogu, bez zapisu';

                        button.appendChild(title);
                        button.appendChild(meta);

                        if (unit.official_excerpt) {
                            const excerpt = document.createElement('span');
                            excerpt.className = 'mt-1 block text-[0.8rem] font-semibold leading-5 text-[#64748b]';
                            excerpt.textContent = unit.official_excerpt;
                            button.appendChild(excerpt);
                        }

                        button.addEventListener('click', () => selectLegalUnit(unit));
                        legalUnitResults.appendChild(button);
                    });
                };

                const searchLegalUnits = async () => {
                    const query = legalUnitSearch?.value.trim() || '';
                    const searchUrl = legalUnitSearch?.getAttribute('data-search-url') || '';

                    if (query !== selectedLegalUnitLabel && legalUnit) {
                        legalUnit.value = '';
                        setSelectedLegalUnitText('Wybierz zweryfikowany przepis z wyników wyszukiwania.');
                    }

                    if (query.length < 2 || searchUrl === '') {
                        hideLegalUnitResults();

                        return;
                    }

                    const sequence = ++legalUnitSearchSequence;
                    const url = new URL(searchUrl, window.location.origin);
                    url.searchParams.set('q', query);

                    try {
                        const response = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const payload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(payload.message || 'Nie udało się wyszukać przepisów.');
                        }

                        if (sequence !== legalUnitSearchSequence) {
                            return;
                        }

                        renderLegalUnitResults(payload.data?.legal_units || []);
                    } catch (error) {
                        setStatus(error instanceof Error ? error.message : 'Nie udało się wyszukać przepisów.', 'error');
                    }
                };

                editButton?.addEventListener('click', showForm);
                cancelButton?.addEventListener('click', () => {
                    if (publicNote) {
                        publicNote.value = lastSavedState.publicNote;
                    }

                    if (legalUnit) {
                        legalUnit.value = lastSavedState.legalUnit;
                    }

                    selectedLegalUnitLabel = lastSavedState.legalUnitLabel;

                    if (legalUnitSearch) {
                        legalUnitSearch.value = lastSavedState.legalUnitLabel;
                    }

                    setSelectedLegalUnitText(lastSavedState.legalUnitLabel
                        ? `Wybrano: ${lastSavedState.legalUnitLabel}`
                        : 'Wpisz minimum 2 znaki i wybierz zweryfikowany przepis.');
                    hideLegalUnitResults();

                    if (legalTopic) {
                        legalTopic.value = lastSavedState.legalTopic;
                    }

                    if (legalPage) {
                        legalPage.value = lastSavedState.legalPage;
                    }

                    hideForm();
                });

                legalUnitSearch?.addEventListener('input', () => {
                    window.clearTimeout(legalUnitSearchTimeout);
                    legalUnitSearchTimeout = window.setTimeout(searchLegalUnits, 250);
                });

                legalUnitSearch?.addEventListener('focus', () => {
                    if ((legalUnitSearch.value.trim() || '').length >= 2) {
                        searchLegalUnits();
                    }
                });

                document.addEventListener('click', (event) => {
                    if (!legalUnitResults || !legalUnitSearch) {
                        return;
                    }

                    if (event.target instanceof Node && (legalUnitResults.contains(event.target) || legalUnitSearch.contains(event.target))) {
                        return;
                    }

                    hideLegalUnitResults();
                });

                form?.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const payload = {
                        reference_id: referenceId?.value || null,
                        public_note: publicNote?.value.trim() || null,
                        legal_unit_id: legalUnit?.value || '',
                        legal_topic_id: legalTopic?.value || null,
                        legal_content_page_id: legalPage?.value || null,
                    };

                    if (payload.legal_unit_id === '') {
                        setStatus('Wybierz zweryfikowany przepis.', 'error');

                        return;
                    }

                    saveButton?.setAttribute('disabled', 'disabled');
                    setStatus('Zapisuję...');

                    try {
                        const response = await fetch(editor.getAttribute('data-update-url'), {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(payload),
                        });
                        const responsePayload = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(responsePayload.message || 'Nie udało się zapisać uzasadnienia prawnego.');
                        }

                        const reference = responsePayload.data?.reference || {};

                        if (referenceId && reference.id) {
                            referenceId.value = reference.id;
                        }

                        lastSavedState = {
                            publicNote: payload.public_note,
                            legalUnit: payload.legal_unit_id,
                            legalUnitLabel: selectedLegalUnitLabel,
                            legalTopic: payload.legal_topic_id,
                            legalPage: payload.legal_content_page_id || '',
                        };

                        hideForm();
                        setStatus('Zapisano uzasadnienie prawne. Odświeżam stronę...', 'success');
                        window.setTimeout(() => window.location.reload(), 250);
                    } catch (error) {
                        setStatus(error instanceof Error ? error.message : 'Nie udało się zapisać uzasadnienia prawnego.', 'error');
                    } finally {
                        saveButton?.removeAttribute('disabled');
                    }
                });
            })();
        </script>
    @endpush
@endif

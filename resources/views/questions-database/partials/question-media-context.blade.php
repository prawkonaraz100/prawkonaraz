<section class="question-detail__media-context" aria-label="Kontekst pytania i statystyki">
                    @if (! empty($referenceSign))
                        @include('questions-database.partials.question-reference-sign')
                    @endif

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

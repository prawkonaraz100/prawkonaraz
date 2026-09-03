@props(['entries'])

<ul class="divide-y divide-[#e9edf2]">
    @foreach ($entries as $entry)
        @php
            $relatedQuestion = $entry['item'];
            $relatedUrl = $relatedQuestion['canonical_url'] ?? $relatedQuestion['url'];
        @endphp

        @if ($entry['starts_group'])
            <li class="bg-[#f8fafc] px-5 py-2.5 sm:px-6">
                <h3 class="text-[0.7rem] font-bold uppercase tracking-[0.08em] text-[#64748b]">
                    {{ $entry['group_label'] }}
                </h3>
            </li>
        @endif

        <li class="group/related-question" data-related-question-id="{{ $relatedQuestion['display_external_id'] ?? $relatedQuestion['external_id'] }}">
            <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-x-3 gap-y-2 px-5 py-4 sm:grid-cols-[104px_minmax(0,1fr)_96px_18px] sm:items-start sm:gap-4 sm:px-6">
                <span class="text-[0.78rem] font-semibold text-[#64748b]">
                    Pytanie {{ $relatedQuestion['display_external_id'] ?? $relatedQuestion['external_id'] }}
                </span>

                <div class="col-span-2 min-w-0 sm:col-span-1">
                    <div class="flex items-start gap-3 sm:gap-4">
                        @if (! empty($relatedQuestion['reference_sign']))
                            <span class="w-[120px] shrink-0 sm:w-[128px]" data-related-question-media>
                                <x-public.sign-badge :sign="$relatedQuestion['reference_sign']" variant="list" />
                            </span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <a
                                href="{{ $relatedUrl }}"
                                class="block text-[0.92rem] font-semibold leading-6 text-[#111827] transition-colors duration-150 hover:text-[#d01921] group-hover/related-question:text-[#d01921] focus-visible:text-[#d01921] focus-visible:outline-none"
                            >
                                {{ $relatedQuestion['prompt_plain'] }}
                            </a>

                            @if (! empty($relatedQuestion['relation_description_html']))
                                <span class="content-prose mt-1 block text-[0.8rem] font-normal leading-5 text-[#64748b]">
                                    {!! $relatedQuestion['relation_description_html'] !!}
                                </span>
                            @elseif (! empty($relatedQuestion['relation_description_plain']))
                                <span class="mt-1 block text-[0.8rem] font-normal leading-5 text-[#64748b]">
                                    {{ $relatedQuestion['relation_description_plain'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <span class="col-start-2 row-start-1 text-right text-[0.78rem] font-semibold text-[#64748b] sm:col-start-auto sm:row-start-auto">
                    @if (! empty($relatedQuestion['category_code']))
                        Kat. {{ $relatedQuestion['category_code'] }}
                    @endif
                </span>

                <span class="hidden text-right text-[1.1rem] text-[#64748b] transition-transform duration-150 group-hover/related-question:translate-x-0.5 sm:block" aria-hidden="true">›</span>
            </div>
        </li>
    @endforeach
</ul>

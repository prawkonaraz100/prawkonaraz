@extends('layouts.public-content')

@php
    $imageUrl = $signImageUrl ?? $meta['preload_image'] ?? null;
    $detailSections = [
        [
            'title' => 'Znaczenie',
            'body' => $sign->meaning,
            'icon' => 'book',
        ],
        [
            'title' => 'Gdzie występuje',
            'body' => $sign->placement,
            'icon' => 'pin',
        ],
        [
            'title' => 'Jak powinien zachować się kierowca',
            'body' => $sign->driver_behavior,
            'icon' => 'wheel',
        ],
        [
            'title' => 'Najczęstsze błędy',
            'body' => $sign->common_mistakes,
            'icon' => 'alert',
        ],
        [
            'title' => 'Podstawa prawna',
            'body' => $sign->legal_summary,
            'icon' => 'scale',
            'link_url' => $sign->legal_reference_url,
            'link_label' => $sign->legal_reference_label ?: 'Zobacz źródło',
        ],
        [
            'title' => 'Mandat i konsekwencje',
            'body' => $sign->fine_summary,
            'icon' => 'coin',
        ],
    ];
@endphp

@section('content')
    <section class="content-band">
        <div class="content-shell pb-8 pt-5 md:pb-9 md:pt-7">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_410px] lg:items-center">
                <div>
                    <p class="text-xs font-bold uppercase text-red-600">{{ $sign->category->name }}</p>
                    <h1 class="mt-5 max-w-3xl text-4xl font-bold tracking-normal text-slate-950 md:text-[42px] md:leading-[1.12]">
                        {{ $sign->publicTitle() }}
                    </h1>

                    @if ($sign->intro_definition)
                        <p class="mt-5 max-w-2xl text-[15px] leading-7 text-slate-700 md:text-base">
                            {{ $sign->intro_definition }}
                        </p>
                    @endif

                    <dl class="mt-7 grid max-w-xl grid-cols-3 gap-6 text-sm">
                        <div>
                            <dt class="text-slate-500">Autor</dt>
                            <dd class="mt-2 font-bold text-slate-950">
                                <a href="{{ route('content-authors.show', $sign->author->slug) }}" class="hover:text-red-600">
                                    {{ $sign->author->name }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Publikacja</dt>
                            <dd class="mt-2 font-bold text-slate-950">{{ $sign->published_at?->format('d.m.Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Aktualizacja</dt>
                            <dd class="mt-2 font-bold text-slate-950">{{ $sign->updated_at->format('d.m.Y') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="flex min-h-[210px] items-center justify-center overflow-visible pb-5 md:pb-6 lg:min-h-[250px] lg:pb-8">
                    @if ($imageUrl)
                        <img
                            src="{{ $imageUrl }}"
                            alt="{{ $sign->publicImageAlt() }}"
                            class="h-auto max-h-[210px] w-full max-w-[280px] object-contain md:max-h-[230px] md:max-w-[320px] lg:max-h-[250px] lg:max-w-[350px]"
                            width="{{ $sign->image_width ?: 1200 }}"
                            height="{{ $sign->image_height ?: 1200 }}"
                            fetchpriority="high"
                        >
                    @else
                        <div class="flex aspect-square w-56 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white text-sm text-slate-500">
                            Brak obrazu znaku
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell pb-8 pt-10 md:pb-9 md:pt-12">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.35fr)_minmax(360px,1fr)]">
                <section>
                    @foreach ($detailSections as $section)
                        @if (filled($section['body'] ?? null))
                            <article class="grid gap-5 border-t border-slate-100 py-7 first:border-t-0 md:grid-cols-[64px_minmax(0,1fr)]">
                                <div class="flex h-14 w-14 items-center justify-center text-red-600">
                                    @switch($section['icon'])
                                        @case('book')
                                            <svg aria-hidden="true" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                                                <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z" />
                                                <path d="M8 2v15" />
                                            </svg>
                                            @break

                                        @case('pin')
                                            <svg aria-hidden="true" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 21s7-6.1 7-12A7 7 0 0 0 5 9c0 5.9 7 12 7 12z" />
                                                <circle cx="12" cy="9" r="2.4" />
                                            </svg>
                                            @break

                                        @case('wheel')
                                            <svg aria-hidden="true" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="9" />
                                                <circle cx="12" cy="12" r="2.5" />
                                                <path d="M12 14.5V21" />
                                                <path d="m9.8 10.7-6-2" />
                                                <path d="m14.2 10.7 6-2" />
                                            </svg>
                                            @break

                                        @case('alert')
                                            <svg aria-hidden="true" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="9" />
                                                <path d="M12 7v6" />
                                                <path d="M12 17h.01" />
                                            </svg>
                                            @break

                                        @case('scale')
                                            <svg aria-hidden="true" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 3v18" />
                                                <path d="M7 6h10" />
                                                <path d="m6 6-4 7h8L6 6z" />
                                                <path d="m18 6-4 7h8l-4-7z" />
                                                <path d="M8 21h8" />
                                            </svg>
                                            @break

                                        @default
                                            <svg aria-hidden="true" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="9" />
                                                <path d="M12 8v4" />
                                                <path d="M12 16h.01" />
                                            </svg>
                                    @endswitch
                                </div>

                                <div>
                                    <h2 class="text-xl font-bold tracking-normal text-slate-950">{{ $section['title'] }}</h2>
                                    <p class="mt-3 whitespace-pre-line text-[15px] leading-7 text-slate-800">{{ $section['body'] }}</p>

                                    @if (filled($section['link_url'] ?? null))
                                        <a href="{{ $section['link_url'] }}" class="mt-4 inline-flex items-center gap-2 text-[15px] font-semibold text-blue-600 hover:text-blue-700">
                                            {{ $section['link_label'] ?? 'Zobacz źródło' }}
                                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M7 17 17 7" />
                                                <path d="M9 7h8v8" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </article>
                        @endif
                    @endforeach
                </section>

                @if (($supportingPages ?? collect())->isNotEmpty())
                    <aside class="self-start lg:border-l lg:border-slate-200 lg:pl-8">
                        <h2 class="text-xl font-bold text-slate-950">Powiązane materiały</h2>

                        <div class="mt-5 divide-y divide-slate-200">
                            @foreach ($supportingPages as $supportingPage)
                                @php
                                    $thumbs = $supportingPage['thumbs'] ?? [];
                                    $thumbCount = count($thumbs);
                                @endphp
                                <a href="{{ $supportingPage['url'] }}" class="grid gap-5 py-5 transition hover:text-red-600 sm:grid-cols-[128px_minmax(0,1fr)_18px] sm:items-center">
                                    <div class="flex h-[102px] w-[128px] items-center justify-center">
                                        @if ($thumbCount > 0)
                                            <div class="{{ $thumbCount >= 3 ? 'grid grid-cols-3 items-center gap-1 px-2' : 'flex items-center justify-center gap-2 px-3' }}">
                                                @foreach ($thumbs as $thumb)
                                                    <img
                                                        src="{{ $thumb['image_url'] }}"
                                                        alt="{{ $thumb['image_alt'] }}"
                                                        class="{{ $thumbCount >= 3 ? 'max-h-[58px] max-w-[34px]' : 'max-h-[68px] max-w-[50px]' }} h-auto w-auto object-contain"
                                                        loading="lazy"
                                                    >
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400">Brak grafiki</span>
                                        @endif
                                    </div>

                                    <div>
                                        <h3 class="text-[15px] font-bold leading-6 text-slate-950">{{ $supportingPage['title'] }}</h3>
                                        <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-700">{{ $supportingPage['description'] }}</p>
                                    </div>

                                    <span class="hidden text-slate-950 sm:block">
                                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell grid gap-12 pb-10 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1fr)]">
            @if (! empty($sign->faq_items))
                <section class="self-start">
                    <h2 class="text-xl font-bold text-slate-950">FAQ</h2>
                    <p class="mt-2 text-sm text-slate-600">Najczęstsze pytania o ten znak</p>

                    <div class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
                        @foreach ($sign->faq_items as $item)
                            @if (is_array($item) && filled($item['question'] ?? null))
                                <details class="group py-5">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-[15px] font-semibold text-slate-950">
                                        {{ $item['question'] }}
                                        <svg aria-hidden="true" class="h-4 w-4 shrink-0 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m6 9 6 6 6-6" />
                                        </svg>
                                    </summary>
                                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $item['answer'] ?? '' }}</p>
                                </details>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            @if (($relatedSignCards ?? collect())->isNotEmpty())
                <section>
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-slate-950">Powiązane znaki</h2>
                        <a href="{{ route('traffic-signs.categories.show', $sign->category->slug) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700">
                            Zobacz całą kategorię
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </a>
                    </div>

                    <div class="mt-7 grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-3">
                        @foreach ($relatedSignCards as $relatedSign)
                            <a href="{{ $relatedSign['url'] }}" class="group flex min-h-[144px] flex-col items-center justify-start px-2 py-2 text-center transition hover:text-red-600">
                                @if ($relatedSign['image_url'])
                                    <img
                                        src="{{ $relatedSign['image_url'] }}"
                                        alt="{{ $relatedSign['image_alt'] }}"
                                        class="h-[82px] w-full object-contain transition group-hover:scale-[1.03]"
                                        loading="lazy"
                                    >
                                @endif
                                <span class="mt-3 text-sm font-bold leading-tight text-slate-950 group-hover:text-red-600">{{ $relatedSign['code'] }}</span>
                                <span class="mt-1 text-xs font-semibold leading-4 text-slate-800">{{ $relatedSign['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell pb-10">
            <x-public.newsroom-reverse-links
                class="mb-10"
                :articles="$newsroomReverseArticles ?? []"
                heading="Materiały powiązane z tym znakiem"
            />

            <a href="{{ route('traffic-signs.categories.show', $sign->category->slug) }}" class="inline-flex min-h-12 items-center gap-3 rounded-md border border-slate-200 bg-white px-5 text-sm font-bold text-slate-950 transition hover:border-slate-300 hover:bg-slate-50">
                <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                Wróć do kategorii
            </a>
        </div>
    </section>
@endsection

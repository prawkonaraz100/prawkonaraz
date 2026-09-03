@extends('layouts.public-content')

@php
    $relatedSignCards = $relatedSignCards ?? collect();
    $publishedAt = \Illuminate\Support\Carbon::parse($page['published_at'])->format('d.m.Y');
    $updatedAt = \Illuminate\Support\Carbon::parse($page['updated_at'])->format('d.m.Y');
    $primaryCategoryUrl = $relatedSignCards->first()['category_url'] ?? null;
    $primaryCategoryName = $relatedSignCards->first()['category_name'] ?? null;
@endphp

@section('content')
    <section class="content-band">
        <div class="content-shell pb-8 pt-5 md:pb-9 md:pt-7">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_520px] lg:items-center">
                <div>
                    <p class="text-xs font-bold uppercase text-red-600">{{ $page['kicker'] }}</p>
                    <h1 class="mt-5 max-w-4xl text-4xl font-bold tracking-normal text-slate-950 md:text-[42px] md:leading-[1.12]">
                        {{ $page['headline'] }}
                    </h1>
                    <p class="mt-5 max-w-3xl text-[15px] leading-7 text-slate-700 md:text-base">{{ $page['intro'] }}</p>

                    <dl class="mt-7 grid max-w-xl grid-cols-3 gap-6 text-sm">
                        <div>
                            <dt class="text-slate-500">Autor</dt>
                            <dd class="mt-2 font-bold text-slate-950">
                                @if ($author)
                                    <a href="{{ route('content-authors.show', $author->slug) }}" class="hover:text-red-600">{{ $author->name }}</a>
                                @else
                                    {{ config('content.organization.name', config('app.name')) }}
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Publikacja</dt>
                            <dd class="mt-2 font-bold text-slate-950">{{ $publishedAt }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Aktualizacja</dt>
                            <dd class="mt-2 font-bold text-slate-950">{{ $updatedAt }}</dd>
                        </div>
                    </dl>
                </div>

                @if ($relatedSignCards->isNotEmpty())
                    <div class="grid grid-cols-3 gap-x-6 gap-y-8 lg:pl-6">
                        @foreach ($relatedSignCards->take(6) as $signCard)
                            <a href="{{ $signCard['url'] }}" class="group flex min-h-[126px] flex-col items-center justify-start px-2 py-2 text-center transition hover:text-red-600">
                                @if ($signCard['image_url'])
                                    <img
                                        src="{{ $signCard['image_url'] }}"
                                        alt="{{ $signCard['image_alt'] }}"
                                        class="h-[84px] w-full object-contain transition group-hover:scale-[1.03]"
                                        loading="eager"
                                    >
                                @endif
                                <span class="mt-3 text-sm font-bold leading-none text-slate-950 group-hover:text-red-600">{{ $signCard['code'] }}</span>
                                <span class="mt-1 line-clamp-2 text-[11px] font-semibold leading-4 text-slate-700">{{ $signCard['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($relatedSignCards->isNotEmpty())
        <section class="content-band">
            <div class="content-shell pb-8 md:pb-9" style="padding-top: 64px;">
                <div class="flex flex-col justify-between gap-3 md:flex-row md:items-end">
                    <div>
                        <p class="text-xs font-bold uppercase text-red-600">Powiązane znaki</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-950">Znaki w tym porównaniu</h2>
                    </div>
                    @if ($primaryCategoryUrl)
                        <a href="{{ $primaryCategoryUrl }}" class="inline-flex items-center gap-2 text-sm font-bold text-red-600 hover:text-red-700">
                            {{ $primaryCategoryName ? 'Zobacz kategorię: '.$primaryCategoryName : 'Zobacz kategorię' }}
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </a>
                    @endif
                </div>

                <div class="mt-7 grid gap-x-9 gap-y-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($relatedSignCards as $signCard)
                        <a href="{{ $signCard['url'] }}" class="group grid min-h-[140px] grid-cols-[116px_minmax(0,1fr)] items-center gap-5 py-2 transition hover:text-red-600">
                            <div class="flex h-[116px] w-[116px] items-center justify-center overflow-visible">
                                @if ($signCard['image_url'])
                                    <img
                                        src="{{ $signCard['image_url'] }}"
                                        alt="{{ $signCard['image_alt'] }}"
                                        class="h-auto max-h-[104px] max-w-[116px] object-contain transition group-hover:scale-[1.03]"
                                        loading="lazy"
                                    >
                                @endif
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase text-red-600">{{ $signCard['code'] }}</p>
                                <h3 class="mt-1 text-[15px] font-bold leading-5 text-slate-950 group-hover:text-red-600">{{ $signCard['name'] }}</h3>
                                @if ($signCard['intro'])
                                    <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-700">{{ $signCard['intro'] }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="content-band">
        <div class="content-shell grid gap-4 pb-10 lg:grid-cols-[minmax(0,1.35fr)_minmax(330px,0.72fr)]">
            <section>
                <div class="pb-6">
                    <p class="text-xs font-bold uppercase text-red-600">Analiza</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-950">Jak czytać różnice</h2>
                </div>

                @foreach ($page['sections'] as $index => $section)
                    <article class="grid gap-5 border-t border-slate-100 py-7 first:border-t-0 md:grid-cols-[56px_minmax(0,1fr)]">
                        <div class="flex h-12 w-12 items-center justify-center text-sm font-bold text-red-600">
                            {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                        </div>
                        <div>
                            <h3 class="text-xl font-bold tracking-normal text-slate-950">{{ $section['title'] }}</h3>
                            <p class="mt-3 whitespace-pre-line text-[15px] leading-7 text-slate-800">{{ $section['body'] }}</p>
                        </div>
                    </article>
                @endforeach
            </section>

            <aside class="space-y-10 lg:sticky lg:top-4 lg:self-start lg:border-l lg:border-slate-200 lg:pl-8">
                <section>
                    <h2 class="text-xl font-bold text-slate-950">Najważniejsze wnioski</h2>
                    <ul class="mt-5 space-y-4">
                        @foreach ($page['takeaways'] as $takeaway)
                            <li class="grid grid-cols-[24px_minmax(0,1fr)] gap-3 text-sm leading-6 text-slate-800">
                                <span class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-red-50 text-red-600">
                                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="m5 12 4 4L19 6" />
                                    </svg>
                                </span>
                                <span>{{ $takeaway }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                @if ($relatedSignCards->isNotEmpty())
                    <section>
                        <h2 class="text-xl font-bold text-slate-950">Szybkie przejście</h2>
                        <div class="mt-5 divide-y divide-slate-200">
                            @foreach ($relatedSignCards as $signCard)
                                <a href="{{ $signCard['url'] }}" class="grid grid-cols-[44px_minmax(0,1fr)_18px] items-center gap-3 py-3 transition hover:text-red-600">
                                    <span class="flex h-11 w-11 items-center justify-center overflow-visible">
                                        @if ($signCard['image_url'])
                                            <img src="{{ $signCard['image_url'] }}" alt="{{ $signCard['image_alt'] }}" class="h-auto max-h-9 max-w-10 object-contain" loading="lazy">
                                        @endif
                                    </span>
                                    <span>
                                        <span class="block text-sm font-bold text-slate-950">{{ $signCard['code'] }} {{ $signCard['name'] }}</span>
                                    </span>
                                    <svg aria-hidden="true" class="h-4 w-4 text-slate-950" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m9 18 6-6-6-6" />
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </section>

    @if (! empty($page['faq_items']))
        <section class="content-band">
            <div class="content-shell pb-10">
                <section class="border-t border-slate-200 pt-8">
                    <p class="text-xs font-bold uppercase text-red-600">FAQ</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-950">Najczęstsze pytania o różnicę między tymi znakami</h2>

                    <div class="mt-6 divide-y divide-slate-200 border-y border-slate-200">
                        @foreach ($page['faq_items'] as $item)
                            <details class="group py-5">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-[15px] font-semibold text-slate-950">
                                    {{ $item['question'] }}
                                    <svg aria-hidden="true" class="h-4 w-4 shrink-0 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </summary>
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $item['answer'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </section>
            </div>
        </section>
    @endif
@endsection

@extends('layouts.public-content')

@php
    $workflow = $article->workflow_status?->value ?? (string) $article->workflow_status;
    $type = $article->type?->value ?? (string) $article->type;
    $publishedLabel = $article->published_at?->format('d.m.Y H:i');
    $scheduledLabel = $article->scheduled_for?->format('d.m.Y H:i');
@endphp

@section('content')
    <style>
        .newsroom-preview-prose p + p { margin-top: 1rem; }
        .newsroom-preview-prose h2 { margin-top: 2rem; font-size: 1.65rem; font-weight: 700; line-height: 1.25; }
        .newsroom-preview-prose h3 { margin-top: 1.5rem; font-size: 1.3rem; font-weight: 700; line-height: 1.3; }
        .newsroom-preview-prose ul { margin-top: 1rem; list-style: disc; padding-left: 1.5rem; }
        .newsroom-preview-prose ol { margin-top: 1rem; list-style: decimal; padding-left: 1.5rem; }
        .newsroom-preview-prose li + li { margin-top: .4rem; }
        .newsroom-preview-prose a { color: #1d4ed8; text-decoration: underline; text-underline-offset: 2px; }
    </style>

    <section class="border-b border-[#d9b331] bg-[#fff9dc]">
        <div class="content-shell py-4">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.12em] text-slate-950">Podgląd redakcyjny</p>
                    <p class="mt-1 text-sm text-slate-700">
                        Ta strona jest prywatna, nieindeksowalna i nie jest publiczną wersją artykułu.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-slate-700">
                    <span class="border border-slate-300 bg-white px-3 py-1.5">{{ $workflow }}</span>
                    <span class="border border-slate-300 bg-white px-3 py-1.5">{{ $type }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell content-hero-section">
            <div class="mx-auto max-w-[860px]">
                @if ($article->category)
                    <p class="content-kicker">{{ $article->category->name }}</p>
                @endif

                <h1 class="mt-3 text-[2.35rem] font-semibold leading-[1.08] tracking-tight text-slate-950 md:text-[3.15rem]">
                    {{ $article->title }}
                </h1>

                @if ($article->lead)
                    <p class="mt-5 max-w-[52rem] text-lg leading-8 text-slate-700">{{ $article->lead }}</p>
                @endif

                <div class="mt-7 flex flex-wrap gap-x-8 gap-y-3 border-y border-slate-200 py-4 text-sm text-slate-700">
                    <span><strong class="text-slate-950">Autor:</strong> {{ $article->author?->name ?? '—' }}</span>
                    <span><strong class="text-slate-950">Reviewer:</strong> {{ $article->reviewer?->name ?? '—' }}</span>
                    @if ($publishedLabel)
                        <span><strong class="text-slate-950">Publikacja:</strong> {{ $publishedLabel }}</span>
                    @endif
                    @if ($scheduledLabel)
                        <span><strong class="text-slate-950">Zaplanowano:</strong> {{ $scheduledLabel }}</span>
                    @endif
                </div>

                @if ($heroImageUrl)
                    <figure class="mt-8 overflow-hidden rounded-md bg-slate-100">
                        <img
                            src="{{ $heroImageUrl }}"
                            alt="{{ $article->hero_image_alt ?: $article->title }}"
                            @if ($article->hero_image_width) width="{{ $article->hero_image_width }}" @endif
                            @if ($article->hero_image_height) height="{{ $article->hero_image_height }}" @endif
                            class="block h-auto w-full object-cover"
                            decoding="async"
                        >
                        @if ($article->hero_image_caption || $article->image_credit)
                            <figcaption class="border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
                                {{ $article->hero_image_caption }}
                                @if ($article->image_credit)
                                    <span class="font-semibold"> {{ $article->image_credit }}</span>
                                @endif
                            </figcaption>
                        @endif
                    </figure>
                @endif
            </div>
        </div>
    </section>

    <section class="content-band">
        <div class="content-shell pb-14 md:pb-20">
            <article class="mx-auto max-w-[860px]">
                @if (! empty($article->key_points))
                    <section class="mb-9 border-l-4 border-[#efc54f] bg-slate-50 px-6 py-6">
                        <h2 class="text-xl font-semibold text-slate-950">W skrócie</h2>
                        <ul class="mt-4 space-y-2 text-[15px] leading-7 text-slate-800">
                            @foreach ($article->key_points as $point)
                                <li class="flex gap-3">
                                    <span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-950" aria-hidden="true"></span>
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <div class="space-y-8">
                    @forelse ($bodyBlocks as $block)
                        @switch($block['type'])
                            @case('rich_text')
                                <section class="newsroom-preview-prose text-[16px] leading-8 text-slate-800">
                                    {!! $block['preview_html'] !!}
                                </section>
                                @break

                            @case('image')
                                <figure class="overflow-hidden rounded-md bg-slate-100">
                                    @if ($block['preview_url'] ?? null)
                                        <img
                                            src="{{ $block['preview_url'] }}"
                                            alt="{{ $block['data']['alt'] }}"
                                            @if ($block['data']['width']) width="{{ $block['data']['width'] }}" @endif
                                            @if ($block['data']['height']) height="{{ $block['data']['height'] }}" @endif
                                            class="block h-auto w-full object-contain"
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    @else
                                        <div class="px-5 py-8 text-sm text-slate-500">Asset obrazu nie ma jeszcze publicznego URL.</div>
                                    @endif
                                    @if ($block['data']['caption'] || $block['data']['credit'])
                                        <figcaption class="border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
                                            {{ $block['data']['caption'] }}
                                            @if ($block['data']['credit'])
                                                <span class="font-semibold"> {{ $block['data']['credit'] }}</span>
                                            @endif
                                        </figcaption>
                                    @endif
                                </figure>
                                @break

                            @case('quote')
                                <blockquote class="border-l-4 border-slate-900 bg-slate-50 px-6 py-5">
                                    <p class="text-lg leading-8 text-slate-900">„{{ $block['data']['text'] }}”</p>
                                    <footer class="mt-3 text-sm font-semibold text-slate-600">
                                        {{ $block['data']['attribution'] }}
                                        @if ($block['data']['source_url'])
                                            · <a href="{{ $block['data']['source_url'] }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 underline">źródło</a>
                                        @endif
                                    </footer>
                                </blockquote>
                                @break

                            @case('table')
                                <div class="overflow-x-auto border border-slate-200">
                                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                        @if ($block['data']['caption'])
                                            <caption class="px-4 py-3 text-left font-semibold text-slate-700">{{ $block['data']['caption'] }}</caption>
                                        @endif
                                        <thead class="bg-slate-50">
                                            <tr>
                                                @foreach ($block['data']['headers'] as $header)
                                                    <th class="px-4 py-3 font-semibold text-slate-950">{{ $header }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            @foreach ($block['data']['rows'] as $row)
                                                <tr>
                                                    @foreach ($row as $cell)
                                                        <td class="px-4 py-3 text-slate-700">{{ $cell }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @break

                            @case('context')
                                <aside class="border-l-4 border-[#efc54f] bg-[#fffdf3] px-6 py-5">
                                    @if ($block['data']['title'])
                                        <h2 class="text-lg font-semibold text-slate-950">{{ $block['data']['title'] }}</h2>
                                    @endif
                                    <p class="{{ $block['data']['title'] ? 'mt-2' : '' }} whitespace-pre-line text-[15px] leading-7 text-slate-800">{{ $block['data']['text'] }}</p>
                                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', $block['data']['variant']) }}</p>
                                </aside>
                                @break

                            @case('related_article')
                                <div class="border border-dashed border-slate-300 bg-slate-50 px-5 py-4 text-sm text-slate-700">
                                    Moduł publiczny N3: powiązany artykuł #{{ $block['data']['article_id'] }}
                                </div>
                                @break

                            @case('legal_reference')
                                <div class="border border-dashed border-slate-300 bg-slate-50 px-5 py-4 text-sm text-slate-700">
                                    Moduł publiczny N3: podstawa prawna #{{ $block['data']['legal_unit_id'] }}
                                </div>
                                @break

                            @case('question_group')
                                <div class="border border-dashed border-slate-300 bg-slate-50 px-5 py-4 text-sm text-slate-700">
                                    Moduł publiczny N3: grupa pytań ({{ count($block['data']['question_ids']) }})
                                </div>
                                @break

                            @case('traffic_sign_group')
                                <div class="border border-dashed border-slate-300 bg-slate-50 px-5 py-4 text-sm text-slate-700">
                                    Moduł publiczny N3: grupa znaków ({{ count($block['data']['traffic_sign_ids']) }})
                                </div>
                                @break

                            @case('product_cta')
                                <div class="border border-dashed border-slate-300 bg-slate-50 px-5 py-4 text-sm text-slate-700">
                                    Moduł publiczny N3: CTA produktu „{{ $block['data']['kind'] }}”
                                </div>
                                @break
                        @endswitch
                    @empty
                        <div class="border border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-center text-sm text-slate-600">
                            Artykuł nie ma jeszcze renderowalnych bloków treści.
                        </div>
                    @endforelse
                </div>

                @if ($publicSources->isNotEmpty())
                    <section class="mt-12 border-t border-slate-200 pt-7">
                        <h2 class="text-xl font-semibold text-slate-950">Źródła publiczne</h2>
                        <ol class="mt-4 space-y-3 text-sm leading-6 text-slate-700">
                            @foreach ($publicSources as $source)
                                <li>
                                    <span class="font-semibold text-slate-950">{{ $source->title }}</span>
                                    @if ($source->publisher)
                                        · {{ $source->publisher }}
                                    @endif
                                    @if ($source->url)
                                        · <a href="{{ $source->url }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 underline">otwórz źródło</a>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            </article>
        </div>
    </section>
@endsection

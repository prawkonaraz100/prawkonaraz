@extends('layouts.public-content')

@section('content')
    <style>
        .newsroom-article-prose { font-size: 18px; line-height: 1.75; overflow-wrap: anywhere; }
        .newsroom-article-prose p + p { margin-top: 1.1rem; }
        .newsroom-article-prose h2 { margin-top: 2.25rem; font-size: 1.75rem; font-weight: 700; line-height: 1.25; }
        .newsroom-article-prose h3 { margin-top: 1.75rem; font-size: 1.35rem; font-weight: 700; line-height: 1.3; }
        .newsroom-article-prose ul { margin-top: 1rem; list-style: disc; padding-left: 1.5rem; }
        .newsroom-article-prose ol { margin-top: 1rem; list-style: decimal; padding-left: 1.5rem; }
        .newsroom-article-prose li + li { margin-top: .45rem; }
        .newsroom-article-prose a { color: #1d4ed8; text-decoration: underline; text-underline-offset: 2px; overflow-wrap: anywhere; }
        .newsroom-article-title { overflow-wrap: anywhere; }
        .newsroom-article img { max-width: 100%; }
        @media (max-width: 639px) { .newsroom-article-prose { font-size: 17px; } }
    </style>

    @if ($isNeedsReview)
        <section class="border-b border-amber-200 bg-amber-50">
            <div class="content-shell py-3 text-sm text-slate-800">
                Materiał jest w trakcie ponownej weryfikacji.
                @if ($modifiedLabel)
                    <span class="font-semibold">Stan informacji: {{ $modifiedLabel }}.</span>
                @endif
            </div>
        </section>
    @elseif ($isArchived)
        <section class="border-b border-slate-200 bg-slate-50">
            <div class="content-shell py-3 text-sm text-slate-700">Materiał archiwalny.</div>
        </section>
    @endif

    <section class="content-band newsroom-article">
        <div class="content-shell content-hero-section">
            <div class="mx-auto max-w-[900px]">
                @if ($article->category)
                    <p class="content-kicker">
                        @if (($article->type?->value ?? (string) $article->type) !== 'guide')
                            <a href="{{ route('public.news.categories.show', $article->category->slug) }}" class="hover:underline">
                                {{ $article->category->name }}
                            </a>
                        @else
                            {{ $article->category->name }}
                        @endif
                    </p>
                @endif

                <h1 class="newsroom-article-title mt-3 text-[2.15rem] font-semibold leading-[1.08] tracking-tight text-slate-950 sm:text-[2.55rem] lg:text-[3.25rem]">
                    {{ $article->title }}
                </h1>

                @if ($article->lead)
                    <p class="mt-5 max-w-[52rem] text-[18px] leading-8 text-slate-700 sm:text-[20px]">{{ $article->lead }}</p>
                @endif

                <div class="mt-7 flex flex-wrap gap-x-6 gap-y-2 border-y border-slate-200 py-4 text-sm leading-6 text-slate-600">
                    @if ($authorBox)
                        <span>
                            Autor:
                            <a href="{{ $authorBox['url'] }}" class="font-semibold text-slate-950 hover:underline">{{ $authorBox['name'] }}</a>
                        </span>
                    @endif
                    @if ($publishedLabel)
                        <span><span class="font-semibold text-slate-950">Publikacja:</span> {{ $publishedLabel }}</span>
                    @endif
                    @if ($modifiedLabel)
                        <span><span class="font-semibold text-slate-950">Aktualizacja:</span> {{ $modifiedLabel }}</span>
                    @endif
                    @if ($provenanceLabel)
                        <span>{{ $provenanceLabel }}</span>
                    @endif
                </div>

                @if ($heroImageUrl)
                    <figure class="mt-8 overflow-hidden rounded-md bg-slate-100">
                        <img
                            src="{{ $heroImageUrl }}"
                            alt="{{ $article->hero_image_alt ?: '' }}"
                            @if ($article->hero_image_width) width="{{ $article->hero_image_width }}" @endif
                            @if ($article->hero_image_height) height="{{ $article->hero_image_height }}" @endif
                            class="block h-auto w-full object-cover"
                            @if ($heroObjectPosition) style="object-position: {{ $heroObjectPosition }}" @endif
                            decoding="async"
                            fetchpriority="high"
                        >
                        @if ($article->hero_image_caption || $article->image_credit)
                            <figcaption class="border-t border-slate-200 px-4 py-3 text-sm leading-6 text-slate-600">
                                @if ($article->hero_image_caption)
                                    <span>{{ $article->hero_image_caption }}</span>
                                @endif
                                @if ($article->image_credit)
                                    <span class="font-semibold">{{ $article->image_credit }}</span>
                                @endif
                            </figcaption>
                        @endif
                    </figure>
                @endif
            </div>
        </div>
    </section>

    <section class="content-band newsroom-article">
        <div class="content-shell pb-14 md:pb-20">
            <article class="mx-auto max-w-[800px]">
                @if (! empty($article->key_points))
                    <section class="mb-9 border-l-4 border-[#efc54f] bg-slate-50 px-5 py-5 sm:px-6 sm:py-6">
                        <h2 class="text-xl font-semibold text-slate-950">W skrócie</h2>
                        <ul class="mt-4 space-y-2 text-[16px] leading-7 text-slate-800">
                            @foreach ($article->key_points as $point)
                                <li class="flex gap-3">
                                    <span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-950" aria-hidden="true"></span>
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($regulatoryContext)
                    <section class="mb-9 border border-slate-200 bg-[#fffdf3] px-5 py-5 sm:px-6 sm:py-6" aria-labelledby="regulatory-context-heading">
                        <h2 id="regulatory-context-heading" class="text-xl font-semibold text-slate-950">Kontekst zmian i egzaminu</h2>
                        <dl class="mt-4 grid gap-4 text-[15px] leading-7 text-slate-800">
                            @if ($regulatoryContext['status'])
                                <div><dt class="font-semibold text-slate-950">Status</dt><dd>{{ $regulatoryContext['status'] }}</dd></div>
                            @endif
                            @if ($regulatoryContext['change_summary'])
                                <div><dt class="font-semibold text-slate-950">Co się zmienia?</dt><dd>{{ $regulatoryContext['change_summary'] }}</dd></div>
                            @endif
                            @if ($regulatoryContext['effective_from'])
                                <div><dt class="font-semibold text-slate-950">Od kiedy?</dt><dd>{{ $regulatoryContext['effective_from'] }}</dd></div>
                            @endif
                            @if ($regulatoryContext['applies_to'])
                                <div><dt class="font-semibold text-slate-950">Kogo dotyczy?</dt><dd>{{ $regulatoryContext['applies_to'] }}</dd></div>
                            @endif
                            @if ($regulatoryContext['exam_impact'])
                                <div><dt class="font-semibold text-slate-950">Co to oznacza na egzaminie?</dt><dd>{{ $regulatoryContext['exam_impact'] }}</dd></div>
                            @endif
                        </dl>
                    </section>
                @endif

                <div class="space-y-8">
                    @foreach ($bodyBlocks as $block)
                        @switch($block['type'])
                            @case('rich_text')
                                <section class="newsroom-article-prose text-slate-800">
                                    {!! $block['render_html'] !!}
                                </section>
                                @break

                            @case('image')
                                <figure class="overflow-hidden rounded-md bg-slate-100">
                                    @if ($block['public_url'] ?? null)
                                        <img
                                            src="{{ $block['public_url'] }}"
                                            alt="{{ $block['data']['alt'] }}"
                                            @if ($block['data']['width']) width="{{ $block['data']['width'] }}" @endif
                                            @if ($block['data']['height']) height="{{ $block['data']['height'] }}" @endif
                                            class="block h-auto w-full object-cover"
                                            @if ($block['object_position'] ?? null) style="object-position: {{ $block['object_position'] }}" @endif
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    @endif
                                    @if ($block['data']['caption'] || $block['data']['credit'])
                                        <figcaption class="border-t border-slate-200 px-4 py-3 text-sm leading-6 text-slate-600">
                                            @if ($block['data']['caption'])
                                                <span>{{ $block['data']['caption'] }}</span>
                                            @endif
                                            @if ($block['data']['credit'])
                                                <span class="font-semibold">{{ $block['data']['credit'] }}</span>
                                            @endif
                                        </figcaption>
                                    @endif
                                </figure>
                                @break

                            @case('quote')
                                <blockquote class="border-l-4 border-slate-900 bg-slate-50 px-5 py-5 sm:px-6">
                                    <p class="text-lg leading-8 text-slate-900">„{{ $block['data']['text'] }}”</p>
                                    <footer class="mt-3 text-sm font-semibold leading-6 text-slate-600">
                                        {{ $block['data']['attribution'] }}
                                        @if ($block['data']['source_url'])
                                            · <a href="{{ $block['data']['source_url'] }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 underline">źródło</a>
                                        @endif
                                    </footer>
                                </blockquote>
                                @break

                            @case('table')
                                <div class="max-w-full overflow-x-auto border border-slate-200">
                                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                        @if ($block['data']['caption'])
                                            <caption class="px-4 py-3 text-left font-semibold text-slate-700">{{ $block['data']['caption'] }}</caption>
                                        @endif
                                        <thead class="bg-slate-50">
                                            <tr>
                                                @foreach ($block['data']['headers'] as $header)
                                                    <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold text-slate-950">{{ $header }}</th>
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
                                <aside class="border-l-4 border-[#efc54f] bg-[#fffdf3] px-5 py-5 sm:px-6">
                                    @if ($block['data']['title'])
                                        <h2 class="text-lg font-semibold text-slate-950">{{ $block['data']['title'] }}</h2>
                                    @endif
                                    <p class="{{ $block['data']['title'] ? 'mt-2' : '' }} whitespace-pre-line text-[16px] leading-7 text-slate-800">{{ $block['data']['text'] }}</p>
                                </aside>
                                @break

                            @case('related_article')
                                <aside class="border border-slate-200 bg-slate-50 px-5 py-5">
                                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Czytaj także</p>
                                    <a href="{{ $block['related_article']['url'] }}" class="mt-2 block text-lg font-semibold leading-7 text-slate-950 hover:underline">
                                        {{ $block['related_article']['title'] }}
                                    </a>
                                    @if ($block['related_article']['lead'])
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $block['related_article']['lead'] }}</p>
                                    @endif
                                </aside>
                                @break
                        @endswitch
                    @endforeach
                </div>

                @if ($publicSources !== [])
                    <section class="mt-12 border-t border-slate-200 pt-7" aria-labelledby="newsroom-sources-heading">
                        <h2 id="newsroom-sources-heading" class="text-xl font-semibold text-slate-950">Źródła</h2>
                        <ol class="mt-4 space-y-3 text-sm leading-6 text-slate-700">
                            @foreach ($publicSources as $source)
                                <li>
                                    <span class="font-semibold text-slate-950">{{ $source['title'] }}</span>
                                    @if ($source['publisher'])
                                        · {{ $source['publisher'] }}
                                    @endif
                                    @if ($source['published_at'])
                                        · {{ $source['published_at'] }}
                                    @endif
                                    @if ($source['url'])
                                        · <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 underline">otwórz źródło</a>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if ($article->correction_note)
                    <section class="mt-10 border-l-4 border-slate-400 bg-slate-50 px-5 py-5" aria-labelledby="newsroom-correction-heading">
                        <h2 id="newsroom-correction-heading" class="text-lg font-semibold text-slate-950">Korekta</h2>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $article->correction_note }}</p>
                    </section>
                @endif

                @if ($authorBox)
                    <section class="mt-12 border-t border-slate-200 pt-7" aria-labelledby="newsroom-author-heading">
                        <div class="flex items-start gap-4">
                            @if ($authorBox['photo_url'])
                                <img src="{{ $authorBox['photo_url'] }}" alt="" width="72" height="72" class="h-16 w-16 shrink-0 rounded-full object-cover sm:h-[72px] sm:w-[72px]" loading="lazy">
                            @endif
                            <div class="min-w-0">
                                <h2 id="newsroom-author-heading" class="text-lg font-semibold text-slate-950">
                                    <a href="{{ $authorBox['url'] }}" class="hover:underline">{{ $authorBox['name'] }}</a>
                                </h2>
                                @if ($authorBox['job_title'])
                                    <p class="mt-1 text-sm font-medium text-slate-600">{{ $authorBox['job_title'] }}</p>
                                @endif
                                @if ($authorBox['bio'])
                                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ $authorBox['bio'] }}</p>
                                @endif
                                <a href="{{ $authorBox['url'] }}" class="mt-3 inline-block text-sm font-semibold text-slate-950 underline underline-offset-4">Więcej materiałów autora</a>
                            </div>
                        </div>
                    </section>
                @endif
            </article>
        </div>
    </section>
@endsection

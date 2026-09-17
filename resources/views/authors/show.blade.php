@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'site-shell')

@php
    $publicationCards = collect($publicationCards ?? []);
    $needsReviewNewsroomCards = collect($needsReviewNewsroomCards ?? []);
    $archivedNewsroomCards = collect($archivedNewsroomCards ?? []);
    $publicationCount = $publicationCards->count();
    $authorPhotoUrl = $authorPhotoUrl ?? null;
    $publishedAt = $author->published_at?->format('d.m.Y');
    $updatedAt = $author->updated_at?->format('d.m.Y');
@endphp

@section('content')
    <section class="content-band">
        <div class="site-shell pb-10 pt-7 md:pb-14 md:pt-12">
            <div class="grid gap-10 lg:grid-cols-[280px_minmax(0,1fr)_360px] lg:items-center xl:gap-16">
                <div class="flex justify-center lg:justify-start">
                    <div class="flex h-56 w-56 items-center justify-center rounded-full bg-slate-50 md:h-64 md:w-64">
                        @if ($authorPhotoUrl)
                            <img
                                src="{{ $authorPhotoUrl }}"
                                alt="{{ $author->name }}"
                                class="h-full w-full rounded-full object-cover"
                                loading="eager"
                            >
                        @else
                            <svg class="h-40 w-40 md:h-48 md:w-48" viewBox="0 0 220 220" role="img" aria-label="{{ $author->name }}">
                                <path d="M110 18 178 43v50c0 44-26 82-68 109-42-27-68-65-68-109V43l68-25Z" fill="#111827"/>
                                <path d="M110 34 160 52v40c0 33-18 62-50 85-32-23-50-52-50-85V52l50-18Z" fill="#263142"/>
                                <path d="M110 42v128" stroke="#f8fafc" stroke-width="8" stroke-linecap="round"/>
                                <path d="M88 166c13-38 18-78 18-122" stroke="#f8fafc" stroke-width="7" stroke-linecap="round"/>
                                <path d="M132 166c-13-38-18-78-18-122" stroke="#f8fafc" stroke-width="7" stroke-linecap="round"/>
                                <path d="M110 67v14M110 103v14M110 139v14" stroke="#111827" stroke-width="5" stroke-linecap="round"/>
                            </svg>
                        @endif
                    </div>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-red-600">Autor</p>
                    <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-950 md:text-5xl">{{ $author->name }}</h1>

                    @if ($author->job_title)
                        <p class="mt-5 text-xl font-semibold text-slate-950">{{ $author->job_title }}</p>
                    @endif

                    @if ($author->bio)
                        <div class="mt-7 max-w-2xl text-base leading-8 text-slate-700 md:text-lg">
                            <p class="whitespace-pre-line">{{ $author->bio }}</p>
                        </div>
                    @endif
                </div>

                <aside class="border-t border-slate-200 pt-8 lg:border-l lg:border-t-0 lg:py-2 lg:pl-14">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-700">Profil społecznościowy</p>

                        @if ($author->linkedin_url)
                            <a
                                href="{{ $author->linkedin_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-5 inline-flex items-center gap-4 text-base font-semibold text-slate-950 hover:text-red-600"
                            >
                                <span class="flex h-9 w-9 items-center justify-center rounded bg-[#0a66c2] text-lg font-bold leading-none text-white">in</span>
                                <span>LinkedIn</span>
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 17 17 7M9 7h8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        @endif

                        @if ($author->external_profile_url)
                            <a
                                href="{{ $author->external_profile_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-3 block text-sm text-slate-600 hover:text-red-600"
                            >
                                Profil zewnętrzny
                            </a>
                        @elseif (! $author->linkedin_url)
                            <p class="mt-5 text-sm text-slate-500">Brak podłączonego profilu.</p>
                        @endif
                    </div>

                    <div class="mt-8 space-y-6 text-sm">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-700">Data publikacji</p>
                            <p class="mt-2 text-base font-semibold text-slate-950">{{ $publishedAt ?? '-' }}</p>
                        </div>

                        <div class="border-t border-slate-200 pt-6">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-700">Data aktualizacji</p>
                            <p class="mt-2 text-base font-semibold text-slate-950">{{ $updatedAt ?? '-' }}</p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <section class="content-band">
        <div class="site-shell pb-14 md:pb-20">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-8 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="text-3xl font-semibold tracking-tight text-slate-950">Publikacje</h2>
                    <p class="mt-2 text-base text-slate-600">Aktualne, opublikowane materiały autora</p>
                </div>

                <p class="text-base font-semibold text-slate-950">{{ number_format($publicationCount, 0, ',', ' ') }} publikacji</p>
            </div>

            @include('authors._publication-cards', [
                'cards' => $publicationCards,
                'emptyMessage' => 'Ten autor nie ma jeszcze aktualnych publikacji.',
            ])

            @if ($needsReviewNewsroomCards->isNotEmpty())
                <section class="mt-12" aria-labelledby="author-needs-review-heading">
                    <div class="border-b border-amber-200 pb-6">
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-amber-700">Status redakcyjny</p>
                        <h2 id="author-needs-review-heading" class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">W trakcie weryfikacji</h2>
                        <p class="mt-2 max-w-3xl text-base leading-7 text-slate-600">Materiały pozostają publiczne, ale oczekują na ponowną weryfikację redakcyjną.</p>
                    </div>

                    @include('authors._publication-cards', [
                        'cards' => $needsReviewNewsroomCards,
                    ])
                </section>
            @endif

            @if ($archivedNewsroomCards->isNotEmpty())
                <section class="mt-12" aria-labelledby="author-archive-heading">
                    <div class="border-b border-slate-200 pb-6">
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Historia publikacji</p>
                        <h2 id="author-archive-heading" class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Archiwum</h2>
                        <p class="mt-2 max-w-3xl text-base leading-7 text-slate-600">Starsze materiały zachowane publicznie jako część historii publikacji autora.</p>
                    </div>

                    @include('authors._publication-cards', [
                        'cards' => $archivedNewsroomCards,
                    ])
                </section>
            @endif

            <div class="pt-8">
                <a href="{{ route('traffic-signs.index') }}" class="inline-flex items-center gap-3 border-l border-slate-300 py-3 pl-4 text-sm font-semibold text-slate-950 hover:text-red-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M19 12H5M11 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Wróć do znaków drogowych
                </a>
                <a href="{{ route('public.regulations') }}" class="ml-5 inline-flex items-center gap-3 border-l border-slate-300 py-3 pl-4 text-sm font-semibold text-slate-950 hover:text-red-600">
                    Przepisy drogowe
                </a>
            </div>
        </div>
    </section>
@endsection

@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'site-shell')

@php
    $publicationCards = collect($publicationCards ?? []);
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
                    <p class="mt-2 text-base text-slate-600">Opublikowane strony autora</p>
                </div>

                <p class="text-base font-semibold text-slate-950">{{ number_format($publicationCount, 0, ',', ' ') }} publikacji</p>
            </div>

            <ul class="divide-y divide-slate-200">
                @forelse ($publicationCards as $card)
                    <li>
                        <div class="group grid gap-5 py-5 transition md:grid-cols-[170px_minmax(0,1fr)_150px_32px] md:items-center md:py-6">
                            <a href="{{ $card['url'] }}" class="flex h-28 w-44 max-w-full items-center justify-center md:h-24 md:w-36" aria-label="{{ $card['title'] }}">
                                @if ($card['image_url'])
                                    <img
                                        src="{{ $card['image_url'] }}"
                                        alt="{{ $card['image_alt'] }}"
                                        class="max-h-full max-w-full object-contain"
                                        loading="lazy"
                                    >
                                @else
                                    <span class="flex h-20 w-24 items-center justify-center border border-slate-200 text-sm font-semibold text-slate-400">
                                        {{ $card['code'] }}
                                    </span>
                                @endif
                            </a>

                            <div class="min-w-0">
                                <a href="{{ $card['url'] }}" class="block text-xl font-semibold text-slate-950 hover:text-red-600">{{ $card['title'] }}</a>

                                @if ($card['intro'])
                                    <span class="mt-3 block max-w-4xl text-base leading-7 text-slate-700">{{ $card['intro'] }}</span>
                                @endif

                                @if ($card['category_name'])
                                    <span class="mt-2 block text-base text-slate-700">
                                        Kategoria:
                                        @if ($card['category_url'])
                                            <a href="{{ $card['category_url'] }}" class="font-medium text-blue-600 hover:text-red-600">{{ $card['category_name'] }}</a>
                                        @else
                                            <span>{{ $card['category_name'] }}</span>
                                        @endif
                                    </span>
                                @endif
                            </div>

                            <span class="text-sm text-slate-700 md:text-right">
                                <span class="block">Aktualizacja</span>
                                <span class="mt-1 block text-base text-slate-950">{{ $card['updated_at'] ?? '-' }}</span>
                            </span>

                            <a href="{{ $card['url'] }}" class="hidden justify-self-end text-slate-950 transition hover:translate-x-1 hover:text-red-600 md:block" aria-label="{{ $card['title'] }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        </div>
                    </li>
                @empty
                    <li class="py-8 text-base text-slate-600">Ten autor nie ma jeszcze opublikowanych stron.</li>
                @endforelse
            </ul>

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

@extends('layouts.public-content')

@php
    $tz = (string) config('app.timezone', 'Europe/Warsaw');
    $atLabel = $previewAt->copy()->timezone($tz)->format('d.m.Y H:i T');

    $articleMeta = static function ($article): string {
        if (! $article) {
            return 'brak';
        }

        $status = $article->workflow_status?->value ?? (string) $article->workflow_status;
        $category = $article->category?->name;

        return '#'.$article->id.' · '.$status.($category ? ' · '.$category : '');
    };
@endphp

@section('content')
    <style>
        .nhp { color: #0f172a; }
        .nhp-banner { border-bottom: 1px solid #d9b331; background: #fff9dc; }
        .nhp-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        .nhp-card { border: 1px solid #dbe3ee; background: #fff; border-radius: 12px; padding: 1rem; }
        .nhp-card h3 { margin: 0; font-size: 1rem; font-weight: 800; }
        .nhp-meta { margin-top: .35rem; font-size: .8rem; color: #64748b; }
        .nhp-section { margin-top: 2rem; }
        .nhp-section h2 { margin: 0 0 1rem; font-size: 1.35rem; font-weight: 800; }
        .nhp-empty { border: 1px dashed #cbd5e1; background: #f8fafc; border-radius: 12px; padding: 1rem; color: #64748b; }
    </style>

    <div class="nhp">
        <section class="nhp-banner">
            <div class="content-shell py-4">
                <p class="text-sm font-bold uppercase tracking-[0.12em] text-slate-950">Podgląd /aktualnosci — niepubliczne</p>
                <p class="mt-1 text-sm text-slate-700">
                    Stan kompozycji dla {{ $atLabel }}. Ten endpoint jest prywatny, no-store i nie jest publicznym hubem N3.
                </p>
            </div>
        </section>

        <section class="content-band">
            <div class="content-shell py-8 md:py-12">
                @if ($composition['breaking'])
                    <div class="mb-6 border-l-4 border-red-700 bg-red-50 px-5 py-4">
                        <div class="text-xs font-bold uppercase tracking-wide text-red-800">Breaking</div>
                        <div class="mt-1 font-semibold text-slate-950">{{ $composition['breaking']->title }}</div>
                        <div class="nhp-meta">{{ $articleMeta($composition['breaking']) }}</div>
                    </div>
                @endif

                <section>
                    <div class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Lead</div>
                    @if ($composition['lead'])
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950 md:text-5xl">{{ $composition['lead']->title }}</h1>
                        <div class="nhp-meta">{{ $articleMeta($composition['lead']) }}</div>
                    @else
                        <div class="nhp-empty mt-3">Brak leadu dla wybranego czasu.</div>
                    @endif
                </section>

                <section class="nhp-section">
                    <h2>Secondary</h2>
                    @if ($composition['secondary'])
                        <div class="nhp-grid">
                            @foreach ($composition['secondary'] as $article)
                                <article class="nhp-card">
                                    <h3>{{ $article->title }}</h3>
                                    <div class="nhp-meta">{{ $articleMeta($article) }}</div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="nhp-empty">Brak kart secondary.</div>
                    @endif
                </section>

                <section class="nhp-section">
                    <h2>Najnowsze</h2>
                    @if ($composition['latest'])
                        <div class="nhp-grid">
                            @foreach ($composition['latest'] as $article)
                                <article class="nhp-card">
                                    <h3>{{ $article->title }}</h3>
                                    <div class="nhp-meta">{{ $articleMeta($article) }}</div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="nhp-empty">Brak kart najnowszych.</div>
                    @endif
                </section>

                @foreach ($composition['categories'] as $block)
                    <section class="nhp-section">
                        <h2>{{ $block['category']->name }}</h2>

                        @if ($block['lead'])
                            <article class="nhp-card" style="margin-bottom:1rem">
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Lead kategorii</div>
                                <h3 class="mt-1">{{ $block['lead']->title }}</h3>
                                <div class="nhp-meta">{{ $articleMeta($block['lead']) }}</div>
                            </article>
                        @endif

                        @if ($block['items'])
                            <div class="nhp-grid">
                                @foreach ($block['items'] as $article)
                                    <article class="nhp-card">
                                        <h3>{{ $article->title }}</h3>
                                        <div class="nhp-meta">{{ $articleMeta($article) }}</div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endforeach

                <section class="nhp-section">
                    <h2>Poradniki</h2>
                    @if ($composition['guides']['lead'])
                        <article class="nhp-card" style="margin-bottom:1rem">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Lead poradników</div>
                            <h3 class="mt-1">{{ $composition['guides']['lead']->title }}</h3>
                            <div class="nhp-meta">{{ $articleMeta($composition['guides']['lead']) }}</div>
                        </article>
                    @endif

                    @if ($composition['guides']['items'])
                        <div class="nhp-grid">
                            @foreach ($composition['guides']['items'] as $article)
                                <article class="nhp-card">
                                    <h3>{{ $article->title }}</h3>
                                    <div class="nhp-meta">{{ $articleMeta($article) }}</div>
                                </article>
                            @endforeach
                        </div>
                    @elseif (! $composition['guides']['lead'])
                        <div class="nhp-empty">Brak poradników dla wybranego czasu.</div>
                    @endif
                </section>

                <section class="nhp-section">
                    <h2>Ważne teraz</h2>
                    @if ($composition['important_now'])
                        <div class="nhp-grid">
                            @foreach ($composition['important_now'] as $article)
                                <article class="nhp-card">
                                    <h3>{{ $article->title }}</h3>
                                    <div class="nhp-meta">{{ $articleMeta($article) }}</div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="nhp-empty">Brak kart „Ważne teraz”.</div>
                    @endif
                </section>
            </div>
        </section>
    </div>
@endsection

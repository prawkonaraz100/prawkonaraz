@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'site-shell')

@section('content')
    <section class="border-b border-slate-200 bg-slate-950 text-white">
        <div class="site-shell py-8 md:py-10">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-red-400">Podgląd administratora</p>
            <div class="mt-3 max-w-4xl">
                <h1 class="text-3xl font-bold tracking-tight md:text-4xl">Kolekcje pytań szkoleniowych</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 md:text-base">
                    Niepubliczne dane robocze. Pytania z tych kolekcji nie są dodawane do zwykłej nauki ani egzaminu kategorii C.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="site-shell py-8 md:py-12">
            @forelse ($collections as $collection)
                <article class="border-t border-slate-300 py-7 first:border-t-0 first:pt-0">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_220px] lg:items-start">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-[0.08em]">
                                <span class="text-red-700">Kategoria {{ $collection->licenseCategory?->code ?? '—' }}</span>
                                <span class="text-slate-400" aria-hidden="true">/</span>
                                <span class="text-slate-600">{{ $collection->kind }}</span>
                                <span class="border border-amber-300 bg-amber-50 px-2 py-1 text-amber-800">
                                    {{ $collection->is_public ? 'Publiczna' : 'Niepubliczna' }}
                                </span>
                            </div>
                            <h2 class="mt-3 text-2xl font-bold leading-tight text-slate-950">{{ $collection->name }}</h2>
                            @if ($collection->description)
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $collection->description }}</p>
                            @endif

                            @if (data_get($collection->metadata, 'lifecycle_status') === 'working_version')
                                <div class="mt-5 border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                                    <p class="font-bold">Wersja robocza — moduł będzie dalej rozwijany</p>
                                    <p class="mt-1 leading-6">
                                        Kurs działa teraz w obecnym systemie nauki. Kod i sposób obsługi tego modułu przebudujemy w kolejnym etapie. Kolekcja pozostaje niepubliczna.
                                    </p>
                                </div>
                            @endif

                            <div class="mt-6 border-y border-slate-200">
                                @forelse ($collection->modules as $module)
                                    <div class="grid gap-4 border-b border-slate-200 py-5 last:border-b-0 md:grid-cols-[84px_minmax(0,1fr)_auto] md:items-center">
                                        <div>
                                            <span class="text-xs font-bold uppercase tracking-[0.1em] text-slate-500">Moduł</span>
                                            <p class="mt-1 text-xl font-bold text-slate-950">{{ $module->code }}</p>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold leading-6 text-slate-950">{{ $module->name }}</h3>
                                            <p class="mt-1 text-sm text-slate-500">
                                                {{ $module->questions_count }} pytań
                                                @if ($module->expected_questions !== null)
                                                    · oczekiwano {{ $module->expected_questions }}
                                                @endif
                                            </p>
                                        </div>
                                        <form method="POST" action="{{ route('admin.question-collections.modules.start', [
                                            'questionCollection' => $collection->slug,
                                            'module' => $module->slug,
                                        ]) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex min-h-11 w-full items-center justify-center bg-red-700 px-5 text-sm font-bold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2"
                                            >
                                                Rozpocznij naukę
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="py-5 text-sm text-slate-500">Ta kolekcja nie ma jeszcze modułów.</p>
                                @endforelse
                            </div>
                        </div>

                        <dl class="border-l-2 border-red-700 pl-5 text-sm">
                            <div>
                                <dt class="text-slate-500">Kod kolekcji</dt>
                                <dd class="mt-1 break-words font-semibold text-slate-950">{{ $collection->code }}</dd>
                            </div>
                            <div class="mt-4">
                                <dt class="text-slate-500">Źródło</dt>
                                <dd class="mt-1 break-words font-semibold text-slate-950">{{ $collection->source ?: 'Brak' }}</dd>
                            </div>
                            <div class="mt-4">
                                <dt class="text-slate-500">Moduły</dt>
                                <dd class="mt-1 text-2xl font-bold text-slate-950">{{ $collection->modules->count() }}</dd>
                            </div>
                        </dl>
                    </div>
                </article>
            @empty
                <div class="border-y border-slate-200 py-12 text-center">
                    <h2 class="text-xl font-bold text-slate-950">Brak kolekcji do podglądu</h2>
                    <p class="mt-2 text-sm text-slate-600">Najpierw zaimportuj niepubliczny moduł pilotażowy.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection

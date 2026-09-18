@props([
    'articles' => [],
    'heading' => 'Powiązane aktualności i poradniki',
])

@if (($articles ?? []) !== [])
    <section data-newsroom-reverse-links {{ $attributes->merge(['class' => 'border-t border-slate-200 pt-7']) }} aria-labelledby="newsroom-reverse-links-heading">
        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Czytaj także</p>
        <h2 id="newsroom-reverse-links-heading" class="mt-1 text-xl font-semibold text-slate-950">{{ $heading }}</h2>

        <div class="mt-5 divide-y divide-slate-200 border-y border-slate-200">
            @foreach ($articles as $article)
                <a href="{{ $article['url'] }}" class="block py-4 hover:bg-slate-50">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold uppercase tracking-[0.08em] text-slate-500">
                        @if ($article['category'])
                            <span>{{ $article['category'] }}</span>
                        @endif
                        <span>{{ $article['type'] === 'guide' ? 'Poradnik' : 'Aktualność' }}</span>
                    </div>
                    <h3 class="mt-2 text-base font-semibold leading-6 text-slate-950">{{ $article['title'] }}</h3>
                    @if ($article['lead'])
                        <p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-600">{{ $article['lead'] }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
@endif

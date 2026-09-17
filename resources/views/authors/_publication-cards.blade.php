<ul class="divide-y divide-slate-200">
    @forelse ($cards as $card)
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
                        <span class="flex h-20 w-24 items-center justify-center border border-slate-200 text-center text-sm font-semibold text-slate-400">
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
        <li class="py-8 text-base text-slate-600">{{ $emptyMessage ?? 'Brak publikacji.' }}</li>
    @endforelse
</ul>

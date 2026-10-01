<aside class="traffic-sign-category__aside">
    @if ($supportingPages->isNotEmpty())
        <div class="traffic-sign-category__side-card overflow-hidden rounded-[6px] border border-slate-200 bg-white">
            <div class="traffic-sign-category__side-heading border-b border-slate-200 px-6 py-6">
                <h2 class="text-[15px] font-bold uppercase text-slate-950">Powiązane porównania</h2>
                <p class="mt-2 text-[15px] leading-6 text-slate-700">Najmocniejsze materiały w tej kategorii</p>
                <p class="mt-3 text-[15px] leading-6 text-slate-700">{{ $supportingPages->count() }} stron wspierających</p>
            </div>

            <div class="divide-y divide-slate-200">
                @foreach ($supportingPages as $supportingPage)
                    @php
                        $thumbCount = count($supportingPage['thumbs']);
                    @endphp
                    <a href="{{ $supportingPage['url'] }}" class="traffic-sign-category__support-link grid min-h-[104px] grid-cols-[74px_minmax(0,1fr)_20px] items-center gap-4 px-5 py-4 transition hover:bg-slate-50">
                        <span class="flex h-[66px] w-[66px] items-center justify-center overflow-hidden rounded-[4px] border border-slate-200 bg-white">
                            @if ($thumbCount > 0)
                                <span class="{{ $thumbCount >= 3 ? 'grid grid-cols-2 place-items-center gap-0.5' : 'flex items-center justify-center gap-1.5' }}">
                                    @foreach ($supportingPage['thumbs'] as $thumb)
                                        @if ($thumb['image_url'])
                                            <img
                                                src="{{ $thumb['image_url'] }}"
                                                alt="{{ $thumb['image_alt'] }}"
                                                class="{{ $thumbCount === 1 ? 'max-h-[52px] max-w-[52px]' : ($thumbCount === 2 ? 'max-h-[35px] max-w-[29px]' : 'max-h-[27px] max-w-[27px]'.($loop->iteration === 3 ? ' col-span-2 justify-self-center' : '')) }} object-contain"
                                                loading="lazy"
                                            >
                                        @endif
                                    @endforeach
                                </span>
                            @else
                                <span class="text-xs font-semibold text-slate-400">BRD</span>
                            @endif
                        </span>
                        <span class="text-[15px] font-semibold leading-6 text-slate-950">{{ $supportingPage['title'] }}</span>
                        <span class="text-slate-950" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                                <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="traffic-sign-category__side-card traffic-sign-category__learn-card relative overflow-hidden rounded-[6px] border border-slate-200 bg-white px-8 py-9">
        <div class="relative z-10 max-w-[17rem]">
            <h2 class="text-[22px] font-semibold leading-tight text-slate-950">Ucz się skuteczniej</h2>
            <p class="mt-4 text-[16px] leading-7 text-slate-700">Rozwiązuj pytania i utrwalaj znaki w praktyce.</p>
            <a
                href="{{ route('public.tests', absolute: false) }}"
                class="traffic-sign-category__cta mt-8 inline-flex min-h-14 items-center justify-center gap-4 rounded-[4px] px-9 text-[16px] font-semibold text-white transition"
            >
                Przejdź do testów
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M4 10h10.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    <path d="M10.5 5.5 15 10l-4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
        </div>
        <svg class="absolute bottom-5 right-5 h-48 w-48 text-slate-100" viewBox="0 0 160 160" fill="none" aria-hidden="true">
            <path d="M47 28h45l20 20v78H47V28Z" stroke="currentColor" stroke-width="4" />
            <path d="M92 28v22h20" stroke="currentColor" stroke-width="4" />
            <path d="M59 65h30M59 82h38M59 99h28" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
            <path d="m45 129 50-18 18 18-50 18-22 4 4-22Z" fill="currentColor" opacity=".55" />
            <path d="m115 88 16 9 16-9-16-9-16 9Z" stroke="currentColor" stroke-width="4" stroke-linejoin="round" />
        </svg>
    </div>
</aside>

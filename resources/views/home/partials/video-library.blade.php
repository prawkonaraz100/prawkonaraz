@if ($homepageVideos->isNotEmpty())
    @php($activeHomepageVideo = $homepageVideos->firstWhere('kind', 'video'))

    <section class="home-video-library" aria-labelledby="home-video-library-title" data-home-video-library>
        <div class="home-entry__shell">
            <header class="home-video-library__header" data-home-reveal>
                <h2 id="home-video-library-title">Podcasty &amp; Video</h2>
            </header>

            <div class="home-video-library__tabs" role="tablist" aria-label="Rodzaj materiałów">
                <button
                    type="button"
                    class="home-video-library__tab is-active"
                    role="tab"
                    aria-selected="true"
                    data-home-video-tab="video"
                >
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.6"/><path d="m10 8.7 5.2 3.3-5.2 3.3V8.7Z" fill="currentColor"/></svg>
                    Video
                </button>
                <button
                    type="button"
                    class="home-video-library__tab"
                    role="tab"
                    aria-selected="false"
                    tabindex="-1"
                    data-home-video-tab="podcast"
                >
                    Podcasty
                </button>
            </div>

            <div class="home-video-library__layout" data-home-reveal>
                <div class="home-video-library__list" aria-label="Filmy PrawkoNaRaz">
                    @foreach ($homepageVideos as $video)
                        @php($isInitialVideo = $activeHomepageVideo && $video['id'] === $activeHomepageVideo['id'])
                        <button
                            type="button"
                            class="home-video-library__item{{ $isInitialVideo ? ' is-active' : '' }}"
                            data-home-video-item
                            data-media-kind="{{ $video['kind'] }}"
                            data-source-type="{{ $video['source_type'] }}"
                            data-source-url="{{ $video['source_url'] }}"
                            data-poster-url="{{ $video['thumbnail_url'] }}"
                            data-video-title="{{ $video['title'] }}"
                            aria-pressed="{{ $isInitialVideo ? 'true' : 'false' }}"
                            @if ($video['kind'] !== 'video') hidden @endif
                        >
                            <img
                                src="{{ $video['thumbnail_url'] }}"
                                alt=""
                                width="168"
                                height="112"
                                loading="lazy"
                                decoding="async"
                            >
                            <span class="home-video-library__item-copy">
                                <span class="home-video-library__meta">
                                    <span class="home-video-library__youtube-mark" aria-hidden="true">▶</span>
                                    {{ $video['source_type'] === 'youtube' ? 'Dostępne na YouTube' : 'PrawkoNaRaz' }}
                                    @if ($video['published_label'])
                                        <span aria-hidden="true">·</span>
                                        {{ $video['published_label'] }}
                                    @endif
                                </span>
                                <strong>{{ $video['title'] }}</strong>
                                @if ($video['duration'])
                                    <span class="home-video-library__duration">
                                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-width="1.5"/><path d="M10 6.2v4.2l2.7 1.6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                        {{ $video['duration'] }}
                                    </span>
                                @endif
                                <span class="home-video-library__play-state">
                                    <span class="home-video-library__play-icon" aria-hidden="true">▶</span>
                                    <span data-home-video-state>{{ $isInitialVideo ? 'Teraz odtwarzasz' : 'Odtwórz teraz' }}</span>
                                </span>
                            </span>
                        </button>
                    @endforeach

                    <p class="home-video-library__empty" data-home-video-empty hidden>
                        Podcasty pojawią się tutaj po dodaniu ich w panelu administratora.
                    </p>
                </div>

                <div class="home-video-library__player{{ $activeHomepageVideo ? '' : ' is-empty' }}" data-home-video-player>
                    @if (! $activeHomepageVideo)
                        <p>Materiały video pojawią się tutaj po dodaniu ich w panelu administratora.</p>
                    @else
                        <button
                            type="button"
                            class="home-video-library__player-start"
                            data-home-video-player-start
                            aria-label="Odtwórz: {{ $activeHomepageVideo['title'] }}"
                        >
                            <img src="{{ $activeHomepageVideo['thumbnail_url'] }}" alt="" loading="lazy">
                            <span aria-hidden="true">▶</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif

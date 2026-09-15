@php
    $homeLearningPaths = [
        [
            'title' => 'Oficjalne testy na prawo jazdy 2026 z wyjaśnieniami',
            'description' => 'Rozwiązuj <strong>aktualne pytania egzaminacyjne WORD</strong> w układzie zgodnym z egzaminem państwowym. Po każdej odpowiedzi możesz sprawdzić jasne wyjaśnienie.',
            'cta' => 'Rozpocznij darmowy test',
            'href' => route('public.tests', absolute: false),
            'image' => \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/exam.webp'),
            'alt' => 'Próbny test teoretyczny na prawo jazdy w PrawkoNaRaz',
        ],
        [
            'title' => 'Kurs na prawo jazdy z pełną bazą pytań WORD',
            'description' => 'Ucz się działami i przechodź przez całą <strong>oficjalną bazę pytań na prawo jazdy</strong>. Postęp zapisuje się, więc zawsze wiesz, co już umiesz.',
            'cta' => 'Zobacz kurs na prawo jazdy',
            'href' => route('public.course', absolute: false),
            'image' => \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/dashboard.webp'),
            'alt' => 'Panel kursu na prawo jazdy z postępem nauki',
        ],
        [
            'title' => 'Pytania egzaminacyjne z odpowiedziami i statystykami',
            'description' => 'Przeglądaj pytania według kategorii i tematów. Zobacz <strong>poprawną odpowiedź, wyjaśnienie, podstawę prawną</strong> oraz poziom trudności.',
            'cta' => 'Przeglądaj pytania egzaminacyjne',
            'href' => route('public.questions.hub', absolute: false),
            'image' => \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/explanation.webp'),
            'alt' => 'Wyjaśnienie odpowiedzi do pytania egzaminacyjnego',
        ],
        [
            'title' => 'Powtórki błędnych pytań bez zaczynania od początku',
            'description' => 'Pomyłki trafiają na osobną listę. Wracasz tylko do zagadnień, które sprawiają Ci trudność, i <strong>utrwalasz je we własnym tempie</strong>.',
            'cta' => 'Załóż konto i zapisuj błędy',
            'href' => route('register', absolute: false),
            'image' => \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/incorrect-questions.webp'),
            'alt' => 'Lista błędnych pytań do ponownej nauki',
        ],
        [
            'title' => 'Trener pamięci do szybszej nauki teorii',
            'description' => 'Inteligentne powtórki pomagają wracać do materiału w odpowiednim momencie. Dzięki temu <strong>zapamiętujesz przepisy na dłużej</strong>, zamiast uczyć się na ostatnią chwilę.',
            'cta' => 'Rozpocznij skuteczną naukę',
            'href' => route('register', absolute: false),
            'image' => \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/memory-trainer.webp'),
            'alt' => 'Trener pamięci wspierający naukę teorii na prawo jazdy',
        ],
        [
            'title' => 'Znaki drogowe – znaczenie, podział i nauka online',
            'description' => 'Poznaj znaki ostrzegawcze, zakazu, nakazu i informacyjne. Czytelne grafiki i opisy ułatwiają <strong>rozpoznawanie znaków na egzaminie</strong>.',
            'cta' => 'Ucz się znaków drogowych',
            'href' => route('traffic-signs.index', absolute: false),
            'image' => \Illuminate\Support\Facades\Vite::asset('resources/images/session/informational-signs-topic.webp'),
            'alt' => 'Nauka znaków drogowych w PrawkoNaRaz',
        ],
    ];
@endphp

<section class="home-learning-paths" aria-labelledby="home-learning-paths-title" data-home-reveal>
    <header class="home-learning-paths__header">
        <p class="home-learning-paths__eyebrow">Wszystko do zdania teorii</p>
        <h2 id="home-learning-paths-title">Ucz się na prawo jazdy z PrawkoNaRaz</h2>
        <p>Wybierz sposób nauki dopasowany do siebie — od darmowego testu po systematyczne powtórki i pełną bazę pytań egzaminacyjnych.</p>
    </header>

    <div class="home-learning-paths__grid">
        @foreach ($homeLearningPaths as $path)
            <article class="home-learning-paths__card">
                <a href="{{ $path['href'] }}" class="home-learning-paths__media" tabindex="-1" aria-hidden="true">
                    <img
                        src="{{ $path['image'] }}"
                        alt="{{ $path['alt'] }}"
                        width="640"
                        height="360"
                        loading="lazy"
                        decoding="async"
                    >
                </a>

                <div class="home-learning-paths__body">
                    <h3><a href="{{ $path['href'] }}">{{ $path['title'] }}</a></h3>
                    <p>{!! $path['description'] !!}</p>
                    <a href="{{ $path['href'] }}" class="home-learning-paths__action">
                        <span>{{ $path['cta'] }}</span>
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </article>
        @endforeach
    </div>
</section>

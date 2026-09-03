@extends('layouts.public-content')

@section('breadcrumb_shell_class', 'site-shell')

@php
    $teamMembers = [
        [
            'name' => 'Katarzyna Wiśniewska',
            'role' => 'Ekspertka ds. organizacji ruchu i BRD',
            'image' => asset('images/authors/katarzyna-wisniewska.png'),
            'url' => route('content-authors.show', 'katarzyna-wisniewska'),
        ],
        [
            'name' => 'Jakub Wiśniewski',
            'role' => 'Recenzent treści szkoleniowych',
            'image' => asset('images/authors/jakub-wisniewski.png'),
            'url' => route('content-authors.show', 'jakub-wisniewski'),
        ],
        [
            'name' => 'Redakcja PrawkoNaRaz',
            'role' => 'Opracowanie pytań i materiałów',
            'image' => asset('images/site-header-logo-20260728.png'),
            'url' => route('about.methodology'),
            'is_logo' => true,
        ],
    ];
@endphp

@section('content')
    <style>
        .about-redaction-banner {
            position: relative;
            height: 220px;
            overflow: hidden;
            border-radius: 26px;
            background: #111318;
            box-shadow: 0 18px 45px rgba(17, 19, 24, 0.10);
            clip-path: polygon(0 0, 100% 0, 100% 88%, 96% 100%, 0 100%);
        }

        .about-redaction-banner__image {
            height: 100%;
            width: 100%;
            object-fit: cover;
            object-position: center 48%;
            filter: grayscale(1);
            opacity: 0.74;
        }

        .about-redaction-banner__shade,
        .about-redaction-banner__left,
        .about-redaction-banner__accent,
        .about-redaction-banner__content {
            position: absolute;
        }

        .about-redaction-banner__shade {
            inset: 0;
            background: rgba(0, 0, 0, 0.42);
        }

        .about-redaction-banner__left {
            inset: 0 auto 0 0;
            width: 52%;
            background: linear-gradient(90deg, rgba(0, 0, 0, 0.88), rgba(0, 0, 0, 0.62), rgba(0, 0, 0, 0));
        }

        .about-redaction-banner__accent {
            inset: 0 0 0 auto;
            width: 28%;
            background: rgba(208, 25, 33, 0.9);
            clip-path: polygon(28% 0, 100% 0, 100% 100%, 0 100%);
        }

        .about-redaction-banner__content {
            left: 40px;
            top: 50%;
            transform: translateY(-50%);
        }

        .about-redaction-banner__brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            border: 5px solid #d01921;
            background: #fff;
            padding: 10px 16px;
        }

        .about-redaction-banner__dot {
            height: 34px;
            width: 34px;
            object-fit: contain;
        }

        .about-redaction-banner__name {
            color: #111318;
            font-size: 1.55rem;
            font-weight: 900;
            letter-spacing: 0;
            line-height: 1;
        }

        .about-redaction-banner__label {
            display: inline-flex;
            margin-top: 12px;
            background: #d01921;
            padding: 7px 14px;
            color: #fff;
            font-size: 1rem;
            font-weight: 900;
            letter-spacing: 0.02em;
            line-height: 1.15;
            text-transform: uppercase;
        }

        @media (max-width: 767px) {
            .about-redaction-banner {
                height: 180px;
                border-radius: 20px;
            }

            .about-redaction-banner__content {
                left: 24px;
            }

            .about-redaction-banner__name {
                font-size: 1.35rem;
            }
        }

        .about-team-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 48px;
        }

        .about-team-card {
            text-align: center;
        }

        .about-team-photo {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 178px;
            height: 178px;
            margin: 0 auto;
            overflow: visible;
            border: 0;
            border-radius: 999px;
            background: transparent;
            box-shadow: 0 16px 34px rgba(17, 19, 24, 0.10);
            text-decoration: none;
        }

        .about-team-photo::before,
        .about-team-photo::after {
            position: absolute;
            border-radius: 999px;
            content: "";
        }

        .about-team-photo::before {
            inset: 0;
            background: conic-gradient(from -28deg, #d01921 0deg 52deg, #111318 52deg 168deg, #eef2f6 168deg 224deg, #d01921 224deg 286deg, #111318 286deg 360deg);
        }

        .about-team-photo::after {
            inset: 7px 5px 6px 9px;
            background: #fff;
        }

        .about-team-photo img {
            position: relative;
            z-index: 1;
            width: 154px;
            height: 154px;
            border-radius: 999px;
            background: #f5f7fa;
            object-fit: cover;
            filter: none;
        }

        .about-team-photo--logo img {
            width: 142px;
            height: 142px;
            padding: 36px 20px;
            object-fit: contain;
        }

        .about-team-name {
            margin-top: 28px;
            color: #111318;
            font-size: 1.25rem;
            font-weight: 900;
            line-height: 1.15;
            text-transform: uppercase;
        }

        .about-team-name::before {
            display: block;
            width: 28px;
            height: 3px;
            margin: 0 auto 14px;
            background: #d01921;
            content: "";
        }

        .about-team-role {
            margin-top: 20px;
            color: #747b86;
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.5;
        }

        .about-team-line {
            width: 100%;
            max-width: 360px;
            height: 1px;
            margin: 28px auto 0;
            background: #c8cbd1;
        }

        @media (max-width: 767px) {
            .about-team-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }
    </style>

    <section class="bg-white">
        <div class="site-shell py-6 md:py-8">
            <div class="about-redaction-banner">
                <img
                    src="{{ asset('images/questions/question-database-hero.png') }}"
                    alt="Nauka prawa jazdy i oficjalna baza pytań PrawkoNaRaz"
                    class="about-redaction-banner__image"
                    loading="eager"
                >
                <div class="about-redaction-banner__shade"></div>
                <div class="about-redaction-banner__left"></div>
                <div class="about-redaction-banner__accent"></div>
                <div class="about-redaction-banner__content">
                    <div class="about-redaction-banner__brand">
                        <img
                            src="{{ asset('images/site-brand-mark.png') }}"
                            alt=""
                            class="about-redaction-banner__dot"
                            aria-hidden="true"
                        >
                        <span class="about-redaction-banner__name">PrawkoNaRaz</span>
                    </div>
                    <div class="about-redaction-banner__label">
                        Oficjalna baza<br>prosta nauka
                    </div>
                </div>
            </div>

            <div class="pt-7 md:pt-8">
                <h1 class="text-[1.45rem] font-black uppercase tracking-[0.01em] text-[#111318]">
                    PrawkoNaRaz - o redakcji
                </h1>
                <p class="mt-6 max-w-6xl text-[1rem] leading-6 text-[#111318]">
                    PrawkoNaRaz.pl to serwis edukacyjny dla kandydatów na kierowców. Łączymy oficjalną bazę pytań, przepisy, znaki drogowe i praktyczne wyjaśnienia, aby nauka do egzaminu teoretycznego była uporządkowana, zrozumiała i możliwa do sprawdzenia.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="site-shell pb-10 pt-5 md:pb-14 md:pt-8">
            <div class="about-team-grid">
                @foreach ($teamMembers as $member)
                    <article class="about-team-card">
                        <a href="{{ $member['url'] }}" class="about-team-photo{{ ($member['is_logo'] ?? false) ? ' about-team-photo--logo' : '' }}" aria-label="{{ $member['name'] }}">
                            @if ($member['is_logo'] ?? false)
                                <img
                                    src="{{ $member['image'] }}"
                                    alt="{{ $member['name'] }}"
                                    loading="lazy"
                                >
                            @else
                                <img
                                    src="{{ $member['image'] }}"
                                    alt="{{ $member['name'] }}"
                                    loading="lazy"
                                >
                            @endif
                        </a>
                        <h2 class="about-team-name">
                            <a href="{{ $member['url'] }}" class="transition hover:text-[#d01921] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#d01921]">
                                {{ $member['name'] }}
                            </a>
                        </h2>
                        <p class="about-team-role">
                            {{ $member['role'] }}
                        </p>
                        <div class="about-team-line" aria-hidden="true"></div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="site-shell pb-10 md:pb-14">
            <h2 class="text-[1.35rem] font-black uppercase tracking-[0.01em] text-[#111318]">
                Misja PrawkoNaRaz
            </h2>
            <div class="mt-6 max-w-6xl space-y-5 text-[1rem] leading-7 text-[#111318]">
                <p>
                    Naszą misją jest sprawienie, żeby kandydat na kierowcę nie uczył się z chaosu. Pytanie egzaminacyjne traktujemy jako część większej mapy wiedzy: znaków, przepisów, pierwszeństwa, zagrożeń i praktycznych decyzji na drodze.
                </p>
                <p>
                    Chcemy, żeby użytkownik rozumiał, dlaczego odpowiedź jest prawidłowa. Dlatego obok testów rozwijamy wyjaśnienia, treści prawne, katalog znaków i materiały, które pomagają wrócić do trudnych tematów bez zgadywania.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="site-shell pb-12">
            <h2 class="text-[1.35rem] font-black text-[#111318]">
                Obszary PrawkoNaRaz:
            </h2>
            <ul class="mt-6 space-y-4 text-[1rem] leading-7 text-[#111318]">
                <li>
                    <span class="font-black">• Oficjalna baza pytań</span>
                    <a href="{{ route('public.questions.hub') }}" class="ml-1 underline decoration-[#d01921] decoration-2 underline-offset-4">/oficjalna-baza-pytan-na-prawo-jazdy</a>
                    <span>- pytania egzaminacyjne uporządkowane według kategorii i tematów.</span>
                </li>
                <li>
                    <span class="font-black">• Znaki drogowe</span>
                    <a href="{{ route('traffic-signs.index') }}" class="ml-1 underline decoration-[#d01921] decoration-2 underline-offset-4">/znaki-drogowe</a>
                    <span>- znaczenie znaków, typowe pomyłki i sytuacje egzaminacyjne.</span>
                </li>
                <li>
                    <span class="font-black">• Przepisy</span>
                    <a href="{{ route('public.regulations') }}" class="ml-1 underline decoration-[#d01921] decoration-2 underline-offset-4">/przepisy</a>
                    <span>- podstawy prawne tłumaczone prostym językiem.</span>
                </li>
                <li>
                    <span class="font-black">• Metodologia</span>
                    <a href="{{ route('about.methodology') }}" class="ml-1 underline decoration-[#d01921] decoration-2 underline-offset-4">/metodologia</a>
                    <span>- jak pracujemy ze źródłami i aktualizujemy materiały.</span>
                </li>
            </ul>
        </div>
    </section>
@endsection

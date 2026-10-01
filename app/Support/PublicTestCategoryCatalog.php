<?php

namespace App\Support;

class PublicTestCategoryCatalog
{
    public const SLUGS = ['a', 'b', 'c', 'd', 't', 'a1', 'am'];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            'a' => [
                'code' => 'A',
                'vehicle_label' => 'motocykl',
                'lead' => 'Przygotuj się do egzaminu teoretycznego na motocykl bez ograniczenia mocy. Ucz się na aktualnej bazie pytań i utrwalaj zagadnienia typowe dla jazdy jednośladem.',
                'minimum_age' => '24 lata lub 20 lat po co najmniej 2 latach z kat. A2',
                'age_note' => 'Dla motocykli trójkołowych o mocy powyżej 15 kW wymagane jest ukończenie 21 lat.',
                'permissions' => [
                    'każdy motocykl',
                    'pojazdy objęte kategorią AM',
                    'w Polsce także odpowiedni zespół pojazdów z przyczepą',
                ],
                'focus' => [
                    'bezpieczeństwo motocyklisty i widoczność na drodze',
                    'technika jazdy, hamowanie i zachowanie na łukach',
                    'pierwszeństwo, znaki i sytuacje szczególnie ryzykowne dla jednośladów',
                    'wyposażenie motocykla i przygotowanie do jazdy',
                ],
                'seo_body' => 'Kategoria A wymaga dobrej znajomości przepisów wspólnych dla wszystkich kierowców oraz zagadnień charakterystycznych dla motocykli. Warto ćwiczyć pytania dotyczące toru jazdy, hamowania, zachowania na nawierzchniach o gorszej przyczepności i widoczności motocyklisty.',
            ],
            'b' => [
                'code' => 'B',
                'vehicle_label' => 'samochód osobowy',
                'lead' => 'Testy na prawo jazdy kat. B obejmują aktualne pytania egzaminacyjne dotyczące samochodu osobowego, zasad ruchu drogowego, bezpieczeństwa i pierwszej pomocy.',
                'minimum_age' => '17 lat',
                'age_note' => 'Do ukończenia 18 lat kierowanie pojazdem kategorii B jest ograniczone do terytorium Polski.',
                'permissions' => [
                    'samochód o DMC do 3,5 t z wyjątkami określonymi w przepisach',
                    'pojazdy objęte kategorią AM',
                    'wybrane zespoły pojazdów z przyczepą oraz — w Polsce — ciągnik rolniczy i pojazd wolnobieżny w zakresie przewidzianym przepisami',
                ],
                'focus' => [
                    'pierwszeństwo, skrzyżowania i zachowanie wobec pieszych',
                    'znaki drogowe, sygnalizacja i ograniczenia prędkości',
                    'bezpieczna jazda, odstęp, hamowanie i warunki drogowe',
                    'podstawy obsługi pojazdu oraz pierwsza pomoc',
                ],
                'seo_body' => 'Kategoria B jest najczęściej wybieraną kategorią prawa jazdy. Podczas nauki warto łączyć rozwiązywanie pełnych testów z powtórką błędów: szczególnie skrzyżowań, pierwszeństwa, znaków, prędkości i pytań sytuacyjnych.',
            ],
            'c' => [
                'code' => 'C',
                'vehicle_label' => 'samochód ciężarowy',
                'lead' => 'Przygotowanie do teorii kat. C łączy przepisy ogólne z wiedzą dotyczącą ciężkich pojazdów, ich masy, ładunku, drogi hamowania i bezpiecznej eksploatacji.',
                'minimum_age' => '21 lat',
                'age_note' => 'Przepisy przewidują wyjątki wiekowe związane m.in. z odpowiednią kwalifikacją wstępną.',
                'permissions' => [
                    'pojazd samochodowy o DMC powyżej 3,5 t, z wyjątkiem autobusu',
                    'taki pojazd z lekką przyczepą',
                    'w Polsce także ciągnik rolniczy i pojazd wolnobieżny w zakresie przewidzianym przepisami',
                ],
                'focus' => [
                    'masy, wymiary, ładunek i stabilność pojazdu',
                    'droga hamowania i zachowanie ciężkiego pojazdu',
                    'ograniczenia i obowiązki dotyczące samochodów ciężarowych',
                    'bezpieczne manewry oraz obserwacja martwych pól',
                ],
                'seo_body' => 'W kategorii C sama znajomość znaków nie wystarcza. Pytania specjalistyczne sprawdzają także rozumienie zachowania ciężkiego pojazdu, wpływu ładunku na jazdę oraz obowiązków kierującego pojazdem o dużej masie.',
            ],
            'd' => [
                'code' => 'D',
                'vehicle_label' => 'autobus',
                'lead' => 'Testy kat. D przygotowują do części teoretycznej egzaminu na autobus. Oprócz przepisów ogólnych ważne są zagadnienia związane z przewozem osób i bezpieczeństwem pasażerów.',
                'minimum_age' => '24 lata',
                'age_note' => 'W określonych przypadkach zawodowych przepisy przewidują niższy minimalny wiek po uzyskaniu wymaganej kwalifikacji.',
                'permissions' => [
                    'autobus',
                    'autobus z lekką przyczepą',
                    'pojazdy objęte kategorią AM oraz — w Polsce — wybrane pojazdy rolnicze i wolnobieżne',
                ],
                'focus' => [
                    'bezpieczeństwo pasażerów i zachowanie na przystankach',
                    'wymiary pojazdu, manewrowanie i martwe pola',
                    'zasady przewozu osób i obowiązki kierowcy autobusu',
                    'ogólne przepisy ruchu, pierwszeństwo i znaki',
                ],
                'seo_body' => 'Kategoria D wymaga myślenia nie tylko o pojeździe, ale też o bezpieczeństwie przewożonych osób. W nauce warto zwrócić uwagę na sytuacje na przystankach, manewrowanie dużym pojazdem i zagadnienia specjalistyczne związane z autobusem.',
            ],
            't' => [
                'code' => 'T',
                'vehicle_label' => 'ciągnik rolniczy',
                'lead' => 'Testy kat. T obejmują pytania dotyczące ciągników rolniczych, pojazdów wolnobieżnych, przyczep oraz bezpiecznego poruszania się takim zestawem po drodze.',
                'minimum_age' => '16 lat',
                'age_note' => 'Kategoria T obejmuje także pojazdy kategorii AM.',
                'permissions' => [
                    'ciągnik rolniczy lub pojazd wolnobieżny',
                    'zespół takich pojazdów z przyczepą lub przyczepami',
                    'pojazdy objęte kategorią AM',
                ],
                'focus' => [
                    'zasady ruchu ciągników i pojazdów wolnobieżnych',
                    'przyczepy, zestawy pojazdów i bezpieczne manewrowanie',
                    'oznakowanie, oświetlenie i widoczność pojazdu',
                    'pierwszeństwo oraz zachowanie na drogach lokalnych i publicznych',
                ],
                'seo_body' => 'W kategorii T ważne są pytania o zestawy pojazdów, widoczność i oznakowanie oraz zachowanie podczas włączania się do ruchu i wykonywania manewrów pojazdem o innych gabarytach niż samochód osobowy.',
            ],
            'a1' => [
                'code' => 'A1',
                'vehicle_label' => 'motocykl do 125 cm³',
                'lead' => 'Testy kat. A1 przygotowują do egzaminu na lekkie motocykle. Pytania obejmują przepisy ruchu drogowego oraz bezpieczeństwo i technikę jazdy jednośladem.',
                'minimum_age' => '16 lat',
                'age_note' => 'Kategoria A1 obejmuje również pojazdy kategorii AM.',
                'permissions' => [
                    'motocykl do 125 cm³ i 11 kW, przy zachowaniu ustawowego stosunku mocy do masy',
                    'motocykl trójkołowy o mocy do 15 kW',
                    'pojazdy objęte kategorią AM',
                ],
                'focus' => [
                    'bezpieczna jazda lekkim motocyklem',
                    'hamowanie, zakręty i utrzymanie stabilności',
                    'widoczność motocyklisty oraz zachowanie innych uczestników ruchu',
                    'znaki, pierwszeństwo i typowe sytuacje drogowe',
                ],
                'seo_body' => 'Kat. A1 jest dobrym pierwszym krokiem do jazdy motocyklem. Nauka teorii powinna łączyć przepisy ogólne z pytaniami o technikę jazdy i ryzyka charakterystyczne dla lekkiego jednośladu.',
            ],
            'am' => [
                'code' => 'AM',
                'vehicle_label' => 'motorower i lekki czterokołowiec',
                'lead' => 'Testy kat. AM pomagają przygotować się do egzaminu na motorower i lekki czterokołowiec. Nacisk kładziemy na podstawowe zasady ruchu i bezpieczeństwo młodego kierowcy.',
                'minimum_age' => '14 lat',
                'age_note' => 'To najniższy standardowy wiek uzyskania kategorii prawa jazdy w Polsce.',
                'permissions' => [
                    'motorower',
                    'czterokołowiec lekki, np. mały quad',
                    'w Polsce także zespół tych pojazdów z przyczepą w zakresie przewidzianym przepisami',
                ],
                'focus' => [
                    'podstawowe zasady pierwszeństwa i znaki drogowe',
                    'bezpieczna jazda motorowerem w ruchu miejskim',
                    'widoczność i zachowanie wobec pieszych oraz rowerzystów',
                    'wyposażenie, stan techniczny i odpowiedzialne manewry',
                ],
                'seo_body' => 'W kategorii AM najważniejsze jest zrozumienie podstaw ruchu drogowego i przewidywanie zagrożeń. Regularne krótkie serie pytań pomagają utrwalić znaki, pierwszeństwo i bezpieczne zachowanie na motorowerze.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        return $this->all()[strtolower($slug)] ?? null;
    }

    /**
     * @return list<array{label: string, href: string, code: string}>
     */
    public function menuItems(): array
    {
        return collect($this->all())
            ->map(fn (array $item, string $slug): array => [
                'label' => 'Kategoria '.$item['code'],
                'code' => $item['code'],
                'href' => route('public.tests.category', ['categorySlug' => $slug], absolute: false),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function faq(array $content): array
    {
        $code = $content['code'];

        if ($code === 'AM') {
            return [
                ['question' => 'Od ilu lat można zrobić prawo jazdy AM?', 'answer' => 'Kategorię AM można uzyskać od 14 lat. Niepełnoletni kandydat potrzebuje pisemnej zgody rodzica lub opiekuna.'],
                ['question' => 'Jakie pojazdy można prowadzić na AM?', 'answer' => 'Kategoria AM obejmuje motorower oraz czterokołowiec lekki. W Polsce obejmuje także zespół takiego pojazdu z przyczepą.'],
                ['question' => 'Czy AM pozwala jeździć skuterem?', 'answer' => 'Tak, jeżeli konkretny skuter jest prawnie klasyfikowany jako motorower. Sama potoczna nazwa pojazdu nie przesądza o uprawnieniach.'],
                ['question' => 'Jaką pojemność może mieć motorower?', 'answer' => 'Silnik spalinowy motoroweru może mieć do 50 cm³, a elektryczny moc do 4 kW. W obu przypadkach prędkość konstrukcyjna wynosi najwyżej 45 km/h.'],
                ['question' => 'Czy kategorią AM można prowadzić małego quada?', 'answer' => 'Tak, jeśli dany pojazd jest sklasyfikowany jako czterokołowiec lekki. Jego masa własna nie może przekraczać 350 kg, a prędkość konstrukcyjna 45 km/h.'],
                ['question' => 'Czy dawna karta motorowerowa zachowuje ważność?', 'answer' => 'Posiadacze dawnej karty motorowerowej mogą ją wymienić na prawo jazdy kategorii AM.'],
                ['question' => 'Ile pytań jest na egzaminie teoretycznym AM?', 'answer' => 'Egzamin obejmuje 32 pytania: 20 podstawowych i 12 specjalistycznych.'],
                ['question' => 'Ile punktów trzeba zdobyć, żeby zdać egzamin AM?', 'answer' => 'Wynik pozytywny wymaga co najmniej 68 punktów z 74 możliwych.'],
                ['question' => 'Ile trwa egzamin teoretyczny AM?', 'answer' => 'Część teoretyczna trwa 25 minut.'],
                ['question' => 'Czy posiadacz kategorii B potrzebuje dodatkowo AM?', 'answer' => 'Nie. Kategoria B obejmuje również uprawnienia do pojazdów określonych dla kategorii AM.'],
            ];
        }

        if ($code === 'A1') {
            return [
                ['question' => 'Od ilu lat można zrobić prawo jazdy A1?', 'answer' => 'Kategorię A1 można uzyskać od 16 lat. Osoba niepełnoletnia potrzebuje pisemnej zgody rodzica lub opiekuna.'],
                ['question' => 'Jakim motocyklem można jeździć na A1?', 'answer' => 'Motocyklem o pojemności do 125 cm³, mocy do 11 kW i stosunku mocy do masy własnej nieprzekraczającym 0,1 kW/kg. Wszystkie te warunki muszą być spełnione.'],
                ['question' => 'Czy A1 obejmuje skutery?', 'answer' => 'Decydują parametry i klasyfikacja konkretnego pojazdu. Kategoria A1 obejmuje motocykle spełniające jej wymagania oraz pojazdy kategorii AM.'],
                ['question' => 'Czy A1 obejmuje kategorię AM?', 'answer' => 'Tak. Posiadacz kategorii A1 może również kierować pojazdami kategorii AM.'],
                ['question' => 'Czy na kat. B można jeździć motocyklem 125 cm³?', 'answer' => 'Po co najmniej 3 latach posiadania kategorii B można w Polsce prowadzić motocykl do 125 cm³, do 11 kW i do 0,1 kW/kg.'],
                ['question' => 'Czy kat. B daje kategorię A1?', 'answer' => 'Nie. Uprawnienie do prowadzenia określonego motocykla na podstawie kategorii B nie jest równoznaczne z uzyskaniem kategorii A1.'],
                ['question' => 'Ile pytań jest na egzaminie teoretycznym A1?', 'answer' => 'Egzamin składa się z 32 pytań: 20 podstawowych i 12 specjalistycznych.'],
                ['question' => 'Ile punktów trzeba zdobyć, żeby zdać egzamin A1?', 'answer' => 'Wynik pozytywny wymaga co najmniej 68 punktów z 74 możliwych.'],
                ['question' => 'Ile trwa egzamin teoretyczny A1?', 'answer' => 'Na cały egzamin przewidziano 25 minut.'],
            ];
        }

        if ($code === 'T') {
            return [
                ['question' => 'Od ilu lat można zrobić prawo jazdy kat. T?', 'answer' => 'Prawo jazdy kategorii T można uzyskać od 16 lat. Osoba niepełnoletnia potrzebuje pisemnej zgody rodzica lub opiekuna.'],
                ['question' => 'Czy kategorią T można prowadzić ciągnik z przyczepą?', 'answer' => 'Tak. Kategoria T obejmuje zespół złożony z ciągnika rolniczego albo pojazdu wolnobieżnego z przyczepą lub przyczepami.'],
                ['question' => 'Czy prawo jazdy kat. T pozwala prowadzić samochód?', 'answer' => 'Nie. Kategoria T nie obejmuje samochodów osobowych kategorii B.'],
                ['question' => 'Czy kat. T obejmuje pojazdy wolnobieżne?', 'answer' => 'Tak. Obejmuje pojazd wolnobieżny, a także zespół takiego pojazdu z przyczepą lub przyczepami.'],
                ['question' => 'Czy kat. T obejmuje kategorię AM?', 'answer' => 'Tak. Posiadacz kategorii T może kierować również pojazdami określonymi dla kategorii AM.'],
                ['question' => 'Czy kategorią T można prowadzić każdego quada?', 'answer' => 'Nie. Uprawnienie zależy od prawnej klasyfikacji pojazdu; kategoria T obejmuje pojazdy kategorii AM, w tym spełniające jej wymagania czterokołowce lekkie.'],
                ['question' => 'Czy kategoria B wystarczy do prowadzenia ciągnika?', 'answer' => 'W Polsce kategoria B obejmuje ciągnik rolniczy i pojazd wolnobieżny, także z przyczepą lekką. Kategoria T daje szersze uprawnienia do zespołów z przyczepą lub przyczepami.'],
                ['question' => 'Jak przygotować się do testów kat. T?', 'answer' => 'Łącz naukę pytań z analizą błędów. Powtarzaj znaki, pierwszeństwo oraz zagadnienia dotyczące ciągników, przyczep i bezpiecznego manewrowania.'],
            ];
        }

        if ($code === 'D') {
            return [
                ['question' => 'Ile pytań jest na egzaminie teoretycznym kat. D?', 'answer' => 'Egzamin zawiera 32 pytania: 20 z wiedzy podstawowej i 12 z wiedzy specjalistycznej.'],
                ['question' => 'Ile trwa egzamin teoretyczny kat. D?', 'answer' => 'Na rozwiązanie egzaminu przewidziano 25 minut.'],
                ['question' => 'Ile punktów trzeba zdobyć, żeby zdać?', 'answer' => 'Wynik pozytywny wymaga co najmniej 68 punktów z 74 możliwych.'],
                ['question' => 'Czy pytania kat. D różnią się od pytań kat. B?', 'answer' => 'Część podstawowa dotyczy ogólnych zasad ruchu drogowego, a część specjalistyczna obejmuje zagadnienia właściwe dla kierowania autobusem.'],
                ['question' => 'Czy kategorią D można prowadzić autobus z przyczepą?', 'answer' => 'Tak, z przyczepą lekką o DMC do 750 kg. Do prowadzenia autobusu z przyczepą inną niż lekka potrzebna jest odpowiednia kategoria D+E.'],
                ['question' => 'Ile trzeba mieć lat na prawo jazdy kat. D?', 'answer' => 'Standardowo 24 lata. Po uzyskaniu odpowiedniej kwalifikacji wstępnej możliwe są niższe limity wieku; w określonych przewozach krajowych od 3 września 2026 r. także 20 lub 18 lat.'],
                ['question' => 'Czy kat. D i D+E to to samo?', 'answer' => 'Nie. Kategoria D obejmuje autobus z przyczepą lekką, a D+E rozszerza uprawnienie na zestaw z przyczepą inną niż lekka.'],
                ['question' => 'Czy samo prawo jazdy D wystarczy do pracy jako kierowca autobusu?', 'answer' => 'Zawodowy przewóz osób może wymagać także odpowiedniej kwalifikacji zawodowej i spełnienia innych wymagań przewidzianych przepisami.'],
            ];
        }

        if ($code === 'C') {
            return [
                ['question' => 'Ile pytań jest na egzaminie teoretycznym kat. C?', 'answer' => 'Egzamin składa się z 32 pytań: 20 podstawowych i 12 specjalistycznych.'],
                ['question' => 'Ile trwa egzamin teoretyczny kategorii C?', 'answer' => 'Na rozwiązanie egzaminu przewidziano 25 minut.'],
                ['question' => 'Ile punktów trzeba zdobyć, żeby zdać?', 'answer' => 'Wynik pozytywny wymaga co najmniej 68 punktów z 74 możliwych.'],
                ['question' => 'Czy pytania kat. C są inne niż pytania kat. B?', 'answer' => 'Część podstawowa sprawdza ogólne zasady ruchu drogowego, natomiast pytania specjalistyczne dotyczą kategorii, na którą zdajesz.'],
                ['question' => 'Czy do kategorii C trzeba mieć kategorię B?', 'answer' => 'Tak. Uzyskanie prawa jazdy kategorii C wymaga posiadania kategorii B.'],
                ['question' => 'Czy na kat. C można prowadzić ciężarówkę z naczepą?', 'answer' => 'Do prowadzenia typowego zestawu ciągnika siodłowego z naczepą potrzebna jest odpowiednia kategoria C+E. Sama kategoria C obejmuje ciężarówkę z przyczepą lekką.'],
                ['question' => 'Ile trzeba mieć lat na kategorię C?', 'answer' => 'Standardowo 21 lat. Przy odpowiedniej kwalifikacji wstępnej kategorię C można uzyskać od 18 lat.'],
                ['question' => 'Czy kat. C wystarczy do pracy jako kierowca zawodowy?', 'answer' => 'Nie zawsze. Zawodowe wykonywanie przewozów wymaga także kwalifikacji zawodowej i odpowiednich dokumentów, zależnie od rodzaju przewozu.'],
            ];
        }

        if ($code === 'B') {
            return [
                ['question' => 'Ile pytań jest na egzaminie kat. B?', 'answer' => 'Egzamin teoretyczny zawiera 32 pytania: 20 podstawowych i 12 specjalistycznych.'],
                ['question' => 'Ile trwa egzamin teoretyczny kat. B?', 'answer' => 'Na rozwiązanie egzaminu przewidziano 25 minut.'],
                ['question' => 'Ile punktów trzeba zdobyć, żeby zdać?', 'answer' => 'Wynik pozytywny wymaga co najmniej 68 punktów z 74 możliwych.'],
                ['question' => 'Czy pytania na egzaminie kat. B są jednokrotnego wyboru?', 'answer' => 'Tak. W części podstawowej wybierasz TAK lub NIE, a w części specjalistycznej jedną z odpowiedzi A, B lub C.'],
                ['question' => 'Czy można wrócić do poprzedniego pytania?', 'answer' => 'Nie. Po zakończeniu pytania i przejściu dalej nie można ponownie udzielić na nie odpowiedzi.'],
                ['question' => 'Od ilu lat można uzyskać prawo jazdy kat. B?', 'answer' => 'Od 17 lat. Do ukończenia 18 lat uprawnienie jest ograniczone do terytorium Polski i obowiązują dodatkowe warunki kierowania pojazdem.'],
                ['question' => 'Jak przygotować się do egzaminu kat. B?', 'answer' => 'Rozwiązuj pytania z poszczególnych działów, analizuj błędy i regularnie wykonuj testy próbne w limicie czasu.'],
                ['question' => 'Czy państwowy egzamin teoretyczny ma bezterminowy wynik?', 'answer' => 'Pozytywny wynik państwowego egzaminu teoretycznego co do zasady nie traci ważności z upływem czasu.'],
            ];
        }

        if ($code === 'A') {
            return [
                [
                    'question' => 'Ile pytań jest na egzaminie teoretycznym na prawo jazdy?',
                    'answer' => 'Egzamin teoretyczny składa się z 32 pytań — 20 podstawowych i 12 specjalistycznych.',
                ],
                [
                    'question' => 'Ile punktów trzeba zdobyć, żeby zdać teorię?',
                    'answer' => 'Do uzyskania wyniku pozytywnego wymagane jest minimum 68 punktów z 74 możliwych.',
                ],
                [
                    'question' => 'Czy można wrócić do poprzedniego pytania?',
                    'answer' => 'Nie. Po zakończeniu danego pytania i przejściu dalej nie można ponownie do niego wrócić.',
                ],
                [
                    'question' => 'Jakie testy wybrać na kategorię A?',
                    'answer' => 'Jeżeli przygotowujesz się do egzaminu na motocykl bez ograniczenia mocy, wybierz testy na prawo jazdy kat. A.',
                ],
                [
                    'question' => 'Czy warto robić próbne testy na prawo jazdy?',
                    'answer' => 'Tak. Próbne testy pomagają sprawdzić wiedzę, tempo odpowiadania i przygotowanie do warunków egzaminacyjnych.',
                ],
                [
                    'question' => 'Jak najlepiej przygotować się do egzaminu teoretycznego?',
                    'answer' => 'Najlepiej połączyć naukę pytań, analizowanie błędów, poznawanie przepisów oraz regularne wykonywanie pełnych testów egzaminacyjnych.',
                ],
            ];
        }

        return [
            [
                'question' => "Jak wygląda egzamin teoretyczny na kategorię {$code}?",
                'answer' => "Egzamin teoretyczny na kategorię {$code} trwa 25 minut i obejmuje 32 pytania: 20 z wiedzy podstawowej oraz 12 z wiedzy specjalistycznej. Maksymalnie można zdobyć 74 punkty, a wynik pozytywny wymaga co najmniej 68 punktów.",
            ],
            [
                'question' => "Od jakiego wieku można uzyskać kategorię {$code}?",
                'answer' => "Standardowy minimalny wiek dla kategorii {$code} to {$content['minimum_age']}. {$content['age_note']}",
            ],
            [
                'question' => "Czego dotyczą pytania na kategorię {$code}?",
                'answer' => 'Pytania łączą ogólne przepisy ruchu drogowego z wiedzą specjalistyczną dla tej kategorii. Szczególną uwagę warto poświęcić takim obszarom jak: '.implode(', ', $content['focus']).'.',
            ],
            [
                'question' => "Czy pytania na kategorię {$code} pochodzą z oficjalnej bazy?",
                'answer' => 'PrawkoNaRaz korzysta z aktualnej bazy pytań egzaminacyjnych publikowanej pod nadzorem Ministra Infrastruktury i udostępnia pytania przypisane do odpowiedniej kategorii prawa jazdy.',
            ],
            [
                'question' => "Jak przygotować się do testu na kategorię {$code}?",
                'answer' => 'Najlepiej łączyć pełne testy próbne z nauką pytań działami i regularnym powrotem do błędów. Przed egzaminem warto przećwiczyć format 32 pytań i pracę pod limitem 25 minut.',
            ],
        ];
    }
}

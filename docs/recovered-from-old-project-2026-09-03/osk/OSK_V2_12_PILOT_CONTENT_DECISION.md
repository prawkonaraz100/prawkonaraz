# OSK V2.12 - karta decyzji tresci pilota kategorii B

**Status:** źródłowa wersja V1 została zatwierdzona i opublikowana lokalnie. Wykonano jeden kontrolowany pilot na danych testowych: jedno lokalne OSK, jeden testowy kursant i jeden enrollment. Nie jest to deklaracja współpracy z prawdziwym OSK ani uruchomienie produkcyjne.

**Aktualizacja Etapow 5J-5K:** oba bezpieczne flow utworzenia zapisu oraz osobna, audytowalna brama kursu przeszły lokalny pilot. Szczegóły, wynik i rollback: [Etap 5J](./OSK_V2_12_STAGE_5J_ENROLLMENT_PROVISIONING.md) oraz [Etap 5K](./OSK_V2_12_STAGE_5K_COURSE_PILOT_ACTIVATION.md).

## Cel

Ta karta zbiera decyzje potrzebne do utworzenia jednego, niewielkiego programu
pilota w nowym module Nauka teorii OSK. Nie jest seedem, regulaminem ani
dokumentem formalnym. Nie wpisujemy tu danych osobowych kursantow ani sekretow
dostepowych.

Technika jest gotowa na lokalny pilot. Treść musi mieć jawne źródło przy każdym
kroku, a jej zatwierdzenie pozostaje oddzielną akcją administratora. Publikacja
jest kolejną, świadomą akcją administratora; nie włącza dostępu kursantowi.

## 1. Decyzja biznesowa

### Stan decyzji na 2026-08-28

- Zakres roboczy jest potwierdzony: kategoria `B`, moduł `Pierwszeństwo i obserwacja drogi` oraz trzy krótkie lekcje.
- Przygotowano lokalny kurs `osk-teoria-b-pierwszenstwo-zrodlowy` z opublikowaną, zamrożoną wersją `V1`. Ma jeden moduł, trzy lekcje i 12 kroków; jest aktywny wyłącznie dla zakończonego lokalnego pilota z jednym enrollmentem testowym.
- Źródłem każdego kroku jest oficjalny tekst jednolity ustawy `Prawo o ruchu drogowym` w ELI: <https://eli.gov.pl/api/acts/DU/2024/1251/text.html>. Rekord ISAP dla tekstu jednolitego i zmian jest pomocniczym punktem kontroli: <https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU20240001251>.
- Materiał jest parafrazą przepisów, nie kopią aktu prawnego. Nie użyto obrazów ani filmów, ponieważ dla nich nie ustalono jeszcze praw do wykorzystania.
- Każdy administrator panelu może zatwierdzić treść w `/admin/programy-nauki-osk`. Zatwierdzenie zapisuje czas i konto administratora; późniejsza zmiana modułu, lekcji, kroku albo ich kolejności automatycznie je cofa.
- Po zatwierdzeniu każdy administrator może użyć `Opublikuj wersję`. Akcja tworzy niezmienny snapshot programu; nie zmienia `is_active` kursu i nie tworzy enrollmentu ani dostępu pilota.
- Lokalny partner OSK i pierwszy enrollment testowy zostali utworzeni wyłącznie do smoke testu. Partner produkcyjny i pierwszy produkcyjny enrollment pozostają **nieustalone**. Zatwierdzenie merytoryczne nie jest decyzją o pilocie produkcyjnym.

- Partner OSK: lokalnie `OSK Zrodlowy Kurs B - PILOT LOKALNY` z `demo_only=true`; partner produkcyjny pozostaje do wskazania.
- Wlasciciel pilota: osoba odpowiedzialna za decyzje produktowe.
- Wlasciciel tresci: osoba akceptujaca material merytoryczny.
- Kategoria: na start tylko `B`.
- Cel pilota: edukacyjny test LessonPlayera, bez uznawania formalnego ukonczenia.
- Zakres osob: jeden aktualnie dopuszczony enrollment kontrolny, potem swiadomie wskazana mala grupa.

### Wynik lokalnego pilota

- Utworzono jedno OSK testowe, jedno konto kursanta testowego, jeden aktywny enrollment i jedną allow-listę.
- Kursant przeszedł lokalnie listę kursów, kurs, player, start sesji, heartbeat, zapis kroku i zamknięcie sesji.
- Operator OSK bez enrollmentu nie otrzymał dostępu do adresu kursanta; istniejące `/nauka` pozostało dostępne bez zmian.
- Nie zapisano surowego tokenu ani danych osobowych w dokumentacji. Nie wykonano deployu, formalnego ukończenia, evidence ani aktywacji produkcji.

### Checkpoint techniczny edytora

- Ręczny test lokalny z 2026-08-27 potwierdził pełny cykl redakcyjny w `DRAFT`: dodawanie, edycję, zmianę kolejności, wyszukiwanie oraz anulowanie formularzy modułów, lekcji i wszystkich sześciu typów kroków.
- Test wykrył i zamknął błąd zapisu opcjonalnego kontekstu pytania. Kontekst jest teraz zapisywany, wraca do formularza po odświeżeniu i ma test regresyjny.
- W lokalnym szkicu istnieją oznaczone dane `TEST UI`. Nie są merytoryczną treścią pilota, nie są publikowane i pozostają wyłącznie materiałem testowym do czasu osobnej decyzji o ich usunięciu.

### Dane wymagane przed pierwszą prawdziwą treścią

| Decyzja | Stan | Co trzeba wpisać przed redakcją treści |
| --- | --- | --- |
| Źródło merytoryczne | Uzupełnione dla draftu | ELI: `Prawo o ruchu drogowym`, Dz. U. 2024 poz. 1251, z późn. zm.; przy każdym kroku zapisano link i artykuł/ustęp. |
| Właściciel treści | Do ustalenia przed pilotem | Osoba odpowiedzialna za dalszą aktualizację materiału. |
| Osoba zatwierdzająca / publikująca | Zdecydowane technicznie | Każdy administrator panelu; system zapisuje konto i czas zatwierdzenia, a publikacja jest oddzielnie potwierdzana. |
| Zakres vertical slice | Uzupełniony lokalnie | Kategoria B, moduł `Pierwszeństwo i obserwacja drogi`; trzy lekcje: manewr, skrzyżowanie, przejście dla pieszych. |
| Media | Do potwierdzenia | Plik, źródło, licencja oraz opis alternatywny; bez tego nie dodajemy obrazu ani wideo. |
| Partner i środowisko pilota | Do potwierdzenia | Nazwa OSK, organizacja docelowa oraz właściciel jednego enrollmentu kontrolnego. |

### Przyklad lokalny przygotowany do smoke testu

- Partner: `OSK Bezpieczny Start - DEMO lokalne`.
- Kurs: `Teoria kategorii B - bezpieczne decyzje (DEMO)`.
- Material: dwa moduly, trzy krotkie lekcje i lacznie pietnascie krokow tekstowych/scenariuszowych. Zakres obejmuje obserwacje skrzyzowania, kolejnosc oceny pierwszenstwa oraz obserwacje przed przejsciem dla pieszych.
- Dane: operator i kursant z domeny `example.test`, oznaczeni jako konta testowe.
- Czas: seed wykorzystuje istniejaca pojedyncza polityke czasu; na pustym lokalnym srodowisku tworzy jedna lokalna polityke demonstracyjna. Gdy wykryje kilka aktywnych polityk, przerywa bez zmiany danych.
- Wersjonowanie: na pustej bazie powstaje kompletna wersja 1. Wczesniejsza, rozpoznana wersja lokalna z jedna lekcja jest kopiowana do wersji 2, a dodatkowe lekcje trafiaja tylko do nowego draftu przed publikacja.
- Widocznosc: poprzedni enrollment i jego dostep formalny pozostaja w bazie bez zmian, ale seed wylacza jego pilotowa allow-liste przez `TheoryLearningPilotAccessService`. Kursant widzi wiec tylko aktualna wersje przykladu.
- Ograniczenie: bez prawdziwych mediow, bez formalnego wyniku, bez evidence i bez uruchamiania produkcji.

## 2. Zrealizowany zakres źródłowego draftu

1. **Obserwacja przed manewrem** — obserwacja, szczególna ostrożność, zmiana pasa i pierwszeństwo pojazdu na docelowym pasie; źródła: art. 3 ust. 1 i art. 22 ust. 1, 4–5.
2. **Pierwszeństwo na skrzyżowaniu** — ocena sytuacji, zasada prawej strony i skręt w lewo; źródło: art. 25 ust. 1.
3. **Pieszy przy przejściu** — szczególna ostrożność, pierwszeństwo pieszego i zakaz omijania pojazdu zatrzymanego przed przejściem; źródło: art. 26 ust. 1–3.

Każda lekcja ma cztery krótkie kroki: wprowadzenie, wyjaśnienie, sytuację do oceny oraz sprawdzenie albo podsumowanie. To materiał edukacyjny bez formalnej oceny wyniku, evidence i ukończenia.

## 3. Minimalny vertical slice

Nie tworzymy od razu calego kursu kategorii B. Pierwszy program ma miec:

1. Jeden modul z jednoznacznym celem nauki.
2. Jedna lub dwie lekcje.
3. Od trzech do szesciu krokow na lekcje.
4. Najwyzej jeden obraz lub film, tylko z zatwierdzonym prawem do wykorzystania.
5. Jedno pytanie refleksyjne, bez zapisu wyniku i bez formalnego assessmentu.

Proponowany opis do uzupelnienia:

```text
Kod kursu:              ____________________
Nazwa kursu:            ____________________
Kod modulu:              ____________________
Nazwa modulu:            ____________________
Cel modulu:              ____________________
Kod lekcji:              ____________________
Nazwa lekcji:            ____________________
Wersja wymagan prawnych: ____________________
```

## 4. Kontrakt tresci krokow

Renderer obsluguje ponizsze typy. Tresc musi pasowac do pola `content` i miec
zrodlo oraz akceptacje wlasciciela tresci.

| Typ kroku | Minimalne pola `content` | Zastosowanie |
| --- | --- | --- |
| `content` | `body` | krotkie wyjasnienie lub zasada |
| `image` | `src`, `alt`, opcjonalnie `caption` | zatwierdzona grafika |
| `video` | `src`, opcjonalnie `caption` | zatwierdzony film |
| `scenario` | `prompt`, opcjonalnie `context` | sytuacja do przemyslenia |
| `question` | `prompt`, opcjonalnie `choices` | pytanie bez oceny wyniku |
| `summary` | `body` | krotkie podsumowanie lekcji |

Do kazdego kroku należy zapisać w panelu:

- nazwę źródła, jego adres i artykuł, punkt albo inne miejsce w materiale;
- datę sprawdzenia źródła, jeśli materiał jest przygotowany automatycznie lub redakcyjnie;
- ograniczenia licencyjne dla mediów;
- podstawową wersję przepisu, jeśli treść odnosi się do prawa.

Zatwierdzenie jest możliwe dopiero, gdy wszystkie kroki wersji mają nazwę źródła, poprawny adres i wskazanie przepisu lub dokumentu.

## 5. Kryteria przed przygotowaniem seeda

- [x] Istnieje lokalny partner testowy i aktywna organizacja wyłącznie dla smoke testu.
- [ ] Istnieje zatwierdzony partner OSK i aktywna organizacja w srodowisku produkcyjnym.
- [x] Jest zatwierdzony kurs kategorii B oraz jego opublikowana wersja programu.
- [x] Wszystkie kroki źródłowego draftu mają źródło i wskazanie przepisu.
- [x] Wybrany administrator zatwierdził aktualną treść po redakcyjnym przeglądzie.
- [x] Program daje się opublikować jako niezmienny snapshot przez oddzielną akcję administratora po zatwierdzeniu treści.
- [x] Wlasciciel/delegowany operator OSK oraz administrator platformy maja odrebne, audytowalne flow tworzenia enrollmentu.
- [x] Wybrano jeden aktywny enrollment właściciela lokalnego testu.
- [x] Potwierdzono, ze lokalny pilot nie oznacza formalnego ukonczenia ani dokumentacji PAPER.

## 6. Lokalny pilot wykonany i kolejna decyzja

1. Lokalny pilot został wykonany zgodnie z runbookiem Etapu 5K, bez deployu i bez zmiany flagi środowiskowej przez ten etap.
2. Przed pierwszym pilotem produkcyjnym wskazać prawdziwe OSK, właściciela pilota, liczbę kursantów, kanał wsparcia oraz osobę odpowiedzialną za rollback.
3. Wdrożyć osobną, zatwierdzoną paczkę z produkcyjną flagą nadal ustawioną na `false`.
4. Dopiero po wdrożeniu i osobnym potwierdzeniu włączyć produkcyjną flagę na minimalny kontrolowany okres oraz dopisać tylko zatwierdzone enrollmenty do allow-listy.
5. W razie problemu najpierw wyłączyć flagę, następnie allow-listę konkretnego enrollmentu, a potem wstrzymać kurs; nie usuwać danych ani audytu.

## 7. Granice

Ten etap nadal nie tworzy formalnego evidence, uznania ukonczenia, assessmentu,
egzaminu wewnetrznego, dokumentow PAPER, maili, QR ani linku w glownym menu.
Kazdy z tych obszarow wymaga osobnego kontraktu przed implementacja.

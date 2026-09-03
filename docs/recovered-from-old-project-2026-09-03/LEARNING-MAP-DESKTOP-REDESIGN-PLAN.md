# Mapa Nauki - Plan Przebudowy Desktopu

**Status:** implementacja desktopu zakonczona lokalnie - oczekuje na akceptacje wizualna i pozostale scenariusze dostepu
**Data utworzenia:** 2026-08-14
**Branch:** `codex/learning-layout-refresh`
**Zakres:** `/nauka` na desktopie. Widok mobilny pozostaje bez zmian.

## Cel

Zamienic obecny, gesty panel konfiguracji nauki w czytelna mape postepu dla wybranej kategorii prawa jazdy. Kursant ma od razu rozumiec:

1. gdzie jest na swojej drodze nauki,
2. ktory dzial warto zrobic teraz,
3. gdzie ma pytania do poprawy,
4. jak wrocic do aktywnej sesji,
5. jak przejsc do pozostalych trybow nauki.

Mapa jest nowym sposobem wyboru dzialu w nauce klasycznej. Nie jest nowym systemem sesji, filtrow ani postepu.

## Zasada bezpieczenstwa

Nowy layout nie moze zmienic sposobu dzialania nauki. Komponent nadrzedny zachowuje obecne formularze, walidacje, adresy i metody uruchamiania sesji. Mapa jedynie emituje wybor dzialu lub intencje rozpoczecia nauki.

Nie zmieniamy w ramach tego zadania:

- tabel bazy danych i migracji,
- tras HTTP,
- `StudySessionController` ani `StudySessionAnswerController`,
- `study-sessions.store`,
- reguly dostepu Premium i PJM,
- logiki listy blednych pytan,
- mobilnego dashboardu i mobilnego menu.

## Stan zastany

### Co juz mamy

- Trasa `/nauka` renderuje `Session/Index` przez `SessionPageController`.
- `LearningHomePayloadBuilder` przekazuje dane potrzebne do mapy: kategorie, grupy dzialow, liczniki pytan, postep, bledne pytania, aktywna sesje, dostep i tryby.
- `group_options` ma kolejnosc i informacje potrzebne do pokazania dzialow.
- Obecne funkcje startu sesji sa sprawdzone testami funkcjonalnymi.
- Mobilny widok jest juz wydzielony do `MobileLearningDashboard.vue` i ukryty na desktopie.
- Utworzono branch `codex/learning-layout-refresh`.

### Problemy obecnego desktopu

- `resources/js/Pages/Session/Index.vue` ma okolo 2 778 linii i laczy logike, desktop oraz historyczne wersje widoku.
- Ten sam wybor trybu wystepuje w lewej nawigacji i w sekcji "Szybkie akcje".
- Dwa stale panele boczne zwezaja glowny obszar na mniejszych desktopach.
- Aktywna sesja nie ma dostatecznie wyraznego priorytetu wizualnego.
- W pliku sa permanentnie ukryte bloki poprzednich layoutow (`class="hidden"`). Sa w bundlu i DOM-ie, ale nie powinny byc usuwane przed przejeciem ich funkcji przez nowy widok.
- Dane `recent_learning_activity`, `weekly_activity` i `study_time` sa obecnie puste. Nie projektujemy na ich podstawie pozornie dzialajacych wykresow.

## Audyt funkcji, ktore musza pozostac nienaruszone

### Kontrakt strony i danych

| Obszar | Obecne zrodlo prawdy | Wymaganie dla mapy |
| --- | --- | --- |
| Strona nauki | `SessionPageController` i `LearningHomePayloadBuilder` | Nie zmieniac trasy `/nauka`, jej dostepu ani struktury danych Inertia |
| Start sesji | `POST study-sessions.store` | Kazdy przycisk nowego widoku ma wywolywac istniejacy formularz, nie nowy endpoint |
| Kategoria | `profile.product.update` i `studyContext` | Zmiana kategorii musi nadal odswiezyc dzialy, postep, dostep i adres powrotu |
| Dzialy | `group_options` | Kolejnosc przystankow wynika z danych backendu, nie z numeracji wpisanej na stale w UI |
| Postep | `learning_dashboard.course_progress` i liczniki dzialow | Mapa pokazuje tylko dane przekazane przez backend |
| Aktywna sesja | `learning_dashboard.active_session` | Powrot korzysta z `resume_url`; nowa sesja otwiera istniejace potwierdzenie zastapienia |
| Dostep | decyzje Premium i PJM | Widok nie moze obejsc blokady, aktywacji ani ograniczenia kategorii |

### Tryby nauki i ich niezmienialne zachowanie

| Tryb | Obecne zachowanie | Kontrakt do zachowania |
| --- | --- | --- |
| Nauka klasyczna | `mode=learn`, `ui_shell=exam_like` | Dzial, status, zakres i kolejnosc sa wybierane przez kursanta |
| Zen mode | `mode=learn`, `ui_shell=zen` | Wymusza `question_status=all` i `randomize_order=false`; nadal korzysta z wybranego dzialu |
| Egzamin probny | `mode=exam`, `ui_shell=exam` | Backend wymusza 32 pytania, pelna kategorie, brak dzialu, `scope=all`, `status=all` i stala kolejnosc |
| Trener pamieci | osobny adres `/trener-pamieci` | Nie uruchamiac go przez formularz klasycznej sesji; zachowac licznik pytan do powtorki |
| Bledy z calego kursu | osobny `globalIncorrectForm` | Ustawia brak dzialu, `scope=all`, stala kolejnosc oraz `mistake_list` albo historyczne `incorrect` |
| Znaki drogowe | osobny adres `session.traffic-signs` | Zachowac wejscie do osobnego modulu, bez udawania klasycznej sesji |
| Ranking | osobny adres `session.ranking` z kategoria | Zachowac przekazanie wybranej kategorii oraz blokade dostepu |
| PJM | osobny adres `session.pjm` i wlasna regola dostepu | Dla konta PJM bez Premium blokowac klasyczne tryby, tak jak obecnie |

**Wazne doprecyzowanie:** aktywna desktopowa nawigacja pokazuje obecnie `PJM` (warunkowo), `Nauke klasyczna`, `Zen mode`, `Trener pamieci` i `Egzamin`. Kod obslugi znakow oraz rankingu istnieje na stronie i w systemie, ale nie jest obecnie podstawowa pozycja tej desktopowej nawigacji. W nowym widoku mozemy je pokazac wyrazniej, lecz nie wolno usunac ich obecnych adresow ani reguly dostepu.

### Filtry i opcje klasycznej sesji

| Kontrolka | Dostepne wartosci | Co robi naprawde | Regula przebudowy |
| --- | --- | --- | --- |
| Dzial | pozycje z `group_options` | Ustawia `question_topic_id` | Klikniecie przystanku musi wywolywac ten sam wybor |
| Status pytan | `all`, `unanswered`, `incorrect`, `correct`, `memorized`, warunkowo `mistake_list` | Filtruje pulę pytan przed startem | Wszystkie dostepne wartosci desktopu pozostaja osiagalne w panelu dzialu |
| Zakres | `all`, `basic`, `specialist` | Ogranicza widoczne dzialy i pule pytan | Przelaczenie zakresu musi zmieniac zarowno mape, jak i poprawny wybor dzialu |
| Kolejnosc | stala lub losowa | W klasycznej sesji stala kolejnosc korzysta z `QuestionLearningOrderService`; losowa omija ten porzadek | Nie wolno zamienic "stalej" kolejnosci w prosty sort po ID |
| Liczba pytan | liczona z wybranego dzialu i statusu | Aktualizuje `question_count` przed wyslaniem formularza | Licznik na mapie i w panelu musi pochodzic z tych samych danych |
| Lista bledow | `mistake_list`, gdy funkcja jest wlaczona | Korzysta z aktywnej, zarzadzanej listy bledow kursanta | Widok sprawdza flagi konfiguracji; nie zaklada dostepnosci listy dla kazdego konta |

### Zachowania pomocnicze do zachowania

- Strona odtwarza ostatnio uzyty dzial oraz rodzaj ekranu nauki z zakonczonych sesji.
- Zmiana zakresu podstawowy/specjalistyczny wybiera pierwszy widoczny dzial, gdy poprzedni przestaje pasowac.
- Wybrany dzial i status aktualizuja liczbe pytan przed wyslaniem formularza.
- Bledy walidacji `license_category_id`, `question_topic_id` i `question_status` musza byc widoczne w nowym panelu.
- Menu wyboru dzialu zamyka sie po kliknieciu poza nim lub klawiszu Escape. Nowy panel mapy musi zachowac rownowazna obsluge klawiatury.
- Baner zaproszenia znajomego jest niezalezny od mapy i moze zostac ukryty przez kursanta bez wplywu na nauke.
- Link do szczegolowych statystyk kategorii pozostaje dostepny z panelu postepu.

## Koncepcja docelowa

### Nawigacja trybow

Lewa nawigacja staje sie jednym miejscem wyboru trybu. Jej podstawowe pozycje to obecne: Nauka klasyczna, Zen mode, Egzamin probny, Trener pamieci oraz PJM, gdy dostepny.

Znaki drogowe i Ranking otrzymaja wyrazne wejscia pomocnicze, ale zachowaja swoje odrebne adresy. Nie beda udawaly przystankow klasycznej mapy.

Tylko **Nauka klasyczna** pokazuje mape dzialow. Pozostale tryby maja krotkie, dedykowane panele wejscia, ale wykorzystuja obecne adresy i akcje.

### Mapa nauki klasycznej

- Dzialy generujemy dynamicznie z `group_options`, bez statycznego obrazka.
- Przystanki sa prawdziwymi przyciskami, dostepnymi klawiatura.
- Dekoracyjna droga nie przekazuje informacji bez tekstu lub koloru pomocniczego.
- Uklad jest "serpentyna": jasna droga SVG prowadzi przez kolejne rzedy malych przystankow i zawraca przy krawedziach.
- Desktopowa mapa ma stale siedem torow: pierwszy rzad zaczyna okraglym `START`, potem sa szesc przystankow; ostatni konczy sie `META`.
- Kazdy przystanek jest prawdziwym przyciskiem z numerem nad kaflem, kolorowa ikona i nazwa dzialu. Kafel nie jest obrazkiem ani statycznym zrzutem ekranu.
- Kolory etapow trasy przechodza od niebieskiego przez turkus, zielony i pomarancz do czerwieni. Szczegoly postepu pozostaja w dostepnej nazwie przycisku oraz w panelu konfiguracji po wyborze dzialu.
- Ikony sa przypisywane lokalnie na podstawie stabilnej nazwy lub klucza dzialu z fallbackiem. Nie sa elementem zrzutu ekranu ani obrazka z tekstem.

### Status przystanku

| Stan | Znaczenie | Prezentacja |
| --- | --- | --- |
| Nie rozpoczeto | Brak rozwiazanych pytan | Neutralny przystanek |
| Nastepny krok | Rekomendowany dzial | Niebieski akcent i etykieta |
| W trakcie | Dzial ma czesciowy postep | Wypelnienie postepu |
| Do poprawy | Dzial zawiera aktywne bledne pytania | Dyskretny czerwony akcent i licznik |
| Ukonczony | Wszystkie pytania zostaly przerobione | Zielony status |

Kolor nie moze byc jedynym nosnikiem znaczenia. Kazdy stan dostaje tekst, liczbe albo odpowiednia etykiete dostepnosci.

### Staly panel konfiguracji dzialu

Klikniecie przystanku nie uruchamia sesji w ciemno. Aktualizuje staly, szeroki panel konfiguracji pod mapa. Nie utrzymujemy starego, wysokiego panelu po prawej stronie, bo ograniczalby czytelnosc mapy.

Panel zachowuje istniejace opcje:

- wybrany dzial,
- zestaw pytan: nieprzerobione, wszystkie, poprawne, bledne, utrwalone i lista bledow, gdy dostepna,
- zakres: wszystkie, podstawowe, specjalistyczne,
- kolejnosc: stala lub losowa,
- glowny przycisk rozpoczecia dla wybranego dzialu.

Na dole panelu znajduja sie dwie rozne akcje w jednym rzedzie:

1. `Rozpocznij nauke` - przycisk glowny. Uruchamia sesje zgodnie z aktualnie wybranym dzialem, zestawem, zakresem i kolejnoscia.
2. `Powtorz bledy z calego kursu - {liczba}` - przycisk drugorzedny z ikona powtorki i aktualnym licznikiem. Uruchamia istniejacy globalny formularz `globalIncorrectForm`, niezaleznie od wybranego dzialu.

Globalne bledy nie sa przystankiem na mapie ani kolejnym trybem nauki. Sa przekrojowa akcja dotyczaca calego kursu, dlatego pozostaja bezposrednio obok glownego startu. Przycisk jest widoczny tylko wtedy, gdy lista zawiera pytania; nie wolno go mylic z filtrem "bledne" w aktualnie wybranym dziale.

Klikniecie "Kontynuuj nauke" ustawia te same wartosci, co obecny formularz, a nastepnie wywoluje obecna logike startu sesji.

Panel nie pokazuje opcji, ktore sa wymuszone przez wybrany tryb: Zen ma zawsze pelny zestaw w stalej kolejnosci, a egzamin ma wlasne, nienegocjowalne zasady 32 pytan.

### Aktywna sesja

Gdy istnieje aktywna sesja, ekran otrzymuje priorytetowy panel nad mapa:

- tytul i postep sesji,
- przycisk "Wroc do sesji",
- drugorzedna akcja konfiguracji nowej sesji.

Rozpoczecie nowej sesji nadal korzysta z obecnego dialogu potwierdzajacego zastapienie sesji.

## Architektura docelowa

`Session/Index.vue` pozostaje wlascicielem danych i logiki. Nowe komponenty nie tworza osobnych formularzy ani nie wykonuja wlasnych zapytan do backendu.

Planowany podzial:

- `DesktopLearningHub.vue` - kontener desktopu i uklad glownej przestrzeni,
- `LearningModeNavigation.vue` - nawigacja trybow,
- `ClassicLearningMap.vue` - generowanie drogi i przystankow,
- `LearningMapStop.vue` - pojedynczy przystanek,
- `LearningStartPanel.vue` - panel konfiguracji wybranego dzialu,
- `ActiveLearningSessionBanner.vue` - priorytetowy powrot do sesji.

Nazwy sa robocze; mozemy je dostosowac do istniejacych konwencji katalogow przed implementacja.

Przeplyw danych:

1. `Session/Index.vue` przekazuje dane do desktopowego kontenera.
2. Mapa emituje `select-topic(topicId)`.
3. Kontener przekazuje wybor do istniejacego `chooseTopic`.
4. Panel startu emituje zmiany statusu, zakresu i kolejnosci.
5. Rodzic wywoluje obecne `startLearning` albo `startGlobalIncorrectLearning`.

## Etapy implementacji

### Etap 0 - Kontrakt i baza regresji

**Status: w toku**

- [x] Audyt aktualnej architektury `/nauka`.
- [x] Audyt wszystkich trybow, filtrow, kolejnosci i sposobu startu sesji.
- [x] Audyt normalizacji trybu egzaminacyjnego, Zen oraz globalnej listy bledow po stronie backendu.
- [x] Potwierdzenie, ze mobile jest osobnym komponentem.
- [x] Potwierdzenie, ze payload backendu wystarcza dla pierwszej wersji mapy.
- [x] Identyfikacja ukrytych historycznych blokow desktopu.
- [ ] Zapisanie referencyjnych screenshotow obecnego desktopu dla kluczowych stanow konta.
- [ ] Spisanie mapowania ikon dla wszystkich dzialow wybranej kategorii.
- [ ] Potwierdzenie ostatecznej stylistyki przystankow i drogi.

### Etap 1 - Wydzielenie desktopu

**Status: zakonczony lokalnie**

- [x] Wydzielono aktywny desktop do `Partials/DesktopLearningMap.vue`.
- [x] `Session/Index.vue` pozostaje wlascicielem formularzy, endpointow i dialogu zastapienia sesji.
- [x] Stary desktop zostal zachowany w `Session/Index.vue` i ukryty tylko wizualnie.
- [x] Przeszedl build Vue oraz wybrane testy strony sesji.

**Punkt kontrolny:** obecny desktop dziala identycznie, ale ma oddzielona strukture komponentow.

### Etap 2 - Mapa nauki klasycznej

**Status: zakonczony lokalnie**

- [x] Dodano dynamiczna serpentynowa trase SVG dla `group_options`; kolejnosc dzialow nadal pochodzi z backendu.
- [x] Przebudowano przystanki do zwartego ukladu zgodnego z zaakceptowana referencja: numer nad kaflem, ikona, nazwa, `START` i `META`.
- [x] Dodano dostepne opisy stanu: nastepny dzial, do poprawy, przerobiony, w toku i nierozpoczety wraz z liczba przerobionych pytan.
- [x] Dodano semantyczne ikony dzialow z bezpiecznym fallbackiem trasy.
- [x] Przystanki sa natywnymi przyciskami z focusem, stanem `aria-pressed` i czytelnymi nazwami.
- [x] Sprawdzono screenshoty przy 1024 i 1280 px oraz widok mobilny przy 390 px.

**Punkt kontrolny:** klikniecie przystanku zmienia wybrany dzial, ale nie zmienia jeszcze zachowania startu sesji.

### Etap 3 - Panel startu i aktywna sesja

**Status: zakonczony lokalnie**

- [x] Panel pod mapa korzysta z obecnych opcji: zestawu pytan, zakresu oraz kolejnosci.
- [x] Dodano wyrazny panel aktywnej sesji z powrotem do sesji.
- [x] Dodano drugorzedna akcje `Bledy z calego kursu - {liczba}` oraz zachowano lacze do zarzadzania lista.
- [x] Klikniecie startu przy aktywnej sesji zostalo manualnie potwierdzone: otwiera obecny dialog zastapienia sesji.

**Punkt kontrolny:** wszystkie akcje klasycznej nauki uruchamiaja ten sam backend co przed przebudowa.

### Etap 4 - Pozostale tryby

**Status: w toku**

- [x] Zen pozostaje podlaczony do istniejacego `selectLearningPath` i zachowuje parametry rodzica.
- [x] Egzamin pozostaje podlaczony do istniejacego formularza i backendowej normalizacji 32 pytan.
- [x] Trener pamieci i PJM pozostaja osobnymi wejsciami; adresy znakow i rankingu nie zostaly zmienione.
- [ ] Manualnie sprawdzic PJM, brak Premium, brak kategorii oraz istniejace wejscia znakow i rankingu.

**Punkt kontrolny:** zadna sciezka z obecnego menu nie znika ani nie prowadzi pod inny adres.

### Etap 5 - Testy i akceptacja wizualna

**Status: w toku**

- [x] Uruchomiono wybrane testy `SessionPageTest` oraz scenariusze egzaminu z `StudySessionFlowTest`.
- [x] Uruchomiono komplet testow dostepu i listy blednych pytan.
- [ ] Dodac testy dla logiki statusow przystankow, gdy zostanie wydzielona.
- [x] Manualnie sprawdzono konto Premium bez postepu i konto z aktywna sesja na tymczasowych kontach lokalnych; konta zostaly usuniete.
- [ ] Sprawdzic manualnie konto z bledami, PJM, brak Premium i brak kategorii.
- [x] Wykonano screenshoty przy 1440, 1280 i 1024 px.
- [x] Zweryfikowano widok mobilny przy 390 px - nowa mapa nie jest renderowana.
- [x] Sprawdzono konsole przegladarki, build i brak bledow TypeScript.

### Etap 6 - Przelaczenie i porzadki

**Status: oczekuje**

- [ ] Wprowadzic tymczasowy przelacznik desktopowego layoutu, z bezpiecznym powrotem do obecnego widoku.
- [ ] Przelaczyc domyslnie na mape dopiero po akceptacji.
- [ ] Usunac martwe bloki `hidden` w osobnym commicie.
- [ ] Powtorzyc build, testy i screenshoty po usunieciu kodu.

## Matryca regresji

| Scenariusz | Wymagany wynik |
| --- | --- |
| Nowy kursant Premium | Widzi nastepny dzial i uruchamia pierwsza sesje |
| Kursant z postepem | Widzi postep oraz kolejne pytania do zrobienia |
| Kursant z aktywna sesja | Moze wrocic do niej jednym kliknieciem |
| Kursant z bledami | Widzi liczbe i przechodzi do listy albo powtorki |
| Nauka klasyczna - wszystkie statusy | Dzial, status, zakres i kolejnosc daja ten sam formularz co przed przebudowa |
| Stala kolejnosc | Nadal wykorzystuje indywidualny porzadek nauki pytan |
| Losowa kolejnosc | Nadal losuje pytania tylko tam, gdzie robi to obecny silnik |
| Tryb Zen | Otwiera sesje z obecnym `ui_shell=zen` |
| Egzamin probny | Backend nadal wymusza 32 pytania i ignoruje dzial, status, zakres oraz kolejnosc z UI |
| Dostep PJM | Nie otwiera klasycznych trybow bez uprawnienia |
| Brak Premium | Prowadzi do obecnej aktywacji, bez bledu |
| Brak kategorii | Pokazuje obecny bezpieczny komunikat |
| Bledy walidacji | Komunikat jest widoczny bez wzgledu na wybrany przystanek |
| Mobile | Nie zmienia wygladu ani zachowania |

## Strategia deployu i rollbacku

1. Kazdy etap otrzymuje osobny commit.
2. Nie wdrazamy polowicznego usuniecia starego widoku.
3. Przed deployem uruchamiamy testy PHP, TypeScript i build frontendu.
4. Po deployu sprawdzamy `/nauka` na koncie testowym oraz konsole przegladarki.
5. Tymczasowy przelacznik layoutu pozwala natychmiast wrocic do poprzedniego desktopu bez zmiany danych uzytkownikow.
6. Usuniecie ukrytego kodu nastapi dopiero po osobnej akceptacji oraz potwierdzeniu wszystkich scenariuszy z matrycy.

## Definicja gotowosci

Projekt jest gotowy do produkcji, gdy:

- mapa dziala dynamicznie dla wybranej kategorii,
- wszystkie stare tryby i akcje pozostaja dostepne,
- nowa sesja i kontynuacja aktywnej sesji zachowuja obecne zabezpieczenia,
- zadne dane nauki, odpowiedzi ani dostepy nie sa migrowane lub zmieniane,
- desktop jest czytelny w trzech kontrolowanych szerokosciach,
- mobile nie ma regresji,
- testy, build i kontrola produkcyjna sa zielone.

## Dziennik decyzji

| Data | Decyzja | Powod |
| --- | --- | --- |
| 2026-08-14 | Mapa tylko dla desktopowej nauki klasycznej | Nie ryzykujemy przebudowy wszystkich trybow naraz |
| 2026-08-14 | Zachowujemy obecne formularze i endpointy | Minimalizuje to ryzyko dla serca platformy |
| 2026-08-14 | Stary ukryty kod usuwamy po akceptacji nowego widoku | Ulatwia bezpieczny rollback |
| 2026-08-14 | Przystanki sa komponentami, nie statyczna grafika | Dzialaja dla danych, dostepnosci i roznych kategorii |
| 2026-08-14 | Globalne bledy sa drugorzedna akcja obok glownego startu | Rozdziela bledy calego kursu od bledow w wybranym dziale i nie zasmieca mapy |
| 2026-08-14 | Pierwsza wersja mapy jest domyslnym desktopem, a stary kod pozostaje ukryty | Umozliwia szybki powrot przez kod przed jego osobnym usunieciem |
| 2026-08-14 | Mapa korzysta ze stalego, siedmiotorowego ukladu z jasna droga SVG | Najlepiej odpowiada dostarczonej referencji, zachowujac dynamiczne dane i klikalne komponenty |
| 2026-08-14 | Panel trybow nauki moze zostac zwiniety do ikon | Mapa odzyskuje szerokosc; wybor uzytkownika jest zapamietywany lokalnie w przegladarce, bez zmiany danych nauki |

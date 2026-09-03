# Miniatury znakow w wyjasnieniach nauki

Status: Etapy 1 i 2 sa zmergowane do czystej galezi integracyjnej; gotowe do kontrolowanego deployu z wylaczona flaga funkcji
Branch wydaniowy: `codex/inline-sign-thumbnails-release` (utworzony z aktualnego `main`)
Branch integracyjny: `codex/inline-sign-thumbnails-integration` (`c72d2bca`)
Ostatnia aktualizacja: 2026-08-14

## Cel

Gdy kursant widzi karte wyjasnienia, ma zobaczyc male, czytelne miniatury
znakow wspomnianych w tekscie. Miniatury maja pomagac zapamietac konkretna
sytuacje drogowa, a nie zastepowac tresc wyjasnienia.

Przyklad:

> Znak [miniatura B-20] nakazuje zatrzymanie, a [miniatura P-12] wskazuje miejsce zatrzymania.

Kody sa zastapione malymi znakami bez ramki, nazwy i dodatkowego opisu.

## Decyzje robocze

1. Tekst wyjasnienia pozostaje niezmieniony. Nie zapisujemy w nim HTML ani
   adresow obrazkow.
2. Pierwsza wersja zastepuje rozpoznany kod znaku jego miniatura bezposrednio
   wewnatrz zdania. W bazie pozostaje zwykly tekst; bezpieczny HTML powstaje
   dopiero w przegladarce, po oczyszczeniu tekstu wyjasnienia.
3. Automatycznie pokazujemy wszystkie rozne znaki, w kolejnosci ich pierwszego
   wystapienia w wyjasnieniu. Ten sam kod jest w zdaniu zastepowany miniatura
   przy kazdym wystapieniu.
4. Pokazujemy tylko znak, ktory istnieje w opublikowanej bazie `traffic_signs`
   i ma dostepna grafike.
5. Nierozpoznany kod zostaje zwyklym tekstem. Nie pokazujemy pustego miejsca ani
   nie zgadujemy znaku.
6. Rekordy recznych korekt maja pierwszenstwo przed automatycznym wykryciem:
   lokalny wyjatek dla pytania ma pierwszenstwo przed wspolna korekta, a ta przed
   automatyka.
7. Miniatury korzystaja z istniejacych plikow WebP z bazy znakow. Nie kopiujemy
   obrazkow do rekordow pytan.

## Co juz istnieje

### Baza znakow i publiczna strona

- `TrafficSign` przechowuje kod, nazwe, opis, tekst alternatywny i sciezke do
  grafiki znaku.
- `PublicQuestionSignReferenceService` rozpoznaje kody typu `A-7`, `B-20` i
  `P-12` w publicznych wyjasnieniach i tworzy dla nich miniatury.
- Publiczny widok Blade ma wlasny HTML oraz modal podgladu znaku. Tego HTML nie
  wolno przekazywac bezposrednio do Vue w `/nauka`.

### Nauka

- `QuestionExplanationRuntimeBlock.vue` potrafi juz pokazac jedna grafike
  pomocnicza obok wyjasnienia.
- `QuestionExplanationAssetPayloadBuilder` buduje bezpieczny payload dla
  pojedynczego przypietego znaku lub grafiki.
- `SharedQuestionExplanationAssetResolver` wspoldzieli przypiety material dla
  pytan o tym samym `external_id` i `source_scope`. Lokalny material pytania ma
  pierwszenstwo przed wspoldzielonym.
- Panel admina ma pole `Znak drogowy z bazy`, wybor zakresu `Tylko to pytanie`
  albo `Wszystkie pytania z tym samym numerem zrodlowym` i podglad.

### Wyniki audytu produkcji z 2026-08-13

| Obszar | Wynik |
| --- | ---: |
| Wszystkie pytania | 18 356 |
| Pytania, ktore juz dostaja znak z bazy w nauce | 2 711 |
| Pytania z inna grafika pomocnicza | 104 |
| Wyjasnienia w nauce | 16 861 |
| Wyjasnienia zawierajace kod znaku | 4 643 |
| Wyjasnienia, dla ktorych wszystkie kody sa dostepne w bazie | 4 148 |
| Wyjasnienia z co najmniej jednym dostepnym znakiem | 4 291 |
| Wyjasnienia z przynajmniej jednym brakujacym kodem | 495 |

Najczestsze brakujace kody to obecnie `S-3`, `P-21`, `T-18`, `T-21` i `T-4`.
Ich tekst pozostanie widoczny, dopoki nie uzupelnimy centralnej bazy znakow.

Przykladowe pliki WebP znakow maja okolo 15-19 KB i sa cachowane przez 7 dni.

## Docelowe zachowanie dla kursanta

1. Aplikacja pokazuje istniejaca karte wyjasnienia, jak dotychczas.
2. Jezeli w wyjasnieniu sa rozpoznane znaki, ich kody sa zastapione malymi
   miniaturami w tym samym zdaniu.
3. Miniatura nie ma ramki, widocznej nazwy ani dodatkowego opisu. Tekst
   alternatywny zachowuje nazwe dla czytnika ekranu i sytuacji, gdy obrazek
   nie moze sie zaladowac.
4. W nauce klasycznej miniatury podazaja za obecna karta wyjasnienia, rowniez
   gdy kursant swiadomie wybierze `Pokaz wyjasnienie` przed odpowiedzia.
5. W egzaminie oraz w aktywnej powtorce SR znaki nie moga pojawic sie przed
   ujawnieniem wyniku. Zachowujemy obecne zasady widocznosci wyjasnien.

## Automatyczne wykrywanie

### Zasada

Backend odczytuje kody z `questions.explanation`, uzywajac tej samej reguly
rozpoznawania co publiczna strona. Nastepnie sprawdza je w `traffic_signs`.
Wynik jest lista danych, a nie gotowym HTML-em.

Proponowany fragment payloadu sesji:

```json
{
  "explanation_sign_references": [
    {
      "code": "B-20",
      "name": "Stop",
      "title": "B-20 Stop",
      "image_url": "https://prawkonaraz.pl/traffic-signs/.../b-20-stop.webp",
      "alt_text": "Znak B-20 Stop",
      "url": "/znaki-drogowe/b-20-stop",
      "source": "automatic"
    }
  ]
}
```

Lista jest pusta, gdy nie ma kodu, brak znaku w bazie, grafiki lub gdy tryb
sesji nie pozwala jeszcze pokazywac wyjasnienia.

### Wydajnosc

- Rozpoznajemy kody w pamieci, bez zapisu do bazy.
- Dla calego aktualnego okna pytan pobieramy znaki jednym zapytaniem
  `whereIn('code', ...)`; bez zapytania osobno dla kazdego pytania.
- Do przegladarki wysylamy tylko rozne, rozpoznane referencje z danego
  wyjasnienia; lokalny audyt wykazal maksymalnie cztery.
- Obrazki maja `loading="lazy"` i korzystaja z obecnego cache HTTP.

## Reczne korekty i wyjatki

Automatyka ma ulatwiac prace, ale nie moze byc nieodwracalna. W panelu edycji
pytania jest sekcja `Znaki w wyjasnieniu`.

### Interfejs administratora

- Sekcja `Znaki w wyjasnieniu` pokazuje podglad juz zapisanego wyjasnienia z
  tymi samymi miniaturami inline, ktore zobaczy kursant.
- Kody obecne w tresci sa wykrywane automatycznie. Redaktor dodaje tylko
  wyjatek jako jedna z akcji: `Ukryj`, `Zamien znak` albo `Dodaj znak po
  fragmencie tekstu`.
- Akcja `Dodaj znak` wymaga unikalnego fragmentu wyjasnienia. Miniatura pojawia
  sie zaraz po nim, wiec znak pozostaje czescia zdania, a tekst nie jest
  usuwany ani przepisywany przez system.
- Wybor zakresu: `Wszystkie pytania z tym samym numerem zrodlowym` jako
  domyslny oraz `Tylko to pytanie` jako rzadki wyjatek.
- Podglad po zapisie pokazuje te same miniatury inline, ktore zobaczy kursant
  w zdaniu.

### Model danych

Istniejacy `question_explanation_assets` obsluguje jedna glowna grafike i nie
powinien byc przeciazany lista automatycznych miniaturek.

Rekomendowane sa dwa modele korekt, zgodne z obecnym wzorcem local/shared:

1. `shared_question_explanation_sign_overrides`
   - klucz: `external_id + source_scope`,
   - sluzy wszystkim kategoriom tego samego pytania zrodlowego.
2. `question_explanation_sign_overrides`
   - klucz: `question_id`,
   - sluzy tylko rzeczywistym wyjatkom.

Kazdy rekord korekty zawiera co najmniej:

- `detected_code` nullable - kod wykryty w tekscie;
- `anchor_text` nullable - unikalny fragment tekstu, po ktorym system dopisze
  znak dla akcji `add`;
- `traffic_sign_id` nullable - znak wybrany z centralnej bazy;
- `action`: `hide`, `replace` albo `add`;
- `position`;
- `created_by`, `updated_by` oraz znaczniki czasu.

Kolejnosc skladania listy dla runtime:

1. automatycznie wykryte, opublikowane znaki;
2. wspoldzielone korekty;
3. lokalne korekty pytania;
4. usuniecie duplikatow oraz zachowanie kolejnosci z tekstu.

Przyklad korekty:

- tekst wspomina `B-20`, ale to tylko porownanie: akcja `hide`;
- tekst zawiera kod, ktory wskazuje zly znak: `replace` z poprawnym
  `traffic_sign_id`;
- tekst zawiera `S-3`, ktorego jeszcze nie ma w katalogu: najpierw dodajemy
  wpis i grafike do `traffic_signs`, potem uzywamy `replace`;
- wyjasnienie omawia znak, ale nie podaje jego kodu: wybieramy `add`, wskazujemy
  znak z katalogu i wpisujemy unikalny fragment zdania, po ktorym ma byc widoczny.

## Kolejnosc wdrozenia

### Etap 0 - wykonane

- [x] Audyt publicznego mechanizmu miniaturek.
- [x] Audyt API i runtime `/nauka`.
- [x] Pomiar danych produkcyjnych oraz dostepnosci obrazkow.
- [x] Weryfikacja wydajnosci i cache.
- [x] Testy istniejacego modelu local/shared oraz payloadu sesji.
- [x] Utworzenie branchu `codex/learning-inline-sign-thumbnails`.

### Etap 1 - automatyczne miniatury

- [x] Utworzono `QuestionExplanationSignReferencePayloadBuilder` dla tekstu
      wyjasnienia.
- [x] Dodano batch preload znakow dla aktualnego okna/puli pytan.
- [x] Dodano `explanation_sign_references` do payloadu strony sesji,
      odpowiedzi JSON, wynikow sesji i `StudySessionApiPayloadBuilder`.
- [x] Rozpoznany kod w gotowym, oczyszczonym HTML wyjasnienia jest zastepowany
      miniatura inline; znaczniki HTML i tekst nierozpoznanych kodow pozostaja
      nietkniete.
- [x] Kazda miniatura ma tekst alternatywny dla czytnika ekranu, bez widocznej
      ramki, nazwy i dodatkowego opisu.
- [x] Dodano feature flag `STUDY_EXPLANATION_SIGN_REFERENCES_ENABLED`.

### Stan techniczny Etapu 1

- Aktualny `main` zawiera revert duzego historycznego merge'a, ktory obejmowal
  rowniez pierwsza wersje miniaturek. Nie przywracamy tego merge'a.
- Funkcja zostala odtworzona jako osobny, ograniczony diff na aktualnym
  `main`. Flaga `STUDY_EXPLANATION_SIGN_REFERENCES_ENABLED` ma domyslnie
  wartosc `false`, wiec sam deploy nie zmieni widoku kursanta.
- Wlaczenie flagi nie zmienia danych pytan ani odpowiedzi. Powoduje jedynie
  dolaczenie gotowej listy miniaturek do tych wyjasnien, ktore sa juz widoczne.
- Aktywny egzamin i aktywna powtorka SR zwracaja pusta liste i nie uruchamiaja
  batch preload znakow. Po zakonczeniu sesji zachowuja obecne zasady pokazywania
  wyjasnien.
- Mechanizm wykorzystuje ten sam parser kodow i katalog `traffic_signs`, co
  publiczne pytania. Nie przenosi HTML z Blade do Vue.
- Reczne korekty, tabele bazy i panel admina sa zakresem Etapu 2.

### Etap 2 - reczne korekty

- [x] Dodano migracje obu tabel korekt z indeksami oraz kluczami obcymi.
- [x] Dodano modele, resolver i zasade pierwszenstwa `local > shared > auto`.
- [x] Dodano sekcje `Znaki w wyjasnieniu` do formularza Filament.
- [x] Dodano audit log zapisu korekt.
- [x] Dodano podglad zapisanego wyjasnienia z miniaturami inline w panelu
      administratora.
- [x] Dodano walidacje: kod dla `hide` i `replace`, opublikowany znak z grafika
      dla `replace` i `add` oraz unikalny fragment tekstu dla `add`.
- [x] Dodano testy payloadu, priorytetu korekt, walidacji i zapisu z panelu.
- [x] Recznie sprawdzono lokalnie wszystkie akcje: `hide`, `replace` i `add`,
      ich wplyw na podglad oraz payload kursanta, a nastepnie usuniecie korekt,
      ktore przywraca automatyczna miniature. Test zostal wykonany na pytaniu
      ID `51018` (`pj360:2360`); po zakonczeniu nie pozostawiono zadnej
      testowej korekty znaku.

### Etap 3 - pilot i rozszerzenie

- [x] Lokalny pilot wszystkich 36 dostepnych wyjasnien z kodami znakow;
  32 wyjasnienia otrzymuja co najmniej jedna miniatura, a payload dokladnie
  odpowiada wszystkim dostepnym znakom.
- [x] Przejrzec wizualnie wyjasnienia z trzema i czterema znakami, z kodem
  powtorzonym, z pogrubieniem oraz z brakujaca grafika.
- [ ] Przejrzec pilot 30-50 pytan na produkcji po osobnej akceptacji.
- [ ] Sprawdzic niewidoczne i mylace przypadki redakcyjne.
- [ ] Uzupelnic katalog o najczestsze brakujace kody, zaczynajac od `S-3`.
- [ ] Wlaczyc funkcje dla nauki dopiero po deployu, migracji i kontroli
      produkcyjnej.

## Testy wymagane przed wdrozeniem

### Backend

- [x] wykrycie jednego, dwoch i wiecej niz trzech kodow;
- [x] zachowanie kolejnosci pierwszego wystapienia oraz usuwanie duplikatow;
- [x] brak znaku lub grafiki pozostawia kod w tekscie i nie daje pustej karty;
- [x] jedna zbiorcza kwerenda znakow dla puli pytan;
- [x] brak referencji w aktywnym egzaminie i aktywnej powtorce SR;
- [x] referencje dostepne po ujawnieniu wyniku;
- [x] pierwszenstwo `local > shared > automatic`.

### Panel administratora

- [x] ukrycie, zamiana i dodanie znaku;
- [x] zakres wspoldzielony aktualizuje wszystkie kategorie o tym samym
  `external_id + source_scope`;
- [x] lokalny wyjatek nie modyfikuje pozostalych kategorii;
- [x] usuniecie korekty przywraca automatyczne dopasowanie;
- [x] zapis trafia do audit logu.

### Frontend i UX

- [x] miniatury sa widoczne tylko wtedy, gdy widoczna jest karta wyjasnienia,
  w tym po recznym `Pokaz wyjasnienie` w nauce klasycznej;
- [x] tekst wyjasnienia zachowuje pogrubienia i kolory;
- [x] obrazki laduja sie dopiero wtedy, gdy sa renderowane;
- [x] klawiatura i czytnik ekranu rozpoznaja kod oraz nazwe znaku;
- [x] pilot lokalny na prawdziwym pytaniu `pj360:2378`: miniatury `B-20`,
  `P-12` i `G-3` zastepuja swoje kody bezposrednio w zdaniu, bez nakladania
  na odpowiedzi.
- [x] pilot lokalny na `pj360:2453`: cztery miniatury `B-33`, `D-42`, `D-43`
  i `E-18a` sa widoczne w jednym wyjasnieniu.
- [x] pilot lokalny na `pj360:3133`: pogrubione `TAK` zostaje zachowane,
  dostepny `A-18b` staje sie miniatura, a brakujacy `T-2` zostaje tekstem.
- [x] ustawienie materialow wizualnych `Wyłączone` nie renderuje miniaturek.
- [ ] przeglad wizualny 30-50 pytan na produkcji.

### Wynik weryfikacji 2026-08-13

- PHP syntax: wszystkie zmienione pliki PHP bez bledow skladni.
- `QuestionExplanationSignReferencePayloadBuilderTest`: 10 testow, 28 asercji.
- `StudySessionFlowTest` dla odkrywania znakow, odpowiedzi JSON, wyniku sesji
  i aktywnego egzaminu: 2 testy, 71 asercji.
- Istniejacy przeplyw pelnej sesji: 1 test, 97 asercji.
- `npm run build`: kompilacja TypeScript i Vite zakonczona powodzeniem.
- `explanationFormatting.test.ts`: 12 testow, w tym zamiana kodow w tekscie
  pogrubionym, wielokrotne wystapienie tego samego kodu oraz zachowanie
  atrybutow gotowego HTML.
- Lokalny pilot `/nauka/teraz`: wszystkie 36 dostepnych lokalnie wyjasnien z
  kodami zostalo porownanych z payloadem; 32 z dostepnymi grafikami sa zgodne,
  a najwiekszy przypadek ma cztery rozne znaki.

### Dodatkowa weryfikacja 2026-08-14

- `QuestionExplanationSignReferencePayloadBuilderTest`: 10 testow, 28 asercji
  - zaliczone ponownie na lokalnej bazie.
- `QuestionVisualExplanationEditPageTest`: 17 testow, 131 asercji - zaliczone
  ponownie; obejmuje zapis `replace`, `hide` i `add` z formularza, zakres
  wspoldzielony, zachowanie korekt przy zapisie innych pol oraz audit log.
- `explanationFormatting.test.ts`: 12 aktualnych testow - zaliczone ponownie.
- Reczna kontrola w przegladarce na `/admin/questions/51018/edit`:
  1. podglad poprawnie pokazal miniaturke `D-6b` bezposrednio w zdaniu;
  2. zapisano wspolna akcje `replace`, ktora zastapila `D-6b` miniaturka
     `A-7` w tym samym miejscu zdania;
  3. zapisano wspolna akcje `add`, ktora dodala miniaturke `A-8` zaraz po
     unikalnym fragmencie `zblizajac sie do przejazdu dla rowerow`;
  4. zapisano wspolna akcje `hide` dla `D-6b` i payload nie zwrocil zadnej
     miniatury;
  5. wszystkie tymczasowe korekty usunieto. W lokalnej bazie pozostalo
     `0` korekt wspoldzielonych i `0` lokalnych dla tego pytania, a payload
     ponownie zwrocil automatyczna miniaturke `D-6b`;
  6. w konsoli przegladarki nie bylo bledow.

### Weryfikacja galezi wydaniowej z aktualnego `main` (2026-08-14)

- Odtworzono tylko 27 plikow koniecznych dla miniaturek i recznych korekt.
  Nie dolaczono niepowiazanych zmian z historycznego merge'a ani lokalnego
  katalogu roboczego.
- `QuestionExplanationSignReferencePayloadBuilderTest`: 10 testow,
  28 asercji - zaliczone.
- `QuestionVisualExplanationEditPageTest`: 17 testow, 131 asercji - zaliczone.
- `explanationFormatting.test.ts`: 12 testow - zaliczone.
- `npm run build`: TypeScript i Vite zakonczone powodzeniem.
- Rozszerzony `StudySessionFlowTest` rozpoczal sie poprawnie; pierwsze 18
  scenariuszy, w tym wykrywanie miniaturek w sesji nauki, przeszlo. Dalsza
  czesc zostala zablokowana przez blad wejscia/wyjscia SQLite na montowanym
  katalogu Windows w izolowanym kontenerze testowym. Nie byl to blad aplikacji
  ani wynik walidacji funkcji.

### Kontrola integracyjna (2026-08-14)

- Branch wydaniowy zostal zmergowany bez konfliktow do czystego worktree od
  aktualnego `main`: commit scalajacy `c72d2bca`.
- Ponownie zaliczono `QuestionExplanationSignReferencePayloadBuilderTest`:
  10 testow i 28 asercji.
- Ponownie zaliczono `QuestionVisualExplanationEditPageTest`: 17 testow i
  131 asercji.
- Ponownie zaliczono frontendowy `explanationFormatting.test.ts`: 12 testow,
  oraz produkcyjny build TypeScript/Vite.
- Ponownie zaliczono dwa krytyczne scenariusze `StudySessionFlowTest`:
  widoczne miniatury w nauce (36 asercji) oraz brak wyjasnien i miniaturek w
  aktywnym egzaminie (35 asercji).
- Dodano osobny test regresji dla recznego dodania znaku po fragmencie
  sformatowanym pogrubieniem. Test jawnie wlacza flage funkcji, aby faktycznie
  sprawdzac zachowanie runtime'u.
- `main` i produkcja nie zostaly jeszcze zmienione. Pierwszy deploy ma
  pozostawic `STUDY_EXPLANATION_SIGN_REFERENCES_ENABLED=false`.

## Kolejny krok: wydanie Etapow 1 i 2

Przed wydaniem nalezy:

1. Zmergowac commit integracyjny `c72d2bca` do aktualnego `main`; nie
   przywracac historycznego merge'a cofnie te komitem.
2. W trakcie deployu uruchomic migracje, zbudowac frontend i odswiezyc cache
   aplikacji, pozostawiajac flage funkcji wylaczona.
3. Sprawdzic produkcyjny panel na jednym pytaniu oraz istniejaca sesje nauki.
4. Dopiero po pozytywnym wyniku ustawic
   `STUDY_EXPLANATION_SIGN_REFERENCES_ENABLED=true` i odswiezyc cache
   konfiguracji.
5. Redakcja moze wtedy dodawac rzeczywiste korekty. Kazda korekta jest
   odwracalna przez usuniecie jej w formularzu.

## Bezpieczny rollout i rollback

1. Najpierw merge i deploy z funkcja wylaczona flaga.
2. Wlaczyc ja lokalnie, wykonac testy funkcjonalne i wizualne.
3. Wlaczyc na produkcji dla pilota lub calej nauki po akceptacji.
4. W razie problemu wylaczyc flage; tekst wyjasnienia, pytania i istniejace
   materialy wizualne pozostaja nietkniete.

## Wynik po zakonczeniu

Kursant zobaczy kontekstowe, male znaki przy wyjasnieniu. Redakcja zachowa
pelna kontrole nad wyjatkami, a dane pytan i odpowiedzi nie zostana zmienione
ani zduplikowane.

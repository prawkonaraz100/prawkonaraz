# Audyt question-first dla warstwy prawnej

Status: audyt 10 pierwotnych artykulow wykonany; wszystkie pozycje question-first zamkniete
Utworzono: 2026-06-15  
Zakres: pierwsze 10 stron `/przepisy/{slug}`, blok `Uzasadnienie prawne` na stronach pytan, relacje `QuestionLegalReference`

## Cel

Chcemy polaczyc dwa swiaty:

- artykuly prawne w dziale `/przepisy`,
- konkretne pytania egzaminacyjne i ich publiczne uzasadnienia prawne.

Nie chodzi o przepisywanie odpowiedzi z modulu nauki. Chodzi o to, zeby kazde publiczne pytanie z blokiem `Uzasadnienie prawne` mialo uczciwe, zweryfikowane i konkretne powiazanie z przepisem oraz artykulem.

## Co juz mamy w kodzie

Obecny model danych wystarcza do MVP:

- `LegalContentPage` - publiczny artykul, np. `/przepisy/tramwaje-i-przystanki`.
- `LegalUnit` - jednostka prawna, np. art., ustep albo punkt.
- `LegalTopic` - edukacyjny temat katalogowy.
- `QuestionLegalReference` - lacznik pytanie -> jednostka prawna -> temat -> artykul.
- `QuestionLegalReference.public_note` - tekst widoczny pod pytaniem jako uzasadnienie prawne.

Wazne: nie zmieniamy `questions.explanation`, odpowiedzi, mediow, timera wideo ani logiki `/nauka`.

## Mapa do dalszego rozszerzania

Audyt 10 artykulow ponizej pozostaje kolejka naprawy juz opublikowanych tresci. Dla nowych artykulow nie wykonujemy kolejnego pelnego skanu bazy.

Gotowe artefakty:

- `docs/LEGAL-ARTICLE-TOPIC-CANDIDATES.md` - 31 filarow i 56 wezsych tematow z licznikami oraz przykladowymi ID,
- `resources/legal-content/generated/article-topic-question-map.json` - komplet promptow i kategorii dla kazdego tematu,
- `resources/legal-content/generated/agent-workspace/QUEUE.md` - operacyjna kolejka tematow dla agenta,
- `resources/legal-content/generated/agent-workspace/topics/{slug}.json` - maszynowe dossier tematu z odpowiedziami, mediami, kontrola konfliktow i istniejacymi relacjami,
- `resources/legal-content/article-topic-candidates.php` - reguly, ktore mozna rozwijac po recznym przegladzie wynikow.
- `docs/LEGAL-CONTENT-SCALING-PLAN.md` - dalsze discovery i utrzymanie duzego portfela po wyczerpaniu kolejki.

Mapa obejmuje 17 044 rekordy, czyli 3 570 pytan kanonicznych. Wszystkie pytania maja temat filarowy, a 767 pytan kanonicznych ma dodatkowo co najmniej jeden temat `focused`.

Relacje z tej mapy sa planistyczne. Nie wolno traktowac ich jako zweryfikowanej podstawy prawnej ani publikowac automatycznie w `QuestionLegalReference`.

Pierwszy pilot z mapy wykonano 19.06.2026 dla `autobus-wyjezdzajacy-z-przystanku`. Zweryfikowano 9 pytan kanonicznych i art. 18 ust. 1-2, a kandydat wskazuje juz opublikowany slug. Drugi wpis z kolejki, `zawracanie`, opublikowano 20.06.2026: z 62 pytan kanonicznych 45 otrzymalo zweryfikowana relacje, a 17 pozostawiono do analizy mediow. Trzeci wpis, `sygnal-dzwiekowy`, rozdzielil 20 zweryfikowanych pytan pomiedzy art. 29 i techniczne wymagania sygnalu, pozostawiajac 12 niejednoznacznych lub falszywie dopasowanych pytan bez publikacji relacji. Dwa kolejne wpisy z 21.06.2026 rozdzielily wspolne pytania o przyczepe pomiedzy art. 61 dotyczacy ladunku i art. 62 dotyczacy masy oraz dlugosci zespolu pojazdow; zweryfikowano odpowiednio 24 i 8 relacji. Wpisy o przejezdzie kolejowym i holowaniu dodaly kolejno 23 oraz 14 relacji opartych o precyzyjne jednostki art. 28, art. 31, art. 45 oraz rozporzadzenia o znakach. Artykuly o pasach i swiatlach dodaly 8 oraz 11 relacji, pozostawiajac pytania techniczne i poboczne dla osobnych tematow.

## Problem do rozwiazania

Pierwsze artykuly sa dobre jako filary tematyczne, ale czesc relacji jest jeszcze zbyt ogolna na poziomie pytania.

Przyklad problemu:

- artykul moze byc o `art. 26`,
- pytanie moze sprawdzac bardzo konkretny obowiazek z jednego ustepu,
- publiczny blok pod pytaniem powinien pokazac precyzje, a nie tylko szeroki artykul.

Dla SEO i E-E-A-T to ma znaczenie, bo Google i uzytkownik widza wtedy, ze pytanie nie ma losowego linku do przepisu, tylko realna, sprawdzona podstawe.

## Zasady pracy

1. Oficjalne zrodlo przed trescia.
   - ISAP, ELI, Dziennik Ustaw albo gov.pl.
   - Konkurencja moze byc tylko tropem roboczym, nigdy zrodlem finalnym.

2. Najpierw pytanie, potem jednostka.
   - Czytamy prompt, poprawna odpowiedz, wyjasnienie, media i kategorie.
   - Dopiero wtedy wskazujemy jednostke prawna.

3. Dokladnosc ponad szerokosc.
   - Jesli pytanie wynika z konkretnego ustepu, tworzymy lub uzywamy `LegalUnit` na poziomie ustepu.
   - Szeroki artykul zostaje dla strony tematycznej.

4. `public_note` jest unikalnym mostem.
   - Ma tlumaczyc, dlaczego to pytanie laczy sie z ta podstawa.
   - Nie ma kopiowac `questions.explanation`.
   - Nie ma byc generycznym opisem artykulu.

5. Brak pewnosci oznacza brak publikacji.
   - Relacja nie idzie jako `verified`, jesli nie umiemy jej obronic.

## Docelowy format `public_note`

Wzor:

```text
To pytanie sprawdza [konkretny obowiazek/zakaz/uprawnienie] w sytuacji [kontekst widoczny w pytaniu]. Podstawa jest [dokladna jednostka], bo [krotkie powiazanie z poprawna odpowiedzia].
```

Dobre cechy:

- 120-320 znakow w typowym przypadku,
- jeden konkretny kontekst,
- jedna glowna podstawa,
- brak lania wody,
- brak pelnych cytatow z ustawy.

Zle wzorce:

- `To pytanie dotyczy przepisow ruchu drogowego.`
- `Podstawa jest art. 26, bo tak wynika z ustawy.`
- ta sama notatka wklejona do kilkunastu roznych pytan.

## Audyt 10 obecnych artykulow

| Priorytet | Slug | Pytania | Obecna podstawa | Co trzeba zrobic |
| --- | --- | --- | --- | --- |
| 1 | `tramwaje-i-przystanki` | `99`, `10314` | art. 26 ust. 6 | Wykonane 15.06.2026: dodano `LegalUnit` dla `art. 26 ust. 6`, dopisano osobne notatki i usuwanie starej relacji do szerokiego `art. 26`. |
| 2 | `piesi-i-przejscia` | `10249`, `10369`, `10474` | art. 26 ust. 1, art. 26 ust. 4 | Wykonane 19.06.2026: pytania przy przejsciu przypisano do ust. 1, a wjazd do bramy przez droge dla pieszych do ust. 4; usuwana jest stara relacja do szerokiego art. 26. |
| 3 | `predkosc-odstep-i-hamowanie` | `13562`, `13781`, `13782`, `4170`, `7237`, `13035`, `2484`, `3966` | art. 19 ust. 1, art. 19 ust. 2 pkt 3, art. 19 ust. 3a, art. 20 ust. 2, art. 20 ust. 3 pkt 1 lit. a | Wykonane 20.06.2026: rozdzielono prędkość bezpieczną, ogólny odstęp, regułę połowy prędkości, strefę zamieszkania i limit na autostradzie; usuwane są stare relacje do szerokich art. 19 i 20. |
| 4 | `wyprzedzanie` | `7403`, `10208`, `7640`, `9541`, `8979`, `10053`, `7398`, `7777`, `10937`, `7012`, `10939`, `12779` | art. 24 ust. 1 pkt 2, ust. 2, ust. 6, ust. 7 pkt 1 i 3, ust. 11 | Wykonane 20.06.2026: wszystkie pytania przypisano do rzeczywistego problemu, bez pytań opartych wyłącznie na znakach. |
| 5 | `wymijanie-omijanie-cofanie` | `3154`, `10966`, `3155`, `3157`, `13540`, `2501`, `13773`, `6206`, `6203`, `6208` | art. 23 ust. 1 pkt 1-3, ust. 2 | Wykonane 20.06.2026: osobno opisano wymijanie, omijanie, obowiązki przy cofaniu i miejsca objęte zakazem. |
| 6 | `zmiana-kierunku-i-pasa-ruchu` | `7219`, `11089`, `11090`, `11500`, `4488`, `2568`, `4155` | art. 22 ust. 1, ust. 2 pkt 2, ust. 5 | Wykonane 20.06.2026: rozdzielono ostrożność, ustawienie przed skrętem oraz rozpoczęcie i zakończenie sygnalizowania. |
| 7 | `wlaczanie-sie-do-ruchu` | `2287`, `6084`, `6095`, `7336`, `1142`, `1338`, `10847` | art. 17 ust. 1, ust. 1 pkt 1, ust. 2 | Wykonane 20.06.2026: rozdzielono definicję, typowe wyjazdy i obowiązek ustąpienia; zachowano kontrprzykład ruszenia po zielonym. |
| 8 | `pierwszenstwo-przejazdu` | `10154`, `10247` | art. 25 ust. 1; § 36 ust. 2 rozporządzenia o znakach i sygnałach | Wykonane 20.06.2026: oddzielono skręt w lewo od ronda oznaczonego A-7 i C-12. |
| 9 | `sygnalizacja-i-osoby-kierujace-ruchem` | `10107`, `469` | art. 5; § 95 ust. 1 pkt 4 i § 108 ust. 2 rozporządzenia o znakach i sygnałach | Wykonane 20.06.2026: pytania wskazują dokładnie postawę policjanta oraz jednoczesny sygnał czerwony i żółty. |
| 10 | `zatrzymanie-i-postoj` | `10237` | art. 49 ust. 1 pkt 5 | Wykonane 20.06.2026: doprecyzowano zakaz zatrzymania przy ciągłej linii krawędziowej na jezdni i poboczu. |

## Kolejnosc wdrozenia

### Etap 1 - `tramwaje-i-przystanki`

Status: wykonany 15.06.2026.

Powod: pytanie `99` jest juz publicznie widocznym przykladem i uzytkownik wskazal je jako wazny przypadek.

Zrobione:

- sprawdzono pytania `99` i `10314`,
- zweryfikowano dokladna jednostke w oficjalnym tekscie ISAP,
- dodano `LegalUnit` `art-26-ust-6`,
- przepieto pytania `99` i `10314` na `art. 26 ust. 6`,
- dodano czyszczenie starej relacji `art. 26` dla tych pytan,
- zaktualizowano testy publicznej strony pytania.

### Etap 2 - `piesi-i-przejscia`

Status: wykonany 19.06.2026.

Powod: to temat o wysokiej wadze edukacyjnej i SEO, a jednoczesnie latwo pomieszac rozne podstawy.

Zrobione:

- sprawdzono prompty, poprawne odpowiedzi, wyjasnienia i nagrania pytan `10249`, `10369`, `10474`,
- dodano `LegalUnit` `art-26-ust-1` i `art-26-ust-4`,
- pytania `10249` i `10474` przypisano do art. 26 ust. 1,
- pytanie `10369` przypisano do art. 26 ust. 4,
- dopisano trzy unikalne `public_note`,
- dodano czyszczenie starych relacji do szerokiego `art. 26`,
- doprecyzowano artykul o scenariusz wjazdu do bramy przez droge dla pieszych,
- zaktualizowano testy publicznych stron pytan i strony artykulu.

### Etap 3 - tematy wielopodstawowe

Kolejno:

1. `predkosc-odstep-i-hamowanie` - wykonane 20.06.2026,
2. `wyprzedzanie`,
3. `wymijanie-omijanie-cofanie`,
4. `zmiana-kierunku-i-pasa-ruchu`.

Powod: maja najwiecej pytan i najwieksze ryzyko generycznych notatek.

#### `predkosc-odstep-i-hamowanie`

Status: wykonany 20.06.2026.

Zrobione:

- sprawdzono osiem opublikowanych pytan oraz obrazy znakow strefy zamieszkania, drogi ekspresowej i autostrady,
- zweryfikowano aktualny tekst ujednolicony Prawa o ruchu drogowym z 3 marca 2026 r.,
- dodano precyzyjne jednostki dla art. 19 ust. 1, art. 19 ust. 2 pkt 2 i 3, art. 19 ust. 3a, art. 20 ust. 2 oraz art. 20 ust. 3 pkt 1 lit. a,
- pytania przepieto z szerokich art. 19 i 20 na jednostki odpowiadajace rzeczywistemu problemowi egzaminacyjnemu,
- dopisano osiem kontekstowych `public_note`, w tym osobne notatki dla znaku drogi ekspresowej i autostrady,
- wyjasniono, ze dostosowanie predkosci do widocznosci nie pozwala przekroczyc limitu,
- przebudowano `exam_context`, `body` i `key_points` wedlug standardu question-first,
- dodano test mapy wszystkich osmiu pytan i usuwania starych szerokich relacji.

#### `wyprzedzanie`

Status: wykonany 20.06.2026.

Zrobione:

- zweryfikowano 12 pytan i rozdzielono je na rozpoczecie manewru, odstep, minimum 1 m, zachowanie wyprzedzanego i konkretne zakazy,
- dodano jednostki od art. 24 ust. 1 pkt 2 do art. 24 ust. 11 wymagane przez faktyczne pytania,
- usuwana jest stara relacja do szerokiego art. 24,
- przebudowano notatki i kontekst artykulu wedlug standardu question-first.

#### `wymijanie-omijanie-cofanie`

Status: wykonany 20.06.2026.

Zrobione:

- potwierdzono granice miedzy art. 23 i wyprzedzaniem z art. 24,
- pytania przypisano osobno do wymijania, omijania, obowiazkow przy cofaniu i zakazow cofania,
- dodano jednostki art. 23 ust. 1 pkt 1-3 oraz art. 23 ust. 2,
- usuwana jest stara relacja do szerokiego art. 23.

#### `zmiana-kierunku-i-pasa-ruchu`

Status: wykonany 20.06.2026.

Zrobione:

- rozdzielono pytania o szczegolna ostroznosc, ustawienie przed skretem w lewo i kierunkowskaz,
- dodano jednostki art. 22 ust. 1, art. 22 ust. 2 pkt 2 i art. 22 ust. 5,
- notatki wskazuja oddzielnie moment wlaczenia i wylaczenia kierunkowskazu,
- usuwana jest stara relacja do szerokiego art. 22.

### Etap 4 - pozostale tematy

Status: wykonany 20.06.2026.

Zrobione:

- `wlaczanie-sie-do-ruchu`: pytania rozdzielono na art. 17 ust. 1, ust. 1 pkt 1 i ust. 2,
- `pierwszenstwo-przejazdu`: skręt w lewo przypisano do art. 25 ust. 1, a rondo A-7 + C-12 do § 36 ust. 2 rozporządzenia,
- `sygnalizacja-i-osoby-kierujace-ruchem`: postawę policjanta przypisano do § 108 ust. 2, a czerwone z żółtym do § 95 ust. 1 pkt 4,
- `zatrzymanie-i-postoj`: pytanie o ciągłą linię krawędziową przypisano do art. 49 ust. 1 pkt 5,
- wszystkie cztery artykuły otrzymały review 20.06.2026 i unikalne notatki question-first.

## Definition of Done dla jednego artykulu

Artykul jest dopracowany question-first, gdy:

- wszystkie powiazane pytania zostaly przeczytane w kontekscie,
- kazde pytanie ma sprawdzona oficjalna podstawe,
- `LegalUnit` jest tak dokladny, jak pozwala tekst przepisu,
- `public_note` jest unikalne i praktyczne,
- `exam_context` artykulu opisuje realny sposob wystepowania tematu w pytaniach,
- `body` artykulu nie jest abstrakcyjnym streszczeniem ustawy, tylko pomaga zrozumiec typowe sytuacje egzaminacyjne,
- testy publiczne przechodza,
- nie zmieniono `questions.explanation`, odpowiedzi ani logiki nauki.

## Komendy pomocnicze

Podejrzenie pytan po external_id:

```powershell
docker compose exec -T app php artisan tinker --execute 'dump(App\Models\Question::query()->whereIn("external_id", ["99","10314"])->get(["id","external_id","prompt","correct_answer","explanation","is_active"])->toArray());'
```

Podejrzenie relacji prawnych dla strony:

```powershell
docker compose exec -T app php artisan tinker --execute '$page = App\Models\LegalContentPage::query()->where("slug", "tramwaje-i-przystanki")->firstOrFail(); dump(App\Models\QuestionLegalReference::query()->where("legal_content_page_id", $page->id)->with(["question:id,external_id,prompt", "legalUnit:id,label,title"])->get()->toArray());'
```

Testy po zmianach:

```powershell
docker compose exec -T app php -l database/seeders/LegalTrustLayerMvpSeeder.php
docker compose exec -T app php artisan test tests/Feature/Public/LegalTrustLayerMvpTest.php
docker compose exec -T app php artisan test --filter=PublicQuestionDatabasePageTest
git diff --check
```

## Zakres poza tym dokumentem

Na tym etapie nie robimy:

- panelu admina do edycji artykulow prawnych,
- automatycznego generowania notatek przez AI,
- masowej publikacji niezweryfikowanych relacji,
- zmian w odpowiedziach egzaminacyjnych,
- zmian w widoku `/nauka`.

Najpierw dopracowujemy jakosc 10 istniejacych artykulow. Nowe tematy wybieramy rownolegle tylko z gotowej mapy kandydatow i publikujemy po pelnej weryfikacji prawnej.

Przy wpisach `rogatki-i-sygnaly-na-przejezdzie-kolejowym` oraz `swiatla-przeciwmglowe` 21.06.2026 ponownie zastosowano waski filtr question-first. Dla rogatek opublikowano tylko pytania rozstrzygane przez art. 28 ust. 1, art. 28 ust. 3 pkt 1 albo czerwony sygnal z par. 98 ust. 5. Dla swiatel rozdzielono przednie przeciwmglowe, tylne z progiem ponizej 50 m i nocny wyjatek na oznakowanej drodze kretej; pytania zalezne wylacznie od obrazu pozostaly bez relacji.

Przy wpisach `foteliki-i-przewoz-dzieci` oraz `obowiazki-uczestnika-wypadku` 21.06.2026 wszystkie pytania w dossier otrzymaly precyzyjne podstawy. W przewozie dzieci rozdzielono regule urzadzenia przytrzymujacego, montaz, wyjatki i ustawowe zakazy. W wypadkach najpierw rozstrzyga sie, czy sa osoby ranne lub zabite; od tego zalezy pomoc, wezwanie Policji, pozostawienie pojazdow i mozliwosc opuszczenia miejsca.

Przy wpisach `telefon-podczas-kierowania` oraz `swiatla-drogowe-i-oslepianie` 21.06.2026 zweryfikowano 17 pytan kanonicznych. Pytania telefoniczne przypisano do art. 45 ust. 2 pkt 1 wedlug tego, czy urzadzenie wymaga trzymania sluchawki lub mikrofonu w rece. W swiatlach drogowych rozdzielono ogolne warunki z art. 51 ust. 3, pojazd z przeciwka z ust. 4 pkt 1 i pojazd poprzedzajacy z ust. 4 pkt 2.

Przy wpisach `gasnica-trojkat-i-obowiazkowe-wyposazenie` oraz `opony-bieznik-i-cisnienie` 21.06.2026 zastosowano dodatkowy filtr zgodnosci klastra. W wyposazeniu opublikowano 6 relacji wynikajacych wprost z par. 11, 12, 40 i 46 rozporzadzenia technicznego. W oponach opublikowano 7 relacji dotyczacych bieznikow, zgodnosci na osi i cisnienia; cztery pytania o cisnienie oleju pozostawiono bez relacji.

Przy wpisach `dokumenty-podczas-kontroli-drogowej` oraz `kategorie-prawa-jazdy-i-uprawnienia` 22.06.2026 zweryfikowano aktualne teksty Prawa o ruchu drogowym i ustawy o kierujacych pojazdami wraz z pozniejszymi nowelizacjami. Dla dokumentow opublikowano 22 relacje do osmiu precyzyjnych jednostek art. 38, pozostawiajac poza publikacja pytanie `2684` o dokument tramwaju. Dla kategorii prawa jazdy wszystkie 23 pytania otrzymaly relacje rozdzielajace rodzaje pojazdow, limity techniczne, krajowe rozszerzenia kategorii B, waznosc dokumentu i termin zawiadomienia starosty.

Przy wpisach `alkohol-i-srodki-dzialajace-podobnie` oraz `zielona-strzalka-warunkowa` 22.06.2026 zweryfikowano odpowiednio 15 i 4 pytania kandydackie. Dla alkoholu opublikowano 8 relacji do art. 45 ust. 1 pkt 1-2, art. 46 ust. 2 i art. 87 par. 1, a pytania o ogolne skutki psychofizyczne pozostawiono bez bezposredniej relacji prawnej. Dla zielonej strzalki wszystkie 4 pytania przypisano do par. 96 ust. 3, poniewaz kazde sprawdza zakaz przejazdu bez pelnego zatrzymania.

Przy wpisach `pojazd-uprzywilejowany` oraz `sygnal-zolty-i-zolty-migajacy` 22.06.2026 zweryfikowano komplet 10 pytan. Pytania o korytarz zycia wskazuja art. 9 ust. 2, zakaz wyprzedzania art. 24 ust. 11, a pytanie ze znakiem D-43 dodatkowo par. 58 ust. 3. Pytania o zolty migajacy wskazuja par. 98 ust. 6 i nie sa mieszane ze stalym zoltym ani czerwonym z zoltym.

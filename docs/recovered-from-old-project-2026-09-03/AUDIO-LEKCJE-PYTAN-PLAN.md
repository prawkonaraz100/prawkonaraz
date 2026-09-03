# Audio lekcji pytan - plan dopasowany do kodu PrawkoNaRaz

## Status decyzji

Ten dokument aktualizuje zewnetrzny plan "lekcji audio" pod rzeczywisty kod serwisu PrawkoNaRaz i pod decyzje produktowe z rozmowy.

Decyzje na teraz:

- nie wdrazamy analityki audio w MVP,
- nie wdrazamy audio w trybie egzaminacyjnym,
- nie robimy autoplay po wejściu na strone ani po odpowiedzi,
- nie generujemy audio na publiczne zadanie HTTP,
- traktujemy audio jako "warstwe lekcji egzaminacyjnej", nie jako sam przycisk MP3,
- projektujemy wspolny payload audio dla publicznej strony pytania i modulu nauki, zeby uniknac pozniejszego przepinania,
- pliki audio w MVP trzymamy na naszym serwerze, nie na R2/S3,
- pliki audio sa publiczne, jesli sa osadzone na publicznej stronie pytania,
- nie robimy osobnego backupu plikow audio; backupujemy baze/metadane, a audio traktujemy jako asset regenerowalny lub odtwarzalny z lokalnej kopii wlasciciela,
- najpierw wdrazamy publiczna strone pytania, zaczynajac od pytania 99; modul nauki implementujemy jako ostatni etap, ale architekture projektujemy od razu tak, zeby go obsluzyc,
- publiczna strona i modul nauki moga miec rozne teksty wyjasnien: publiczna strona uzywa warstwy SEO, a modul nauki uzywa tekstu przeznaczonego dla uczacego sie uzytkownika.

## Jak ten dokument laczy sie z programem generatora

Ten dokument opisuje czesc aplikacyjna w Laravel: model danych, wybor tekstow, publiczny payload, SEO/schema, import plikow, raport pokrycia i pozniejsza integracje z `/nauka`.

Plik `C:\Users\xxx\Desktop\propozycja programu dla audio .txt` opisuje osobny lokalny program, ktory nie zna logiki produktu. Jego zadanie to:

- wczytac manifest eksportowany z Laravel,
- wygenerowac MP3 w ElevenLabs,
- pilnowac lokalnego wznowienia pracy,
- oddac manifest wynikowy dla importu do Laravel.

Wspolny kontrakt obu dokumentow to pola:

```text
asset_key
external_id
content_scope
audio_type
locale
source_text_hash
voice_provider
voice_id
model_id
generation_version
```

Jesli dokumenty kiedys beda sie roznic, nadrzedna zasada jest taka: Laravel jest zrodlem prawdy o stanie produktu i pokryciu audio, a generator jest tylko wykonawca plikow MP3.

## Zakres MVP

Etap 1 MVP obejmuje:

- publiczna strone pojedynczego pytania w oficjalnej bazie,
- pytanie 99 jako pierwszy golden sample,
- tylko gotowe, wygenerowane wczesniej pliki audio,
- przyciski odtwarzania widoczne tylko wtedy, gdy dany plik audio istnieje i ma status gotowy,
- JSON-LD `AudioObject` tylko na publicznych stronach i tylko dla audio realnie dostepnego na stronie.

Etapy pozniejsze:

- batch publicznych pytan 20-50,
- klasyczny modul nauki `/nauka`,
- tryby nauki/review, w ktorych wyjasnienie jest normalnie pokazywane uzytkownikowi.

Poza MVP:

- tryb egzaminacyjny,
- automatyczne audio po odpowiedzi,
- eventy analityczne,
- wielojezycznosc,
- personalizacja glosu,
- masowa generacja calej bazy bez wczesniejszego testu na malej probce,
- audio dla PJM jako osobny temat, bo obecny plan PJM zaklada materialy wideo bez dzwieku.

## Istniejace miejsca w kodzie

Publiczne strony pytan:

- `routes/web.php` - trasy publicznej bazy pytan:
  - `/oficjalna-baza-pytan-na-prawo-jazdy`,
  - `/oficjalna-baza-pytan-na-prawo-jazdy/{categorySlug}/pytanie/{externalId}/{slug?}`,
  - `/pytanie/{externalId}/{slug?}`.
- `app/Http/Controllers/PublicQuestionDatabaseController.php`
  - `renderQuestion()` sklada dane dla widoku pytania.
- `resources/views/questions-database/show.blade.php`
  - renderuje publiczna strone pytania.
- `app/Support/PublicQuestionSchemaService.php`
  - buduje JSON-LD dla pytania, wideo, obrazow, podstawy prawnej i `LearningResource`.

Warstwa publicznego wyjasnienia:

- `app/Models/QuestionPublicExplanation.php`
  - przechowuje publiczne SEO-wyjasnienie, haczyk egzaminacyjny i najczestsze bledy.
- `app/Support/PublicQuestionExplanationService.php`
  - wybiera publiczne wyjasnienie dla grupy pytan albo fallback z `questions.explanation`.
- `database/migrations/2026_06_15_120000_create_question_public_explanations_table.php`
- `database/migrations/2026_06_22_180000_add_common_mistakes_to_question_public_explanations_table.php`
- `database/migrations/2026_06_24_120000_add_exam_trap_to_question_public_explanations_table.php`

Modul nauki:

- `app/Http/Controllers/StudySessionController.php`
  - `transformQuestion()` buduje payload pytania dla Inertia/Vue.
  - `questionRelationsForPayload()` okresla relacje doladowywane do pytan.
  - `shouldShowQuestionExplanations()` i `shouldIncludeQuestionExplanationText()` decyduja, kiedy pokazywac wyjasnienia.
- `app/Http/Controllers/StudySessionAnswerController.php`
  - `answerRevealPayload()` zwraca dane po odpowiedzi.
- `app/Support/StudySessionApiPayloadBuilder.php`
  - buduje payload dla endpointow API sesji.
- `app/Support/PublicDemoQuestionPayloadBuilder.php`
  - buduje payload dla publicznego demo, ktore uzywa tego samego glownego komponentu Vue.
- `resources/js/Pages/StudySessions/Show.vue`
  - glowny widok klasycznej nauki, review i demo.
- `resources/js/Pages/StudySessions/Exam.vue`
  - tryb egzaminacyjny; audio w tym trybie zostaje poza zakresem.
- `resources/js/Pages/StudySessions/ExamResult.vue`
  - wynik egzaminu; audio rowniez poza zakresem.

Storage:

- `config/filesystems.php`
  - dostepne sa dyski `public`, `media_local`, `s3`, `r2`.
  - decyzja MVP: audio zapisujemy lokalnie na naszym serwerze, najlepiej przez `media_local`.
- `config/media.php`
  - obecne media pytan uzywaja publicznych dyskow i publicznego resolvera URL.
- `app/Support/MediaUrlResolver.php`
  - wzorzec do rozstrzygania publicznego URL na podstawie dysku i sciezki.

## Najwazniejsza roznica wzgledem pierwotnego dokumentu

Pierwotny dokument zaklada prosty model: `question_id + category_code + audio_type`.

W naszym kodzie to moze powodowac nadmiarowe pliki, bo jedno pytanie moze wystepowac w wielu kategoriach, a publiczne wyjasnienie moze byc przypiete do grupy przez `external_id`.

Dla przykladu pytanie 99 wystepuje w wielu kategoriach. Publiczne wyjasnienie, haczyk i bledy sa wspolna lekcja dla tej samej sytuacji drogowej. Nie ma sensu generowac 11 identycznych plikow audio tylko dlatego, ze pytanie jest widoczne w 11 kategoriach.

Dlatego audio powinno byc identyfikowane przez zrodlo tresci i hash, a nie tylko przez pojedynczy rekord pytania.

## Proponowany model danych

Tabela:

```text
question_audio_assets
```

Proponowane pola:

```text
id
question_id nullable
external_id nullable
category_code nullable
content_scope
locale
audio_type
source_text
source_text_hash
storage_disk
storage_path
duration_seconds
encoding_format
bytes
voice_provider
voice_id
voice_name
model_id
generation_version
status
error_message
generated_at
created_at
updated_at
```

Znaczenie wybranych pol:

- `question_id` - konkretny rekord pytania, gdy audio dotyczy tresci specyficznej dla tego rekordu.
- `external_id` - wspolny identyfikator pytania z oficjalnej bazy, gdy audio dotyczy grupy pytan.
- `category_code` - opcjonalne; uzywac tylko wtedy, gdy tresc audio faktycznie rozni sie per kategoria.
- `content_scope` - zrodlo tekstu, np. `question`, `public_explanation`, `system_explanation`, `answer_feedback`.
- `audio_type` - typ audio w UI, np. `question`, `explanation`, `exam_trap`, pozniej `correct_feedback`, `wrong_feedback`.
- `source_text_hash` - hash znormalizowanego tekstu zrodlowego w danym `content_scope`, `audio_type` i `locale`; nie zawiera glosu, modelu ani wersji generatora.
- `storage_disk` i `storage_path` - przechowywac tak jak media, a publiczny URL budowac przez resolver.
- `generation_version` - wersja logiki budowania tekstu, zeby moc wymusic regeneracje po zmianie buildera.

Statusy:

```text
pending
generating
generated
failed
outdated
disabled
```

Minimalny indeks unikalnosci:

```text
content_scope
external_id
question_id
category_code
locale
audio_type
source_text_hash
voice_provider
voice_id
voice_name
model_id
generation_version
```

W praktyce trzeba uwazac na `null` w indeksach unikalnych, bo MySQL/PostgreSQL inaczej traktuja `null`. Bezpieczniej mozna zastosowac `asset_key`, np. stabilny hash zlozony z powyzszych pol.

## Typy audio

Architektura danych i payload powinny od poczatku obsluzyc:

```text
question
explanation
exam_trap
```

Pierwszy przebieg generatora ElevenLabs:

```text
question
```

Czyli lokalny program audio generuje na start tylko odsluch tresci pytania. `explanation` i `exam_trap` zostaja w modelu danych oraz kontrakcie payloadu jako kolejne etapy, zeby nie przepisywac struktury po udanym tescie na pytaniu 99.

Po MVP:

```text
correct_feedback
wrong_feedback
short_answer
```

Nie wdrazamy w MVP:

- `correct_feedback`,
- `wrong_feedback`,
- automatycznego czytania feedbacku po odpowiedzi.

## Builder tekstu audio

Potrzebny jest serwis:

```text
QuestionAudioTextBuilder
```

Odpowiedzialnosc:

- zbudowac tekst audio dla konkretnego pytania i kontekstu,
- korzystac z tego samego zrodla tresci, ktore widzi uzytkownik,
- usunac HTML i formatowanie pomocnicze,
- znormalizowac spacje,
- pilnowac, zeby transkrypcja audio odpowiadala widocznej tresci.

Warianty:

### Publiczna strona pytania

Dla publicznej strony builder powinien korzystac z:

- `question.prompt` dla `question`,
- `PublicQuestionExplanationService::publicOrSystemFallbackForQuestionGroup()` dla `explanation`,
- `exam_trap_plain` z publicznego wyjasnienia dla `exam_trap`.

Regula:

```text
tekst widoczny na publicznej stronie = source_text audio = transcript w JSON-LD
```

### Modul nauki

Decyzja:

- dla `question` uzyc `questions.prompt`,
- dla `explanation` w module nauki uzyc tego samego tekstu, ktory pokazuje modul nauki, czyli obecnie `questions.explanation`.

Uzasadnienie:

- publiczna warstwa `question_public_explanations` jest tworzona pod SEO/E-E-A-T i moze byc dluzsza lub inaczej sformulowana,
- `questions.explanation` zostaje trescia uzytkowa dla uczacego sie uzytkownika,
- roznica miedzy tymi tekstami jest zamierzona i poprawna,
- audio zawsze ma czytac tekst z tej warstwy, ktora uzytkownik widzi w danym miejscu.

Nie wolno dopuscic do sytuacji, w ktorej audio w nauce czyta publiczne SEO-wyjasnienie, a ekran pokazuje inne, krotsze `questions.explanation`. To samo dziala w druga strone: publiczna strona nie powinna emitowac `AudioObject` dla ukrytego tekstu z modulu nauki.

## Payload audio

Dodac wspolny builder:

```text
QuestionAudioPayloadBuilder
```

Przykladowy payload:

```json
{
  "question": {
    "type": "question",
    "url": "https://prawkonaraz.pl/storage-bulk/audio/questions/99/pl/question-a1b2c3.mp3",
    "duration_seconds": 12,
    "encoding_format": "audio/mpeg",
    "transcript": "Czy w tej sytuacji masz obowiazek zatrzymac pojazd?"
  },
  "explanation": {
    "type": "explanation",
    "url": "https://prawkonaraz.pl/storage-bulk/audio/questions/99/pl/explanation-d4e5f6.mp3",
    "duration_seconds": 42,
    "encoding_format": "audio/mpeg",
    "transcript": "Tak. Na nagraniu widoczny jest tramwaj..."
  },
  "exam_trap": {
    "type": "exam_trap",
    "url": "https://prawkonaraz.pl/storage-bulk/audio/questions/99/pl/exam-trap-g7h8i9.mp3",
    "duration_seconds": 18,
    "encoding_format": "audio/mpeg",
    "transcript": "W tym pytaniu nie chodzi tylko o obecnosc tramwaju..."
  }
}
```

Payload powinien zwracac tylko audio ze statusem `generated`.

Jesli audio nie istnieje:

```json
{}
```

Nie zwracamy rekordow `pending`, `failed`, `outdated` ani `disabled` do publicznego UI.

## Raport postepu audio

Zrodlem prawdy o tym, ile pytan ma audio, jest baza Laravel i tabela `question_audio_assets`.

Lokalny generator audio moze miec wlasny SQLite do wznawiania generowania, ale ten SQLite nie powinien byc traktowany jako produktowy licznik postepu. Generator odpowiada za produkcje plikow MP3, a aplikacja odpowiada za:

- wybor pytan do eksportu,
- definicje tego, co znaczy "zrobione",
- import gotowych plikow,
- raport pokrycia audio.

Definicja "zrobione" dla etapu 1:

```text
external_id jest zrobione dla audio pytania, jezeli istnieje question_audio_asset:
content_scope = question
audio_type = question
status = generated
source_text_hash = aktualny hash promptu
plik istnieje na storage
```

Docelowo raport powinien liczyc osobno:

- `question` - odsluch tresci pytania,
- `public_explanation` / `explanation` - odsluch publicznego wyjasnienia,
- `exam_trap` - odsluch haczyka egzaminacyjnego,
- `complete_public_lesson` - komplet wymaganych typow dla publicznej lekcji.

Dla publicznej bazy i pierwszego etapu liczymy postep po kanonicznym `external_id`, a nie po surowych rekordach `questions`, bo jedno pytanie wystepuje w wielu kategoriach.

Ostatni lokalny snapshot orientacyjny:

```text
aktywnych rekordow pytan w widocznych kategoriach: 14630
kanonicznych external_id: 5040
external_id obecnych w wielu kategoriach: 2534
external_id z roznym brzmieniem pytania: 0
```

Te liczby sluza jako kontekst projektowy, nie jako wartosc wpisana na stale w kodzie. Komenda raportujaca ma zawsze liczyc aktualny stan z bazy.

Proponowana komenda Laravel:

```bash
php artisan questions:audio-coverage --type=question
php artisan questions:audio-coverage --type=question --json
php artisan questions:audio-coverage --type=question --missing-limit=50
```

Minimalny wynik tekstowy:

```text
Audio pytan
Kanoniczne pytania: 5040
Gotowe: 1240
Do zrobienia: 3800
Bledy: 7
Nieaktualny hash: 2
Brakujace pliki: 1
Postep: 24.6%
```

Raport powinien miec tez rozbicie po kategoriach, ale suma globalna ma byc deduplikowana po `external_id`.

## Integracja z publiczna strona pytania

Miejsca zmian przy przyszlej implementacji:

- `PublicQuestionDatabaseController::renderQuestion()`
  - dolaczyc payload `audio`.
- `resources/views/questions-database/show.blade.php`
  - pokazac przyciski odsluchu przy pytaniu, wyjasnieniu i haczyku tylko wtedy, gdy istnieje dany typ audio.
- `PublicQuestionSchemaService::question()`
  - przyjac payload audio i dodac `AudioObject`.

UI:

- przycisk przy pytaniu: `Odsłuchaj pytanie`,
- przycisk przy wyjasnieniu: `Odsłuchaj wyjaśnienie`,
- przycisk przy haczyku: `Odsłuchaj haczyk`,
- jeden aktywny odtwarzacz naraz,
- drugi klik pauzuje/zatrzymuje,
- klik innego audio zatrzymuje poprzednie,
- brak autoplay,
- `preload="none"` albo tworzenie `Audio` dopiero po kliknieciu.

JSON-LD:

- dodawac `AudioObject` tylko dla plikow widocznych w UI,
- `transcript` musi odpowiadac `source_text`,
- `LearningResource.hasPart` powinien wskazywac dostepne czesci lekcji,
- `WebPage.hasPart` musi obslugiwac liste, bo dzis moze juz zawierac `VideoObject`.

Cel SEO:

- Google ma dostac jasny sygnal, ze publiczna strona pytania zawiera kompletna lekcje: tresc pytania, wideo/obraz, odpowiedz, wyjasnienie, haczyk i dostepne audio.
- `AudioObject` ma opisywac realny plik audio widoczny na stronie, z `contentUrl`, `encodingFormat`, `duration` i `transcript`.
- To jest semantyczny sygnal strukturalny, ale nie nalezy obiecywac osobnego audio rich result, bo Google Search ma formalnie mocniejsze i lepiej opisane funkcje dla `VideoObject` niz dla zwyklego `AudioObject`.
- Nie dodajemy audio sitemap w MVP. Najpierw HTML + JSON-LD + stabilne publiczne URL-e + test w Rich Results / URL Inspection.

Uwaga techniczna:

Obecnie `PublicQuestionSchemaService` ustawia `WebPage.hasPart` jako pojedynczy obiekt dla wideo. Po dodaniu audio trzeba zmienic to na liste czesci, zeby nie nadpisac `VideoObject`.

## Integracja z modulem nauki

Miejsca zmian przy przyszlej implementacji:

- `StudySessionController::questionRelationsForPayload()`
  - doladowac relacje audio albo korzystac z buildera, ktory robi osobne zapytanie dla listy pytan.
- `StudySessionController::transformQuestion()`
  - dodac pole `audio`.
- `StudySessionAnswerController::answerRevealPayload()`
  - nie dodawac automatycznego feedbacku w MVP.
  - w przyszlosci to bedzie miejsce na `correct_feedback` i `wrong_feedback`.
- `StudySessionApiPayloadBuilder::question()`
  - dodac ten sam payload `audio`, zeby API nie rozjechalo sie z Inertia.
- `PublicDemoQuestionPayloadBuilder::forQuestion()`
  - decyzja na pozniej: demo moze ignorowac audio albo dostac ten sam payload. Rekomendacja techniczna: dodac ten sam payload, ale niekoniecznie pokazywac UI w pierwszym kroku.
- `resources/js/Pages/StudySessions/Show.vue`
  - dodac typ `QuestionAudioAsset`,
  - dodac pole `audio` do `CurrentQuestion` i `ResultItem`,
  - dodac maly komponent odtwarzacza.

Wazna decyzja dla klasycznej nauki i Zen:

- klasyczna nauka i Zen korzystaja z tego samego glownego komponentu `StudySessions/Show.vue`,
- roznia sie glownie `session.ui_shell`: `exam_like` dla klasycznej nauki oraz `zen` dla Zen,
- oba warianty moga miec `session.mode = learn`,
- audio w module nauki nalezy wlaczac/wylaczac na podstawie `session.mode`, a nie samego `ui_shell`,
- nie wolno potraktowac `ui_shell = exam_like` jako prawdziwego trybu egzaminacyjnego, bo wtedy klasyczna nauka przypadkowo straci audio.

Regula:

```text
session.mode === "exam" -> brak audio
session.mode === "learn" -> audio dozwolone, niezaleznie od ui_shell
session.ui_shell kontroluje tylko layout i miejsce przyciskow
```

Konsekwencje UX:

- w klasycznej nauce przyciski audio moga byc bardziej widoczne przy pytaniu i wyjasnieniu,
- w Zen powinny byc kompaktowe i nie powinny rozbijac trybu skupienia,
- w obu wariantach obowiazuje brak autoplay, `preload="none"` i jeden aktywny odtwarzacz naraz,
- audio wyjasnienia pokazujemy tylko wtedy, gdy obecna logika sesji pokazuje wyjasnienie.

Nie zmieniac w MVP:

- `resources/js/Pages/StudySessions/Exam.vue`,
- `resources/js/Pages/StudySessions/ExamResult.vue`,
- logiki czasu egzaminu,
- faz pytania egzaminacyjnego.

## Tryb egzaminacyjny

Audio w trybie egzaminacyjnym jest poza zakresem.

Powody:

- mogloby zmienic charakter symulacji egzaminu,
- mogloby wplywac na czas odpowiedzi,
- wymaga osobnych decyzji UX i regulaminowych,
- zwieksza ryzyko regresji w najbardziej wrazliwym przeplywie.

Technicznie:

- nie dodawac przyciskow audio do `StudySessions/Exam.vue`,
- nie dodawac automatycznego czytania pytania w egzaminie,
- nie mieszac audio z `examUi` i timerami.

## Analityka

Analityka audio zostaje pominieta w MVP.

Powody:

- w kodzie nie ma obecnie wyraznej warstwy eventowej typu `track`, `gtag`, `dataLayer`,
- dodanie eventow wymagaloby osobnej decyzji narzedziowej,
- funkcja audio moze dzialac wartosciowo bez sledzenia.

Nie robimy w MVP:

- `audio_question_play`,
- `audio_explanation_play`,
- `audio_exam_trick_play`,
- `audio_pause`,
- `audio_complete`,
- `audio_error`,
- `answer_selected_after_audio`.

Po MVP mozna dodac analityke jako osobny etap, jesli zostanie wybrane narzedzie i polityka prywatnosci bedzie to obejmowac.

## Generowanie audio

Decyzja MVP:

- nie wymagamy stalego queue workera na produkcji,
- nie generujemy audio w tle po kliknieciu w panelu ani po wejsciu uzytkownika,
- pierwszy wariant moze dzialac przez eksport tekstow do pliku, wygenerowanie glosu poza aplikacja, a potem import gotowych plikow MP3.
- Laravel pozostaje zrodlem prawdy dla listy pytan i raportu postepu.
- Zewnetrzny generator ElevenLabs nie zapisuje bezposrednio do bazy Laravel; zwraca gotowe MP3 i manifest wynikowy.
- lokalny katalog generatora na komputerze wlasciciela: `D:\audio-generator`.
- pierwszy glos ElevenLabs: `N0GCuK2B0qwWozQNTS8F`.
- pierwszy zakres generowania: wylacznie tresc pytania, czyli `audio_type=question`.

To pasuje do wariantu z ElevenLabs:

1. aplikacja eksportuje plik manifestu dla pytania 99 albo batcha,
2. manifest zawiera `asset_key`, `external_id`, `audio_type`, `content_scope`, `source_text`, `source_text_hash`, sugerowana nazwe pliku i metadane glosu,
3. pliki audio sa generowane zewnetrznie, np. w ElevenLabs,
4. gotowe MP3 wracaja do katalogu importu,
5. komenda importu zapisuje pliki na `media_local`, tworzy/aktualizuje rekordy `question_audio_assets` i zapisuje czas trwania, rozmiar oraz metadane glosu.

Mozliwy pozniejszy wariant:

- adapter API do ElevenLabs albo innego TTS,
- job w kolejce,
- generowanie z panelu admina.

Na start wystarczy tryb plikowy, bo daje kontrole kosztu, jakosci i tempa pracy.

Audio generujemy operacyjnie:

- z komendy Artisan,
- przez eksport manifestu,
- przez import gotowych plikow,
- ewentualnie pozniej z panelu admina.

Nie generujemy:

- przy kazdym wejsciu na strone,
- przez publiczny endpoint z dowolnym tekstem,
- automatycznie dla calej bazy bez kontroli kosztow.

Potrzebne elementy:

- `QuestionAudioCoverageService`,
- `questions:audio-coverage`,
- `QuestionAudioExportManifestBuilder`,
- `QuestionAudioImportService`,
- opcjonalnie `TextToSpeechProvider` jako interfejs na pozniejszy etap API,
- opcjonalnie `FakeTextToSpeechProvider` do testow lokalnych,
- opcjonalnie docelowy provider TTS przez adapter,
- `GeneratedAudio` jako obiekt wyniku,
- komenda eksportu manifestu dla jednego pytania,
- komenda eksportu manifestu dla probki 20-50 pytan,
- komenda do importu gotowych plikow,
- komenda do sprawdzenia, ktore rekordy sa nieaktualne wzgledem aktualnego `source_text_hash`.

Przykladowe komendy:

```bash
php artisan questions:audio-coverage --type=question
php artisan questions:audio-export --external-id=99 --types=question --output="D:\audio-generator\input\audio-export-question-99.json"
php artisan questions:audio-import --manifest="D:\audio-generator\output\manifest-result.json" --input="D:\audio-generator\output\audio"
php artisan questions:audio-export --category=C --types=question --limit=50 --output="D:\audio-generator\input\category-c-batch-001.json"
php artisan questions:audio-audit-stale --external-id=99
```

Sciezki w MVP wskazuja bezposrednio katalog lokalnego generatora `D:\audio-generator`, bo na dysku C jest malo miejsca. Wazny jest kontrakt manifestu, a nie to, z ktorego dysku plik zostal podany do komendy.

W lokalnym Dockerze katalog hosta `D:\audio-generator` jest montowany do kontenera aplikacji jako `/audio-generator`. Wtedy komendy uruchamiane przez Docker powinny uzywac sciezek kontenerowych:

```bash
docker compose exec -T app php artisan questions:audio-export --external-id=99 --types=question --output=/audio-generator/input/audio-export-question-99.json
docker compose exec -T app php artisan questions:audio-import --manifest=/audio-generator/output/manifest-result.json --input=/audio-generator/output/audio
```

Wyjasnienie "queue worker":

`QUEUE_CONNECTION=sync` oznacza, ze zadania kolejkowe w produkcji nie dzialaja jako osobny proces w tle. Gdyby implementacja zakladala generowanie przez joby, trzeba byloby wdrozyc i utrzymywac stalego workera. Dla MVP tego unikamy.

## Panel admina

Rekomendacja:

- nie mieszac audio z ekranem uploadu mediow,
- dodac osobna sekcje albo osobna strone `Audio lekcji` przy pytaniu.

W panelu powinno byc widac:

- typ audio,
- status,
- czy audio jest aktualne wzgledem tekstu,
- data wygenerowania,
- czas trwania,
- provider i glos,
- przycisk odtworzenia,
- przycisk regeneracji,
- ostatni blad.

Minimalny MVP admina moze zaczac sie od komend Artisan bez panelu. Panel jest wygodny, ale nie jest warunkiem pierwszego testu technicznego.

## Storage i sciezki

Rekomendacja:

- dodac osobny `config/audio.php`,
- domyslnie uzyc lokalnego dysku `media_local`,
- pliki MP3 trzymac na naszym serwerze i serwowac publicznie przez `/storage-bulk`,
- nie uzywac R2/S3 w MVP,
- nie dodawac plikow audio do backupu aplikacyjnego,
- trzymac lokalna kopie zrodlowa/wlascicielska poza VPS albo mozliwosc ponownego wygenerowania z manifestu,
- dodac prosty mechanizm sprzatania starych plikow po zmianie hasha, gdy zaczniemy generowac wieksze batche,
- zostawic `storage_disk` i `storage_path` w modelu, zeby w przyszlosci migracja na inny storage nie wymagala zmiany kontraktu danych.

Proponowana konfiguracja:

```php
return [
    'disk' => env('QUESTION_AUDIO_DISK', 'media_local'),
    'prefix' => trim((string) env('QUESTION_AUDIO_PREFIX', 'audio/questions'), '/'),
];
```

W `.env` dla MVP:

```text
QUESTION_AUDIO_DISK=media_local
QUESTION_AUDIO_PREFIX=audio/questions
```

Przykladowa sciezka:

```text
audio/questions/99/pl/question-a1b2c3d4.mp3
audio/questions/99/pl/explanation-a1b2c3d4.mp3
audio/questions/99/pl/exam-trap-a1b2c3d4.mp3
```

Hash w nazwie jest wazny, bo pozwala ustawic dlugi cache i bezpiecznie regenerowac pliki po zmianie tekstu.

Decyzja o nieaktualnosci:

- `source_text_hash` jest potrzebny juz w pierwszym etapie, bo chroni przed odtwarzaniem starego audio po zmianie tekstu,
- automatyczne oznaczanie statusu `outdated` nie jest niezbedne w pierwszej implementacji,
- wystarczy, ze payload/UI zwraca tylko rekord, ktorego hash zgadza sie z aktualnym tekstem,
- osobna komenda audytowa moze pokazac rekordy, ktore sa stare i wymagaja ponownego wygenerowania.

## Zasady UX

Wspolne zasady:

- brak autoplay,
- brak odtwarzania po samym zaladowaniu strony,
- odtwarzanie tylko po kliknieciu uzytkownika,
- jeden aktywny odtwarzacz naraz,
- czytelny stan: idle, loading, playing, paused, error,
- dostepnosc klawiatura,
- przycisk widoczny tylko dla dostepnego audio,
- tekst audio zawsze widoczny na stronie w tej samej tresci lub rownowaznej transkrypcji.

Publiczna strona:

- przycisk pytania w okolicy naglowka lub pod metadanymi,
- przycisk wyjasnienia w sekcji `Wyjaśnienie`,
- przycisk haczyka w sekcji `Haczyk egzaminacyjny`.

Modul nauki:

- przycisk pytania obok tresci pytania,
- przycisk wyjasnienia dopiero tam, gdzie wyjasnienie jest juz pokazane albo dozwolone przez obecna logike,
- brak przycisku w trybie egzaminacyjnym.

## Ryzyka i zabezpieczenia

| Ryzyko | Na co uwazac | Zabezpieczenie w projekcie |
| --- | --- | --- |
| Pomieszanie tresci SEO z trescia nauki | Publiczne `question_public_explanations` i naukowe `questions.explanation` moga byc celowo rozne. | Uzywac `content_scope`; publiczna strona czyta publiczna warstwe, a `/nauka` czyta tekst widoczny w module nauki. |
| Stare audio po zmianie tekstu | Nawet jesli pytan nie edytujemy, mozemy poprawiac publiczne wyjasnienie albo haczyk. | Zostawic `source_text_hash`; payload pokazuje tylko audio, ktore pasuje do aktualnego hasha tekstu. |
| Klasyczna nauka pomylona z egzaminem | Klasyczna nauka ma `ui_shell = exam_like`, ale nadal moze miec `session.mode = learn`. | Decyzje funkcjonalne opierac o `session.mode`; `ui_shell` traktowac tylko jako layout. |
| Zen rozbity przez dodatkowe kontrolki | Zen jest trybem skupienia i nie powinien dostac ciezkiego UI. | Ten sam payload, ale inny, kompaktowy placement przycisku w `ui_shell = zen`. |
| Audio w trybie egzaminacyjnym | Audio mogloby zmienic charakter symulacji i timerow. | `session.mode === "exam"` zawsze bez audio; nie zmieniac `Exam.vue` i `ExamResult.vue` w MVP. |
| Ujawnienie wyjasnienia w review za wczesnie | Audio wyjasnienia moze zdradzic odpowiedz przed momentem, w ktorym UI ja pokazuje. | Audio wyjasnienia pokazywac tylko wtedy, gdy obecna logika pokazuje tekst wyjasnienia. |
| `AudioObject` nadpisze `VideoObject` | `PublicQuestionSchemaService` obecnie obsluguje `WebPage.hasPart` dla wideo jako pojedynczy obiekt. | Zmienic `hasPart` na liste elementow i testowac strone z wideo + audio. |
| Google dostaje sygnal, ktorego strona nie pokazuje | Schema nie moze obiecywac audio, ktorego nie ma w UI. | `AudioObject` tylko dla plikow widocznych i gotowych; `transcript` zgodny z widocznym tekstem. |
| Zbyt duze oczekiwania SEO | `AudioObject` jest semantycznym sygnalem, ale nie gwarancja osobnego rich result. | Nie dodawac audio sitemap w MVP; najpierw HTML, JSON-LD, stabilne URL-e i test URL Inspection/Rich Results. |
| Publiczne pliki audio sa dostepne bez logowania | `/storage-bulk/audio/...` bedzie mozliwe do odtworzenia przez kazdego z URL-em. | To jest zaakceptowane dla publicznych stron; nie umieszczac tam tresci premium ani ukrytej tresci nauki. |
| Brak backupu audio na VPS | Utrata plikow audio z serwera oznacza brak odtwarzania. | Backupowac metadane i manifesty; trzymac lokalna kopie wlascicielska albo miec mozliwosc regeneracji z manifestu. |
| Zapelnienie dysku na serwerze | Audio lokalne doklada sie do obrazow i wideo w `storage-bulk`. | Start od pytania 99, potem male batche; dodac audyt rozmiaru i czyszczenie starych hashy przed skala. |
| Konflikt cache po regeneracji | Nadpisanie tego samego URL-a moze zostawic stare MP3 w cache. | Hash w nazwie pliku; nie nadpisywac starych sciezek tym samym URL-em. |
| Bledny import plikow z ElevenLabs | Latwo podpiac MP3 do zlego typu audio albo zlego pytania. | Manifest z `asset_key`, `audio_type`, `content_scope`, `source_text_hash`; import waliduje pliki przed zapisem. |
| Nadmiarowe pliki dla wielu kategorii | To samo pytanie wystepuje w wielu kategoriach. | Deduplikacja po `external_id`; `category_code` tylko gdy tekst realnie rozni sie per kategoria. |
| Zalozenie nieistniejacej kolejki | Produkcja ma `QUEUE_CONNECTION=sync`, brak stalego workera na start. | MVP przez eksport/import i komendy Artisan; queue/API TTS dopiero jako pozniejszy etap. |
| Pogorszenie wydajnosci publicznej strony | Audio nie moze blokowac LCP ani pobierac MP3 od razu. | `preload="none"` albo tworzenie `Audio` dopiero po kliknieciu; brak autoplay. |
| Slaba dostepnosc kontrolek | Audio ma pomagac, nie tworzyc bariery. | Przyciski obslugiwane klawiatura, czytelne stany, widoczny tekst jako transkrypcja. |

## Kolejnosc prac przy przyszlej implementacji

1. Dodac model, migracje i fabryke `QuestionAudioAsset`.
2. Dodac `config/audio.php`.
3. Dodac `QuestionAudioTextBuilder`.
4. Dodac `QuestionAudioPayloadBuilder`.
5. Dodac `QuestionAudioCoverageService` i komende `questions:audio-coverage`.
6. Dodac eksport manifestu audio dla pytania 99.
7. Dodac import gotowych plikow MP3 z manifestu.
8. Dodac publiczny payload audio do `PublicQuestionDatabaseController`.
9. Dodac UI audio do publicznego Blade.
10. Dodac `AudioObject` w `PublicQuestionSchemaService`.
11. Przetestowac pytanie 99.
12. Dopiero po akceptacji dodac batch 20-50 publicznych pytan.
13. Dopiero po publicznym etapie dodac payload audio do klasycznej nauki w `StudySessionController`.
14. Dodac typy i komponent audio w `StudySessions/Show.vue`.
15. Dodac payload audio do `StudySessionApiPayloadBuilder`.

Nie dodawac w tej kolejce:

- analityki,
- trybu egzaminacyjnego,
- automatycznego feedbacku po odpowiedzi.

## Testy akceptacyjne MVP

Publiczna strona:

- strona pytania dziala bez audio,
- przyciski audio pojawiaja sie tylko dla statusu `generated`,
- klik odtwarza audio,
- drugi klik pauzuje albo zatrzymuje audio,
- klik innego audio zatrzymuje poprzednie,
- `AudioObject` istnieje tylko dla dostepnych plikow,
- `transcript` odpowiada widocznej tresci,
- `VideoObject` nie znika po dodaniu audio,
- brak audio nie zmienia LCP ani nie blokuje renderowania strony.

Modul nauki:

- klasyczna nauka dostaje `question.audio`,
- review dostaje `question.audio` tylko tam, gdzie zgodne jest to z obecna logika ujawniania tresci,
- tryb egzaminacyjny nie pokazuje audio,
- odpowiedz po kliknieciu nie uruchamia audio automatycznie,
- brak audio nie psuje payloadu pytania,
- prefetched questions i current question maja ten sam ksztalt payloadu.

Backend:

- komenda `questions:audio-coverage` liczy postep po kanonicznym `external_id`,
- zmiana tekstu powoduje nowy hash,
- stary plik moze zostac oznaczony jako `outdated`,
- komenda dla jednego pytania generuje tylko brakujace audio,
- komenda dla probki respektuje limit,
- publiczny payload nie zwraca `failed`, `pending`, `outdated`, `disabled`.

## Rekomendacja koncowa

Funkcja ma sens, ale powinna wejsc jako wspolna infrastruktura lekcji audio, nie jako osobny widget na jednej stronie.

Najbezpieczniejszy MVP:

- pytanie 99 jako pierwszy przypadek testowy,
- potem 20-50 pytan z filmami, tramwajami, pierwszenstwem, zatrzymaniem i znakami,
- najpierw publiczna strona,
- klasyczna nauka i Zen jako pozniejszy etap na tej samej infrastrukturze,
- bez analityki,
- bez trybu egzaminacyjnego,
- bez automatycznego czytania po odpowiedzi.

Taki zakres daje wartosc uzytkownikowi i SEO, ale nie rozlewa sie na wrazliwe przeplywy egzaminu ani na dodatkowa infrastrukture analityczna.

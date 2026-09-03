# Strategia Testow

## 1. Cel dokumentu

Ten dokument definiuje strategie testowania dla projektu od MVP do V2.

Celem jest:

- utrzymac stabilnosc krytycznych flow,
- ograniczyc regresje podczas szybkiego rozwoju,
- testowac to, co faktycznie niesie ryzyko biznesowe i techniczne,
- uniknac sytuacji, w ktorej mamy albo zero testow, albo mase testow bez wartosci.

## 2. Zasady nadrzedne

1. Najpierw testujemy rzeczy krytyczne dla uzytkownika.
2. Testy maja skracac czas dowozenia zmian, nie tylko zwiekszac formalizm.
3. Nie kazdy detal wymaga E2E.
4. Najdrozsze testy uruchamiamy najrzadziej.
5. Kontrakty API i model danych sa miejscem, gdzie regresje bola najbardziej.

## 3. Zakres testow

Strategia obejmuje:

- testy jednostkowe,
- testy integracyjne,
- testy kontraktowe API,
- testy E2E,
- testy smoke po deployu,
- testy import pipeline,
- testy bezpieczenstwa w podstawowym zakresie,
- testy wydajnosciowe w wersji lekkiej.

## 4. Priorytet ryzyka

Najwyzszy priorytet testowy maja:

1. logowanie i autoryzacja,
2. tworzenie sesji,
3. zapis odpowiedzi,
4. finalizacja sesji,
5. dashboard i kolejka powtorek,
6. import pytan i assetow,
7. kontrola dostepu do endpointow admin.

Nizszy priorytet:

- drobne detale UI,
- layouty marketingowe,
- kosmetyczne stany komponentow bez logiki.

## 5. Podzial testow

## 5.1. Unit tests

Cel:

- testowanie czystej logiki i malych modulow.

Co testujemy:

- scoring sesji,
- mapowanie SM-2,
- walidatory payloadow,
- helpery do liczenia dashboardu,
- parsery danych importowych,
- mapowanie statusow i rol.

Unit tests powinny byc:

- szybkie,
- deterministyczne,
- bez sieci,
- bez prawdziwego storage.

## 5.2. Integration tests

Cel:

- testowanie polaczenia warstw aplikacji i bazy.

Co testujemy:

- tworzenie sesji z zapisem `session_questions`,
- zapis odpowiedzi do poprawnego pytania,
- aktualizacje `user_question_progress`,
- odczyt dashboardu,
- zapis `media_assets`,
- role i autoryzacje backendowe.

Te testy powinny dotykac:

- bazy testowej,
- warstwy serwisowej,
- walidacji i repository layer.

## 5.3. Contract/API tests

Cel:

- pilnowac, zeby endpointy nie zmienialy sie przypadkiem.

Co testujemy:

- shape requestu,
- shape odpowiedzi,
- kody statusu,
- bledy walidacyjne,
- brak wycieku pol niedozwolonych w `exam`.

Najwazniejsze endpointy:

- `POST /api/v1/sessions`
- `POST /api/v1/sessions/{id}/answers`
- `POST /api/v1/sessions/{id}/complete`
- `GET /api/v1/me/dashboard`
- `POST /api/v1/admin/media/presign`

## 5.4. E2E tests

Cel:

- sprawdzic, czy prawdziwy user flow dziala od poczatku do konca.

Minimalny zestaw MVP:

1. logowanie,
2. start albo wznowienie sesji z aktualnego panelu `/nauka`,
3. zapis odpowiedzi i przejscie playera do kolejnego stanu,
4. powrot do dashboardu `/nauka`,
5. rejestracje service workera i brak cache prywatnych sciezek,
6. offline fallback,
7. mobilna nawigacje oraz brak poziomego overflow na 360/390/430 px.

Obecna implementacja MVP:

- `npm run e2e:smoke`

Zakres obecnego browser smoke:

- bootstrap danych smoke,
- lokalny start aplikacji albo wskazany `E2E_SMOKE_BASE_URL`,
- logowanie,
- start albo wznowienie sesji z obecnego UI `/nauka`,
- zapis jednej odpowiedzi w playerze,
- kontrakt manifestu oraz rejestracje service workera,
- offline fallback i kontrola Cache Storage bez prywatnego HTML/API,
- mobilna matryce 360/390/430 px wraz z dolna nawigacja,
- screenshoty i raport JSON z przebiegu.

Media obraz/wideo pozostaja objete przez serwerowy `ops:smoke-test --require-media`; browser smoke nie wymusza juz sztucznego ukonczenia calej sesji tylko po to, aby przejsc przez oba media.

W V2 dochodzi:

- flow organizacji,
- invitation flow,
- dostep instruktora do raportu,
- blokada usera bez odpowiedniej roli.

## 5.5. Smoke tests

Cel:

- szybka walidacja po deployu.

Smoke test powinien potwierdzic:

- aplikacja zyje,
- baza odpowiada,
- sesja sie tworzy,
- media sie laduja,
- dashboard zwraca dane.

Kanoniczna automatyzacja MVP:

- `php artisan ops:seed-smoke-data`
- `php artisan ops:smoke-test`
- `php artisan ops:smoke-test --require-media`

Ten smoke test ma byc uruchamiany po deployu i ma failowac, gdy krytyczne flow serwerowe nie przechodzi.

W praktyce CI powinno bootstrapowac minimalny, deterministyczny dataset przez `ops:seed-smoke-data`, a dopiero potem uruchamiac smoke.

## 6. Test pyramid dla projektu

Preferowany model:

- duzo unit tests,
- srednio integration/contract,
- malo, ale dobrych E2E.

Powod:

- E2E sa drogie i kruche,
- sama baza unit testow bez integration nie wystarczy,
- kontrakt API jest krytyczny dla tego produktu.

## 7. Testy import pipeline

Pipeline importu wymaga osobnej klasy testow.

Minimalne scenariusze:

- poprawny rekord przechodzi,
- brak wymaganych pol odrzuca rekord,
- zly `correct_answer` odrzuca rekord,
- brak pliku mediow daje blad,
- preprocessing tworzy oczekiwane warianty assetow,
- upload zapisuje poprawny `object_key`,
- zapis do bazy tworzy rekordy pytan i mediow,
- dry-run nie zapisuje zmian.

Obecny minimalny standard kodu:

- test `catalog:import-json`,
- test `catalog:import-manifest`,
- test dry-run dla batcha stagingowego,
- test bledu dla brakujacego assetu w stagingu.

## 8. Testy bezpieczenstwa

Na MVP minimum:

- test autoryzacji endpointow `me`,
- test autoryzacji endpointow admin,
- test, ze user nie widzi cudzych sesji,
- test, ze `exam` nie zdradza poprawnych odpowiedzi przed koncem,
- test, ze prywatne assety nie sa zwracane jako publiczne.

Dodatkowy standard dla dojrzalszego MVP:

- test, ze udane operacje administracyjne tworza wpisy w `audit_logs`,
- test, ze admin moze przegladac audit log w panelu,
- test, ze audit log nie przechowuje wrazliwych pol technicznych.

W V2:

- test izolacji tenantow,
- test rol organizacyjnych,
- test audit logu dla operacji administracyjnych.

## 9. Testy wydajnosciowe

Nie potrzebujemy od razu wielkiego performance labu.

Ale potrzebujemy:

- lekkiego benchmarku tworzenia sesji,
- lekkiego benchmarku finalizacji sesji,
- kontroli czasu odpowiedzi dashboardu,
- sprawdzenia, czy media nie obciazaja backendu przez zly flow.

Mierzymy:

- czas odpowiedzi API,
- zuzycie CPU przy krytycznych flow,
- zuzycie RAM po deployu,
- liczbe zapytan do bazy na request.

Obecna automatyzacja MVP:

- `php artisan ops:perf-smoke`,
- `php artisan ops:perf-smoke --assert`.

## 10. Test data strategy

Dane testowe powinny byc:

- male,
- przewidywalne,
- anonimowe,
- latwe do odtworzenia.

Powinnismy miec:

- fixture kategorii `B`,
- kilka pytan bez mediow,
- kilka pytan z obrazem,
- kilka pytan z wideo,
- dane usera,
- dane sesji zakonczonej i trwajacej.

## 11. Co uruchamiac kiedy

### Na kazdym PR

- unit tests,
- integration tests krytyczne,
- contract tests API,
- lint/typecheck.

### Przed wdrozeniem

- smoke tests,
- wybrane E2E krytyczne.

Obecny minimalny standard przed manualnym deployem MVP:

- `php artisan ops:smoke-test --require-media`
- `npm run e2e:smoke`

### Cyklicznie

- pelniejszy zestaw E2E,
- testy pipeline importu,
- lekki performance smoke przez `ops:perf-smoke`.

## 12. Kryteria akceptacji zmian

Zmiana jest gotowa do deployu, gdy:

- przechodzi wymagany zestaw testow,
- nie lamie kanonicznego API,
- nie lamie modelu danych,
- nie pogarsza krytycznych flow bez swiadomej decyzji,
- ma sensowny plan rollbacku, jesli dotyka produkcji.

## 13. Flaki testowe, ktorych unikamy

Nie chcemy:

- E2E zaleznych od losowosci,
- testow opartych o niestabilne sleepy,
- testow API bez kontroli payloadu,
- testow bazy zaleznych od recznego stanu developera,
- sztucznie ogromnej liczby testow komponentow bez wartosci biznesowej.

## 14. Priorytet narzedziowy

Rekomendowany zestaw:

- unit/integration/API: `Pest` + natywne testowanie `Laravel`,
- API contracts: HTTP tests z fixture DB i kontrola shape response,
- E2E: Playwright,
- smoke: prosty zestaw skryptow lub Playwright smoke suite.

Najwazniejsze nie jest narzedzie, tylko stabilnosc i sensowny zakres.

## 15. Definition of done dla jakosci

MVP ma wystarczajaca jakosc, gdy:

- krytyczny flow usera jest pokryty testami,
- import pipeline ma testy walidacyjne,
- najwazniejsze endpointy API maja testy kontraktowe,
- po deployu mamy szybki smoke test,
- regresje sa wykrywane przed produkcja czesciej niz po produkcji.

## 16. Finalna rekomendacja

Profesjonalna strategia testow dla tego projektu powinna byc:

- pragmatyczna,
- oparta o ryzyko,
- skupiona na sesjach, mediach, auth i imporcie,
- wystarczajaco lekka, by nie spowalniac zespolu,
- wystarczajaco mocna, by chronic produkt przed glupimi regresjami.

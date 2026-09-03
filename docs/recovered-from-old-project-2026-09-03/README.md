# Dokumentacja Techniczna

## 1. Cel

Ten katalog zawiera kanoniczna dokumentacje techniczna projektu.

Dokumenty sa rozdzielone tak, aby:

- decyzje architektoniczne byly czytelne,
- infrastruktura MVP i V2 nie mieszaly sie ze specyfikacja API i bazy,
- zespol mial jasny punkt odniesienia przy implementacji,
- kolejne etapy rozwoju nie wymuszaly zgadywania "co bylo uzgodnione".

## 2. Jak czytac te dokumenty

Jesli zaczynasz od zera, rekomendowana kolejnosc jest taka:

1. [JAK-URUCHOMIC-PROJEKT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/JAK-URUCHOMIC-PROJEKT.md)
2. [STATUS-MVP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/STATUS-MVP.md)
3. [ROADMAP-TECH.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ROADMAP-TECH.md)
4. [STACK-DECISION.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/STACK-DECISION.md)
5. [INFRA-MVP-MIKRUS-4.1-R2.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/INFRA-MVP-MIKRUS-4.1-R2.md)
6. [AUTH-DEPLOYMENT-REPAIR-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/AUTH-DEPLOYMENT-REPAIR-PLAN.md)
7. [DATABASE-SCHEMA.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/DATABASE-SCHEMA.md)
8. [API-SPEC.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/API-SPEC.md)
9. [IMPORT-PIPELINE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/IMPORT-PIPELINE.md)
10. [PUBLIC-QUESTION-EXPLANATION-IMPORT-RUNBOOK.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PUBLIC-QUESTION-EXPLANATION-IMPORT-RUNBOOK.md)
11. [QUESTION-INTEGRITY-AUDIT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/QUESTION-INTEGRITY-AUDIT.md)
12. [TEST-STRATEGY.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/TEST-STRATEGY.md)
13. [ADR-001-MODULAR-MONOLITH.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ADR-001-MODULAR-MONOLITH.md)
14. [RUNBOOK-OPS.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/RUNBOOK-OPS.md)
15. [CI-CD.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/CI-CD.md)
16. [SECURITY-BASELINE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SECURITY-BASELINE.md)
17. [INFRA-V2-ENTERPRISE-B2B.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/INFRA-V2-ENTERPRISE-B2B.md)
18. [GOVPL-INTEGRATION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/GOVPL-INTEGRATION-PLAN.md)
19. [SESSION-MODULE-ARCHITECTURE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SESSION-MODULE-ARCHITECTURE.md)
20. [MENU-SYSTEM-REFERENCE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/MENU-SYSTEM-REFERENCE.md)
21. [AUTH-CSRF-419-AUDIT-AND-REPAIR-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/AUTH-CSRF-419-AUDIT-AND-REPAIR-PLAN.md)

## Produkcja rolek wideo

Jeżeli zadanie dotyczy tworzenia rolki z publicznego pytania, użyj
[RUNBOOK_PRODUKCJA_ROLEK_WIDEO_CLASSIC_QUIZ_V1.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/RUNBOOK_PRODUKCJA_ROLEK_WIDEO_CLASSIC_QUIZ_V1.md).
To kanoniczny punkt wejścia do gotowego blueprintu, ElevenLabs, renderu i QA.

## OSK V2.12 — nowy moduł B2B

Jeżeli zadanie dotyczy OSK, formalnej nauki teorii, organizacji, enrollmentów,
PAPER lub egzaminów wewnętrznych, rozpocznij od
[OSK V2.12 — start i przekazanie pracy](./osk/README.md).

Moduł OSK jest planowanym, odrębnym kontekstem domenowym. Nie należy mylić go
z istniejącym `/nauka`, symulatorem egzaminu ani kolekcją pytań „Kwalifikacja”.

## 3. Mapa dokumentow

### Kanoniczne

- [JAK-URUCHOMIC-PROJEKT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/JAK-URUCHOMIC-PROJEKT.md)
  Najkrotsza instrukcja uruchomienia repo lokalnie: Docker Desktop, build frontendu, media z lokalnej paczki i podstawowa diagnostyka.

- [ROADMAP-TECH.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ROADMAP-TECH.md)
  Opisuje kolejnosc prac od MVP do V2 oraz bramki decyzyjne.

- [STATUS-MVP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/STATUS-MVP.md)
  Zapisuje rzeczywisty status projektu po wdrozeniu technicznego MVP, pierwszym produkcyjnym deployu i decyzji o docelowej domenie `https://prawkonaraz.pl`, blokery launchu i rekomendowany nastepny krok.

- [INFRA-MVP-MIKRUS-4.1-R2.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/INFRA-MVP-MIKRUS-4.1-R2.md)
  Definiuje aktualny setup MVP po wyborze `Mikrus 4.1 PRO`: aplikacja docelowo pod `https://prawkonaraz.pl`, lokalny PostgreSQL i Redis na jednym VPS, Cloudflare DNS/HTTPS, kanoniczny redirect `www -> bez www` oraz docelowe R2 dla mediow i backupow.

- [AUTH-DEPLOYMENT-REPAIR-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/AUTH-DEPLOYMENT-REPAIR-PLAN.md)
  Kanoniczny plan naprawy rejestracji, logowania, OAuth, weryfikacji email, resetu hasla i produkcyjnego SMTP. Rozdziela rzeczy wykonane, ryzyka, decyzje do podjecia, kolejnosc wdrozenia i matryce testow akceptacyjnych.

- [PAYMENT-ACCESS-MODE-IMPLEMENTATION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PAYMENT-ACCESS-MODE-IMPLEMENTATION-PLAN.md)
  Kanoniczny plan runtime przełącznika `Platnosc wymagana / Dostep otwarty`: rozdziela darmowa rejestracje od dostepu do nauki, opisuje zawieszenie PJM, panel administratora, bezpieczne zachowanie checkoutu, testy i deploy.

- [osk/README.md](./osk/README.md)
  Punkt wejścia do planowanego modułu OSK V2.12: kolejność dokumentów, granice względem `/nauka`, aktualny stan, checklisty testów i instrukcja przekazania pracy kolejnemu agentowi.

- [INFRA-MVP-MIKRUS-3.5-R2.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/INFRA-MVP-MIKRUS-3.5-R2.md)
  Historyczny wariant minimalnego kosztu po wyborze `Mikrus 3.5`: aplikacja na Mikrusie, PostgreSQL poza procesem aplikacji.

- [INFRA-MVP-HETZNER-R2.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/INFRA-MVP-HETZNER-R2.md)
  Historyczny wariant budzetowej infrastruktury oparty o `Hetzner VPS`. Zostaje jako punkt odniesienia, ale nie jest juz glownym targetem wdrozeniowym.

- [STACK-DECISION.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/STACK-DECISION.md)
  Formalizuje finalny wybor Laravel + Inertia + Vue + PostgreSQL + Filament.

- [DATABASE-SCHEMA.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/DATABASE-SCHEMA.md)
  Definiuje model danych, relacje, indeksy i granice MVP/V2.

- [API-SPEC.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/API-SPEC.md)
  Definiuje kontrakty API, auth, bledy, rate limiting i endpointy.

- [IMPORT-PIPELINE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/IMPORT-PIPELINE.md)
  Opisuje kanoniczny proces importu pytan i assetow do systemu.

- [PUBLIC-QUESTION-EXPLANATION-IMPORT-RUNBOOK.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PUBLIC-QUESTION-EXPLANATION-IMPORT-RUNBOOK.md)
  Operacyjny runbook importu redakcyjnych wyjasnien pytan z plikow markdown: payloady, preview, walidacje, `Nie pomyl z`, `Powiazane pytania`, podstawy prawne, brakujace przepisy i checklista produkcyjna dla kolejnego agenta.

- [PUBLIC-QUESTION-EXPLANATION-IMPORT-LOG.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PUBLIC-QUESTION-EXPLANATION-IMPORT-LOG.md)
  Chronologiczny log produkcyjnych importow publicznych wyjasnien pytan: zakres wsadu, zaakceptowane warianty promptow, dodane przepisy, backupy, weryfikacja i znane braki znakow.

- [QUESTION-INTEGRITY-AUDIT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/QUESTION-INTEGRITY-AUDIT.md)
  Definiuje powtarzalny audit integralnosci po kazdym nowym wsadzie `gov.pl`: snapshoty, diffy, pola krytyczne i decyzje operatorskie po imporcie.

- [TEST-STRATEGY.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/TEST-STRATEGY.md)
  Definiuje warstwy testow, priorytety ryzyka i minimalny standard jakosci.

- [ADR-001-MODULAR-MONOLITH.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ADR-001-MODULAR-MONOLITH.md)
  Formalizuje decyzje architektoniczna o wyborze modularnego monolitu.

- [RUNBOOK-OPS.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/RUNBOOK-OPS.md)
  Opisuje deploy, backup, restore, monitoring i reakcje na typowe awarie dla aktualnego setupu `Mikrus 4.1 + Nginx + Cloudflare`.

- [CI-CD.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/CI-CD.md)
  Formalizuje aktualny workflow CI, bootstrap smoke data i gate jakosci przed deployem.

- [SECURITY-BASELINE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SECURITY-BASELINE.md)
  Definiuje minimalny standard bezpieczenstwa dla MVP i kierunek dojrzewania do V2.

- [INFRA-V2-ENTERPRISE-B2B.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/INFRA-V2-ENTERPRISE-B2B.md)
  Opisuje przejscie z MVP do bardziej enterprise/B2B architektury.

### Wspierajace

- [GOVPL-INTEGRATION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/GOVPL-INTEGRATION-PLAN.md)
  Zapisuje stan integracji oficjalnej bazy gov.pl, wyniki audytu mediow i plan wdrozenia `B-first`.

- [UI-BENCHMARK-PL-DRIVING-TESTS.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-BENCHMARK-PL-DRIVING-TESTS.md)
  Zapisuje skrotowy benchmark polskich serwisow z kategorii `testy na prawo jazdy`.

- [UI-BENCHMARK-PL-DRIVING-TESTS-DEEP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-BENCHMARK-PL-DRIVING-TESTS-DEEP.md)
  Rozbija topowe serwisy na konkretne wzorce layoutu, mobile i kierunki referencyjne dla redesignu frontu.

- [PLAYER-AUDIT-ZDAMYTO-NAUKA.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PLAYER-AUDIT-ZDAMYTO-NAUKA.md)
  Szczegolowy audyt samego playera nauki pytan w ZdamyTo: stany, media, pomiary szybkosci i backlog zmian potrzebnych, by nasz panel nauki dzialal podobnie szybko.

- [PUBLIC-DEMO-20-PYTAN-PLAYER-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PUBLIC-DEMO-20-PYTAN-PLAYER-PLAN.md)
  Szczegolowy plan publicznego demo 20 pytan na `/testy-na-prawo-jazdy`: izolowany flow klasycznej nauki, dobor pytan, frozen packet bez requestu po kazdej odpowiedzi, granice bezpieczenstwa dla egzaminu/trenera pamieci, sprinty i QA.

- [PWA-NAUKA-MOBILE-RAPORT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-NAUKA-MOBILE-RAPORT.md)
  Roboczy raport i zrodlo wiedzy dla przejscia `/nauka` w profesjonalna PWA oraz pozniejsza aplikacje mobilna pod Google Play/TWA: aktualny stan kodu, mapa flow, braki, ryzyka, decyzje i rekomendowana kolejnosc prac.

- [MOBILE-UX-AUDYT-2026-07-10.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/MOBILE-UX-AUDYT-2026-07-10.md)
  Audyt realnych widokow mobile `/nauka`, konfiguratora sesji, trenera pamieci, profilu i playera: blokujace tap targets, flow aplikacyjny, docelowy kontrakt ekranow oraz kolejnosc napraw UI/UX.

- [IOS-NATIVE-MOBILE-REDESIGN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/IOS-NATIVE-MOBILE-REDESIGN.md)
  Kontrakt przebudowy zalogowanej aplikacji mobile do spojnego, iOS-style UI: wspolny shell, destination, wzorce komponentow, granice playera i kolejnosc wdrozenia.

- [PWA-TWA-V1-PROJEKT-APLIKACJI-MOBILNEJ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-PROJEKT-APLIKACJI-MOBILNEJ.md)
  Dokument projektowy PWA/TWA v1: zakres aplikacji mobilnej, ekrany, app shell, kontrakty danych, PWA runtime, TWA release path, quality gate, monitoring, sprinty i decyzje do zatwierdzenia.

- [PWA-TWA-V1-IMPLEMENTATION-BACKLOG.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-IMPLEMENTATION-BACKLOG.md)
  Backlog wykonawczy PWA/TWA v1: sprinty, zadania, pliki, testy, DoD, ryzyka i pytania otwarte przed pierwszym kodowaniem.

- [PWA-TWA-V1-SPRINT-1-MOBILE-HOME-SPEC.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-SPRINT-1-MOBILE-HOME-SPEC.md)
  Spec wykonawczy Sprintu 1: mobilny home `/nauka`, `active_session`, minimalny `learning_dashboard`, usuniecie placeholderow, confirmation flow i testy.

- [PWA-TWA-V1-MOBILE-API-CONTRACT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PWA-TWA-V1-MOBILE-API-CONTRACT.md)
  Projekt kontraktu mobile API: `learning-home`, `sessions/current`, pelne filtry startu sesji, odpowiedzi, bledy, cache policy i idempotencja pod przyszle offline-lite.

- [SESSION-MODULE-ARCHITECTURE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SESSION-MODULE-ARCHITECTURE.md)
  Kanoniczna dokumentacja architektury modulu `Sesja`: granice odpowiedzialnosci, layout `zen mode`, model stanu playera, tryby feedbacku, sterowanie klawiatura, invariants wydajnosciowe i zasady bezpiecznej rozbudowy dla AI-agenta.

- [QUESTION-EXPLANATION-VISUAL-SYSTEM.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/QUESTION-EXPLANATION-VISUAL-SYSTEM.md)
  Kanoniczna specyfikacja systemu wizualnych objasnien do pytan. Obejmuje wdrozony `Sprint 1 / MVP v1`, bazowy `Etap 2 / annotations MVP`, shared flow grafik po `external_id + source`, runtime stopklatek `video_frame`, toggles `Pogrubienia / Kolory` w `Ustawienia nauki`, stabilne laczenie markerow inline (`[green]/[red]` + `**bold**`), nowy copy UX bez technicznej skladni, adminowe usprawnienia listy `Baza pytan` (search odporny na markery i bardziej czytelne pole wyszukiwania) oraz najnowsze fixy stabilizacyjne: pewny zapis markerow (`data.explanation_annotations`) i guard overflow `interval_days` w progresie odpowiedzi.

- [ANNOTATION-ARROW-EXTENSION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ANNOTATION-ARROW-EXTENSION-PLAN.md)
  Szczegolowy audyt i plan bezpiecznego rozszerzenia adnotacji medium o typ `arrow` (dlugosc, wielkosc, kierunek, kolor), obejmujacy backend, admin helper, runtime i testy rolloutowe.

- [SHARED-QUESTION-EXPLANATION-ASSETS.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SHARED-QUESTION-EXPLANATION-ASSETS.md)
  Kanoniczny opis wdrozonego shared flow dla grafiki pokazywanej po lewej stronie wyjasnienia: model `local -> shared -> fallback`, zapis `single / shared_external_id`, zabezpieczenia plikowe, zakres funkcji i rzeczy swiadomie pozostawione poza tym mechanizmem.

- [PREMIUM-REVIEW-TRAINER-ROADMAP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PREMIUM-REVIEW-TRAINER-ROADMAP.md)
  Kanoniczny plan rozbudowy `Trenera pamieci` od obecnego `sr_review` do premium trenera pamieciowego: baseline logiki, model danych v2, event log, planner dnia, Sprint 1 dla planner v1, versioned policy engine i kolejnosc wdrozenia bez przepalania architektury.

- [ADAPTIVE-LEARNING-ARCHITECTURE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ADAPTIVE-LEARNING-ARCHITECTURE.md)
  Dokument integracyjny spinajacy `Trenera pamieci` z `Question Explanation Visual System`: poziomy interwencji, typy bledow, petla uczenia, kontrakt event logu i Sprint 1 dla pierwszej warstwy adaptive learning.

- [SESSION-EXPLANATION-UX-MOCK.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SESSION-EXPLANATION-UX-MOCK.md)
  Dokumentuje domkniety flow hintow w trybie `Dobrze / zle + wyjasnienie`: gdzie pokazujemy krotki hint instruktora po bledzie, jak dzialamy bez `questions.explanation` i jaki standard tresci ma obowiazywac w trakcie sesji.

- [SESSION-MODULE-DEBUG-2026-03-27.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SESSION-MODULE-DEBUG-2026-03-27.md)
  Kanoniczny debug log dla modulu `Sesja`: reprodukcja, pomiary, root cause'y, odrzucone tropy i aktualna prawda robocza dla agenta AI debugujacego flow `kategoria -> sesja -> pytanie -> odpowiedz -> nastepne pytanie`.

- [UI-AUDIT-PRAWO-JAZDY-360.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-AUDIT-PRAWO-JAZDY-360.md)
  Pelny audyt publicznych i prywatnych shelli, komponentow i flow Prawo-Jazdy-360 po szostym, ostatecznym coverage passie, z domknietym `cennik`, checkoutem pre-gateway, kontaktem, opiniami, watkiem forum i praktycznym pokryciem `100%` rodzin shelli i stanow.

- [MENU-SYSTEM-REFERENCE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/MENU-SYSTEM-REFERENCE.md)
  Kanoniczna referencja publicznego menu: zrodla danych, renderery Vue i Blade, stany guest/auth/admin/moderator, mobile, wyszukiwarka, account dropdown, drawery logowania/rejestracji, footer oraz screenshoty kontrolne.

- [AUTH-CSRF-419-AUDIT-AND-REPAIR-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/AUTH-CSRF-419-AUDIT-AND-REPAIR-PLAN.md)
  Audyt produkcyjnych bledow `419 Page Expired`: odrzucona hipoteza full-page cache, potwierdzony zwiazek z cyklem zycia sesji/tokenow, mapa podatnych formularzy w menu Blade/Vue oraz plan bezpiecznego odswiezania CSRF bez wylaczania zabezpieczen i bez automatycznego powtarzania zapisow w module nauki.

- [PROFILE-AVATAR-IMPLEMENTATION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PROFILE-AVATAR-IMPLEMENTATION-PLAN.md)
  Plan wdrozenia zdjec profilowych uzytkownikow: priorytet wlasnego uploadu nad Google avatarem, model danych, storage, miejsca wyswietlania, endpointy, QA, deployment i rollback.

- [TODO-PRD-PRAWO-JAZDY-360-ADAPTATION.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/TODO-PRD-PRAWO-JAZDY-360-ADAPTATION.md)
  Operacyjny dokument wdrozeniowy: jak mapujemy caly system shelli PJ360, w tym wynik/review, utility screens, mobile i shelle poboczne, na nasze widoki, z potwierdzonym zakresem po szostym passie audytu i rekomendowana kolejnoscia implementacji.

- [PJ360-EXPLANATION-ADAPTATION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXPLANATION-ADAPTATION-PLAN.md)
  Kanoniczny plan wykorzystania korpusu pytan i wyjasnien PJ360 jako materialu referencyjnego do uzupelniania `questions.explanation`: twarde liczby po skanie, zasady bezpiecznego matchingu, kolejki `Tier A / B / C / PT`, ryzyka prompt duplicate i architektura procesu adaptacji bez slepego kopiowania tresci.

- [PJ360-EXPLANATION-ADAPTATION-RUNBOOK.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXPLANATION-ADAPTATION-RUNBOOK.md)
  Operacyjny runbook do kolejnych przebiegow skanu PJ360, budowy kolejek adaptacji i prowadzenia review: komendy, warunki wejscia, kolejnosc pracy, reguly QA i `no-go rules` przed publikacja wyjasnien.

- [PJ360-CATEGORY-CONSISTENCY-AUDIT-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-CATEGORY-CONSISTENCY-AUDIT-PLAN.md)
  Runbook do audytu zgodnosci dzialow i licznikow pytan miedzy PJ360 a naszym `/nauka`: metoda zbierania danych per kategoria, mapowanie etykiet dzialow, liczenie delt i zejscie do poziomu pytan tylko tam, gdzie liczby faktycznie sie rozjezdzaja.

- [PJ360-CATEGORY-CONSISTENCY-AUDIT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-CATEGORY-CONSISTENCY-AUDIT.md)
  Roboczy raport z pierwszego przebiegu audytu zgodnosci PJ360 vs nasze `/nauka`: zebrane snapshoty, bucket totals, normalizacja etykiet tematow, najwieksze systemowe delty i hipotezy wskazujace na problem klasyfikacji tematow po naszej stronie.

- [PJ360-CATEGORY-TARGET-MATRIX.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-CATEGORY-TARGET-MATRIX.md)
  Operacyjna macierz targetow PJ360 per kategoria dla tematow pilotowych aktywnie strojonych w klasyfikatorze i przyszlych override'ach: `road_markings`, `lane_change_and_turning`, `vehicle_load_and_passenger_safety`, `safety_equipment_and_restraints`, `owner_obligations_insurance_documents`.

- [PJ360-B-SAFE-OVERRIDE-PACKAGE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-B-SAFE-OVERRIDE-PACKAGE.md)
  Pierwszy curated, high-confidence batch override dla kategorii `B`: allowlisty `matched_by` i `topic`, wynik przebiegu oraz bezpieczna sekwencja `review -> sync -> reclassify -> delta report`.

- [PJ360-B-FULL-CLOSURE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-B-FULL-CLOSURE.md)
  Operacyjny plan domkniecia calej kategorii `B` do rozkladu PJ360, juz z dopisanym wykonanym batch-em `missing-local import` i obecnym statusem po dosypaniu brakujacych pytan.

- [PJ360-C-FULL-CLOSURE.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-C-FULL-CLOSURE.md)
  Operacyjny runbook domkniecia `kat. C` po zamknieciu `kat. B`: pelna historia fal (`retopic`, `missing_local import`, `delta-aware`, `manual rebalance wave6`) i aktualny residual po domknieciu najwiekszych rozjazdow.

- [PJ360-MEDIA-AUDIT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-MEDIA-AUDIT.md)
  Kanoniczny zapis audytu i enrichmentu mediow dla pytan importowanych z PJ360: co faktycznie bylo brakujace, co zostalo uzupelnione (`poster_path`, wymiary, duration), i kiedy problem z medium przestaje byc problemem importu.

- [PJ360-B-AMBIGUOUS-AND-MISSING-WORKSET.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-B-AMBIGUOUS-AND-MISSING-WORKSET.md)
  Operacyjny runbook dla etapu `kat. B` po exact sync: historyczny review pack dla `ambiguous_local_match`, shortlista brakow importowych i aktualizacja po wykonanym batchu importowym, po ktorym zostaly juz tylko `already_recoverable_same_category`.

- [PJ360-B-MISSING-LOCAL-INVENTORY.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-B-MISSING-LOCAL-INVENTORY.md)
  Kanoniczny backlog importowy dla `kat. B` po domknieciu topic assignment, juz z dopisanym wykonanym importem brakow i stanem po batchu, gdzie remaining `missing_local` zeszlo do `3`.

- [PJ360-EXACT-TOPIC-MEMBERSHIP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXACT-TOPIC-MEMBERSHIP.md)
  Nowy kanoniczny kierunek domykania zgodnosci z PJ360 na poziomie `temat -> konkretne pytania`: extractor exact-membership z `/kurs/<topic>`, exact-match do naszej bazy i exact override packages.

- [PJ360-EXACT-TOPIC-MEMBERSHIP-STATUS.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXACT-TOPIC-MEMBERSHIP-STATUS.md)
  Krótki status przejścia z modelu `classifier + audited overrides` do modelu `PJ360 exact-membership + exact overrides`, juz z dopisanym milestone `B missing-local import batch`.

- [PJ360-EXACT-VS-LOCAL-MEMBERSHIP-DIFF.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXACT-VS-LOCAL-MEMBERSHIP-DIFF.md)
  Kanoniczny opis bezposredniego porownania `PJ360 -> temat -> pytania` z naszym `local effective membership`, juz na poziomie konkretnych pytan, typu roznicy i pozostalych konfliktow po pierwszej fali `retopic`.

- [PJ360-TOOLING-RUNBOOK.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-TOOLING-RUNBOOK.md)
  Operacyjna instrukcja narzedzi krok-po-kroku: exact extract, local effective, diff, retopic/conflicts, missing-local, import batch, sync override, refresh i checklist domykania kategorii.

- [pj360-b-delta-aware-conflict-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-delta-aware-conflict-package.json)
  Review-only artefakt ostatniej warstwy automatyki dla `B`: planner oparty o `remaining deficit`, ktory po finalnych falach zszedl juz do `0` nowych override i `0` manualnych konfliktow.

- [pj360-b-manual-conflict-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-manual-conflict-package.json)
  Jawnie rozstrzygniety final-pass dla `B`: 4 reczne decyzje tematyczne, ktore domknely `Znaki zakazu, nakazu` oraz `Skrzyzowania z sygnalizacja` do targetow PJ360.

- [PJ360-UNRESOLVED-AND-LOCAL-GAPS.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-UNRESOLVED-AND-LOCAL-GAPS.md)
  Dokument przejścia do kolejnego etapu po exact sync: analiza `unresolved`, `missing locally` i `local-only`, która ma domknąć ostatnie różnice względem PJ360.

- [local-effective-topic-membership-all.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/local-effective-topic-membership-all.json)
  Lustrzany snapshot po naszej stronie: `kategoria -> temat -> liczba pytan -> konkretne pytania` dla realnego `active + ready`, czyli dokladnie tego, co zasila `/nauka`.

- [local-effective-topic-membership-B.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/local-effective-topic-membership-B.json)
  Wycinek lokalnego snapshotu tylko dla `kat. B`, gotowy do bezposredniego porownania z `PJ360 B -> temat -> pytania`.

- [exact-vs-local-topic-membership-diff.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/exact-vs-local-topic-membership-diff.json)
  Bezposredni diff dla `A..T`: ile pytan mamy juz w dobrym temacie, ile jest lokalnie w innym temacie, ile brakuje lokalnie i ile mamy lokalnie ponad membership PJ360.

- [b-exact-vs-local-topic-membership-diff.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-exact-vs-local-topic-membership-diff.json)
  Ten sam diff tylko dla `kat. B`, czyli aktualnego frontu prac.

- [missing-locally-by-category-topic.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/missing-locally-by-category-topic.json)
  Czysta lista braków lokalnych po exact rolloutcie: `kategoria -> temat -> ile sygnatur PJ360 nadal nie ma u nas`.

- [missing-local-inventory-all.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/missing-local-inventory-all.json)
  Pelny inventory `PJ360 -> lokalnie brakuje` po exact rolloutcie: nie tylko liczniki, ale konkretne pytania temat-po-temacie dla wszystkich kategorii `A..T`.

- [ambiguous-local-match-by-category-topic.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/ambiguous-local-match-by-category-topic.json)
  Czysta lista nierozstrzygniętych duplikatów po exact rolloutcie: `kategoria -> temat -> ile przypadków nadal wymaga rozstrzygnięcia w matching layerze`.

- [b-ambiguous-review.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-ambiguous-review.json)
  Szczegolowy review pack tylko dla `kat. B`: pytanie PJ360 + lista lokalnych kandydatow z `question_id`, `external_id`, `current_topic_key` i `main_media_original`.

- [b-import-gap-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-import-gap-shortlist.json)
  Krótka, operacyjna lista realnych brakow importowych dla `kat. B`, z rozdzieleniem od `answer/media mismatch`.

- [b-missing-local-inventory.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-inventory.json)
  Pelna, kanoniczna lista brakujacych lokalnie pytan tylko dla `kat. B`, juz pogrupowana tematami i gotowa do dalszego backlogu importowego.

- [b-missing-local-recovery-candidates.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-missing-local-recovery-candidates.json)
  Rozbicie brakow `kat. B` na trzy grupy wykonawcze: `truly missing`, `recoverable from other category` oraz `already recoverable same category`.

- [b-retopic-candidate-shortlist.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-retopic-candidate-shortlist.json)
  Lista pytan `kat. B`, ktore mamy juz lokalnie, ale PJ360 wskazuje im inny, docelowy temat; to najczystszy backlog do kolejnej paczki `retopic`.

- [b-retopic-true-conflicts.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-retopic-true-conflicts.json)
  Konflikty sygnatur po pierwszej fali `retopic`: PJ360 nadal rozdziela te same lokalne pytania na wiecej niz jeden temat, wiec wymagaja juz review, a nie automatycznego syncu.

- [b-retopic-override-guard-conflicts.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/b-retopic-override-guard-conflicts.json)
  Proby odwrocenia juz aktywnych override `retopic`, swiadomie zatrzymane przez guard, zeby kolejne przebiegi nie oscylowaly miedzy tematami.

- [pj360-b-retopic-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-retopic-package.json)
  Aktualny review-only package `retopic` dla `B`; po pierwszej zsynchronizowanej fali jest juz no-op i sluzy glownie jako pojemnik na `skipped_conflicts` oraz `skipped_existing_override_conflicts`.

- [pj360-b-safe-override-package-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-safe-override-package-spec.json)
  Kanoniczna, wykonywalna specyfikacja pierwszej fali override dla `B`, wykorzystywana przez builder review-only paczek override.

- [pj360-b-wave-1-fallback-cleanup-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-1-fallback-cleanup-spec.json)
  Wykonywalna specyfikacja pierwszej realnie zastosowanej fali `B full closure`: cleanup najbardziej oczywistych przypadkow z `fallback:basic` i `fallback:specialist`.

- [pj360-b-wave-1-fallback-cleanup-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-1-fallback-cleanup-package.json)
  Review-only, ale juz przetestowana paczka override wygenerowana z fali 1 dla `B`; sluzy jako odtwarzalny artefakt `build -> review -> sync`.

- [pj360-b-wave-2-signals-and-signs-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-2-signals-and-signs-spec.json)
  Wykonywalna specyfikacja drugiej fali `B full closure`: sygnaly swietlne, skrzyzowania z sygnalizacja oraz najczystsze pytania znakowe, ktore mozna bezpiecznie wyjac z `special_caution`.

- [pj360-b-wave-2-signals-and-signs-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-2-signals-and-signs-package.json)
  Review-only paczka override dla fali 2 `B`: sygnaly, skrzyzowania z sygnalizacja i curated batch pytan znakowych.

- [pj360-b-wave-3-speed-risk-merge-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-3-speed-risk-merge-spec.json)
  Wykonywalna specyfikacja trzeciej fali `B full closure`: `speed_limits`, `risk_factors_conditions_and_weather` oraz pytania o jazde na suwak dla `lane_change_and_turning`.

- [pj360-b-wave-3-speed-risk-merge-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-3-speed-risk-merge-package.json)
  Review-only paczka override dla fali 3 `B`, juz wykorzystana do realnego sync i dalszego domkniecia delty kategorii `B`.

- [pj360-b-wave-4-vulnerable-and-rescue-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-4-vulnerable-and-rescue-spec.json)
  Wykonywalna specyfikacja czwartej fali `B full closure`: piesi, przystanki komunikacji publicznej, akcje ratunkowe oraz kilka bardzo czystych ruchow dla `road_markings`, `lane_change` i `intersections_with_traffic_lights`.

- [pj360-b-wave-4-vulnerable-and-rescue-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-4-vulnerable-and-rescue-package.json)
  Review-only paczka override dla fali 4 `B`, juz wykorzystana do realnego sync i dalszego domkniecia delty kategorii `B`.

- [pj360-b-wave-5-signalized-and-lane-spec.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-5-signalized-and-lane-spec.json)
  Wykonywalna specyfikacja piątej fali `B full closure`: wejscie w pakiet `skrzyzowania z sygnalizacja` oraz ewidentne przypadki `lane_change`, `overtaking` i `prohibition` wyciagniete z `joining_traffic`.

- [pj360-b-wave-5-signalized-and-lane-package.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides/pj360-b-wave-5-signalized-and-lane-package.json)
  Review-only paczka override dla fali 5 `B`, juz wykorzystana do realnego sync i dalszego zdejmowania nadmiaru z `joining_traffic`.

- [PJ360-OVERRIDE-PACKAGE-SPEC.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-OVERRIDE-PACKAGE-SPEC.md)
  Kanoniczny format spec-driven review package dla override: kiedy uzywamy specyfikacji, jak wyglada schema JSON, jakie sa gwarancje bezpieczenstwa buildera i jak prowadzimy rollout `build -> review -> sync -> delta`.

- [UI-DELTA-PJ360-VS-CURRENT-APP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-DELTA-PJ360-VS-CURRENT-APP.md)
  Konkretna lista tego, czego nadal nie mamy wzgledem PJ360: utility actions, review detail, question detail, global controls, forum/community i inne brakujace warstwy shellu.

- [UI-COVERAGE-PJ360-TESTY-WYKLADY.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-COVERAGE-PJ360-TESTY-WYKLADY.md)
  Osobna macierz pokrycia tylko dla dwoch krytycznych modulow referencyjnych: `testy-na-prawo-jazdy` i `wyklady`, z wynikiem `100/100` liczonym po rodzinach stanow i shelli.

- [UI-COVERAGE-PJ360-KNOWLEDGE-CONTENT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/UI-COVERAGE-PJ360-KNOWLEDGE-CONTENT.md)
  Pokrycie wskazanej warstwy wiedzy/contentu PJ360 z rozbiciem na realne rodziny shelli: `word`, `znaki`, `kodeks`, `aktualnosci`, `punkty-karne` i `longform article`.

- [ZNAKI-DROGOWE-SEO-ETAP-1-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ZNAKI-DROGOWE-SEO-ETAP-1-PLAN.md)
  Kanoniczny plan wdrozenia pierwszego modulu SEO `znaki drogowe` pod obecne repo: decyzje architektoniczne, mapa plikow, checklisty milestone'ow, status board i definicja done dla `Etapu 1`.

- [ZNAKI-DROGOWE-SEO-MILESTONE-5-QA.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ZNAKI-DROGOWE-SEO-MILESTONE-5-QA.md)
  Operacyjny runbook QA dla pierwszego batcha publikacyjnego modulu `znaki drogowe`: seed, URL-e do obchodu, lokalne checki, testy i granica miedzy walidacja lokalna a staging/public URL.

- [ZNAKI-DROGOWE-NAUKA-MODUL-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ZNAKI-DROGOWE-NAUKA-MODUL-PLAN.md)
  Plan osobnego modulu nauki znakow drogowych: granice wzgledem katalogu SEO i `Trenera pamieci`, model nauki, progres, QA, no-go rules oraz sprinty od audytu danych po release MVP.

- [ZNAKI-OSTRZEGAWCZE-INWENTARZ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ZNAKI-OSTRZEGAWCZE-INWENTARZ.md)
  Kanoniczny inwentarz kategorii `A`: komplet `42` znakow ostrzegawczych, stan rolloutow `05-08`, supporting pages i aktualny status quality passu.

- [ZNAKI-ZAKAZU-INWENTARZ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ZNAKI-ZAKAZU-INWENTARZ.md)
  Kanoniczny inwentarz kategorii `B`: komplet `51` znakow zakazu, stan rolloutow `02-04` i wynik domkniecia backlogu.

- [ZNAKI-NAKAZU-INWENTARZ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ZNAKI-NAKAZU-INWENTARZ.md)
  Kanoniczny inwentarz kategorii `C`: komplet `23` znakow nakazu, stan `rollout-09`, supporting pages i brak backlogu inventory.

- [ZNAKI-INFORMACYJNE-INWENTARZ.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ZNAKI-INFORMACYJNE-INWENTARZ.md)
  Kanoniczny inwentarz kategorii `D`: komplet `72` znakow informacyjnych, stan `rollout-10` i powiazane materialy wspierajace.

- [SEO-CONTENT-ROADMAP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SEO-CONTENT-ROADMAP.md)
  Nadrzedna roadmapa dalszych etapow publicznego pionu contentowego po `Etapie 1`: skalowanie klastra znakow, `kodeks drogowy`, `mandaty`, freshness, AI readiness i multilang z wyraznymi bramkami przejscia.

- [LEGAL-TRUST-LAYER-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/LEGAL-TRUST-LAYER-PLAN.md)
  Plan wykonawczy warstwy zaufania `przepisy / kodeks drogowy / podstawy prawne`: model danych dla aktow i jednostek prawnych, relacje z pytaniami i znakami, zasady oficjalnych zrodel, review oraz publiczny blok `Uzasadnienie prawne`.

- [LEGAL-CONTENT-AUTHORING-WORKFLOW.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/LEGAL-CONTENT-AUTHORING-WORKFLOW.md)
  Operacyjny workflow dla kolejnych agentow dodajacych wpisy do `/przepisy`: rejestr juz opublikowanych tematow, miejsca edycji, standard zrodel, szablon tresci, powiazania z pytaniami i komendy QA.

- [SEO-SITEMAP-REPAIR-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/SEO-SITEMAP-REPAIR-PLAN.md)
  Operacyjny plan naprawy sitemap/robots po audycie produkcji: statyczne mapy XML, podzial pytan per kategoria, `robots.txt`, walidacja Google/Bing i definition of done pod techniczne SEO.

- [INDEXNOW-IMPLEMENTATION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/INDEXNOW-IMPLEMENTATION-PLAN.md)
  Plan bezpiecznego wdrozenia IndexNow dla `https://prawkonaraz.pl`: status audytu, decyzje architektoniczne, zakres fazy 1, komendy CLI, testy, rollout produkcyjny i checklisty.

- [AI-CRAWLER-POLICY.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/AI-CRAWLER-POLICY.md)
  Polityka dostepu dla AI crawlerow: public search/retrieval tak, AI training nie, jawne reguly `robots.txt`, `llms.txt`, Cloudflare Managed robots.txt i kontrola po deployu.

- [QUESTION-DATABASE-SEO-MASTERPLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/QUESTION-DATABASE-SEO-MASTERPLAN.md)
  Kanoniczny masterplan rozwoju publicznej bazy pytan egzaminacyjnych: analiza konkurencji, docelowy model stron, roadmapa `30 / 60 / 90 dni` i zasady budowy przewagi SEO ponad same publiczne karty pytan.

- [ENTERPRISE-SCHEMA-STAGE-1-2-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/ENTERPRISE-SCHEMA-STAGE-1-2-PLAN.md)
  Szczegolowy plan refaktoru schema do poziomu enterprise dla etapow 1 + 2: aktualny stan produkcji, docelowy jeden `@graph`, stabilne `@id`, klasy do utworzenia, testy kontraktowe, checklisty i kryteria akceptacji.

- [QUESTION-DATABASE-SEO-POLICY.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/QUESTION-DATABASE-SEO-POLICY.md)
  Operacyjna polityka SEO dla publicznej bazy pytan po `Sprincie 1`: zasady indeksacji, canonicali, sitemap, internal linkingu i baseline schema dla `hub / category / question`.

- [PUBLIC-QUESTION-EXPLANATION-LAYER-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PUBLIC-QUESTION-EXPLANATION-LAYER-PLAN.md)
  Plan wdrozenia osobnej publicznej warstwy `Wyjasnienie` na stronach pytan: architektura danych, fallback do `questions.explanation`, spojnosc z `acceptedAnswer`, workflow redakcyjny i bezpieczne skalowanie po pytaniu `99`.

Na ten moment brak innych osobnych dokumentow wspierajacych typu:

- data retention policy,
- admin/reporting guide.

Te dokumenty warto dodac pozniej, gdy pojawi sie implementacja.

## 4. Aktualna decyzja kanoniczna

Na obecnym etapie przyjmujemy:

- `Laravel` jako glowny framework backendowy,
- `Inertia.js + Vue 3 + TypeScript` jako frontend aplikacyjny,
- `Filament` jako panel admina i backoffice,
- `Laravel` session auth dla web app,
- start od `kategorii B`,
- `1x Mikrus 4.1 PRO` dla aplikacji,
- lokalny `PostgreSQL` na tym samym VPS,
- `Cloudflare R2` dla obrazow, wideo i backupow,
- `prawkonaraz.pl` jako kanoniczna domena produkcyjna,
- `www.prawkonaraz.pl` jako redirect 301 do domeny bez `www`,
- media poza baza i poza lokalnym dyskiem aplikacji,
- preprocessing assetow przed uploadem,
- modularny monolit zamiast mikroserwisow,
- wejscie w B2B dopiero po sygnalach trakcji.

Aktualny stan wykonania tych decyzji i blockerow launchu jest zapisany w [STATUS-MVP.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/STATUS-MVP.md).

Jesli jakis przyszly dokument bedzie przeczyc tym punktom, to wymaga to jawnej decyzji i aktualizacji dokumentacji kanonicznej.

## 5. Standard dokumentowania

Kazdy nowy dokument techniczny powinien:

- miec jasno zdefiniowany cel,
- okreslac scope MVP vs V2,
- opisywac decyzje, a nie tylko pomysly,
- rozdzielac stan obecny od przyszlych opcji,
- wskazywac ograniczenia i ryzyka,
- byc spojny z istniejacymi dokumentami.

## 6. Co jest juz ustalone

Ustalona jest:

- finalna decyzja stackowa,
- strategia storage dla mediow,
- strategia kosztowa MVP,
- droga do V2,
- wysokopoziomowy model bazy,
- wysokopoziomowy model API.

Nieustalone pozostaja jeszcze szczegoly implementacyjne:

- konkretne payloady importu pytan,
- szczegoly implementacji autoryzacji administracyjnej,
- szczegolowa polityka raportow B2B.

## 7. Co dodac pozniej

Po rozpoczeciu implementacji warto dopisac:

1. `DATA-RETENTION.md`
2. `B2B-REPORTING-SPEC.md`
3. `ERROR-HANDLING-STANDARD.md`
4. `ADMIN-OPERATIONS.md`

## 8. Zasada utrzymania porzadku

Jesli decyzja zmienia:

- koszt,
- topologie infrastruktury,
- model danych,
- kontrakt API,
- sposob przechowywania mediow,
- model multi-tenancy,

to zmiana musi trafic do odpowiedniego dokumentu w tym katalogu. Nie trzymamy takich decyzji tylko w rozmowach albo commitach.

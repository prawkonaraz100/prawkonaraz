# OSK V2.12 — Etap 3: reguły prawne i fakty enrollmentu

**Status:** fundament domenowy gotowy lokalnie, bez UI i bez deployu.
**Data weryfikacji:** 2026-08-25.
**Właściwy plan:** [OSK_V2_12_IMPLEMENTATION_PLAN.md](./OSK_V2_12_IMPLEMENTATION_PLAN.md)

## Cel

Etap 3 nie rozstrzyga prawa w kodzie widoku. Utrwala dane, z których można odtworzyć decyzję o wymaganiach szkolenia dla konkretnego enrollmentu OSK:

```text
CourseEnrollment
  -> CourseEnrollmentFacts vN
  -> LegalRequirementsVersion (published)
  -> CourseEnrollmentRequirement vN
  -> CourseEnrollmentRequirementAdjustment[]
```

Jeżeli podane fakty lub reguły nie pozwalają na pewny wynik, system nie tworzy wymagań formalnych. Zamiast tego zapisuje `LegalReviewFlag` z identyfikatorem wersji faktów, reguł i powodem zatrzymania.

## Co powstało

Migracja `2026_08_25_110000_create_osk_legal_requirements_foundation` dodaje wyłącznie nowe tabele:

| Tabela | Rola |
| --- | --- |
| `legal_requirements_versions` | wersjonowane, hashowane reguły oraz źródło |
| `course_enrollment_facts` | append-only wersje faktów kursanta i szkolenia |
| `course_enrollment_requirements` | niezmienny snapshot wyniku wyliczenia |
| `course_enrollment_requirement_adjustments` | osobne, wyjaśnialne korekty wyniku |
| `legal_review_flags` | blokujące sprawy wymagające ręcznej oceny |

Wprowadzone są trzy rozdzielone resolvery:

1. `EnrollmentRequirementsResolver` — ustala wymagania szkolenia dla enrollmentu.
2. `EntitlementEquivalenceResolver` — sprawdza, czy posiadane uprawnienie czyni nowe szkolenie zbędnym.
3. `StateExamEligibilityResolver` — ustala wyłącznie stan gotowości do egzaminu państwowego; nie zastępuje wymagań szkolenia.

## Kontrakt wersji i faktów

`LegalRequirementsVersion` przechodzi przez `DRAFT`, `PUBLISHED`, `ARCHIVED`. Po publikacji nie może zostać zmieniona; zmiana wymaga następnej wersji. Hash obliczany jest z canonical JSON reguł.

`CourseEnrollmentFacts` jest kolejną wersją faktów, nigdy aktualizacją w miejscu. Zawiera m.in. docelową kategorię/uprawnienie, posiadane kategorie i ograniczenia, pozytywne wyniki państwowe, uznane wyniki teorii, dane o szkoleniu równoległym, wcześniejsze kredyty szkoleniowe, kontekst OSK, źródło danych oraz weryfikację przez użytkownika.

Każdy poprawny wynik resolvera utrwala `CourseEnrollmentRequirement` z snapshotem, hashem, `facts_version` i kodem wersji reguł. Zmiana faktów tworzy następny rekord faktów i następny wynik; poprzednie dane pozostają do audytu.

## Bezpieczne zachowanie resolvera

- Nie ma pola typu `exemption = true/false`.
- Korekta wskazuje wymiar, operację, wartość czasu, regułę, priorytet i politykę łączenia.
- Brak reguły bazowej, nieobsługiwany warunek, nieprawidłowa korekta albo niejednoznaczne łączenie korekt tworzy flagę i nie tworzy formalnego wyniku.
- Flaga jest powiązana z konkretną wersją faktów, więc jej późniejsze rozpatrzenie nie miesza się z korektą danych kursanta.
- Reguły nie są odczytywane z Vue, kontrolerów, `StudySession`, `QuestionCollection`, `ProductAccessGrant`, `PurchaseOrder` ani checkoutu B2C.

## Zakres testów

`tests/Feature/Osk/LegalRequirementsTest.php` obejmuje:

1. pełny, niezmienny snapshot wymagań;
2. korektę faktów i kolejną wersję wyniku;
3. zwolnienie z teorii po uznanym uprawnieniu A1 -> A2;
4. redukcję praktyki B1 -> B;
5. równoważność uprawnienia B + C1 + E;
6. blokadę przy niejednoznacznym łączeniu redukcji praktycznych;
7. blokadę przy braku reguły bazowej;
8. odrzucenie kursu, którego deklarowana wersja reguł nie pasuje;
9. niezmienność opublikowanej wersji reguł.

Weryfikacja lokalna: 9 testów / 49 asercji dla Etapu 3, 23 testy / 137 asercji dla Etapów 1–3 oraz 98 istniejących testów / 1399 asercji regresji aplikacji.

## Źródło prawne i granica produkcyjna

Model został przygotowany na podstawie struktury wymagań z [rozporządzenia Ministra Infrastruktury w sprawie szkolenia osób ubiegających się o uprawnienia do kierowania pojazdami, instruktorów i wykładowców](https://api.sejm.gov.pl/eli/acts/DU/2018/1885/text.html). Dokumentacja techniczna nie jest opinią prawną ani zatwierdzoną tabelą wyjątków.

Przed formalnym użyciem na produkcji właściciel compliance musi:

1. zatwierdzić każdą publikowaną wersję reguł i przypisane źródło;
2. sprawdzić kategorie, wyjątki, ograniczenia oraz warunki szkolenia równoległego;
3. ustalić proces obsługi `LegalReviewFlag`;
4. zdecydować o pilocie i feature fladze.

Do tego momentu nie seedujemy produkcyjnych reguł i nie pokazujemy kursantowi ani pracownikowi OSK wyniku jako wiążącej informacji formalnej.

## Następny etap

Etap 4 buduje tylko wersjonowany program teorii: moduły, lekcje i kroki pod `CourseVersion`. Nie wolno podłączać czasu formalnego, dokumentów PAPER, formalnego egzaminu ani UI aktywacji przed jego oddzielną bramą testową.

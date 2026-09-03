# PJ360 Category Target Matrix

## Cel

Ten dokument zamraza kanoniczne targety PJ360 dla tematow, ktore sa teraz aktywnie strojone w klasyfikatorze.

Nie jest to pelny zamiennik dla:

- [pj360-topic-groups.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-category-consistency/pj360-topic-groups.json)

Ten plik pozostaje zrodlem prawdy dla kompletnych `31` tematow per kategoria. Ten dokument sluzy operacyjnie do codziennego strojenia najbardziej problematycznych tematow.

## Zrodla

### Target PJ360

Targety pochodza z:

- [pj360-topic-groups.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-category-consistency/pj360-topic-groups.json)

### Aktualny stan lokalny

Aktualne liczniki po naszej stronie sa liczone na branchu:

- `codex/pj360-category-consistency-audit`

na biezacym, roboczym stanie klasyfikatora w:

- [QuestionTopicClassifier.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/QuestionTopicClassifier.php)

To sa liczniki robocze, nie jeszcze stan zatwierdzony na `main`.

## Tematy pilotowe

Na tym etapie aktywnie stroimy 5 tematow:

- `road_markings`
- `lane_change_and_turning`
- `vehicle_load_and_passenger_safety`
- `safety_equipment_and_restraints`
- `owner_obligations_insurance_documents`

## 1. Road Markings

| Kategoria | PJ360 | Aktualnie | Delta |
| --- | ---: | ---: | ---: |
| `AM` | 92 | 70 | -22 |
| `A1` | 85 | 67 | -18 |
| `A2` | 85 | 67 | -18 |
| `A` | 85 | 67 | -18 |
| `B1` | 88 | 67 | -21 |
| `B` | 113 | 86 | -27 |
| `C1` | 84 | 67 | -17 |
| `C` | 84 | 67 | -17 |
| `D1` | 85 | 67 | -18 |
| `D` | 85 | 67 | -18 |
| `T` | 83 | 67 | -16 |

Wniosek:

- temat jest nadal zanizony we wszystkich kategoriach
- najwiekszy brak zostaje w `B`

## 2. Lane Change And Turning

| Kategoria | PJ360 | Aktualnie | Delta |
| --- | ---: | ---: | ---: |
| `AM` | 133 | 111 | -22 |
| `A1` | 121 | 99 | -22 |
| `A2` | 121 | 99 | -22 |
| `A` | 122 | 99 | -23 |
| `B1` | 121 | 97 | -24 |
| `B` | 148 | 125 | -23 |
| `C1` | 122 | 98 | -24 |
| `C` | 121 | 97 | -24 |
| `D1` | 122 | 99 | -23 |
| `D` | 122 | 99 | -23 |
| `T` | 112 | 91 | -21 |

Wniosek:

- temat jest rownomiernie zanizony prawie wszedzie
- nie jest to juz problem jednej kategorii, tylko wspolnej granicy tematu

## 3. Vehicle Load And Passenger Safety

| Kategoria | PJ360 | Aktualnie | Delta |
| --- | ---: | ---: | ---: |
| `AM` | 22 | 10 | -12 |
| `A1` | 33 | 15 | -18 |
| `A2` | 33 | 15 | -18 |
| `A` | 36 | 15 | -21 |
| `B1` | 29 | 11 | -18 |
| `B` | 88 | 54 | -34 |
| `C1` | 39 | 33 | -6 |
| `C` | 61 | 38 | -23 |
| `D1` | 58 | 30 | -28 |
| `D` | 64 | 30 | -34 |
| `T` | 20 | 15 | -5 |

Wniosek:

- temat poprawil sie po ostatnich iteracjach, ale nadal jest zanizony praktycznie wszedzie
- najwiekszy brak pozostaje w `B` i `D`
- relatywnie najblizej targetu sa `C1` i `T`

## 4. Safety Equipment And Restraints

| Kategoria | PJ360 | Aktualnie | Delta |
| --- | ---: | ---: | ---: |
| `AM` | 2 | 4 | +2 |
| `A1` | 6 | 5 | -1 |
| `A2` | 6 | 5 | -1 |
| `A` | 6 | 5 | -1 |
| `B1` | 8 | 6 | -2 |
| `B` | 22 | 22 | 0 |
| `C1` | 13 | 2 | -11 |
| `C` | 13 | 2 | -11 |
| `D1` | 9 | 6 | -3 |
| `D` | 10 | 8 | -2 |
| `T` | 5 | 1 | -4 |

Wniosek:

- `B` jest juz trafione idealnie
- `AM` jest lekko przeszacowane
- `C1`, `C` i `T` nadal mocno odstaja w dol

## 5. Owner Obligations, Insurance, Documents

| Kategoria | PJ360 | Aktualnie | Delta |
| --- | ---: | ---: | ---: |
| `AM` | 10 | 8 | -2 |
| `A1` | 19 | 11 | -8 |
| `A2` | 20 | 11 | -9 |
| `A` | 31 | 19 | -12 |
| `B1` | 13 | 19 | +6 |
| `B` | 30 | 35 | +5 |
| `C1` | 30 | 18 | -12 |
| `C` | 30 | 24 | -6 |
| `D1` | 37 | 33 | -4 |
| `D` | 38 | 38 | 0 |
| `T` | 32 | 19 | -13 |

Wniosek:

- `D` jest juz trafione idealnie
- `B` i `B1` sa przeszacowane
- pozostale kategorie sa dalej zanizone

## Najwazniejsze obserwacje przekrojowe

1. `road_markings` i `lane_change_and_turning` sa zanizone systemowo, niemal w kazdej kategorii.
2. `vehicle_load_and_passenger_safety` nadal jest za nisko prawie wszedzie, mimo ostatnich poprawek.
3. `safety_equipment_and_restraints` nie jest globalnie zle, ale ma duzy problem w `C1`, `C` i `T`.
4. `owner_obligations_insurance_documents` ma rozjazd mieszany:
   - za wysoko w `B` i `B1`
   - trafione w `D`
   - za nisko gdzie indziej

## Jak uzywac tego dokumentu

Przy kazdej iteracji klasyfikatora:

1. przelicz aktualne liczniki dla tych 5 tematow,
2. porownaj je z ta macierza,
3. zapisz tylko takie zmiany, ktore poprawiaja realna delte do PJ360,
4. nie stroic `B` w oderwaniu od pozostalych kategorii.

## Powiazane dokumenty

- [PJ360-CATEGORY-CONSISTENCY-AUDIT.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-CATEGORY-CONSISTENCY-AUDIT.md)
- [PJ360-CATEGORY-CONSISTENCY-AUDIT-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-CATEGORY-CONSISTENCY-AUDIT-PLAN.md)

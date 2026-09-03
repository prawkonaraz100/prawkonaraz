# Znaki zakazu: kanoniczny inwentarz

## Cel

Ten dokument spina jedną prostą rzecz: chcemy mieć pewność, że w planie i backlogu nie brakuje żadnego oficjalnego znaku zakazu występującego w Polsce.

Nie jest to jeszcze lista stron gotowych do publikacji. To jest lista kontrolna `coverage`, na której opieramy dalsze rollouty i pracę redakcyjną.

## Stan na 2026-04-27

- kanoniczny inwentarz obejmuje `46` znaków zakazu `B-1` do `B-44`,
- uwzględniono aktualne brzmienie `B-19` po zmianie z `2021-03-08`,
- zmiany z `2022-10-14` i `2026-01-14` nie rozszerzały grupy `Znaki zakazu`,
- inwentarz został przełożony do systemu przez `TrafficSignProhibitionInventorySeeder`,
- brakujące znaki zakazu były seedowane jako rekordy `in_review` + backlog w `Mapa zapytań`, a następnie domknięte rolloutami publikacyjnymi,
- każdy brakujący znak dostaje już bazowy pakiet redakcyjny:
  - `intro_definition`
  - `meaning`
  - `placement`
  - `driver_behavior`
  - `legal_summary`
  - `fine_summary`
  - `common_mistakes`
  - minimum `2` FAQ
  - `meta title + meta description`
  - placeholder asset główny i `OG`,
- publicznie opublikowane zostają tylko te znaki, które przejdą pełny pass redakcyjny i źródłowy.
- po rollout-04 publicznie dostępne są już wszystkie `46` znaków zakazu, więc backlog tej kategorii został wyzerowany.

## Lista kanoniczna

1. `B-1` Zakaz ruchu w obu kierunkach
2. `B-2` Zakaz wjazdu
3. `B-3` Zakaz wjazdu pojazdów silnikowych, z wyjątkiem motocykli jednośladowych
4. `B-3a` Zakaz wjazdu autobusów
5. `B-4` Zakaz wjazdu motocykli
6. `B-5` Zakaz wjazdu samochodów ciężarowych
7. `B-6` Zakaz wjazdu ciągników rolniczych
8. `B-7` Zakaz wjazdu pojazdów silnikowych z przyczepą
9. `B-8` Zakaz wjazdu pojazdów zaprzęgowych
10. `B-9` Zakaz wjazdu rowerów
11. `B-10` Zakaz wjazdu motorowerów
12. `B-11` Zakaz wjazdu wózków rowerowych
13. `B-12` Zakaz wjazdu wózków ręcznych
14. `B-13` Zakaz wjazdu pojazdów z materiałami wybuchowymi lub łatwo zapalnymi
15. `B-13a` Zakaz wjazdu pojazdów z materiałami niebezpiecznymi
16. `B-14` Zakaz wjazdu pojazdów z materiałami, które mogą skazić wodę
17. `B-15` Zakaz wjazdu pojazdów o szerokości ponad `... m`
18. `B-16` Zakaz wjazdu pojazdów o wysokości ponad `... m`
19. `B-17` Zakaz wjazdu pojazdów o długości ponad `... m`
20. `B-18` Zakaz wjazdu pojazdów o rzeczywistej masie całkowitej ponad `... t`
21. `B-19` Zakaz wjazdu pojazdów o nacisku pojedynczej osi napędowej powyżej `... t`
22. `B-20` STOP
23. `B-21` Zakaz skręcania w lewo
24. `B-22` Zakaz skręcania w prawo
25. `B-23` Zakaz zawracania
26. `B-24` Koniec zakazu zawracania
27. `B-25` Zakaz wyprzedzania
28. `B-26` Zakaz wyprzedzania przez samochody ciężarowe
29. `B-27` Koniec zakazu wyprzedzania
30. `B-28` Koniec zakazu wyprzedzania przez samochody ciężarowe
31. `B-29` Zakaz używania sygnałów dźwiękowych
32. `B-30` Koniec zakazu używania sygnałów dźwiękowych
33. `B-31` Pierwszeństwo dla nadjeżdżających z przeciwka
34. `B-32` Stój - kontrola celna
35. `B-33` Ograniczenie prędkości
36. `B-34` Koniec ograniczenia prędkości
37. `B-35` Zakaz postoju
38. `B-36` Zakaz zatrzymywania się
39. `B-37` Zakaz postoju w dni nieparzyste
40. `B-38` Zakaz postoju w dni parzyste
41. `B-39` Strefa ograniczonego postoju
42. `B-40` Koniec strefy ograniczonego postoju
43. `B-41` Zakaz ruchu pieszych
44. `B-42` Koniec zakazów
45. `B-43` Strefa ograniczonej prędkości
46. `B-44` Koniec strefy ograniczonej prędkości

## Jak tego używamy

- strony już opublikowane zostają w swoim normalnym rollout flow,
- brakujące pozycje trafiały do systemu jako rekordy `TrafficSign` w statusie `in_review`,
- dla brakujących pozycji tworzyliśmy też wpisy w `Mapa zapytań` z batch label `rollout-02-prohibitions`,
- rekordy z tego batcha nie były publikowane automatycznie,
- publikujemy je dopiero wtedy, gdy przejdą pełną checklistę redakcyjną i źródłową.

## Status wdrożenia

- `46/46` oficjalnych znaków zakazu ma już publiczne strony,
- kategoria `Znaki zakazu` nie ma już brakujących rekordów w statusie `in_review`,
- dalsza praca przenosi się z pokrycia katalogu na rewizje jakości, kolejne materiały wspierające i monitoring realnych zapytań.

## Źródła oficjalne

- [ELI: Rozporządzenie z 31 lipca 2002 r. - tekst ogłoszony, spis znaków](https://eli.gov.pl/eli/DU/2002/1393/ogl/pol/pdf)
- [ELI: Rozporządzenie z 8 marca 2021 r. - zmiana brzmienia `B-19`](https://eli.gov.pl/api/acts/DU/2021/433/text.html)
- [ELI: Rozporządzenie z 14 października 2022 r. - brak rozszerzenia grupy `Znaki zakazu`](https://eli.gov.pl/eli/DU/2022/2372/ogl/pol)
- [ELI: Rozporządzenie z 14 stycznia 2026 r. - brak rozszerzenia grupy `Znaki zakazu`](https://eli.gov.pl/eli/DU/2026/133/ogl)

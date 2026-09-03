# Znaki ostrzegawcze: kanoniczny inwentarz

## Cel

Ten dokument przygotowuje start kolejnej kategorii po domknieciu `Znaki zakazu`.

Chcemy miec od razu jasnosc:

- ile oficjalnych znakow ostrzegawczych musimy pokryc,
- jakie sa ich kanoniczne nazwy i kody,
- od jakiego oficjalnego wykazu zaczynamy dalsze rollouty.

## Stan na 2026-04-28

- kanoniczny inwentarz obejmuje `42` oficjalne znaki ostrzegawcze od `A-1` do `A-34`,
- lista jest oparta o urzedowy `Spis wzorow znakow i sygnalow drogowych`,
- zmiana techniczna z `2026-01-14` modyfikowala zasady stosowania wybranych znakow ostrzegawczych, ale nie rozszerzyla samego katalogu kodow,
- do kodu projektu zostal juz dodany kanoniczny katalog:
  - [PolishWarningSignCatalog.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/PolishWarningSignCatalog.php)
- do modulu doszedl tez produkcyjny szkielet backlogu:
  - [PolishWarningSignContentBuilder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/PolishWarningSignContentBuilder.php)
  - [TrafficSignWarningInventorySeeder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/database/seeders/TrafficSignWarningInventorySeeder.php)
- po seedzie lokalnym warning cluster jest juz domkniety publicznie:
  - `42` rekordy `Znaki ostrzegawcze` w bazie
  - `42` rekordy publiczne
  - `0` rekordow `in_review` w backlogu tej kategorii
  - quality pass dla `42/42` warning signs jest domkniety:
    - kazdy znak ma juz osobny asset `WebP 1200x1200` pod kanonicznym slugiem, zamiast wspolnego `warning-generic.svg`
    - `image_path` i `og_image_path` wskazuja ten sam produkcyjny plik znaku, wiec `OG` i `Article.image` sa juz zgrane z jednym URL-em assetu
    - publiczny layout emituje `max-image-preview:large`, zeby Google mial pelny sygnal do duzego podgladu obrazu
    - strony kategorii warning signs renderuja tez sekcje `Powiazane porownania`, wzmacniajaca linkowanie do materialow wspierajacych
  - `13` opublikowanych materialow wspierajacych dla warning rolloutow `05-08`:
    - `/znaki-drogowe/porownania/a-1-do-a-4`
    - `/znaki-drogowe/porownania/a-5-do-a-8`
    - `/znaki-drogowe/porownania/a-13-a-19-a-21`
    - `/znaki-drogowe/porownania/a-6d-vs-a-6e`
    - `/znaki-drogowe/porownania/a-9-vs-a-10`
    - `/znaki-drogowe/porownania/a-11-do-a-12c`
    - `/znaki-drogowe/porownania/a-14-a-15-a-20`
    - `/znaki-drogowe/porownania/a-16-vs-a-17-vs-a-24`
    - `/znaki-drogowe/porownania/a-18a-vs-a-18b`
    - `/znaki-drogowe/porownania/a-22-vs-a-23`
    - `/znaki-drogowe/porownania/a-25-do-a-28`
    - `/znaki-drogowe/porownania/a-29-vs-a-30`
    - `/znaki-drogowe/porownania/a-31-do-a-34`

## Lista kanoniczna

1. `A-1` Niebezpieczny zakret w prawo
2. `A-2` Niebezpieczny zakret w lewo
3. `A-3` Niebezpieczne zakrety - pierwszy w prawo
4. `A-4` Niebezpieczne zakrety - pierwszy w lewo
5. `A-5` Skrzyzowanie drog
6. `A-6a` Skrzyzowanie z droga podporzadkowana wystepujaca po obu stronach
7. `A-6b` Skrzyzowanie z droga podporzadkowana wystepujaca po prawej stronie
8. `A-6c` Skrzyzowanie z droga podporzadkowana wystepujaca po lewej stronie
9. `A-6d` Wlot drogi jednokierunkowej z prawej strony
10. `A-6e` Wlot drogi jednokierunkowej z lewej strony
11. `A-7` Ustap pierwszenstwa
12. `A-8` Skrzyzowanie o ruchu okreznym
13. `A-9` Przejazd kolejowy z zaporami
14. `A-10` Przejazd kolejowy bez zapor
15. `A-11` Nierowna droga
16. `A-11a` Prog zwalniajacy
17. `A-12a` Zwezenie jezdni - dwustronne
18. `A-12b` Zwezenie jezdni - prawostronne
19. `A-12c` Zwezenie jezdni - lewostronne
20. `A-13` Ruchomy most
21. `A-14` Roboty na drodze
22. `A-15` Sliska jezdnia
23. `A-16` Przejscie dla pieszych
24. `A-17` Dzieci
25. `A-18a` Zwierzeta gospodarskie
26. `A-18b` Zwierzeta dzikie
27. `A-19` Boczny wiatr
28. `A-20` Odcinek jezdni o ruchu dwukierunkowym
29. `A-21` Tramwaj
30. `A-22` Niebezpieczny zjazd
31. `A-23` Stromy podjazd
32. `A-24` Rowerzysci
33. `A-25` Spadajace odlamki skalne
34. `A-26` Lotnisko
35. `A-27` Nabrzeze lub brzeg rzeki
36. `A-28` Sypki zwir
37. `A-29` Sygnaly swietlne
38. `A-30` Inne niebezpieczenstwo
39. `A-31` Niebezpieczne pobocze
40. `A-32` Oszronienie jezdni
41. `A-33` Zator drogowy
42. `A-34` Wypadek drogowy

## Co dalej

Najblizszy sensowny krok dla tej kategorii:

1. utrzymac quality pass calego warning cluster `42/42` jako nowy standard dla kolejnych kategorii,
2. przejsc stopniowo z placeholder assetow na docelowe, finalne grafiki tam, gdzie beda juz gotowe lepsze materialy wizualne,
3. dopracowac kolejne strony wspierajace na podstawie realnego ruchu i query mapy.

W praktyce ten etap jest juz gotowy od strony systemowej:

- oficjalny katalog jest domkniety,
- publiczny rollout warning signs jest kompletny: `42/42`,
- warning assets dzialaja juz jako finalne `WebP 1200x1200` per znak, a nie jako wspolny warning placeholder,
- test kompletności seedera pilnuje, zeby zaden kod `A-*` nie wypadl z systemu.
- publiczne rollouty warning signs sa juz cztery i obejmuja lacznie `42` stron znakow oraz `13` supporting pages.

## Zrodla oficjalne

- [ELI: Rozporzadzenie z 31 lipca 2002 r. - spis wzorow znakow i sygnalow drogowych](https://eli.gov.pl/api/acts/DU/2002/1393/text/O/D20021393.pdf)
- [ELI: Rozporzadzenie z 14 stycznia 2026 r. - zmiany techniczne dla znakow ostrzegawczych](https://eli.gov.pl/eli/DU/2026/132/ogl/pol/pdf)

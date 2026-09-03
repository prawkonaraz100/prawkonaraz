# Znaki informacyjne: kanoniczny inwentarz

## Cel

Ten dokument domyka dokumentacyjnie rodzine `D`, czyli `Znaki informacyjne`.

Chcemy miec jasnosc:

- ile znakow `D` obejmuje aktualny katalog wdrozeniowy,
- czy to nadal backlog, czy juz publiczny cluster,
- ktore supporting pages spinaja najwazniejsze mini-intencje w tej rodzinie.

## Stan na `2026-06-23`

- kanoniczny inwentarz obejmuje `73` znaki informacyjne z rodziny `D`,
- katalog jest zaimplementowany w:
  - [PolishInformationalSignCatalog.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/PolishInformationalSignCatalog.php)
  - [PolishInformationalSignContentBuilder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/PolishInformationalSignContentBuilder.php)
  - [TrafficSignSeoSeeder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/database/seeders/TrafficSignSeoSeeder.php)
- `rollout-10-informational` jest publiczny:
  - `73/73` znakow z katalogu `D` ma strony publiczne w glownym seederze,
  - query map dla rodziny `D` jest oznaczona jako `published`,
  - assets `main + OG` sa utrzymywane jako docelowe pliki `WebP 1200x1200`,
  - category page i sign pages sa juz podlaczone do supporting pages.
- klaster ma juz `4` supporting pages:
  - `/znaki-drogowe/porownania/d-1-vs-d-2`
  - `/znaki-drogowe/porownania/d-4a-vs-d-4b`
  - `/znaki-drogowe/porownania/d-6-vs-d-6a`
  - `/znaki-drogowe/porownania/d-18-d-23-d-28-d-34`

## Lista kanoniczna

1. `D-1` Droga z pierwszenstwem
2. `D-2` Koniec drogi z pierwszenstwem
3. `D-3` Droga jednokierunkowa
4. `D-4a` Droga bez przejazdu
5. `D-4b` Wjazd na droge bez przejazdu
6. `D-4c` Wjazd na droge bez przejazdu z lewej strony
7. `D-5` Pierwszenstwo na zwezonym odcinku jezdni
8. `D-6` Przejscie dla pieszych
9. `D-6a` Przejazd dla rowerzystow
10. `D-6b` Przejscie dla pieszych i przejazd dla rowerow
11. `D-7` Droga ekspresowa
12. `D-8` Koniec drogi ekspresowej
13. `D-9` Autostrada
14. `D-10` Koniec autostrady
15. `D-11` Poczatek pasa ruchu dla autobusow
16. `D-12` Pas ruchu dla autobusow
17. `D-13a` Poczatek pasa ruchu
18. `D-14` Koniec pasa ruchu
19. `D-17` Przystanek tramwajowy
20. `D-18` Parking
21. `D-18a` Parking - miejsce zastrzezone
22. `D-18b` Parking zadaszony
23. `D-19` Postoj taksowek
24. `D-20` Koniec postoju taksowek
25. `D-21` Szpital
26. `D-21a` Policja
27. `D-22` Punkt opatrunkowy
28. `D-23` Stacja paliwowa
29. `D-23a` Stacja paliwowa z gazem do napedu pojazdow
30. `D-23b` Stacja paliwowa z punktem ladowania pojazdow elektrycznych
31. `D-23c` Punkt ladowania pojazdow elektrycznych
32. `D-24` Telefon
33. `D-25` Poczta
34. `D-26` Stacja obslugi technicznej
35. `D-26a` Wulkanizacja
36. `D-26b` Myjnia
37. `D-26c` Toaleta publiczna
38. `D-26d` Natrysk
39. `D-27` Bufet lub kawiarnia
40. `D-28` Restauracja
41. `D-29` Hotel lub motel
42. `D-30` Obozowisko (kemping)
43. `D-31` Obozowisko (kemping) z podlaczeniami elektrycznymi do przyczep
44. `D-32` Pole biwakowe
45. `D-33` Schronisko mlodziezowe
46. `D-34` Punkt informacji turystycznej
47. `D-34a` Informacja radiowa o ruchu drogowym
48. `D-34b` Zbiorcza tablica informacyjna
49. `D-35` Przejscie podziemne dla pieszych
50. `D-35a` Schody ruchome w dol
51. `D-36` Przejscie nadziemne dla pieszych
52. `D-36a` Schody ruchome w gore
53. `D-37` Tunel
54. `D-38` Koniec tunelu
55. `D-40` Strefa zamieszkania
56. `D-41` Koniec strefy zamieszkania
57. `D-42` Obszar zabudowany
58. `D-43` Koniec obszaru zabudowanego
59. `D-44` Strefa platnego parkowania
60. `D-45` Koniec strefy platnego parkowania
61. `D-46` Droga wewnetrzna
62. `D-47` Koniec drogi wewnetrznej
63. `D-48` Zmiana pierwszenstwa na skrzyzowaniu rownorzednym
64. `D-48a` Zmiana pierwszenstwa
65. `D-49` Pobor oplat
66. `D-50` Zatoka
67. `D-51` Automatyczna kontrola predkosci
68. `D-51a` Automatyczna kontrola sredniej predkosci
69. `D-51b` Koniec automatycznej kontroli sredniej predkosci
70. `D-52` Strefa ruchu
71. `D-53` Koniec strefy ruchu
72. `D-54` Strefa czystego transportu
73. `D-55` Koniec strefy czystego transportu

## Jak tego uzywamy

- rodzina `D` jest juz publicznym clustrem, wiec dokument sluzy bardziej do utrzymania coverage niz do pilnowania pustego backlogu,
- query map i supporting pages porzadkuja najsilniejsze mini-intencje w tej rodzinie:
  - `D-1` vs `D-2`,
  - `D-4a` vs `D-4b`,
  - `D-6` vs `D-6a`,
  - signs uslugowe `D-18 / D-23 / D-28 / D-34`,
- kolejne prace w tej kategorii powinny isc glownie w:
  - dalsze porownania i relacje do innych rodzin znakow,
  - quality pass tresci,
  - examples / scenario writing dla ruchu miejskiego, tras i uslug drogowych.

## Status wdrozenia

- `73/73` znakow z katalogu `D` ma juz publiczne strony w glownym seederze,
- `4` supporting pages spina najmocniejsze mini-klastry porownawcze i uslugowe,
- klaster quality test pilnuje, zeby liczba opublikowanych znakow zgadzala sie z pelnym katalogiem `PolishInformationalSignCatalog`.

## Zrodla oficjalne

- [ELI: Rozporzadzenie z 31 lipca 2002 r. - spis wzorow znakow i sygnalow drogowych](https://eli.gov.pl/api/acts/DU/2002/1393/text/O/D20021393.pdf)

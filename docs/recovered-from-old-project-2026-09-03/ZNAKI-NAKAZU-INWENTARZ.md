# Znaki nakazu: kanoniczny inwentarz

## Cel

Ten dokument spina kategorie `Znaki nakazu` tak samo, jak osobne inwentarze spinaja juz `Znaki zakazu` i `Znaki ostrzegawcze`.

Chcemy miec od razu jasnosc:

- ile oficjalnych znakow nakazu musimy pokryc,
- jakie sa ich kanoniczne kody i nazwy,
- czy rollout kategorii `C` jest juz tylko szkicem, czy pelnym publicznym clustrem.

## Stan na `2026-04-29`

- kanoniczny inwentarz obejmuje `23` oficjalne znaki nakazu z rodziny `C`,
- katalog jest zaimplementowany w:
  - [PolishMandatorySignCatalog.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/PolishMandatorySignCatalog.php)
  - [PolishMandatorySignContentBuilder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/app/Support/PolishMandatorySignContentBuilder.php)
  - [TrafficSignMandatoryInventorySeeder.php](C:/Users/xxx/Desktop/serwistestyprawojazdy/database/seeders/TrafficSignMandatoryInventorySeeder.php)
- `rollout-09-mandatory` jest domkniety publicznie:
  - `23/23` znakow kategorii `C` ma publiczne strony,
  - backlog `rollout-09-mandatory-inventory` jest wyzerowany,
  - category page, sign pages i query map dla rodziny `C` sa oznaczone jako `published`,
  - assets `main + OG` sa trzymane jako docelowe pliki `WebP 1200x1200`.
- klaster ma juz `6` supporting pages:
  - `/znaki-drogowe/porownania/c-1-do-c-4`
  - `/znaki-drogowe/porownania/c-5-do-c-8`
  - `/znaki-drogowe/porownania/c-9-do-c-11`
  - `/znaki-drogowe/porownania/c-13-do-c-16a`
  - `/znaki-drogowe/porownania/c-14-vs-c-15`
  - `/znaki-drogowe/porownania/c-18-vs-c-19`

## Lista kanoniczna

1. `C-1` Nakaz jazdy w prawo
2. `C-2` Nakaz skrecania w prawo
3. `C-3` Nakaz jazdy w lewo
4. `C-4` Nakaz skrecania w lewo
5. `C-5` Nakaz jazdy prosto
6. `C-6` Nakaz jazdy prosto lub w prawo
7. `C-7` Nakaz jazdy prosto lub w lewo
8. `C-8` Nakaz jazdy w lewo lub w prawo
9. `C-9` Nakaz objazdu przeszkody z prawej strony
10. `C-10` Nakaz objazdu przeszkody z lewej strony
11. `C-11` Nakaz objazdu przeszkody z obu stron
12. `C-12` Ruch okrezny
13. `C-13` Droga dla rowerow
14. `C-13a` Koniec drogi dla rowerow
15. `C-13/16` Droga dla rowerow i pieszych
16. `C-13a/16a` Koniec drogi dla rowerow i pieszych
17. `C-14` Predkosc minimalna `40 km/h`
18. `C-15` Koniec predkosci minimalnej
19. `C-16` Droga dla pieszych
20. `C-16a` Koniec drogi dla pieszych
21. `C-17` Nakazany kierunek dla pojazdow z materialami niebezpiecznymi
22. `C-18` Nakaz uzywania lancuchow przeciwslizgowych
23. `C-19` Koniec nakazu uzywania lancuchow przeciwslizgowych

## Jak tego uzywamy

- kategoria `Znaki nakazu` jest juz publicznie kompletna, wiec ten dokument nie sluzy do pilnowania brakow inventory,
- dokument sluzy glownie do:
  - rewizji jakosci contentu,
  - utrzymania supporting pages,
  - pilnowania, zeby zaden znak `C-*` nie wypadl z publicznego corpusu przy kolejnych zmianach seedera albo query mapy,
- kolejne prace w tej rodzinie powinny isc bardziej w:
  - dopracowanie porownan,
  - examples / scenario writing,
  - relacje do oznakowania poziomego i sygnalizacji.

## Status wdrozenia

- `23/23` oficjalnych znakow nakazu ma juz publiczne strony,
- `6` supporting pages spina glowne mini-klastry porownawcze kategorii `C`,
- osobny test inwentarza i osobny test cluster quality pilnuja, zeby rodzina `C` pozostawala kompletna i publiczna.

## Zrodla oficjalne

- [ELI: Rozporzadzenie z 31 lipca 2002 r. - spis wzorow znakow i sygnalow drogowych](https://eli.gov.pl/api/acts/DU/2002/1393/text/O/D20021393.pdf)

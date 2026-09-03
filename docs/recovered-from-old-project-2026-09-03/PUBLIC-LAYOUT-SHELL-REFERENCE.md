# Public Layout Shell Reference

Status: wdrozone lokalnie na branchu `codex/unify-public-menu`
Data: 2026-06-04

## Cel

Publiczne strony marketingowe i SEO maja trzymac wspolna os wzgledem headera.
Wczesniej kluczowe widoki uzywaly roznych szerokosci:

- header: `1440px`,
- strona glowna: ok. `1936px`,
- znaki drogowe: `1780px`,
- oficjalna baza pytan: `1780px`,
- przepisy: `1200px`.

To powodowalo wrazenie, ze menu i strona maja inny rytm na kazdym widoku.

## Standard

Domyslny publiczny shell:

- maksymalna szerokosc: `1440px`,
- padding boczny: `16px` mobile, `24px` od `640px`, `32px` od `1280px`,
- klasy: `.site-shell` dla nowych/niestandardowych sekcji i `.content-shell` dla widokow opartych o `layouts.public-content`.

Tla sekcji moga nadal isc na pelna szerokosc strony. Wspolna szerokosc dotyczy tresci wewnatrz sekcji.

## Zakres aktualnej zmiany

Ujednolicone widoki:

- `/`,
- `/przepisy`,
- `/znaki-drogowe`,
- `/znaki-drogowe/kategorie/...`,
- `/oficjalna-baza-pytan-na-prawo-jazdy`,
- `/oficjalna-baza-pytan-na-prawo-jazdy/...`,
- `/pytanie/...`,
- `/autorzy/...`.

Nie ruszamy tym mechanizmem:

- playera pytan,
- modulu nauki,
- egzaminu,
- widokow aplikacyjnych po zalogowaniu.

## Kiedy mozna uzyc szerszego wariantu

Szerszy shell moze miec sens tylko dla widokow katalogowych z bardzo gesta siatka danych albo tabelami, ale powinien byc nazwany i opisany jako wariant, a nie wpisany ad hoc jako `max-w-[1780px]`.

Domyslnie nowe publiczne strony powinny zaczynac od `.site-shell` albo `.content-shell`.

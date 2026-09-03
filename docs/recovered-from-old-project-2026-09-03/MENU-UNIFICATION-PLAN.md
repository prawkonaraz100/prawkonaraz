# Plan ujednolicenia wygladu menu

Status: wdrozone lokalnie, do review przed commitem/deployem  
Branch: `codex/unify-public-menu`  
Decyzja produktowa: docelowym wzorcem wygladu jest kompaktowy desktopowy header z `/nauka`.

## Cel

Ujednolicic wyglad menu na stronach Vue/Inertia i Blade/publicznych tak, aby uzytkownik widzial jeden spojny system nawigacji w calym serwisie.

Zmiana dotyczy wygladu i ukladu menu. Nie zmieniamy zasad dostepu, routingu, logoutu, auth drawerow, wyszukiwarki ani logiki przekierowan.

## Docelowy desktopowy układ primary menu

Kolejnosc linkow:

1. Strona glowna -> `/`
2. Aktualnosci -> `/aktualnosci`
3. Nauka -> `navigation.learning_href`
4. Pytania -> `/oficjalna-baza-pytan-na-prawo-jazdy`
5. Znaki drogowe -> `/znaki-drogowe`
6. Przepisy -> `/przepisy`
7. Testy online -> `/testy-na-prawo-jazdy`
8. Rankingi -> `/nauka/ranking`
9. Cennik -> `/cennik`

Zasada dla `Nauka`: link moze byc widoczny dla zalogowanego uzytkownika bez aktywnego planu. To jest swiadome CTA. Backend nadal decyduje, czy pokazac panel nauki, czy przekierowac na `/aktywuj-dostep`.

## Zakres implementacji

### Vue/Inertia

Plik: `resources/js/Components/SiteHeader.vue`

- przeniesc kompaktowy wariant z `learningPanel` na standardowy desktop header,
- ustawic jeden kanoniczny zestaw `desktopPrimaryLinks`,
- usunac roznice wizualne, ktore sprawiaja, ze `/nauka` wyglada jak osobna aplikacja,
- zachowac dzialanie login/register drawerow,
- zachowac `POST /logout` z CSRF,
- zachowac mobile menu bez ryzykownej przebudowy.

### Blade/publiczne strony SEO

Plik: `resources/views/components/site/public-header.blade.php`

- usunac desktopowy utility strip,
- zastosowac kompaktowa wysokosc i szerokosc z wariantu `/nauka`,
- zbudowac primary menu w tej samej kolejnosc co Vue,
- zachowac dotychczasowe mobile menu,
- zachowac dropdown konta i `POST /logout`.

### Dokumentacja referencyjna

Plik: `docs/MENU-SYSTEM-REFERENCE.md`

- opisac jeden kanoniczny desktopowy header,
- usunac opis `learningPanel` jako osobnego wygladu menu,
- zostawic informacje, ze `/nauka` moze miec szerszy shell strony, ale nie osobna liste menu.

## Czego nie ruszamy

- `ProductAccessResolver`,
- `PjmFreeAccessResolver`,
- middleware dostepu do produktu,
- definicje tras,
- auth drawer flow,
- `documentNavigationPrefixes`,
- publiczne adresy SEO,
- zachowanie `/nauka`, `/nauka/teraz`, `/nauka/ranking` po stronie backendu.

## Weryfikacja

Minimalny zestaw:

- `npm run build`,
- `git diff --check`,
- lokalny sanity check publicznej strony jako gosc,
- lokalny sanity check `/nauka` jesli dostepna jest aktywna sesja,
- sprawdzenie, czy mobile menu nadal sie otwiera i zawiera linki.

## Ryzyka

1. Dwa renderery menu (`Vue` i `Blade`) moga rozejsc sie wizualnie, jesli zmienimy tylko jeden.
2. Link `Rankingi` prowadzi do prywatnej trasy. To jest akceptowalne, bo backend pilnuje dostepu, ale nalezy obserwowac UX goscia.
3. Publiczne strony traca desktopowy utility strip. Linki utility nadal zostaja w mobile i footerze, a `Pomoc` zostaje w gornym rzedzie desktopowego headera.
4. Kompaktowy header moze wymusic korekty szerokosci na nietypowych breakpointach, szczegolnie ok. `1024-1280px`.

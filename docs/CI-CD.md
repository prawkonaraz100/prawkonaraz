# CI/CD

## 1. Cel dokumentu

Ten dokument opisuje aktualny standard CI/CD dla MVP.

Jego celem jest:

- zautomatyzowac podstawowa walidacje zmian,
- wymusic staly minimalny standard jakosci przed deployem,
- ograniczyc ryzyko sytuacji, w ktorej kod przechodzi lokalnie, ale nie przechodzi na czystym srodowisku,
- przygotowac grunt pod pozniejszy bardziej dojrzaly deploy na VPS.

## 2. Zakres

Obecny dokument obejmuje:

- CI na GitHub Actions,
- bootstrap aplikacji na czystym runnerze,
- seed danych smoke,
- smoke test krytycznego flow,
- backend test suite,
- style check,
- build frontendowy.

Nie obejmuje jeszcze:

- automatycznego deployu na produkcje,
- blue/green deployu,
- canary rollout,
- automatycznych migracji na produkcji po merge,
- automatycznego rollbacku.

## 3. Aktualny stan MVP

Aktualny workflow CI znajduje sie w:

- `.github/workflows/ci.yml`
- `.github/workflows/browser-smoke.yml`

Pipeline uruchamia sie dla:

- `push`,
- `pull_request`,
- `workflow_dispatch`.

Osobny workflow browser smoke uruchamia sie przez:

- `workflow_dispatch`.

## 4. Kanoniczny przebieg CI

Workflow wykonuje nastepujace kroki:

1. checkout repozytorium,
2. setup `PHP 8.3`,
3. setup `Node.js 22`,
4. instalacja zaleznosci Composer i npm,
5. bootstrap `.env` i testowe `SQLite`,
6. migracje bazy,
7. `php artisan ops:seed-smoke-data`,
8. `php artisan ops:smoke-test --require-media`,
9. `php artisan test`,
10. `php vendor/bin/pint --test`,
11. `npm run build`.

To znaczy, ze pipeline waliduje nie tylko kod i testy, ale tez minimalna gotowosc aplikacji do dzialania na czystym srodowisku.

CI celowo zostaje przy `SQLite`, bo daje najszybszy i najlzejszy feedback dla testow.
Docelowy deploy produkcyjny jest przygotowywany pod `PostgreSQL`, ale nie chcemy, zeby sama zmiana targetu infrastruktury spowolnila codzienny pipeline developerski.

Dodatkowo projekt ma lokalny/manualny browser smoke:

```bash
npm run e2e:smoke
```

Ta komenda jest tez podpieta pod osobny workflow GitHub Actions `Browser Smoke`, uruchamiany recznie. Sluzy jako dodatkowa bramka przed deployem i do walidacji krytycznego flow w prawdziwej przegladarce wraz z artefaktami `output/playwright`.

Lokalnie mozna skierowac ten sam test na kontener `web` i sprawdzic rowniez naglowki Nginx dla PWA:

```powershell
$env:DB_HOST = '127.0.0.1'
$env:E2E_SMOKE_BASE_URL = 'http://localhost:8000'
$env:E2E_SMOKE_EXPECT_PWA_CACHE_HEADERS = 'true'
npm run e2e:smoke
```

Tryb z `E2E_SMOKE_BASE_URL` nie uruchamia pomocniczego `artisan serve`; najpierw buduje aktualny frontend, potem uzywa wskazanego wdrozeniowego web servera. Flaga `E2E_SMOKE_EXPECT_PWA_CACHE_HEADERS` wymaga `Cache-Control: no-store` dla `service-worker.js`.

## 5. Deterministyczny dataset smoke

CI opiera sie na dedykowanej komendzie:

```bash
php artisan ops:seed-smoke-data
```

Jej zadaniem jest:

- utworzyc aktywna kategorie `B`,
- utworzyc deterministyczne pytanie z obrazem,
- utworzyc deterministyczne pytanie z wideo,
- zapisac placeholder assets na skonfigurowanym publicznym dysku mediow,
- zapewnic, ze `ops:smoke-test --require-media` ma na czym pracowac.

Wlasciwosci:

- komenda jest idempotentna,
- nie tworzy duplikatow przy kolejnym uruchomieniu,
- sluzy do walidacji serwerowego flow, nie do demo-content dla uzytkownikow.

## 6. Rola smoke testu w pipeline

Smoke test uruchamiany w CI nie zastepuje pelnych testow, ale lapie awarie integracyjne w miejscach, gdzie zwykly unit albo contract test nie wystarcza.

Smoke ma potwierdzic:

- health endpoint dziala,
- publiczne kategorie sa widoczne,
- sesja `learn` daje sie utworzyc i zamknac,
- dashboard reaguje na zakonczona sesje,
- obraz i wideo istnieja na storage i maja rozwiazywalny URL.

Kanoniczna komenda:

```bash
php artisan ops:smoke-test --require-media
```

## 7. Standard merge i deploy

Minimalny standard MVP:

1. workflow CI musi byc zielony,
2. smoke test musi byc zielony,
3. backend tests musza byc zielone,
4. `pint` musi byc zielony,
5. frontend build musi byc zielony,
6. przed manualnym deployem powinien przejsc `npm run e2e:smoke`.

Dopiero po tym zmiana powinna byc uznana za gotowa do deployu.

## 7.1. Browser smoke workflow

Osobny workflow:

- instaluje `Chromium`,
- przygotowuje lokalne `.env` i testowe `SQLite`,
- uruchamia `npm run e2e:smoke`,
- publikuje screenshot i raport JSON jako artifact.

W workflow test obejmuje manifest, aktywny service worker, offline fallback, brak prywatnych wpisow w Cache Storage oraz mobile matrix 360/390/430 px. Kontrola konkretnych naglowkow Nginx jest wykonywana w lokalnym/stagingowym trybie z `E2E_SMOKE_BASE_URL`.

To daje lekki, ale bardzo praktyczny most miedzy zwyklym CI a pelnym testowaniem przed wdrozeniem.

## 8. Aktualne granice MVP

Na obecnym etapie CI jest automatyczne, ale CD nadal pozostaje kontrolowane manualnie.

To jest celowe, bo przy:

- `1x budzetowy serwer`,
- zewnetrznym `PostgreSQL`,
- prostym MVP,

bezpieczniej jest zachowac jawny moment wdrozenia niz udawac dojrzaly pipeline produkcyjny, ktorego jeszcze nie potrzebujemy.

## 9. Kolejny krok po MVP

Po stabilizacji MVP sensowna droga dalsza to:

1. osobny workflow deployowy po zielonym CI,
2. wymuszenie backupu przed migracjami produkcyjnymi,
3. zdalne odpalenie `ops:smoke-test` po wdrozeniu,
4. zbieranie artefaktow i logow w razie niepowodzenia,
5. dopiero potem rozwazanie automatycznego rollbacku.

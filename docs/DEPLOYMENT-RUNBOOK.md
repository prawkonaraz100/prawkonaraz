# Deployment Runbook: prawkonaraz.pl na Mikrusie

Status: kanoniczna procedura manualnego deployu  
Ostatnia aktualizacja: 2026-09-19  
Produkcja: `https://prawkonaraz.pl`  
Serwer: `henryk153.mikrus.xyz`, SSH port `10153`  
Katalog aplikacji na VPS: `/var/www/prawkobit/current`

## 1. Cel

Ten dokument jest pierwszym miejscem, do ktorego powinien zajrzec kolejny agent
przed deployem produkcyjnym.

Dokument opisuje codzienny/manualny deploy zmian aplikacji. Nie opisuje od zera
zakladania serwera, DNS ani pierwszej instalacji systemu. Do tego sluzy
`docs/INFRA-MVP-MIKRUS-4.1-R2.md`.

## 2. Aktualny model produkcji

- Produkcja dziala bez Dockera.
- Laravel dziala na `Nginx + PHP-FPM`.
- PostgreSQL i Redis dzialaja lokalnie na tym samym VPS.
- Cloudflare obsluguje DNS, proxy i HTTPS.
- Nginx na VPS nadal ma legacy nazwe pliku/katalogu `prawkobit`; to jest nazwa
  techniczna i nie oznacza starej domeny.
- Produkcyjna domena kanoniczna to `https://prawkonaraz.pl`.
- `www.prawkonaraz.pl` ma przekierowywac na wersje bez `www`.
- Legacy hosty `prawkoapp.pl`, `www.prawkoapp.pl` i
  `wild-bison5536.byst.re` maja przekierowywac `301` na odpowiadajaca sciezke
  pod `https://prawkonaraz.pl`.
- Nieznane hosty trafiajace bezposrednio do Nginx maja byc odrzucane przez
  domyslny blok `return 444`, a nie obslugiwane przez aplikacje.
- Sekretow, hasel, tokenow SMTP, OAuth ani `.env` nie zapisujemy w repo.

## 3. Kiedy uzywac tego runbooka

Uzyj tego dokumentu, gdy:

- user prosi o `deploy`,
- zmiana zostala zmergowana do `main`,
- trzeba wgrac nowy build frontendu,
- trzeba wgrac zmiany w PHP/routes/views,
- trzeba uruchomic migracje albo odswiezyc cache.

Nie uzywaj tego dokumentu jako jedynego zrodla, gdy:

- trzeba pierwszy raz postawic serwer,
- trzeba zmienic DNS/Cloudflare/SSL,
- trzeba zmienic produkcyjny `.env`,
- trzeba migrowac duze media albo baze danych,
- trzeba robic restore po awarii.

W tych przypadkach najpierw sprawdz:

- `docs/INFRA-MVP-MIKRUS-4.1-R2.md`,
- `docs/RUNBOOK-OPS.md`,
- `docs/AUTH-DEPLOYMENT-REPAIR-PLAN.md` dla zmian auth/mail/OAuth.

## 4. Minimalny standard przed deployem

Przed deployem sprawdz:

```powershell
git status --short --branch
npm run build
```

Testy dobieraj do zakresu zmiany. Nie uruchamiaj domyslnie pelnego
`php artisan test` przy rutynowym deployu, jesli zmiana dotyka waskiego obszaru
i da sie wskazac celowane testy regresyjne. W finalnej odpowiedzi zawsze zapisz,
jakie testy zostaly uruchomione i dlaczego byly w zakresie zmiany.

Przyklady:

```powershell
# zmiany auth
docker exec serwistestyprawojazdy-app-1 php artisan test tests/Feature/Auth/AuthenticationTest.php

# zmiany w /przepisy i uzasadnieniach prawnych
docker exec serwistestyprawojazdy-app-1 php artisan test tests/Feature/Public/LegalTrustLayerMvpTest.php
docker exec serwistestyprawojazdy-app-1 php artisan test tests/Feature/Public/SeoSitemapGenerationTest.php
docker exec serwistestyprawojazdy-app-1 php artisan test --filter=PublicQuestionDatabasePageTest

# zmiany sitemap/SEO
docker exec serwistestyprawojazdy-app-1 php artisan test tests/Feature/Public/SeoSitemapGenerationTest.php
docker exec serwistestyprawojazdy-app-1 php artisan test tests/Unit/Infrastructure/NginxSeoStaticDeliveryConfigurationTest.php
```

Pelny `php artisan test` uruchamiaj wtedy, gdy zmiana jest przekrojowa, dotyka
warstw wspoldzielonych, migracji, autoryzacji, konfiguracji aplikacji albo gdy
nie da sie uczciwie wyznaczyc waskiego zakresu ryzyka.

Przed deployem frontendowym musi powstac:

```text
public/build/manifest.json
```

Produkcja nie buduje regularnie assetow Node/Vite. Najbezpieczniejszy aktualny
wzor to: build lokalnie, potem dostarczenie gotowego `public/build` w paczce.

## 5. Merge przed deployem

Rutynowy deploy robimy z `main`.

Przed deployem:

```powershell
git checkout main
git merge --no-ff NAZWA_BRANCHA
git status --short --branch
```

Jesli merge utworzyl commit, w finalnej odpowiedzi Codex powinien pokazac
dyrektywe git commit zgodnie z instrukcjami aplikacji.

## 6. Przygotowanie paczki

Aktualnie najbezpieczniejszy wzor to paczka `.tar.gz` z:

- konkretnymi plikami zmienionymi w danym deployu,
- `public/build`,
- ewentualnie migracjami/seederami/komendami, jesli zmiana ich wymaga.

Nie pakuj:

- `.env`,
- `storage`,
- `vendor`,
- `node_modules`,
- lokalnych dumpow bazy,
- prywatnych kluczy i sekretow.

Przyklad PowerShell dla waskiej paczki:

```powershell
$stamp = Get-Date -Format 'yyyyMMddHHmmss'
$pkg = "output\release-prawkonaraz-$stamp.tar.gz"
$files = @(
  'app/Http/Controllers/ExampleController.php',
  'resources/js/Pages/Example.vue',
  'resources/views/example.blade.php',
  'routes/web.php',
  'public/build'
)
tar -czf $pkg @files
Resolve-Path $pkg
```

Liste plikow najlepiej wziac z diffu merge commita:

```powershell
git diff --name-only HEAD^1 HEAD
```

Jesli deploy nie jest merge commitem, uzyj zakresu branch/main, np.:

```powershell
git diff --name-only main...NAZWA_BRANCHA
```

## 7. Wyslanie paczki na VPS

Uzywamy SSH na porcie `10153`. Hasla nie dokumentujemy. Lokalnie jest uzywany
klucz SSH skonfigurowany dla Mikrusa.

```powershell
scp -i C:\Users\xxx\.ssh\mikr_henryk153_ed25519 -P 10153 `
  output\release-prawkonaraz-YYYYMMDDHHMMSS.tar.gz `
  root@henryk153.mikrus.xyz:/tmp/release-prawkonaraz-YYYYMMDDHHMMSS.tar.gz
```

Docelowo nalezy utworzyc osobnego uzytkownika deployowego i przestac wykonywac
codzienne deploye jako `root`. Na moment tej dokumentacji produkcja nadal uzywa
root SSH.

## 8. Deploy na serwerze

Standardowy zdalny deploy powinien:

1. wejsc do `/var/www/prawkobit/current`,
2. zrobic backup zmienianych sciezek do `/tmp/...`,
3. wlaczyc maintenance mode,
4. rozpakowac paczke,
5. ustawic wlasciciela dla `public/build` i nowych katalogow assetow,
6. odswiezyc autoload/cache,
7. uruchomic migracje,
8. wylaczyc maintenance mode,
9. przeladowac PHP-FPM,
10. wykonac smoke testy.

Szablon zdalnego deployu:

```powershell
$script = @'
set -euo pipefail
APP_DIR="/var/www/prawkobit/current"
PKG="/tmp/release-prawkonaraz-YYYYMMDDHHMMSS.tar.gz"
STAMP="$(date +%Y%m%d%H%M%S)"
BACKUP_DIR="/tmp/prawkonaraz-release-backup-${STAMP}"

cd "${APP_DIR}"

echo "[1/8] Backup changed files -> ${BACKUP_DIR}"
mkdir -p "${BACKUP_DIR}"
for path in \
  app/Http/Controllers/ExampleController.php \
  resources/js/Pages/Example.vue \
  resources/views/example.blade.php \
  routes/web.php \
  public/build
do
  if [ -e "${path}" ]; then
    mkdir -p "${BACKUP_DIR}/$(dirname "${path}")"
    cp -a "${path}" "${BACKUP_DIR}/${path}"
  fi
done

echo "[2/8] Enable maintenance mode"
php artisan down --retry=60 || true
cleanup() { php artisan up || true; }
trap cleanup EXIT

echo "[3/8] Extract package"
tar -xzf "${PKG}" -C "${APP_DIR}"
chown -R www-data:www-data "${APP_DIR}/public/build" || true

echo "[4/8] Refresh autoload and caches"
composer dump-autoload --optimize --no-interaction
php artisan optimize:clear
php artisan config:cache
php artisan view:cache

echo "[5/8] Run migrations"
php artisan migrate --force

echo "[6/8] Disable maintenance and reload PHP-FPM"
php artisan up
trap - EXIT
systemctl reload php8.3-fpm || true

echo "[7/8] App smoke checks"
php artisan ops:health-report
php artisan ops:smoke-test

echo "[8/8] Done"
echo "DEPLOY_OK"
echo "BACKUP_DIR=${BACKUP_DIR}"
'@
$script | ssh -i C:\Users\xxx\.ssh\mikr_henryk153_ed25519 -p 10153 `
  root@henryk153.mikrus.xyz 'bash -s'
```

Przy zmianach sitemap/SEO po deployu dodaj:

```bash
php artisan seo:refresh-sitemaps
php artisan seo:audit-sitemaps
```

Przy konfiguracji albo rotacji klucza IndexNow dodaj:

```bash
php artisan seo:indexnow-key-file --dry-run
php artisan seo:indexnow-key-file
curl https://prawkonaraz.pl/indexnow-....txt
```

Prawdziwego klucza nie commituj do repo. Preferowany format klucza to
`indexnow-...`, ale klucz wygenerowany przez Bing bez tego prefiksu tez jest
poprawny. Publiczne pliki `.txt` z kluczami sa ignorowane przez git, z
wyjatkami dla jawnych plikow repo typu `robots.txt` i `llms.txt`.

Jesli masz klucz jako lokalny plik `.txt`, mozesz wygenerowac plik publiczny
bez wpisywania klucza do `.env` lokalnie:

```bash
php artisan seo:indexnow-key-file --source=storage/app/NAZWA-PLIKU.txt --dry-run
php artisan seo:indexnow-key-file --source=storage/app/NAZWA-PLIKU.txt
```

Przed pierwszym realnym submitowaniem URL-i do IndexNow wykonaj dry-run:

```bash
php artisan seo:indexnow-submit --from-sitemap --dry-run --limit=10
```

Pierwsza realna wysylka powinna byc mala probka z raportem:

```bash
php artisan seo:indexnow-submit --from-sitemap --limit=10 --report=storage/app/reports/indexnow-first-submit.json
```

Jesli API zwroci `429`, nie ponawiaj natychmiast calej paczki. Zmniejsz limit
albo poczekaj. `200` i `202` traktujemy jako przyjete operacyjnie.

Przed produkcyjnym wlaczeniem fazy 2 IndexNow nie ma automatycznego
harmonogramu. Nie uruchamia sie sam po deployu, po `seo:refresh-sitemaps` ani po
zapisach w panelu. Po zmianach SEO/importach uruchom submit recznie, najlepiej
najpierw z `--dry-run`.

Jezeli produkcyjny `.env` nie zawiera `INDEXNOW_KEY`, uzyj aktualnego pliku
klucza jako `--key-source` i nie zapisuj pelnego klucza w dokumentacji:

```bash
php artisan config:clear
INDEXNOW_ENABLED=true php artisan seo:indexnow-submit --from-sitemap --dry-run --limit=10 --key-source=public/{key}.txt
INDEXNOW_ENABLED=true php artisan seo:indexnow-submit --from-sitemap --limit=10 --key-source=public/{key}.txt --report=storage/app/reports/indexnow-manual-submit.json
php artisan config:cache
```

`config:clear` jest potrzebne przy jednorazowym `INDEXNOW_ENABLED=true` w
komendzie, gdy produkcyjny config byl juz cache'owany z wylaczonym IndexNow.
Jezeli `INDEXNOW_ENABLED=true` jest zapisane w produkcyjnym `.env` i wykonano
`php artisan config:cache`, inline env nie jest potrzebny.

Automatyzacja IndexNow w fazie 2 dziala przez lokalna kolejke zmienionych URL-i
i scheduler wysylajacy male paczki, zamiast cyklicznego wysylania calej sitemap.
Po wdrozeniu kodu fazy 2 ustaw produkcyjnie:

```dotenv
INDEXNOW_ENABLED=true
INDEXNOW_KEY_SOURCE=public/{key}.txt
INDEXNOW_AUTOMATION_ENABLED=true
INDEXNOW_QUEUE_BATCH_SIZE=50
INDEXNOW_QUEUE_DEBOUNCE_MINUTES=10
INDEXNOW_QUEUE_RETRY_MINUTES=60
```

Mozna uzyc `INDEXNOW_KEY` zamiast `INDEXNOW_KEY_SOURCE`, ale wtedy prawdziwy
klucz musi byc bezpiecznie zapisany w produkcyjnym `.env`.

Nastepnie sprawdz:

```bash
php artisan migrate --force
php artisan seo:indexnow-drain-queue --dry-run --limit=10
php artisan seo:indexnow-enqueue-public-explanations --updated-since=2026-07-09 --dry-run --limit=10
```

Po masowej aktualizacji publicznych wyjasnien wrzuc zmienione URL-e do kolejki:

```bash
php artisan seo:indexnow-enqueue-public-explanations --updated-since=2026-07-09 --report=storage/app/reports/indexnow-public-explanations-enqueue.json
```

Gdy przez miesiac albo dwa nie ma zmian tresci, kolejka powinna byc pusta.
Scheduler nadal moze uruchamiac `seo:indexnow-drain-queue`, ale komenda wykonuje
wtedy tylko lekki odczyt po indeksie i nie wysyla requestow HTTP.

Przy zmianach multimediow dodaj:

```bash
php artisan ops:smoke-test --require-media
```

Przy zmianach Filament/admin zwykle wystarcza `composer dump-autoload`,
`optimize:clear`, cache i smoke, ale po duzych zmianach admina warto otworzyc
panel recznie.

## 9. Alternatywny skrypt w repo

W repo istnieje bazowy skrypt:

```text
deploy/mikrus/deploy.sh
```

Ten skrypt zaklada, ze kod i `public/build` sa juz na serwerze, a potem wykonuje
standardowe kroki Laravela:

- maintenance mode,
- `composer install --no-dev`,
- migracje,
- `storage:link`,
- czyszczenie i budowanie cache,
- powrot aplikacji online.

W praktyce dla mniejszych release'ow czesto uzywamy paczki `.tar.gz` i
dedykowanego zdalnego skryptu, bo daje to:

- waski zakres plikow,
- backup tylko zmienianych sciezek,
- szybszy deploy,
- latwiejszy rollback plikow.

## 10. Kontrole po deployu

Jesli release zmienia `deploy/mikrus/nginx/prawkobit.conf.example`, sam deploy aplikacji **nie aktualizuje automatycznie** aktywnej konfiguracji Nginx. Najpierw porownaj i zastosuj zmianę do produkcyjnego vhosta, a następnie:

```bash
nginx -t
systemctl reload nginx
```

Nie traktuj repo-level testu konfiguracji jako dowodu, że aktywny origin używa nowego configu.

Na serwerze:

```bash
cd /var/www/prawkobit/current
php artisan ops:health-report
php artisan ops:smoke-test
php artisan route:list --path=logout
```

Z lokalnego komputera:

```powershell
Invoke-WebRequest -Uri https://prawkonaraz.pl/login -UseBasicParsing
Invoke-WebRequest -Uri https://prawkonaraz.pl/api/v1/health -UseBasicParsing
```

Dla release'u dotykającego robots/sitemap/Nginx uruchom również executable production SEO smoke:

```bash
bash scripts/production-seo-delivery-smoke.sh https://prawkonaraz.pl
```

Skrypt sprawdza `/robots.txt`, `/sitemap.xml`, `/sitemaps/static.xml` i `/aktualnosci/feed.xml`: status, Content-Type, public Cache-Control dla statycznych crawler assets, brak `Set-Cookie`, ETag/Last-Modified oraz conditional `304`. Domyślnie feed może zwrócić `404`, gdy newsroom public gate jest wyłączony. Po świadomym włączeniu publicznego feedu uruchom:

```bash
REQUIRE_NEWSROOM_FEED=1 bash scripts/production-seo-delivery-smoke.sh https://prawkonaraz.pl
```

Zachowaj wynik tego realnego runu jako evidence. Sam fakt, że skrypt istnieje w repo lub że jego test konfiguracji jest PASS, nie oznacza production smoke PASS.

Od PR #117 ten sam smoke ma dedykowany workflow GitHub Actions `.github/workflows/production-seo-delivery-smoke.yml`:
- run na `pull_request` jest **REPORT-ONLY** przeciw aktualnej produkcji; może zakończyć job zielono mimo niezgodności, ale zapisuje raport i warning,
- `workflow_dispatch` jest domyślnie **STRICT** i jest właściwym sposobem zapisania post-deploy production evidence po zastosowaniu aktualnego Nginx configu,
- opcja `require_newsroom_feed` wymusza publiczny kontrakt Atom feedu dopiero po świadomym włączeniu newsroom public gate.

Pierwszy report-only run workflow z PR #117 nie był production PASS: publiczny `/robots.txt` zwrócił `Cache-Control: max-age=14400`, podczas gdy kontrakt repo oczekuje `public, max-age=3600`; smoke zakończył się kodem `1`. Taki zielony REPORT-ONLY job jest wyłącznie evidence rozbieżności. Production PASS wolno zapisać dopiero po udanym STRICT `workflow_dispatch`.

Minimalny sukces:

- publiczne URL-e zwracaja `200` albo oczekiwane `301`,
- legacy domeny zachowuja sciezke i query string w pojedynczym przekierowaniu
  `301` do `https://prawkonaraz.pl`,
- zadna legacy domena ani tymczasowy host nie zwraca strony aplikacji z kodem
  `200` i wlasnym canonicalem,
- `ops:health-report` ma status `OK` albo znany, wyjasniony `degraded`,
- `ops:smoke-test` zwraca `Smoke test status: OK`,
- dla zmian SEO/static delivery `production-seo-delivery-smoke.sh` kończy się `Production SEO delivery smoke passed.` na rzeczywistym publicznym URL,
- aplikacja nie zostala w maintenance mode.

Po deployu frontendowym, jesli uzytkownik widzi stary CSS/JS:

- najpierw odswiez twardo przegladarke,
- przy problemie przez Cloudflare zrob selective/custom purge dla konkretnego
  URL-a,
- nie rob `Purge Everything`, jesli nie ma potrzeby.

## 11. Rollback

Kazdy deploy paczkowy powinien drukowac:

```text
BACKUP_DIR=/tmp/prawkonaraz-...-backup-YYYYMMDDHHMMSS
```

Rollback plikow wykonuj ostroznie:

```bash
cd /var/www/prawkobit/current
cp -a /tmp/prawkonaraz-...-backup-YYYYMMDDHHMMSS/. .
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
systemctl reload php8.3-fpm || true
php artisan ops:smoke-test
```

Jesli deploy zawieral migracje destrukcyjne albo zmiany danych, rollback plikow
nie wystarczy. Wtedy sprawdz backupy bazy:

```bash
php artisan ops:list-db-backups --limit=10
```

Restore bazy wykonuj tylko po swiadomej decyzji, najlepiej z dodatkowym backupem
aktualnego stanu:

```bash
php artisan ops:restore-db backups/database-manifests/YYYY/MM/manifest.json --force --backup-current
```

## 12. Najczestsze pulapki

- Nie deployuj samego Vue bez nowego `public/build`.
- Nie zakladaj, ze produkcja ma dzialajacy Node build.
- Nie zapisuj sekretow w dokumentacji ani w paczce.
- Nie nadpisuj `.env` na produkcji przy zwyklym deployu.
- Nie zostawiaj aplikacji w maintenance mode po bledzie; zdalny skrypt musi miec
  `trap`.
- Nie myl legacy katalogu `/var/www/prawkobit/current` z domena. Domena
  produkcyjna to `prawkonaraz.pl`.
- Przy zmianach sitemap pamietaj, ze XML-e sa artefaktem produkcyjnym generowanym
  z aktualnej bazy, a nie plikami commitowanymi do repo.
- Produkcyjne sesje uzytkownikow sa przechowywane w Redis DB `0`, a cache
  aplikacyjny w Redis DB `1`. Nie wykonuj `FLUSHALL` ani recznego czyszczenia
  DB `0` podczas deployu. Restart albo utrata danych sesyjnych moze wywolac
  serie bledow CSRF `419` w dlugo otwartych kartach.
- Po zmianach auth/menu wykonaj test starej karty oraz sprawdz plan
  `docs/AUTH-CSRF-419-AUDIT-AND-REPAIR-PLAN.md`.

## 13. Ostatni potwierdzony deploy wedlug tego wzorca

2026-06-04 wykonano deploy zmian:

- layout `/zaproszenie`,
- tlo `/login` i `/register`,
- bezpieczny `GET /logout` z ekranem potwierdzenia,
- `POST /logout` jako realne wylogowanie.

Weryfikacja:

- `npm run build` lokalnie: OK,
- `tests/Feature/Auth/AuthenticationTest.php`: 8 testow OK,
- produkcyjny `ops:health-report`: OK,
- produkcyjny `ops:smoke-test`: OK,
- publiczne `https://prawkonaraz.pl/login`: `200`,
- publiczne `https://prawkonaraz.pl/zaproszenie`: `200`.

Backup z tamtego deployu:

```text
/tmp/prawkonaraz-invitation-logout-backup-20260604195159
```

2026-06-18 wykonano deploy naprawy auth/CSRF `419`:

- endpoint odświeżania sesji i tokenu,
- bezpieczne flow login/register/logout,
- terminalne zatrzymanie mutacji, timerów i realtime po utracie sesji,
- obsługa nauki, PJM, egzaminu, znaków drogowych, demo i rankingu.

Weryfikacja:

- produkcyjny `ops:health-report`: OK,
- produkcyjny `ops:smoke-test`: OK,
- `/auth/csrf-token`: `200`, `no-store, private`, Cloudflare `DYNAMIC`,
- dwa cookie jar otrzymały różne tokeny,
- stary token zwrócił kontrolowane `419/CSRF_TOKEN_MISMATCH`,
- świeży token przepuścił request przez middleware CSRF,
- Redis: `noeviction`, brak limitu pamięci, RDB OK, AOF wyłączone.

Backup:

```text
/tmp/prawkonaraz-csrf-419-backup-20260618150745
```

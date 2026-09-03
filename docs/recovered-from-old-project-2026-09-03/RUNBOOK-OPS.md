# Runbook Operacyjny

## 1. Cel dokumentu

Ten dokument opisuje minimalny standard operacyjny dla projektu.

Jego celem jest:

- uproscic codzienna obsluge systemu,
- zmniejszyc chaos przy wdrozeniach i awariach,
- ustalic jednolity sposob reakcji na najczestsze problemy,
- zabezpieczyc MVP przed "hero debuggingiem" bez procedur.

To nie jest pelny SRE handbook. To jest praktyczny runbook dla malego, ale profesjonalnie prowadzonego produktu.

## 2. Zakres

Dokument obejmuje:

- MVP na `Mikrus 4.1 PRO + Cloudflare + docelowo R2`,
- podstawowe operacje deployowe,
- backup i restore,
- monitoring,
- reagowanie na awarie,
- podstawowe symptomy przeciazenia,
- przygotowanie pod V2.

## 3. Architektura operacyjna

Na MVP zakladamy:

- `1x VPS` Mikrus z `Laravel`, `PHP-FPM`, `PostgreSQL` i `Redis`,
- `Cloudflare` przed ruchem,
- `R2` docelowo dla assetow i backupow,
- `systemd` do zarzadzania uslugami,
- `Nginx` jako reverse proxy,
- `cron` dla schedulerow systemowych i `Laravel Scheduler`.

To oznacza:

- mamy prosty stack,
- ale pojedynczy serwer jest krytyczny,
- dlatego backup, logi i procedury restore sa obowiazkowe.

Aktualny stan produkcji z 2026-05-25:

- docelowy kanoniczny URL po decyzji klienta: `https://prawkonaraz.pl`,
- aktualny URL produkcyjny: `https://prawkonaraz.pl`,
- `www.prawkonaraz.pl` przekierowuje `301` na `https://prawkonaraz.pl`,
- Cloudflare ma wlaczone `Always Use HTTPS`,
- Cloudflare SSL/TLS dziala w trybie `Flexible`,
- aplikacja dziala na VPS `henryk153`,
- katalog aplikacji: `/var/www/prawkobit/current`,
- docelowy publiczny health endpoint:
  `https://prawkonaraz.pl/api/v1/health`,
- media i backupy sa jeszcze lokalnym etapem pomostowym; R2 zostaje nastepnym krokiem operacyjnym.

Uwaga domenowa:

- SMTP/Brevo i OAuth wymagaja osobnej autoryzacji nowej domeny,
- po przepieciu serwera publiczny `521` z Cloudflare oznacza, ze origin Nginx
  dziala lokalnie, ale tryb SSL/TLS Cloudflare nie pasuje do aktualnego originu
  HTTP; na MVP ustawiamy `SSL/TLS -> Overview -> Flexible`, a `Full (strict)`
  dopiero po skonfigurowaniu certyfikatu origin i portu 443.

## 4. Minimalna lista uslug krytycznych

Uslugi krytyczne:

- reverse proxy,
- `PHP-FPM`,
- aplikacja webowa/API,
- PostgreSQL,
- scheduler aplikacyjny,
- `cron -> php artisan schedule:run`,
- queue worker, jesli jest wlaczony,
- cron backupu,
- DNS i routing przez Cloudflare,
- publiczny storage mediow,
- monitoring uptime.

## 5. Standard operacyjny

Kazda zmiana produkcyjna powinna spelniac te zasady:

1. Mozna ja cofnac albo naprawic forward fixem.
2. Wiadomo, kto ja wdrazal.
3. Wiadomo, kiedy byla wdrazana.
4. Po wdrozeniu jest wykonany podstawowy smoke test.
5. Nie wdrazamy duzych zmian bez aktualnego backupu.
6. Zmiana powinna miec zielony workflow CI zanim trafi na produkcje.

## 6. Minimalny smoke test po deployu

Po kazdym wdrozeniu nalezy sprawdzic:

1. strona glowna dziala,
2. logowanie dziala,
3. start sesji dziala,
4. zapis odpowiedzi dziala,
5. zakonczenie sesji dziala,
6. dashboard dziala,
7. obraz laduje sie poprawnie,
8. wideo laduje sie poprawnie,
9. health endpoint odpowiada.

Dla aktualnej produkcji minimalny publiczny check po kazdej zmianie:

```bash
curl -I https://prawkonaraz.pl
curl -I https://www.prawkonaraz.pl
curl -s https://prawkonaraz.pl/api/v1/health
```

Oczekiwane:

- `https://prawkonaraz.pl` zwraca `200`,
- `https://www.prawkonaraz.pl` zwraca `301` do `https://prawkonaraz.pl/...`,
- `/api/v1/health` zwraca `status: ok`.

Kanoniczna automatyzacja serwerowa:

```bash
php artisan ops:seed-smoke-data
php artisan ops:smoke-test
php artisan ops:smoke-test --require-media
```

Na produkcji komendy uruchamiamy z katalogu:

```bash
cd /var/www/prawkobit/current
php artisan ops:health-report
php artisan ops:smoke-test
```

Interpretacja:

- `ops:seed-smoke-data` przygotowuje deterministyczny zestaw danych i placeholder assetow pod lokalny lub CI smoke,
- bazowy smoke test sprawdza health, publiczne kategorie, flow sesji `learn` i dashboard,
- `--require-media` dodatkowo wymaga co najmniej jednego obrazu i jednego wideo obecnego na storage,
- jesli smoke test failuje, deploy nie powinien byc uznany za zakonczony poprawnie.

## 7. Deploy checklist

Kanoniczna, szczegolowa procedura codziennego/manualnego deployu jest w:

- `docs/DEPLOYMENT-RUNBOOK.md`

Ta sekcja zostaje jako skrot operacyjny. Jesli kolejne kroki albo komendy sa
niejasne, uzyj `DEPLOYMENT-RUNBOOK.md` jako zrodla prawdy.

### Przed deployem

- upewnic sie, ze zmiana ma sensowny zakres,
- sprawdzic, czy migracje bazy sa bezpieczne,
- wykonac backup bazy,
- sprawdzic plik `.env`,
- sprawdzic, czy storage i domeny sa dostepne,
- znac plan rollbacku.

### W trakcie deployu

- przygotowac artefakt aplikacji i assety frontendowe,
- upewnic sie, ze workflow CI jest zielony,
- wdrozyc nowa wersje aplikacji,
- uruchomic `composer install --no-dev`,
- uruchomic `php artisan migrate --force`,
- odswiezyc `config`, `routes` i inne cache aplikacji,
- zrestartowac `PHP-FPM` i ewentualny worker,
- sprawdzic logi aplikacji,
- wykonac `php artisan ops:smoke-test`,
- dla rolloutow z multimediami wykonac tez `php artisan ops:smoke-test --require-media`.

### Po deployu

- sprawdzic bledy aplikacyjne,
- sprawdzic odpowiedzi API,
- sprawdzic zuzycie CPU, RAM i dysku,
- potwierdzic, ze scheduler i backup nadal dzialaja.

Przykladowy wynik sukcesu powinien zawierac co najmniej:

- `health_api -> OK`
- `categories_api -> OK`
- `session_flow -> OK`
- `dashboard_metrics -> OK`

## 8. Backup policy

## 8.1. Co backupujemy

Backupujemy:

- PostgreSQL,
- najwazniejsze pliki konfiguracyjne serwera,
- metadane lub manifesty mediow, jesli sa trzymane poza baza,
- sekrety tylko zgodnie z bezpieczna polityka i nigdy w publicznym repo.

Nie backupujemy jako glownej strategii:

- runtime assetow z lokalnego dysku, bo te nie powinny tam zyc,
- tymczasowych plikow builda,
- cache,
- logow bez retencji.

## 8.2. Minimalna polityka backupu

- backup bazy codziennie,
- kompresja,
- upload do R2,
- retencja:
  - 7 dziennych,
  - 4 tygodniowe,
  - 3 miesieczne.

Kanoniczna komenda aplikacyjna:

```bash
php artisan ops:backup-db
```

Przykladowe warianty:

```bash
php artisan ops:backup-db --connection=pgsql
php artisan ops:backup-db --connection=pgsql --label=pre-deploy
php artisan ops:backup-db --connection=pgsql --no-prune
```

Efekt:

- powstaje skompresowany backup `.gz`,
- zapisuje sie manifest JSON,
- backup trafia na skonfigurowany `BACKUP_DISK`,
- retencja czysci stare backupy zgodnie z polityka.

## 8.3. Backup success criteria

Backup jest uznany za poprawny, gdy:

- plik zostal wygenerowany,
- plik nie ma zerowego rozmiaru,
- upload do R2 zakonczyl sie sukcesem,
- wpis w logu backupu zostal zapisany.

## 9. Restore policy

Backup bez restore testu nie jest prawdziwym backupem.

Minimalny standard:

- okresowo przeprowadzic test odtworzenia,
- potwierdzic, ze backup daje sie przywrocic,
- zapisac czas restore.

## 9.1. Kiedy restore jest wymagany

- po uszkodzeniu danych,
- po nieodwracalnej blednej migracji,
- po przypadkowym usunieciu krytycznych rekordow,
- po awarii dysku lub serwera.

## 9.2. Restore checklist

1. zatrzymac aplikacje,
2. zabezpieczyc aktualny stan bazy, jesli to mozliwe,
3. wybrac poprawny punkt backupu,
4. odtworzyc baze,
5. uruchomic aplikacje,
6. wykonac smoke test,
7. udokumentowac zdarzenie.

Kanoniczne komendy aplikacyjne:

```bash
php artisan ops:list-db-backups --limit=10
php artisan ops:restore-db backups/database-manifests/2026/03/example.json --force
```

Wariant bezpieczniejszy:

```bash
php artisan ops:restore-db backups/database-manifests/2026/03/example.json --force --backup-current
```

Uwagi:

- `ops:list-db-backups` pokazuje ostatnie manifesty z backupami,
- `ops:restore-db` wymaga `--force`,
- `--backup-current` robi snapshot obecnego stanu przed restore,
- restore na SQLite wymaga plikowej bazy, nie `:memory:`,
- restore na PostgreSQL korzysta z lokalnych narzedzi `psql`.

## 10. Monitoring

Minimalny monitoring MVP:

- uptime check,
- health endpoint,
- alarm na niski wolny dysk,
- alarm na wysoki RAM,
- alarm na brak ostatniego backupu,
- podstawowy error tracking.

Kanoniczne komendy monitorujace:

```bash
php artisan ops:health-report
php artisan ops:health-report --json
php artisan ops:assert-backup-fresh
php artisan ops:perf-smoke
php artisan ops:perf-smoke --assert
```

## 10.1. Health endpoint

Health endpoint powinien:

- odpowiadac szybko,
- nie wykonywac ciezkich zapytan,
- zwracac prosty status,
- byc wykorzystywany przez monitoring i load balancer w V2.

Praktyka:

- publiczny `/api/v1/health` raportuje stan aplikacji i bazy,
- backup freshness moze oznaczyc stan jako `degraded`, ale nie musi zwracac `503`,
- alarm na backup najlepiej opierac dodatkowo o `ops:assert-backup-fresh`.

## 10.2. Metryki minimalne

Obserwujemy:

- CPU,
- RAM,
- wolne miejsce na dysku,
- restart count uslug,
- liczbe bledow aplikacyjnych,
- czas odpowiedzi API,
- sukces backupu.

Minimalna automatyzacja:

- scheduler produkcyjny uruchamia `ops:backup-db` codziennie,
- scheduler produkcyjny uruchamia `ops:assert-backup-fresh` co godzine,
- monitoring zewnetrzny odpytuje `/api/v1/health`,
- okresowo warto uruchamiac `ops:perf-smoke`, zeby zlapac regresje wydajnosciowe krytycznych flow zanim zauwaza je uzytkownicy.

## 11. Logowanie

Logi musza byc:

- czytelne,
- rotowane,
- ograniczone rozmiarem,
- przydatne do diagnozy,
- bez wycieku sekretow i danych wrazliwych.

Nie logujemy:

- hasel,
- tokenow,
- pelnych payloadow z danymi prywatnymi,
- binarnych assetow.

Minimalny standard MVP:

- domyslny kanal aplikacji powinien korzystac z rotacji dziennej,
- retencja logow powinna byc kontrolowana przez `LOG_DAILY_DAYS`,
- odpowiedzi HTTP i wpisy logow powinny dawac sie korelowac przez `X-Request-Id`,
- przy diagnozie incydentu najpierw notujemy `request_id`, a dopiero potem schodzimy do szczegolow logow.

Praktyka:

```bash
tail -n 200 storage/logs/laravel.log
ls storage/logs
```

## 12. Najczestsze awarie i reakcje

## 12.1. Aplikacja nie odpowiada

Sprawdzic:

1. status uslugi aplikacji,
2. logi aplikacji,
3. reverse proxy,
4. wolne miejsce na dysku,
5. RAM i OOM,
6. dostepnosc bazy.

Pierwsza reakcja:

- jesli problem jest chwilowy i znany, kontrolowany restart uslugi,
- jesli przyczyna nieznana, najpierw zabezpieczyc logi i stan, potem restart.

## 12.2. Baza nie odpowiada

Sprawdzic:

1. status PostgreSQL,
2. logi bazy,
3. wolne miejsce na dysku,
4. liczbe polaczen,
5. blokujace zapytania,
6. ostatnia migracje.

Pierwsza reakcja:

- nie restartowac odruchowo bez sprawdzenia dysku i polaczen,
- jesli winna jest migracja albo lock, diagnozowac to najpierw.

## 12.3. Media sie nie laduja

Sprawdzic:

1. czy URL assetu jest poprawny,
2. czy plik istnieje w R2,
3. czy custom domain dziala,
4. czy cache rules nie blokuja odpowiedzi,
5. czy nie zostala zmieniona nazwa obiektu bez aktualizacji metadanych.

## 12.4. Serwer ma za malo miejsca

Sprawdzic:

1. logi,
2. backupy lokalne,
3. pliki builda,
4. temporary files,
5. stare artefakty deployowe,
6. PostgreSQL WAL i tabele.

Pierwsza reakcja:

- odzyskac miejsce bez usuwania rzeczy krytycznych w ciemno,
- nie dotykac produkcyjnej bazy destrukcyjnie bez planu.

## 12.5. Wysoki RAM

Sprawdzic:

1. proces aplikacji,
2. PostgreSQL,
3. czy nie uruchomiono niepotrzebnych workerow,
4. czy nie ma memory leak po deployu.

Pierwsza reakcja:

- ograniczyc zbedne procesy,
- zrestartowac aplikacje jesli przyczyna znana,
- jesli powtarza sie regularnie, planowac optymalizacje lub wiekszy host.

## 13. Progi eskalacji

Eskalujemy, gdy:

- brak dzialania produkcji trwa dluzej niz kilka minut i nie ma jasnej przyczyny,
- backup nie wykonal sie kolejny raz,
- restore okazuje sie niemozliwe,
- dane uzytkownika sa niespojne lub utracone,
- podejrzewamy incydent bezpieczenstwa,
- VPS regularnie osiaga limit i problem wraca.

## 14. Kiedy przechodzimy na V2 operacyjnie

Sygnaly do zmiany modelu operacyjnego:

- zbyt ryzykowny single point of failure,
- coraz trudniejsze deploye,
- baza zaczyna konkurowac z aplikacja o zasoby,
- trzeba wdrazac bez przestojow,
- pojawiaja sie klienci B2B wymagajacy wyzszej niezawodnosci.

Wtedy:

- oddzielamy baze,
- dodajemy drugi app node,
- dodajemy load balancer,
- wydzielamy worker,
- wzmacniamy monitoring i audyt.

## 15. Runbook dla V2

W V2 dochodzi:

- health checks na load balancerze,
- rolling deploy,
- procedura wyjmowania pojedynczego noda z ruchu,
- osobny monitoring workera,
- testy awarii pojedynczego komponentu,
- mocniejszy audit trail.

## 16. Dokumentowanie incydentow

Kazdy powazniejszy incydent powinien miec zapis:

- co sie stalo,
- kiedy,
- jaki byl impact,
- jaka byla przyczyna,
- jak zostalo naprawione,
- jak zapobiegniemy powtorce.

## 17. Finalna rekomendacja

Profesjonalny runbook dla tego projektu nie musi byc ogromny, ale musi byc konkretny.

Minimalny sukces operacyjny MVP to:

- dzialajacy backup,
- przewidywalny deploy,
- podstawowy monitoring,
- umiejetnosc restore,
- brak paniki przy typowych awariach.

# Testy na Prawo Jazdy

Laravel 12 + Inertia.js + Vue 3 + TypeScript foundation for a multimedia driving-license learning platform.

## Stack

- Laravel 12
- Inertia.js + Vue 3 + TypeScript
- Filament for the admin panel
- PostgreSQL for local and production data
- Redis for application cache and sessions
- Pest for backend testing
- Vite for frontend builds

## Budget Production Target

Current low-cost production target:

- `Mikrus 4.1 PRO` for the application host
- local `PostgreSQL`
- local `Redis`
- `Cloudflare R2` for media and backups
- no Docker in production

Current production status:

- target canonical URL: `https://prawkonaraz.pl`
- current production URL: `https://prawkonaraz.pl`
- `www.prawkonaraz.pl` redirects to `https://prawkonaraz.pl`
- Cloudflare handles DNS, HTTPS, and HTTP-to-HTTPS redirects for the current setup
- application path on the VPS: `/var/www/prawkobit/current`
- target production health endpoint: `https://prawkonaraz.pl/api/v1/health`
- media and backups are still on the local VPS bridge setup; Cloudflare R2 is the next operational step

Deployment references:

- [DEPLOYMENT-RUNBOOK.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/DEPLOYMENT-RUNBOOK.md)
- [INFRA-MVP-MIKRUS-4.1-R2.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/INFRA-MVP-MIKRUS-4.1-R2.md)
- [INFRA-MVP-MIKRUS-3.5-R2.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/INFRA-MVP-MIKRUS-3.5-R2.md)
- [deploy/mikrus/deploy.sh](C:/Users/xxx/Desktop/serwistestyprawojazdy/deploy/mikrus/deploy.sh)
- [deploy/mikrus/nginx/prawkobit.conf.example](C:/Users/xxx/Desktop/serwistestyprawojazdy/deploy/mikrus/nginx/prawkobit.conf.example)
- [deploy/mikrus/cron/prawkobit.cron](C:/Users/xxx/Desktop/serwistestyprawojazdy/deploy/mikrus/cron/prawkobit.cron)
- [.env.mikrus.example](C:/Users/xxx/Desktop/serwistestyprawojazdy/.env.mikrus.example)

## Local Profiles

The repo now supports two local infrastructure profiles:

- default `PostgreSQL + Redis` profile via [.env.example](C:/Users/xxx/Desktop/serwistestyprawojazdy/.env.example)
- fallback `sqlite` profile via [.env.sqlite.example](C:/Users/xxx/Desktop/serwistestyprawojazdy/.env.sqlite.example)

## Local Setup

Recommended local start on Windows:

1. Start `Docker Desktop`
2. Double click [URUCHOM-PROJEKT.cmd](C:/Users/xxx/Desktop/serwistestyprawojazdy/URUCHOM-PROJEKT.cmd)

The launcher will:

- validate Docker daemon availability,
- build frontend assets,
- start `docker compose`,
- run migrations.

If you prefer the manual flow, use the steps below.

1. Copy the environment file: `Copy-Item .env.example .env`
2. Generate the app key: `.tools\\php83\\php.exe artisan key:generate`
3. Install frontend dependencies: `npm install`
4. Build assets: `node_modules\\.bin\\vite.cmd build`
5. Start the stack: `docker compose up -d --build`
6. Run migrations inside the app container: `docker compose exec -T app php artisan migrate --force`
7. If you are switching from an old local sqlite setup, copy the data once: `docker compose exec -T app php artisan ops:copy-sqlite-to-pgsql`
8. Optionally refresh Laravel caches: `docker compose exec -T app php artisan optimize:clear`

Fallback sqlite profile:

1. Copy `Copy-Item .env.sqlite.example .env`
2. Create `database/database.sqlite`
3. Run the same build and migration steps

For local media delivery keep these values in `.env`:

```env
MEDIA_DISK=public
MEDIA_PUBLIC_DISK=public
MEDIA_PUBLIC_BASE_URL=
BACKUP_DISK=local
HEALTH_MONITOR_BACKUP=false
LOG_STACK=daily
```

For large official `gov.pl` imports you can move media outside the repo to a bulk local disk:

```env
MEDIA_DISK=media_local
MEDIA_PUBLIC_DISK=media_local
MEDIA_UPLOAD_DISK=media_local
MEDIA_LOCAL_ROOT=D:/datasets/mi-prawo-jazdy-2026/app-media
MEDIA_LOCAL_HOST_ROOT=D:/datasets/mi-prawo-jazdy-2026/app-media
MEDIA_LOCAL_URL=http://127.0.0.1:8081
MEDIA_LOCAL_LINK=storage-bulk
```

When you run the stack through Docker, `MEDIA_LOCAL_HOST_ROOT` is mounted into the containers at `/srv/media`, so Laravel inside Docker should not use the Windows `D:/...` path directly.

If you serve media directly without Docker, refresh public symlinks with `php artisan storage:link`.

## Admin Access

Filament is available at `/admin`.

Operational audit trail is available in the admin panel at `/admin/audit-logs`.

Content import history is available in the admin panel at `/admin/content-import-runs`.

Official local test accounts:

- admin:
  - login: `test-admin@local.test`
  - password: `change-me-now`
  - purpose: default admin account for local QA, `/admin`, `/nauka`, inline question/explanation edits and browser smoke checks
- user:
  - login: `test-user@local.test`
  - password: `change-me-now`
  - purpose: default non-admin account for regular study flow, permissions checks and user-facing QA

Create or refresh that administrator account with:

```bash
php artisan app:make-admin test-admin@local.test --name="Official Test Admin" --password="change-me-now"
```

## Media Storage

The application now resolves media URLs through a dedicated media layer:

- local development: `public` disk + `storage:link`
- production: `r2` disk backed by Cloudflare R2
- public delivery: `MEDIA_PUBLIC_BASE_URL` set to the Cloudflare custom domain, for example `https://media.example.com`

## Content Import

Import a question catalog from JSON with:

```bash
php artisan catalog:import-json storage/app/catalog.json
```

Import a staged manifest batch with ready assets:

```bash
php artisan catalog:import-manifest storage/app/import/batch-2026-03-19/manifest.json
php artisan catalog:import-manifest storage/app/import/batch-2026-03-19/manifest.json --dry-run
```

Import a whole chunk series in one run:

```bash
php artisan catalog:import-manifest-series storage/app/import/gov-batch-b-series --dry-run
php artisan catalog:import-manifest-series storage/app/import/gov-batch-b-series --from-chunk=3 --continue-on-error
php artisan catalog:import-manifest-series storage/app/import/gov-batch-b-series --resume-from-report storage/app/import-reports/latest-series-import.json --report storage/app/import-reports/latest-series-import.json
```

Prepare a staging batch directly from the official `gov.pl` XLSX and downloaded media archives:

```bash
php artisan catalog:prepare-gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip
php artisan catalog:prepare-gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B
php artisan catalog:prepare-gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-ready D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --ready-only
php artisan catalog:prepare-gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --materialize-media
php artisan catalog:prepare-gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-audit D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --materialize-media --allow-missing-media
php artisan catalog:prepare-gov-batch D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b-chunk-01 D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --materialize-media --source-offset=0 --source-limit=50
php artisan catalog:prepare-gov-batch-series D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b-series D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --materialize-media --chunk-size=50
php artisan catalog:prepare-gov-batch-series D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b-series D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --materialize-media --chunk-size=50 --skip-existing
php artisan catalog:prepare-gov-batch-series D:/datasets/mi-prawo-jazdy-2026/raw/baza-pytan.xlsx storage/app/import/gov-batch-b-series D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz1.zip D:/datasets/mi-prawo-jazdy-2026/raw/multimedia-cz2.zip --categories=B --materialize-media --chunk-size=50 --skip-existing --allow-missing-media
```

The government batch builder:

- reads the official `katalog` sheet from the XLSX,
- duplicates rows per licence category,
- can scope the staging batch to a comma-separated subset such as `--categories=B,A`,
- can emit a `--ready-only` batch that keeps only rows importable with the current media setup,
- can process the official XLSX in deterministic chunks with `--source-offset` and `--source-limit`,
- can generate a whole chunk series with `catalog:prepare-gov-batch-series`,
- can resume a large chunk series with `--skip-existing` and reuse already valid chunk reports,
- stores official IDs, sheet names, translations and PJM references in `questions.metadata`,
- audits main media and PJM references even before assets are materialized,
- can materialize images directly from ZIP packages,
- generates `thumb.webp` variants for staged images when `ffmpeg` is available,
- converts `WMV` files to `MP4` only when `ffmpeg` is available in `PATH` or under `MEDIA_FFMPEG_BINARY`,
- generates posters for staged videos during `WMV -> MP4` preprocessing,
- can keep long-running staging jobs alive with `--allow-missing-media` when the official packages have gaps,
- and the manifest importer can auto-fit a few oversized staged official images into `.webp` derivatives when they exceed `MEDIA_MAX_IMAGE_BYTES`,
- writes `manifest.json`, `questions.csv` and `report.json` into the output directory.

Import and staging commands also persist a normalized run log to the database, so admin users can review pipeline history in Filament without digging through raw JSON reports.

Validate a batch without writing to the database:

```bash
php artisan catalog:import-json storage/app/catalog.json --dry-run
```

Import a staged manifest locally with images written to the public disk:

```powershell
$env:MEDIA_UPLOAD_DISK='public'
$env:MEDIA_DISK='public'
.tools\php83\php.exe artisan catalog:import-manifest storage\app\import\gov-batch-b-ready-v2\manifest.json
```

Import a full official series to the bulk local disk on `D:`:

```powershell
$env:MEDIA_UPLOAD_DISK='media_local'
$env:MEDIA_DISK='media_local'
$env:MEDIA_PUBLIC_DISK='media_local'
.tools\php83\php.exe artisan catalog:import-manifest-series D:\datasets\mi-prawo-jazdy-2026\staging\gov-full-series --dry-run
.tools\php83\php.exe artisan catalog:import-manifest-series D:\datasets\mi-prawo-jazdy-2026\staging\gov-full-series
```

Audit delivery readiness after import and optionally deactivate questions with missing primary media:

```bash
php artisan content:audit-delivery-readiness
php artisan content:audit-delivery-readiness --category=B
php artisan content:audit-delivery-readiness --deactivate-missing-primary-media
```

On the current local full `gov.pl` dataset the audit detected and deactivated `788` records with missing primary media, leaving `17 587` active questions ready for pilot delivery.

Write an explicit JSON report:

```bash
php artisan catalog:import-json storage/app/catalog.json --report=storage/app/import-reports/catalog-report.json
```

The importer now:

- validates categories, questions and media records,
- supports `dry-run` mode,
- writes a JSON report with counts, errors and affected records,
- returns a non-zero exit code when the batch contains validation errors,
- supports strict manifest-based imports of preprocessed assets from a local staging directory.

## Operations

Database backup and restore commands are now built into the app:

```bash
php artisan ops:backup-db
php artisan ops:list-db-backups --limit=10
php artisan ops:restore-db backups/database-manifests/2026/03/example.json --force
```

For a safer restore, snapshot the current state first:

```bash
php artisan ops:restore-db backups/database-manifests/2026/03/example.json --force --backup-current
```

Health and monitoring helpers:

```bash
php artisan ops:seed-smoke-data
php artisan ops:health-report
php artisan ops:health-report --json
php artisan ops:assert-backup-fresh
php artisan ops:smoke-test
php artisan ops:smoke-test --require-media
php artisan ops:perf-smoke
php artisan ops:perf-smoke --assert
npm run e2e:smoke
```

Operational defaults:

- `LOG_STACK=daily` enables daily log rotation out of the box,
- every app and API response includes `X-Request-Id` for traceability,
- API error envelopes also expose `meta.request_id`.
- `ops:seed-smoke-data` provisions deterministic category/question/media fixtures for local and CI smoke runs,
- `ops:smoke-test` validates health, public categories, learn session flow and dashboard metrics after deploy.
- `ops:perf-smoke` mierzy session start, first answer, session complete i dashboard metrics jako lekki benchmark operatorski.
- `npm run e2e:smoke` uruchamia browser smoke w Playwright: provisioning danych, logowanie, start albo wznowienie realnej sesji `/nauka`, zapis odpowiedzi, kontrola manifestu i service workera, offline fallback oraz matryca 360/390/430 px z dolna nawigacja. Zrzuty i raport JSON trafiaja do `output/playwright/`.

Dla lokalnego quality gate PWA przez rzeczywisty Nginx Docker:

```powershell
$env:DB_HOST = '127.0.0.1'
$env:E2E_SMOKE_BASE_URL = 'http://localhost:8000'
$env:E2E_SMOKE_EXPECT_PWA_CACHE_HEADERS = 'true'
npm run e2e:smoke
```

W tym trybie test zawsze buduje aktualny frontend i dodatkowo wymaga naglowka `no-store` dla `service-worker.js`. W CI bez `E2E_SMOKE_BASE_URL` test uruchamia wlasny serwer PHP i sprawdza zachowanie aplikacji, ale nie udaje warstwy cache Nginx.

Expected JSON shape:

```json
{
  "categories": [
    {
      "code": "B",
      "name": "Kategoria B",
      "questions": [
        {
          "external_id": "B-001",
          "prompt": "Czy przed ruszeniem nalezy zapinac pasy?",
          "option_a": "Tak",
          "option_b": "Nie",
          "correct_answer": "a",
          "question_type": "boolean",
          "media": [
            {
              "kind": "image",
              "path": "questions/b/b-001.webp"
            }
          ]
        }
      ]
    }
  ]
}
```
```

## Project Docs

Detailed documentation lives in [docs](./docs):

- [docs/README.md](./docs/README.md)
- [docs/STACK-DECISION.md](./docs/STACK-DECISION.md)
- [docs/ROADMAP-TECH.md](./docs/ROADMAP-TECH.md)
- [docs/ZNAKI-DROGOWE-SEO-ETAP-1-PLAN.md](./docs/ZNAKI-DROGOWE-SEO-ETAP-1-PLAN.md)
- [docs/ZNAKI-DROGOWE-SEO-MILESTONE-5-QA.md](./docs/ZNAKI-DROGOWE-SEO-MILESTONE-5-QA.md)
- [docs/SEO-CONTENT-ROADMAP.md](./docs/SEO-CONTENT-ROADMAP.md)
- [docs/NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./docs/NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- [docs/NEWSROOM-IMPLEMENTATION-BACKLOG.md](./docs/NEWSROOM-IMPLEMENTATION-BACKLOG.md)
- [docs/ENTERPRISE-SCHEMA-STAGE-1-2-PLAN.md](./docs/ENTERPRISE-SCHEMA-STAGE-1-2-PLAN.md)
- [docs/INFRA-MVP-HETZNER-R2.md](./docs/INFRA-MVP-HETZNER-R2.md)
- [docs/CI-CD.md](./docs/CI-CD.md)

## Quality Checks

- Backend tests: `php artisan test`
- Frontend build: `npm run build`
- Browser smoke: `npm run e2e:smoke`

# Jak Uruchomic Projekt

## 1. Cel

To jest najkrotsza, kanoniczna instrukcja uruchomienia projektu lokalnie.

Na dzis rekomendowany tryb pracy to:

- `Docker Desktop` do aplikacji i serwera mediow,
- lokalny build frontendu do `public/build`,
- lokalna paczka mediow z `gov.pl` montowana z dysku `D:`.

Repo wspiera teraz dwa warianty lokalne:

- domyslny stack `PostgreSQL + Redis`
- fallback `sqlite`, gdy potrzebujesz ultra-lekkiego debugowego profilu

Launcher uruchamia kontenery pod stala nazwa projektu Docker:

- `serwistestyprawojazdy_local`

## 2. Wymagania

Potrzebne sa:

- `Docker Desktop`
- `Node.js + npm`
- plik `.env`
- lokalne media pod sciezka:
  - `D:\datasets\mi-prawo-jazdy-2026\app-media`

Wazne:

- sam zainstalowany `docker.exe` nie wystarczy
- musi byc uruchomiony `Docker Desktop`
- launcher sprawdza teraz nie tylko obecność Dockera, ale tez to, czy daemon faktycznie odpowiada

## 3. Szybki Start

Jesli projekt jest juz zbootstrapowany, zwykle wystarcza te komendy:

```powershell
node_modules\.bin\vite.cmd build
docker compose up -d --build
```

Po starcie aplikacja jest dostepna tutaj:

- app: [http://127.0.0.1:8000](http://127.0.0.1:8000)
- nauka: [http://127.0.0.1:8000/nauka](http://127.0.0.1:8000/nauka)
- media: [http://127.0.0.1:8081](http://127.0.0.1:8081)

Domyslny lokalny profil to:

```powershell
Copy-Item .env.example .env
node_modules\.bin\vite.cmd build
docker compose up -d --build
```

Oficjalne lokalne konta testowe:

- admin:
  - login: `test-admin@local.test`
  - haslo: `change-me-now`
  - uzycie: domyslne konto do testow `/admin`, `/nauka`, inline edycji pytan i wyjasnien
- user:
  - login: `test-user@local.test`
  - haslo: `change-me-now`
  - uzycie: domyslne konto do zwyklej nauki, testow uprawnien i scenariuszy bez roli admina

## 3A. Start Jednym Kliknieciem

Jest tez launcher do dwukliku:

- [URUCHOM-PROJEKT.cmd](C:/Users/xxx/Desktop/serwistestyprawojazdy/URUCHOM-PROJEKT.cmd)

Ten plik:

- dopilnuje `.env`
- wygeneruje `APP_KEY`, jesli jest puste
- zbuduje frontend
- uruchomi `docker compose`
- odpali migracje

To jest najprostsza opcja dla czlowieka, ktory chce po prostu kliknac i odpalic projekt.

Jesli Docker Desktop nie dziala, launcher zatrzyma sie od razu z czytelnym komunikatem, zamiast wywalac surowy blad `npipe/dockerDesktopLinuxEngine`.

## 4. Pierwsze Uruchomienie

Jesli odpalasz projekt pierwszy raz albo na nowej maszynie:

1. Skopiuj env:

```powershell
Copy-Item .env.example .env
```

Alternatywa dla fallbackowego `sqlite`:

```powershell
Copy-Item .env.sqlite.example .env
```

2. Jesli `APP_KEY` jest puste, wygeneruj je:

```powershell
.tools\php83\php.exe artisan key:generate
```

3. Wykonaj migracje:

```powershell
docker compose exec -T app php artisan migrate --force
```

4. Jesli nie masz zaleznosci frontendu:

```powershell
npm install
```

5. Zbuduj frontend:

```powershell
node_modules\.bin\vite.cmd build
```

6. Uruchom kontenery:

```powershell
docker compose up -d --build
```

## 5. Jak Dziala Lokalny Setup

Lokalny stack sklada sie z 5 uslug:

- `app` - PHP-FPM z Laravelem
- `web` - nginx dla aplikacji
- `media` - osobny nginx serwujacy obrazy i filmy
- `postgres` - lokalna baza `PostgreSQL`
- `redis` - lokalny cache

W praktyce:

- aplikacja chodzi pod `:8000`
- media chodza pod `:8081`
- frontend nie jest serwowany z Vite dev servera, tylko z gotowego `public/build`

Dlatego po zmianach w Vue lub CSS trzeba zwykle przebudowac frontend:

```powershell
node_modules\.bin\vite.cmd build
```

## 6. Najczestszy Workflow

### Start projektu

```powershell
node_modules\.bin\vite.cmd build
docker compose up -d --build
```

### Sprawdzenie czy kontenery dzialaja

```powershell
docker compose ps
```

### Podglad logow aplikacji

```powershell
docker compose logs --tail=100 app web
```

### Restart po zmianach backendu lub konfiguracji Dockera

```powershell
docker compose up -d --build --force-recreate
```

### Start z fallbackowym sqlite

```powershell
Copy-Item .env.sqlite.example .env
node_modules\.bin\vite.cmd build
docker compose up -d --build
```

### Zatrzymanie projektu

```powershell
docker compose down
```

## 7. Co Zrobic Po Zmianach

### Zmiany w Vue, TS, CSS

```powershell
node_modules\.bin\vite.cmd build
```

### Zmiany w PHP

Zwykle wystarczy:

```powershell
docker compose up -d --build
```

### Zmiany w migracjach

```powershell
docker compose up -d --build
docker compose exec -T app php artisan migrate --force
```

### Jednorazowe przeniesienie starego lokalnego sqlite do PostgreSQL

Jesli wczesniej pracowales na `database/database.sqlite` i chcesz zachowac pytania, media, userow i postepy:

```powershell
docker compose exec -T app php artisan ops:copy-sqlite-to-pgsql
```

## 8. Diagnostyka

### Strona sie nie odswieza po zmianach

1. zrob:

```powershell
node_modules\.bin\vite.cmd build
```

2. potem w przegladarce:

- `Ctrl+F5`

### Nie laduja sie zdjecia albo filmy

Sprawdz:

- czy istnieje katalog `D:\datasets\mi-prawo-jazdy-2026\app-media`
- czy kontener `media` dziala:

```powershell
docker compose ps
```

- czy media odpowiadaja:
  - [http://127.0.0.1:8081](http://127.0.0.1:8081)

### Aplikacja nie wstaje

Sprawdz logi:

```powershell
docker compose logs --tail=100 app web
```

### Launcher `URUCHOM-PROJEKT.cmd` konczy sie od razu bledem

Najczestsza przyczyna:

- Docker Desktop nie jest uruchomiony

Co zrobic:

1. uruchom `Docker Desktop`
2. poczekaj, az daemon bedzie gotowy
3. kliknij ponownie [URUCHOM-PROJEKT.cmd](C:/Users/xxx/Desktop/serwistestyprawojazdy/URUCHOM-PROJEKT.cmd)

### Baza nie dziala

Sprawdz:

- czy kontenery `postgres` i `redis` dzialaja:

```powershell
docker compose ps
```

- czy migracje przeszly:

```powershell
docker compose exec -T app php artisan migrate --force
```

## 9. Fallback Bez Dockera

Ten tryb traktujemy jako awaryjny, nie glowny.

```powershell
Copy-Item .env.example .env
.tools\php83\php.exe artisan key:generate
New-Item -ItemType File -Path database/database.sqlite -Force
.tools\php83\php.exe artisan migrate
.tools\php83\php.exe artisan storage:link
npm install
php artisan serve
npm run dev
```

Ale aktualny, rekomendowany tryb pracy dla tego repo to nadal:

- `vite build`
- `docker compose up -d --build`
- `docker compose exec -T app php artisan migrate --force`

## 10. Punkt Prawdy Dla Agenta AI

Jesli agent AI ma tylko jedna rzecz zapamietac, to ta:

1. frontend budujemy lokalnie do `public/build`
2. aplikacje odpalamy przez `docker compose`
3. media bierzemy z lokalnej paczki na `D:`, nie sciagamy ich ponownie
4. glowny ekran roboczy projektu to:
- [http://127.0.0.1:8000/nauka](http://127.0.0.1:8000/nauka)

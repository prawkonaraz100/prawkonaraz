# Lokalny katalog roboczy projektu

Ten katalog jest edytowalna kopia robocza utworzona z aktywnego wydania produkcyjnego pobranego 2 wrzesnia 2026 r. To tutaj wykonujemy dalsze zmiany i testy lokalne.

## Najwazniejsze lokalizacje

- Kod roboczy: `F:\serwistestyprawojazdy\workspace`
- Dane mediow: `F:\serwistestyprawojazdy\workspace-data\media`
- Oryginalna, nieedytowalna kopia produkcyjna: `F:\serwistestyprawojazdy\production-source`
- Zrzut produkcyjnej bazy: `F:\serwistestyprawojazdy\production-source\database\prawkobit-production.dump`
- Dane Dockera: `F:\DockerData`

Nie edytuj katalogu `production-source`. Sluzy on jako punkt odniesienia i material do ponownego odtworzenia srodowiska.

## Lokalne uruchomienie

Projekt Docker Compose nazywa sie `serwistestyprawojazdy_f` i korzysta z dwoch plikow konfiguracji:

```powershell
Set-Location 'F:\serwistestyprawojazdy\workspace'
docker compose -p serwistestyprawojazdy_f -f docker-compose.yml -f docker-compose.local.yml up -d
```

Zatrzymanie bez usuwania bazy i innych wolumenow:

```powershell
docker compose -p serwistestyprawojazdy_f -f docker-compose.yml -f docker-compose.local.yml stop
```

Status:

```powershell
docker compose -p serwistestyprawojazdy_f -f docker-compose.yml -f docker-compose.local.yml ps
```

Adres aplikacji: <http://localhost:8000>

Serwer mediow: <http://localhost:8081>

Domyslnie aplikacja korzysta ze skompilowanych assetow CSS/JS pobranych z produkcji. Dzieki temu wyglad odpowiada aktywnemu wydaniu produkcyjnemu.

Vite jest opcjonalny i nalezy go wlaczac tylko podczas pracy nad frontendem:

```powershell
docker compose -p serwistestyprawojazdy_f -f docker-compose.yml -f docker-compose.local.yml --profile frontend-dev up -d vite
```

Po zakonczeniu pracy z Vite zatrzymaj go i usun wygenerowany plik `public\hot`, aby wrocic do produkcyjnych assetow:

```powershell
docker compose -p serwistestyprawojazdy_f -f docker-compose.yml -f docker-compose.local.yml stop vite
Remove-Item -LiteralPath '.\public\hot' -ErrorAction SilentlyContinue
```

WebSocket: `localhost:8080`

## Stan odtworzenia

- Kod pochodzi z aktywnego wydania produkcyjnego `20260527230800-admin-email-verified`.
- Produkcyjny zrzut PostgreSQL zostal odtworzony do osobnego lokalnego wolumenu Dockera.
- Media zostaly skopiowane do edytowalnego katalogu na F i sa montowane do kontenerow.
- Redis zostal uruchomiony jako czysty lokalny magazyn. Produkcyjnych sesji ani cache nie odtwarzano.
- Strona domyslnie korzysta z gotowych assetow `public\build` z produkcji; usluga Vite nalezy do opcjonalnego profilu `frontend-dev`.
- Lokalne ustawienia w `.env` wylaczaja wysylke poczty i inne zewnetrzne integracje. Plik `.env` jest wykluczony z Git i nie wolno go publikowac.
- Poprzedni projekt Docker z dysku G pozostaje zachowany, lecz zatrzymany.

## Repozytorium Git

- Glowne repozytorium robocze znajduje sie w tym katalogu na dysku F.
- Galaz `main` rozpoczyna sie od czystego punktu bazowego odzyskanego z aktywnej produkcji.
- Plik `.env.mikrus.example` pozostaje tylko lokalnie i jest wykluczony z Git, poniewaz zawiera ustawienia wdrozeniowe.
- Stary katalog `.git` na dysku G jest uszkodzony po awarii: czesc commitow i reflogow jest czytelna, ale brakuje czesci obiektow. Nie nalezy go kopiowac nad to repozytorium ani usuwac, dopoki nie zakonczymy osobnego odzyskiwania historii.
- Repozytorium nie ma jeszcze zdalnego serwera `origin`; kopia na GitHubie lub GitLabie wymaga pozniejszego podlaczenia wybranego prywatnego repozytorium.

## Zasady bezpieczenstwa

- Nie uruchamiaj produkcyjnego pliku `.env` lokalnie.
- Nie publikuj zrzutu bazy, mediow, `.env` ani kluczy dostepowych.
- Nie uzywaj `docker compose down -v`, jesli chcesz zachowac odtworzona baze.
- Przed wdrozeniem zmian na produkcje wykonaj osobny przeglad i kopie zapasowa.

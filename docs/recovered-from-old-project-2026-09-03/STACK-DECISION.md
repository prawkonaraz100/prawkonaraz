# Stack Decision

## 1. Status

Accepted

## 2. Cel

Ten dokument formalizuje finalny wybor stacku technologicznego dla projektu.

Jego celem jest:

- usunac niejednoznacznosc,
- zapisac kanoniczne decyzje startowe,
- rozdzielic rzeczy wybrane od rzeczy odlozonych,
- uproscic start implementacji.

## 3. Wybrany stack

### Backend

- `Laravel`

### Frontend aplikacyjny

- `Inertia.js`
- `Vue 3`
- `TypeScript`

### Panel administracyjny / backoffice

- `Filament`

### Auth

- `Laravel` web-first auth oparty o sesje i cookies
- ochrona CSRF dla requestow zmieniajacych stan

### Baza danych

- `PostgreSQL`

### Queue i scheduler

- `Laravel queue` na driverze `database` w MVP
- `Laravel scheduler` uruchamiany przez `cron`

### Testy

- `Pest` dla unit, integration i HTTP/API tests
- `Playwright` dla E2E i smoke tests

### Infrastruktura

- `Mikrus 3.5` jako budzetowy target MVP
- `Nginx`
- `Cloudflare`
- `Cloudflare R2`

### Media pipeline

- `ffmpeg`
- `ImageMagick`

## 4. Kluczowe zalozenia

1. Produkcja nie potrzebuje stalego runtime `Node.js`.
2. `Node.js` i `Vite` sa narzedziami build-time dla assetow frontendowych.
3. Multimedia sa poza baza i poza lokalnym dyskiem runtime.
4. Aplikacja startuje jako modularny monolit.
5. W MVP unikamy dodatkowych uslug typu Redis/Valkey, jesli nie ma twardej potrzeby.

## 5. Co swiadomie odrzucilismy na start

- `Next.js` jako glowny fullstack framework
- mikroserwisy
- managed BaaS jako centralny model auth i danych
- stale procesy `Node.js` w produkcji
- osobny broker queue na MVP

## 6. Dlaczego ten stack

Wybralismy go, bo daje najlepszy balans:

- szybkosci budowy MVP,
- prostoty operacyjnej na malym VPS,
- niskiego kosztu utrzymania,
- gotowosci pod panel admina i B2B,
- czytelnej architektury backendowej,
- dobrej drogi wzrostu do V2.

## 7. Co to oznacza dla implementacji

- API i web app zyja w jednej aplikacji Laravel,
- frontend aplikacyjny jest renderowany przez Inertia,
- auth nie opiera sie domyslnie o Bearer token dla web app,
- panel admina budujemy w oparciu o Filament,
- testy backendowe opieramy o Pest,
- E2E opieramy o Playwright.

## 8. Kiedy rewizja tej decyzji ma sens

Rewizja ma sens tylko wtedy, gdy:

- zmienia sie fundamentalnie typ produktu,
- dochodzi wiele niezaleznych klientow aplikacyjnych,
- pojawia sie twarda potrzeba zewnetrznego public API z tokenowym auth,
- skala systemu uzasadnia inny model runtime lub podzialu uslug.

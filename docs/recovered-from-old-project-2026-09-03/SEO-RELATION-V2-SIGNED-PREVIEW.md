# Podpisany podgląd publiczny relacji V2

Status: `HISTORICAL PREVIEW / PRODUCTION V2 FULL-TOPIC ROLLOUT ACTIVE`

Podpisany preview jest narzędziem administracyjnym, a nie mechanizmem
aktywacji canary. Aktualny publiczny rollout `mode=canary` z ekspozycją 100%
obejmuje wszystkie 29 źródeł `secondary:zawracanie`; szczegóły operacyjne są
w `docs/SEO-RELATION-V2-CANARY-RUNBOOK.md`.

## Cel i zakres

Podgląd pozwala otworzyć prawdziwą stronę pytania z komponentem relacji z
aktywnego snapshotu V2 (`mode=shadow` lub `mode=canary`), bez zmieniania
samego zwykłego SSR. Służy do oceny układu i konkretnego zestawu rekomendacji
na produkcji.

- zwykły kanoniczny URL stosuje niezależnie reguły aktywnego canary: obecnie
  V2 dla 29/29 źródeł `secondary:zawracanie`, V1 w pozostałych topicach;
- preview działa tylko dla jednego pytania i ważnego, czasowego podpisu;
- nie tworzy publicznego canary, nie zmienia runu, rolloutu, flag ani danych;
- podgląd pytania 352 używa aktywnego produkcyjnego runu nr 2; nie zastępuje
  go lokalny artefakt ani nie zmienia przypisania do kohorty.

## Generowanie linku

Na produkcji, z katalogu aplikacji:

```bash
php artisan seo:make-question-relation-v2-preview-url 352 --minutes=120
```

Komenda akceptuje tylko pytanie z aktywnym snapshotem `shadow` albo `canary`;
zakres ważności wynosi 5–1440 minut. Zwraca URL z `relation_preview=v2`,
`expires` i `signature`.

## Ochrona i SEO

- parametr preview bez poprawnego podpisu albo z wygasłym podpisem zwraca
  `403`;
- ważny URL pokazuje widoczny znacznik „Podgląd V2 · link tymczasowy,
  nieindeksowany”;
- response ma `<meta name="robots" content="noindex,nofollow,noarchive">` i
  nie emituje JSON-LD;
- link nie jest umieszczany w nawigacji, sitemapach ani w treści zwykłych stron;
- po wygaśnięciu podpisu nie ma dostępu do V2 z tego URL-a.

Podpisany link jest uprawnieniem typu capability — należy udostępniać go tylko
osobom, którym można pokazać przygotowany snapshot V2.

## Weryfikacja wdrożenia

W dniu wdrożenia preview 2026-07-28 potwierdzono:

- `ops:health-report`: `OK`,
- `ops:smoke-test`: `OK`,
- podgląd pytania 352: 15 rekomendacji V2, meta robots `noindex,nofollow,noarchive`,
  brak JSON-LD,
- zwykły URL pytania 352: `200`, bez markera V2 i bez `noindex`.

Po pierwszej aktywacji canary potwierdzono URL-e 1178 i 6019 w V2 oraz 352 w
V1. Po promocji pełnego topicu serwerowa kontrola potwierdziła V2 z 15 linkami
dla 29/29 źródeł, a zwykłe URL-e 352, 1178 i 1428 mają HTTP 200 oraz poprawny
canonical. Monitor canary ma 0 błędów i 0 ostrzeżeń.

Backup wdrożenia: `/tmp/prawkonaraz-v2-signed-preview-backup-20260728005530`.

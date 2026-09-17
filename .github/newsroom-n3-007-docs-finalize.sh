#!/usr/bin/env bash
set -euo pipefail

python3 <<'PY'
from pathlib import Path
import re

MAIN = 'c68672f6aa7c41defaeec56debb541d5a60d9f4f'
IMPL = 'bf7981d23ff99a6335b55ecaeb6ce36b6622a042'

def load(path):
    return Path(path).read_text()

def save(path, text):
    Path(path).write_text(text)

def one(text, old, new, label):
    n = text.count(old)
    if n != 1:
        raise SystemExit(f'{label}: expected exactly 1 occurrence, got {n}')
    return text.replace(old, new, 1)

def insert_after_heading(text, heading, block, label):
    marker = heading + '\n'
    if text.count(marker) != 1:
        raise SystemExit(f'{label}: heading occurrence mismatch')
    return text.replace(marker, marker + '\n' + block.rstrip() + '\n', 1)

# Canonical architecture: preserve historical truth and mark current N3-007 status.
p = 'docs/NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md'
s = load(p)
s = one(s,
    '- [x] `NEWSROOM-N3-006` — old-path -> canonical 301.\n\nN3-007 author-profile integration jest następnym wykonywalnym taskiem; N3-008 rollout gate pozostaje otwarte po N3-008.',
    '- [x] `NEWSROOM-N3-006` — old-path -> canonical 301.\n- [x] `NEWSROOM-N3-007` — author-profile integration.\n\nN3-007 author-profile integration jest zmaterializowane; N3-008 rollout gate jest następnym wykonywalnym taskiem.',
    'architecture current task status')
s = one(s,
    '- następnym taskiem wykonawczym jest NEWSROOM-N3-008 author-profile integration; N3-008 rollout gate, N4 reverse links/huby oraz N5 discovery pozostają otwarte.',
    '- następnym taskiem wykonawczym jest NEWSROOM-N3-007 author-profile integration; N3-008 rollout gate, N4 reverse links/huby oraz N5 discovery pozostają otwarte.',
    'architecture v0.35 historical next task')
s = one(s,
    '- następnym taskiem wykonawczym jest NEWSROOM-N3-006 historical old-path -> canonical 301; N3-008 i N3-008 pozostają otwarte.',
    '- następnym taskiem wykonawczym jest NEWSROOM-N3-006 historical old-path -> canonical 301; N3-007 i N3-008 pozostają otwarte.',
    'architecture v0.34 historical open tasks')
save(p, s)

# Backlog: keep current status accurate and restore historical N3-007/N3-008 wording.
p = 'docs/NEWSROOM-IMPLEMENTATION-BACKLOG.md'
s = load(p)
s = one(s,
    f'**DONE w kodzie — PR #77 zmergowany i zweryfikowany na `main@{MAIN}`.** Dokumentacyjny docs-sync jest osobnym krokiem po potwierdzonym post-merge gate.',
    f'**DONE w kodzie — PR #77 zmergowany i zweryfikowany na `main@{MAIN}`.** Ten osobny docs-sync synchronizuje źródła prawdy po potwierdzonym post-merge gate.',
    'backlog docs-sync status')
s = one(s,
    '- następnym taskiem wykonawczym jest NEWSROOM-N3-006 historical redirect resolver; N3-008/N3-008 pozostają otwarte.',
    '- następnym taskiem wykonawczym jest NEWSROOM-N3-006 historical redirect resolver; N3-007/N3-008 pozostają otwarte.',
    'backlog historical open tasks')
save(p, s)

# Public UI/UX: only N3-007-relevant live-state/status/history changes.
p = 'docs/NEWSROOM-PUBLIC-UI-UX-SPEC.md'
s = load(p)
s = one(s,
    'Na 2026-09-17 po NEWSROOM-N3-006:',
    f'Na 2026-09-17 po NEWSROOM-N3-007, zweryfikowanym na `main@{MAIN}`:',
    'ui live state heading')
s = one(s,
    '- historyczne old-slug -> current canonical 301 są wdrożone przez NEWSROOM-N3-006 bez dodatkowego UI surface; `NEWSROOM_PUBLIC_ENABLED` pozostaje NEWSROOM-N3-008, a newsroomowe rozszerzenie profilu autora NEWSROOM-N3-007,',
    '- historyczne old-slug -> current canonical 301 są wdrożone przez NEWSROOM-N3-006 bez dodatkowego UI surface; `NEWSROOM_PUBLIC_ENABLED` pozostaje NEWSROOM-N3-008,\n- NEWSROOM-N3-007 rozszerza istniejący `/autorzy/{slug}`: `published` trafia do aktualnych publikacji, `needs_review+indexable` do osobnej sekcji „W trakcie weryfikacji”, `archived+indexable` do osobnego „Archiwum”, a noindex/scheduled/withdrawn/inactive-category nie są listowane,',
    'ui author-profile live state')
s = one(s,
    '- [ ] zbudować NEWSROOM-N3-007 author-profile integration — **następny wykonywalny task**,\n- [ ] zbudować NEWSROOM-N3-008 public rollout config gate.',
    '- [x] zbudować NEWSROOM-N3-007 author-profile integration,\n- [ ] zbudować NEWSROOM-N3-008 public rollout config gate — **następny wykonywalny task**.',
    'ui remaining tasks')
ui_hist = f'''### 2026-09-17 — v0.15

- NEWSROOM-N3-007 zmergowano przez PR #77 na `main@{MAIN}` bez tworzenia drugiej strony autora,
- istniejący `/autorzy/{{slug}}` pokazuje Newsroom corpus z osobnymi stanami bieżącym, „W trakcie weryfikacji” i „Archiwum”; stany noindex/scheduled/withdrawn/inactive-category nie są listowane,
- `ContentAuthorProfileTest` chroni profile lifecycle i author links; exact-head CI #295 oraz post-merge CI #296 są PASS,
- następnym publicznym taskiem jest NEWSROOM-N3-008 rollout gate.
'''
s = insert_after_heading(s, '## 69. Historia zmian', ui_hist, 'ui history')
save(p, s)

# SEO/distribution: shared author identity + public-state-aware sitemap, plus two stale current-state facts encountered during verification.
p = 'docs/NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md'
s = load(p)
s = one(s,
    'Stan obecny: `ContentAuthorController` i `TrafficSignSchemaService::author()` już renderują publiczny ProfilePage, ale podczas integracji newsroomu jego `mainEntity Person` należy wyrównać do tego samego stabilnego `/autorzy/{slug}#person` i `worksFor -> /#organization`, którego używa article graph. Nie tworzymy drugiego ProfilePage.',
    'Stan obecny po NEWSROOM-N3-007: `ContentAuthorSchemaService` jest współdzielonym builderem `ProfilePage -> Person`, a `SharedAuthorTrafficSignSchemaService` deleguje do niego istniejących consumers. ProfilePage i Article używają identycznego stabilnego `/autorzy/{slug}#person` oraz `worksFor -> /#organization`. `SeoSitemapBuilder::authorUrls()` kwalifikuje również autorów przez indeksowalne Newsroom articles w aktywnych kategoriach i używa ich `public_state_changed_at` dla newsroomowego author lastmod zamiast technicznego article `updated_at`. Nie utworzono drugiego ProfilePage.',
    'seo author current state')
s = one(s,
    '- author pages istnieją i mają istniejący ProfilePage pattern,',
    f'- author pages istnieją; po NEWSROOM-N3-007 ProfilePage/Article współdzielą stabilny Person `@id`, a author sitemap uwzględnia indexable Newsroom corpus z `public_state_changed_at` (`main@{MAIN}`),',
    'seo implementation author bullet')
s = one(s,
    '- backendowy newsroom Article schema graph istnieje przez `ContentArticleSchemaService` po NEWSROOM-N3-003, ale nie jest jeszcze emitowany przez publiczny article HTTP renderer; news sitemap i feed nadal nie istnieją,',
    '- newsroom Article schema graph istnieje przez `ContentArticleSchemaService` po NEWSROOM-N3-003 i jest emitowany przez publiczny article HTTP renderer od N3-004; news sitemap i feed nadal nie istnieją,',
    'seo stale article schema state')
s = one(s,
    '- category/topic/article route namespaces są zarejestrowane, ale pozostają 404 bez publicznych controllerów.',
    '- category/topic route namespaces są zarejestrowane i nadal pozostają 404 bez publicznych controllerów; article detail routes są aktywne od N3-004.',
    'seo stale route state')
s = one(s,
    '- [x] wdrożyć ContentArticleSchemaService; publiczne osadzenie graphu w article HTML pozostaje częścią N3-004,',
    '- [x] wdrożyć ContentArticleSchemaService i publiczne osadzenie graphu w article HTML w N3-004,\n- [x] zintegrować ProfilePage/Article author identity i author sitemap z Newsroom corpus w N3-007,',
    'seo completed author task')
seo_hist = f'''### 2026-09-17 — v0.12

- NEWSROOM-N3-007 zmergowano przez PR #77 na `main@{MAIN}`: ProfilePage i Article współdzielą stabilny Person `@id` i canonical Organization reference,
- `SeoSitemapBuilder::authorUrls()` kwalifikuje autorów przez indexable Newsroom corpus w aktywnych kategoriach i używa newsroomowego `public_state_changed_at` zamiast technicznego article `updated_at`,
- `ContentAuthorProfileTest` chroni identity i sitemap freshness; exact-head CI #295 oraz post-merge CI #296 są PASS,
- news sitemap/feed i rollout gate pozostają dalszym zakresem; N3-007 nie oznacza ich jako wdrożonych.
'''
s = insert_after_heading(s, '## 70. Historia zmian', seo_hist, 'seo history')
save(p, s)

# Test/release runbook: record exact regression and gates without inventing Browser Smoke evidence.
p = 'docs/NEWSROOM-TEST-RELEASE-AND-ROLLBACK-RUNBOOK.md'
s = load(p)
s = one(s,
    'Na 2026-09-17 po NEWSROOM-N3-006:',
    f'Na 2026-09-17 po NEWSROOM-N3-007, zweryfikowanym na `main@{MAIN}`:',
    'runbook live state heading')
s = one(s,
    '- current-canonical public detail ma automatyczne 200/404/410 coverage; 410/404 nie renderują treści i są noindex, a `NewsroomArticleRedirectTest` pokrywa old-slug one-hop 301/fail-closed behavior N3-006,',
    '- current-canonical public detail ma automatyczne 200/404/410 coverage; 410/404 nie renderują treści i są noindex, a `NewsroomArticleRedirectTest` pokrywa old-slug one-hop 301/fail-closed behavior N3-006,\n- `ContentAuthorProfileTest` pokrywa N3-007 lifecycle profilu autora, wspólną Person/Organization identity, author-sitemap freshness z `public_state_changed_at` oraz blokadę odpublikowania autora z zależnym indexable article i odblokowanie po noindex,',
    'runbook N3-007 regression state')
s = one(s,
    '- hub/category/topic/feed browser surfaces, author profile integration N3-007, rollout config gate N3-008 i reverse links N4-008 pozostają otwarte; old-slug redirects N3-006 są zamknięte,',
    '- hub/category/topic/feed browser surfaces, rollout config gate N3-008 i reverse links N4-008 pozostają otwarte; author profile integration N3-007 i old-slug redirects N3-006 są zamknięte,',
    'runbook open scope')
s = one(s,
    '- [ ] dodać dalsze test files w trakcie N3-007..N5,',
    '- [ ] dodać dalsze test files w trakcie N3-008..N5,',
    'runbook future test range')
s = one(s,
    '- [x] dodać old-slug 301 HTTP integration w N3-006; `NewsroomArticleRedirectTest` pokrywa newsroom/guides one-hop redirect, current canonical 200 oraz invalid redirect fail-closed 404,',
    '- [x] dodać old-slug 301 HTTP integration w N3-006; `NewsroomArticleRedirectTest` pokrywa newsroom/guides one-hop redirect, current canonical 200 oraz invalid redirect fail-closed 404,\n- [x] dodać N3-007 `ContentAuthorProfileTest` dla author-profile lifecycle, shared schema identity, public-state sitemap freshness i unpublish guard,',
    'runbook completed author regression')
run_hist = f'''### 2026-09-17 — v0.19

- NEWSROOM-N3-007 implementation PR #77 zakończył exact-head CI #295 PASS na `{IMPL}`; post-merge CI #296 zakończył PASS na `main@{MAIN}`,
- `ContentAuthorProfileTest` potwierdza lifecycle author profile, public-state sitemap freshness, blokadę unpublish z indexable article oraz odblokowanie po noindex,
- post-merge `quality`: 1055 passed / 19 540 assertions / 2 skipped; Pint 1055 files PASS; frontend build PASS; `newsroom-postgres` PASS,
- N3-007 nie miało osobnego Browser Smoke i dokument nie dopisuje takiego dowodu; następny zakres zaczyna się od N3-008.
'''
s = insert_after_heading(s, '## 60. Historia zmian', run_hist, 'runbook history')
save(p, s)

# Cross-document final assertions: current state + history + no accidental duplicate rewrites.
checks = {
    'docs/NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md': [
        f'`main@{MAIN}`',
        '- [x] `NEWSROOM-N3-007` — author-profile integration.',
        'N3-008 rollout gate jest następnym wykonywalnym taskiem.',
        'NEWSROOM-N3-007 author-profile integration; N3-008 rollout gate',
    ],
    'docs/NEWSROOM-IMPLEMENTATION-BACKLOG.md': [
        f'`main@{MAIN}`',
        'ContentAuthorProfileTest.php',
        'CI #295 — PASS',
        'post-merge CI #296 — PASS',
        'NEWSROOM-N3-008 — Public rollout config gate',
    ],
    'docs/NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md': [
        f'main@{MAIN}',
        'Aktualny stan implementacji po NEWSROOM-N3-007',
        'ContentAuthorSchemaService',
        'public_state_changed_at',
    ],
    'docs/NEWSROOM-PUBLIC-UI-UX-SPEC.md': [
        f'main@{MAIN}',
        'NEWSROOM-N3-007 rozszerza istniejący `/autorzy/{slug}`',
        '- [x] zbudować NEWSROOM-N3-007 author-profile integration',
        'NEWSROOM-N3-008 public rollout config gate — **następny wykonywalny task**',
    ],
    'docs/NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md': [
        'Stan obecny po NEWSROOM-N3-007',
        'SharedAuthorTrafficSignSchemaService',
        'public_state_changed_at',
        'zintegrować ProfilePage/Article author identity',
    ],
    'docs/NEWSROOM-TEST-RELEASE-AND-ROLLBACK-RUNBOOK.md': [
        f'main@{MAIN}',
        'ContentAuthorProfileTest',
        '1055 passed / 19 540 assertions / 2 skipped',
        'N3-007 nie miało osobnego Browser Smoke',
    ],
}
for path, needles in checks.items():
    text = load(path)
    for needle in needles:
        if needle not in text:
            raise SystemExit(f'{path}: missing required final evidence: {needle}')

joined = '\n'.join(load(p) for p in checks)
for bad in ['N3-008/N3-008', 'N3-008 i N3-008', 'NEWSROOM-N3-008 author-profile integration']:
    if bad in joined:
        raise SystemExit(f'accidental history rewrite still present: {bad}')
if 'jego `mainEntity Person` należy wyrównać' in load('docs/NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md'):
    raise SystemExit('stale N3-007 author schema plan still present')
PY

rm -f .github/newsroom-n3-007-docs-finalize.sh
rm -f .github/workflows/newsroom-n3-007-docs-sync-pr.yml

git diff --check
changed="$(git diff --name-only | sort)"
expected="$(printf '%s\n' \
  .github/newsroom-n3-007-docs-finalize.sh \
  .github/workflows/newsroom-n3-007-docs-sync-pr.yml \
  docs/NEWSROOM-IMPLEMENTATION-BACKLOG.md \
  docs/NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md \
  docs/NEWSROOM-PUBLIC-UI-UX-SPEC.md \
  docs/NEWSROOM-SEO-DISTRIBUTION-AND-OBSERVABILITY.md \
  docs/NEWSROOM-TEST-RELEASE-AND-ROLLBACK-RUNBOOK.md | sort)"
test "$changed" = "$expected"

git config user.name github-actions[bot]
git config user.email 41898282+github-actions[bot]@users.noreply.github.com
git add -A
git commit -m 'docs(newsroom): finalize N3-007 documentation sync'
git push origin HEAD:docs/newsroom-n3-007-sync

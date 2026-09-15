# Newsroom SEO, Distribution and Observability Specification

## 1. Status

- Status: Proposed / implementation-ready specification
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Powiązane:
  - [SEO-CONTENT-ROADMAP.md](./SEO-CONTENT-ROADMAP.md)
  - [NEWSROOM-PUBLIC-UI-UX-SPEC.md](./NEWSROOM-PUBLIC-UI-UX-SPEC.md)
  - [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md)
- Data weryfikacji źródeł zewnętrznych: 2026-09-15
- Cel: zdefiniować techniczny i redakcyjny kontrakt Search/Discover/News/sitemap/feed/analytics przed implementacją.

---

## 2. Zasada nadrzędna

SEO newsroomu nie jest osobną warstwą doklejaną po implementacji.

Model danych, URL, publiczny renderer, obrazy, autorzy, źródła i workflow muszą od początku umożliwiać:

- stabilne canonical URLs,
- jednoznaczne daty,
- rozpoznawalnego autora i publishera,
- crawlable HTML,
- duże obrazy,
- structured data,
- sitemapy,
- pomiar publikacji i dystrybucji.

---

## 3. Zewnętrzne źródła referencyjne

Wymagania zmienne w czasie należy ponownie weryfikować przy wdrożeniu.

Aktualne źródła oficjalne:

- Google Article structured data:
  https://developers.google.com/search/docs/appearance/structured-data/article
- Google News sitemaps:
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/news-sitemap
- Google Discover:
  https://developers.google.com/search/docs/appearance/google-discover
- Google canonicalization:
  https://developers.google.com/search/docs/crawling-indexing/canonicalization
- Schema.org NewsArticle:
  https://schema.org/NewsArticle
- Schema.org Article:
  https://schema.org/Article

Nie utrwalamy w kodzie założeń o zewnętrznych limitach bez testu i dokumentacji aktualnej na moment implementacji.

---

## 4. Aktualnie potwierdzone zasady Google — 2026-09-15

### 4.1. Article structured data

Google obsługuje Article, NewsArticle i BlogPosting.

Article markup nie jest wymaganiem wejścia do Google News/Top stories, ale pomaga Google zrozumieć m.in.:

- typ treści,
- headline,
- author,
- images,
- datePublished,
- dateModified.

Dla newsroomu preferujemy:

- NewsArticle dla realnych newsów,
- Article dla analysis/report/explainer, jeśli NewsArticle nie jest semantycznie właściwe,
- BlogPosting nie jest domyślnym typem portalu informacyjnego.

### 4.2. News sitemap

Na dzień weryfikacji Google zaleca:

- aktualizować tę samą news sitemap przy publikacjach,
- umieszczać w news sitemap tylko artykuły utworzone w ostatnich 2 dniach,
- po 2 dniach usunąć URL z news sitemap albo usunąć news metadata,
- jedna news sitemap może mieć do 1000 news entries,
- publication_date ma być oryginalną datą publikacji na stronie, nie datą dodania do sitemap.

Te parametry muszą być ponownie sprawdzone w oficjalnej dokumentacji przy wdrożeniu.

### 4.3. Discover

Na dzień weryfikacji:

- nie ma specjalnego tagu ani schema wymaganych do kwalifikacji,
- content musi być indeksowany i zgodny z Discover policies,
- Google zaleca unikać clickbaitu i sensacyjności,
- duże obrazy są ważne,
- Google rekomenduje obrazy co najmniej 1200 px szerokości,
- max-image-preview:large powinien być włączony dla dużych previews,
- og:image lub schema image mogą pomóc wskazać preferowany obraz,
- logo lub obrazy mocno tekstowe nie powinny być domyślnym hero/preview,
- ruch Discover jest dodatkowy i może być niestabilny; nie traktujemy go jako gwarantowanego kanału.

---

## 5. Canonical URL policy

Każdy publiczny artykuł ma dokładnie jeden canonical URL.

Domyślnie:

- self-canonical,
- HTTPS,
- bez parametrów trackingowych,
- bez alternatywnego „mobile URL”,
- bez duplikacji między /aktualnosci i /poradniki.

Canonical nie służy jako metoda maskowania źle zaprojektowanego routingu.

---

## 6. URL policy

### 6.1. Artykuły

- /aktualnosci/{articleSlug}
- /poradniki/{articleSlug}

### 6.2. Kategorie

Rekomendowany wariant technicznie jednoznaczny:

- /aktualnosci/kategoria/{categorySlug}

Alternatywa krótsza wymaga reserved-slug policy.

### 6.3. Slug

Slug:

- lower case,
- ASCII transliteration,
- hyphens,
- opisowy,
- bez daty,
- bez kategorii powtórzonej w slugu, jeśli nie daje wartości.

---

## 7. Slug changes

Zmiana sluga opublikowanego artykułu:

1. tworzy 301,
2. aktualizuje wszystkie wewnętrzne linki,
3. canonical nowej strony wskazuje nowy URL,
4. sitemap pokazuje tylko nowy URL,
5. stary URL nie pozostaje 200.

Unikać zmian slugów bez realnej potrzeby.

---

## 8. Duplicate prevention

Nie publikować:

- tego samego newsa jako /aktualnosci/x i /poradniki/x,
- osobnych stron tylko dla wariantu title,
- filtrowanych category pages jako indeksowalne kombinacje parametrów,
- paginacji z niepoprawnym canonical do page 1.

---

## 9. Robots policy

### Published

Domyślnie:

index,follow,max-image-preview:large

### Draft / in_review / preview

noindex,nofollow lub noindex,follow zależnie od preview transportu; przede wszystkim brak publicznego crawlable URL.

### Scheduled

Nie pojawia się publicznie przed czasem.

### Archived

Decyzja per materiał:

- 200 + index dla wartości archiwalnej,
- 200 + noindex tylko gdy istnieje uzasadnienie,
- 404/410 dla usuniętego contentu,
- 301 tylko do rzeczywistego następcy.

---

## 10. Page title

Zasady:

- oparty na headline,
- marka dodawana konsekwentnie,
- nie duplikować category + title bez potrzeby,
- brak keyword stuffing,
- title SSR w initial HTML.

Fallback:

{seo_title ?? title} + brand pattern

Brand pattern powinien mieć jedno źródło konfiguracyjne.

---

## 11. Meta description

- ręczna seo_description preferowana dla ważnych materiałów,
- fallback może bazować na leadzie,
- nie może zawierać HTML,
- nie powinna być identyczna na wielu artykułach.

---

## 12. OpenGraph

Minimum:

- og:locale pl_PL
- og:type article
- og:title
- og:description
- og:url
- og:image
- og:image:alt
- image dimensions, jeśli znane
- article:published_time
- article:modified_time

Dla newsroomu og:type pozostaje article również gdy schema @type = NewsArticle.

---

## 13. Twitter/X metadata

Minimum:

- summary_large_image dla artykułu z właściwym hero,
- title,
- description,
- image,
- image alt.

Nie wymagamy integracji z X API.

---

## 14. Preferred image strategy

Każdy ważny artykuł powinien mieć source asset, focal point i image variants nadające się do:

- page hero,
- lead card,
- standard/compact cards,
- OpenGraph,
- structured data.

Preferowane cropy:

- 16:9,
- 4:3,
- 1:1, jeśli pipeline to uzasadnia.

Nie blokujemy v1 koniecznością ręcznego przygotowania wszystkich cropów. Preferowany jest deterministyczny pipeline generujący warianty z jednego źródła i focal point.

---

## 15. Discover image baseline

Dla materiałów, które mają szansę na dystrybucję wizualną:

- szerokość minimum 1200 px,
- wysoka rozdzielczość,
- znaczący subject zachowany w landscape,
- brak dominującego tekstu w obrazie,
- brak generycznego logo zamiast ilustracji.

Jeżeli asset nie spełnia baseline, artykuł nadal może być publikowany; jest to dystrybucyjny quality signal, nie globalny DB invariant.

---

## 16. Structured data — NewsArticle

Rekomendowany JSON-LD:

- @context
- @type: NewsArticle
- headline
- description
- image
- datePublished
- dateModified
- mainEntityOfPage
- author Person
- publisher Organization
- articleSection
- inLanguage
- url

Opcjonalnie:

- about,
- keywords,
- isPartOf,
- publishingPrinciples,
- correction, jeśli schema support/policy jest potwierdzone na moment implementacji.

---

## 17. datePublished

Źródło:

first_published_at

Nigdy:

- updated_at,
- sitemap generation time,
- data „odświeżenia SEO”.

---

## 18. dateModified

Źródło:

last_substantive_update_at lub kontrolowany fallback.

Nie zmieniać dateModified tylko dlatego, że:

- przebudowano cache,
- zmieniono niewidoczne pole techniczne,
- wykonano touch rekordu.

---

## 19. Author schema

Author:

- @type Person,
- name,
- url do /autorzy/{slug}.

Profil autora powinien być publiczny i spójny.

Nie tworzymy fikcyjnych autorów typu „Redakcja”, jeśli nie ma publicznej strony i jasnej odpowiedzialności. Jeśli istnieje author organizacyjny, modelujemy go jawnie jako Organization zgodnie z realnym stanem.

---

## 20. Publisher schema

Przed uruchomieniem newsroomu trzeba zamknąć N0 branding.

Publisher:

- jedna kanoniczna nazwa PrawkoNaRaz,
- jeden canonical organization URL,
- jedno logo,
- zgodność z homepage Organization schema.

Nie może równolegle występować „Orły na Drodze” jako publisher tego samego serwisu bez jawnej relacji marki.

---

## 21. Organization source of truth

Rekomendacja:

utworzyć jeden serwis/config np. SiteOrganizationSchema lub config/brand.php, używany przez:

- homepage,
- article schema,
- author pages,
- legal content,
- future organization metadata.

Nie kopiować nazwy/logo w wielu kontrolerach.

---

## 22. Breadcrumb schema

Newsroom:

- Home
- Aktualności
- Kategoria opcjonalnie
- Artykuł

Breadcrumb visual i BreadcrumbList muszą opisywać ten sam logiczny path.

---

## 23. Article section

articleSection powinno odzwierciedlać nazwę głównej kategorii, nie listę tagów.

---

## 24. MainEntityOfPage

Wskazuje canonical article WebPage.

Nie wskazuje homepage.

---

## 25. News sitemap endpoint

Rekomendowany endpoint:

/sitemaps/news.xml

Dodany do istniejącego /sitemap.xml index.

News sitemap:

- tylko type=news,
- tylko status published,
- published w oknie zgodnym z aktualnym Google requirement,
- max entries zgodnie z aktualną dokumentacją,
- generation cache krótkie.

---

## 26. Standard article sitemap

Niezależnie od news sitemap potrzebujemy długoterminowej sitemap artykułów.

Rekomendacja:

/sitemaps/articles.xml

Zawiera wszystkie indeksowalne:

- news,
- guide,
- explainer,
- analysis,
- report.

Starszy news znika z news sitemap, ale zostaje w normalnej sitemap, jeśli nadal indeksowalny.

---

## 27. Sitemap index

Istniejący sitemap index powinien po wdrożeniu wskazywać:

- articles.xml
- news.xml

Nie tworzymy kolejnego niezależnego sitemap index dla newsroomu.

---

## 28. lastmod

lastmod:

- only if meaningful,
- oparty o ostatnią istotną zmianę publicznej treści,
- nie o generation time.

---

## 29. RSS / Atom

V1 rekomendacja:

/aktualnosci/feed.xml

Minimum:

- title,
- link,
- guid stable,
- published date,
- updated date,
- summary,
- author jeśli format wspiera.

Feed zawiera najnowsze publiczne artykuły newsowe i ewentualnie inne typy po jawnej decyzji.

---

## 30. Feed caching

- cache 5–15 min jako punkt startowy,
- invalidate po publish/archive,
- poprawny content-type,
- ETag/Last-Modified, jeśli łatwo wspierane.

---

## 31. Google News eligibility

Nie projektujemy feature flag „Google News accepted”.

Eligibility/visibility jest kontrolowana zewnętrznie i może się zmieniać.

System ma:

- spełniać techniczne standardy,
- publikować jakościowy content,
- umożliwiać monitoring.

Nie obiecujemy pojawienia się w Google News/Top stories/Discover.

---

## 32. Search Console

Po rollout:

- submit sitemap index,
- monitor pages/indexing,
- monitor article URLs,
- Rich Results/URL Inspection sample,
- Discover report jeśli dane się pojawią,
- Search Performance per newsroom path.

---

## 33. Analytics event model

Minimalne eventy:

- newsroom_article_view
- newsroom_related_article_click
- newsroom_related_question_click
- newsroom_related_legal_click
- newsroom_related_sign_click
- newsroom_product_cta_click
- newsroom_source_click
- newsroom_category_click
- newsroom_pagination_click

---

## 34. Event fields

Standard:

- article_id
- article_type
- category_slug
- module
- position
- destination_path
- auth_state, jeśli dozwolone przez analytics policy
- timestamp po stronie systemu analytics

Nie wysyłać treści body ani danych osobowych w event names/params.

---

## 35. Module naming

Stabilne wartości:

- lead
- secondary
- latest
- category_przepisy
- category_egzaminy
- guides
- product_bridge
- related_articles
- related_questions
- sources

Nie używać dynamicznych tytułów artykułów jako module identifier.

---

## 36. Article view

View event nie służy sam w sobie do „najczęściej czytane”, dopóki nie mamy:

- bot filtering,
- dedup policy,
- window,
- data retention,
- stable counting.

---

## 37. Popular content

Przed wdrożeniem rankingu popularności potrzebny osobny kontrakt.

Minimalna definicja później:

score = qualified_views in rolling window

z:

- wykluczeniem znanych botów,
- minimalnym czasem/engagement opcjonalnie,
- anti-refresh abuse.

Do v1 nie blokuje launchu.

---

## 38. Scroll depth

Optional.

Jeśli wdrażamy:

- 25/50/75/90,
- jeden event per threshold/session,
- nie wysyłamy eventu na każdy scroll.

---

## 39. Web performance

Newsroom jest content-first.

Cele operacyjne:

- LCP <= 2.5 s na 75 percentylu jako target,
- INP <= 200 ms,
- CLS <= 0.1,
- TTFB monitorowany osobno.

To są cele jakościowe, nie gwarancje bez danych produkcyjnych.

---

## 40. LCP discipline

Najczęstszy LCP:

- lead image na homepage,
- hero na article page,
- H1 jeśli obraz nie jest above-fold.

Zasady:

- hero dimensions,
- no lazy for actual LCP,
- preload/fetchpriority tylko dla realnego LCP assetu,
- nie preloadować wielu obrazów.

---

## 41. CLS discipline

- width/height lub aspect-ratio dla obrazów,
- reserved space dla hero,
- brak późno wstrzykiwanych top banners,
- breaking strip renderowany server-side z określoną wysokością.

---

## 42. JS budget

V1 newsroom nie wymaga SPA bundle.

public-content.ts rozszerzamy ostrożnie.

Nowe JS tylko dla:

- auth drawer,
- nav,
- analytics delegation,
- drobnych controls.

Nie potrzebujemy framework runtime do czytania article page.

---

## 43. Crawlability

- links jako href,
- body w HTML,
- pagination SSR,
- category links crawlable,
- no JS-only navigation.

---

## 44. Internal linking graph

Każdy article może linkować do:

- category,
- topic/dossier,
- author,
- legal content,
- questions,
- signs,
- related articles,
- product CTA.

Monitorować orphan articles.

---

## 45. Orphan detection

Rekomendowana komenda QA:

newsroom:audit-links

Raportuje:

- published article bez wejściowego linku z huba/category,
- broken related links,
- draft link targets,
- redirect chains.

Może wejść w N5/N6.

---

## 46. Source links and SEO

External source links:

- normalne crawlable links domyślnie,
- sponsored/ugc rel tylko jeśli faktycznie dotyczą,
- nofollow nie jest domyślnym sposobem linkowania do oficjalnych źródeł.

---

## 47. Social distribution

V1 nie wymaga automatycznej publikacji przez API.

System powinien zapewnić:

- dobry OG preview,
- stabilny canonical,
- łatwe copy link.

Automatyzacja social jest późniejsza.

---

## 48. UTM policy

Wewnętrzne linki nie używają UTM.

Zewnętrzne kampanie mogą używać UTM zgodnie z konwencją analytics.

Canonical ignoruje parametry kampanii.

---

## 49. IndexNow

Repo ma już model IndexNowUrlSubmission.

Po analizie istniejącego pipeline można podłączyć publish/update artykułu do IndexNow.

Wymagania:

- idempotentne,
- queue/retry,
- nie blokuje publikacji,
- nie zgłasza preview/draft.

Nie zakładamy, że IndexNow steruje Google indexing.

---

## 50. Monitoring techniczny

Minimum:

- 5xx rate na newsroom routes,
- response time,
- cache hit,
- article 404 count,
- sitemap generation errors,
- feed generation errors,
- scheduled publish failures.

---

## 51. Monitoring redakcyjny

- scheduled overdue,
- published without sources,
- freshness overdue,
- broken primary source,
- breaking expired but still flagged,
- article without image quality baseline.

---

## 52. Search monitoring

Po launch:

- indexed vs submitted,
- crawl errors,
- canonical mismatches,
- structured data issues,
- impressions/clicks per category,
- Discover exposure, jeśli wystąpi.

---

## 53. Alerting

Nie potrzebujemy alertu na każdy 404 od bota.

Alert-worthy:

- sitemap 500,
- scheduler publish failure,
- masowy wzrost 5xx,
- zero articles w feed przy istniejących publikacjach,
- canonical host mismatch,
- robots accidentally noindex newsroom.

---

## 54. Release SEO checklist

Przed pierwszym production launch:

- [ ] publisher branding ujednolicony
- [ ] canonical domain prawkonaraz.pl
- [ ] robots pozwala crawl
- [ ] /aktualnosci 200
- [ ] sample article 200
- [ ] draft unavailable publicly
- [ ] title/meta
- [ ] OG
- [ ] NewsArticle
- [ ] BreadcrumbList
- [ ] author URL
- [ ] sitemap articles
- [ ] news sitemap
- [ ] feed
- [ ] Search Console sitemap submit
- [ ] URL Inspection sample
- [ ] max-image-preview:large
- [ ] hero >= recommended baseline dla sample Discover-target article

---

## 55. Rich Results / validation

Przed launch co najmniej kilka realnych article pages testujemy:

- Rich Results Test, jeśli typ jest wspierany w narzędziu,
- schema validator,
- URL Inspection po produkcyjnym deploy.

Nie uznajemy samego „JSON parsuje się” za pełne SEO QA.

---

## 56. Environment rules

Local/staging:

- nie mogą przypadkiem indeksować się jako produkcyjne,
- canonical nie może wskazywać local host w wygenerowanej produkcji,
- testy canonical bazują na APP_URL/config.

Production:

- canonical host prawkonaraz.pl.

---

## 57. 404 / 410

404:

- URL nigdy nie istniał lub nie ma celowego gone state.

410:

- celowo usunięty materiał bez zastępcy.

301:

- tylko gdy istnieje rzeczywisty odpowiednik/następca.

Nie przekierowujemy wszystkich usuniętych newsów do /aktualnosci.

---

## 58. Pagination SEO

Category/list pages:

- self-canonical per page,
- crawlable next pages,
- title może zawierać „strona N”,
- nie canonicalizować każdej strony do page 1.

---

## 59. Tags i topics

### Tags

V1:

- brak indeksowalnych tag pages domyślnie.

Tag służy lekkiej klasyfikacji.

### Topics / dossier

Topic jest osobnym, ręcznie zarządzanym hubem.

Może być indeksowalny tylko gdy:

- status = published,
- ma własny opis i meta,
- ma wystarczający, realny corpus,
- nie duplikuje kategorii/tag page,
- featured article i lista materiałów są publiczne.

Topic nie powstaje automatycznie z taga.

---

## 60. Local WORD SEO

Deferred.

Nie generujemy setek stron miasto/WORD bez realnej treści.

Warunki przyszłego rollout:

- verified entity,
- unikalne dane,
- aktualizowalność,
- editorial ownership.

---

## 61. Data reports SEO

Raport własny powinien mieć:

- methodology,
- period,
- author,
- stable URL,
- citeable charts/tables,
- clear source.

Raport może mieć większą wartość linkową niż dziesiątki cienkich newsów.

---

## 62. Content freshness and search

Nie zmieniamy dateModified tylko po to, by wyglądać świeżo.

Aktualizacja musi odpowiadać realnej zmianie treści.

---

## 63. Redirect monitoring

Po slug change:

- test old -> 301 -> new 200,
- brak chain,
- internal links zaktualizowane,
- old URL usunięty z sitemap.

---

## 63.1. Audio i AI derivatives — SEO policy

Przyszły odsłuch, transkrypcja, skrót AI lub „zapytaj o artykuł” nie tworzą automatycznie osobnych indeksowalnych stron.

Zasady:

- canonical pozostaje na źródłowym artykule,
- generowany skrót nie może być alternatywnym thin URL,
- audio player nie blokuje crawlable HTML body,
- transkrypcja audio pochodzącego z tego samego artykułu nie powinna dublować pełnego body w osobnym URL,
- funkcje AI nie zmieniają dateModified artykułu, jeśli sam artykuł nie został merytorycznie zaktualizowany.

---

## 64. Preferred source references w dokumentacji

Każdy zewnętrzny wymóg Google w kodzie powinien mieć:

- link do oficjalnej dokumentacji w docblock/test description lub tym dokumencie,
- datę weryfikacji w dokumentacji projektowej.

Nie linkujemy do przypadkowego SEO bloga jako źródła normatywnego.

---

## 65. Definition of Done SEO/Distribution v1

- canonical URLs stabilne,
- article metadata kompletne,
- NewsArticle/Article semantycznie poprawne,
- publisher/author spójni,
- articles sitemap działa,
- news sitemap działa zgodnie z aktualnymi wymaganiami,
- feed działa,
- images spełniają ustalone baseline i respektują focal point/crop policy,
- opublikowane topic pages spełniają kryteria jakości,
- max-image-preview:large włączone dla indeksowalnych artykułów,
- analytics event model działa,
- produkcyjne 5xx/scheduler/sitemap są monitorowane,
- Search Console ma sitemap index,
- nie ma draft leakage.

---

## 66. Stan implementacji

Obecnie:

- public-content layout ma canonical/OG/Twitter/article times support,
- istnieją sitemapy innych content types,
- istnieje IndexNowUrlSubmission,
- author pages istnieją,
- newsroom-specific Article schema/news sitemap/feed nie istnieją,
- /aktualnosci jest placeholderem.

---

## 67. Pozostałe zadania

- [ ] ujednolicić Organization/publisher,
- [ ] wdrożyć ContentArticleSeoService,
- [ ] wdrożyć ContentArticleSchemaService,
- [ ] wdrożyć article sitemap,
- [ ] wdrożyć news sitemap,
- [ ] wdrożyć feed,
- [ ] wdrożyć analytics hooks/events,
- [ ] wdrożyć topic SEO dla faktycznie publikowanych dossier,
- [ ] zweryfikować crop/OG output z focal point,
- [ ] podłączyć monitoring,
- [ ] wykonać production Search Console verification.

---

## 68. Historia zmian

### 2026-09-15 — v0.2

- rozdzielono nieindeksowalne tags od ręcznie publikowanych topic/dossier,
- rozszerzono image strategy o focal point i warianty kart,
- zapisano SEO policy dla przyszłych audio/AI derivatives bez tworzenia duplikujących URL,
- doprecyzowano topic/crop checks w Definition of Done.

### 2026-09-15 — v0.1

- utworzono SEO/distribution contract,
- zweryfikowano aktualne Google Article, News sitemap i Discover guidelines,
- zapisano current 2-day/1000-entry news sitemap constraints jako verify-at-implementation,
- zdefiniowano schema, canonical, image, feed, analytics i observability,
- rozdzielono długoterminową article sitemap od krótkiego news sitemap.

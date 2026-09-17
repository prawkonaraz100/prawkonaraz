# Newsroom SEO, Distribution and Observability Specification

## 1. Status

- Status: Canonical specification + live implementation status
- Dokument nadrzędny: [NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md](./NEWSROOM-MEDIA-PORTAL-ARCHITECTURE.md)
- Powiązane:
  - [SEO-CONTENT-ROADMAP.md](./SEO-CONTENT-ROADMAP.md)
  - [SEO-SITEMAP-REPAIR-PLAN.md](./SEO-SITEMAP-REPAIR-PLAN.md) — nadrzędny dla sposobu produkcyjnego dostarczania sitemap/robots
  - [SEO-ENTERPRISE-INTERNAL-LINKING-ROADMAP-V2.md](./SEO-ENTERPRISE-INTERNAL-LINKING-ROADMAP-V2.md) — nadrzędny dla istniejącego question relation graphu
  - [NEWSROOM-PUBLIC-UI-UX-SPEC.md](./NEWSROOM-PUBLIC-UI-UX-SPEC.md)
  - [NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md](./NEWSROOM-DATA-MODEL-AND-DOMAIN-SPEC.md)
- Data weryfikacji źródeł zewnętrznych: 2026-09-16
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
- Google sitemap limits / best practices:
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap
- Google site names:
  https://developers.google.com/search/docs/appearance/site-names
- Google Organization structured data:
  https://developers.google.com/search/docs/appearance/structured-data/organization
- Google ProfilePage structured data:
  https://developers.google.com/search/docs/appearance/structured-data/profile-page
- Google publication dates:
  https://developers.google.com/search/docs/appearance/publication-dates
- Google crawl efficiency / HTTP caching:
  https://developers.google.com/crawling/docs/crawl-budget
- Google Preferred Sources:
  https://developers.google.com/search/docs/appearance/preferred-sources
- Google News policies / transparency:
  https://support.google.com/news/publisher-center/answer/6204050
- Google News article page best practices:
  https://support.google.com/news/publisher-center/answer/9607104
- Google News automatically generated publication pages:
  https://support.google.com/news/publisher-center/answer/15898024
- IndexNow protocol:
  https://www.indexnow.org/documentation
- Schema.org NewsArticle:
  https://schema.org/NewsArticle
- Schema.org Article:
  https://schema.org/Article

Nie utrwalamy w kodzie założeń o zewnętrznych limitach bez testu i dokumentacji aktualnej na moment implementacji.

---

## 4. Aktualnie potwierdzone zasady Google — 2026-09-16

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

Aktualna dokumentacja Google Article zaleca zwięzły `headline`, ponieważ długie tytuły mogą być skracane na urządzeniach; nie podaje obecnie stałego limitu 110 znaków. Dlatego długość headline jest kontrolą redakcyjną/warningiem, a nie trwałym DB invariantem.

### 4.2. News sitemap

Na dzień weryfikacji Google zaleca:

- aktualizować tę samą news sitemap przy publikacjach,
- umieszczać w news sitemap tylko artykuły utworzone w ostatnich 2 dniach,
- po 2 dniach usunąć URL z news sitemap albo usunąć news metadata,
- jedna news sitemap może mieć do 1000 news entries,
- publication_date ma być oryginalną datą publikacji na stronie, nie datą dodania do sitemap,
- wymagane są news:name, news:language, news:publication_date i news:title,
- news:title ma odpowiadać widocznemu tytułowi artykułu i nie zawierać nazwy autora/publikacji/daty.

Te parametry muszą być ponownie sprawdzone w oficjalnej dokumentacji przy wdrożeniu.

### 4.3. Site name / Organization / crawl efficiency

Na dzień weryfikacji:

- site name jest sygnałem domeny/subdomeny, nie osobnego katalogu /aktualnosci,
- WebSite z name/url powinien mieć kanoniczne źródło na homepage i nie należy tworzyć konkurencyjnych WebSite nodes,
- Organization logo powinno być crawlable/indexable i mieć co najmniej 112x112 px,
- pojedyncza sitemap ma limit 50 000 URL lub 50 MB nieskompresowanego XML,
- serwis przy wzroście powinien wspierać HTTP conditional requests / 304 dla niezmienionych zasobów, szczególnie sitemap/feed.

### 4.4. Discover

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

V1 używa twardego self-canonical wyliczanego z route family + slug. CMS nie ma ręcznego canonical override.

- self-canonical,
- HTTPS,
- bez parametrów trackingowych,
- bez alternatywnego „mobile URL”,
- bez duplikacji między /aktualnosci i /poradniki.

Canonical nie służy jako metoda maskowania źle zaprojektowanego routingu.

Enterprise rule: sygnały muszą być zbieżne. Dla canonical URL:

- internal links wskazują canonical,
- sitemap zawiera canonical,
- redirect source nie pozostaje równoległym 200,
- OG url i schema url/mainEntityOfPage zgadzają się z canonical,
- nie tworzymy sprzecznego noindex/canonical/sitemap zestawu.

Rel=canonical jest sygnałem, nie gwarancją wyboru przez wyszukiwarkę; po rollout porównujemy declared i Google-selected canonical w Search Console.

Cross-domain/cross-URL canonical, jeśli kiedyś będzie potrzebny dla syndication, wymaga osobnej decyzji i dedykowanego workflow. Nie dodajemy go jako pole redaktora „na zapas”.

---

## 6. URL policy

### 6.1. Artykuły

- /aktualnosci/{articleSlug}
- /poradniki/{articleSlug}

### 6.2. Kategorie

Rekomendowany wariant technicznie jednoznaczny:

- /aktualnosci/kategoria/{categorySlug}

Alternatywa krótsza wymaga reserved-slug policy.

### 6.3. Topics / dossier

- /aktualnosci/temat/{topicSlug}

Topic jest jawnie opublikowanym hubem redakcyjnym, nie automatyczną stroną taga.

### 6.4. Język / hreflang

V1 jest polskojęzyczne.

- HTML/schema używają właściwego języka `pl` / `pl-PL` zależnie od kontraktu,
- nie dodajemy pustych ani sztucznych hreflang variants,
- hreflang pojawia się dopiero, gdy istnieją realne, równoważne wersje językowe z własnymi canonical URLs.

### 6.5. Route family

- news/explainer/analysis/report -> `/aktualnosci/{slug}`
- guide -> `/poradniki/{slug}`

Po pierwszej publikacji zwykła zmiana typu nie może przenieść artykułu między tymi rodzinami URL. Wewnętrzna zmiana typu w rodzinie newsroom nie zmienia canonical path.

Przyszła kontrolowana migracja route family musi być traktowana jak site move pojedynczego URL: jeden 301, zaktualizowane internal links, sitemap tylko nowego URL, brak chain.

### 6.6. Slug

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

Aktualny stan implementacji po NEWSROOM-N3-006: publiczny old article path jest rozwiązywany dopiero po nieudanym current-canonical lookup i zwraca dokładnie jeden 301 wyłącznie wtedy, gdy zapisany redirect ma status 301 oraz `to_path` równe bieżącemu canonical route family + slug. Stale/malformed/self-loop history failuje do 404. Redirect target jest path-only, więc query params/tracking nie są kopiowane. Internal-link i sitemap migration pozostają osobnymi downstream obowiązkami; N3-006 nie deklaruje ich jako wykonanych.

---

## 8. Duplicate prevention

Nie publikować:

- tego samego newsa jako /aktualnosci/x i /poradniki/x,
- osobnych stron tylko dla wariantu title,
- filtrowanych category pages jako indeksowalne kombinacje parametrów,
- paginacji z niepoprawnym canonical do page 1.

---

### 8.1. Robots/sitemap delivery compatibility

Repo ma równolegle statyczny `public/robots.txt` i route `RobotsController`. Zgodnie z `SEO-SITEMAP-REPAIR-PLAN.md` produkcyjnie preferowany jest statyczny plik oraz jawna weryfikacja Nginx/Cloudflare.

Globalny `NEWSROOM_PUBLIC_ENABLED=false` jest wdrożony przez N3-008 jako pre-launch/dark-deploy gate. `config/newsroom.php` ma bezpieczny default `false`, a `NewsroomPublicGate` centralizuje decyzję. Przy `false` publiczne article/guide detail oraz historyczne old-path redirecty failują do 404 przed lookupem/redirect resolverem; `/aktualnosci` i `/poradniki` pozostają linkowanymi pre-launch placeholder pages z `X-Robots-Tag: noindex, follow`. Profil autora nie emituje newsroom publications, a `SeoSitemapBuilder` nie uwzględnia newsroom-only author eligibility ani newsroomowego freshness contribution. Istniejący `IndexNowUrlCollector` dodatkowo odrzuca namespace `/aktualnosci` i `/poradniki` przy wyłączonym gate. Feed, article/news sitemap oraz reverse-link surfaces nadal nie istnieją i muszą konsumować ten sam gate dopiero przy wdrożeniu w N4/N5.

Ta flaga jest przede wszystkim **pre-launch/dark-deploy gate**. Po pierwszym publicznym rollout nie używamy długotrwale `false` jako technicznego rollbacku dla już indeksowanych article URLs, jeśli skutkiem byłyby masowe 404. Dla krótkiej awarii technicznej preferujemy kontrolowane 503/Retry-After lub rollback kodu zachowujący publiczne routes; dla pojedynczej błędnej treści używamy `withdrawn`.

Newsroom nie usuwa kontrolera ani nie zmienia sposobu serwowania robots w zwykłym PR implementacyjnym. Osobny hardening może później usunąć duplikat dopiero po:

- potwierdzeniu faktycznej produkcyjnej odpowiedzi,
- testach webserver/CDN,
- sprawdzeniu Sitemap directive,
- bezpiecznym deploy/rollback.

---

## 9. Robots policy

### Published

Domyślnie:

index,follow,max-image-preview:large

### Draft / in_review / preview

Draft/in_review nie mają publicznego crawlable URL. Admin preview jest authenticated-only, `private, no-store` i ma `noindex,nofollow`.

### Scheduled

Nie pojawia się publicznie przed czasem.

### Needs review

Canonical detail URL pozostaje publiczny i może pozostać indexable zgodnie z robots policy, ale materiał jest wyłączony z aktywnej dystrybucji (home/category/topic latest/feed/news sitemap) do czasu ponownego review.

Jeśli pozostaje indexable:

- public page pokazuje transparentny review-state banner,
- musi zachować crawlable inbound (v1: oznaczona sekcja author profile),
- nie trafia do promotional reverse-link modules.

### Archived

V1 ma jedną deterministyczną semantykę:

- jeśli artykuł był wcześniej opublikowany, canonical detail URL pozostaje `200`,
- archive usuwa materiał z aktywnej dystrybucji: home/latest/category/topic listings, feed i news sitemap,
- domyślnie historyczny artykuł pozostaje indexable; kontrolowane `noindex` można ustawić tylko z merytorycznego powodu,
- standardowa article sitemap zawiera archived URL tylko jeśli nadal jest indexable,
- archive samo w sobie nigdy nie generuje 301/404/410.

301 jest osobnym use case dla rzeczywistego następcy. 404/410 jest osobnym use case dla faktycznie usuniętego/gone URL i nie jest wyprowadzane z workflow_status=archived.

Artykuł, który nigdy nie był publiczny, nie staje się publicznym URL tylko przez ustawienie archived.

### Withdrawn

- rekord pozostaje w backoffice,
- dawny canonical path zwraca 410 Gone, jeśli nie istnieje rzeczywisty następca,
- URL znika ze wszystkich sitemap/feed/internal distribution,
- nie emitujemy Article/NewsArticle schema z wycofaną treścią na stronie 410,
- 301 jest używany zamiast 410 tylko przy realnym następcy.

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
- og:site_name z kanonicznego site identity
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

Publiczne URL-e obrazów używane w OG/schema muszą być:

- stabilne,
- bez wygasających podpisów,
- dostępne bez auth/cookies,
- crawlable i indexable,
- w formacie obsługiwanym przez wyszukiwarki.

Dla structured data, jeśli istnieją faktycznie wygenerowane warianty, preferujemy zestaw reprezentatywnych obrazów 1:1, 4:3 i 16:9 zamiast jednego przypadkowego cropu. Nie deklarujemy wariantu, który fizycznie nie istnieje.

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

## 16. Structured data — NewsArticle / Article graph

Nie budujemy isolated JSON-LD object z kopiami publishera/autora. Newsroom korzysta z istniejącego wzorca graph oraz stabilnych @id.

Minimalny graph article page:

- WebSite -> `https://prawkonaraz.pl/#website`
- Organization -> `https://prawkonaraz.pl/#organization`
- WebPage -> `{canonical}#webpage`
- NewsArticle lub Article -> `{canonical}#article`
- Person autora -> `/autorzy/{slug}#person`
- BreadcrumbList -> `{canonical}#breadcrumb`
- ImageObject dla realnych obrazów

Article node:

- @type: NewsArticle dla news albo Article dla pozostałych właściwych typów,
- headline,
- description,
- image jako references/URL-e realnych wariantów,
- datePublished,
- dateModified,
- mainEntityOfPage -> WebPage @id,
- author -> Person @id,
- publisher -> Organization @id,
- articleSection,
- inLanguage: pl-PL,
- url,
- isPartOf -> WebSite @id.

WebPage:

- url canonical,
- isPartOf -> WebSite,
- breadcrumb -> BreadcrumbList,
- primaryImageOfPage, jeśli istnieje,
- mainEntity -> Article.

Opcjonalnie tylko gdy odpowiada treści:

- about,
- keywords,
- publishingPrinciples.

Nie deklarujemy properties tylko dlatego, że istnieją w schema.org; markup musi odpowiadać widocznym i prawdziwym danym.

### 16.1. CollectionPage graph dla hubów

`/aktualnosci`, category pages, topic/dossier i `/poradniki` korzystają z istniejącego graph pattern analogicznego do innych publicznych hubów:

- WebSite / Organization przez stabilne @id,
- CollectionPage/WebPage dla bieżącego canonical,
- BreadcrumbList,
- ItemList dla widocznego, crawlable zestawu artykułów, gdy jest semantycznie użyteczny.

Hub nie udaje `NewsArticle`. ItemList references prowadzą do canonical article URLs i odpowiadają faktycznie widocznym elementom strony.

Nie traktujemy CollectionPage/ItemList jako obietnicy rich result; celem jest spójna semantyka entity graph.

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

Kontrakt v1:

`dateModified = last_substantive_update_at ?? first_published_at`

Widoczny label „Aktualizacja” renderujemy tylko, jeśli `last_substantive_update_at` istnieje i oznacza zmianę późniejszą od pierwszej publikacji.

Nie zmieniać dateModified tylko dlatego, że:

- przebudowano cache,
- zmieniono niewidoczne pole techniczne,
- wykonano touch rekordu.

### 18.1. Spójność dat

- visible publication/update date i JSON-LD muszą opisywać ten sam moment,
- serializacja structured data używa poprawnego offsetu strefy Europe/Warsaw wraz z DST,
- future date jest niedozwolona na publicznym artykule,
- `effective_from`/data wydarzenia nie może zostać pomylona z datePublished/dateModified.

---

## 19. Author schema i ProfilePage

Newsroom reużywa istniejący `ContentAuthor`, route `/autorzy/{slug}` oraz istniejący wzorzec `ProfilePage -> Person`.

Article author:

- @type Person przez stabilny @id `/autorzy/{slug}#person`,
- name = samo imię/nazwisko autora, bez roli/brandingu w name,
- url = publiczny profil autora.

ProfilePage / Person może zawierać wyłącznie prawdziwe dane istniejącego autora:

- jobTitle,
- bio/description,
- image,
- sameAs,
- worksFor -> canonical Organization.

Nie tworzymy fikcyjnych autorów typu „Redakcja”, jeśli nie ma publicznej strony i jasnej odpowiedzialności. Nie dopisujemy credentials/ekspertyzy, których system i publiczny profil nie potwierdzają.

Po wdrożeniu newsroomu publiczny profil autora i sitemap lastmod autorów muszą uwzględniać również opublikowane ContentArticle, a nie tylko starsze moduły contentowe.

Stan obecny po NEWSROOM-N3-007: `ContentAuthorSchemaService` jest współdzielonym builderem `ProfilePage -> Person`, a `SharedAuthorTrafficSignSchemaService` deleguje do niego istniejących consumers. ProfilePage i Article używają identycznego stabilnego `/autorzy/{slug}#person` oraz `worksFor -> /#organization`. `SeoSitemapBuilder::authorUrls()` kwalifikuje również autorów przez indeksowalne Newsroom articles w aktywnych kategoriach i używa ich `public_state_changed_at` dla newsroomowego author lastmod zamiast technicznego article `updated_at`. Nie utworzono drugiego ProfilePage.

---

## 20. Publisher schema

NEWSROOM-N0-001 zamknął domenowy branding/site identity foundation. Newsroom ma reużywać ten sam kanoniczny publisher graph.

Publisher:

- jedna kanoniczna nazwa PrawkoNaRaz,
- jeden canonical organization URL,
- jedno logo,
- zgodność z homepage Organization schema.

Nie może równolegle występować „Orły na Drodze” jako publisher tego samego serwisu bez jawnej relacji marki.

---

## 21. Site identity i Organization source of truth

### 21.1. Stan istniejący w repo

Repo posiada:

- `config/content.php -> organization` jako kanoniczne dane organizacji,
- `SchemaIds::organization()` -> root `/#organization`,
- `SchemaIds::website()` -> root `/#website`,
- `SchemaRenderer` i graph pattern,
- współdzielony `SiteIdentitySchema` dla Organization/WebSite,
- homepage oraz główne public-question/traffic-sign/legal-content graph services korzystające z tego samego buildera,
- wspólny `og:site_name` z organization config.

Legacy hardcode „Orły na Drodze” został usunięty z homepage w NEWSROOM-N0-001.

### 21.2. Implementacja N0-001

N0-001:

- zachował `config/content.php['organization']` jako kanoniczne dane brand/organization,
- wyekstrahował wspólny site identity/schema builder zamiast dalszego kopiowania Organization/WebSite,
- zachował stabilne IDs `/#organization` i `/#website`,
- przeniósł homepage na te same dane i IDs,
- usunął stare hardcoded logo/name/alt,
- dodał WebSite `alternateName` z rzeczywistego `app.name`,
- zapisał publiczne logo jako ImageObject z url/contentUrl i potwierdzonymi wymiarami 256×256.

Nie tworzymy równoległego brand configu. Ewentualna migracja istniejącego `config/content.php['organization']` wymaga osobnej, jawnej decyzji architektonicznej.

### 21.3. Site name

Kanoniczny `WebSite` z name/url jest emitowany na domenowym homepage. Może mieć `alternateName` tylko gdy istnieje rzeczywiście używana alternatywa; sensownym fallbackiem może być `prawkonaraz.pl`.

`/aktualnosci` jest subdirectory i nie próbuje ustanawiać osobnego site name.

Publiczny header, homepage, WebSite, Organization, OG site_name i article publisher muszą używać tej samej tożsamości.

### 21.4. Logo

Canonical Organization logo:

- publiczny i stabilny URL,
- min. 112x112,
- crawlable/indexable,
- poprawny na białym tle,
- ImageObject z url/contentUrl oraz width/height, jeśli znane.

---

## 22. Breadcrumb schema

Newsroom article:

- Home
- Aktualności
- Primary category
- Artykuł

Guide:

- Home
- Poradniki
- Guide

Primary category guide'a może być widoczna jako klasyfikacja/link kontekstowy, ale nie jest wciskana do głównego breadcrumb pomiędzy `Poradniki` i guide.

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
- tylko indexable + status published,
- kwalifikacja czasowa wyłącznie po `first_published_at`,
- artykuł po istotnej aktualizacji NIE wraca do news sitemap, jeśli jego first_published_at jest starsze niż aktualne okno,
- `news:name` = kanoniczna nazwa publikacji,
- `news:language` = `pl`,
- `news:publication_date` = first_published_at w W3C format,
- `news:title` = widoczny title artykułu bez autora/brandingu/daty,
- max 1000 news entries per plik na dzień weryfikacji,
- po przekroczeniu limitu deterministic split + wpisy w głównym sitemap index,
- `news:name` bierze canonical publication identity z jednego source of truth; po uruchomieniu sprawdzamy, czy odpowiada nazwie publikacji pokazywanej przez Google News,
- statyczny artefakt jest odświeżany po publikacji świeżych newsów; daily cron nie jest jedynym mechanizmem freshness.

---

## 26. Standard article sitemap

Niezależnie od news sitemap potrzebujemy długoterminowej sitemap artykułów.

Rekomendacja przy corpus mieszczącym się w jednym pliku:

`/sitemaps/articles.xml`

Zawiera wszystkie indeksowalne:

- news,
- guide,
- explainer,
- analysis,
- report.

Starszy news znika z news sitemap, ale zostaje w normalnej sitemap, jeśli nadal indeksowalny. To samo dotyczy wcześniej opublikowanego archived article: pozostaje w article sitemap wyłącznie przy `200 + indexable`.

### 26.1. Skalowanie sitemap

Każda zwykła sitemap przestrzega zewnętrznych limitów zweryfikowanych przy implementacji. Na dzień 2026-09-16 jest to 50 000 URL albo 50 MB nieskompresowanego XML na plik.

Implementation contract:

- builder nie może zakładać, że `articles.xml` zawsze zmieści cały corpus,
- sharding ma być deterministyczny i stabilny, np. `articles-2026-001.xml`,
- główny `/sitemap.xml` wskazuje wynikowe shard files bezpośrednio; nie tworzymy zagnieżdżonego newsroom sitemap-index,
- nie używać offset-based shardów powodujących masowe przesuwanie URL między plikami,
- wszystkie `loc` są absolutne, HTTPS, canonical i indexable,
- draft/noindex/redirect source/withdrawn nie trafia do article sitemap,
- current canonical path nie może kolidować z historycznym reserved `from_path`.

---

## 27. Sitemap index i coverage hubów

Po rollout sitemap coverage obejmuje nie tylko detail articles, ale również publiczne/indexowalne:

- `/aktualnosci`,
- `/poradniki`,
- aktywne category hubs,
- published/indexable topic hubs.

Mogą zostać dodane do istniejącego `static.xml`/buildera albo do jawnego newsroom-hub urlset; nie wolno zostawić ich wyłącznie w internal linking bez sitemap tylko przez przeoczenie implementacyjne.

Istniejący główny sitemap index powinien po wdrożeniu wskazywać:

- `articles.xml` i `news.xml`, dopóki każdy typ mieści się w jednym pliku,
- bezpośrednie article/news shard files po przekroczeniu limitów.

Nie tworzymy kolejnego ani zagnieżdżonego sitemap index dla newsroomu.

Istniejący produkcyjny pipeline `SeoSitemapGenerator` + `SeoSitemapBuilder` + `SeoSitemapAuditor` jest rozszerzany o newsroom. Nie budujemy równoległego systemu sitemap.

`SEO-SITEMAP-REPAIR-PLAN.md` pozostaje nadrzędny: na produkcji preferujemy gotowe XML w `public/`, serwowane bez kosztownego runtime query. Istniejący `SitemapController` może pozostać dla kompatybilności/testów, ale nie jest drugim źródłem prawdy dla produkcyjnego XML.

---

## 28. lastmod

Article sitemap URL `lastmod`:

`max(first_published_at, last_substantive_update_at, public_state_changed_at)`

z pominięciem wartości null.

`dateModified` pozostaje merytoryczne i NIE bierze `public_state_changed_at`. Dzięki temu archive/restore/robots/indexability mogą prawidłowo zmienić sitemap lastmod bez udawania aktualizacji treści.

Nie używamy technicznego `updated_at` ani czasu generacji XML.

Sitemap-index child `lastmod`, jeśli emitowany, opisuje faktyczny moment zmiany zawartości danego child sitemap/shard, a nie każde odczytanie/generowanie requestu.

Dynamiczne huby mają własną semantykę public-output `lastmod`:

- article detail: `max(first_published_at, last_substantive_update_at, public_state_changed_at)`,
- category/topic/guides listing: max z publicznej metadata huba oraz timestampów artykułów, których dodanie/usunięcie/zmiana eligibility zmieniła widoczny corpus,
- newsroom home: max z publicznie znaczącej zmiany placements/composition oraz eligible article output,
- nie ustawiamy hub `lastmod=now()` tylko dlatego, że generator właśnie się uruchomił.

Implementacja może wyliczać te wartości read-modelem/builderem; nie wymaga osobnej kolumny na każdy hub, jeśli wynik jest deterministyczny.

---

## 29. RSS / Atom

V1 rekomendacja:

/aktualnosci/feed.xml

Minimum:

- title,
- canonical link,
- stable opaque item id oparty wyłącznie na niezmiennym `content_articles.id`, niezależny od sluga/canonical/timestampów, dokładnie w formacie `urn:prawkonaraz:content-article:{id}`; RSS używa go jako GUID z `isPermaLink=false`, Atom jako `id`,
- zmiana sluga aktualizuje link, ale nie item id i nie tworzy „nowej publikacji” w czytniku,
- published date = first_published_at,
- updated date = last_substantive_update_at ?? first_published_at,
- summary,
- author jeśli format wspiera.

Feed zawiera najnowsze `activelyDistributed()` artykuły newsowe i ewentualnie inne typy po jawnej decyzji. `needs_review` i `archived` nie są dystrybuowane w feedzie mimo publicznego detail URL.

Publiczny newsroom/article layout wystawia feed discovery:

`<link rel="alternate" type="application/rss+xml" ...>`

lub Atom odpowiednio do wybranego formatu.

---

## 30. Static sitemap publication, freshness i HTTP caching

### 30.1. Produkcyjny source of truth

Newsroom sitemap rozszerza istniejący statyczny pipeline:

`SeoSitemapGenerator -> public/sitemap.xml + public/sitemaps/*.xml`

Nie przenosimy produkcyjnego source of truth do runtime `SitemapController`.

**Potwierdzony gap bieżącego kodu:** aktualny `SeoSitemapGenerator::prepareSitemapDirectory()` usuwa wszystkie `public/sitemaps/*.xml` przed pętlą zapisu, a `generate()` zapisuje `sitemap.xml` przed child files. Dzisiejszy pipeline ma więc przejściowe okno brakujących child XML podczas refreshu. N5-007 musi najpierw usunąć ten istniejący broken-set window, zanim zwiększymy częstotliwość refreshu dla newsroomu. Nie opisujemy atomic publication jako funkcji już zaimplementowanej.

### 30.2. Refresh po zmianie publicznego corpus — bez założenia o queue workerze

Stan repo podczas audytu:

- `.env.example` ma `QUEUE_CONNECTION=sync`,
- nie ma kontraktu zawsze działającego Laravel queue workera,
- scheduler istnieje i już utrzymuje `seo:refresh-sitemaps`.

Dlatego v1 NIE dispatchuje pełnego generatora jako zwykłego queued joba i nie nazywa tego asynchronicznym, dopóki produkcja nie ma rzeczywistego async transportu + workera + monitoringu.

Zmiany wpływające na newsroom sitemap/feed:

- publish,
- archive/unarchive,
- withdraw/restore/republish,
- slug change,
- substantive public update wpływający na lastmod/feed,
- robots/indexability change,
- topic/category public-state change wpływający na sitemap.

Po udanym commit:

1. zapisujemy tani `dirty/version signal` dla SEO artifacts w współdzielonym cache/store,
2. scheduler np. co minutę uruchamia lekką komendę `newsroom:refresh-seo-artifacts-if-dirty`,
3. komenda bierze distributed lock / `withoutOverlapping`,
4. wiele zmian coalescuje się do jednego pełnego `seo:refresh-sitemaps`,
5. po udanej generacji dirty marker jest czyszczony tylko jeśli wersja nie zmieniła się w trakcie pracy; inaczej kolejny pass pozostaje wymagany,
6. failure pozostawia dirty state i jest logowany/monitorowany,
7. istniejący daily `seo:refresh-sitemaps` pozostaje niezależnym safety netem na wypadek utraty cache markeru.

Jeśli w przyszłości wdrożymy realny non-sync queue worker, coordinator może używać queued joba, ale dopiero po osobnym deploy/monitoring gate.

Target operacyjny dla news sitemap: świeży statyczny artefakt powinien pojawić się w ciągu kilku minut od publikacji, nie dopiero przy następnym daily cron.

### 30.3. Publikacja zestawu bez broken-index window

Generator nie może publikować nowego `sitemap.xml`, który wskazuje jeszcze nieistniejące child files.

Bezpieczna kolejność:

1. zbuduj wszystkie payloady,
2. zapisz je do plików tymczasowych na tym samym filesystemie,
3. zwaliduj XML, limity i duplikaty,
4. atomowo podmień nowe/zmienione child files,
5. atomowo podmień `sitemap.xml` na końcu,
6. dopiero po przełączeniu indexu usuń stare, już nie referencjonowane shardy.

Przy błędzie przed krokiem 5 stary kompletny zestaw pozostaje aktywny.

### 30.4. Topologia publikacji statycznych artefaktów

Atomic rename jest wystarczające tylko w obrębie filesystemu widzianego przez requesty.

Przed N5 release trzeba potwierdzić:

- czy produkcja ma jeden web node,
- czy `public/` jest współdzielone między node'ami,
- czy CDN/origin publikuje jeden wspólny artifact set.

Przy wielu node'ach generator uruchomiony na jednym hostcie nie może zostawić pozostałych z inną wersją sitemap. Redis lock/`onOneServer` rozwiązuje concurrency generatora, ale nie dystrybucję plików.

### 30.5. HTTP validators na właściwej warstwie

Dla statycznych XML oraz `public/robots.txt` preferowane:

- poprawny Content-Type,
- public Cache-Control zgodny z deployment/CDN policy,
- ETag i/lub Last-Modified,
- conditional request -> 304, gdy warstwa Nginx/CDN/static delivery to wspiera,
- brak Set-Cookie/session.

Nie uznajemy dodania headerów wyłącznie do Laravel `SitemapController` za spełnienie tego wymagania, jeśli produkcja serwuje statyczny plik przed wejściem do PHP.

Feed może pozostać dynamiczny/cachowany aplikacyjnie i mieć własne validators.

---

## 31. Google News eligibility i transparency

Nie projektujemy feature flag „Google News accepted” ani starego procesu ręcznego tworzenia publication page w Publisher Center.

Na dzień 2026-09-16 Google News używa automatycznie generowanych publication pages; content zgodny z policies jest automatycznie kwalifikowany do rozważenia, ale widoczność nie jest gwarantowana.

System ma zapewniać:

- jasny widoczny headline,
- dla newsów wyraźną datę i czas publikacji blisko headline/byline,
- jawny byline autora,
- publiczny profil autora,
- informacje o publikacji/publisherze i podmiocie stojącym za serwisem,
- łatwo dostępne dane kontaktowe,
- jasne oznaczenie sponsoringu/paid content, jeśli kiedykolwiek wystąpi,
- jakościowy i oryginalny wkład redakcyjny,
- techniczne standardy Search/News,
- monitoring.

Wykorzystujemy istniejące publiczne powierzchnie Organization/Contact/Methodology tam, gdzie spełniają wymaganie. Jeśli audyt przed rolloutem wykaże lukę, uzupełniamy istniejącą powierzchnię albo tworzymy celową stronę zasad redakcyjnych/korekt — nie deklarujemy `publishingPrinciples` w schema bez realnego publicznego URL.

Nie obiecujemy pojawienia się w Google News/Top stories/Discover.

---

## 32. Search Console

Po rollout:

- submit główny sitemap index,
- monitor Pages/Indexing,
- monitor article URLs,
- Rich Results/URL Inspection sample,
- Discover report jeśli dane się pojawią,
- Search Performance per newsroom path,
- segmentować diagnostykę co najmniej na articles / categories / topics / guides,
- przy sharding można submitować/obserwować wybrane child sitemaps osobno dla łatwiejszej diagnozy,
- monitorować submitted vs indexed i Google-selected canonical vs declared canonical.

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

## 43. Crawlability i crawl efficiency

- links jako href,
- body w HTML,
- pagination SSR,
- category/topic links crawlable,
- no JS-only navigation,
- canonical/noindex/redirect/sitemap signals nie mogą sobie przeczyć,
- nie generujemy nieskończonych crawlable kombinacji parametrów,
- 304/HTTP caching dla niezmienionych zasobów ogranicza niepotrzebne transfery,
- crawl-budget optimizations traktujemy jako skalowalność, nie rytuał dla małego corpus.

---

## 44. Semantic silo i internal linking graph

Nie budujemy „sztywnego silo”, w którym klastry są sztucznie odizolowane. Targetem jest czytelny semantic graph: stabilna hierarchia główna + kontekstowe cross-links tam, gdzie realnie pomagają użytkownikowi.

### 44.1. Kanoniczna hierarchia

Dla zwykłego newsa/analysis/report/explainer:

`/aktualnosci -> primary category -> article`

Topic/dossier jest dodatkowym hubem tematycznym:

`/aktualnosci -> topic -> article`

i nie zastępuje primary category.

Dla guide:

`/poradniki -> guide article`

Guide nadal ma dokładnie jedną primary category w modelu domenowym i może linkować do jej huba, ale nie tworzymy drugiego canonical URL pod kategorią.

### 44.2. Primary category invariant

Każdy opublikowany artykuł ma dokładnie jedną primary category.

Primary category odpowiada za:

- articleSection,
- główny breadcrumb dla newsroom article,
- podstawowy category hub,
- bazowy kontekst related-content.

Tag i topic nie mogą stać się alternatywną primary category.

### 44.3. Linki poziome i pionowe

Każdy article może linkować do:

- primary category,
- 0..n topic/dossier,
- author,
- legal content,
- questions,
- signs,
- related articles,
- product CTA.

Nie wymuszamy linku do każdego typu relacji. Link istnieje tylko przy rzeczywistej zależności semantycznej.

### 44.4. Dwukierunkowe mosty do istniejących klastrów

Newsroom nie tworzy drugiego `question_relations` ani drugiej taksonomii pytań. `content_article_question` jest nowym edge article ↔ question i może zasilać osobny moduł „Powiązane aktualności” na stronie pytania bez ingerencji w ranking „Powiązane pytania”.

Najważniejsze relacje newsroomu powinny działać w obie strony:

- article -> legal page/unit,
- relevant legal page -> najważniejsze/aktualne article(s),
- article -> question/topic,
- question/topic hub -> wybrane powiązane newsroom article(s), gdy wnosi to kontekst,
- article -> traffic sign,
- traffic sign/supporting page -> wybrane article(s), gdy istnieje bezpośredni związek.

To nie jest sitewide reciprocal linking. Reverse link jest renderowany tylko dla jawnej relacji i ograniczonej, istotnej listy.

Cel:

- nowy article nie jest orphan,
- evergreen/source-of-truth pages przekazują kontekst do świeżych materiałów,
- świeże newsy wzmacniają istniejące zasoby edukacyjne i prawne,
- użytkownik może przejść od „co się zmieniło” do „jak działa reguła” i do praktyki/testu.

### 44.5. Anchor text policy

- crawlable `<a href>`,
- anchor opisowy i naturalny,
- title artykułu jest dobrym anchor dla kart/list,
- w body preferujemy kontekstowy fragment zdania zamiast „kliknij tutaj”,
- nie wymuszamy exact-match keyword anchor,
- nie generujemy bloków dziesiątek słabo związanych linków.

### 44.6. Click depth / discoverability

Operacyjny target:

- aktywne, ważne i evergreen article: zwykle <= 3 crawlable hops od `/aktualnosci` lub odpowiedniego top-level huba,
- każdy indexable article ma co najmniej jeden crawlable inbound link z publicznej strony,
- archived+indexable article nadal podlega tej regule; v1 gwarantuje fallback inbound przez publiczny profil autora z oznaczeniem materiału archiwalnego,
- starsze materiały pozostają osiągalne przez category/topic pagination i nie polegają wyłącznie na sitemapie,
- sitemap wspiera discovery, ale nie zastępuje linkowania wewnętrznego.

### 44.7. Deduplikacja related content

Related resolver:

- preferuje tę samą primary category/topic i jawne entity relations,
- nie powtarza tego samego URL w kilku modułach jednego viewportu bez powodu,
- nie linkuje do draft/noindex/redirect source,
- nie tworzy łańcuchów „related” wyłącznie na podstawie podobnego title.

Monitorować orphan articles i nadmiernie odizolowane klastry.

---

## 45. Orphan detection

Rekomendowana komenda QA:

newsroom:audit-links

Raportuje:

- published article bez crawlable inbound linku,
- ważny article z nadmiernym click depth,
- broken related/reverse links,
- draft/noindex/redirect-source targets,
- duplicate URL w kilku related modules,
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

Repo ma już `IndexNowUrlSubmission`, `IndexNowSubmissionService`, `IndexNowQueueService` i `IndexNowUrlCollector`.

Potwierdzony aktualny pipeline już obsługuje:

- canonical-host filtering,
- lokalną politykę safety wymagającą HTTPS i odrzucającą query string/private/technical/non-page paths,
- key/keyLocation,
- batching do maks. 10 000 URL per request,
- 200/202 jako accepted states,
- rozróżnienie 400/403/422/429/5xx,
- deduplikację kolejki per dokładny URL przez `url_hash` i debounce/retry.

Newsroom ma **REUSE** ten pipeline. Nie tworzymy osobnego klienta ani drugiej kolejki.

### 49.1. Semantyka protokołu vs lokalny `event_type`

IndexNow POST wysyła `host`, `key`, opcjonalne `keyLocation` i `urlList`. Protokół nie ma osobnego pola/verb `created|updated|deleted`. `IndexNowUrlSubmission.event_type` jest wyłącznie lokalnym metadanym kolejki/audytu i **nie może być serializowany jako rzekoma komenda protokołu**.

Dokładna semantyka newsroomu po udanym commit:

- pierwsza publikacja: canonical już odpowiada `200` -> enqueue canonical z lokalnym `EVENT_CREATED`,
- merytoryczna publiczna aktualizacja / republish: finalny canonical odpowiada `200` -> enqueue canonical z lokalnym `EVENT_UPDATED`,
- archive: canonical historycznego artykułu nadal odpowiada `200`; enqueue `EVENT_UPDATED` tylko wtedy, gdy archive realnie zmieniło publiczną reprezentację/robots detail page. Samo usunięcie z home/feed/sitemap nie udaje „delete” URL,
- withdrawn: transakcja najpierw ustanawia finalny publiczny stan `410 Gone`; dopiero after commit enqueue tego samego dawnego canonical z lokalnym `EVENT_DELETED`,
- zmiana sluga: transakcja najpierw ustanawia stary URL jako `301 -> new canonical` i nowy canonical jako `200`; dopiero after commit enqueue obu URL-i. Stary URL dostaje lokalny `EVENT_UPDATED` (jego publiczna odpowiedź zmieniła się na redirect), **nie `EVENT_DELETED`**; nowy canonical dostaje `EVENT_CREATED` albo `EVENT_UPDATED` zgodnie z use case,
- restore withdrawn -> review nie enqueue'uje publicznego URL, bo pozostaje `410`; dopiero skuteczny republish zgłasza ponownie canonical po przywróceniu `200`.

Dodatkowe invariants:

- żadnego enqueue przed commit; rollback nie zostawia submission row,
- nie zgłaszamy preview/draft/in_review/scheduled-before-time ani URL z `noindex`,
- `NEWSROOM_PUBLIC_ENABLED=false` wyłącza newsroom collector/automation,
- failure/retry IndexNow nie blokuje publish/withdraw/slug transaction,
- collector zostaje rozszerzony o newsroom zamiast tworzenia osobnego klienta,
- 200/202 oznacza wyłącznie przyjęcie zgłoszenia przez usługę; nie jest gwarancją crawl/index/ranking.

Oficjalny protokół opisuje jeden `url-changed` dla URL dodanego, zaktualizowanego lub usuniętego. Stan docelowy wyszukiwarka poznaje przez ponowne pobranie zgłoszonego URL; dlatego kolejność **public state commit -> enqueue** jest częścią kontraktu, a nie detalem implementacyjnym.

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
- [ ] homepage WebSite/site name i Organization używają tego samego identity
- [ ] Organization logo publiczne/crawlable i >= 112x112
- [ ] `og:site_name` spójne z identity
- [ ] canonical domain prawkonaraz.pl
- [ ] robots pozwala crawl i wskazuje główny sitemap index
- [ ] /aktualnosci 200
- [ ] sample article 200
- [ ] draft unavailable publicly
- [ ] title/meta
- [ ] OG image + alt + stabilny publiczny URL
- [ ] Article/NewsArticle graph + stabilne @id
- [ ] BreadcrumbList
- [ ] author URL + ProfilePage Person identity
- [ ] visible published/updated dates zgodne z schema
- [ ] publication/publisher/contact transparency widoczna
- [ ] articles sitemap/shard
- [ ] news sitemap required tags i first_published_at eligibility
- [ ] feed + head auto-discovery
- [ ] sitemap/feed HTTP validator + 304 sample
- [ ] Search Console main sitemap index submit
- [ ] URL Inspection sample
- [ ] max-image-preview:large
- [ ] hero >= recommended baseline dla sample Discover-target article

---

## 55. Rich Results / validation

Przed launch co najmniej kilka realnych article pages testujemy:

- Rich Results Test dla wspieranych typów Article,
- Schema Markup Validator dla pełnego graphu,
- homepage site-name WebSite w Schema Markup Validator + URL Inspection (site name nie jest walidowany przez Rich Results Test),
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

Canonical route v1:

`/aktualnosci/temat/{topicSlug}`

Może być indeksowalny tylko gdy:

- status = published,
- ma własny, niepusty opis redakcyjny i sensowne meta/fallback,
- v1 baseline to co najmniej 3 actively-distributed, indeksowalne artykuły w corpus,
- nie duplikuje kategorii/tag page,
- featured article, jeśli ustawiony, jest publiczny i należy do topicu.

Próg 3 to wewnętrzny quality gate produktu, nie sygnał ani gwarancja Google.

Topic nie powstaje automatycznie z taga.

Próg corpus jest gate'em publikacyjnym, nie dynamicznym przełącznikiem HTTP. Jeśli już opublikowany topic później spadnie poniżej baseline, pozostaje 200 do jawnej decyzji redakcyjnej, ale wypada z redakcyjnej promocji i trafia do health warning/audytu. Jawne `archived` topicu usuwa go z sitemap/nav i dla wcześniej publicznego URL zwraca 410 (albo 301 przy realnym następcy).

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

## 64. Audio i AI derivatives — SEO policy

Przyszły odsłuch, transkrypcja, skrót AI lub „zapytaj o artykuł” nie tworzą automatycznie osobnych indeksowalnych stron.

Zasady:

- canonical pozostaje na źródłowym artykule,
- generowany skrót nie może być alternatywnym thin URL,
- audio player nie blokuje crawlable HTML body,
- transkrypcja audio pochodzącego z tego samego artykułu nie powinna dublować pełnego body w osobnym URL,
- funkcje AI nie zmieniają dateModified artykułu, jeśli sam artykuł nie został merytorycznie zaktualizowany.

---

## 65. Google Preferred Sources — post-launch opportunity

Google udostępnia globalnie mechanizm Preferred Sources dla domen/subdomen. `prawkonaraz.pl` może być potencjalnym kandydatem jako domena, ale `/aktualnosci` nie jest osobnym źródłem na poziomie subdirectory.

To nie jest warunek indeksacji ani Google News.

Po uruchomieniu newsroomu i ustabilizowaniu publisher identity:

1. sprawdzić, czy prawkonaraz.pl pojawia się w Google source preferences tool,
2. jeśli tak, rozważyć oficjalny button/deeplink „Dodaj jako preferowane źródło”,
3. wdrożyć go bez agresywnego popupu i dopiero po pomiarze UX.

Nie kopiujemy kodu integracji na zapas przed potwierdzeniem dostępności dla domeny.

---

### 65.1. Anti-scaled-content guard

Nie tworzymy masowych stron tylko po to, by pokryć warianty fraz.

W szczególności bez osobnej decyzji jakościowej nie wolno:

- publikować strony dla każdego taga,
- generować setek WORD/miasto landingów z minimalnie zmienionym tekstem,
- generować AI articles bez własnej wartości, źródeł i review,
- składać treści z cudzych źródeł bez istotnego wkładu redakcyjnego.

Automation może wspierać redakcję, ale każde indeksowalne URL musi mieć samodzielną wartość dla użytkownika.

---

## 66. Preferred source references w dokumentacji

Każdy zewnętrzny wymóg Google w kodzie powinien mieć:

- link do oficjalnej dokumentacji w docblock/test description lub tym dokumencie,
- datę weryfikacji w dokumentacji projektowej.

Nie linkujemy do przypadkowego SEO bloga jako źródła normatywnego.

---

## 67. Definition of Done SEO/Distribution v1

- canonical URLs stabilne,
- article metadata kompletne,
- NewsArticle/Article graph semantycznie poprawny i używa stabilnych @id,
- publisher/WebSite/site name/author spójni,
- statyczny articles sitemap działa przez istniejący generator i ma deterministic sharding readiness,
- statyczny news sitemap ma poprawne news:name/language/publication_date/title i działa zgodnie z aktualnymi wymaganiami,
- child files są publikowane przed nowym głównym sitemap index,
- dirty/version coordinator + scheduler utrzymuje news sitemap świeżą bez zależności od queue workera, a daily cron pozostaje recovery path,
- rzeczywisty static/Nginx/CDN delivery ma zweryfikowany Content-Type/cache/Set-Cookie/validators contract,
- feed działa, ma discovery link i własny validator/cache contract,
- images spełniają ustalone baseline i respektują focal point/crop policy,
- opublikowane topic pages spełniają kryteria jakości,
- max-image-preview:large włączone dla indeksowalnych artykułów,
- analytics event model działa,
- produkcyjne 5xx/scheduler/sitemap są monitorowane,
- Search Console ma sitemap index,
- nie ma draft leakage.

---

## 68. Stan implementacji

Obecnie:

- public-content layout ma canonical/OG/Twitter/article times support,
- NEWSROOM-N3-002 jest wdrożone na `main@d9be735eee1915f4b53a6de42ec665a37442acae`: `ContentArticleSeoService` generuje layout-compatible title/description/self-canonical/robots/social-image/article-time metadata wyłącznie dla publicznie widocznego artykułu,
- istnieją statycznie generowane sitemapy innych content types przez `SeoSitemapGenerator`, `SeoSitemapBuilder` i `SeoSitemapAuditor`,
- scheduler uruchamia `seo:refresh-sitemaps` codziennie jako istniejący safety net,
- istnieje zarówno `public/robots.txt`, jak i route `RobotsController`; production delivery trzeba traktować zgodnie z `SEO-SITEMAP-REPAIR-PLAN.md`,
- istnieje IndexNowUrlSubmission,
- author pages istnieją; N3-007 utrzymuje wspólny ProfilePage/Article Person `@id`, a po N3-008 przy `NEWSROOM_PUBLIC_ENABLED=false` profil nie pokazuje Newsroom corpus, zaś author sitemap nie używa newsroom-only eligibility ani newsroomowego `public_state_changed_at`; aktualny zweryfikowany kod to `main@23b952b77e39cd25fb39edc252faf05849946bd7`,
- `config/content.php['organization']`, `SchemaIds`, `SchemaRenderer` i współdzielony `SiteIdentitySchema` stanowią fundament entity graph,
- HomePageController korzysta z kanonicznego Organization/WebSite graph; legacy „Orły na Drodze” nie jest już emitowane przez homepage,
- wspólny public-content layout emituje `og:site_name` z kanonicznego identity,
- istnieje również runtime `SitemapController`, ale statyczne pliki są nadrzędnym produkcyjnym modelem; samo dodanie headerów do kontrolera nie rozwiązuje static delivery,
- newsroom Article schema graph istnieje przez `ContentArticleSchemaService` po NEWSROOM-N3-003 i jest emitowany przez publiczny article HTTP renderer od N3-004; news sitemap i feed nadal nie istnieją,
- newsroom dirty/version refresh coordinator i atomowy child-before-index switch nie istnieją,
- repo nie gwarantuje async Laravel queue workera (`QUEUE_CONNECTION=sync` w env example), więc newsroom nie może opierać freshness na ShouldQueue,
- `/aktualnosci` i `/poradniki` są pre-launch placeholderami 200 z `X-Robots-Tag: noindex, follow`,
- `/aktualnosci/feed.xml` ma zarejestrowany route contract, ale obecnie zwraca 404; feed ani feed discovery nie są jeszcze wdrożone,
- category/topic route namespaces są zarejestrowane i nadal pozostają 404 bez publicznych controllerów; article detail routes są aktywne od N3-004.
- NEWSROOM-N3-008 jest wdrożone: `NEWSROOM_PUBLIC_ENABLED=false` blokuje current detail/guide i historyczne redirecty, zachowuje top-level placeholdery oraz filtruje newsroom namespace z obecnego IndexNow collectora; przyszłe N4/N5 discovery surfaces pozostają otwarte.

---

## 69. Pozostałe zadania

- [x] ujednolicić Organization/WebSite/site name na istniejącym config/schema infrastructure,
- [x] dodać `og:site_name` do wspólnego public layout contract,
- [ ] dodać feed discovery do wspólnego public layout contract,
- [x] wdrożyć ContentArticleSeoService,
- [x] wdrożyć ContentArticleSchemaService i publiczne osadzenie graphu w article HTML w N3-004,
- [x] zintegrować ProfilePage/Article author identity i author sitemap z Newsroom corpus w N3-007,
- [x] wdrożyć NEWSROOM-N3-008 public rollout gate dla istniejących detail/redirect, author profile/author sitemap contribution i obecnego IndexNow collectora; przyszłe feed/article sitemap/reverse links nadal muszą respektować ten sam gate,
- [ ] rozszerzyć istniejący statyczny generator o article sitemap z deterministic sharding readiness,
- [ ] wdrożyć statyczny news sitemap z pełnymi wymaganymi news tags,
- [ ] wdrożyć child-before-index atomic publication i cleanup obsolete shards po switchu,
- [ ] wdrożyć dirty/version refresh coordinator + frequent scheduler lock; zachować daily cron jako safety net i nie wymagać queue workera,
- [ ] rozszerzyć istniejący sitemap auditor o newsroom/news namespace checks,
- [ ] zweryfikować rzeczywiste static/Nginx/CDN headers/304 bez przenoszenia source of truth do SitemapController,
- [ ] wdrożyć feed + auto-discovery + własne validators,
- [ ] wdrożyć analytics hooks/events,
- [ ] wdrożyć topic SEO dla faktycznie publikowanych dossier,
- [ ] zweryfikować crop/OG output z focal point,
- [ ] podłączyć monitoring,
- [ ] wykonać production Search Console verification,
- [ ] po launch sprawdzić eligibility domeny w Google Preferred Sources.

---

## 70. Historia zmian

### 2026-09-18 — v0.13

- NEWSROOM-N3-008 zmergowano przez PR #79 na `main@23b952b77e39cd25fb39edc252faf05849946bd7`; exact implementation head `c8484aa1529eb41805a76ceb7be1f55db63aec14`,
- przy `NEWSROOM_PUBLIC_ENABLED=false` detail/guide i historyczne redirecty nie ujawniają dark-deployed content, top-level placeholdery pozostają 200 + noindex, a Newsroom znika z author profile i newsroomowego wkładu do author sitemap,
- obecny `IndexNowUrlCollector` defensywnie filtruje `/aktualnosci` i `/poradniki` przy wyłączonym gate; article-specific IndexNow automation pozostaje N5-005,
- feed, article/news sitemap i reverse-link discovery nadal nie są wdrożone; exact-head CI #308, Browser Smoke #23 i post-merge CI #309 zakończyły się PASS.


### 2026-09-17 — v0.12

- NEWSROOM-N3-007 zmergowano przez PR #77 na `main@c68672f6aa7c41defaeec56debb541d5a60d9f4f`: ProfilePage i Article współdzielą stabilny Person `@id` i canonical Organization reference,
- `SeoSitemapBuilder::authorUrls()` kwalifikuje autorów przez indexable Newsroom corpus w aktywnych kategoriach i używa newsroomowego `public_state_changed_at` zamiast technicznego article `updated_at`,
- `ContentAuthorProfileTest` chroni identity i sitemap freshness; exact-head CI #295 oraz post-merge CI #296 są PASS,
- news sitemap/feed i rollout gate pozostają dalszym zakresem; N3-007 nie oznacza ich jako wdrożonych.

### 2026-09-17 — v0.11

- NEWSROOM-N3-006 zmergowano przez PR #75 na `main@33d9946219595a4be75d789b19cc8d10efc2ecc0` i materializuje HTTP część istniejącej slug-change policy: old path -> exactly one 301 -> current canonical 200,
- resolver honoruje wyłącznie persisted 301 bezpośrednio do recomputed current canonical; stale/malformed/self-loop redirect nie tworzy alternatywnego URL i failuje do 404,
- redirect nie forwarduje query params; current canonical/OG/schema contracts N3-002/N3-003 pozostają bez zmian,
- sitemap/internal-link cleanup nadal należy do downstream discovery/linking i nie jest oznaczony jako wykonany przez N3-006; post-merge CI #280 był pełnym PASS.

### 2026-09-17 — v0.10

- NEWSROOM-N3-003 zmergowano przez PR #68; finalny zweryfikowany `main@5f85bec331428a73f3859ae40b555f55e7e7820d` przeszedł push-CI #245 z pełnym PASS: 1043 tests / 19 418 assertions / 2 skipped, Pint 1045 files, frontend 9.04 s, PostgreSQL 7/94,
- wdrożony `ContentArticleSchemaService` reużywa kanonicznego site identity i metadata N3-002, emituje stabilne entity IDs oraz Article/NewsArticle, Person, BreadcrumbList i ImageObject nodes dla ustawionych media paths,
- service-level regression potwierdza spójność canonical/dateModified/datePublished, publisher/author references, route-family breadcrumbs, type mapping i image dedupe,
- publiczny article renderer nadal jest 404, więc JSON-LD nie jest jeszcze newsroomowym publicznym HTML outputem; to pozostaje N3-004. News sitemap/feed/static distribution pozostają otwarte.

### 2026-09-17 — v0.9

- NEWSROOM-N3-002 zmergowano przez PR #66 na `main@d9be735eee1915f4b53a6de42ec665a37442acae`; finalny push-CI #237 potwierdził pełny PASS: 1034 tests / 19 376 assertions / 2 skipped, Pint 1043 files, frontend build 8.50 s, PostgreSQL 7/94,
- wdrożono `ContentArticleSeoService` bez ręcznego canonical override: canonical pochodzi z `NewsroomRouteContract` i `PublicUrlResolver`,
- service generuje title/description/robots, OG-image z hero fallbackiem oraz `first_published_at` / `last_substantive_update_at` times; techniczne `updated_at` nie steruje dateModified,
- metadata są zgodne z istniejącym public-content layout contract, ale article HTTP renderer pozostaje pre-launch 404; schema graph jest kolejnym NEWSROOM-N3-003.

### 2026-09-16 — v0.8

- zsynchronizowano SEO current state z wdrożonym NEWSROOM-N0-002,
- pre-launch `/aktualnosci` i `/poradniki` mają jawny crawler-level `X-Robots-Tag: noindex, follow`,
- odnotowano istniejący route contract `/aktualnosci/feed.xml`, ale feed i discovery pozostają niewdrożone,
- future article/category/topic routes pozostają 404, więc nie są opisywane jako publiczny corpus.

### 2026-09-16 — v0.6

- doprecyzowano stabilny feed GUID do dokładnego `urn:prawkonaraz:content-article:{content_articles.id}`, niezależnego od sluga i timestampów,
- wyrównano IndexNow do faktycznego kodu i protokołu: `event_type` jest lokalnym metadanym, a payload wysyła URL listę bez verbów created/updated/deleted,
- zdefiniowano kolejność commit -> enqueue dla publish/update/withdraw/slug change oraz poprawiono slug-change semantics: stary URL po 301 jest updated, nie deleted.

### 2026-09-16 — v0.5

- ustalono deterministyczną archive policy: historyczny canonical 200, oraz osobny withdrawn=410 dla jawnego takedownu,
- zastąpiono fikcyjne założenie o async queue jobie dirty/version coordinator + scheduler/lock zgodnym z aktualnym QUEUE_CONNECTION=sync,
- doprecyzowano topic indexability baseline do min. 3 actively-distributed/indexable articles,
- rozdzielono dateModified od sitemap lastmod przez public_state_changed_at,
- udokumentowano potwierdzony istniejący gap generatora: delete-all child XML + zapis indexu przed childami,
- dodano sitemap coverage dla newsroom/guides/category/topic hubs,
- ograniczono NEWSROOM_PUBLIC_ENABLED=false do dark-deploy; po launch techniczny rollback nie może masowo zamieniać indeksowanych URL-i w 404,
- poprawiono kolejność sekcji 4.3/4.4 i wyrównano current-state/remaining-work do faktycznego backendu.

### 2026-09-16 — v0.7

- wdrożono NEWSROOM-N0-001: wspólny SiteIdentitySchema, stabilny Organization/WebSite graph i jeden site name,
- usunięto legacy publisher „Orły na Drodze” z homepage,
- dodano `og:site_name`, WebSite alternateName i Organization logo ImageObject z wymiarami 256×256,
- istniejące public-question/traffic-sign/legal-content schema services reużywają wspólny builder,
- pozostawiono feed discovery oraz NewsArticle/news sitemap jako późniejsze zadania.

### 2026-09-16 — v0.4

- podporządkowano newsroom istniejącemu statycznemu pipeline SeoSitemapGenerator i SEO-SITEMAP-REPAIR-PLAN,
- dodano asynchroniczny/debounced refresh po zmianach publicznego corpus oraz daily cron jako safety net,
- dodano bezpieczną publikację child files przed sitemap index i sprzątanie starych shardów po przełączeniu,
- przeniesiono ETag/304 contract na faktyczną warstwę static/Nginx/CDN zamiast zakładać runtime controller,
- zapisano kompatybilność statycznego robots.txt i istniejącego RobotsController bez usuwania backendu,
- przyjęto twardy self-canonical i stabilność route family,
- doprecyzowano guide breadcrumbs, seo_title vs H1 oraz izolację newsroom edges od question graphu,
- skorygowano wcześniejsze założenie o 110 znakach headline zgodnie z aktualną dokumentacją Google.

### 2026-09-16 — v0.3

- wykonano ponowny audit względem aktualnego kodu i oficjalnej dokumentacji Google,
- skorygowano site identity source of truth do istniejącego config/content.php + SchemaIds/SchemaRenderer,
- zdefiniowano stabilny Organization/WebSite/Person/Article/WebPage graph,
- doprecyzowano site name, Organization logo, author ProfilePage reuse i date consistency,
- doprecyzowano wymagane pola news sitemap oraz first_published_at eligibility,
- dodano enterprise sitemap sharding, HTTP 304/cache validators i RSS discovery,
- dodano Search Console segmentation, anti-scaled-content guard i post-launch Google Preferred Sources,
- opisano istniejące braki kodu bez oznaczania ich jako wdrożonych.

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

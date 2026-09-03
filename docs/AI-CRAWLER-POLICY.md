# AI crawler policy

Status: wdrozone dla `prawkonaraz.pl`  
Data: 2026-05-28  
Decyzja: AI search i user-requested retrieval tak, AI training nie.

## Cel

Celem jest ulatwienie botom wyszukiwarkowym i narzedziom AI odkrywania publicznych stron serwisu bez otwierania prywatnych albo technicznych obszarow aplikacji oraz bez udzielania zgody na trenowanie modeli na tresci serwisu.

## Polityka

Pozwalamy na public search/retrieval:

- `Googlebot`,
- `Bingbot`,
- `OAI-SearchBot`,
- `ChatGPT-User`,
- `Claude-SearchBot`,
- `Claude-User`,
- `PerplexityBot`,
- `Perplexity-User`,
- `Applebot`,
- `DuckAssistBot`,
- `MistralAI-User`,
- `Meta-ExternalFetcher`,
- `Manus Bot`,
- `Terracotta Bot`,
- inne zgodne crawlery respektujace `robots.txt`.

Nie udzielamy zgody na AI training:

- `GPTBot`,
- `ClaudeBot`,
- `Google-Extended`,
- `CCBot`,
- `Bytespider`,
- `Applebot-Extended`,
- `meta-externalagent`,
- `Meta-ExternalAgent`,
- `Amazonbot`,
- `FacebookBot`,
- `Google-CloudVertexBot`,
- `PetalBot`,
- `TikTok Spider`,
- `Timpibot`,
- `ProRataInc`,
- `Novellum AI Crawl`,
- `Anchor Browser`.

## Pliki publiczne

### `/robots.txt`

Originowy plik `public/robots.txt` zawiera trzy warstwy:

1. Jawne `Allow: /` dla botow AI search i user-requested retrieval.
2. Jawne `Disallow: /` dla botow trainingowych.
3. Ogolne `Allow: /` dla publicznej czesci serwisu plus blokady dla:
   - `/admin/`,
   - `/api/`,
   - `/dashboard`,
   - `/auth/`,
   - `/checkout/`,
   - `/konto/`,
   - `/profile/`,
   - `/nauka/`,
   - `/zaproszenie/`,
   - auth/reset/verification/password paths.

Na produkcji Cloudflare Managed robots.txt jest wlaczony i dokleja swoj blok na poczatku pliku. To jest zgodne z nasza polityka, bo Cloudflare blokuje przede wszystkim znane boty trainingowe i dodaje sygnal:

```txt
Content-Signal: search=yes,ai-train=no
```

Nasz originowy plik dodaje precyzyjniejszy sygnal:

```txt
Content-Signal: search=yes,ai-input=yes,ai-train=no
```

### `/llms.txt`

Dodano `public/llms.txt` jako skondensowany drogowskaz dla AI search/retrieval:

- glowny opis serwisu,
- preferowany jezyk odpowiedzi,
- linki do najwazniejszych publicznych sekcji,
- link do sitemap index,
- opis publicznych klastrow URL,
- streszczenie polityki access/training.

`llms.txt` nie zastepuje `sitemap.xml`. Jego zadanie jest inne: dac modelom i agentom krotki, czytelny kontekst, zanim beda eksplorowac sitemap albo konkretne strony.

## Kontrole po deployu

```bash
curl -I https://prawkonaraz.pl/robots.txt
curl https://prawkonaraz.pl/robots.txt
curl -I https://prawkonaraz.pl/llms.txt
curl https://prawkonaraz.pl/llms.txt
curl -A "Googlebot/2.1 (+http://www.google.com/bot.html)" -I https://prawkonaraz.pl/sitemap.xml
curl -A "Mozilla/5.0 (compatible; OAI-SearchBot/1.3; +https://openai.com/searchbot)" -I https://prawkonaraz.pl/sitemap.xml
```

Po deployu trzeba potwierdzic:

- `robots.txt` zawiera `OAI-SearchBot`, `Claude-SearchBot`, `PerplexityBot`,
- `robots.txt` nadal zawiera `Sitemap: https://prawkonaraz.pl/sitemap.xml`,
- `llms.txt` zwraca `200`,
- `sitemap.xml` zwraca `200` dla zwyklego requestu i dla user-agentow crawlerow.

## Ryzyka

- `robots.txt` jest deklaracja preferencji, a nie techniczna blokada. Jesli potrzebna bedzie egzekucja, trzeba uzyc Cloudflare WAF albo AI Crawl Control.
- Niektore user-requested fetchery moga ignorowac `robots.txt`, bo dzialaja na bezposrednia prosbe uzytkownika.
- Cloudflare moze blokowac ruch na warstwie WAF niezaleznie od `robots.txt`. Przy problemach trzeba sprawdzac logi Cloudflare i ewentualnie dodac allowlisty po user-agent + oficjalnych IP ranges.
- `llms.txt` jest emerging convention, nie formalnym standardem IETF/W3C. Warto go miec, ale nie nalezy traktowac go jako gwarancji widocznosci.

## Zrodla

- OpenAI crawlers: `https://developers.openai.com/api/docs/bots`
- Anthropic crawlers: `https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler`
- Perplexity crawlers: `https://docs.perplexity.ai/docs/resources/perplexity-crawlers`
- Google crawlers and Google-Extended: `https://developers.google.com/crawling/docs/crawlers-fetchers/google-common-crawlers`
- Cloudflare Managed robots.txt: `https://developers.cloudflare.com/bots/additional-configurations/managed-robots-txt/`
- llms.txt proposal: `https://llmstxt.org/`

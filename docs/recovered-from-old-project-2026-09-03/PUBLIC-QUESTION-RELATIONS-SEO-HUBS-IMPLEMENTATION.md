# Powiązane pytania i huby SEO — dokumentacja wdrożeniowa

## Cel

Każda publiczna strona pytania ma udostępniać co najmniej 15 użytecznych, kanonicznych linków do dalszej nauki. Rzeczywiste relacje bezpośrednie nie mają limitu. Jeżeli jest ich mniej niż 15, lista jest uzupełniana pytaniami z tego samego podtematu, następnie z tego samego tematu głównego, a na końcu z jawnie skonfigurowanych tematów sąsiednich.

Nie tworzymy fikcyjnych relacji bezpośrednich. Użytkownik widzi różnicę między pytaniami blisko powiązanymi a szerszym kontekstem tematycznym.

## Źródło danych

Importer korzysta z dwóch plików w katalogu artefaktów:

- `question-catalog.jsonl` — przypisania pytanie → temat główny → podtemat,
- `relations.jsonl` — znormalizowane pary pytań z typem, wynikiem i uzasadnieniem.

Plików źródłowych nie przechowujemy w repozytorium. Ścieżka jest argumentem operacyjnym komendy i może wskazywać katalog lokalny albo zamontowany wolumin wdrożeniowy.

Audyt dostarczonego zbioru z 17 lipca 2026 r. wykazał:

- 2180 wierszy katalogu i 2176 unikalnych identyfikatorów,
- cztery kolizyjne identyfikatory: `2328`, `3540`, `3545`, `3623`,
- 4804 wybrane pary relacji,
- 35 par dotykających identyfikatorów kolizyjnych — te rekordy muszą trafić do kwarantanny,
- jeden niemal globalny komponent grafu, dlatego komponenty spójne nie mogą pełnić roli klastrów SEO,
- 13 tematów głównych i 88 podtematów; klastry i huby budujemy z tej taksonomii.

## Model danych

### `question_relations`

Jedna, kanonicznie uporządkowana para wyjaśnień publicznych. Najważniejsze pola:

- `left_explanation_id`, `right_explanation_id`,
- `relation_type`: `blizniacze`, `ta_sama_zasada`, `wariant`, `kontrast`, `nie_pomyl_z`, `rozszerzenie`, `tematyczne`,
- `source`: `editorial` albo `graph`,
- `status`: `verified`, `automatic`, `candidate`, `rejected`,
- `score`, opis wspólnej zasady, różnica oraz kotwice dla obu kierunków,
- metadane wersji i przeglądu.

Relacje zapisane dotąd w JSON `question_public_explanations.related_questions` są migrowane jako `editorial + verified`. Import grafu nigdy ich nie obniża ani nie nadpisuje.

### Taksonomia SEO

- `question_seo_topics` — temat główny lub podtemat, relacja rodzic–dziecko, slug, opis i stan indeksowania,
- `question_seo_topic_memberships` — przypisanie pytania do podtematu,
- `question_seo_topic_relations` — kontrolowane, kierunkowe przejścia do tematów sąsiednich.

Podtemat staje się indeksowalnym hubem dopiero po przekroczeniu progu liczby pytań i uzupełnieniu wymaganej treści. Małe podtematy mogą służyć do rekomendacji, ale nie generują cienkich stron indeksowanych.

## Import i kwarantanna

Domyślny przebieg komendy jest tylko podglądem:

```bash
php artisan questions:import-seo-relations --path=/sciezka/do/seo-relations
```

Zapis wymaga jawnej flagi:

```bash
php artisan questions:import-seo-relations --path=/sciezka/do/seo-relations --write
```

Importer:

1. czyta JSONL strumieniowo,
2. odrzuca niepoprawny JSON i brakujące pola,
3. wykrywa powielone `source_id` i wyklucza wszystkie ich warianty,
4. rozwiązuje wyłącznie istniejące, opublikowane `question_public_explanations`,
5. zapisuje taksonomię i członkostwa,
6. normalizuje strony pary według ID rekordu w bazie,
7. pozostawia relacje grafowe jako kandydatów, poza mocnymi parami z tego samego podtematu spełniającymi próg automatycznej publikacji,
8. raportuje liczbę pominiętych, poddanych kwarantannie i zapisanych rekordów.

Próg automatycznej publikacji jest konfigurowalny. Startowo wynosi `0.50` i dodatkowo wymaga zgodnego podtematu po obu stronach.

## Algorytm linkowania

Dla bieżącego pytania selektor buduje grupy w tej kolejności:

1. wszystkie `verified` relacje redakcyjne,
2. wszystkie opublikowane relacje bezpośrednie z grafu (`automatic`),
3. pytania z tego samego podtematu, aż łączna liczba linków osiągnie minimum,
4. pytania z tego samego tematu głównego,
5. pytania z kontrolowanych tematów sąsiednich,
6. istniejący fallback kategorii egzaminacyjnej, jeśli dane taksonomii nie zapewniają minimum.

Kolejność relacji bezpośrednich: źródło redakcyjne, typ relacji, wynik malejąco, ręczna kolejność. Kandydaci bez statusu publikacji nie są widoczni publicznie.

Wynik ma następujące grupy prezentacyjne:

- `Najbliższe pytania`,
- `Ta sama zasada`,
- `Nie pomyl z`,
- `Więcej z tego tematu`,
- `Rozszerz temat`.

Każde pytanie występuje tylko raz. Link używa zawsze `canonical_url`.

## Komponent publiczny

Nagłówek: `Powiązane pytania`, z liczbą pytań pokazaną osobno jako informacja pomocnicza.

- Pierwsze osiem linków jest od razu widocznych na małym ekranie.
- Pozycje 9–15 są od razu widoczne od breakpointu desktopowego.
- Dalsze pozycje są w semantycznym `<details>`.
- Wszystkie odnośniki istnieją w SSR HTML bez zależności od JavaScriptu.
- Tekst kotwicy opisuje pytanie; numer i kategoria są informacją pomocniczą.

## Huby tematyczne

Trasa docelowa:

`/oficjalna-baza-pytan-na-prawo-jazdy/temat/{slug}`

Hub indeksowalny wymaga co najmniej 10 opublikowanych pytań, unikalnego tytułu, opisu i poprawnego rodzica. Huby indeksowalne trafiają do breadcrumbów oraz osobnej mapy witryny. Pozostałe zwracają `noindex,follow` albo nie są publicznie linkowane jako huby.

Pierwsza pomoc może być tematem sąsiednim wyłącznie dla scenariuszy semantycznie uzasadnionych, np. wypadek, omdlenie lub wezwanie służb. Nie jest globalnym wypełniaczem.

## Kolejność wdrożenia

1. Migracje i modele.
2. Importer z trybem podglądu oraz migracją starych relacji redakcyjnych.
3. Serwis rekomendacji i integracja strony pytania.
4. Komponent responsywny z kanonicznymi linkami.
5. Huby, breadcrumbs i sitemap.
6. Testy jednostkowe, feature oraz próbny import.
7. Pilot: 50 pytań z kilku podtematów, kontrola jakości i logów 404.
8. Pełny import i ponowne wygenerowanie sitemap.

## Kryteria odbioru

- każda strona pytania ma co najmniej 15 unikalnych linków, o ile baza zawiera co najmniej 16 publicznych pytań,
- wszystkie prawdziwe relacje bezpośrednie są pokazane, również gdy jest ich więcej niż 15,
- relacja redakcyjna nie zostaje nadpisana importem grafu,
- kolizyjne identyfikatory i ich pary nie trafiają do publikacji,
- kandydat poniżej progu nie jest publiczny,
- brak duplikatów i brak self-linków,
- wszystkie linki w komponencie są kanoniczne i obecne w SSR,
- małe huby nie są indeksowane ani dodawane do sitemap,
- ponowny import jest idempotentny.

## Wycofanie

Widok może zostać przełączony na dotychczasowy fallback przez `QUESTION_RELATIONS_ENABLED=false`. Dane pozostają w tabelach i mogą zostać poprawione bez utraty relacji redakcyjnych. Migracji nie cofamy na produkcji jako metody wyłączenia funkcji.

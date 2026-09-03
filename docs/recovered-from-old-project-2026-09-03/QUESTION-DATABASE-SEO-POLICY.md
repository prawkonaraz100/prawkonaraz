# Question Database SEO Policy

## 1. Cel

Ten dokument zapisuje decyzje SEO dla publicznej bazy pytan egzaminacyjnych po wdrozeniu `Sprintu 1`.

Jego rola jest praktyczna:

- ustalic, co jest indeksowane,
- ustalic, jakie typy schema emitujemy,
- zmniejszyc ryzyko przypadkowego mnozenia duplikatow i cienkich stron,
- dac zespolowi jedna prawde robocza przed dalsza rozbudowa topic pages, intent pages i facet pages.

## 2. Publiczne typy stron objete polityka

Na tym etapie polityka obejmuje:

- hub bazy pytan
- strony kategorii pytan
- strony pojedynczych pytan

Docelowo bedzie rozszerzona o:

- topic pages
- guide pages
- ranking / facet pages

## 3. Polityka indeksacji

### 3.1 Strony, ktore maja byc indeksowane

`index,follow`:

- `/oficjalna-baza-pytan-na-prawo-jazdy`
- `/oficjalna-baza-pytan-na-prawo-jazdy/{categorySlug}`
- `/pytanie/{externalId}/{slug}`

Powod:

- to sa strony kanoniczne,
- maja stabilny URL,
- maja unikalna role w architekturze informacji,
- sa bezposrednio wspierane przez sitemap i internal linking.

### 3.2 Strony, ktore maja byc domyslnie nieindeksowane

`noindex,follow` powinno byc domyslnym punktem startowym dla:

- surowych wynikow wyszukiwarki,
- stron filtrow generowanych przez query params,
- stron sortowan i eksperymentalnych listingow bez unikalnego intro,
- stron facetowych bez wystarczajacego `information gain`.

Takie strony moga wejsc do indeksu dopiero wtedy, gdy:

- dostana stabilny URL,
- dostana unikalny title,
- dostana unikalne intro copy,
- beda mialy sens biznesowy i SEO jako samodzielny landing.

## 4. Polityka canonical

### 4.1 Pytania

Kanoniczny URL pytania to zawsze:

- `/pytanie/{externalId}/{slug}`

Wersja bez sluga lub z blednym slugiem:

- nie jest traktowana jako rownorzedna strona,
- musi byc przekierowana `301` do URL-a kanonicznego.

### 4.2 Kategorie

Kanoniczny URL kategorii to:

- `/oficjalna-baza-pytan-na-prawo-jazdy/{categorySlug}`

Paginacja:

- strona `1` wskazuje podstawowy URL kategorii,
- strona `2+` ma self-canonical z parametrem `?page=`.

### 4.3 Hub

Kanoniczny URL huba to:

- `/oficjalna-baza-pytan-na-prawo-jazdy`

## 5. Polityka schema

### 5.1 Co emitujemy dzisiaj

Hub:

- `BreadcrumbList`
- `CollectionPage`

Kategorie:

- `BreadcrumbList`
- `CollectionPage`

Pytanie:

- `BreadcrumbList`
- `WebPage`
- zagniezdzony `Question` jako `mainEntity`

### 5.2 Dlaczego nie opieramy strategii na `QAPage`

Zgodnie z aktualna dokumentacja Google:

- [QAPage structured data](https://developers.google.com/search/docs/appearance/structured-data/qapage)

`QAPage` jest opisane jako model dla stron zawierajacych pytanie i odpowiedzi w formacie Q&A. Dokumentacja i przyklady mocno opieraja sie na modelu:

- pytanie,
- zaakceptowana odpowiedz,
- odpowiedzi sugerowane,
- autorzy odpowiedzi,
- glosy / interakcje znane z klasycznych stron Q&A.

Nasza karta pytania egzaminacyjnego nie jest klasyczna strona Q&A:

- nie ma wieloglosu odpowiedzi od roznych osob,
- nie jest forum lub community thread,
- prezentuje jedna autorytatywna tresc pytania i odpowiedzi.

Dlatego:

- `QAPage` nie jest naszym glownym filarem strategii,
- traktujemy je co najwyzej jako eksperyment kontrolowany,
- bezpiecznym baseline jest `BreadcrumbList + WebPage + Question`.

## 6. Polityka sitemap

W `Sprint 1` utrzymujemy trzy jawne sitemapy dla klastra pytan:

- sitemap huba pytan
- sitemap kategorii pytan
- sitemap pojedynczych pytan

Powod:

- chcemy rozdzielic poziomy architektury,
- latwiej monitorowac indeksacje,
- latwiej wychwycic problemy per typ strony.

## 7. Polityka internal linking

### 7.1 Zasada ogolna

Internal linking ma byc:

- stabilny,
- przewidywalny,
- semantyczny,
- crawlable.

### 7.2 Related questions

`Podobne pytania`:

- nie powinny byc losowane na kazdym requestcie,
- powinny byc deterministyczne,
- powinny preferowac najpierw pytania z tego samego tematu, potem z tej samej kategorii.

### 7.3 Wejscia do klastra

Hub pytan musi miec mocne linki z:

- headera
- home
- footera
- stron pokrewnych, takich jak `Najtrudniejsze pytania`

## 8. Warunek wejscia dla kolejnych typow stron

Kazdy nowy typ strony SEO w tym klastrze musi przed publikacja odpowiedziec na 5 pytan:

1. Czy ma stabilny kanoniczny URL?
2. Czy ma unikalny title?
3. Czy ma unikalny intro copy?
4. Czy wnosi nowa wartosc ponad hub, kategorie i pojedyncze pytanie?
5. Czy ma plan internal linkingu i miejsce w sitemap strategy?

Jesli odpowiedz na ktorekolwiek z tych pytan brzmi `nie`, strona nie powinna byc jeszcze targetem indeksacji.

## 9. Status decyzji

Status:

- `accepted` dla `Sprintu 1`

Do rewizji:

- przy wdrozeniu topic pages
- przy wdrozeniu intent pages
- przy wdrozeniu ranking / facet pages

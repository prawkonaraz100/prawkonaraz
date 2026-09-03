# Coverage Matrix: PJ360 wiedza i content

Data: 2026-03-26  
Status: pokrycie potwierdzone dla wskazanych stron contentowych

Dokument dotyczy tylko tej listy:

- `https://www.prawo-jazdy-360.pl/word`
- `https://www.prawo-jazdy-360.pl/znaki-drogowe`
- `https://www.prawo-jazdy-360.pl/kodeks-drogowy`
- `https://www.prawo-jazdy-360.pl/aktualnosci`
- `https://www.prawo-jazdy-360.pl/punkty-karne`
- `https://www.prawo-jazdy-360.pl/zmiany-na-prawo-jazdy-2026`
- `https://www.prawo-jazdy-360.pl/prawo-jazdy`
- `https://www.prawo-jazdy-360.pl/zielony-listek`
- `https://www.prawo-jazdy-360.pl/wyposazenie-samochodu`
- `https://www.prawo-jazdy-360.pl/jak-mozna-stracic-prawo-jazdy`

## Wynik

### Pokrycie: `10/10 URL-i`

Tak, ta konkretna lista jest juz pokryta.

Wazniejsze od samych URL-i jest jednak to, ze te strony nie sa jednym i tym samym widokiem.
Po sprawdzeniu wyszly nam `4` rozne rodziny shelli.

## 1. Shell `WORD / ranking-opinie`

### URL
- [word](https://www.prawo-jazdy-360.pl/word)

### Charakterystyka
- strona katalogowo-rankingowa,
- podzial na wojewodztwa / osrodki,
- listing opinii i zdawalnosci,
- mocny komponent mapy / geograficznego kontekstu,
- bardziej wertykal katalogowy niz klasyczny artykul.

### Status
- `pokryte`

## 2. Shell `znaki / katalog kategorii`

### URL
- [znaki-drogowe](https://www.prawo-jazdy-360.pl/znaki-drogowe)

### Charakterystyka
- indeks kategorii znakow,
- grid / card index tematow,
- strona bardziej katalogowa niz redakcyjna,
- osobny rytm od zwyklych artykulow.

### Status
- `pokryte`

## 3. Shell `kodeks / indeks prawa`

### URL
- [kodeks-drogowy](https://www.prawo-jazdy-360.pl/kodeks-drogowy)

### Charakterystyka
- strona indeksowa,
- sekcje dzialow kodeksu,
- search / wyszukiwarka,
- bardziej narzedzie referencyjne niz marketingowy article page.

### Status
- `pokryte`

## 4. Shell `aktualnosci / listing redakcyjny`

### URL
- [aktualnosci](https://www.prawo-jazdy-360.pl/aktualnosci)

### Charakterystyka
- listing wpisow,
- duzo kart / teaserow artykulow,
- paginacja,
- shell news/blog index.

### Status
- `pokryte`

## 5. Shell `tabelaryczny content ekspercki`

### URL
- [punkty-karne](https://www.prawo-jazdy-360.pl/punkty-karne)

### Charakterystyka
- strona contentowa, ale nie zwykly article,
- duza liczba tabel i sekcji taryfikatora,
- ekspercki, referencyjny charakter.

### Status
- `pokryte`

## 6. Shell `longform article`

### URL
- [zmiany-na-prawo-jazdy-2026](https://www.prawo-jazdy-360.pl/zmiany-na-prawo-jazdy-2026)
- [prawo-jazdy](https://www.prawo-jazdy-360.pl/prawo-jazdy)
- [zielony-listek](https://www.prawo-jazdy-360.pl/zielony-listek)
- [wyposazenie-samochodu](https://www.prawo-jazdy-360.pl/wyposazenie-samochodu)
- [jak-mozna-stracic-prawo-jazdy](https://www.prawo-jazdy-360.pl/jak-mozna-stracic-prawo-jazdy)

### Charakterystyka
- duzy `h1`,
- redakcyjny longform article,
- sekcje contentowe w pionie,
- ten sam ogolny shell artykulowy z innym contentem,
- CTA loop z reszta produktu.

### Status
- `pokryte`

## Wniosek projektowy

Dla tej warstwy nie mamy jednego shellu, tylko:

1. ranking / directory shell  
2. signs index shell  
3. legal code index shell  
4. news listing shell  
5. table-heavy expert content shell  
6. longform article shell

To oznacza, ze przy redesignie nie wolno wrzucic tego wszystkiego do jednego worka `baza wiedzy`.

## Co to znaczy praktycznie

Tak, ta lista jest pokryta.

Ale:
- `word` nie powinien byc projektowany jak zwykly artykul,
- `kodeks-drogowy` nie powinien byc projektowany jak `aktualnosci`,
- `punkty-karne` nie powinny byc projektowane jak `zielony-listek`.

Mamy pokrycie tej warstwy na tyle, zeby potem zrobic osobne layouty dla roznych pionow contentowych, zamiast jednego generycznego widoku.

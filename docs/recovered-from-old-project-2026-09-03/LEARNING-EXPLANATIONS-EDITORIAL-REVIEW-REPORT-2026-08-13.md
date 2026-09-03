# Raport redakcyjny: wyjasnienia bez krotkiej zasady

Data: 2026-08-13
Zrodlo: produkcyjny preview synchronizacji `63`
Zakres: 337 opublikowanych wyjasnien, ktore nie przeszly obecnego,
konserwatywnego parsera krotkiej zasady.

## Wynik

| Grupa | Liczba | Dalsze postepowanie |
| --- | ---: | --- |
| Brak frazy `zasada do zapamietania` | 278 | Audyt tresci i dopisanie lub rozpoznanie jednoznacznej etykiety |
| Fraza jest obecna, ale format nie jest bezpieczny | 59 | Oddzielny, bardzo waski parser po testach |
| Gotowa regula pod etykieta `**Prosta zasada:**` lub podobna | 101 | Kandydaci do osobnego parsera etykiety alternatywnej |
| Regula w calosci pogrubiona, lacznie z etykieta | 35 | Kandydaci do osobnego parsera jednego pogrubionego zdania |
| Wymagaja redakcyjnego podsumowania | 201 | Nie importowac automatycznie |
| Pytania z medium wsrod 337 rekordow | 299 | Dobre kandydaty do wizualnego przegladu |
| Rekordy bez lokalnego pytania | 0 | Nie wystepuja |

Zestawy kandydatow czesciowo wynikaja z formatu, nie z podobienstwa tresci.
Nie nalezy laczyc ich ani importowac na podstawie AI, numeru w URL lub
przyblizonego dopasowania.

## Trzy sprawdzone przyklady

### `10041` - dobra regula pod inna etykieta

Publiczne wyjasnienie konczy sie jasno sformulowana sekcja
`**Prosta zasada:**`. Tresc jest zrozumiala i krotka, ale obecny parser
celowo jej nie dopuszcza, bo akceptuje wylacznie jawna etykiete
`Zasada do zapamietania`.

Wniosek: kandydat do osobnego rozszerzenia parsera z testem zachowania
wewnetrznych pogrubien. Nie zmieniac jeszcze zadnych pytan.

Publiczna strona: [pytanie 10041](https://prawkonaraz.pl/pytanie/10041).

### `13142` - regula i etykieta zapisane w jednym pogrubieniu

Wyjasnienie zawiera krotka zasade, ale caly fragment jest zapisany jako
jedno pogrubione zdanie: `**Zasada do zapamietania: ...**`.

Wniosek: to dobra kandydatura do nowej, scisle ograniczonej regulki parsera.
Najpierw nalezy zbudowac liste wszystkich 35 takich rekordow, przetestowac
wynik na kazdym z nich i dopiero wtedy wykonac osobny preview.

Publiczna strona: [pytanie 13142](https://prawkonaraz.pl/pytanie/13142).

### `13784` - prawidlowa tresc, ale brak podsumowania

Wyjasnienie dobrze opisuje strefe zamieszkania i pierwszenstwo uczestnika
ruchu, lecz nie ma wydzielonej jednej zasady do zapamietania.

Wniosek: nie tworzyc automatycznego skrotu. Ten przypadek wymaga redakcyjnego
dopisania jednej, zatwierdzonej zasady.

Publiczna strona: [pytanie 13784](https://prawkonaraz.pl/pytanie/13784).

## Artefakt pelnej listy

Pelny raport JSON zawiera dla kazdego rekordu: `external_id`, URL publiczny,
pelny tekst wyjasnienia, pytanie, kategorie, liczbe mediow oraz klasyfikacje.
Zostal pobrany z produkcji do lokalnego pliku:

`output/reports/public-memory-rules-editorial-review-20260813.json`

## Rekomendowana kolejnosc

1. Przejrzec 35 rekordow z regula w calosci pogrubiona i przygotowac wylacznie
   dla nich nowy parser oraz testy.
2. Przejrzec 101 rekordow z etykieta `Prosta zasada`; zaakceptowac tylko
   rzeczywiscie krotkie, jednoznaczne podsumowania.
3. Dla pozostalych 201 przypadkow przygotowac redakcyjne zasady partiami,
   zaczynajac od pytan z medium i najczesciej wykorzystywanych kategorii.
4. Kazda kolejna partia przechodzi przez osobny preview, canary i snapshot;
   obecnej synchronizacji run `64` nie wolno nadpisywac zbiorczo.

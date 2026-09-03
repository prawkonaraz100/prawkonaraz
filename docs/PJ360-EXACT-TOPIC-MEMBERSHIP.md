# PJ360 Exact Topic Membership

## Cel

Ten dokument opisuje nowy, docelowy kierunek domykania zgodnosci tematow z PJ360:

- nie tylko liczniki per temat,
- nie tylko heurystyka klasyfikatora,
- ale exact-membership:
  - `kategoria -> temat -> konkretne pytania`.

To jest naturalny kolejny krok po audycie `PJ360 vs /nauka`, bo wczesniejsze artefakty dawaly nam glownie:

- identyfikacje pytan PJ360,
- overlap promptow,
- korpus wyjasnien,
- liczniki per temat.

Nie dawaly jeszcze kanonicznej listy:

- `B -> Znaki ostrzegawcze -> 114 pytan`
- `B -> Znaki zakazu, nakazu -> 102 pytania`
- itd.

## Co juz mielismy przed tym pivotem

Wczesniejsze porownanie PJ360:

- [PJ360-EXPLANATION-ADAPTATION-PLAN.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXPLANATION-ADAPTATION-PLAN.md)
- [PJ360-EXPLANATION-ADAPTATION-RUNBOOK.md](C:/Users/xxx/Desktop/serwistestyprawojazdy/docs/PJ360-EXPLANATION-ADAPTATION-RUNBOOK.md)

potwierdzilo, ze:

- `3076` naszych pytan ma exact prompt overlap z PJ360,
- `3087` ma co najmniej loose overlap,
- mamy bardzo duzy pokrywajacy sie katalog pytan.

To jest bardzo mocna baza do exact-membership, ale to nie jest jeszcze source-of-truth dla:

- dzialu `/kurs/<slug>`
- i jego paginowanej listy pytan.

## Nowa decyzja operacyjna

Dla aktywnie domykanych kategorii:

- najpierw zaciagamy exact listy pytan z PJ360 per temat,
- potem mapujemy je do naszej bazy,
- potem generujemy reviewable lub exact override package,
- dopiero na koncu synchronizujemy override do systemu.

W praktyce oznacza to model:

1. `extract exact topic membership`
2. `match membership -> local questions`
3. `build exact override package`
4. `sync overrides`
5. `reclassify`
6. `delta report`

## Dlaczego to jest lepsze od dalszego strojenia samych heurystyk

Bo rozdziela dwa problemy:

- `mamy pytanie, ale siedzi w zlym temacie`
- `PJ360 ma pytanie, a my go w tej kategorii po prostu nie mamy`

Po exact-membership przestajemy zgadywac:

- czy brak `97 vs 114` w `warning_signs` wynika z klasyfikacji,
- czy z realnego braku pytan.

To widac od razu po przecieciu zbiorow.

## Zakres pierwszej implementacji

Pierwszy rollout exact-membership:

- zaczyna od `kat. B`,
- jako source-of-truth bierze strony `/kurs/<topic-slug>`,
- czyta paginacje `strona=1..N`,
- parsuje wszystkie pytania z HTML topic page,
- mapuje etykiety PJ360 na nasze `topic_key`,
- buduje exact override package dla lokalnych pytan, ktore juz mamy.

## Zasady bezpieczenstwa

- exact-membership nie nadpisuje nic bez artefaktu JSON i mozliwosci review,
- unresolved i ambiguous przypadki nie wchodza do sync automatem,
- exact package ma byc odtwarzalny i wersjonowany,
- matching musi zostawic slad:
  - prompt,
  - accepted answer,
  - media kind,
  - topic slug,
  - topic label,
  - category.

## Operacyjny status

Na tym etapie:

- exact-membership jest uznany za kanoniczny kierunek dla domykania `B`,
- extractor i exact-package builder sa wdrazane jako nowa warstwa robocza,
- dotychczasowe fale override dla `B` pozostaja waznym etapem przejsciowym,
- ale kolejne duze ruchy powinny juz wychodzic z exact list PJ360, nie z samych heurystyk.

## Mirror snapshot po naszej stronie

Od tego etapu trzymamy juz nie tylko exact-membership z PJ360, ale tez lustrzany snapshot lokalny w tym samym duchu:

- `kategoria -> temat -> liczba pytan -> konkretne pytania`

Kanoniczne artefakty:

- [exact-topic-membership-all.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/exact-topic-membership-all.json)
- [local-effective-topic-membership-all.json](C:/Users/xxx/Desktop/serwistestyprawojazdy/output/analysis/pj360-exact-topic-membership/local-effective-topic-membership-all.json)

Lokalny snapshot:

- bierze tylko `active + ready`,
- czyta realne `question_topic_id`,
- odzwierciedla to, co faktycznie zasila nasze `/nauka`,
- zapisuje konkretne pytania z:
  - `question_id`
  - `external_id`
  - `source`
  - `prompt`
  - `accepted_answer`
  - `question_media_kind`
  - `main_media_original`

To jest teraz kanoniczna para do porownan:

1. PJ360 membership
2. local effective membership

czyli przestajemy porownywac tylko liczniki, a mozemy porownywac 1:1:

- temat,
- liczbe,
- i konkretne pytania.

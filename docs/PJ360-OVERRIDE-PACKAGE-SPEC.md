# PJ360 Override Package Spec

## Cel

Ten dokument opisuje kanoniczny format review-only paczek override budowanych ze specyfikacji.

Same wykonywalne pliki spec trzymamy w repo poza `docs/`, w:

- [resources/topic-overrides](C:/Users/xxx/Desktop/serwistestyprawojazdy/resources/topic-overrides)

zeby komendy uruchamiane w kontenerze mogly je czytac bez dodatkowego kopiowania.

To jest kolejny etap po:

- classifier baseline
- audit-only reclassifier
- review-only eksporcie `current != classifier`

Ten mechanizm jest potrzebny wtedy, gdy:

- lokalna baza jest juz zgodna z obecnym classifierem
- ale nadal nie jest zgodna z targetem PJ360

## Dlaczego uzywamy spec-driven package

Sam classifier nie powinien odpowiadac za wszystkie ostatnie mile do PJ360, bo:

- poprawa jednej kategorii moze psuc inna
- czesc ruchow jest kategoria-zalezna
- chcemy review i rollback na poziomie konkretnej paczki

Dlatego najbezpieczniejszy model jest taki:

1. classifier daje baseline
2. spec definiuje tylko te precyzyjne ruchy, ktore chcemy domknac
3. builder generuje review-only JSON override
4. sync importuje ten JSON do `question_topic_overrides`

## Format specyfikacji

Plik spec powinien byc obiektem JSON:

```json
{
  "category": "B",
  "scope": "active_ready",
  "rules": [
    {
      "name": "b-road-markings-reflectors",
      "target_topic_key": "road_markings",
      "reason": "PJ360 B: oznakowanie poziome i odblaski",
      "current_topic_keys": [
        "road_position_entry_exit_stopping",
        "intersections_with_priority_signs"
      ],
      "classifier_matched_by": [
        "keyword:road_markings_reflectors"
      ]
    }
  ]
}
```

## Pola top-level

- `category`
  - wymagane
  - kod kategorii, np. `B`, `C1`, `T`
- `scope`
  - opcjonalne
  - `active_ready`, `active` albo `all`
  - domyslnie `active_ready`
- `rules`
  - wymagane
  - niepusta lista regul

## Pola rule

- `name`
  - opcjonalne
  - techniczna nazwa reguly do raportu
- `target_topic_key`
  - wymagane
  - temat docelowy override
- `reason`
  - wymagane
  - operatorowa przyczyna, ktora trafi do JSON i tabeli override
- `current_topic_keys`
  - opcjonalne
  - allowlista tematow biezacych
- `classifier_matched_by`
  - opcjonalne
  - allowlista `matched_by` z classifiera
- `prompt_contains_any`
  - opcjonalne
  - allowlista fraz, ktore po normalizacji musza wystapic w `prompt`
- `prompt_not_contains_any`
  - opcjonalne
  - frazy, ktore po normalizacji nie moga wystapic w `prompt`
- `media_contains_any`
  - opcjonalne
  - allowlista fragmentow dopasowywanych do `questions.metadata.main_media_original`
- `media_not_contains_any`
  - opcjonalne
  - fragmenty, ktore nie moga wystapic w `questions.metadata.main_media_original`
- `external_ids`
  - opcjonalne
  - allowlista stabilnych `external_id` do bardzo precyzyjnych batchy review

## Gwarancje bezpieczenstwa buildera

Builder review-only:

- nie zapisuje nic do bazy
- pomija pytania bez:
  - `source`
  - `external_id`
- pomija rekordy, ktore juz siedza w `target_topic_key`
- deduplikuje po stabilnym kluczu:
  - `license_category_code`
  - `source`
  - `external_id`
- zapisuje do metadata pomocniczo:
  - `prompt_excerpt`
  - `main_media_original`
  - `classifier_matched_by`

To sprawia, ze jedna paczka jest:

- powtarzalna
- reviewowalna
- bezpieczna do synchronizacji

## Zalecany workflow

1. Tworzymy spec JSON dla jednej kategorii i jednej fali.
2. Budujemy review-only paczke override ze specyfikacji:
   - `content:build-question-topic-override-package <spec.json> --json=<package.json>`
3. Robimy review wygenerowanego JSON.
4. Uruchamiamy `content:sync-question-topic-overrides --dry-run`.
5. Uruchamiamy realny sync.
6. Przeliczamy temat dla kategorii:
   - `content:classify-question-topics --refresh --category=<KOD>`
7. Robimy raport delty:
   - `content:report-question-topic-assignment-delta --category=<KOD>`

## Zasada utrzymania

Spec nie zastępuje:

- target matrix
- audytu PJ360
- klasyfikatora

Spec jest tylko precyzyjna warstwa rolloutowa pomiedzy:

- diagnoza
- a synchronizacja override do bazy

## Aktualny status

Ten mechanizm jest juz wdrozony i wspiera:

- stare filtry:
  - `current_topic_keys`
  - `classifier_matched_by`
- nowe filtry do full closure kategorii:
  - `prompt_contains_any`
  - `prompt_not_contains_any`
  - `media_contains_any`
  - `media_not_contains_any`
  - `external_ids`

To jest aktualnie glowna droga do domykania `kat. B` temat po temacie bez ryzyka masowego, slepego przepinania rekordow.

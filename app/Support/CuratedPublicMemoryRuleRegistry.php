<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class CuratedPublicMemoryRuleRegistry
{
    /**
     * @var array<string, string>
     */
    private const RULES = [
        '11018' => '**Zasada do zapamiętania:** przekroczenie prędkości o ponad 50 km/h w obszarze zabudowanym oznacza pokwitowanie ważne przez 24 godziny od zatrzymania prawa jazdy.',
        '6194' => '**Zasada do zapamiętania:** przeciwny kierunek to wymijanie, ten sam kierunek to wyprzedzanie, a nieruchomy pojazd lub przeszkoda to omijanie.',
        '6205' => '**Zasada do zapamiętania:** przeciwny kierunek to wymijanie, ten sam kierunek to wyprzedzanie, a nieruchomy pojazd lub przeszkoda to omijanie.',
        '8317' => '**Zasada do zapamiętania:** A-9 ostrzega o przejeździe kolejowym z zaporami lub półzaporami, a A-10 o przejeździe bez zapór.',
        '13575' => '**Zasada do zapamiętania:** C-12 bez A-7 oznacza zasadę prawej strony; C-12 z A-7 daje pierwszeństwo pojazdom już znajdującym się na skrzyżowaniu.',
        '13629' => '**Zasada do zapamiętania:** mała różnica prędkości oznacza dłuższy czas i dłuższą drogę wyprzedzania.',
    ];

    /**
     * @param  list<string>  $requestedExternalIds
     * @return array<string, string>
     */
    public function rulesFor(array $requestedExternalIds = []): array
    {
        $requestedExternalIds = array_values(array_unique(array_filter(
            array_map(fn (mixed $externalId): string => trim((string) $externalId), $requestedExternalIds),
        )));

        if ($requestedExternalIds === []) {
            return self::RULES;
        }

        $unsupported = array_values(array_diff($requestedExternalIds, array_keys(self::RULES)));

        if ($unsupported !== []) {
            throw ValidationException::withMessages([
                'external-id' => 'Brak zatwierdzonej zasady redakcyjnej dla: '.implode(', ', $unsupported).'.',
            ]);
        }

        return array_intersect_key(self::RULES, array_flip($requestedExternalIds));
    }
}

<?php

namespace App\Support;

class PolishTramSignalCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return [
            [
                'code' => 'ST-1',
                'name' => 'Sygnał zakazujący wjazdu za sygnalizator (pozioma kreska)',
                'slug' => 'st-1-zakaz-wjazdu-pozioma-kreska',
                'primary_query' => 'sygnal st zakaz wjazdu tramwaj',
            ],
            [
                'code' => 'ST-2',
                'name' => 'Sygnał zezwalający na jazdę na wprost (pionowa kreska)',
                'slug' => 'st-2-jazda-na-wprost-pionowa-kreska',
                'primary_query' => 'sygnal st jazda prosto tramwaj',
            ],
            [
                'code' => 'ST-3',
                'name' => 'Sygnał zezwalający na skręcanie (ukośna kreska)',
                'slug' => 'st-3-jazda-w-kierunku-ukosna-kreska',
                'primary_query' => 'sygnal st jazda w prawo lewo tramwaj',
            ],
            [
                'code' => 'ST-4',
                'name' => 'Sygnał o zmianie zezwolenia (migająca kreska)',
                'slug' => 'st-4-zmiana-sygnalu-migajaca-kreska',
                'primary_query' => 'sygnal st migajacy tramwaj',
            ],
        ];
    }
}

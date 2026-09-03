<?php

namespace App\Support;

class PolishTramSignCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return [
            [
                'code' => 'AT-1',
                'name' => 'Sygnalizacja świetlna',
                'slug' => 'at-1-sygnalizacja-swietlna',
                'primary_query' => 'znak tramwajowy at-1 sygnalizacja swietlna',
            ],
            [
                'code' => 'AT-2',
                'name' => 'Sygnalizacja świetlna wzbudzana',
                'slug' => 'at-2-sygnalizacja-swietlna-wzbudzana',
                'primary_query' => 'znak tramwajowy at-2 sygnalizacja wzbudzana',
            ],
            [
                'code' => 'AT-3',
                'name' => 'Niebezpieczny zjazd',
                'slug' => 'at-3-niebezpieczny-zjazd',
                'primary_query' => 'znak tramwajowy at-3 niebezpieczny zjazd',
            ],
            [
                'code' => 'AT-4',
                'name' => 'Stromy podjazd',
                'slug' => 'at-4-stromy-podjazd',
                'primary_query' => 'znak tramwajowy at-4 stromy podjazd',
            ],
            [
                'code' => 'AT-5',
                'name' => 'Ruch kolizyjny',
                'slug' => 'at-5-ruch-kolizyjny',
                'primary_query' => 'znak tramwajowy at-5 ruch kolizyjny',
            ],
            [
                'code' => 'BT-1',
                'name' => 'Ograniczenie prędkości',
                'slug' => 'bt-1-ograniczenie-predkosci',
                'primary_query' => 'znak tramwajowy bt-1 ograniczenie predkosci',
            ],
        ];
    }
}

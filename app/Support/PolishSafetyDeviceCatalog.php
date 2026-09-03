<?php

namespace App\Support;

class PolishSafetyDeviceCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return [
            [
                'code' => 'U-1a',
                'name' => 'Słupek wskaźnikowy (prowadzący)',
                'slug' => 'u-1a-slupek-wskaznikowy-prowadzacy',
                'primary_query' => 'urzadzenie brd u-1a slupek prowadzacy odblaskowy',
            ],
            [
                'code' => 'U-5a',
                'name' => 'Słupek przeszkodowy',
                'slug' => 'u-5a-slupek-przeszkodowy',
                'primary_query' => 'urzadzenie brd u-5a slupek na wysepce',
            ],
            [
                'code' => 'U-9',
                'name' => 'Pachołek drogowy',
                'slug' => 'u-9-pacholek-drogowy',
                'primary_query' => 'urzadzenie brd u-9 pacholek drogowy',
            ],
            [
                'code' => 'U-12',
                'name' => 'Zapora drogowa',
                'slug' => 'u-12-zapora-drogowa',
                'primary_query' => 'urzadzenie brd u-12 zapora drogowa',
            ],
            [
                'code' => 'U-21',
                'name' => 'Tablica kierująca (sierżant)',
                'slug' => 'u-21-tablica-kierujaca-sierzant',
                'primary_query' => 'urzadzenie brd u-21 tablica sierżant',
            ],
            [
                'code' => 'U-25',
                'name' => 'Separator ruchu',
                'slug' => 'u-25-separator-ruchu',
                'primary_query' => 'urzadzenie brd u-25 separator ruchu krawężnik',
            ],
        ];
    }
}

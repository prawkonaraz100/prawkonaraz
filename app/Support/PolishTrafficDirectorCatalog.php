<?php

namespace App\Support;

class PolishTrafficDirectorCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return [
            [
                'code' => 'R-1',
                'name' => 'Policjant z podniesioną ręką',
                'slug' => 'r-1-postawa-z-podniesiona-reka',
                'primary_query' => 'policjant z podniesiona reka r-1',
            ],
            [
                'code' => 'R-2',
                'name' => 'Policjant przodem lub tyłem do kierunku ruchu',
                'slug' => 'r-2-postawa-przodem-lub-tylem',
                'primary_query' => 'policjant kierujacy ruchem przodem tylem r-2',
            ],
            [
                'code' => 'R-3',
                'name' => 'Policjant bokiem do kierunku ruchu',
                'slug' => 'r-3-postawa-bokiem',
                'primary_query' => 'policjant kierujacy ruchem bokiem r-3',
            ],
            [
                'code' => 'R-4',
                'name' => 'Policjant z ręką wyciągniętą w kierunku pojazdu',
                'slug' => 'r-4-reka-wyciagnieta-w-kierunku-pojazdu',
                'primary_query' => 'policjant zatrzymanie reka wyciagnieta r-4',
            ],
        ];
    }
}

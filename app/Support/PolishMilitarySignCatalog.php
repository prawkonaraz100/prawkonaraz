<?php

namespace App\Support;

class PolishMilitarySignCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return [
            [
                'code' => 'W-1',
                'name' => 'Klasa obciążenia mostu o ruchu jednokierunkowym',
                'slug' => 'w-1-klasa-obciazenia-mostu-ruch-jednokierunkowy',
                'primary_query' => 'znak wojskowy w-1 most jednokierunkowy',
            ],
            [
                'code' => 'W-2',
                'name' => 'Klasa obciążenia mostu o ruchu dwukierunkowym',
                'slug' => 'w-2-klasa-obciazenia-mostu-ruch-dwukierunkowy',
                'primary_query' => 'znak wojskowy w-2 most dwukierunkowy',
            ],
            [
                'code' => 'W-3',
                'name' => 'Klasa obciążenia mostu o ruchu jednokierunkowym i dwukierunkowym',
                'slug' => 'w-3-klasa-obciazenia-mostu-jedno-dwukierunkowy',
                'primary_query' => 'znak wojskowy w-3',
            ],
            [
                'code' => 'W-4',
                'name' => 'Klasa obciążenia mostu o ruchu jednokierunkowym dla pojazdów kołowych i gąsienicowych',
                'slug' => 'w-4-klasa-obciazenia-mostu-pojazdy-kolowe-gasienicowe',
                'primary_query' => 'znak wojskowy w-4 pojazdy gasienicowe',
            ],
            [
                'code' => 'W-5',
                'name' => 'Klasa obciążenia mostu o ruchu dwukierunkowym dla pojazdów kołowych i gąsienicowych',
                'slug' => 'w-5-klasa-obciazenia-mostu-dwukierunkowy-kolowe-gasienicowe',
                'primary_query' => 'znak wojskowy w-5',
            ],
            [
                'code' => 'W-6',
                'name' => 'Szerokość mostu lub samej jezdni',
                'slug' => 'w-6-szerokosc-mostu',
                'primary_query' => 'znak wojskowy w-6 szerokosc mostu',
            ],
            [
                'code' => 'W-7',
                'name' => 'Wysokość skrajni pionowej nad jezdnią',
                'slug' => 'w-7-wysokosc-skrajni-pionowej',
                'primary_query' => 'znak wojskowy w-7 wysokosc skrajni',
            ],
        ];
    }
}

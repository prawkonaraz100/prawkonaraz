<?php

namespace App\Support;

class PolishComplementarySignCatalog
{
    /**
     * @return list<array{code: string, slug: string, name: string, primary_query: string}>
     */
    public function all(): array
    {
        return [
            ['code' => 'F-1', 'slug' => 'f-1-przejscie-graniczne', 'name' => 'Przejście graniczne', 'primary_query' => 'f-1 przejście graniczne'],
            ['code' => 'F-5', 'slug' => 'f-5-uprzedzenie-o-zakazie', 'name' => 'Uprzedzenie o zakazie', 'primary_query' => 'f-5 uprzedzenie o zakazie'],
            ['code' => 'F-10', 'slug' => 'f-10-kierunki-na-pasach-ruchu', 'name' => 'Kierunki na pasach ruchu', 'primary_query' => 'f-10 kierunki na pasach ruchu'],
            ['code' => 'F-11', 'slug' => 'f-11-kierunki-na-pasie-ruchu', 'name' => 'Kierunki na pasie ruchu', 'primary_query' => 'f-11 kierunki na pasie ruchu'],
        ];
    }
}

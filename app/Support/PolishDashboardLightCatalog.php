<?php

namespace App\Support;

class PolishDashboardLightCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return [
            [
                'code' => 'K-1',
                'name' => 'Kontrolka awarii silnika (Check Engine)',
                'slug' => 'k-1-check-engine',
                'primary_query' => 'kontrolka silnika check engine k-1',
            ],
            [
                'code' => 'K-2',
                'name' => 'Kontrolka ciśnienia oleju silnikowego',
                'slug' => 'k-2-cisnienie-oleju',
                'primary_query' => 'kontrolka oleju silnikowego k-2',
            ],
            [
                'code' => 'K-3',
                'name' => 'Kontrolka ładowania akumulatora',
                'slug' => 'k-3-akumulator',
                'primary_query' => 'kontrolka ladowania akumulatora k-3',
            ],
            [
                'code' => 'K-4',
                'name' => 'Kontrolka temperatury płynu chłodniczego',
                'slug' => 'k-4-temperatura-plynu-chlodniczego',
                'primary_query' => 'kontrolka temperatury plynu chlodniczego k-4',
            ],
            [
                'code' => 'K-5',
                'name' => 'Kontrolka układu hamulcowego / hamulca ręcznego',
                'slug' => 'k-5-uklad-hamulcowy',
                'primary_query' => 'kontrolka uklad hamulcowy reczny k-5',
            ],
        ];
    }
}

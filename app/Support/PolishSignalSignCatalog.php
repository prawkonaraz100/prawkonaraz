<?php

namespace App\Support;

class PolishSignalSignCatalog
{
    /**
     * @return list<array{code: string, slug: string, name: string, primary_query: string}>
     */
    public function all(): array
    {
        return [
            ['code' => 'S-1', 'slug' => 's-1-sygnalizator-ogolny', 'name' => 'Sygnalizator z sygnałami ogólnymi', 'primary_query' => 's-1 sygnalizator ogólny'],
            ['code' => 'S-1a', 'slug' => 's-1a-sygnal-czerwony', 'name' => 'Sygnał czerwony', 'primary_query' => 's-1 sygnał czerwony'],
            ['code' => 'S-1b', 'slug' => 's-1b-sygnal-zolty', 'name' => 'Sygnał żółty', 'primary_query' => 's-1 sygnał żółty'],
            ['code' => 'S-1c', 'slug' => 's-1c-sygnal-zielony', 'name' => 'Sygnał zielony', 'primary_query' => 's-1 sygnał zielony'],
            ['code' => 'S-1d', 'slug' => 's-1d-sygnaly-czerwony-i-zolty', 'name' => 'Sygnały czerwony i żółty', 'primary_query' => 's-1 sygnały czerwony i żółty'],
            ['code' => 'S-2', 'slug' => 's-2-sygnalizator-ze-strzalka-warunkowa', 'name' => 'Sygnalizator z warunkowym zezwoleniem na skręt w prawo', 'primary_query' => 's-2 warunkowe zezwolenie na skręt w prawo'],
            ['code' => 'S-3a', 'slug' => 's-3a-sygnalizator-kierunkowy-na-wprost-i-w-lewo', 'name' => 'Sygnalizator kierunkowy na wprost i w lewo', 'primary_query' => 's-3a sygnalizator kierunkowy na wprost i w lewo'],
            ['code' => 'S-3b', 'slug' => 's-3b-sygnalizator-kierunkowy-na-wprost-i-w-prawo', 'name' => 'Sygnalizator kierunkowy na wprost i w prawo', 'primary_query' => 's-3b sygnalizator kierunkowy na wprost i w prawo'],
            ['code' => 'S-3c', 'slug' => 's-3c-sygnalizator-kierunkowy-na-wprost', 'name' => 'Sygnalizator kierunkowy na wprost', 'primary_query' => 's-3c sygnalizator kierunkowy na wprost'],
            ['code' => 'S-3d', 'slug' => 's-3d-sygnalizator-kierunkowy-w-prawo', 'name' => 'Sygnalizator kierunkowy w prawo', 'primary_query' => 's-3d sygnalizator kierunkowy w prawo'],
            ['code' => 'S-3e', 'slug' => 's-3e-sygnalizator-kierunkowy-w-lewo', 'name' => 'Sygnalizator kierunkowy w lewo', 'primary_query' => 's-3e sygnalizator kierunkowy w lewo'],
            ['code' => 'S-3f', 'slug' => 's-3f-sygnalizator-kierunkowy-w-lewo-zezwalajacy-na-zawracanie', 'name' => 'Sygnalizator kierunkowy w lewo zezwalający na zawracanie', 'primary_query' => 's-3f sygnalizator kierunkowy w lewo zezwalający na zawracanie'],
            ['code' => 'S-3g', 'slug' => 's-3g-sygnalizator-kierunkowy-dla-zawracajacych', 'name' => 'Sygnalizator kierunkowy dla zawracających', 'primary_query' => 's-3g sygnalizator kierunkowy dla zawracających'],
        ];
    }
}

<?php

namespace App\Support;

class PolishPlateSignCatalog
{
    /**
     * @return list<array{code: string, slug: string, name: string, primary_query: string}>
     */
    public function all(): array
    {
        return [
            ['code' => 'T-1', 'slug' => 't-1-odleglosc-znaku-ostrzegawczego-od-miejsca-niebezpiecznego', 'name' => 'Tabliczka wskazująca odległość znaku ostrzegawczego od miejsca niebezpiecznego', 'primary_query' => 't-1 odległość od miejsca niebezpiecznego'],
            ['code' => 'T-6a', 'slug' => 't-6a-tabliczka-wskazujaca-rzeczywisty-przebieg-drogi-z-pierwszenstwem', 'name' => 'Tabliczka wskazująca rzeczywisty przebieg drogi z pierwszeństwem przez skrzyżowanie', 'primary_query' => 't-6a tabliczka łamane pierwszeństwo'],
            ['code' => 'T-24', 'slug' => 't-24-tabliczka-wskazujaca-ze-pozostawiony-pojazd-zostanie-usuniety', 'name' => 'Tabliczka wskazująca, że pozostawiony pojazd zostanie usunięty na koszt właściciela', 'primary_query' => 't-24 tabliczka odholowanie'],
            ['code' => 'T-30', 'slug' => 't-30-tabliczka-wskazujaca-spadek-podluzny-drogi', 'name' => 'Tabliczka wskazująca spadek podłużny drogi', 'primary_query' => 't-30 tabliczka spadek podłużny'],
        ];
    }
}

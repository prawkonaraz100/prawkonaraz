<?php

namespace App\Support;

class PolishRailwaySignCatalog
{
    /**
     * @return list<array{code: string, slug: string, name: string, primary_query: string}>
     */
    public function all(): array
    {
        return [
            ['code' => 'G-1a', 'slug' => 'g-1a-slupek-wskaznikowy-z-trzema-kreskami-umieszczany-po-prawej-stronie-jezdni', 'name' => 'Słupek wskaźnikowy z trzema kreskami umieszczany po prawej stronie jezdni', 'primary_query' => 'g-1a słupek z trzema kreskami'],
            ['code' => 'G-1b', 'slug' => 'g-1b-slupek-wskaznikowy-z-dwiema-kreskami-umieszczany-po-prawej-stronie-jezdni', 'name' => 'Słupek wskaźnikowy z dwiema kreskami umieszczany po prawej stronie jezdni', 'primary_query' => 'g-1b słupek z dwiema kreskami'],
            ['code' => 'G-1c', 'slug' => 'g-1c-slupek-wskaznikowy-z-jedna-kreska-umieszczany-po-prawej-stronie-jezdni', 'name' => 'Słupek wskaźnikowy z jedną kreską umieszczany po prawej stronie jezdni', 'primary_query' => 'g-1c słupek z jedną kreską'],
            ['code' => 'G-1d', 'slug' => 'g-1d-slupek-wskaznikowy-z-trzema-kreskami-umieszczany-po-lewej-stronie-jezdni', 'name' => 'Słupek wskaźnikowy z trzema kreskami umieszczany po lewej stronie jezdni', 'primary_query' => 'g-1d słupek z trzema kreskami'],
            ['code' => 'G-1e', 'slug' => 'g-1e-slupek-wskaznikowy-z-dwiema-kreskami-umieszczany-po-lewej-stronie-jezdni', 'name' => 'Słupek wskaźnikowy z dwiema kreskami umieszczany po lewej stronie jezdni', 'primary_query' => 'g-1e słupek z dwiema kreskami'],
            ['code' => 'G-1f', 'slug' => 'g-1f-slupek-wskaznikowy-z-jedna-kreska-umieszczany-po-lewej-stronie-jezdni', 'name' => 'Słupek wskaźnikowy z jedną kreską umieszczany po lewej stronie jezdni', 'primary_query' => 'g-1f słupek z jedną kreską'],
            ['code' => 'G-3', 'slug' => 'g-3-krzyz-sw-andrzeja-przed-przejazdem-kolejowym-jednotorowym', 'name' => 'Krzyż św. Andrzeja przed przejazdem kolejowym jednotorowym', 'primary_query' => 'g-3 krzyż św andrzeja jednotorowy'],
            ['code' => 'G-4', 'slug' => 'g-4-krzyz-sw-andrzeja-przed-przejazdem-kolejowym-wielotorowym', 'name' => 'Krzyż św. Andrzeja przed przejazdem kolejowym wielotorowym', 'primary_query' => 'g-4 krzyż św andrzeja wielotorowy'],
        ];
    }
}

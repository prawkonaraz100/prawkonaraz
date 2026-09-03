<?php

namespace App\Support;

class PolishMandatorySignCatalog
{
    /**
     * @return list<array{code: string, slug: string, name: string, primary_query: string}>
     */
    public function all(): array
    {
        return [
            ['code' => 'C-1', 'slug' => 'c-1-nakaz-jazdy-w-prawo', 'name' => 'Nakaz jazdy w prawo', 'primary_query' => 'c-1 nakaz jazdy w prawo'],
            ['code' => 'C-2', 'slug' => 'c-2-nakaz-skrecania-w-prawo', 'name' => 'Nakaz skręcania w prawo', 'primary_query' => 'c-2 nakaz skręcania w prawo'],
            ['code' => 'C-3', 'slug' => 'c-3-nakaz-jazdy-w-lewo', 'name' => 'Nakaz jazdy w lewo', 'primary_query' => 'c-3 nakaz jazdy w lewo'],
            ['code' => 'C-4', 'slug' => 'c-4-nakaz-skrecania-w-lewo', 'name' => 'Nakaz skręcania w lewo', 'primary_query' => 'c-4 nakaz skręcania w lewo'],
            ['code' => 'C-5', 'slug' => 'c-5-nakaz-jazdy-prosto', 'name' => 'Nakaz jazdy prosto', 'primary_query' => 'c-5 nakaz jazdy prosto'],
            ['code' => 'C-6', 'slug' => 'c-6-nakaz-jazdy-prosto-lub-w-prawo', 'name' => 'Nakaz jazdy prosto lub w prawo', 'primary_query' => 'c-6 nakaz jazdy prosto lub w prawo'],
            ['code' => 'C-7', 'slug' => 'c-7-nakaz-jazdy-prosto-lub-w-lewo', 'name' => 'Nakaz jazdy prosto lub w lewo', 'primary_query' => 'c-7 nakaz jazdy prosto lub w lewo'],
            ['code' => 'C-8', 'slug' => 'c-8-nakaz-jazdy-w-lewo-lub-w-prawo', 'name' => 'Nakaz jazdy w lewo lub w prawo', 'primary_query' => 'c-8 nakaz jazdy w lewo lub w prawo'],
            ['code' => 'C-9', 'slug' => 'c-9-nakaz-objazdu-przeszkody-z-prawej-strony', 'name' => 'Nakaz objazdu przeszkody z prawej strony', 'primary_query' => 'c-9 nakaz objazdu przeszkody z prawej strony'],
            ['code' => 'C-10', 'slug' => 'c-10-nakaz-objazdu-przeszkody-z-lewej-strony', 'name' => 'Nakaz objazdu przeszkody z lewej strony', 'primary_query' => 'c-10 nakaz objazdu przeszkody z lewej strony'],
            ['code' => 'C-11', 'slug' => 'c-11-nakaz-objazdu-przeszkody-z-obu-stron', 'name' => 'Nakaz objazdu przeszkody z obu stron', 'primary_query' => 'c-11 nakaz objazdu przeszkody z obu stron'],
            ['code' => 'C-12', 'slug' => 'c-12-ruch-okrezny', 'name' => 'Ruch okrężny', 'primary_query' => 'c-12 ruch okrężny'],
            ['code' => 'C-13', 'slug' => 'c-13-droga-dla-rowerow', 'name' => 'Droga dla rowerów', 'primary_query' => 'c-13 droga dla rowerów'],
            ['code' => 'C-13a', 'slug' => 'c-13a-koniec-drogi-dla-rowerow', 'name' => 'Koniec drogi dla rowerów', 'primary_query' => 'c-13a koniec drogi dla rowerów'],
            ['code' => 'C-13/16', 'slug' => 'c-13-16-droga-dla-rowerow-i-pieszych', 'name' => 'Droga dla rowerów i pieszych', 'primary_query' => 'c-13/16 droga dla rowerów i pieszych'],
            ['code' => 'C-13a/16a', 'slug' => 'c-13a-16a-koniec-drogi-dla-rowerow-i-pieszych', 'name' => 'Koniec drogi dla rowerów i pieszych', 'primary_query' => 'c-13a/16a koniec drogi dla rowerów i pieszych'],
            ['code' => 'C-14', 'slug' => 'c-14-predkosc-minimalna-40', 'name' => 'Prędkość minimalna 40 km/h', 'primary_query' => 'c-14 prędkość minimalna 40'],
            ['code' => 'C-15', 'slug' => 'c-15-koniec-predkosci-minimalnej', 'name' => 'Koniec prędkości minimalnej', 'primary_query' => 'c-15 koniec prędkości minimalnej'],
            ['code' => 'C-16', 'slug' => 'c-16-droga-dla-pieszych', 'name' => 'Droga dla pieszych', 'primary_query' => 'c-16 droga dla pieszych'],
            ['code' => 'C-16a', 'slug' => 'c-16a-koniec-drogi-dla-pieszych', 'name' => 'Koniec drogi dla pieszych', 'primary_query' => 'c-16a koniec drogi dla pieszych'],
            ['code' => 'C-17', 'slug' => 'c-17-nakazany-kierunek-dla-pojazdow-z-materialami-niebezpiecznymi', 'name' => 'Nakazany kierunek dla pojazdów z materiałami niebezpiecznymi', 'primary_query' => 'c-17 nakazany kierunek dla pojazdów z materiałami niebezpiecznymi'],
            ['code' => 'C-18', 'slug' => 'c-18-nakaz-uzywania-lancuchow-przeciwslizgowych', 'name' => 'Nakaz używania łańcuchów przeciwślizgowych', 'primary_query' => 'c-18 nakaz używania łańcuchów przeciwślizgowych'],
            ['code' => 'C-19', 'slug' => 'c-19-koniec-nakazu-uzywania-lancuchow-przeciwslizgowych', 'name' => 'Koniec nakazu używania łańcuchów przeciwślizgowych', 'primary_query' => 'c-19 koniec nakazu używania łańcuchów przeciwślizgowych'],
        ];
    }
}

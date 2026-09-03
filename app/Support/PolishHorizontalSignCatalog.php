<?php

namespace App\Support;

class PolishHorizontalSignCatalog
{
    /**
     * @return list<array{code: string, slug: string, name: string, primary_query: string}>
     */
    public function all(): array
    {
        return [
            ['code' => 'P-1', 'slug' => 'p-1-linia-pojedyncza-przerywana', 'name' => 'Linia pojedyncza przerywana', 'primary_query' => 'p-1 linia pojedyncza przerywana'],
            ['code' => 'P-2', 'slug' => 'p-2-linia-pojedyncza-ciagla', 'name' => 'Linia pojedyncza ciągła', 'primary_query' => 'p-2 linia pojedyncza ciągła'],
            ['code' => 'P-2Y', 'slug' => 'p-2y-zolta-linia-pojedyncza-ciagla', 'name' => 'Żółta linia pojedyncza ciągła', 'primary_query' => 'żółta linia pojedyncza ciągła'],
            ['code' => 'P-3', 'slug' => 'p-3-linia-jednostronnie-przekraczalna', 'name' => 'Linia jednostronnie przekraczalna', 'primary_query' => 'p-3 linia jednostronnie przekraczalna'],
            ['code' => 'P-4', 'slug' => 'p-4-linia-podwojna-ciagla', 'name' => 'Linia podwójna ciągła', 'primary_query' => 'p-4 linia podwójna ciągła'],
            ['code' => 'P-4Y', 'slug' => 'p-4y-zolta-linia-podwojna-ciagla', 'name' => 'Żółta linia podwójna ciągła', 'primary_query' => 'żółta linia podwójna ciągła'],
            ['code' => 'P-7a', 'slug' => 'p-7a-linia-krawedziowa-przerywana', 'name' => 'Linia krawędziowa przerywana', 'primary_query' => 'p-7a linia krawędziowa przerywana'],
            ['code' => 'P-7b', 'slug' => 'p-7b-linia-krawedziowa-ciagla', 'name' => 'Linia krawędziowa ciągła', 'primary_query' => 'p-7b linia krawędziowa ciągła'],
            ['code' => 'P-8a', 'slug' => 'p-8a-strzalka-kierunkowa-na-wprost', 'name' => 'Strzałka kierunkowa na wprost', 'primary_query' => 'p-8a strzałka kierunkowa na wprost'],
            ['code' => 'P-8aY', 'slug' => 'p-8ay-zolta-strzalka-kierunkowa-na-wprost', 'name' => 'Żółta strzałka kierunkowa na wprost', 'primary_query' => 'żółta strzałka kierunkowa na wprost'],
            ['code' => 'P-8b', 'slug' => 'p-8b-strzalka-kierunkowa-do-skrecania', 'name' => 'Strzałka kierunkowa do skręcania', 'primary_query' => 'p-8b strzałka kierunkowa do skręcania'],
            ['code' => 'P-8bY-L', 'slug' => 'p-8by-zolta-strzalka-kierunkowa-do-skrecania-w-lewo', 'name' => 'Żółta strzałka kierunkowa do skręcania w lewo', 'primary_query' => 'żółta strzałka kierunkowa do skręcania w lewo'],
            ['code' => 'P-8bY-R', 'slug' => 'p-8by-zolta-strzalka-kierunkowa-do-skrecania-w-prawo', 'name' => 'Żółta strzałka kierunkowa do skręcania w prawo', 'primary_query' => 'żółta strzałka kierunkowa do skręcania w prawo'],
            ['code' => 'P-8c', 'slug' => 'p-8c-strzalka-kierunkowa-do-zawracania', 'name' => 'Strzałka kierunkowa do zawracania', 'primary_query' => 'p-8c strzałka kierunkowa do zawracania'],
            ['code' => 'P-9', 'slug' => 'p-9-strzalka-naprowadzajaca-w-lewo', 'name' => 'Strzałka naprowadzająca w lewo', 'primary_query' => 'p-9 strzałka naprowadzająca w lewo'],
            ['code' => 'P-9b', 'slug' => 'p-9b-strzalka-naprowadzajaca-w-prawo', 'name' => 'Strzałka naprowadzająca w prawo', 'primary_query' => 'p-9b strzałka naprowadzająca w prawo'],
            ['code' => 'P-10', 'slug' => 'p-10-przejscie-dla-pieszych', 'name' => 'Przejście dla pieszych', 'primary_query' => 'p-10 przejście dla pieszych zebra'],
            ['code' => 'P-12', 'slug' => 'p-12-linia-bezwzglednego-zatrzymania-stop', 'name' => 'Linia bezwzględnego zatrzymania – stop', 'primary_query' => 'p-12 linia bezwzględnego zatrzymania stop'],
            ['code' => 'P-13', 'slug' => 'p-13-linia-warunkowego-zatrzymania-zlozona-z-trojkatow', 'name' => 'Linia warunkowego zatrzymania złożona z trójkątów', 'primary_query' => 'p-13 linia złożona z trójkątów'],
            ['code' => 'P-14', 'slug' => 'p-14-linia-warunkowego-zatrzymania-zlozona-z-prostokatow', 'name' => 'Linia warunkowego zatrzymania złożona z prostokątów', 'primary_query' => 'p-14 linia złożona z prostokątów'],
            ['code' => 'P-17', 'slug' => 'p-17-linia-przystankowa', 'name' => 'Linia przystankowa', 'primary_query' => 'p-17 linia przystankowa'],
        ];
    }
}

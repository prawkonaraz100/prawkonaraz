<?php

namespace App\Support;

class PolishProhibitionSignCatalog
{
    /**
     * @return list<array{code: string, slug: string, name: string, primary_query: string}>
     */
    public function all(): array
    {
        return [
            ['code' => 'B-1', 'slug' => 'b-1-zakaz-ruchu-w-obu-kierunkach', 'name' => 'Zakaz ruchu w obu kierunkach', 'primary_query' => 'b-1 zakaz ruchu w obu kierunkach'],
            ['code' => 'B-2', 'slug' => 'b-2-zakaz-wjazdu', 'name' => 'Zakaz wjazdu', 'primary_query' => 'b-2 zakaz wjazdu'],
            ['code' => 'B-3', 'slug' => 'b-3-zakaz-wjazdu-pojazdow-silnikowych', 'name' => 'Zakaz wjazdu pojazdów silnikowych, z wyjątkiem motocykli jednośladowych', 'primary_query' => 'b-3 zakaz wjazdu pojazdów silnikowych'],
            ['code' => 'B-3a', 'slug' => 'b-3a-zakaz-wjazdu-autobusow', 'name' => 'Zakaz wjazdu autobusów', 'primary_query' => 'b-3a zakaz wjazdu autobusów'],
            ['code' => 'B-4', 'slug' => 'b-4-zakaz-wjazdu-motocykli', 'name' => 'Zakaz wjazdu motocykli', 'primary_query' => 'b-4 zakaz wjazdu motocykli'],
            ['code' => 'B-5', 'slug' => 'b-5-zakaz-wjazdu-samochodow-ciezarowych', 'name' => 'Zakaz wjazdu samochodów ciężarowych', 'primary_query' => 'b-5 zakaz wjazdu samochodów ciężarowych'],
            ['code' => 'B-6', 'slug' => 'b-6-zakaz-wjazdu-ciagnikow-rolniczych', 'name' => 'Zakaz wjazdu ciągników rolniczych', 'primary_query' => 'b-6 zakaz wjazdu ciągników rolniczych'],
            ['code' => 'B-7', 'slug' => 'b-7-zakaz-wjazdu-pojazdow-silnikowych-z-przyczepa', 'name' => 'Zakaz wjazdu pojazdów silnikowych z przyczepą', 'primary_query' => 'b-7 zakaz wjazdu pojazdów silnikowych z przyczepą'],
            ['code' => 'B-8', 'slug' => 'b-8-zakaz-wjazdu-pojazdow-zaprzegowych', 'name' => 'Zakaz wjazdu pojazdów zaprzęgowych', 'primary_query' => 'b-8 zakaz wjazdu pojazdów zaprzęgowych'],
            ['code' => 'B-9', 'slug' => 'b-9-zakaz-wjazdu-rowerow', 'name' => 'Zakaz wjazdu rowerów', 'primary_query' => 'b-9 zakaz wjazdu rowerów'],
            ['code' => 'B-10', 'slug' => 'b-10-zakaz-wjazdu-motorowerow', 'name' => 'Zakaz wjazdu motorowerów', 'primary_query' => 'b-10 zakaz wjazdu motorowerów'],
            ['code' => 'B-11', 'slug' => 'b-11-zakaz-wjazdu-wozow-recznych', 'name' => 'Zakaz wjazdu wozów ręcznych', 'primary_query' => 'b-11 zakaz wjazdu wozów ręcznych'],
            ['code' => 'B-12', 'slug' => 'b-12-zakaz-wjazdu-wozow-recznych-z-towarem', 'name' => 'Zakaz wjazdu wozów ręcznych z towarem', 'primary_query' => 'b-12 zakaz wjazdu wozów ręcznych z towarem'],
            ['code' => 'B-13', 'slug' => 'b-13-zakaz-wjazdu-pojazdow-z-materialami-wybuchowymi', 'name' => 'Zakaz wjazdu pojazdów z materiałami wybuchowymi lub łatwo zapalnymi', 'primary_query' => 'b-13 zakaz wjazdu pojazdów z materiałami wybuchowymi'],
            ['code' => 'B-13a', 'slug' => 'b-13a-zakaz-wjazdu-pojazdow-z-materialami-niebezpiecznymi', 'name' => 'Zakaz wjazdu pojazdów z materiałami niebezpiecznymi', 'primary_query' => 'b-13a zakaz wjazdu pojazdów z materiałami niebezpiecznymi'],
            ['code' => 'B-14', 'slug' => 'b-14-zakaz-wjazdu-pojazdow-z-materialami-mogacymi-skazic-wode', 'name' => 'Zakaz wjazdu pojazdów z materiałami, które mogą skazić wodę', 'primary_query' => 'b-14 zakaz wjazdu pojazdów które mogą skazić wodę'],
            ['code' => 'B-15', 'slug' => 'b-15-zakaz-wjazdu-pojazdow-o-szerokosci-ponad', 'name' => 'Zakaz wjazdu pojazdów o szerokości ponad ... m', 'primary_query' => 'b-15 zakaz szerokości'],
            ['code' => 'B-16', 'slug' => 'b-16-zakaz-wjazdu-pojazdow-o-wysokosci-ponad', 'name' => 'Zakaz wjazdu pojazdów o wysokości ponad ... m', 'primary_query' => 'b-16 zakaz wysokości'],
            ['code' => 'B-17', 'slug' => 'b-17-zakaz-wjazdu-pojazdow-o-dlugosci-ponad', 'name' => 'Zakaz wjazdu pojazdów o długości ponad ... m', 'primary_query' => 'b-17 zakaz długości'],
            ['code' => 'B-18', 'slug' => 'b-18-zakaz-wjazdu-pojazdow-o-rzeczywistej-masie-calkowitej-ponad', 'name' => 'Zakaz wjazdu pojazdów o rzeczywistej masie całkowitej ponad ... t', 'primary_query' => 'b-18 zakaz masy całkowitej'],
            ['code' => 'B-19', 'slug' => 'b-19-zakaz-wjazdu-pojazdow-o-nacisku-pojedynczej-osi-napedowej-powyzej', 'name' => 'Zakaz wjazdu pojazdów o nacisku pojedynczej osi napędowej powyżej ... t', 'primary_query' => 'b-19 zakaz nacisku pojedynczej osi napędowej'],
            ['code' => 'B-20', 'slug' => 'b-20-stop', 'name' => 'STOP', 'primary_query' => 'b-20 stop'],
            ['code' => 'B-21', 'slug' => 'b-21-zakaz-skrecania-w-lewo', 'name' => 'Zakaz skręcania w lewo', 'primary_query' => 'b-21 zakaz skręcania w lewo'],
            ['code' => 'B-22', 'slug' => 'b-22-zakaz-skrecania-w-prawo', 'name' => 'Zakaz skręcania w prawo', 'primary_query' => 'b-22 zakaz skręcania w prawo'],
            ['code' => 'B-23', 'slug' => 'b-23-zakaz-zawracania', 'name' => 'Zakaz zawracania', 'primary_query' => 'b-23 zakaz zawracania'],
            ['code' => 'B-24', 'slug' => 'b-24-koniec-zakazu-zawracania', 'name' => 'Koniec zakazu zawracania', 'primary_query' => 'b-24 koniec zakazu zawracania'],
            ['code' => 'B-25', 'slug' => 'b-25-zakaz-wyprzedzania', 'name' => 'Zakaz wyprzedzania', 'primary_query' => 'b-25 zakaz wyprzedzania'],
            ['code' => 'B-26', 'slug' => 'b-26-zakaz-wyprzedzania-przez-samochody-ciezarowe', 'name' => 'Zakaz wyprzedzania przez samochody ciężarowe', 'primary_query' => 'b-26 zakaz wyprzedzania przez samochody ciężarowe'],
            ['code' => 'B-27', 'slug' => 'b-27-koniec-zakazu-wyprzedzania', 'name' => 'Koniec zakazu wyprzedzania', 'primary_query' => 'b-27 koniec zakazu wyprzedzania'],
            ['code' => 'B-28', 'slug' => 'b-28-koniec-zakazu-wyprzedzania-przez-samochody-ciezarowe', 'name' => 'Koniec zakazu wyprzedzania przez samochody ciężarowe', 'primary_query' => 'b-28 koniec zakazu wyprzedzania przez samochody ciężarowe'],
            ['code' => 'B-29', 'slug' => 'b-29-zakaz-uzywania-sygnalow-dzwiekowych', 'name' => 'Zakaz używania sygnałów dźwiękowych', 'primary_query' => 'b-29 zakaz używania sygnałów dźwiękowych'],
            ['code' => 'B-30', 'slug' => 'b-30-koniec-zakazu-uzywania-sygnalow-dzwiekowych', 'name' => 'Koniec zakazu używania sygnałów dźwiękowych', 'primary_query' => 'b-30 koniec zakazu używania sygnałów dźwiękowych'],
            ['code' => 'B-31', 'slug' => 'b-31-pierwszenstwo-dla-nadjezdzajacych-z-przeciwka', 'name' => 'Pierwszeństwo dla nadjeżdżających z przeciwka', 'primary_query' => 'b-31 pierwszeństwo dla nadjeżdżających z przeciwka'],
            ['code' => 'B-32', 'slug' => 'b-32-zatrzymanie-i-odprawa-celna', 'name' => 'Zatrzymanie i odprawa celna', 'primary_query' => 'b-32 zatrzymanie i odprawa celna'],
            ['code' => 'B-32a', 'slug' => 'b-32a-kontrola-graniczna', 'name' => 'Kontrola graniczna', 'primary_query' => 'b-32a kontrola graniczna'],
            ['code' => 'B-32b', 'slug' => 'b-32b-rogatka-uszkodzona', 'name' => 'Rogatka uszkodzona', 'primary_query' => 'b-32b rogatka uszkodzona'],
            ['code' => 'B-32c', 'slug' => 'b-32c-sygnalizacja-uszkodzona', 'name' => 'Sygnalizacja uszkodzona', 'primary_query' => 'b-32c sygnalizacja uszkodzona'],
            ['code' => 'B-32d', 'slug' => 'b-32d-wjazd-na-prom', 'name' => 'Wjazd na prom', 'primary_query' => 'b-32d wjazd na prom'],
            ['code' => 'B-32e', 'slug' => 'b-32e-kontrola-drogowa', 'name' => 'Kontrola drogowa', 'primary_query' => 'b-32e kontrola drogowa'],
            ['code' => 'B-33', 'slug' => 'b-33-ograniczenie-predkosci', 'name' => 'Ograniczenie prędkości', 'primary_query' => 'b-33 ograniczenie prędkości'],
            ['code' => 'B-34', 'slug' => 'b-34-koniec-ograniczenia-predkosci', 'name' => 'Koniec ograniczenia prędkości', 'primary_query' => 'b-34 koniec ograniczenia prędkości'],
            ['code' => 'B-35', 'slug' => 'b-35-zakaz-postoju', 'name' => 'Zakaz postoju', 'primary_query' => 'b-35 zakaz postoju'],
            ['code' => 'B-36', 'slug' => 'b-36-zakaz-zatrzymywania-sie', 'name' => 'Zakaz zatrzymywania się', 'primary_query' => 'b-36 zakaz zatrzymywania się'],
            ['code' => 'B-37', 'slug' => 'b-37-zakaz-postoju-w-dni-nieparzyste', 'name' => 'Zakaz postoju w dni nieparzyste', 'primary_query' => 'b-37 zakaz postoju w dni nieparzyste'],
            ['code' => 'B-38', 'slug' => 'b-38-zakaz-postoju-w-dni-parzyste', 'name' => 'Zakaz postoju w dni parzyste', 'primary_query' => 'b-38 zakaz postoju w dni parzyste'],
            ['code' => 'B-39', 'slug' => 'b-39-strefa-ograniczonego-postoju', 'name' => 'Strefa ograniczonego postoju', 'primary_query' => 'b-39 strefa ograniczonego postoju'],
            ['code' => 'B-40', 'slug' => 'b-40-koniec-strefy-ograniczonego-postoju', 'name' => 'Koniec strefy ograniczonego postoju', 'primary_query' => 'b-40 koniec strefy ograniczonego postoju'],
            ['code' => 'B-41', 'slug' => 'b-41-zakaz-ruchu-pieszych', 'name' => 'Zakaz ruchu pieszych', 'primary_query' => 'b-41 zakaz ruchu pieszych'],
            ['code' => 'B-42', 'slug' => 'b-42-koniec-zakazow', 'name' => 'Koniec zakazów', 'primary_query' => 'b-42 koniec zakazów'],
            ['code' => 'B-43', 'slug' => 'b-43-strefa-ograniczonej-predkosci', 'name' => 'Strefa ograniczonej prędkości', 'primary_query' => 'b-43 strefa ograniczonej prędkości'],
            ['code' => 'B-44', 'slug' => 'b-44-koniec-strefy-ograniczonej-predkosci', 'name' => 'Koniec strefy ograniczonej prędkości', 'primary_query' => 'b-44 koniec strefy ograniczonej prędkości'],
        ];
    }
}

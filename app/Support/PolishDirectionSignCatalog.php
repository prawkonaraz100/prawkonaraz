<?php

namespace App\Support;

class PolishDirectionSignCatalog
{
    /**
     * @return list<array{code: string, slug: string, name: string, primary_query: string}>
     */
    public function all(): array
    {
        return [
            ['code' => 'E-1', 'slug' => 'e-1-tablica-przeddrogowskazowa', 'name' => 'Tablica przeddrogowskazowa', 'primary_query' => 'e-1 tablica przeddrogowskazowa'],
            ['code' => 'E-1a', 'slug' => 'e-1a-tablica-przeddrogowskazowa-na-autostradzie', 'name' => 'Tablica przeddrogowskazowa na autostradzie', 'primary_query' => 'e-1a tablica przeddrogowskazowa na autostradzie'],
            ['code' => 'E-1b', 'slug' => 'e-1b-tablica-przeddrogowskazowa-przed-wjazdem-na-autostrade', 'name' => 'Tablica przeddrogowskazowa przed wjazdem na autostradę', 'primary_query' => 'e-1b tablica przeddrogowskazowa przed wjazdem na autostradę'],
            ['code' => 'E-2a', 'slug' => 'e-2a-drogowskaz-tablicowy-obok-jezdni', 'name' => 'Drogowskaz tablicowy umieszczany obok jezdni', 'primary_query' => 'e-2a drogowskaz tablicowy obok jezdni'],
            ['code' => 'E-2b', 'slug' => 'e-2b-drogowskaz-tablicowy-nad-jezdnia', 'name' => 'Drogowskaz tablicowy umieszczany nad jezdnią', 'primary_query' => 'e-2b drogowskaz tablicowy nad jezdnią'],
            ['code' => 'E-2c', 'slug' => 'e-2c-drogowskaz-tablicowy-obok-jezdni-na-autostradzie', 'name' => 'Drogowskaz tablicowy umieszczany obok jezdni na autostradzie', 'primary_query' => 'e-2c drogowskaz tablicowy obok jezdni na autostradzie'],
            ['code' => 'E-2d', 'slug' => 'e-2d-drogowskaz-tablicowy-nad-jezdnia-na-autostradzie', 'name' => 'Drogowskaz tablicowy umieszczany nad jezdnią na autostradzie', 'primary_query' => 'e-2d drogowskaz tablicowy nad jezdnią na autostradzie'],
            ['code' => 'E-2e', 'slug' => 'e-2e-drogowskaz-tablicowy-obok-jezdni-przed-wjazdem-na-autostrade', 'name' => 'Drogowskaz tablicowy umieszczony obok jezdni przed wjazdem na autostradę', 'primary_query' => 'e-2e drogowskaz tablicowy obok jezdni przed wjazdem na autostradę'],
            ['code' => 'E-2f', 'slug' => 'e-2f-drogowskaz-tablicowy-nad-jezdnia-przed-wjazdem-na-autostrade', 'name' => 'Drogowskaz tablicowy umieszczany nad jezdnią przed wjazdem na autostradę', 'primary_query' => 'e-2f drogowskaz tablicowy nad jezdnią przed wjazdem na autostradę'],
            ['code' => 'E-3', 'slug' => 'e-3-drogowskaz-w-ksztalcie-strzaly-do-miejscowosci-ze-wskazaniem-numeru-drogi', 'name' => 'Drogowskaz w kształcie strzały do miejscowości ze wskazaniem numeru drogi', 'primary_query' => 'e-3 drogowskaz w kształcie strzały do miejscowości ze wskazaniem numeru drogi'],
            ['code' => 'E-4', 'slug' => 'e-4-drogowskaz-w-ksztalcie-strzaly-do-miejscowosci-z-odlegloscia', 'name' => 'Drogowskaz w kształcie strzały do miejscowości podający odległość', 'primary_query' => 'e-4 drogowskaz w kształcie strzały z odległością'],
            ['code' => 'E-5', 'slug' => 'e-5-drogowskaz-do-dzielnicy-miasta', 'name' => 'Drogowskaz do dzielnicy miasta', 'primary_query' => 'e-5 drogowskaz do dzielnicy miasta'],
            ['code' => 'E-5a', 'slug' => 'e-5a-drogowskaz-do-centrum-miasta', 'name' => 'Drogowskaz do centrum miasta', 'primary_query' => 'e-5a drogowskaz do centrum miasta'],
            ['code' => 'E-6', 'slug' => 'e-6-drogowskaz-do-lotniska', 'name' => 'Drogowskaz do lotniska', 'primary_query' => 'e-6 drogowskaz do lotniska'],
            ['code' => 'E-6a', 'slug' => 'e-6a-drogowskaz-do-dworca-lub-stacji-kolejowej', 'name' => 'Drogowskaz do dworca lub stacji kolejowej', 'primary_query' => 'e-6a drogowskaz do dworca lub stacji kolejowej'],
            ['code' => 'E-6b', 'slug' => 'e-6b-drogowskaz-do-dworca-autobusowego', 'name' => 'Drogowskaz do dworca autobusowego', 'primary_query' => 'e-6b drogowskaz do dworca autobusowego'],
            ['code' => 'E-6c', 'slug' => 'e-6c-drogowskaz-do-przystani-promowej', 'name' => 'Drogowskaz do przystani promowej', 'primary_query' => 'e-6c drogowskaz do przystani promowej'],
            ['code' => 'E-7', 'slug' => 'e-7-drogowskaz-do-przystani-wodnej-lub-zeglugi', 'name' => 'Drogowskaz do przystani wodnej lub żeglugi', 'primary_query' => 'e-7 drogowskaz do przystani wodnej lub żeglugi'],
            ['code' => 'E-8', 'slug' => 'e-8-drogowskaz-do-plazy-lub-miejsca-kapielowego', 'name' => 'Drogowskaz do plaży lub miejsca kąpielowego', 'primary_query' => 'e-8 drogowskaz do plaży lub miejsca kąpielowego'],
            ['code' => 'E-9', 'slug' => 'e-9-drogowskaz-do-muzeum', 'name' => 'Drogowskaz do muzeum', 'primary_query' => 'e-9 drogowskaz do muzeum'],
            ['code' => 'E-10', 'slug' => 'e-10-drogowskaz-do-zabytku-jako-dobra-kultury', 'name' => 'Drogowskaz do zabytku jako dobra kultury', 'primary_query' => 'e-10 drogowskaz do zabytku jako dobra kultury'],
            ['code' => 'E-11', 'slug' => 'e-11-drogowskaz-do-zabytku-przyrody', 'name' => 'Drogowskaz do zabytku przyrody', 'primary_query' => 'e-11 drogowskaz do zabytku przyrody'],
            ['code' => 'E-12', 'slug' => 'e-12-drogowskaz-do-punktu-widokowego', 'name' => 'Drogowskaz do punktu widokowego', 'primary_query' => 'e-12 drogowskaz do punktu widokowego'],
            ['code' => 'E-12a', 'slug' => 'e-12a-drogowskaz-do-szlaku-rowerowego', 'name' => 'Drogowskaz do szlaku rowerowego', 'primary_query' => 'e-12a drogowskaz do szlaku rowerowego'],
            ['code' => 'E-13', 'slug' => 'e-13-tablica-kierunkowa', 'name' => 'Tablica kierunkowa', 'primary_query' => 'e-13 tablica kierunkowa'],
            ['code' => 'E-14', 'slug' => 'e-14-tablica-szlaku-drogowego', 'name' => 'Tablica szlaku drogowego', 'primary_query' => 'e-14 tablica szlaku drogowego'],
            ['code' => 'E-14a', 'slug' => 'e-14a-tablica-szlaku-drogowego-na-autostradzie', 'name' => 'Tablica szlaku drogowego na autostradzie', 'primary_query' => 'e-14a tablica szlaku drogowego na autostradzie'],
            ['code' => 'E-15a', 'slug' => 'e-15a-numer-drogi-krajowej', 'name' => 'Numer drogi krajowej', 'primary_query' => 'e-15a numer drogi krajowej'],
            ['code' => 'E-15b', 'slug' => 'e-15b-numer-drogi-wojewodzkiej', 'name' => 'Numer drogi wojewódzkiej', 'primary_query' => 'e-15b numer drogi wojewódzkiej'],
            ['code' => 'E-15c', 'slug' => 'e-15c-numer-autostrady', 'name' => 'Numer autostrady', 'primary_query' => 'e-15c numer autostrady'],
            ['code' => 'E-15d', 'slug' => 'e-15d-numer-drogi-ekspresowej', 'name' => 'Numer drogi ekspresowej', 'primary_query' => 'e-15d numer drogi ekspresowej'],
            ['code' => 'E-15e', 'slug' => 'e-15e-numer-drogi-wojewodzkiej-o-zwiekszonym-do-10-t-dopuszczalnym-nacisku-osi-pojazdu', 'name' => 'Numer drogi wojewódzkiej o zwiększonym do 10 t dopuszczalnym nacisku osi pojazdu', 'primary_query' => 'e-15e numer drogi wojewódzkiej nacisk osi 10 t'],
            ['code' => 'E-15f', 'slug' => 'e-15f-numer-drogi-krajowej-o-dopuszczalnym-nacisku-osi-pojazdu-10-t', 'name' => 'Numer drogi krajowej o dopuszczalnym nacisku osi pojazdu 10 t', 'primary_query' => 'e-15f numer drogi krajowej nacisk osi 10 t'],
            ['code' => 'E-15g', 'slug' => 'e-15g-numer-drogi-krajowej-o-dopuszczalnym-nacisku-osi-pojazdu-8-t', 'name' => 'Numer drogi krajowej o dopuszczalnym nacisku osi pojazdu 8 t', 'primary_query' => 'e-15g numer drogi krajowej nacisk osi 8 t'],
            ['code' => 'E-16', 'slug' => 'e-16-numer-szlaku-miedzynarodowego', 'name' => 'Numer szlaku międzynarodowego', 'primary_query' => 'e-16 numer szlaku międzynarodowego'],
            ['code' => 'E-17a', 'slug' => 'e-17a-miejscowosc', 'name' => 'Miejscowość', 'primary_query' => 'e-17a miejscowość'],
            ['code' => 'E-18a', 'slug' => 'e-18a-koniec-miejscowosci', 'name' => 'Koniec miejscowości', 'primary_query' => 'e-18a koniec miejscowości'],
            ['code' => 'E-19a', 'slug' => 'e-19a-obwodnica', 'name' => 'Obwodnica', 'primary_query' => 'e-19a obwodnica'],
            ['code' => 'E-20', 'slug' => 'e-20-tablica-wezla-drogowego-na-autostradzie', 'name' => 'Tablica węzła drogowego na autostradzie', 'primary_query' => 'e-20 tablica węzła drogowego na autostradzie'],
            ['code' => 'E-20a', 'slug' => 'e-20a-tablica-wezla-drogowego-na-autostradzie-z-nazwa-i-oznaczeniem-wezla', 'name' => 'Tablica węzła drogowego na autostradzie z nazwą i oznaczeniem węzła', 'primary_query' => 'e-20a tablica węzła drogowego nazwa oznaczenie'],
            ['code' => 'E-21', 'slug' => 'e-21-dzielnica-osiedle', 'name' => 'Dzielnica (osiedle)', 'primary_query' => 'e-21 dzielnica osiedle'],
            ['code' => 'E-22a', 'slug' => 'e-22a-samochodowy-szlak-turystyczny', 'name' => 'Samochodowy szlak turystyczny', 'primary_query' => 'e-22a samochodowy szlak turystyczny'],
            ['code' => 'E-22b', 'slug' => 'e-22b-obiekt-na-samochodowym-szlaku-turystycznym', 'name' => 'Obiekt na samochodowym szlaku turystycznym', 'primary_query' => 'e-22b obiekt na samochodowym szlaku turystycznym'],
            ['code' => 'E-22c', 'slug' => 'e-22c-informacja-o-obiektach-turystycznych', 'name' => 'Informacja o obiektach turystycznych', 'primary_query' => 'e-22c informacja o obiektach turystycznych'],
        ];
    }
}

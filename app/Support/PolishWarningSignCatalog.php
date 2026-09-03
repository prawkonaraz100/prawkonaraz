<?php

namespace App\Support;

class PolishWarningSignCatalog
{
    /**
     * @return list<array{code: string, slug: string, name: string, primary_query: string}>
     */
    public function all(): array
    {
        return [
            ['code' => 'A-1', 'slug' => 'a-1-niebezpieczny-zakret-w-prawo', 'name' => 'Niebezpieczny zakręt w prawo', 'primary_query' => 'a-1 niebezpieczny zakręt w prawo'],
            ['code' => 'A-2', 'slug' => 'a-2-niebezpieczny-zakret-w-lewo', 'name' => 'Niebezpieczny zakręt w lewo', 'primary_query' => 'a-2 niebezpieczny zakręt w lewo'],
            ['code' => 'A-3', 'slug' => 'a-3-niebezpieczne-zakrety-pierwszy-w-prawo', 'name' => 'Niebezpieczne zakręty - pierwszy w prawo', 'primary_query' => 'a-3 niebezpieczne zakręty pierwszy w prawo'],
            ['code' => 'A-4', 'slug' => 'a-4-niebezpieczne-zakrety-pierwszy-w-lewo', 'name' => 'Niebezpieczne zakręty - pierwszy w lewo', 'primary_query' => 'a-4 niebezpieczne zakręty pierwszy w lewo'],
            ['code' => 'A-5', 'slug' => 'a-5-skrzyzowanie-drog', 'name' => 'Skrzyżowanie dróg', 'primary_query' => 'a-5 skrzyżowanie dróg'],
            ['code' => 'A-6a', 'slug' => 'a-6a-skrzyzowanie-z-droga-podporzadkowana-po-obu-stronach', 'name' => 'Skrzyżowanie z drogą podporządkowaną występującą po obu stronach', 'primary_query' => 'a-6a skrzyżowanie z drogą podporządkowaną po obu stronach'],
            ['code' => 'A-6b', 'slug' => 'a-6b-skrzyzowanie-z-droga-podporzadkowana-po-prawej-stronie', 'name' => 'Skrzyżowanie z drogą podporządkowaną występującą po prawej stronie', 'primary_query' => 'a-6b skrzyżowanie z drogą podporządkowaną po prawej stronie'],
            ['code' => 'A-6c', 'slug' => 'a-6c-skrzyzowanie-z-droga-podporzadkowana-po-lewej-stronie', 'name' => 'Skrzyżowanie z drogą podporządkowaną występującą po lewej stronie', 'primary_query' => 'a-6c skrzyżowanie z drogą podporządkowaną po lewej stronie'],
            ['code' => 'A-6d', 'slug' => 'a-6d-wlot-drogi-jednokierunkowej-z-prawej-strony', 'name' => 'Wlot drogi jednokierunkowej z prawej strony', 'primary_query' => 'a-6d wlot drogi jednokierunkowej z prawej strony'],
            ['code' => 'A-6e', 'slug' => 'a-6e-wlot-drogi-jednokierunkowej-z-lewej-strony', 'name' => 'Wlot drogi jednokierunkowej z lewej strony', 'primary_query' => 'a-6e wlot drogi jednokierunkowej z lewej strony'],
            ['code' => 'A-7', 'slug' => 'a-7-ustap-pierwszenstwa', 'name' => 'Ustąp pierwszeństwa', 'primary_query' => 'a-7 ustąp pierwszeństwa'],
            ['code' => 'A-8', 'slug' => 'a-8-skrzyzowanie-o-ruchu-okreznym', 'name' => 'Skrzyżowanie o ruchu okrężnym', 'primary_query' => 'a-8 skrzyżowanie o ruchu okrężnym'],
            ['code' => 'A-9', 'slug' => 'a-9-przejazd-kolejowy-z-zaporami', 'name' => 'Przejazd kolejowy z zaporami', 'primary_query' => 'a-9 przejazd kolejowy z zaporami'],
            ['code' => 'A-10', 'slug' => 'a-10-przejazd-kolejowy-bez-zapor', 'name' => 'Przejazd kolejowy bez zapór', 'primary_query' => 'a-10 przejazd kolejowy bez zapór'],
            ['code' => 'A-11', 'slug' => 'a-11-nierowna-droga', 'name' => 'Nierówna droga', 'primary_query' => 'a-11 nierówna droga'],
            ['code' => 'A-11a', 'slug' => 'a-11a-prog-zwalniajacy', 'name' => 'Próg zwalniający', 'primary_query' => 'a-11a próg zwalniający'],
            ['code' => 'A-12a', 'slug' => 'a-12a-zwezenie-jezdni-dwustronne', 'name' => 'Zwężenie jezdni - dwustronne', 'primary_query' => 'a-12a zwężenie jezdni dwustronne'],
            ['code' => 'A-12b', 'slug' => 'a-12b-zwezenie-jezdni-prawostronne', 'name' => 'Zwężenie jezdni - prawostronne', 'primary_query' => 'a-12b zwężenie jezdni prawostronne'],
            ['code' => 'A-12c', 'slug' => 'a-12c-zwezenie-jezdni-lewostronne', 'name' => 'Zwężenie jezdni - lewostronne', 'primary_query' => 'a-12c zwężenie jezdni lewostronne'],
            ['code' => 'A-13', 'slug' => 'a-13-ruchomy-most', 'name' => 'Ruchomy most', 'primary_query' => 'a-13 ruchomy most'],
            ['code' => 'A-14', 'slug' => 'a-14-roboty-na-drodze', 'name' => 'Roboty na drodze', 'primary_query' => 'a-14 roboty na drodze'],
            ['code' => 'A-15', 'slug' => 'a-15-sliska-jezdnia', 'name' => 'Śliska jezdnia', 'primary_query' => 'a-15 śliska jezdnia'],
            ['code' => 'A-16', 'slug' => 'a-16-przejscie-dla-pieszych', 'name' => 'Przejście dla pieszych', 'primary_query' => 'a-16 przejście dla pieszych'],
            ['code' => 'A-17', 'slug' => 'a-17-dzieci', 'name' => 'Dzieci', 'primary_query' => 'a-17 dzieci'],
            ['code' => 'A-18a', 'slug' => 'a-18a-zwierzeta-gospodarskie', 'name' => 'Zwierzęta gospodarskie', 'primary_query' => 'a-18a zwierzęta gospodarskie'],
            ['code' => 'A-18b', 'slug' => 'a-18b-zwierzeta-dzikie', 'name' => 'Zwierzęta dzikie', 'primary_query' => 'a-18b zwierzęta dzikie'],
            ['code' => 'A-19', 'slug' => 'a-19-boczny-wiatr', 'name' => 'Boczny wiatr', 'primary_query' => 'a-19 boczny wiatr'],
            ['code' => 'A-20', 'slug' => 'a-20-odcinek-jezdni-o-ruchu-dwukierunkowym', 'name' => 'Odcinek jezdni o ruchu dwukierunkowym', 'primary_query' => 'a-20 odcinek jezdni o ruchu dwukierunkowym'],
            ['code' => 'A-21', 'slug' => 'a-21-tramwaj', 'name' => 'Tramwaj', 'primary_query' => 'a-21 tramwaj'],
            ['code' => 'A-22', 'slug' => 'a-22-niebezpieczny-zjazd', 'name' => 'Niebezpieczny zjazd', 'primary_query' => 'a-22 niebezpieczny zjazd'],
            ['code' => 'A-23', 'slug' => 'a-23-stromy-podjazd', 'name' => 'Stromy podjazd', 'primary_query' => 'a-23 stromy podjazd'],
            ['code' => 'A-24', 'slug' => 'a-24-rowerzysci', 'name' => 'Rowerzyści', 'primary_query' => 'a-24 rowerzyści'],
            ['code' => 'A-25', 'slug' => 'a-25-spadajace-odlamki-skalne', 'name' => 'Spadające odłamki skalne', 'primary_query' => 'a-25 spadające odłamki skalne'],
            ['code' => 'A-26', 'slug' => 'a-26-lotnisko', 'name' => 'Lotnisko', 'primary_query' => 'a-26 lotnisko'],
            ['code' => 'A-27', 'slug' => 'a-27-nabrzeze-lub-brzeg-rzeki', 'name' => 'Nabrzeże lub brzeg rzeki', 'primary_query' => 'a-27 nabrzeże lub brzeg rzeki'],
            ['code' => 'A-28', 'slug' => 'a-28-sypki-zwir', 'name' => 'Sypki żwir', 'primary_query' => 'a-28 sypki żwir'],
            ['code' => 'A-29', 'slug' => 'a-29-sygnaly-swietlne', 'name' => 'Sygnały świetlne', 'primary_query' => 'a-29 sygnały świetlne'],
            ['code' => 'A-30', 'slug' => 'a-30-inne-niebezpieczenstwo', 'name' => 'Inne niebezpieczeństwo', 'primary_query' => 'a-30 inne niebezpieczeństwo'],
            ['code' => 'A-31', 'slug' => 'a-31-niebezpieczne-pobocze', 'name' => 'Niebezpieczne pobocze', 'primary_query' => 'a-31 niebezpieczne pobocze'],
            ['code' => 'A-32', 'slug' => 'a-32-oszronienie-jezdni', 'name' => 'Oszronienie jezdni', 'primary_query' => 'a-32 oszronienie jezdni'],
            ['code' => 'A-33', 'slug' => 'a-33-zator-drogowy', 'name' => 'Zator drogowy', 'primary_query' => 'a-33 zator drogowy'],
            ['code' => 'A-34', 'slug' => 'a-34-wypadek-drogowy', 'name' => 'Wypadek drogowy', 'primary_query' => 'a-34 wypadek drogowy'],
        ];
    }
}

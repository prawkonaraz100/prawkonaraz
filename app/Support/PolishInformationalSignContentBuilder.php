<?php

namespace App\Support;

class PolishInformationalSignContentBuilder
{
    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array<string, mixed>
     */
    public function build(array $sign, int $index): array
    {
        $config = $this->contentFor($sign);
        $assetPath = $this->assetPath($sign['slug']);
        $imageAlt = $sign['name'];

        return [
            'intro_definition' => $config['intro'],
            'meaning' => $config['meaning'],
            'placement' => $config['placement'],
            'driver_behavior' => $config['behavior'],
            'legal_summary' => $config['legal'],
            'fine_summary' => $config['fine'],
            'common_mistakes' => $config['mistake'],
            'editorial_notes' => $config['editorial_notes'],
            'review_notes' => 'Treść po quality passie dla znaków informacyjnych D. Każdy akapit ma opisywać realny skutek znaku dla kierowcy: status drogi, sposób zachowania, typowy błąd oraz praktykę czytania organizacji ruchu.',
            'source_notes' => 'Treść przygotowana na podstawie funkcji znaku w rozporządzeniu oraz praktycznych scenariuszy szkolenia kierowców. W znakach usługowych nacisk położono na planowanie zjazdu, a w znakach statusu drogi na konsekwencje prawne i organizacyjne.',
            'faq_items' => [
                [
                    'question' => "Co w praktyce oznacza znak {$sign['code']}?",
                    'answer' => $config['faq_duty'],
                ],
                [
                    'question' => "Jaki błąd najczęściej pojawia się przy znaku {$sign['code']}?",
                    'answer' => $config['faq_mistake'],
                ],
            ],
            'meta_title' => "{$sign['code']} {$sign['name']} - znaczenie, przepisy i zachowanie kierowcy",
            'meta_description' => "Sprawdź, co oznacza znak {$sign['code']} {$sign['name']}, gdzie się pojawia i jak kierowca powinien odczytać jego skutki na drodze.",
            'image_path' => $assetPath,
            'image_alt' => $imageAlt,
            'image_width' => 1200,
            'image_height' => 1200,
            'og_image_path' => $assetPath,
            'og_image_alt' => $imageAlt,
            'og_image_width' => 1200,
            'og_image_height' => 1200,
            'sort_order' => 10 + ($index * 10),
            'legal_reference_label' => 'Rozporządzenie w sprawie znaków i sygnałów drogowych',
            'legal_reference_url' => 'https://isap.sejm.gov.pl/',
        ];
    }

    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array{intro: string, meaning: string, placement: string, behavior: string, legal: string, fine: string, mistake: string, faq_duty: string, faq_mistake: string, editorial_notes: string}
     */
    protected function contentFor(array $sign): array
    {
        return match ($sign['code']) {
            'D-1' => $this->entry(
                'Znak D-1 Droga z pierwszeństwem informuje, że wjeżdżasz na odcinek, na którym podporządkowane wloty powinny ustępować pierwszeństwa aż do miejsca, w którym inny znak zmieni tę relację.',
                'To nie jest jednorazowa informacja o jednym skrzyżowaniu, tylko opis przebiegu drogi głównej na dalszym odcinku. Kierowca powinien dzięki niemu rozumieć, że kolejne boczne wloty nie są równorzędne.',
                'Znak ustawia się na początku drogi z pierwszeństwem oraz po skrzyżowaniach, po których trzeba ponownie potwierdzić, że główny przebieg nadal zachowuje swój uprzywilejowany status.',
                'Jedź pewnie, ale nie bezrefleksyjnie. D-1 daje Ci pierwszeństwo, lecz nie zwalnia z obserwacji wlotów podporządkowanych, pieszych ani kierowców, którzy mogą zachować się nieprawidłowo.',
                'D-1 porządkuje układ pierwszeństwa na kolejnych skrzyżowaniach. Trzeba go czytać razem z tabliczkami pokazującymi przebieg drogi z pierwszeństwem oraz z oznakowaniem ustawionym na bocznych wlotach.',
                'Problem praktyczny pojawia się wtedy, gdy kierowca traktuje znak jak gwarancję, że inni na pewno ustąpią. Zbyt pewny wjazd na skrzyżowanie może skończyć się kolizją, a w jej następstwie mandatem i odpowiedzialnością za niezachowanie ostrożności.',
                'Najczęstszy błąd polega na odpuszczeniu obserwacji po zauważeniu żółtego rombu. Kierowca słusznie wie, że ma pierwszeństwo, ale błędnie zakłada, że może przestać kontrolować zachowanie innych.',
                'Znak potwierdza, że poruszasz się drogą główną i na kolejnych podporządkowanych wlotach to inni uczestnicy ruchu powinni ustąpić Tobie pierwszeństwa.',
                'Najczęściej zawodzi nie znajomość znaczenia znaku, lecz zbyt duża pewność siebie i za słaba obserwacja tych kierowców, którzy mimo podporządkowania próbują wjechać pierwsi.',
                'Warto później rozbudować tę stronę o porównanie D-1 z A-7, B-20 i z tabliczkami pokazującymi przebieg drogi głównej.'
            ),
            'D-2' => $this->entry(
                'Znak D-2 Koniec drogi z pierwszeństwem odwołuje wcześniejszy status drogi głównej. Od tego miejsca nie wolno już zakładać, że kolejne skrzyżowania automatycznie dają Ci relację nadrzędną.',
                'To sygnał resetujący wcześniejsze przyzwyczajenie. Kierowca po minięciu znaku powinien znów aktywnie czytać następne skrzyżowanie, zamiast jechać na pamięć z przekonaniem, że nadal ma pierwszeństwo.',
                'D-2 stawia się tam, gdzie kończy się przebieg drogi z pierwszeństwem albo gdzie dalsza relacja ma być odczytywana od nowa z innych znaków i z geometrii skrzyżowania.',
                'Po minięciu znaku odruchowo wróć do pełnej analizy kolejnego wlotu. To dobry moment, by zdjąć nogę z gazu i sprawdzić, czy dalej nie pojawi się A-7, B-20 albo skrzyżowanie równorzędne.',
                'D-2 nie nakłada samodzielnego zakazu ani nakazu manewru, ale odwołuje wcześniejszą relację pierwszeństwa. Po tym znaku układ trzeba rozstrzygać według dalszego oznakowania i ogólnych zasad ruchu.',
                'Największe ryzyko nie bierze się z samego minięcia znaku, tylko z jazdy według starego założenia. Kierowca, który nadal myśli kategorią drogi głównej, może wymusić pierwszeństwo na następnym skrzyżowaniu.',
                'Typowym błędem jest utrzymywanie w głowie starego statusu drogi jeszcze przez jedno lub dwa skrzyżowania. To właśnie po D-2 najłatwiej o pomyłkę wynikającą z rutyny, a nie z braku wiedzy.',
                'Znak mówi wprost, że wcześniejsza droga z pierwszeństwem już się skończyła i następne relacje przejazdu trzeba odczytać od nowa.',
                'Najczęściej kierowca nie przełącza uwagi z trybu „jadę główną” na tryb ponownej oceny skrzyżowania i za długo zachowuje się tak, jakby znak D-1 nadal obowiązywał.',
                'Dobrze trzymać ten opis blisko D-1 oraz materiałów tłumaczących, jak kończy się przebieg drogi głównej.'
            ),
            'D-3' => $this->entry(
                'Znak D-3 Droga jednokierunkowa informuje, że dalszy odcinek prowadzi ruch tylko w jednym kierunku. To opis organizacji całej ulicy, a nie jedynie pojedynczego manewru przy najbliższym skrzyżowaniu.',
                'Po zobaczeniu D-3 kierowca powinien inaczej czytać źródła zagrożenia: inaczej ocenia się możliwość parkowania, dojazdu do skrzyżowania i to, z której strony mogą pojawić się inne pojazdy.',
                'Znak pojawia się na początku ulic jednokierunkowych, za skrzyżowaniami oraz tam, gdzie trzeba jednoznacznie potwierdzić kierunek organizacji ruchu na całym odcinku.',
                'Nie ograniczaj znaczenia znaku do samej jazdy prosto. D-3 zmienia sposób obserwacji otoczenia na dalszej ulicy, dlatego warto od razu myśleć o parkowaniu, pasach oraz wlotach podporządkowanych w logice ruchu jednokierunkowego.',
                'D-3 opisuje status odcinka drogi. Trzeba go czytać razem z innymi znakami, zwłaszcza z B-2 od strony przeciwnej, z wyznaczeniem pasów oraz z miejscami dopuszczonego postoju.',
                'Sam znak rzadko skutkuje mandatem wprost, ale jego zlekceważenie prowadzi do złego ustawienia auta, błędnego wjazdu w relacje skrętne albo do późnej reakcji na ruch wyjeżdżający z miejsc parkingowych.',
                'Najczęściej myli się ten znak z prostą informacją „jedź prosto”. Tymczasem chodzi o zasady ruchu na całej ulicy, a nie wyłącznie o najbliższy metr jezdni przed maską samochodu.',
                'Znak informuje, że na danym odcinku obowiązuje ruch w jednym kierunku i właśnie według tej organizacji trzeba czytać dalszą ulicę.',
                'Błąd zwykle polega na tym, że kierowca niby rozpoznaje znak, ale nie wyciąga z niego praktycznych konsekwencji dla parkowania, ustawienia pojazdu i obserwacji bocznych wlotów.',
                'W kolejnym kroku warto dodać czytelne porównanie D-3 z B-2 i z zakazem wjazdu od strony przeciwnej.'
            ),
            'D-4a' => $this->entry(
                'Znak D-4a Droga bez przejazdu informuje, że ulica lub odcinek nie prowadzi dalej do przelotowego połączenia. To znak planistyczny, który pozwala uniknąć niepotrzebnego wjazdu w ślepą ulicę.',
                'Jego sens nie polega na zakazie, lecz na oszczędzeniu kierowcy błędnego wyboru trasy. Jeśli szukasz przejazdu przez osiedle albo boczną ulicę, ten znak od razu mówi, że dalej trzeba będzie zawrócić lub wycofać się inną drogą.',
                'Najczęściej ustawia się go przy ulicach osiedlowych, dojazdach do posesji, drogach technicznych i wszędzie tam, gdzie odcinek kończy się placem manewrowym, parkingiem albo fizyczną barierą.',
                'Potraktuj znak jako informację do podjęcia decyzji odpowiednio wcześnie. Jeżeli nie masz celu na tej ulicy, lepiej wybrać inną relację niż szukać miejsca do zawracania dopiero na jej końcu.',
                'D-4a nie zakazuje wjazdu. Informuje tylko, że odcinek nie ma dalszego przejazdu, dlatego trzeba go łączyć z przepisami ogólnymi dotyczącymi zawracania, cofania i postoju w ciasnej przestrzeni.',
                'Ryzyko mandatu pojawia się zwykle pośrednio: kierowca ignoruje znak, a potem zawraca w miejscu niedozwolonym, blokuje przejazd albo wykonuje nerwowe cofanie w strefie o ograniczonej widoczności.',
                'Najczęstszy błąd to zignorowanie znaku w nadziei, że „może jednak będzie przelot”. W praktyce prowadzi to do niepotrzebnego manewrowania i do kłopotów z wyjazdem z wąskiej uliczki.',
                'Znak ostrzega planistycznie, że za wjazdem nie będzie przejazdu na wprost do innej ulicy ani drogi przelotowej.',
                'Kierowcy najczęściej nie doceniają wartości tej informacji i dopiero pod końcem ulicy szukają miejsca do zawrócenia, choć można było uniknąć całego wjazdu.',
                'Warto połączyć stronę z D-4b i D-4c, aby użytkownik łatwo rozumiał różnicę między ślepą ulicą a wlotem na ślepą ulicę.'
            ),
            'D-4b' => $this->entry(
                'Znak D-4b Wjazd na drogę bez przejazdu uprzedza przy skrzyżowaniu, że boczny wlot prowadzi w ślepą ulicę. To precyzyjniejsza informacja niż D-4a, bo dotyczy konkretnego skrętu.',
                'Kierowca dostaje ją jeszcze przed wyborem wlotu. Dzięki temu może od razu zrezygnować ze skrętu, jeśli szuka trasy przelotowej, albo świadomie wjechać tam tylko wtedy, gdy ma na tej ulicy konkretny cel.',
                'Najczęściej znak stoi przy skrzyżowaniach osiedlowych i lokalnych, na których tylko jedna z bocznych ulic nie ma dalszego połączenia z siecią dróg.',
                'Czytaj go razem z kierunkiem, którego dotyczy. Zamiast skręcać intuicyjnie, oceń wcześniej, czy rzeczywiście chcesz wjechać w ulicę kończącą się bez dalszego wyjazdu.',
                'D-4b opisuje funkcję konkretnego wlotu. Nie zakazuje skrętu, ale uprzedza, że po wjechaniu na wskazany odcinek nie będzie dalszej relacji przelotowej.',
                'Problemy pojawiają się zwykle wtedy, gdy kierowca zignoruje informację i dopiero za skrzyżowaniem zorientuje się, że wybrał ślepą ulicę. To często kończy się zbędnym zawracaniem lub blokowaniem dojazdu mieszkańcom.',
                'Najczęściej myli się zakres znaku: kierowca patrzy na niego, ale nie zauważa, że odnosi się do bocznego wlotu, a nie do drogi, po której aktualnie jedzie.',
                'Znak mówi, że właśnie ten wjazd, który masz przed sobą, prowadzi na drogę bez dalszego przejazdu.',
                'Błąd najczęściej wynika ze zbyt późnego spojrzenia na znak albo z niedoczytania, którego ramienia skrzyżowania dotyczy informacja.',
                'Dobrze zostawić na tej stronie prosty schemat porównujący D-4a z D-4b.'
            ),
            'D-4c' => $this->entry(
                'Znak D-4c Wjazd na drogę bez przejazdu z lewej strony uprzedza, że lewy wlot przy skrzyżowaniu prowadzi w ślepą ulicę. Informacja jest kierunkowa i dotyczy właśnie tej relacji skrętnej.',
                'Jego rolą jest uporządkowanie decyzji kierowcy jeszcze przed manewrem. Jeśli nawigacyjnie szukasz przejazdu przelotowego, już w tym miejscu wiesz, że skręt w lewo nie da dalszego wyjazdu.',
                'Najczęściej spotyka się go tam, gdzie po lewej stronie od głównej jezdni odchodzi krótki dojazd osiedlowy, techniczny albo uliczka kończąca się placem.',
                'Zwróć uwagę na kierunkowość znaku. Nie wystarczy rozpoznać symbol drogi bez przejazdu; trzeba jeszcze prawidłowo odczytać, że ślepa relacja znajduje się właśnie po lewej stronie.',
                'D-4c nie zamyka wlotu prawnie, lecz uprzedza o jego funkcji. W praktyce pozwala zawczasu zrezygnować z błędnego skrętu i uniknąć późniejszego manewrowania na końcu odcinka.',
                'Ryzyko sankcji pojawia się pośrednio, gdy kierowca po zignorowaniu znaku próbuje potem zawracać albo cofać w miejscu, które nie nadaje się do bezpiecznego wykonania takiego manewru.',
                'Najczęstszym błędem jest przeoczenie, że znak dotyczy lewego wlotu. Kierowca widzi sam symbol ślepej ulicy, ale nie łączy go poprawnie z układem skrzyżowania.',
                'Znak uprzedza, że skręt w lewo prowadzi na odcinek bez dalszego przejazdu i warto to uwzględnić przed wykonaniem manewru.',
                'Najczęściej zawodzi nie sama znajomość symbolu, tylko błędne powiązanie go z kierunkiem wlotu, którego dotyczy.',
                'Warto rozbudować opis o krótki blok „na co patrzeć na schemacie znaku”, bo tu często powstają pomyłki egzaminacyjne.'
            ),
            'D-5' => $this->entry(
                'Znak D-5 Pierwszeństwo na zwężonym odcinku jezdni informuje, że na przewężeniu to Twój kierunek ma uprzywilejowaną relację przejazdu względem pojazdów z przeciwka.',
                'To ważna informacja organizacyjna na odcinkach, na których dwa pojazdy nie miną się swobodnie. Znak nie znosi jednak obowiązku rozsądnej oceny sytuacji i zachowania ostrożności przy wjeździe w zwężenie.',
                'Najczęściej znak ustawia się przed mostkami, robotami drogowymi, wyspami i innymi miejscami, gdzie szerokość jezdni chwilowo nie pozwala na równoczesny przejazd obu kierunków.',
                'Jeśli masz pierwszeństwo, wjedź zdecydowanie, ale dopiero po ocenie, czy pojazd z przeciwka nie rozpoczął już przejazdu. Najbezpieczniej czytać znak razem z długością przewężenia i realną widocznością końca odcinka.',
                'D-5 określa pierwszeństwo na konkretnym przewężonym fragmencie drogi. Nie daje prawa do agresywnego forsowania przejazdu, jeżeli drugi pojazd już znajduje się w strefie zwężenia.',
                'Mandat lub odpowiedzialność za zdarzenie pojawia się najczęściej wtedy, gdy kierowca mający znak D-5 myli pierwszeństwo z bezwzględnym przywilejem i wjeżdża w zwężenie mimo oczywistego konfliktu z pojazdem nadjeżdżającym.',
                'Najczęstszy błąd polega na mechanicznym odczytaniu „mam pierwszeństwo, więc jadę”, bez sprawdzenia, czy drugi uczestnik nie wjechał już wcześniej w wąski odcinek.',
                'Znak informuje, że na przewężeniu Twój kierunek ma uprzywilejowaną relację przejazdu, ale wciąż trzeba ocenić realną sytuację na jezdni.',
                'Błąd wynika zwykle z utożsamienia pierwszeństwa z prawem do wymuszenia przejazdu bez względu na położenie pojazdu z przeciwka.',
                'Dobrze zestawić ten opis z B-31, aby użytkownik widział oba warianty organizacji ruchu na zwężeniu.'
            ),
            'D-6' => $this->entry(
                'Znak D-6 Przejście dla pieszych wskazuje miejsce, w którym ruch pieszy przecina jezdnię w sposób zorganizowany i wymagający od kierowcy wcześniejszego przygotowania do obserwacji oraz ewentualnego zatrzymania.',
                'To jeden z najważniejszych znaków informacyjnych w ruchu miejskim. Jego rola polega nie tylko na pokazaniu pasów, ale na uprzedzeniu, że właśnie tu trzeba intensywnie szukać pieszego dochodzącego do krawędzi jezdni.',
                'Znak ustawia się bezpośrednio przy przejściach dla pieszych, zwłaszcza przy szkołach, przystankach, skrzyżowaniach i w miejscach o zwiększonym ruchu pieszym.',
                'Reaguj kilka sekund wcześniej niż przy samych pasach. Zdejmij nogę z gazu, obserwuj obie strony chodnika i przygotuj się do ustąpienia pierwszeństwa pieszemu, który wchodzi lub zamierza wejść na przejście.',
                'D-6 wskazuje przejście dla pieszych, a obowiązki kierowcy wynikają dalej z przepisów o zachowaniu wobec pieszych. Znak trzeba czytać razem z oznakowaniem poziomym oraz z widocznością dojść do przejścia.',
                'W praktyce mandat pojawia się najczęściej nie za sam znak, lecz za spóźnioną reakcję: nieustąpienie pierwszeństwa pieszemu, zbyt szybki dojazd albo wyprzedzanie w rejonie przejścia.',
                'Najczęstszy błąd polega na tym, że kierowca zauważa dopiero pasy na jezdni, a nie przygotowuje prędkości i obserwacji już na etapie dostrzeżenia znaku D-6.',
                'Znak uprzedza, że przed Tobą znajduje się przejście dla pieszych i właśnie od tego momentu trzeba aktywnie szukać pieszego oraz przygotować się do ustąpienia mu pierwszeństwa.',
                'Najczęściej problemem nie jest nierozpoznanie znaku, lecz zbyt późne przeniesienie uwagi na otoczenie przejścia i pieszych podchodzących do krawędzi jezdni.',
                'Warto rozbudować tę stronę o porównanie D-6 z A-16 oraz o materiał o najczęstszych błędach przed przejściem.'
            ),
            'D-6a' => $this->entry(
                'Znak D-6a Przejazd dla rowerzystów wskazuje miejsce, w którym tor jazdy samochodu przecina się z wyznaczonym torem ruchu roweru. Dla kierowcy to sygnał do wcześniejszej obserwacji rowerzystów.',
                'Rowerzysta dojeżdża do przejazdu szybciej niż pieszy do przejścia, dlatego znak trzeba czytać z wyprzedzeniem i z myślą o dynamice zbliżającego się roweru, a nie tylko o samym miejscu przecięcia torów ruchu.',
                'Znak ustawia się przy przejazdach rowerowych, przy zjazdach z posesji, na skrzyżowaniach z drogą dla rowerów oraz wszędzie tam, gdzie ruch rowerowy przecina jezdnię ogólną.',
                'Poszerz obserwację na drogi dla rowerów i patrz dalej niż na najbliższy metr jezdni. Szczególnie ważna jest ocena prędkości rowerzysty, który może pojawić się w strefie kolizji szybciej, niż intuicyjnie zakłada kierowca.',
                'D-6a oznacza przejazd dla rowerzystów, a sposób zachowania trzeba odczytywać łącznie z organizacją pierwszeństwa, drogą dla rowerów i geometrią zjazdu albo skrzyżowania.',
                'Największe ryzyko mandatu pojawia się przy zbyt późnej reakcji na rowerzystę. Kierowca, który potraktuje znak jak odpowiednik przejścia dla pieszych bez uwzględnienia różnicy prędkości, łatwo doprowadzi do wymuszenia pierwszeństwa.',
                'Najczęściej błędnie zakłada się, że rowerzysta będzie zbliżał się wolno i przewidywalnie. W praktyce spóźniona ocena tempa dojazdu roweru jest częstszą przyczyną błędu niż samo przeoczenie znaku.',
                'Znak informuje, że przed Tobą znajduje się wyznaczony przejazd dla rowerzystów i trzeba wcześniej ocenić możliwość przecięcia toru jazdy z rowerem.',
                'Błąd zwykle wynika z niedoszacowania prędkości rowerzysty albo z patrzenia wyłącznie na jezdnię przed samochodem, bez kontroli drogi dla rowerów.',
                'Dobrze zestawić tę stronę z D-6 oraz z materiałem o różnicy między przejściem dla pieszych i przejazdem rowerowym.'
            ),
            'D-6b' => $this->entry(
                'Znak D-6b Przejście dla pieszych i przejazd dla rowerów wskazuje miejsce wspólnego lub sąsiadującego przecięcia jezdni przez dwie różne grupy niechronionych uczestników ruchu: pieszych i rowerzystów.',
                'To znak bardziej złożony niż samo przejście albo sam przejazd. Kierowca powinien od razu zakładać, że w strefie kolizji mogą pojawić się zarówno piesi poruszający się wolniej, jak i rowerzyści nadjeżdżający szybciej.',
                'Znak ustawia się tam, gdzie obok siebie albo w jednej zintegrowanej relacji występuje przejście dla pieszych i przejazd rowerowy, zwykle przy drogach dla rowerów, skrzyżowaniach i ciągach pieszo-rowerowych.',
                'Podejdź do niego szeroką obserwacją: skanuj chodnik, dojście do przejścia i drogę dla rowerów. Reakcja powinna być wcześniejsza niż przy zwykłym przejściu, bo jedna strefa łączy dwa typy zagrożeń.',
                'D-6b opisuje złożone miejsce organizacji ruchu. W praktyce obowiązki kierowcy wynikają dalej z przepisów o pieszych, rowerzystach i zasadach pierwszeństwa na danym przecięciu torów ruchu.',
                'Błąd przy tym znaku łatwo skutkuje poważną kolizją, bo kierowca często przygotowuje się tylko na jeden rodzaj uczestnika. Spóźniona reakcja wobec drugiej grupy może skończyć się mandatem lub odpowiedzialnością za wypadek.',
                'Najczęściej kierowca skupia się wyłącznie na pieszych albo wyłącznie na rowerzystach. Tymczasem istotą D-6b jest właśnie konieczność jednoczesnego czytania obu relacji ruchu.',
                'Znak uprzedza, że w jednym miejscu możesz spotkać zarówno przejście dla pieszych, jak i przejazd dla rowerów, więc trzeba przygotować obserwację pod obie grupy.',
                'Typowy błąd polega na zbyt wąskim patrzeniu na otoczenie i zignorowaniu jednej z dwóch relacji, które znak łączy w jednym miejscu.',
                'Warto później dołożyć porównanie D-6, D-6a i D-6b na jednym schemacie.'
            ),
            'D-7' => $this->entry(
                'Znak D-7 Droga ekspresowa oznacza początek odcinka o podwyższonym standardzie ruchu, ograniczonym dostępie i specjalnych zasadach korzystania z drogi.',
                'Po minięciu znaku wjeżdżasz w reżim drogi ekspresowej. Zmienia się nie tylko geometria trasy, ale też to, kto może na nią wjechać i jak należy planować zatrzymanie, zmianę pasa oraz zjazd.',
                'Znak ustawia się na wjazdach na drogę ekspresową, po węzłach i w miejscach, gdzie trzeba jednoznacznie potwierdzić, że zaczyna się odcinek o zasadach innych niż zwykła droga publiczna.',
                'Sprawdź, czy Twój pojazd może korzystać z takiej drogi, trzymaj płynny rytm ruchu i planuj manewry wcześniej niż w mieście. Na ekspresówce szczególnie kosztowne są późne decyzje o zjeździe i nieczytelne zmiany pasa.',
                'D-7 wprowadza odcinek drogi o szczególnych regułach. Trzeba go czytać razem z ograniczeniami dotyczącymi dopuszczonych uczestników, z zasadami postoju tylko w miejscach do tego przeznaczonych oraz z oznakowaniem pasa i węzłów.',
                'Ryzyko sankcji powstaje zwykle wtedy, gdy kierowca zatrzymuje się poza miejscem dozwolonym, cofa, zawraca albo jedzie pojazdem, który nie powinien znaleźć się na drodze ekspresowej.',
                'Najczęstszy błąd to traktowanie D-7 jak zwykłej informacji o „szybkiej drodze”, bez zrozumienia, że po znaku obowiązują inne reguły dostępności, postoju i planowania manewrów.',
                'Znak oznacza początek drogi ekspresowej, czyli odcinka o ograniczonym dostępie i odmiennych zasadach korzystania z drogi.',
                'Najczęściej kierowca nie docenia różnicy między zwykłą drogą krajową a ekspresową i zbyt późno planuje zjazd albo zatrzymanie.',
                'Warto później dodać porównanie D-7 z D-9 oraz blok o pojazdach, które nie mogą korzystać z dróg szybkiego ruchu.'
            ),
            'D-8' => $this->entry(
                'Znak D-8 Koniec drogi ekspresowej odwołuje zasady obowiązujące na ekspresówce i informuje, że od tego miejsca kierowca wraca do innego typu drogi oraz innej logiki odczytywania otoczenia.',
                'To ważny moment przejścia między dwoma reżimami ruchu. Po znaku zwykle zmienia się charakter zjazdów, liczba punktów kolizyjnych oraz sposób planowania prędkości i pasa ruchu.',
                'Znak ustawia się na końcu drogi ekspresowej albo przed odcinkiem, na którym standard ekspresowy i wynikające z niego zasady formalnie przestają obowiązywać.',
                'Nie przyjmuj automatycznie, że po końcu ekspresówki można jechać tak samo jak chwilę wcześniej. Trzeba od razu czytać nową geometrię drogi, możliwe skrzyżowania, wloty lokalne i obowiązujące limity.',
                'D-8 odwołuje status drogi ekspresowej. Dalej obowiązują już reguły właściwe dla nowego typu drogi oraz kolejne znaki ustawione na odcinku przejściowym.',
                'Ryzyko pojawia się wtedy, gdy kierowca zbyt długo prowadzi samochód „jak na ekspresówce” i za późno dostrzega zmianę otoczenia, limitów prędkości albo pojawienie się skrzyżowań i ruchu lokalnego.',
                'Najczęstszym błędem jest utrzymanie ekspresowego tempa myślenia i jazdy po minięciu znaku, mimo że dalsza droga wymaga już innej ostrożności i szybszego reagowania na lokalne zdarzenia.',
                'Znak informuje, że kończy się odcinek drogi ekspresowej i odtąd trzeba stosować zasady wynikające z nowego rodzaju drogi.',
                'Błąd najczęściej polega na zbyt późnym „przestawieniu głowy” z jazdy bezkolizyjnej na drogę, na której szybciej pojawiają się wloty, skrzyżowania i ruch lokalny.',
                'Warto połączyć tę stronę z D-7 i z materiałem o zachowaniu na odcinkach przejściowych.'
            ),
            'D-9' => $this->entry(
                'Znak D-9 Autostrada oznacza początek drogi o najwyższym standardzie dostępu i separacji ruchu. Po jego minięciu kierowca wjeżdża w szczególny reżim autostradowy.',
                'Autostrada nie jest po prostu „szeroką drogą”. To odcinek o ścisłych zasadach wjazdu, postoju, cofania i korzystania z pasa, dlatego znak D-9 od razu zmienia sposób planowania dalszej jazdy.',
                'Znak ustawia się na wjazdach na autostradę oraz za węzłami, na których trzeba ponownie potwierdzić, że dalsza trasa zachowuje status autostrady.',
                'Po wjeździe utrzymuj płynność ruchu, przygotowuj manewry wcześniej i pamiętaj, że nie każdy pojazd może korzystać z autostrady. Zjazdy, wyprzedzanie i zatrzymanie wymagają tu znacznie większego wyprzedzenia decyzji.',
                'D-9 oznacza początek autostrady i wszystkich szczególnych reguł z nią związanych. Trzeba go czytać razem z ograniczeniami dotyczącymi wjazdu określonych pojazdów oraz z zakazem zatrzymywania się poza miejscami do tego przeznaczonymi.',
                'Najpoważniejsze konsekwencje wynikają nie z samego znaku, lecz z naruszeń typowych dla autostrady: zatrzymania na poboczu bez potrzeby, cofania, zawracania lub korzystania z drogi przez pojazd, który nie spełnia wymagań.',
                'Najczęstszy błąd polega na przeniesieniu na autostradę miejskich nawyków: zbyt późnej zmiany pasa, zbyt późnego odczytania zjazdu albo niedoszacowania drogi potrzebnej do bezpiecznego manewru.',
                'Znak mówi, że zaczyna się autostrada, czyli droga o ograniczonym dostępie i szczególnych zasadach korzystania z pasa, postoju oraz wjazdu pojazdów.',
                'Najczęściej zawodzi planowanie: kierowca za późno myśli o zjeździe, przecina pasy w ostatniej chwili albo traktuje pobocze jak zwykłe miejsce technicznego zatrzymania.',
                'Dobrze rozszerzyć ten opis o porównanie autostrady z drogą ekspresową oraz o praktykę przygotowania do zjazdu.'
            ),
            'D-10' => $this->entry(
                'Znak D-10 Koniec autostrady odwołuje status autostrady i informuje, że kierowca opuszcza odcinek o najwyższym standardzie separacji ruchu. Dalsza droga może wymagać szybszej reakcji na lokalne zdarzenia.',
                'Po minięciu znaku nie wystarczy kontynuować jazdę w autostradowym rytmie. Zmienia się otoczenie, a wraz z nim liczba punktów kolizyjnych, sposób odczytywania zjazdów i limity wynikające z dalszego oznakowania.',
                'Znak ustawia się na końcu autostrady albo bezpośrednio przed miejscem, w którym kończy się formalny status tego typu drogi.',
                'Zdejmij z głowy założenie, że dalej jedziesz po drodze całkowicie bezkolizyjnej. Po D-10 trzeba bardzo szybko czytać nowe znaki, wloty, sygnalizację i lokalną organizację ruchu.',
                'D-10 odwołuje autostradowy status odcinka. Od tego miejsca obowiązują już reguły właściwe dla nowego typu drogi oraz limity i zakazy wprowadzone dalszym oznakowaniem.',
                'Ryzyko błędu pojawia się wtedy, gdy kierowca zbyt długo prowadzi samochód jak na autostradzie: za szybko, z za małą gotowością do zmiany otoczenia i zbyt późną reakcją na nowe znaki.',
                'Najczęstszy błąd to brak płynnego przejścia z jazdy autostradowej do ruchu na drodze, gdzie mogą szybciej pojawić się skrzyżowania, sygnalizacja, piesi lub ruch lokalny.',
                'Znak informuje, że autostrada właśnie się kończy i trzeba odtąd prowadzić pojazd według zasad następnego rodzaju drogi.',
                'Najczęściej kierowca nie przestawia się wystarczająco szybko na bardziej złożone otoczenie i przez chwilę jedzie tak, jakby nadal był na autostradzie.',
                'Warto trzymać ten opis obok D-9 i sekcji o odcinkach przejściowych po zjeździe z dróg szybkiego ruchu.'
            ),
            'D-11' => $this->entry(
                'Znak D-11 Początek pasa ruchu dla autobusów informuje, że od tego miejsca rozpoczyna się buspas, czyli pas przeznaczony przede wszystkim dla autobusów i innych pojazdów dopuszczonych oznakowaniem.',
                'To znak o bardzo praktycznym znaczeniu dla kierowcy samochodu osobowego. Po minięciu D-11 trzeba od razu wiedzieć, że nie każdy może korzystać z nowo wydzielonego pasa, nawet jeśli wygląda on „wolno i szeroko”.',
                'Znak ustawia się na początku buspasów, zwykle w miastach, na dojazdach do skrzyżowań i na odcinkach, gdzie organizator ruchu chce uprzywilejować transport zbiorowy.',
                'Przed znakiem zawczasu ustaw się na właściwym pasie. Jeśli nie masz prawa wjechać na buspas, nie odkładaj zmiany pasa do ostatniego momentu i czytaj uważnie ewentualne wyjątki z tabliczek lub oznakowania poziomego.',
                'D-11 rozpoczyna odcinek pasa ruchu o szczególnym przeznaczeniu. Zakres dopuszczonych pojazdów zawsze trzeba czytać łącznie z dodatkowymi tabliczkami oraz z oznaczeniem poziomym na jezdni.',
                'Mandat pojawia się zwykle wtedy, gdy kierowca samochodu osobowego wjeżdża na buspas bez podstawy albo wykorzystuje go do omijania korka, nie odczytując wyjątków i czasu obowiązywania.',
                'Najczęstszy błąd to zbyt późne zauważenie początku buspasa i nerwowe przecinanie linii albo pozostanie na pasie, z którego kierowca nie ma prawa dalej korzystać.',
                'Znak informuje, że od tego miejsca zaczyna się pas przeznaczony dla autobusów i tylko niektórych innych pojazdów wskazanych dodatkowym oznakowaniem.',
                'Najczęściej kierowca nie planuje ustawienia auta odpowiednio wcześnie i dopiero przy samym początku buspasa próbuje improwizować zmianę pasa albo zostaje na nim bez uprawnienia.',
                'Warto później połączyć tę stronę z D-12 oraz z materiałem o wyjątkach dopuszczających wjazd na buspas.'
            ),
            'D-12' => $this->entry(
                'Znak D-12 Pas ruchu dla autobusów potwierdza, że dany pas na dalszym odcinku jest przeznaczony dla autobusów i innych pojazdów dopuszczonych przez oznakowanie.',
                'W przeciwieństwie do D-11, który mówi o początku takiego pasa, D-12 utrwala jego obowiązywanie na dalszym przebiegu. Kierowca nie może więc tłumaczyć pozostania na nim tym, że „początek był wcześniej i już go nie widział”.',
                'Znak ustawia się wzdłuż buspasa, zwłaszcza po skrzyżowaniach i w miejscach, gdzie trzeba przypomnieć uczestnikom ruchu, że wydzielony pas nadal zachowuje swój szczególny status.',
                'Trzymaj się zasad przypisanych do pasa. Jeżeli nie jesteś pojazdem uprawnionym, wróć na właściwy pas przy pierwszej bezpiecznej okazji i nie traktuj buspasa jako zwykłej rezerwy na korektę toru jazdy.',
                'D-12 podtrzymuje przeznaczenie pasa ruchu. O tym, kto może z niego korzystać, decydują przepisy oraz ewentualne dodatkowe oznakowanie ustawione dla danego odcinka.',
                'Najczęstsze sankcje wiążą się z nieuprawnionym poruszaniem się po buspasie, omijaniem korka takim pasem albo blokowaniem pojazdów komunikacji zbiorowej.',
                'Typowy błąd polega na potraktowaniu znaku jak mało ważnego przypomnienia. W praktyce D-12 ma pełne znaczenie organizacyjne i po skrzyżowaniu nadal rozstrzyga, jakiego pasa wolno używać.',
                'Znak potwierdza, że na dalszym odcinku dany pas jest nadal pasem dla autobusów i nie staje się automatycznie zwykłym pasem ruchu.',
                'Najczęściej kierowcy mylnie zakładają, że po skrzyżowaniu buspas się skończył, chociaż D-12 właśnie potwierdza jego dalsze obowiązywanie.',
                'Dobrze rozbudować opis o przykłady sytuacji, w których pas jest dostępny także dla taksówek, motocykli albo pojazdów z lokalnych wyjątków.'
            ),
            'D-13a' => $this->entry(
                'Znak D-13a Początek pasa ruchu informuje, że na odcinku pojawia się dodatkowy pas albo zmienia się układ pasów ruchu. Dla kierowcy to sygnał, że dalej nie jedzie już po dotychczasowym, prostym schemacie jezdni.',
                'Znaczenie znaku jest czysto organizacyjne, ale bardzo praktyczne. Dzięki niemu kierowca ma czas zrozumieć nowy układ pasa jeszcze przed miejscem, w którym pas się naprawdę wyodrębni i zacznie wpływać na wybór relacji jazdy.',
                'Znak ustawia się tam, gdzie rozpoczyna się nowy pas ruchu, poszerza się jezdnia albo organizacja ruchu wymaga wcześniejszego uprzedzenia o dodatkowej przestrzeni dla pojazdów.',
                'Patrz dalej niż na samą tablicę. Od razu oceń, czy nowy pas jest Ci potrzebny do dalszej jazdy, i zacznij planować ustawienie samochodu z wyprzedzeniem, zamiast reagować dopiero przy rozwidleniu pasów.',
                'D-13a nie wprowadza sam w sobie pierwszeństwa, ale opisuje nowy układ jezdni. Trzeba go czytać razem ze strzałkami kierunkowymi, liniami prowadzącymi oraz z dalszym oznakowaniem pasa.',
                'Ryzyko błędu polega głównie na spóźnionym ustawieniu auta. Zbyt późna reakcja na nowy pas często prowadzi do nieczytelnej zmiany toru jazdy albo do przegapienia właściwej relacji skrętnej.',
                'Najczęściej kierowca zauważa dodatkowy pas dopiero wzrokiem na jezdni i ignoruje wcześniejszą informację z D-13a, przez co jego decyzja o ustawieniu auta jest spóźniona.',
                'Znak uprzedza, że od tego miejsca zaczyna się nowy układ pasów ruchu i warto wcześniej zdecydować, który pas będzie właściwy do dalszej jazdy.',
                'Błąd wynika zwykle z pozostawienia decyzji na ostatnią chwilę, kiedy pas już fizycznie się rozwidla i brakuje miejsca na spokojną korektę toru.',
                'Warto zestawić ten znak z D-14 oraz z oznakowaniem poziomym pokazującym, dokąd prowadzi każdy z pasów.'
            ),
            'D-14' => $this->entry(
                'Znak D-14 Koniec pasa ruchu informuje, że jeden z pasów na dalszym odcinku się zakończy i kierowca musi odpowiednio wcześniej przygotować się do włączenia w pozostały układ jezdni.',
                'To znak porządkujący przestrzeń przed zwężeniem. Nie chodzi w nim o samo „zniknięcie asfaltu”, tylko o danie czasu na bezpieczne zakończenie jazdy pasem, który zaraz przestanie istnieć.',
                'Znak ustawia się przed miejscami, gdzie pas ruchu zanika: przed zwężeniami, końcem dodatkowego pasa do wyprzedzania, końcem pasa rozbiegowego albo inną zmianą geometrii jezdni.',
                'Nie czekaj do końca pasa. Po dostrzeżeniu znaku zacznij szukać bezpiecznej luki i odpowiednio wcześnie włącz się w dalszy tor ruchu, zamiast wymuszać wpuszczenie przy samej krawędzi zaniku pasa.',
                'D-14 informuje o końcu pasa, a praktyka zachowania wynika dalej z zasad zmiany pasa ruchu, pierwszeństwa i obowiązku zachowania szczególnej ostrożności przy włączaniu się w tor innych pojazdów.',
                'Najczęstsze konsekwencje pojawiają się wtedy, gdy kierowca dojeżdża „do końca na siłę”, a potem gwałtownie wciska się przed inny pojazd. Taki błąd łatwo prowadzi do kolizji i odpowiedzialności za nieprawidłową zmianę pasa.',
                'Najczęstszy błąd to odkładanie reakcji do ostatniego metra. Kierowca widzi znak, ale liczy, że uda się „jeszcze kawałek” pojechać swoim pasem bez planowania bezpiecznego włączenia.',
                'Znak uprzedza, że wybrany pas się skończy i już od tego momentu trzeba przygotować bezpieczną zmianę toru jazdy.',
                'Najczęściej zawodzi nie znajomość znaczenia znaku, lecz spóźniony moment podjęcia decyzji o włączeniu się w sąsiedni strumień ruchu.',
                'Dobrze uzupełnić stronę o materiał o kulturze wpuszczania na końcu pasa i o różnicy między końcem pasa a początkiem pasa wyłączania.'
            ),
            'D-15' => $this->entry(
                'Znak D-15 Przystanek autobusowy oznacza miejsce zatrzymywania się autobusów oraz innych pojazdów wykonujących odpłatny przewóz osób na regularnych liniach. Oznacza także miejsce zatrzymywania się pojazdów przeznaczonych do przewozu dzieci do szkół i przedszkoli.',
                'Dla kierowcy znak jest informacją o przystanku transportu publicznego, wzmożonym ruchu pieszych oraz możliwości włączania się autobusu do ruchu. Na obszarze zabudowanym autobus sygnalizujący zamiar wyjazdu z zatoki lub zmiany pasa powinien otrzymać możliwość wykonania tego manewru.',
                'D-15 ustawia się w miejscu przystanku autobusowego, przy zatoce albo bezpośrednio przy krawędzi jezdni. Obszar przystanku może być dodatkowo wyznaczony linią poziomą P-17.',
                'Zbliżając się do oznaczonego przystanku na obszarze zabudowanym, zmniejsz prędkość i obserwuj pasażerów oraz autobus. Jeżeli autobus kierunkowskazem sygnalizuje zamiar wjazdu z zatoki na jezdnię lub na sąsiedni pas, w razie potrzeby zatrzymaj się, aby umożliwić mu wykonanie manewru.',
                'D-15 wskazuje miejsce zatrzymywania się pojazdów regularnego transportu osób. Obowiązek ułatwienia autobusowi włączenia się do ruchu dotyczy oznaczonego przystanku na obszarze zabudowanym; kierujący autobusem nadal musi upewnić się, że jego manewr nie spowoduje zagrożenia.',
                'Nieprawidłowe zachowanie przy przystanku może prowadzić do stworzenia zagrożenia dla pasażerów albo kolizji z autobusem opuszczającym zatokę. Szczególnie ryzykowne jest przejeżdżanie obok przystanku bez obserwacji pieszych oraz blokowanie sygnalizowanego wyjazdu autobusu.',
                'Najczęstszy błąd polega na przyspieszaniu, aby zdążyć przejechać przed autobusem sygnalizującym wyjazd z zatoki. Kierowcy często zapominają też, że w rejonie przystanku pieszy może nagle wejść na jezdnię zza stojącego pojazdu.',
                'Znak wskazuje przystanek autobusowy. Na obszarze zabudowanym przygotuj się do umożliwienia autobusowi wyjazdu z zatoki lub zmiany pasa, jeżeli sygnalizuje taki zamiar.',
                'Typowym błędem jest wymuszanie przejazdu przed autobusem albo obserwowanie wyłącznie pojazdu i pominięcie pasażerów poruszających się przy przystanku.',
                'Powiązać stronę ze znakiem poziomym P-17 oraz materiałem wyjaśniającym zasady wyjazdu autobusu z zatoki przystankowej.'
            ),
            'D-17' => $this->entry(
                'Znak D-17 Przystanek tramwajowy oznacza miejsce zatrzymywania się tramwajów wykonujących odpłatny przewóz osób na regularnych liniach. Dla kierowcy jest także wyraźnym sygnałem, że w bezpośrednim otoczeniu jezdni mogą pojawić się pasażerowie wsiadający do tramwaju lub z niego wysiadający.',
                'Znak nie jest wyłącznie informacją dla pasażerów i motorniczego. Jeżeli przystanek znajduje się przy torowisku przebiegającym w jezdni, kierowca musi zawczasu rozpoznać, czy pasażerowie korzystają z wysepki, czy będą przechodzili pomiędzy drogą dla pieszych a tramwajem przez część jezdni przeznaczoną dla pojazdów.',
                'D-17 ustawia się w miejscu przystanku tramwajowego, zwykle przy peronie, wysepce albo przy krawędzi jezdni w sąsiedztwie torowiska. Szczególnej uwagi wymagają przystanki bez wysepki, na których droga pasażera do drzwi tramwaju przecina tor jazdy samochodów.',
                'Zachowaj szczególną ostrożność i obserwuj jednocześnie tramwaj, drzwi pojazdu oraz osoby oczekujące przy przystanku. Gdy przystanek nie ma wysepki, a tramwaj wjeżdża na przystanek lub już na nim stoi, zatrzymaj pojazd tak, aby pasażerowie mogli swobodnie dojść do tramwaju albo wrócić na drogę dla pieszych.',
                'D-17 wyznacza miejsce zatrzymywania się tramwajów na regularnej linii. Obowiązki kierowcy przejeżdżającego obok takiego miejsca wynikają również z przepisów o szczególnej ostrożności przy oznaczonym przystanku oraz o zapewnieniu pasażerom bezpiecznego dojścia przy przystanku bez wysepki.',
                'Najpoważniejsze konsekwencje wiążą się z niezatrzymaniem pojazdu lub przejazdem bez odpowiedniej ostrożności wtedy, gdy pasażerowie wchodzą na jezdnię do tramwaju albo z niego wysiadają. Taki błąd może prowadzić do potrącenia, mandatu i odpowiedzialności za stworzenie zagrożenia w ruchu.',
                'Najczęstszy błąd polega na obserwowaniu wyłącznie samego tramwaju i pominięciu pasażerów. Kierowca może też błędnie założyć, że wolno mu przejechać obok stojącego tramwaju, mimo że przystanek nie ma wysepki i ludzie muszą przekroczyć jego pas ruchu.',
                'Znak wskazuje przystanek tramwajowy, a przy przystanku bez wysepki wymaga od kierowcy gotowości do zatrzymania i zapewnienia pasażerom swobodnego dojścia do tramwaju lub na drogę dla pieszych.',
                'Najczęściej kierowca zbyt późno rozpoznaje brak wysepki albo skupia się na tramwaju zamiast na osobach, które za chwilę wejdą na jezdnię.',
                'Warto powiązać tę stronę ze znakiem A-21 Tramwaj oraz z materiałem o przejeżdżaniu obok przystanku tramwajowego bez wysepki.'
            ),
            'D-18' => $this->parkingEntry(
                $sign,
                'wskazuje zorganizowane miejsce przeznaczone do parkowania pojazdów',
                'Stawia się go przy parkingach miejskich, zatokach postojowych, placach przy obiektach usługowych i wszędzie tam, gdzie organizator ruchu wyraźnie wyznacza miejsce postoju.',
                'Czytaj znak razem z tabliczkami, liniami stanowisk i zasadami lokalnymi. Sam symbol parkingu nie rozstrzyga jeszcze, czy postój jest płatny, czasowy albo przeznaczony dla określonych pojazdów.',
                'Najczęstszym błędem jest utożsamienie znaku z pełną dowolnością parkowania. Kierowca widzi literę P i nie sprawdza, czy obok nie ma zastrzeżeń dotyczących czasu, sposobu ustawienia auta lub opłat.',
                'Warto później dołożyć porównanie D-18 z D-18a, D-18b i ze strefą płatnego parkowania.'
            ),
            'D-18a' => $this->parkingEntry(
                $sign,
                'oznacza parking lub stanowisko zastrzeżone dla określonej grupy pojazdów albo konkretnego użytkownika wskazanego dodatkowym oznakowaniem',
                'Najczęściej pojawia się na miejscach dla służb, osób z uprawnieniami, lokatorów, pojazdów firmowych albo innych użytkowników opisanych znakiem lub tabliczką.',
                'Przed zaparkowaniem sprawdź, komu miejsce jest zastrzeżone. Jeśli znak nie dotyczy Twojego pojazdu, fizycznie wolne stanowisko nadal nie daje prawa do postoju.',
                'Typowy błąd polega na zajmowaniu wolnego miejsca bez przeczytania, dla kogo zostało wyłączone. Kierowca widzi parking, ale pomija najważniejszą część informacji: jego zastrzeżony charakter.',
                'Dobrze powiązać ten opis z tabliczkami określającymi wyjątki i z materiałem o miejscach dla osób z niepełnosprawnościami.'
            ),
            'D-18b' => $this->parkingEntry(
                $sign,
                'informuje o parkingu zadaszonym, czyli miejscu postoju osłoniętym konstrukcją hali, wiaty albo garażu wielostanowiskowego',
                'Znak ustawia się przy wjazdach do parkingów pod dachem, wielopoziomowych obiektów parkingowych albo zadaszonych zatok postojowych.',
                'Traktuj go jako informację o rodzaju obiektu, a nie o innych zasadach ruchu. Przed wjazdem nadal trzeba sprawdzić wysokość, sposób poboru opłat, kierunki ruchu wewnątrz i ograniczenia wynikające z lokalnego oznakowania.',
                'Najczęstszym błędem jest skupienie się na samym fakcie zadaszenia i pominięcie praktycznych ograniczeń wjazdu, takich jak wysokość obiektu, jednokierunkowy układ alejek albo system opłat.',
                'Warto później dołożyć krótki moduł o tym, jak czytać znaki wysokości i kierunku ruchu w parkingach zadaszonych.'
            ),
            'D-19' => $this->entry(
                'Znak D-19 Postój taksówek wyznacza miejsce przeznaczone do oczekiwania taksówek. Dla innych kierowców to sygnał, że dana przestrzeń nie jest zwykłym ogólnodostępnym miejscem postoju.',
                'Sens znaku polega na wydzieleniu fragmentu pasa albo zatoki dla taksówek obsługujących pasażerów. Kierowca samochodu osobowego powinien czytać go jako ograniczenie funkcji tego miejsca, nawet jeśli chwilowo stoi tam pusto.',
                'Znak ustawia się przy dworcach, hotelach, lotniskach, centrach miast i w innych punktach, gdzie organizator ruchu przewiduje regularną obsługę pasażerów przez taksówki.',
                'Nie zatrzymuj się tam „na chwilę”, żeby wysadzić pasażera lub odebrać telefon. Jeśli nie jesteś pojazdem uprawnionym do korzystania z postoju taksówek, szukaj innego miejsca postoju lub zatrzymania zgodnego z przepisami.',
                'D-19 wyznacza miejsce postoju o określonym przeznaczeniu. W praktyce trzeba go czytać razem z dodatkowymi tabliczkami, które mogą precyzować zasięg postoju albo wyjątki dla innych pojazdów.',
                'Najczęstszy problem to zajmowanie miejsca przez kierowców, którzy nie są taksówkami. Nawet krótki postój w takim miejscu może skutkować interwencją i mandatem za nieuprawnione korzystanie z wydzielonej przestrzeni.',
                'Najczęstszy błąd polega na potraktowaniu znaku jak zwykłej informacji o tym, że „tu bywa taksówka”. W rzeczywistości jest to konkretne wyznaczenie przeznaczenia miejsca postoju.',
                'Znak wskazuje, że dane miejsce zostało przeznaczone na postój taksówek i inni kierowcy nie powinni go zajmować jak zwykłego parkingu.',
                'Błąd najczęściej wynika z bagatelizowania przeznaczenia miejsca i krótkiego zatrzymania się tam z myślą, że skoro stoi pusto, to można na moment skorzystać.',
                'Warto połączyć tę stronę z D-20 i z materiałem o różnicy między zatrzymaniem, postojem i miejscem zastrzeżonym.'
            ),
            'D-20' => $this->entry(
                'Znak D-20 Koniec postoju taksówek odwołuje wcześniej wyznaczone miejsce przeznaczone dla taksówek. Informuje, że od tego punktu kończy się zasięg zastrzeżonego postoju.',
                'To ważne zwłaszcza tam, gdzie postój taksówek rozciąga się wzdłuż krawędzi jezdni. D-20 pokazuje, gdzie kończy się strefa zarezerwowana dla tej funkcji i od którego miejsca wracają zwykłe zasady organizacji postoju.',
                'Znak stawia się na końcu odcinka oznaczonego wcześniej jako postój taksówek, najczęściej przy długich zatokach lub krawędziowych miejscach oczekiwania na pasażerów.',
                'Nie traktuj go jako automatycznej zgody na dowolne parkowanie od pierwszego metra za znakiem. Po jego minięciu trzeba jeszcze sprawdzić dalsze oznakowanie i to, czy nie obowiązują inne ograniczenia postoju.',
                'D-20 odwołuje status postoju taksówek. Dalej obowiązują już zasady wynikające z kolejnych znaków i z ogólnych reguł zatrzymania oraz postoju.',
                'Ryzyko błędu polega na tym, że kierowca kończy lub zaczyna postój w złym miejscu, bo nie zauważył końca zastrzeżonego odcinka albo uznał, że po znaku nie trzeba już nic sprawdzać.',
                'Najczęstszym błędem jest przeoczenie granicy obowiązywania postoju taksówek albo przekonanie, że po końcu zastrzeżonego miejsca dalej nie ma już żadnych innych zasad dotyczących postoju.',
                'Znak informuje, że od tego miejsca kończy się wyznaczony postój taksówek i dalszy odcinek trzeba ocenić według kolejnego oznakowania.',
                'Kierowcy najczęściej zakładają zbyt dużo po samym końcu postoju i nie sprawdzają, czy za znakiem nie obowiązuje np. zakaz postoju albo inna organizacja stanowisk.',
                'Naturalna para do D-19 i dobry punkt do wyjaśnienia zasięgu działania znaków wyznaczających funkcję miejsca.'
            ),
            'D-21' => $this->serviceEntry(
                $sign,
                'szpital',
                'Pozwala wcześniej zaplanować zjazd do placówki medycznej i przypomina, że w jej otoczeniu częściej pojawiają się karetki, piesi oraz kierowcy wykonujący nagłe manewry dojazdu.',
                'Znak ustawia się na trasach dojazdowych do szpitali i przy ulicach, z których można bezpośrednio dojechać do dużej placówki medycznej.',
                'Jeżeli nie zjeżdżasz do szpitala, jedź przewidywalnie i z większą uwagą na ruch wlotowy. Jeżeli chcesz skręcić, ustaw pas wcześniej i nie tnij toru jazdy przy samym wjeździe.',
                'Typowym błędem jest impulsywne hamowanie po późnym zauważeniu znaku albo pominięcie tego, że przy szpitalu częściej spotkasz ruch pieszy i pojazdy uprzywilejowane.',
                'Warto potem dołożyć blok o zachowaniu kierowcy w sąsiedztwie szpitala i przejeździe karetek.'
            ),
            'D-21a' => $this->serviceEntry(
                $sign,
                'komisariat Policji',
                'Ma pomóc w orientacji przestrzennej i szybkim odnalezieniu jednostki Policji bez nerwowego szukania zjazdu na ostatnią chwilę.',
                'Najczęściej pojawia się na głównych ulicach miejskich i na dojazdach do rejonu, w którym znajduje się komisariat lub posterunek.',
                'Czytaj znak spokojnie i planuj dojazd wcześniej. Sama obecność obiektu nie zmienia zasad pierwszeństwa na drodze, więc nie wykonuj gwałtownych manewrów tylko po to, by od razu skręcić.',
                'Najczęstszym błędem jest zbyt późna decyzja o zjeździe lub nagłe zatrzymanie przy krawędzi jezdni, bo kierowca dopiero po minięciu znaku rozumie, gdzie znajduje się obiekt.',
                'Na tej stronie warto później dopisać sekcję o tym, że znak nie daje żadnych szczególnych uprawnień kierowcy szukającemu komisariatu.'
            ),
            'D-22' => $this->serviceEntry(
                $sign,
                'punkt pierwszej pomocy lub punkt opatrunkowy',
                'To informacja szczególnie cenna dla podróżnych, którzy potrzebują szybkiej pomocy medycznej niewymagającej od razu pełnej placówki szpitalnej.',
                'Znak występuje przy drogach turystycznych, trasach szybkiego ruchu, obiektach dla podróżnych i w miejscach, z których można dojechać do punktu udzielania pierwszej pomocy.',
                'Jeśli chcesz zjechać, rób to planowo. Jeżeli jedziesz dalej, nie traktuj znaku jako powodu do gwałtownego zwolnienia, lecz jako informację o dostępnej funkcji drogowej w razie potrzeby.',
                'Błędem jest reagowanie zbyt impulsywnie na samą informację o pomocy medycznej albo mylenie punktu opatrunkowego ze szpitalem i szukanie innych uprawnień na drodze, których znak nie daje.',
                'Dobrze będzie później rozdzielić w treści zakres D-21 i D-22: szpital kontra punkt pierwszej pomocy.'
            ),
            'D-23' => $this->serviceEntry(
                $sign,
                'stacja paliwowa',
                'Pozwala kierowcy zaplanować tankowanie wcześniej, bez wymuszonych zjazdów i chaotycznych zmian pasa przy samym wlocie do obiektu.',
                'Znak ustawia się przy drogach tranzytowych, przed zjazdami do stacji, na dojazdach do MOP-ów oraz tam, gdzie ważne jest wcześniejsze uprzedzenie o dostępnej infrastrukturze paliwowej.',
                'Potraktuj znak planistycznie. Jeśli potrzebujesz paliwa, ustaw się do zjazdu wcześniej; jeśli nie, jedź dalej płynnie i nie reaguj gwałtownie na widok dystrybutorów przy drodze.',
                'Najczęstszym błędem jest decyzja o zjeździe podjęta za późno, skutkująca nagłym hamowaniem lub przecinaniem pasa ruchu w ostatniej chwili.',
                'Warto później powiązać tę stronę z D-23a, D-23b i D-23c jako rodziną znaków paliwowo-energetycznych.'
            ),
            'D-23a' => $this->serviceEntry(
                $sign,
                'stacja paliwowa oferująca gaz do napędu pojazdów',
                'Znak doprecyzowuje rodzaj dostępnego paliwa i jest szczególnie istotny dla kierowców planujących tankowanie LPG, CNG lub innego gazu napędowego.',
                'Najczęściej pojawia się przy trasach tranzytowych i stacjach, na których trzeba wcześniej poinformować kierowcę o bardziej specjalistycznej usłudze paliwowej.',
                'Jeżeli korzystasz z napędu gazowego, potraktuj znak jako informację do spokojnego przygotowania zjazdu. Jeżeli nie, nie reaguj gwałtownie tylko dlatego, że obiekt pojawił się przy trasie.',
                'Typowy błąd to zbyt późne zorientowanie się, że właśnie ten zjazd oferuje potrzebny rodzaj paliwa, i próba nerwowego manewru po minięciu wlotu.',
                'Dobrze rozszerzyć opis o prostą różnicę między zwykłą stacją paliwową a stacją z gazem do napędu pojazdów.'
            ),
            'D-23b' => $this->serviceEntry(
                $sign,
                'stacja paliwowa z punktem ładowania pojazdów elektrycznych',
                'Łączy informację o klasycznym tankowaniu z możliwością ładowania auta elektrycznego, co pomaga planować postoje na dłuższych trasach przy różnych typach napędu.',
                'Znak ustawia się przy obiektach, które oferują jednocześnie paliwo tradycyjne i infrastrukturę ładowania, zwykle na trasach głównych i przy większych stacjach.',
                'Czytaj go jak informację logistyczną. Jeżeli chcesz skorzystać z obiektu, przygotuj zjazd wcześniej; jeżeli jedziesz dalej, nie wykonuj gwałtownej korekty tylko dlatego, że stacja ma szerszą ofertę.',
                'Najczęściej kierowca za późno odczytuje, że obiekt łączy dwa rodzaje zasilania, i podejmuje spóźnioną decyzję o zjeździe.',
                'Warto później spiąć ten opis z D-23 i D-23c w jednym porównaniu dla kierowców aut spalinowych i elektrycznych.'
            ),
            'D-23c' => $this->serviceEntry(
                $sign,
                'punkt ładowania pojazdów elektrycznych',
                'To znak usługowy ważny głównie dla kierowców aut elektrycznych, bo pozwala wcześniej zaplanować postój techniczny i dobrać zjazd bez improwizacji.',
                'Ustawia się go przy ładowarkach publicznych, MOP-ach, parkingach i miejscach, w których istotne jest wcześniejsze uprzedzenie o dostępnej infrastrukturze do ładowania.',
                'Planuj postój wcześniej, zwłaszcza jeśli jedziesz przy niskim poziomie energii. Sam znak nie rozstrzyga jeszcze dostępności stanowiska, sposobu płatności ani mocy ładowarki, więc nie warto reagować impulsywnie.',
                'Najczęstszym błędem jest zbyt późna decyzja o zjeździe lub automatyczne założenie, że sam symbol oznacza wolne i natychmiast dostępne stanowisko ładowania.',
                'Dobrze dołożyć później praktyczne FAQ o różnicy między samym punktem ładowania a stacją paliwową z ładowarką.'
            ),
            'D-24' => $this->serviceEntry(
                $sign,
                'telefon alarmowy lub publiczny punkt telefoniczny',
                'Jego sens jest dziś bardziej awaryjny niż codzienny, ale nadal chodzi o poinformowanie kierowcy, gdzie może skorzystać z łączności w razie potrzeby.',
                'Znak pojawia się przy trasach, MOP-ach i innych miejscach, gdzie przewiduje się potrzebę zapewnienia podróżnym dostępu do telefonu.',
                'Nie zatrzymuj się gwałtownie przy samym znaku. Jeśli potrzebujesz skorzystać z telefonu, zaplanuj bezpieczny zjazd albo postój w miejscu do tego przeznaczonym.',
                'Błąd polega zwykle na traktowaniu znaku jak uzasadnienia dla nagłego postoju przy krawędzi jezdni, choć sam symbol informuje o usłudze, a nie o prawie zatrzymania w dowolnym miejscu.',
                'Na tej stronie można później dopisać, że w praktyce znak ma dziś bardziej znaczenie awaryjne niż nawigacyjne.'
            ),
            'D-25' => $this->serviceEntry(
                $sign,
                'poczta',
                'Pomaga kierowcy zlokalizować placówkę pocztową bez późnego szukania adresu i bez nerwowych zmian pasa w ścisłej zabudowie.',
                'Najczęściej występuje w miastach i miejscowościach, gdzie ważne jest wcześniejsze wskazanie kierowcy dojazdu do urzędu pocztowego.',
                'Jeżeli chcesz zjechać do poczty, ustaw pojazd i wybierz pas odpowiednio wcześnie. Sam znak nie zmienia pierwszeństwa ani nie usprawiedliwia postoju w niedozwolonym miejscu przed budynkiem.',
                'Najczęstszy błąd to spóźnione hamowanie albo zatrzymanie się „na awaryjnych” przy poczcie, bo kierowca zbyt późno odczytał informację o obiekcie.',
                'Warto zostawić tę stronę w klastrze usług miejskich obok D-21a i D-34.'
            ),
            'D-26' => $this->serviceEntry(
                $sign,
                'stacja obsługi technicznej',
                'Znak pomaga zaplanować zjazd do miejsca, w którym można wykonać przegląd, drobną naprawę albo skorzystać z zaplecza technicznego dla pojazdu.',
                'Najczęściej ustawia się go przy trasach przelotowych, strefach usług dla kierowców i na dojazdach do warsztatów lub obiektów serwisowych.',
                'Jeżeli potrzebujesz obsługi technicznej, przygotuj zjazd wcześniej. Nie ścinaj pasa ruchu w ostatniej chwili i nie traktuj znaku jako uzasadnienia do zatrzymania auta w przypadkowym miejscu przed obiektem.',
                'Typowy błąd polega na reagowaniu zbyt późno i na gwałtownej korekcie toru jazdy po dostrzeżeniu warsztatu lub bramy wjazdowej.',
                'Warto później rozbudować klaster o D-26a i D-26b jako usługi wyspecjalizowane.'
            ),
            'D-26a' => $this->serviceEntry(
                $sign,
                'punkt wulkanizacji',
                'Jest szczególnie użyteczny przy problemach z oponą albo przy planowaniu serwisu ogumienia w trasie.',
                'Znak pojawia się na drogach tranzytowych, przy stacjach obsługi oraz na dojazdach do punktów zajmujących się naprawą i wymianą opon.',
                'Jeżeli sytuacja jest awaryjna, najpierw zadbaj o bezpieczne miejsce zatrzymania, a dopiero potem planuj dojazd do punktu. Sam znak nie daje prawa do awaryjnego manewru na jezdni.',
                'Najczęstszym błędem jest próba dotarcia do punktu za wszelką cenę mimo pogarszającego się stanu opony albo późne zauważenie zjazdu i gwałtowna zmiana pasa.',
                'Dobrze będzie połączyć stronę z materiałem o tym, kiedy można jechać dalej po uszkodzeniu ogumienia, a kiedy trzeba zatrzymać się od razu.'
            ),
            'D-26b' => $this->serviceEntry(
                $sign,
                'myjnia',
                'To typowo usługowa informacja o obiekcie przy drodze. Nie wpływa na pierwszeństwo ani na tor jazdy, ale pozwala zaplanować zjazd do wybranego punktu obsługi auta.',
                'Znak ustawia się przy stacjach paliwowych, obiektach serwisowych i innych miejscach, gdzie kierowca może skorzystać z myjni.',
                'Jeżeli chcesz zjechać, przygotuj manewr wcześniej. Jeżeli nie, jedź płynnie dalej; nie warto reagować nerwowo na samą informację o dostępnej usłudze.',
                'Typowym błędem jest impulsywny zjazd pod wpływem samego znaku albo zatrzymanie się przy krawędzi jezdni w oczekiwaniu na wjazd do obiektu.',
                'To dobry kandydat do krótkiego klastra „usługi dla auta” razem z D-23, D-26 i D-26a.'
            ),
            'D-26c' => $this->serviceEntry(
                $sign,
                'toaleta publiczna',
                'Znak ma przede wszystkim funkcję porządkującą dla podróżnych i pomaga wcześniej zaplanować postój przy trasie lub w mieście.',
                'Najczęściej pojawia się przy MOP-ach, parkingach, punktach usługowych i w miejscowościach turystycznych, czyli tam, gdzie organizator ruchu chce wcześniej wskazać podróżnym dostępną infrastrukturę sanitarną.',
                'Jeżeli planujesz zatrzymanie, zrób to w miejscu dozwolonym i przygotuj zjazd wcześniej. Sama informacja o toalecie nie usprawiedliwia postoju na pasie ruchu ani przy zakazie zatrzymania.',
                'Najczęstszym błędem jest utożsamienie ważnej potrzeby pasażerów z możliwością zatrzymania „gdziekolwiek”, zamiast wykorzystania wskazanego obiektu zgodnie z organizacją ruchu.',
                'Warto później powiązać ten znak z D-18 i z materiałem o planowaniu postojów na dłuższej trasie.'
            ),
            'D-26d' => $this->serviceEntry(
                $sign,
                'natrysk',
                'To informacja usługowa typowa dla obiektów podróżnych, kempingowych i większych punktów obsługi kierowców.',
                'Znak pojawia się tam, gdzie przy trasie lub w obiekcie towarzyszącym drodze dostępna jest infrastruktura sanitarna z prysznicami.',
                'Traktuj znak planistycznie, zwłaszcza na dłuższej trasie. Jeżeli chcesz skorzystać z obiektu, przygotuj wjazd bez gwałtownego hamowania i sprawdź zasady postoju na miejscu.',
                'Błąd polega zwykle na zbyt późnej decyzji o zjeździe albo na założeniu, że skoro obiekt oferuje natrysk, to można zatrzymać pojazd w dowolnym miejscu w jego otoczeniu.',
                'Dobrze połączyć tę stronę z D-30 do D-33, gdzie podobne usługi często występują razem.'
            ),
            'D-27' => $this->serviceEntry(
                $sign,
                'bufet lub kawiarnia',
                'Pomaga kierowcy zaplanować krótki postój regeneracyjny bez improwizowania zjazdu przy samym obiekcie.',
                'Znak pojawia się na trasach turystycznych, drogach przelotowych, MOP-ach oraz przy punktach usług dla podróżnych.',
                'Jeżeli planujesz postój, przygotuj się do zjazdu zawczasu. Informacja o gastronomii nie usprawiedliwia gwałtownej zmiany pasa ani zatrzymania się przy krawędzi jezdni.',
                'Najczęstszym błędem jest podejmowanie decyzji o postoju dopiero po minięciu znaku i próba nerwowego zjazdu do obiektu.',
                'Warto trzymać znak razem z D-28 i D-29 w klastrze usług dla podróżnych.'
            ),
            'D-28' => $this->serviceEntry(
                $sign,
                'restauracja',
                'To znak usługowy dla kierowcy planującego dłuższy postój. Ma dać czas na spokojne przygotowanie wjazdu do obiektu, a nie wywoływać nagłą reakcję.',
                'Znak ustawia się przy drogach tranzytowych, zjazdach do obiektów gastronomicznych i w strefach podróżnych, gdzie usługa jest istotna dla osób jadących w trasę.',
                'Czytaj go jako zapowiedź usługi, nie jako sygnał do ostrego hamowania. Jeżeli chcesz zjechać, ustaw się wcześniej; jeśli nie, jedź dalej płynnie i przewidywalnie.',
                'Najczęstszym błędem jest impulsowe reagowanie na znak i próba gwałtownego dojazdu do restauracji po zbyt późnym zauważeniu wjazdu.',
                'Dobrze będzie zestawić stronę z D-27 i D-29, aby opisać różne typy postoju dla kierowcy.'
            ),
            'D-29' => $this->serviceEntry(
                $sign,
                'hotel lub motel',
                'To ważna informacja dla kierowców planujących nocleg albo dłuższy postój na trasie. Znak nie służy do bieżącej organizacji pierwszeństwa, tylko do spokojnego planowania zjazdu.',
                'Najczęściej pojawia się przy drogach tranzytowych, dojazdach do obiektów noclegowych i w rejonach turystycznych.',
                'Jeżeli potrzebujesz noclegu, potraktuj znak jako informację o dostępnej funkcji obiektu i zjazd zaplanuj odpowiednio wcześnie. Nie ścinaj pasa ani nie zatrzymuj się przy wlocie tylko po to, by sprawdzić ofertę.',
                'Typowym błędem jest zbyt późne hamowanie albo zajmowanie niedozwolonego miejsca w pobliżu obiektu, bo kierowca dopiero przy samym budynku szuka wjazdu lub recepcji.',
                'Warto połączyć znak z D-30 do D-33 jako klastrem noclegowo-biwakowym.'
            ),
            'D-30' => $this->serviceEntry(
                $sign,
                'obozowisko lub kemping',
                'Znak informuje o miejscu przeznaczonym do postoju i noclegu w formule kempingowej, co jest istotne zwłaszcza dla kierowców kamperów i osób podróżujących z przyczepą.',
                'Najczęściej ustawia się go w rejonach turystycznych, na dojazdach do pól kempingowych i przy trasach obsługujących ruch wakacyjny.',
                'Jeżeli planujesz nocleg, potraktuj znak jako wskazówkę do spokojnego zaplanowania zjazdu i postoju. Sam symbol nie oznacza jeszcze, że możesz zatrzymać pojazd w dowolnym miejscu poza terenem obiektu.',
                'Najczęstszym błędem jest utożsamienie znaku z prawem do biwakowania przy samej drodze albo zbyt późna decyzja o zjeździe z przyczepą na trudny wlot.',
                'Na tej stronie warto potem dopisać różnicę między D-30, D-31 i D-32.'
            ),
            'D-31' => $this->serviceEntry(
                $sign,
                'kemping z podłączeniami elektrycznymi do przyczep',
                'To doprecyzowana informacja dla podróżnych korzystających z przyczep kempingowych i kamperów, którym zależy na pełniejszej infrastrukturze postoju.',
                'Znak pojawia się głównie na trasach turystycznych i w rejonach, gdzie działa infrastruktura noclegowa dla przyczep z dostępem do energii elektrycznej.',
                'Jeżeli podróżujesz z przyczepą, czytaj znak jako informację techniczno-logistyczną i planuj zjazd spokojnie, bo zestaw pojazdów wymaga większego wyprzedzenia decyzji niż auto osobowe.',
                'Typowym błędem jest traktowanie znaku tak samo jak zwykłego pola namiotowego, bez uwzględnienia, że wskazuje on bardziej specjalistyczną infrastrukturę dla zestawu pojazdów.',
                'Dobrze będzie wyjaśnić różnicę między kempingiem z mediami a zwykłym polem biwakowym.'
            ),
            'D-32' => $this->serviceEntry(
                $sign,
                'pole biwakowe',
                'Znak informuje o prostszej formie postoju i noclegu niż klasyczny kemping. Pomaga kierowcy zaplanować zjazd do miejsca przeznaczonego do biwakowania.',
                'Najczęściej występuje w rejonach turystycznych, nad wodą, w górach i przy drogach prowadzących do miejsc wypoczynku sezonowego.',
                'Czytaj znak jako informację o funkcji miejsca, a nie o zasadach zatrzymania na poboczu. Jeżeli chcesz z niego skorzystać, zjedź zgodnie z organizacją ruchu i dojeżdżaj dopiero do właściwego obiektu.',
                'Najczęstszym błędem jest założenie, że skoro w pobliżu jest pole biwakowe, to można zatrzymać się lub biwakować poza wskazanym miejscem, byle obok drogi.',
                'Warto trzymać ten znak blisko D-30 i D-31, bo użytkownicy często mylą te trzy oznaczenia.'
            ),
            'D-33' => $this->serviceEntry(
                $sign,
                'schronisko młodzieżowe',
                'Pomaga wcześniej odnaleźć miejsce noclegowe przeznaczone dla podróżnych i grup zorganizowanych, bez chaotycznego szukania adresu w ostatniej chwili.',
                'Znak pojawia się na dojazdach do obiektów noclegowych tego typu, zwykle w miastach turystycznych i miejscowościach o funkcji wypoczynkowej.',
                'Jeżeli zamierzasz zjechać, przygotuj manewr wcześniej i nie traktuj informacji o schronisku jako powodu do nagłego zatrzymania się przy krawędzi jezdni.',
                'Najczęściej kierowcy reagują za późno i dopiero po minięciu znaku próbują gwałtownie zawrócić albo szukać dojazdu inną ulicą.',
                'Dobrze będzie połączyć ten opis z D-29 i D-30-D-32 w jednym klastrze noclegowym.'
            ),
            'D-34' => $this->serviceEntry(
                $sign,
                'punkt informacji turystycznej',
                'Jego zadaniem jest pomóc kierowcy w spokojnym zaplanowaniu postoju lub zjazdu do miejsca, w którym uzyska informacje o regionie, trasach i obiektach.',
                'Znak ustawia się w miejscowościach turystycznych, na dojazdach do centrów miast, przy parkingach oraz w rejonach o zwiększonym ruchu odwiedzających.',
                'Jeżeli chcesz skorzystać z punktu, ustaw się do zjazdu wcześniej. Jeśli nie, potraktuj znak jako informację orientacyjną i nie reaguj na nią gwałtownym hamowaniem lub zmianą pasa.',
                'Typowy błąd to decyzja o zjeździe podjęta za późno, gdy wlot do punktu informacji jest już tuż obok pojazdu.',
                'Warto połączyć stronę z D-34a i D-34b jako trzema różnymi formami informacji dla kierowcy.'
            ),
            'D-34a' => $this->serviceEntry(
                $sign,
                'informację radiową o ruchu drogowym',
                'Znak wskazuje kierowcy możliwość skorzystania z przekazu radiowego o aktualnej sytuacji na drodze, objazdach lub utrudnieniach.',
                'Najczęściej pojawia się na drogach tranzytowych i w rejonach, gdzie zarządca drogi chce zachęcić kierowców do śledzenia bieżących komunikatów.',
                'Potraktuj znak jako wsparcie planowania trasy, a nie jako powód do manipulowania radiem w najtrudniejszym momencie jazdy. Bezpieczniej ustawić stację wcześniej albo skorzystać z komunikatów w chwili, gdy sytuacja na drodze jest stabilna.',
                'Najczęstszy błąd to próba strojenia radia i szukania częstotliwości w momencie, gdy uwaga powinna być skupiona na manewrze lub trudnym odcinku trasy.',
                'Na tej stronie dobrze będzie dopisać, że znak wspiera decyzje nawigacyjne, ale nie zastępuje obserwacji bieżącego oznakowania.'
            ),
            'D-34b' => $this->entry(
                'Znak D-34b Zbiorcza tablica informacyjna łączy kilka usług lub funkcji dostępnych w jednym miejscu albo w tym samym kierunku. Zamiast pojedynczego obiektu pokazuje zestaw informacji istotnych dla podróżnego.',
                'To znak oszczędzający miejsce i porządkujący przekaz. Kierowca powinien odczytać nie tylko sam fakt dostępności usług, ale też ich układ, kolejność i ewentualne odległości wskazane na tablicy.',
                'Tablicę stosuje się przy drogach tranzytowych, MOP-ach, parkingach i zjazdach do kompleksów usługowych, gdzie obok siebie znajduje się kilka funkcji ważnych dla podróżnych.',
                'Najważniejsze jest czytanie całości z wyprzedzeniem. Nie skupiaj się na pierwszym piktogramie, który przyciągnie wzrok, tylko oceń cały zestaw usług i dopiero wtedy zdecyduj, czy warto zjechać.',
                'D-34b nie daje żadnych szczególnych uprawnień na drodze, ale organizuje informacje o infrastrukturze usługowej. W praktyce trzeba czytać go razem z odległościami, kierunkiem zjazdu i dalszym oznakowaniem pasa.',
                'Największe ryzyko pojawia się przy spóźnionym odczytaniu tablicy. Kierowca zauważa interesującą usługę w ostatniej chwili i wtedy podejmuje nerwowy manewr lub hamowanie.',
                'Najczęstszy błąd polega na wybiórczym czytaniu tablicy. Kierowca widzi jeden symbol, reaguje odruchowo i nie analizuje całego zestawu informacji ani bezpiecznej możliwości dojazdu.',
                'Znak grupuje kilka usług drogowych w jednym przekazie, dzięki czemu można spokojnie zdecydować, czy i do którego obiektu warto zjechać.',
                'Najczęściej problemem jest spóźnione albo wybiórcze czytanie tablicy, które prowadzi do niepotrzebnych, gwałtownych decyzji przy samym zjeździe.',
                'Warto tę stronę uzupełnić o przykłady poprawnego czytania tablic zbiorczych na MOP-ach i przy większych parkingach.'
            ),
            'D-35' => $this->entry(
                'Znak D-35 Przejście podziemne dla pieszych informuje o istnieniu podziemnej relacji przejścia dla pieszych, czyli przeprowadzenia ruchu pieszego poza poziomem jezdni.',
                'Dla kierowcy to sygnał, że w tym miejscu piesi mają zorganizowaną infrastrukturę odseparowaną od jezdni. Nie oznacza to jednak, że przy wlotach i wejściach do przejścia piesi przestają być istotnym elementem otoczenia.',
                'Znak ustawia się przy wejściach do przejść podziemnych i w ich pobliżu, zwykle na większych arteriach, gdzie ruch pieszy ma być oddzielony od ruchu samochodowego.',
                'Czytaj znak szeroko. Jezdnia może być odciążona od bezpośredniego przechodzenia w poziomie, ale w pobliżu wejść do przejścia nadal trzeba uważać na pieszych zbliżających się do schodów, wind lub wyjść.',
                'D-35 opisuje rodzaj infrastruktury pieszej, a nie zwalnia z ostrożności wobec pieszych w okolicy. Nadal trzeba uwzględniać inne znaki, sygnalizację i realne zachowanie uczestników ruchu w sąsiedztwie obiektu.',
                'Mandat rzadko wiąże się z minięciem samego znaku. Ryzyko bierze się z błędnego założenia, że skoro piesi mają przejście podziemne, to można całkowicie przestać ich szukać w otoczeniu drogi.',
                'Najczęstszym błędem jest odczytanie znaku jako gwarancji braku pieszych przy jezdni. W praktyce wejścia i wyjścia z przejścia nadal generują ruch pieszy w sąsiedztwie ulicy.',
                'Znak wskazuje, że piesi mogą przejść pod jezdnią, czyli bez bezpośredniego przecinania ruchu w poziomie pasa samochodowego.',
                'Błąd polega zwykle na nadmiernym rozluźnieniu obserwacji i założeniu, że w pobliżu wejść do przejścia nie trzeba już spodziewać się pieszych.',
                'Warto później dodać porównanie D-35 z D-36 oraz z D-35a i D-36a.'
            ),
            'D-35a' => $this->entry(
                'Znak D-35a Schody ruchome w dół informuje, że dojście do przejścia dla pieszych lub innego ciągu pieszego odbywa się z wykorzystaniem ruchomych schodów prowadzących w dół.',
                'To detal infrastrukturalny ważny głównie dla orientacji pieszych, ale dla kierowcy także oznacza miejsce skupiające ruch przy wejściu do przejścia lub obiektu podziemnego.',
                'Znak ustawia się przy wejściach do przejść podziemnych, stacji i innych obiektów, gdzie ciąg pieszy prowadzi schodami ruchomymi w dół.',
                'Z punktu widzenia kierowcy nie chodzi o sam mechanizm schodów, lecz o to, że w pobliżu wejścia może kumulować się ruch pieszy. Przy dojazdach, zatokach i wlotach warto zachować spokojne tempo i szeroką obserwację chodnika.',
                'D-35a opisuje szczegół infrastruktury pieszej. Nie zmienia zasad pierwszeństwa na jezdni, ale pomaga zrozumieć, jak zorganizowany jest ruch pieszy w sąsiedztwie drogi.',
                'Ryzyko bierze się głównie z lekceważenia strefy wejścia do obiektu. Kierowca skupia się na jezdni i za późno zauważa pieszych gromadzących się przy zejściu.',
                'Najczęstszym błędem jest potraktowanie znaku jako całkowicie nieistotnego dla kierowcy. Tymczasem wejścia do przejść i stacji generują ruch pieszy, który trzeba uwzględniać przy manewrach w pobliżu.',
                'Znak pokazuje, że w tym miejscu ruch pieszy schodzi na niższy poziom schodami ruchomymi, co pomaga zrozumieć układ przejścia i punktów wejścia.',
                'Najczęściej problemem jest pominięcie praktycznej konsekwencji znaku: przy wejściu do obiektu nadal trzeba liczyć się z ruchem pieszych i obserwować okolice chodnika.',
                'Warto potraktować ten opis jako uzupełnienie D-35, a nie osobną, oderwaną informację.'
            ),
            'D-36' => $this->entry(
                'Znak D-36 Przejście nadziemne dla pieszych informuje o przeprowadzeniu ruchu pieszego ponad jezdnią, czyli po kładce lub innym obiekcie nad drogą.',
                'To sygnał o odseparowanej infrastrukturze pieszej. Dla kierowcy oznacza zwykle, że piesi nie powinni przecinać jezdni w poziomie, ale nadal mogą pojawiać się przy dojściach do kładki oraz w jej sąsiedztwie.',
                'Znak ustawia się przy wejściach do przejść nadziemnych i w pobliżu obiektów, które prowadzą pieszych ponad jezdnią o dużym natężeniu ruchu.',
                'Nie traktuj znaku jako usprawiedliwienia dla całkowitego odpuszczenia obserwacji pieszych. Szczególnie przy przystankach, windach i dojściach do kładki nadal trzeba uważać na osoby poruszające się w pobliżu jezdni.',
                'D-36 opisuje sposób organizacji ruchu pieszego ponad drogą. Sam fakt istnienia kładki nie odwołuje obowiązku ostrożnej jazdy w rejonie wejść, wyjść i punktów dojścia do obiektu.',
                'Błąd praktyczny polega na błędnym założeniu, że skoro jest kładka, piesi nie pojawią się już w żadnej relacji blisko drogi. To może prowadzić do spóźnionej reakcji przy manewrach przy krawędzi jezdni.',
                'Najczęstszym błędem jest całkowite wyłączenie pieszych z pola obserwacji tylko dlatego, że główne przejście odbywa się nad jezdnią.',
                'Znak informuje, że ruch pieszy jest poprowadzony nad drogą, czyli poza poziomem jezdni, choć w sąsiedztwie obiektu nadal mogą pojawiać się piesi.',
                'Błąd polega zwykle na zbyt daleko idącym wniosku, że okolica kładki nie wymaga już żadnej dodatkowej obserwacji pod pieszych.',
                'Warto zestawić znak z D-35 i pokazać oba sposoby separacji ruchu pieszego od jezdni.'
            ),
            'D-36a' => $this->entry(
                'Znak D-36a Schody ruchome w górę informuje o dojściu do przejścia nadziemnego lub innego ciągu pieszego schodami ruchomymi prowadzącymi ku górze.',
                'To znak infrastrukturalny, który dla kierowcy ma znaczenie pośrednie: wskazuje miejsce wejścia do obiektu pieszo-komunikacyjnego, a więc strefę, w której piesi będą się gromadzić i zmieniać kierunek ruchu.',
                'Znak ustawia się przy wejściach na kładki, do stacji i przy obiektach, gdzie pieszy musi wejść na wyższy poziom schodami ruchomymi.',
                'W pobliżu takich wejść zwracaj uwagę na pieszych zmierzających do schodów lub z nich wychodzących. Sama informacja o schodach ruchomych nie zmienia zasad pierwszeństwa, ale pomaga lepiej rozumieć układ ruchu przy krawędzi jezdni.',
                'D-36a opisuje szczegół organizacji infrastruktury pieszej. Dla kierowcy znaczenie znaku jest wtórne, ale użyteczne: pozwala przewidzieć miejsca większej koncentracji pieszych przy dojściu do przejścia nadziemnego.',
                'Ryzyko pojawia się wtedy, gdy kierowca całkowicie ignoruje wejście do obiektu i skupia się wyłącznie na pasie przed autem. To może oznaczać spóźnioną reakcję przy manewrach przy zatoce, przystanku lub dojeździe.',
                'Najczęstszym błędem jest uznanie znaku za całkowicie nieistotny dla kierującego. Tymczasem wejście na kładkę lub stację nadal organizuje ruch pieszy w bezpośrednim otoczeniu jezdni.',
                'Znak pokazuje, że dojście dla pieszych odbywa się schodami ruchomymi w górę, co pomaga rozumieć, gdzie skupia się ruch pieszy przy obiekcie.',
                'Najczęściej pomijana jest praktyczna konsekwencja tej informacji: w pobliżu wejścia do przejścia nadziemnego nadal trzeba obserwować pieszych przy chodniku i zatokach.',
                'Dobrze połączyć tę stronę z D-36 i z materiałem o bezpieczeństwie przy wejściach do obiektów pieszych.'
            ),
            'D-37' => $this->entry(
                'Znak D-37 Tunel informuje, że wjeżdżasz do odcinka zamkniętego obudową tunelową i objętego dodatkowymi rygorami bezpieczeństwa oraz inną charakterystyką obserwacji drogi.',
                'W tunelu zmienia się percepcja prędkości, widoczność, możliwość zatrzymania i sposób reagowania na awarie. Znak D-37 ma przygotować kierowcę na tę zmianę jeszcze przed wjazdem pod strop obiektu.',
                'Znak ustawia się bezpośrednio przed tunelami oraz przy wjazdach na odcinki, które formalnie rozpoczynają się jako tunel.',
                'Przed wjazdem uporządkuj prędkość, zwiększ koncentrację i czytaj bardzo uważnie dalsze oznakowanie oraz sygnały w tunelu. Nie zostawiaj żadnego manewru na ostatni moment, bo w zamkniętej przestrzeni margines błędu jest dużo mniejszy.',
                'D-37 oznacza początek tunelu, a wraz z nim wejście w strefę o szczególnych zasadach bezpieczeństwa. Na takich odcinkach trzeba zwracać większą uwagę na zakazy zatrzymania, awarie, oświetlenie pojazdu i komunikaty zarządcy drogi.',
                'Największe ryzyko błędu dotyczy pozostawienia sobie za mało dystansu i zbyt późnego reagowania na zdarzenie w tunelu. W zamkniętej przestrzeni każdy nieprzemyślany manewr może szybko eskalować w poważne zagrożenie.',
                'Najczęstszym błędem jest wejście do tunelu bez zmiany stylu jazdy: za mały odstęp, za słaba obserwacja sygnałów i przekonanie, że to tylko zwykły fragment drogi z dachem nad głową.',
                'Znak uprzedza, że zaczyna się tunel i od tego momentu trzeba prowadzić pojazd z większą dyscypliną obserwacji oraz z myślą o szczególnych zasadach bezpieczeństwa.',
                'Błąd wynika zwykle z niedocenienia specyfiki tunelu: mniejszej swobody manewru, większego znaczenia sygnałów i ograniczonej przestrzeni na reakcję awaryjną.',
                'Warto później połączyć tę stronę z D-38 i z materiałem o zachowaniu kierowcy w tunelu podczas awarii lub pożaru.'
            ),
            'D-38' => $this->entry(
                'Znak D-38 Koniec tunelu informuje, że kończy się odcinek o tunelowym reżimie bezpieczeństwa i kierowca wyjeżdża z zamkniętej przestrzeni do zwykłej relacji drogowej.',
                'To moment zmiany warunków percepcji. Po wyjeździe z tunelu mogą gwałtownie zmienić się natężenie światła, widoczność pobocza i sposób odczytywania dalszego oznakowania.',
                'Znak ustawia się przy wyjeździe z tunelu albo tuż za jego końcem, aby formalnie odwołać wcześniejszy status odcinka.',
                'Nie przyspieszaj automatycznie tylko dlatego, że tunel się kończy. Najpierw daj sobie moment na odczytanie nowego otoczenia, dostosowanie wzroku i sprawdzenie dalszych znaków oraz geometrii drogi.',
                'D-38 odwołuje status tunelu i kończy szczególną relację infrastrukturalną tego odcinka. Dalej obowiązują już zasady wynikające z nowego typu drogi i kolejnego oznakowania.',
                'Ryzyko pojawia się wtedy, gdy kierowca natychmiast po wyjeździe traci dyscyplinę jazdy: zbyt mocno przyspiesza, za szybko zmienia pas albo za późno zauważa dalsze ograniczenia ustawione zaraz za tunelem.',
                'Najczęstszy błąd to potraktowanie końca tunelu jako sygnału pełnego rozluźnienia, mimo że właśnie w strefie przejściowej często trzeba szybko odczytać nowe warunki oświetlenia i oznakowanie.',
                'Znak informuje, że od tego miejsca tunel formalnie się kończy i dalszą drogę trzeba czytać już według normalnego układu odcinka za wyjazdem.',
                'Błąd wynika zwykle z natychmiastowego rozluźnienia po wyjeździe z tunelu i zbyt późnego zauważenia nowych ograniczeń lub zmiany geometrii drogi.',
                'Warto zestawić znak z D-37 oraz z materiałem o efekcie adaptacji wzroku po wyjeździe z tunelu.'
            ),
            'D-40' => $this->entry(
                'Znak D-40 Strefa zamieszkania oznacza wjazd do obszaru o bardzo szczególnej ochronie pieszych i uspokojonym ruchu. To jeden z najważniejszych znaków strefowych, bo zmienia kilka kluczowych zasad jednocześnie.',
                'Po minięciu znaku pieszy może korzystać z całej szerokości drogi i ma pierwszeństwo przed pojazdem, a kierowca co do zasady musi poruszać się z prędkością do 20 km/h i parkować wyłącznie w miejscach wyznaczonych.',
                'Znak stawia się przy wjazdach na osiedla, strefy wewnętrzne zabudowy mieszkaniowej oraz inne obszary, w których ruch samochodów ma być podporządkowany bezpieczeństwu mieszkańców i pieszych.',
                'Po wjeździe natychmiast zmień styl jazdy: zwolnij, patrz szeroko pod dzieci, pieszych i rowerzystów, nie oczekuj klasycznego podziału na chodnik i jezdnię oraz nie parkuj poza miejscami wyznaczonymi.',
                'D-40 wprowadza strefę zamieszkania, a więc cały pakiet szczególnych zasad obowiązujących na całym obszarze aż do znaku D-41. To nie znak punktowy, lecz strefowy: po skręcie w kolejną uliczkę nadal pozostajesz w tej samej strefie.',
                'Najczęstsze sankcje wynikają z przekraczania 20 km/h, nieustąpienia pierwszeństwa pieszym korzystającym z całej szerokości drogi oraz z parkowania poza wyznaczonymi miejscami. To właśnie te trzy skutki znaku najczęściej bywają ignorowane.',
                'Najczęstszym błędem jest potraktowanie D-40 jak „ładnej tablicy osiedlowej”, a nie jak znaku, który realnie zmienia pierwszeństwo pieszych, dopuszczalną prędkość i zasady postoju.',
                'Znak oznacza wjazd do strefy zamieszkania, w której piesi są szczególnie chronieni, kierowca jedzie bardzo wolno, a parkowanie jest dozwolone tylko w miejscach wyznaczonych.',
                'Błąd najczęściej polega na przeoczeniu strefowego charakteru znaku: kierowca pamięta o nim przez chwilę, a potem po pierwszym skręcie wraca do zwykłych nawyków jazdy i postoju.',
                'Ta strona powinna później dostać osobny blok „3 skutki D-40”, bo to jeden z najczęściej mylonych znaków na egzaminie.'
            ),
            'D-41' => $this->entry(
                'Znak D-41 Koniec strefy zamieszkania odwołuje szczególne zasady obowiązujące na obszarze objętym wcześniej znakiem D-40. To moment wyjścia z ruchu uspokojonego do zwykłej organizacji drogi.',
                'Po minięciu D-41 przestają działać reguły właściwe dla strefy zamieszkania, ale nie oznacza to pełnej dowolności. Trzeba od razu czytać nowe oznakowanie, lokalne ograniczenia i zwykłe zasady wynikające z typu dalszej drogi.',
                'Znak ustawia się przy wyjazdach z osiedli i innych obszarów, w których wcześniej obowiązywał szczególny reżim ochrony pieszych oraz ograniczonej prędkości.',
                'Nie przyspieszaj odruchowo w chwili minięcia znaku. Najpierw upewnij się, jaki charakter ma dalsza droga, czy nie wjeżdżasz od razu na skrzyżowanie i jakie zasady obowiązują poza strefą.',
                'D-41 kończy strefę zamieszkania, czyli odwołuje jej strefowy zestaw zasad. Od tego miejsca kierowca wraca do reguł wynikających z dalszego oznakowania oraz z przepisów ogólnych dla danej drogi.',
                'Ryzyko błędu wynika z nadmiernie gwałtownego „odpuszczenia” po wyjeździe ze strefy. Kierowca zbyt szybko zwiększa tempo jazdy i za późno zauważa nowe wloty, przejścia albo inne ograniczenia.',
                'Najczęstszym błędem jest traktowanie D-41 jak pozwolenia na natychmiastowy powrót do szybkiej jazdy, bez sprawdzenia, czy za wyjazdem nie obowiązują inne lokalne ograniczenia.',
                'Znak oznacza, że skończyła się strefa zamieszkania i od tego miejsca nie obowiązuje już specjalny reżim D-40.',
                'Błąd zwykle polega na zbyt gwałtownym przejściu do zwykłej jazdy bez ponownego odczytania dalszego oznakowania za granicą strefy.',
                'Warto połączyć opis z D-40 i wyraźnie pokazać, które reguły kończą się razem z D-41.'
            ),
            'D-42' => $this->entry(
                'Znak D-42 Obszar zabudowany oznacza wjazd do strefy zabudowy, w której zmienia się domyślne otoczenie ruchu, a wraz z nim zasady prowadzenia pojazdu i ogólne ograniczenia prędkości.',
                'Dla kierowcy to sygnał, że od tego miejsca trzeba spodziewać się większej liczby pieszych, skrzyżowań, przystanków i zabudowy przy drodze. Co do zasady obowiązuje tu także limit 50 km/h, jeśli inne znaki nie stanowią inaczej.',
                'Znak stawia się na wjazdach do miejscowości lub do zwartej zabudowy, w której ruch drogowy zaczyna przebiegać w sąsiedztwie budynków i większej aktywności pieszej.',
                'Po minięciu znaku od razu zresetuj tempo jazdy i sposób obserwacji. Szukaj przejść, pieszych, rowerzystów i bocznych wlotów wcześniej niż poza terenem zabudowanym, bo to właśnie ten kontekst opisuje D-42.',
                'D-42 wprowadza obszar zabudowany w rozumieniu zasad ruchu. To znak strefowy, dlatego jego skutki trwają do D-43, a nie tylko do najbliższego skrzyżowania. Ograniczenie prędkości i sposób obserwacji drogi trzeba odczytywać w tej logice.',
                'Mandaty najczęściej wiążą się tu z przekraczaniem prędkości, ale sam znak ma szersze znaczenie: przypomina o zwiększonym ryzyku kontaktu z pieszymi i o konieczności spokojniejszej, bardziej miejskiej jazdy.',
                'Najczęstszym błędem jest redukowanie znaczenia D-42 wyłącznie do „tablicy z limitem”. Tymczasem znak opisuje całe środowisko ruchu, a nie tylko jedną cyfrę prędkości.',
                'Znak oznacza wjazd do obszaru zabudowanego, czyli strefy o miejskim lub miejscowościowym charakterze ruchu, gdzie co do zasady obowiązuje 50 km/h, o ile inne znaki nie wskazują inaczej.',
                'Kierowcy często pamiętają o samej prędkości, ale zapominają, że po D-42 trzeba też dużo wcześniej czytać pieszych, przystanki, skrzyżowania i gęstą zabudowę przy jezdni.',
                'Ta strona powinna później dostać wyraźne porównanie D-42 z D-40 i D-52, bo użytkownicy często mylą zakres tych stref.'
            ),
            'D-43' => $this->entry(
                'Znak D-43 Koniec obszaru zabudowanego odwołuje zasady związane z wcześniejszym wjazdem do terenu zabudowanego. Od tego miejsca kierowca opuszcza strefę opisaną znakiem D-42.',
                'To nie tylko informacja o zmianie tablicy miejscowościowej, ale o zmianie środowiska ruchu. Po znaku zwykle maleje gęstość zabudowy i liczba punktów kolizyjnych, choć nadal trzeba czytać dalsze ograniczenia oraz geometrię drogi.',
                'Znak ustawia się na wyjazdach z miejscowości i tam, gdzie kończy się zwarta zabudowa uzasadniająca wcześniejszy status obszaru zabudowanego.',
                'Nie przyspieszaj automatycznie w chwili minięcia znaku. Najpierw sprawdź, czy za wyjazdem nie ma kolejnego ograniczenia, przejścia, skrzyżowania albo innej lokalnej przyczyny do zachowania ostrożności.',
                'D-43 odwołuje obszar zabudowany jako strefę obowiązywania wcześniejszych reguł. Dalej stosuje się już zasady wynikające z nowego typu drogi i z dalszego oznakowania, a nie domyślnie te z D-42.',
                'Ryzyko błędu polega na gwałtownym zwiększeniu prędkości zaraz za znakiem bez sprawdzenia, czy dalsza droga rzeczywiście pozwala na bezpieczne przyspieszenie i czy nie ma kolejnych ograniczeń.',
                'Najczęstszym błędem jest traktowanie D-43 jako natychmiastowego przyzwolenia na mocne przyspieszenie, zamiast jako informacji o końcu strefy, po której nadal trzeba odczytać nowe warunki drogi.',
                'Znak informuje, że kończy się obszar zabudowany i od tego miejsca przestają działać zasady powiązane ze strefą D-42.',
                'Najczęściej kierowca zbyt szybko wraca do szybszej jazdy i nie sprawdza, jakie znaki lub ograniczenia stoją bezpośrednio za końcem obszaru zabudowanego.',
                'Dobrze pokazać ten znak w parze z D-42 i z przykładami dróg wylotowych z miejscowości.'
            ),
            'D-44' => $this->entry(
                'Znak D-44 Strefa płatnego parkowania oznacza wjazd do obszaru, w którym postój jest odpłatny według lokalnych zasad ustalonych dla całej strefy.',
                'To znak strefowy, a więc jego działanie nie kończy się na pierwszym skrzyżowaniu. Kierowca powinien od razu założyć, że opłata i zasady postoju obowiązują na całym obszarze aż do znaku D-45, chyba że organizacja strefy stanowi inaczej.',
                'Znak ustawia się przy wjazdach do śródmieść, dzielnic usługowych i innych obszarów, gdzie gmina lub zarządca drogi organizuje płatny postój w formie strefowej.',
                'Po wjeździe nie szukaj już „kolejnego potwierdzenia” po każdym skrzyżowaniu. Zamiast tego sprawdź lokalne zasady opłat, godziny obowiązywania i sposób wniesienia opłaty, a potem zaparkuj zgodnie z wyznaczonymi miejscami.',
                'D-44 wprowadza strefę płatnego parkowania jako obszar obowiązywania określonych zasad postoju. Trzeba go czytać razem z informacjami o godzinach, abonamentach, wyjątkach i sposobie poboru opłaty, ale sam zakres strefy trwa do D-45.',
                'Najczęstsze problemy wynikają z nieopłacenia postoju albo z błędnego założenia, że po skręcie w boczną ulicę strefa już nie obowiązuje. To właśnie strefowy charakter znaku generuje najwięcej pomyłek.',
                'Najczęstszy błąd polega na myleniu D-44 ze zwykłą tablicą parkingową. Kierowca skupia się na miejscu postoju, ale nie rozumie, że wjechał na cały obszar objęty opłatą.',
                'Znak oznacza wjazd do strefy płatnego parkowania, więc opłaty i zasady postoju działają na całym obszarze aż do znaku kończącego strefę.',
                'Błąd najczęściej wynika z zapomnienia o strefowym charakterze znaku i z założenia, że po najbliższym skrzyżowaniu trzeba będzie dopiero szukać kolejnego potwierdzenia opłat.',
                'Ta strona powinna później dostać prosty blok „czy D-44 działa po skrzyżowaniu?”, bo to jedno z najczęstszych pytań użytkowników.'
            ),
            'D-45' => $this->entry(
                'Znak D-45 Koniec strefy płatnego parkowania odwołuje wcześniejszy obszar objęty opłatami za postój. Od tego miejsca kończy się strefowy reżim wprowadzony znakiem D-44.',
                'To ważna granica organizacyjna, ale nie oznacza automatycznie pełnej dowolności parkowania. Po znaku trzeba jeszcze sprawdzić, czy na dalszym odcinku nie obowiązują inne lokalne zasady postoju, zakazy albo kolejne strefy.',
                'Znak ustawia się przy wyjazdach z obszaru płatnego parkowania, na granicy strefy miejskiej lub dzielnicowej objętej opłatami.',
                'Nie zakładaj automatycznie, że za D-45 możesz zostawić auto w dowolnym miejscu. Najpierw sprawdź dalsze znaki, sposób wyznaczenia stanowisk oraz ewentualne zakazy zatrzymania lub postoju.',
                'D-45 kończy strefę płatnego parkowania, czyli odwołuje obszarowe zasady opłat wynikające z D-44. Dalszą relację postoju trzeba oceniać już według kolejnego oznakowania i lokalnej organizacji ruchu.',
                'Błędy praktyczne wynikają głównie z mylnego wniosku, że koniec strefy oznacza koniec wszystkich zasad dotyczących postoju. Kierowca nie czyta dalszej ulicy i wpada w inny zakaz lub ograniczenie.',
                'Najczęstszy błąd to utożsamienie końca strefy z pełną zgodą na dowolne parkowanie, bez sprawdzenia, jakie reguły zaczynają obowiązywać zaraz za znakiem.',
                'Znak oznacza, że od tego miejsca kończy się strefa płatnego parkowania i nie obowiązuje już obszarowa opłata wynikająca z D-44.',
                'Najczęściej kierowca słusznie rozpoznaje koniec opłat, ale błędnie zakłada, że oznacza to również brak innych zasad postoju na dalszej ulicy.',
                'Warto pokazać D-45 w parze z D-44 i wyjaśnić użytkownikowi, jak czytać granice strefy po obu stronach ulicy.'
            ),
            'D-46' => $this->entry(
                'Znak D-46 Droga wewnętrzna oznacza wjazd na odcinek niebędący zwykłą drogą publiczną, lecz drogą o wewnętrznym charakterze, np. osiedlowym, zakładowym albo parkingowym.',
                'Dla kierowcy to ważny sygnał zmiany kontekstu. Na drodze wewnętrznej częściej spotkasz lokalne regulaminy, oznakowanie podporządkowane funkcji terenu oraz sytuacje, w których wyjazd na drogę publiczną jest traktowany jak włączanie się do ruchu.',
                'Znak ustawia się przy wjazdach na tereny osiedli, zakładów, centrów handlowych, parkingów i innych obszarów, które mają własną organizację ruchu wewnętrznego.',
                'Po minięciu znaku jedź ostrożniej i czytaj teren jak przestrzeń lokalną, a nie przelotową. Zwracaj uwagę na pieszych, strefy załadunku, ruch manewrowy oraz na to, jak będziesz później wyjeżdżać z takiego obszaru na drogę publiczną.',
                'D-46 opisuje status drogi jako wewnętrznej. Nie znosi to konieczności stosowania się do ustawionych znaków, ale przypomina, że sposób organizacji ruchu może być bardziej lokalny i podporządkowany funkcji terenu.',
                'Błędy najczęściej pojawiają się przy wyjeździe z drogi wewnętrznej, gdy kierowca zachowuje się tak, jakby nadal poruszał się zwykłą drogą publiczną, albo gdy ignoruje pieszych i ruch manewrowy charakterystyczny dla parkingów i osiedli.',
                'Najczęstszy błąd polega na potraktowaniu drogi wewnętrznej jak zwykłej ulicy przelotowej: zbyt szybka jazda, słaba obserwacja pieszych i nieprawidłowe zachowanie przy włączaniu się do ruchu na wyjeździe.',
                'Znak informuje, że wjeżdżasz na drogę wewnętrzną, czyli teren o bardziej lokalnej organizacji ruchu niż zwykła droga publiczna.',
                'Najczęściej problem wynika z niezauważenia, że po D-46 zmienia się sposób czytania otoczenia i szczególne znaczenie ma późniejszy wyjazd na drogę publiczną.',
                'Na tej stronie warto dodać potem prosty blok o relacji D-46 do D-52 i do znaku wyjazdu ze strefy ruchu.'
            ),
            'D-47' => $this->entry(
                'Znak D-47 Koniec drogi wewnętrznej informuje, że kończy się odcinek o wewnętrznym statusie i kierowca opuszcza teren, na którym ruch miał lokalny, wewnętrzny charakter.',
                'To ważny moment zwłaszcza przed wyjazdem na drogę publiczną. Kierowca powinien od razu myśleć o ponownym odczytaniu układu pierwszeństwa i o tym, że kończy się lokalna logika organizacji parkingu, osiedla lub terenu zakładu.',
                'Znak ustawia się przy wyjazdach z terenów oznaczonych wcześniej jako droga wewnętrzna, zwłaszcza na końcu osiedli, parkingów, stref handlowych i dojazdów do obiektów o lokalnej organizacji ruchu.',
                'Po minięciu znaku nie kontynuuj jazdy „na pamięć”. Sprawdź bardzo dokładnie dalsze oznakowanie, relację z drogą publiczną i sposób bezpiecznego włączenia się w nowy strumień ruchu.',
                'D-47 kończy status drogi wewnętrznej. Od tego miejsca kierowca wraca do relacji ruchu określonej przez dalszą drogę i jej oznakowanie, a nie przez wcześniejszy teren lokalny.',
                'Najwięcej problemów praktycznych pojawia się na samym wyjeździe z obszaru. Kierowca zbyt automatycznie opuszcza teren i za późno ocenia pierwszeństwo oraz ruch na drodze, na którą wjeżdża.',
                'Najczęstszym błędem jest przeoczenie, że po końcu drogi wewnętrznej trzeba ponownie czytać pierwszeństwo i sposób włączenia się do ruchu, zamiast wyjeżdżać tak, jakby cały czas była to zwykła ulica.',
                'Znak informuje, że kończy się droga wewnętrzna i dalszy wyjazd trzeba ocenić według oznakowania oraz zasad obowiązujących na nowej drodze.',
                'Najczęściej kierowca niedostatecznie przygotowuje się do wyjazdu z terenu lokalnego i za szybko wjeżdża w relację z drogą publiczną.',
                'Warto zestawić D-47 z D-46 i pokazać typowe błędy popełniane przy wyjeździe z osiedla lub parkingu.'
            ),
            'D-48' => $this->entry(
                'Znak D-48 Zmiana pierwszeństwa na skrzyżowaniu równorzędnym uprzedza, że dotychczasowa relacja pierwszeństwa zmienia się przed najbliższym skrzyżowaniem i trzeba wrócić do logiki skrzyżowania równorzędnego.',
                'To znak przejściowy: ma wyrwać kierowcę z nawyku jazdy drogą uprzywilejowaną. Pokazuje, że za chwilę nie wystarczy jechać „jak po głównej”, tylko trzeba od nowa ocenić relacje z prawej i lewej strony zgodnie z zasadami skrzyżowania równorzędnego.',
                'Znak stawia się przed skrzyżowaniami, na których droga mająca wcześniej szczególny status przechodzi w zwykłą relację równorzędną.',
                'Po zobaczeniu znaku zwolnij mentalnie i dosłownie. Najbliższe skrzyżowanie wymaga aktywnej obserwacji oraz porzucenia założenia, że Twoja droga nadal ma uprzywilejowany przebieg.',
                'D-48 nie daje pierwszeństwa ani go nie odbiera sam w sobie na całym odcinku, lecz uprzedza o zmianie relacji właśnie na najbliższym skrzyżowaniu. Znak ma zapobiec jeździe na pamięć po wcześniejszej drodze głównej.',
                'Ryzyko jest bardzo praktyczne: kierowca rozpoznaje znak za późno albo lekceważy go i wjeżdża na skrzyżowanie z nastawieniem „dalej jadę główną”, co może skutkować wymuszeniem pierwszeństwa.',
                'Najczęstszy błąd polega na zignorowaniu przejściowego charakteru znaku. Kierowca wie, że coś „zmienia się z pierwszeństwem”, ale nie wyciąga z tego wniosku, że najbliższy wlot trzeba czytać jak skrzyżowanie równorzędne.',
                'Znak uprzedza, że przy najbliższym skrzyżowaniu kończy się dotychczasowa relacja pierwszeństwa i trzeba zastosować reguły właściwe dla skrzyżowania równorzędnego.',
                'Błąd najczęściej wynika z jazdy na pamięć po wcześniejszej drodze głównej i zbyt późnego przestawienia się na nowe zasady pierwszeństwa.',
                'Warto dołożyć schemat pokazujący kierowcy, że znak nie działa „na zawsze”, lecz uprzedza konkretną zmianę przy najbliższym skrzyżowaniu.'
            ),
            'D-48a' => $this->entry(
                'Znak D-48a Zmiana pierwszeństwa uprzedza, że dotychczasowy układ pierwszeństwa na najbliższym skrzyżowaniu zmieni się i trzeba bardzo uważnie odczytać nową relację przejazdu.',
                'To znak szczególnie ważny tam, gdzie kierowca mógłby jechać rutynowo według starego układu drogi głównej. Jego sens polega na wyrwaniu z tego automatyzmu i przygotowaniu do innego rozłożenia pierwszeństwa na skrzyżowaniu.',
                'Znak ustawia się przed skrzyżowaniami, na których przebieg drogi z pierwszeństwem zmienia się albo gdzie kierowca nie może polegać na dotychczasowym, prostym założeniu co do uprzywilejowania własnej relacji.',
                'Po zobaczeniu znaku patrz nie tylko na jezdnię przed sobą, ale na cały układ skrzyżowania i znaki towarzyszące. To miejsce, w którym trzeba porzucić jazdę „na pamięć” i świadomie odczytać nowy przebieg pierwszeństwa.',
                'D-48a uprzedza o zmianie pierwszeństwa na najbliższym skrzyżowaniu. W praktyce trzeba go zawsze czytać razem z geometrią skrzyżowania oraz z oznakowaniem, które pokazuje, jak dokładnie rozkłada się relacja drogi głównej i podporządkowanej.',
                'Największe ryzyko polega na wymuszeniu pierwszeństwa z powodu rutyny. Kierowca zna trasę, ale nie zauważa lub bagatelizuje fakt, że tu przebieg pierwszeństwa nie jest już intuicyjny ani taki sam jak wcześniej.',
                'Najczęstszym błędem jest patrzenie na znak powierzchownie i uznanie, że skoro wcześniej droga była główna, to nadal można wjechać pewnie. To właśnie D-48a ma ostrzec przed takim uproszczeniem.',
                'Znak informuje, że na najbliższym skrzyżowaniu zmieni się dotychczasowa relacja pierwszeństwa i trzeba dokładnie odczytać nowy układ przejazdu.',
                'Błąd zwykle wynika z jazdy na przyzwyczajeniu oraz z niedoczytania, jak naprawdę rozkłada się pierwszeństwo na nadchodzącym skrzyżowaniu.',
                'Warto uzupełnić stronę prostą grafiką pokazującą zmianę przebiegu drogi z pierwszeństwem.'
            ),
            'D-49' => $this->entry(
                'Znak D-49 Pobór opłat informuje o zbliżaniu się do miejsca, w którym przejazd będzie obsługiwany przez punkt opłat lub inną formę kontroli należności za korzystanie z odcinka drogi.',
                'To znak organizacyjny i planistyczny. Kierowca ma dzięki niemu czas, by wybrać właściwy pas, przygotować formę płatności i uniknąć gwałtownego hamowania przed bramkami lub stanowiskami poboru.',
                'Znak ustawia się przed placami poboru opłat, przed pasami selekcji oraz w miejscach, gdzie trzeba uprzedzić o zmianie układu ruchu na dojeździe do punktu poboru.',
                'Po zobaczeniu znaku wcześniej zwolnij i sprawdź oznaczenia pasów. Nie zmieniaj pasa w ostatniej chwili i nie próbuj improwizować wyboru bramki, bo punkt poboru opłat szybko porządkuje ruch w wąskie relacje.',
                'D-49 nie nakłada sam opłaty, ale uprzedza o odcinku lub miejscu, w którym trzeba podporządkować się organizacji poboru. Znak trzeba czytać razem z oznaczeniem pasów, systemu płatności i dalszym sterowaniem ruchem.',
                'Problemy praktyczne pojawiają się zwykle przy złym wyborze pasa albo zbyt późnym hamowaniu. To może prowadzić do niebezpiecznej zmiany toru jazdy i kolizji w strefie bramek lub stanowisk.',
                'Najczęstszym błędem jest zostawienie decyzji o wyborze pasa i formy płatności do ostatniej chwili, gdy inne pojazdy są już ustawione do właściwych bramek.',
                'Znak uprzedza, że przed Tobą znajduje się punkt lub strefa poboru opłat i trzeba wcześniej przygotować sposób przejazdu.',
                'Błąd najczęściej wynika ze spóźnionego wyboru pasa albo z nieprzygotowania płatności, co powoduje nerwowe manewry na dojeździe do bramek.',
                'Warto później dołożyć praktyczny moduł o czytaniu pasów manualnych, automatycznych i elektronicznych przy punktach poboru.'
            ),
            'D-50' => $this->entry(
                'Znak D-50 Zatoka informuje o zatoce drogowej, czyli poszerzeniu jezdni lub wydzielonym fragmencie służącym do czasowego wyłączenia pojazdu z głównego strumienia ruchu.',
                'To informacja o funkcji przestrzeni obok jezdni. Kierowca powinien rozumieć, że zatoka pozwala zatrzymać się bez blokowania pasa ruchu, ale nie daje automatycznie prawa do postoju sprzecznego z innymi znakami.',
                'Znak ustawia się przy zatokach postojowych, awaryjnych lub technicznych, gdzie organizator ruchu chce wyraźnie zaznaczyć obecność takiej przestrzeni obok zasadniczego toru jazdy.',
                'Jeżeli potrzebujesz zjechać z głównego strumienia ruchu, zatoka może być dobrym miejscem, ale nadal musisz sprawdzić, czy zatrzymanie lub postój są tam dozwolone. Sam fakt istnienia zatoki nie znosi innych ograniczeń.',
                'D-50 opisuje element infrastruktury drogowej. Trzeba go czytać razem z zakazami zatrzymania, postoju albo dodatkowymi informacjami o przeznaczeniu zatoki, bo jej funkcja może być różna.',
                'Największe ryzyko błędu polega na uznaniu, że skoro jest zatoka, to wolno w niej zatrzymać się w każdej sytuacji. To może prowadzić do postoju w miejscu przeznaczonym wyłącznie dla określonej funkcji albo do utrudniania ruchu.',
                'Najczęstszy błąd to utożsamienie zatoki z ogólnodostępnym parkingiem. Kierowca nie czyta innych znaków i traktuje każde poszerzenie jezdni jako swobodne miejsce postoju.',
                'Znak informuje o zatoce, czyli wydzielonej przestrzeni obok jezdni, która może służyć do wyłączenia pojazdu z głównego ruchu zgodnie z jej przeznaczeniem.',
                'Błąd wynika zwykle z pominięcia dalszego oznakowania i z błędnego założenia, że każda zatoka pozwala zatrzymać się lub zaparkować bez żadnych warunków.',
                'Warto później dołożyć przykłady różnych rodzajów zatok: postojowych, autobusowych i technicznych.'
            ),
            'D-51' => $this->entry(
                'Znak D-51 Automatyczna kontrola prędkości uprzedza, że na odcinku lub w punkcie drogi działa urządzenie rejestrujące prędkość pojazdu.',
                'Jego rola jest szersza niż sama „informacja o fotoradarze”. Ma zmienić zachowanie kierowcy już przed punktem kontroli: uporządkować prędkość, ograniczyć jazdę na pamięć i wymusić realne dostosowanie tempa do obowiązujących znaków.',
                'Znak ustawia się przed miejscami kontroli prędkości, na odcinkach o podwyższonym ryzyku przekroczeń oraz tam, gdzie zarządca drogi chce wyraźnie uprzedzić o automatycznej rejestracji.',
                'Po zobaczeniu znaku nie „hamuj do samego urządzenia”, tylko spokojnie dostosuj prędkość już do obowiązującego limitu na całym dojeździe. Najlepiej potraktować go jako przypomnienie, że masz jechać zgodnie z przepisami, a nie zgodnie z chwilową widocznością kamery.',
                'D-51 informuje o automatycznej kontroli prędkości, ale obowiązek jazdy z odpowiednią prędkością wynika nadal z ogólnych limitów i znaków ograniczenia. Znak nie tworzy osobnego limitu, lecz zapowiada egzekwowanie już istniejących zasad.',
                'Najczęstszą konsekwencją jest mandat za przekroczenie prędkości, ale problemem bywa też sama reakcja kierowców: nagłe, spóźnione hamowanie przed urządzeniem i przyspieszanie zaraz za nim, mimo że warunki ruchu się nie zmieniły.',
                'Najczęstszy błąd to traktowanie znaku jak punktu „zwolnij na chwilę”. Kierowca reaguje skokowo zamiast po prostu utrzymywać prawidłową prędkość na całym odcinku.',
                'Znak uprzedza o automatycznej kontroli prędkości i przypomina, że trzeba utrzymywać prędkość zgodną z obowiązującym limitem, a nie dopiero zwalniać przy samym urządzeniu.',
                'Błąd zwykle polega na gwałtownym hamowaniu pod kamerę albo na natychmiastowym przyspieszaniu po jej minięciu bez refleksji nad realnym limitem i bezpieczeństwem.',
                'Dobrze połączyć stronę z D-51a i D-51b, aby wyjaśnić różnicę między kontrolą punktową a odcinkową.'
            ),
            'D-51a' => $this->entry(
                'Znak D-51a Automatyczna kontrola średniej prędkości oznacza początek odcinka, na którym mierzona będzie średnia prędkość przejazdu pomiędzy punktami kontrolnymi.',
                'To znak strefowy dla określonego odcinka, a nie pojedynczego punktu. Kierowca powinien po jego minięciu myśleć o równym, zgodnym z limitem tempie jazdy na całej długości kontroli, a nie o jednorazowym hamowaniu pod kamerę.',
                'Znak ustawia się na początku odcinków objętych odcinkowym pomiarem średniej prędkości, zwykle w miejscach, gdzie problemem jest długotrwałe utrzymywanie zbyt wysokiego tempa jazdy.',
                'Po zobaczeniu znaku ustaw prędkość na poziomie zgodnym z limitem i utrzymuj ją spokojnie przez cały odcinek. Najgorszą strategią jest naprzemienne ostre hamowanie i przyspieszanie między punktami pomiaru.',
                'D-51a rozpoczyna odcinek objęty pomiarem średniej prędkości. Nie tworzy osobnego limitu, ale uprzedza o sposobie egzekwowania limitów obowiązujących na całej długości oznaczonego fragmentu drogi.',
                'Najczęstsza sankcja to mandat za przekroczenie średniej prędkości, ale w praktyce równie niebezpieczne są gwałtowne wahania tempa jazdy wywołane niezrozumieniem, jak działa odcinkowy pomiar.',
                'Najczęstszym błędem jest traktowanie D-51a tak samo jak zwykłego fotoradaru. Kierowca zwalnia tylko przy znaku lub przy pierwszej kamerze, a potem wraca do zbyt wysokiej prędkości.',
                'Znak oznacza początek odcinkowej kontroli średniej prędkości i przypomina, że prawidłowe tempo trzeba utrzymać na całym wyznaczonym fragmencie drogi.',
                'Błąd wynika zwykle z nieporozumienia co do mechanizmu pomiaru: kierowca reaguje punktowo, choć kontrola dotyczy całego odcinka między urządzeniami.',
                'Warto dodać w przyszłości prostą grafikę wyjaśniającą, jak obliczana jest średnia prędkość na odcinku.'
            ),
            'D-51b' => $this->entry(
                'Znak D-51b Koniec automatycznej kontroli średniej prędkości odwołuje wcześniejszy odcinek objęty pomiarem odcinkowym. To granica kończąca kontrolę średniej prędkości, a nie automatycznie wszystkie inne ograniczenia na drodze.',
                'Po minięciu znaku pomiar odcinkowy już się nie ciągnie, ale kierowca nadal musi czytać zwykłe limity i warunki drogowe. D-51b kończy sposób kontroli, nie zawsze kończy też ograniczenie prędkości.',
                'Znak ustawia się na końcu odcinka objętego pomiarem średniej prędkości, zwykle po drugim punkcie rejestracji lub w miejscu formalnego zakończenia tej strefy kontroli.',
                'Nie przyspieszaj automatycznie po minięciu znaku. Najpierw sprawdź, jaki limit obowiązuje dalej i czy charakter drogi rzeczywiście się zmienia. Zakończenie pomiaru nie jest samo w sobie zgodą na szybszą jazdę.',
                'D-51b kończy odcinkowy pomiar średniej prędkości jako sposób egzekwowania przepisów. Dalej obowiązują już zwykłe limity wynikające z innych znaków i z przepisów ogólnych.',
                'Ryzyko błędu polega na odruchowym przyspieszaniu zaraz po końcu pomiaru, mimo że droga nadal może mieć ten sam limit. Wtedy kierowca wpada w kolejne naruszenie, już poza samym odcinkowym systemem kontroli.',
                'Najczęstszy błąd to potraktowanie znaku jako odpowiednika końca ograniczenia prędkości. To dwa różne komunikaty: D-51b kończy kontrolę średniej, ale nie musi kończyć limitu jazdy.',
                'Znak informuje, że od tego miejsca kończy się odcinkowy pomiar średniej prędkości, ale dalszy limit nadal trzeba odczytać z oznakowania drogi.',
                'Najczęściej kierowcy poprawnie rozumieją koniec kontroli, ale błędnie uznają, że jednocześnie mogą bez sprawdzenia zwiększyć prędkość.',
                'Dobrze połączyć tę stronę z D-51a i wyraźnie odróżnić koniec kontroli od końca ograniczenia prędkości.'
            ),
            'D-52' => $this->entry(
                'Znak D-52 Strefa ruchu oznacza wjazd do obszaru, w którym stosuje się przepisy ruchu drogowego tak jak na drodze publicznej, mimo że teren może mieć charakter wewnętrzny lub prywatny.',
                'To bardzo praktyczny znak dla parkingów, osiedli, centrów handlowych i podobnych miejsc. Po jego minięciu kierowca nie może tłumaczyć wykroczeń tym, że „to tylko teren prywatny”, bo właśnie znak przywraca pełne znaczenie zasad ruchu.',
                'Znak ustawia się przy wjazdach na parkingi wielkopowierzchniowe, tereny handlowe, osiedla i inne obszary, gdzie zarządca chce, by obowiązywały klasyczne reguły ruchu drogowego.',
                'Po wjeździe czytaj teren dokładnie tak jak zwykłą drogę: zwracaj uwagę na pierwszeństwo, pieszych, znaki, prędkość i sposób parkowania. Strefa ruchu nie jest „luźnym” parkingiem, tylko obszarem z pełną odpowiedzialnością za zachowanie na drodze.',
                'D-52 wprowadza strefę ruchu, czyli obszar, w którym stosuje się przepisy ruchu drogowego. To znak strefowy i działa na całym obszarze aż do D-53, niezależnie od skrętów wewnątrz terenu.',
                'Najczęstsze sankcje wynikają z błędnego założenia, że na takim terenie przepisy nie działają. Kierowcy lekceważą pierwszeństwo, oznakowanie lub zasady postoju, a potem odpowiadają tak jak za naruszenia na drodze publicznej.',
                'Najczęstszy błąd polega na traktowaniu strefy ruchu jak zwykłego placu parkingowego bez konsekwencji prawnych. To właśnie D-52 ma przeciąć taki sposób myślenia.',
                'Znak oznacza wjazd do strefy ruchu, czyli obszaru, na którym przepisy ruchu drogowego działają tak jak na zwykłej drodze.',
                'Błąd najczęściej wynika z przekonania, że skoro teren jest prywatny lub parkingowy, to znaki i zasady pierwszeństwa mają tam tylko „umowne” znaczenie.',
                'Ta strona powinna później dostać porównanie D-52 z D-46 i D-40, bo użytkownicy regularnie mylą te trzy strefy.'
            ),
            'D-53' => $this->entry(
                'Znak D-53 Koniec strefy ruchu odwołuje wcześniejszy obszar, na którym przepisy ruchu drogowego działały jak na drodze publicznej na podstawie znaku D-52.',
                'Po minięciu znaku kończy się właśnie ten strefowy status. Kierowca powinien jednak nadal uważnie czytać teren, bo dalsza organizacja może wynikać z lokalnych reguł, znaku D-46 albo z bezpośredniego wyjazdu na drogę publiczną.',
                'Znak ustawia się przy wyjazdach ze stref ruchu, najczęściej na parkingach, terenach handlowych i osiedlach, gdzie wcześniej wprowadzono pełne stosowanie przepisów ruchu drogowego.',
                'Nie wyjeżdżaj automatycznie „na pamięć”. Koniec strefy ruchu zwykle oznacza też zmianę statusu terenu, więc trzeba dokładnie ocenić dalsze pierwszeństwo, charakter wyjazdu i sposób włączenia się do ruchu.',
                'D-53 kończy strefę ruchu, a więc odwołuje obszarowe działanie D-52. Dalszy status odcinka trzeba ustalić na podstawie kolejnych znaków i funkcji terenu, na który kierowca właśnie wjeżdża lub z którego wyjeżdża.',
                'Najwięcej błędów zdarza się na wyjeździe z takiego obszaru. Kierowca zbyt szybko zakłada, że wszystko działa jak na zwykłej drodze albo odwrotnie: nadal nie czyta poważnie oznakowania po opuszczeniu strefy.',
                'Najczęstszy błąd to brak ponownej analizy otoczenia po minięciu znaku. Kierowca wie, że strefa się skończyła, ale nie sprawdza, jaki status i jakie zasady obowiązują od kolejnego metra.',
                'Znak informuje, że kończy się strefa ruchu i od tego miejsca dalszą relację drogi trzeba ocenić według nowego statusu terenu i dalszego oznakowania.',
                'Błąd zwykle polega na wyjeździe z obszaru bez uważnego sprawdzenia, czy kierowca włącza się do ruchu, wraca na drogę publiczną czy pozostaje na innej drodze wewnętrznej.',
                'Warto zestawić znak z D-52 i D-46, bo to właśnie na granicach tych obszarów kierowcy popełniają najwięcej pomyłek.'
            ),
            'D-54' => $this->entry(
                'Znak D-54 Strefa czystego transportu oznacza wjazd do obszaru, w którym ruch części pojazdów może być ograniczony zgodnie z lokalnymi zasadami dotyczącymi emisji i wyjątków.',
                'To znak strefowy o istotnym znaczeniu planistycznym. Kierowca powinien od razu sprawdzić, czy jego pojazd może legalnie wjechać do strefy oraz czy nie obowiązują dodatkowe warunki, identyfikatory lub wyjątki wskazane przez organizatora.',
                'Znak ustawia się na wjazdach do miejskich obszarów objętych polityką czystego transportu, zwykle tam, gdzie ograniczenie wjazdu ma poprawić jakość powietrza i uporządkować ruch.',
                'Po zobaczeniu znaku nie zakładaj, że dotyczy on tylko „jakichś innych aut”. Jeżeli nie masz pewności co do uprawnień swojego pojazdu, sprawdź zasady strefy przed wjazdem, a nie dopiero po fakcie.',
                'D-54 wprowadza strefę czystego transportu jako obszar obowiązywania lokalnych ograniczeń wjazdu. Skutki znaku trwają do D-55, a szczegółowe warunki mogą wynikać z uchwały, tablic uzupełniających lub lokalnego systemu identyfikacji pojazdów.',
                'Najpoważniejsze konsekwencje wynikają z nieuprawnionego wjazdu do strefy. Problem praktyczny zaczyna się jednak wcześniej: wielu kierowców nie sprawdza, czy ich pojazd spełnia warunki i traktuje znak jako mało ważną informację miejską.',
                'Najczęstszy błąd polega na zlekceważeniu strefowego charakteru znaku oraz na założeniu, że jeśli nikt nie zatrzyma kierowcy na wjeździe, to ograniczenia i tak można pominąć.',
                'Znak oznacza wjazd do strefy czystego transportu, w której mogą obowiązywać ograniczenia dla pojazdów niespełniających określonych warunków emisyjnych lub lokalnych wymagań.',
                'Błąd wynika zwykle z braku wcześniejszego sprawdzenia, czy dany pojazd w ogóle może legalnie poruszać się po tej strefie i na jakich warunkach.',
                'Na tej stronie warto potem dodać bardzo praktyczny blok o tym, że D-54 działa obszarowo aż do D-55.'
            ),
            'D-55' => $this->entry(
                'Znak D-55 Koniec strefy czystego transportu odwołuje wcześniejszy obszar ograniczeń środowiskowych wprowadzony znakiem D-54.',
                'Po minięciu znaku kończy się strefowy reżim wjazdu związany z czystym transportem, ale nie oznacza to automatycznie końca wszystkich innych ograniczeń ruchu obowiązujących na dalszej drodze.',
                'Znak ustawia się przy wyjazdach ze stref czystego transportu, na granicy obszaru objętego lokalną polityką ograniczania wjazdu części pojazdów.',
                'Nie czytaj go jak sygnału do gwałtownej zmiany stylu jazdy. Po prostu przyjmij, że skończył się szczególny reżim strefy i od tego miejsca trzeba znów sprawdzić zwykłe oznakowanie oraz status dalszej drogi.',
                'D-55 kończy obszar strefy czystego transportu. To znak strefowy, dlatego dopiero po jego minięciu wygasają ograniczenia wjazdu wynikające z D-54.',
                'Błąd praktyczny pojawia się wtedy, gdy kierowca myli koniec strefy środowiskowej z końcem wszystkich reguł lokalnych i przestaje czytać dalsze oznakowanie zaraz za wyjazdem.',
                'Najczęstszym błędem jest poprawne rozpoznanie końca strefy, ale błędne założenie, że po wyjeździe nie trzeba już analizować żadnych innych ograniczeń, zakazów czy relacji pierwszeństwa.',
                'Znak oznacza, że od tego miejsca kończy się strefa czystego transportu i przestają działać ograniczenia strefowe wynikające z D-54.',
                'Błąd zwykle wynika z nadmiernego uproszczenia: kierowca uznaje, że po końcu strefy wszystko znowu działa „bez żadnych warunków”, choć dalsza droga może mieć własne zasady.',
                'Warto trzymać ten opis w parze z D-54 oraz z krótkim FAQ o granicach strefy i wyjątkach.'
            ),
            default => throw new \InvalidArgumentException("Missing informational content for [{$sign['code']} {$sign['name']}]"),
        };
    }

    /**
     * @return array{intro: string, meaning: string, placement: string, behavior: string, legal: string, fine: string, mistake: string, faq_duty: string, faq_mistake: string, editorial_notes: string}
     */
    protected function entry(
        string $intro,
        string $meaning,
        string $placement,
        string $behavior,
        string $legal,
        string $fine,
        string $mistake,
        string $faqDuty,
        string $faqMistake,
        string $editorialNotes,
    ): array {
        return [
            'intro' => $intro,
            'meaning' => $meaning,
            'placement' => $placement,
            'behavior' => $behavior,
            'legal' => $legal,
            'fine' => $fine,
            'mistake' => $mistake,
            'faq_duty' => $faqDuty,
            'faq_mistake' => $faqMistake,
            'editorial_notes' => $editorialNotes,
        ];
    }

    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array{intro: string, meaning: string, placement: string, behavior: string, legal: string, fine: string, mistake: string, faq_duty: string, faq_mistake: string, editorial_notes: string}
     */
    protected function parkingEntry(
        array $sign,
        string $meaning,
        string $placement,
        string $behavior,
        string $mistake,
        string $editorialNotes,
    ): array {
        return $this->entry(
            "Znak {$sign['code']} {$sign['name']} {$meaning}. Dla kierowcy nie jest to tylko prosty symbol miejsca postoju, ale konkretna wskazówka, że z tej przestrzeni wolno korzystać wyłącznie zgodnie z dodatkowymi warunkami odczytanymi z tabliczek, wyznaczenia stanowisk i lokalnej organizacji ruchu.",
            'To znak opisujący funkcję miejsca przy drodze. Kierowca powinien odczytać go razem z tabliczkami, oznakowaniem poziomym i lokalnymi zasadami postoju, bo dopiero cały zestaw mówi, jak realnie wolno korzystać z przestrzeni postojowej.',
            $placement,
            $behavior,
            'Znaki z rodziny parkingowej nie zawsze dają samodzielną odpowiedź, czy można stanąć w dowolny sposób i na dowolny czas. Trzeba je łączyć z innymi znakami, tabliczkami oraz z wyznaczeniem stanowisk.',
            'Problemy praktyczne przy tych znakach wynikają głównie z błędnego korzystania z miejsca: postoju bez uprawnienia, bez opłaty albo wbrew lokalnej organizacji stanowisk. Sam znak zwykle nie tworzy wykroczenia bez kontekstu, ale uruchamia potrzebę sprawdzenia warunków korzystania z miejsca.',
            $mistake,
            "Znak {$sign['code']} informuje o sposobie lub przeznaczeniu miejsca postojowego i trzeba go czytać razem z dodatkowymi warunkami ustawionymi przy stanowisku.",
            $mistake,
            $editorialNotes
        );
    }

    /**
     * @param  array{code: string, slug: string, name: string, primary_query: string}  $sign
     * @return array{intro: string, meaning: string, placement: string, behavior: string, legal: string, fine: string, mistake: string, faq_duty: string, faq_mistake: string, editorial_notes: string}
     */
    protected function serviceEntry(
        array $sign,
        string $serviceLabel,
        string $meaning,
        string $placement,
        string $behavior,
        string $mistake,
        string $editorialNotes,
    ): array {
        return $this->entry(
            "Znak {$sign['code']} {$sign['name']} informuje o usłudze lub obiekcie: {$serviceLabel}. Jego rola polega na tym, by kierowca mógł spokojnie zaplanować zjazd, postój albo dalszą logistykę podróży z wyprzedzeniem, zamiast reagować gwałtownie dopiero przy samym wlocie do obiektu.",
            $meaning.' Dla kierowcy najważniejsze jest to, że taki znak ma charakter planistyczny: pomaga wcześniej uporządkować decyzję o zjeździe, ale nie daje podstawy do nerwowego hamowania, nagłej zmiany pasa ani zatrzymania w miejscu niedozwolonym.',
            $placement,
            $behavior,
            'To znak usługowy. Sam z siebie nie daje pierwszeństwa, nie zmienia dozwolonej prędkości i nie tworzy prawa do zatrzymania się w dowolnym miejscu. Jego rolą jest wskazanie dostępnej funkcji drogowej lub obiektu widocznego z trasy.',
            'Mandat rzadko wynika z minięcia samego znaku usługowego. Ryzyko pojawia się wtedy, gdy kierowca reaguje na informację impulsywnie: gwałtownie hamuje, przecina pasy, zatrzymuje się w niedozwolonym miejscu albo źle ocenia wlot do obiektu.',
            $mistake.' W praktyce taki błąd niemal zawsze zaczyna się od spóźnionej decyzji i od potraktowania znaku usługowego jak sygnału do natychmiastowego manewru, zamiast jako spokojnej wskazówki do wcześniejszego zaplanowania zjazdu.',
            "Znak {$sign['code']} podpowiada, że z tej trasy można dojechać do obiektu lub usługi: {$serviceLabel}, więc warto wcześniej zaplanować zjazd albo postój.",
            $mistake,
            $editorialNotes
        );
    }

    protected function assetPath(string $slug): string
    {
        return 'traffic-signs/signs/informational/znak-'.$slug.'.webp';
    }
}

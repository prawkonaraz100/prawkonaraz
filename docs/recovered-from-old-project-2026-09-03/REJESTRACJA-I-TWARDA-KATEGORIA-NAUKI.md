# Rejestracja i Twarda Kategoria Nauki

## Cel

Ten dokument porządkuje decyzję produktową, żeby nowy użytkownik już na etapie rejestracji wybierał jedną kategorię prawa jazdy i uczył się wyłącznie w jej ramach.

Ma odpowiedzieć na sześć pytań:

1. co dziś mamy już w rejestracji,
2. jak dziś działa wybór kategorii w aplikacji,
3. które części bazy i produktu zależą od kategorii użytkownika,
4. jaki kierunek wdrożenia jest najbezpieczniejszy przy twardej blokadzie,
5. jak uporządkować dostęp do produktu, role, moderatorów i płatności,
6. jak podzielić wdrożenie na sprinty i weryfikować postęp developmentu.

## Decyzja Produktowa

Założenie docelowe jest twarde:

- użytkownik przy rejestracji wybiera jedną kategorię prawa jazdy,
- ta kategoria staje się jego stałą kategorią nauki,
- w normalnym UI użytkownik nie może sam jej zmieniać,
- jeśli chce zmianę, kontaktuje się z administracją,
- administracja może przy takiej zmianie wyzerować dane nauki, jeśli to będzie najbezpieczniejsze dla spójności systemu.

Priorytetem nie jest zachowanie każdego historycznego postępu za wszelką cenę. Priorytetem jest prosty model produktu, brak chaosu między kategoriami i przewidywalne działanie serwisu.

Ta zasada dotyczy zwykłego kursanta. Nie powinna automatycznie obejmować kont wewnętrznych i operacyjnych.

## Dostęp Do Serwisu Po Rejestracji

Poza samą kategorią nauki musimy rozdzielić jeszcze drugi temat: prawo do korzystania z produktu.

Docelowa zasada biznesowa:

- użytkownik, który sam rejestruje konto, musi wykupić dostęp, żeby korzystać z serwisu,
- użytkownik dodany przez moderatora nie musi kupować dostępu,
- użytkownik dodany przez moderatora dostaje dostęp do produktu na 90 dni,
- moderator obsługuje takie konto end-to-end i sam nadaje użytkownikowi dane startowe do pierwszego logowania,
- jeśli moderator nie zna e-maila użytkownika, może utworzyć konto tymczasowe do przejęcia przez użytkownika przy pierwszym logowaniu,
- dostęp nadany przez moderatora jest pełnoprawnym dostępem do produktu, ale nie pochodzi z zakupu,
- ten wyjątek musi być jawnie zaplanowany w modelu danych i logice dostępu.

To jest krytyczne, bo `rola użytkownika`, `kategoria nauki` i `prawo do korzystania z produktu` to trzy różne osie systemu.

Nie wolno ich mieszać w jeden skrót typu:

- `is_admin`,
- `tier`,
- albo przypadkowy wyjątek w UI.

## Plan, Płatność I Prawo Dostępu To Nie To Samo

W tym obszarze trzeba pilnować rozdzielenia pojęć:

- `rola` odpowiada na pytanie, kim jest użytkownik w systemie,
- `kategoria` odpowiada na pytanie, w jakiej kategorii się uczy,
- `plan` albo `tier` odpowiada na pytanie, jaki wariant produktu mu przypisaliśmy,
- `prawo dostępu` odpowiada na pytanie, czy wolno mu realnie korzystać z nauki.

To rozróżnienie jest szczególnie ważne przy użytkownikach dodanych przez moderatora.

Taki użytkownik może:

- nie mieć zakupu,
- mieć aktywny dostęp do produktu,
- mieć przypisaną kategorię,
- mieć konto utworzone i skonfigurowane przez moderatora,
- przejąć konto tymczasowe przy pierwszym logowaniu i dopiero wtedy podać docelowy e-mail,
- nie być administratorem.

Dlatego moderatorowy wyjątek nie powinien być modelowany jako samo `premium`, samo `is_admin` ani samo `tier`.

## Docelowe Metody Uwierzytelniania

Docelowo produkt powinien wspierać trzy metody wejścia po stronie użytkownika:

- własna rejestracja i logowanie przez `e-mail + hasło`,
- logowanie i rejestracja przez `Google`,
- logowanie i rejestracja przez `Facebook`.

Dodatkowo pozostaje osobny flow operatorski:

- konto tworzone przez moderatora, jako konto pełne przez `e-mail + hasło` albo jako konto tymczasowe przez techniczny login wewnętrzny.

Ważne doprecyzowanie:

- na dziś nasz lokalny `login` to po prostu `adres e-mail`,
- jeśli moderator zna e-mail użytkownika, może od razu utworzyć pełne konto oparte o `e-mail + hasło`,
- jeśli moderator nie zna e-maila użytkownika, najbezpieczniejszy wariant to wygenerowane konto tymczasowe oparte o wewnętrzny techniczny adres e-mail, który pełni rolę tymczasowego loginu do momentu przejęcia konta przez użytkownika.

## Konto Tymczasowe Tworzone Przez Moderatora

To jest rekomendowany wariant dla sytuacji, w której moderator nie zna jeszcze prawdziwego e-maila użytkownika.

Założenia:

- moderator może utworzyć konto bez znajomości docelowego e-maila użytkownika,
- system generuje wewnętrzny techniczny adres e-mail tylko do obsługi tymczasowego logowania,
- moderator przekazuje użytkownikowi dane tymczasowe do pierwszego wejścia,
- użytkownik przy pierwszym logowaniu musi przejąć konto zanim trafi do produktu.

Najważniejsza zasada:

- konto tymczasowe nie jest jeszcze w pełni przejętym kontem użytkownika,
- to etap provisioningowy, a nie zakończony onboarding.

## Zasady Dla Social Loginu

Social login ma być metodą uwierzytelnienia, a nie osobnym źródłem uprawnień do produktu.

To oznacza:

- użytkownik rejestrujący się przez `Google` albo `Facebook` dalej jest zwykłym użytkownikiem self-service,
- taka rejestracja nie omija zakupu dostępu,
- taka rejestracja nie omija wyboru kategorii,
- taka rejestracja nie tworzy automatycznie wyjątku moderatorskiego,
- social login nie może psuć limitu `30` kont na moderatora.

Najważniejsza zasada architektoniczna:

- `sposób logowania` nie może decydować o `roli`, `kategorii` ani `prawie dostępu do produktu`.

## Social Login Nie Może Omijać Paywalla

To trzeba zapisać bardzo twardo:

- użytkownik, który sam wszedł przez `Google` albo `Facebook`, nadal powinien przejść przez normalne zasady dostępu do produktu,
- jeśli nie ma aktywnego prawa dostępu, po uwierzytelnieniu nie powinien trafiać do pełnej nauki,
- zamiast tego powinien trafić do dalszego onboardingu albo do paywalla, zależnie od stanu konta.

Krótko:

- `Google` i `Facebook` upraszczają logowanie,
- ale nie zastępują zakupu.

## Jeden Resolver Konta Po Callbacku

Ekran `Zaloguj się` i ekran `Załóż konto` mogą mieć osobne przyciski społecznościowe, ale backend nie powinien mieć dwóch niezależnych logik tworzenia użytkownika.

Powinniśmy mieć jeden wspólny resolver po callbacku OAuth, który zawsze robi te same kroki:

1. rozpoznaje, czy provider jest już podpięty do istniejącego konta,
2. jeśli nie, próbuje bezpiecznie dopasować konto po e-mailu,
3. jeśli nadal nie ma konta, tworzy nowe konto self-service,
4. po uwierzytelnieniu przepuszcza użytkownika przez jeden wspólny post-auth flow.

To jest ważne, bo inaczej bardzo łatwo zrobić osobne zachowanie dla:

- lokalnej rejestracji,
- Google,
- Facebooka,
- kont moderatorskich,

i zacząć produkować duplikaty, niespójne weryfikacje i różne ścieżki dostępu.

## Rekomendowany Post-Auth Flow

Po każdym skutecznym uwierzytelnieniu, niezależnie od metody, system powinien przechodzić przez jedną kolejność decyzji:

1. jeśli konto jest zablokowane, dostęp ma zostać odrzucony,
2. jeśli konto jest tymczasowe i nie zostało jeszcze przejęte, użytkownik trafia do obowiązkowego kroku przejęcia konta,
3. jeśli konto wymaga obowiązkowej zmiany hasła startowego, użytkownik trafia do kroku ustawienia własnego hasła,
4. jeśli konto nie spełnia zasad weryfikacji e-mail, użytkownik trafia do odpowiedniego kroku weryfikacyjnego,
5. jeśli konto nie ma jeszcze przypisanej kategorii, użytkownik trafia do kroku wyboru kategorii,
6. jeśli konto nie ma aktywnego prawa dostępu do produktu, użytkownik trafia do paywalla albo odpowiedniego ekranu dostępu,
7. dopiero po przejściu tych warstw użytkownik trafia do produktu.

Ta kolejność ma znaczenie, bo dzięki niej:

- social login nie ominie blokady kategorii,
- social login nie ominie paywalla,
- konto moderatorskie zachowa swój wyjątek dostępu,
- wszystkie metody logowania będą kończyć w tym samym, spójnym miejscu.

Ta sekcja jest kluczowa także dla paywalla:

- paywall nie powinien być osobnym przypadkiem tylko dla zwykłej rejestracji lokalnej,
- paywall ma być jedną z warstw wspólnego `post-auth flow`,
- każda metoda wejścia do systemu ma kończyć się tym samym resolverem stanu konta.

To oznacza, że produkt nie powinien pytać:

- `czy user wszedł z register czy login?`,
- `czy user wszedł lokalnie czy przez Google?`

Powinien pytać:

- `czy user może już wejść do produktu?`
- `jeśli nie, to który krok ma wykonać teraz?`

## Zasady Łączenia Kont

To jest najważniejsza część całego planu.

Docelowa zasada:

- jeden realny użytkownik powinien mieć jedno lokalne konto w naszej bazie,
- `Google`, `Facebook` i lokalne hasło mają być metodami wejścia do tego samego konta,
- nie wolno tworzyć duplikatu tylko dlatego, że użytkownik zmienił sposób logowania.

Najbezpieczniejsze reguły:

- jeśli provider jest już podpięty do konta, logowanie idzie po tym powiązaniu,
- jeśli provider nie jest jeszcze podpięty, można próbować dopasowania po znormalizowanym e-mailu,
- jeśli e-mail pasuje do istniejącego konta i polityka zaufania do providera na to pozwala, provider powinien zostać podpięty do istniejącego konta,
- jeśli e-mail jest już zajęty, nie wolno tworzyć drugiego konta,
- jeśli dopasowanie nie jest bezpieczne, system powinien zatrzymać auto-link i przejść do kontrolowanego flow ręcznego.

Najważniejsza zasada bezpieczeństwa:

- po `e-mailu` dopasowujemy tylko przy pierwszym linkowaniu,
- po późniejszym podpięciu głównym kluczem logowania społecznościowego powinno być już stabilne ID użytkownika po stronie providera, a nie sam e-mail.

## Moderator A Social Login

To jest krytyczny edge case dla naszego produktu.

Jeśli moderator utworzył użytkownikowi konto pełne albo konto tymczasowe, a użytkownik później kliknie `Zaloguj przez Google` albo `Zaloguj przez Facebook`, system nie może:

- utworzyć drugiego konta,
- skasować moderatorskiego dostępu 90-dniowego,
- wyzerować przypisanej kategorii,
- spalić kolejnego slotu z limitu `30` kont moderatora.

Docelowa reguła:

- jeśli social provider zwraca ten sam e-mail co konto utworzone przez moderatora i polityka zaufania na to pozwala, provider ma zostać podpięty do istniejącego konta moderatorskiego,
- konto ma zachować ten sam dostęp, tę samą kategorię i tę samą historię,
- samo podpięcie Google albo Facebooka nie jest nowym utworzeniem konta moderatorskiego.

Drugi ważny przypadek:

- jeśli konto moderatorskie zostało utworzone na inny e-mail niż ten, który wraca z Google albo Facebooka, system nie powinien robić automatycznego linkowania na siłę.
- jeśli konto moderatorskie jest jeszcze kontem tymczasowym z wewnętrznym technicznym e-mailem, social login nie powinien go automatycznie przejmować bez kontrolowanego flow.

W takim przypadku potrzebny jest kontrolowany flow:

- użytkownik loguje się na istniejące konto lokalnie,
- jeśli konto jest tymczasowe, najpierw przejmuje konto przez podanie prawdziwego e-maila i ustawienie własnego hasła,
- potem dopina provider ręcznie,
- albo taki przypadek obsługuje moderator lub support.

## Ręczne Podpinanie I Odpinanie Providera

Żeby nie opierać wszystkiego wyłącznie na automatycznym dopasowaniu po callbacku, warto od razu zaplanować także ręczne zarządzanie providerami.

Najbezpieczniejsze zasady:

- użytkownik zalogowany lokalnie powinien docelowo móc ręcznie podpiąć `Google` albo `Facebook` do istniejącego konta,
- to jest szczególnie ważne, gdy konto moderatorskie ma inny e-mail niż konto u providera,
- odpinanie providera nie może zostawić użytkownika bez żadnej działającej metody logowania,
- jeśli konto nie ma ustawionego hasła lokalnego i ma tylko jednego providera, system nie powinien pozwolić odpiąć ostatniej metody wejścia bez wcześniejszego ustawienia hasła albo podpięcia innej metody.

## Polityka Zaufania Do Providera

Nie każdy provider i nie każdy callback powinien być traktowany tak samo.

Dokumentacyjnie trzeba przyjąć zasadę:

- system ma mieć jawną politykę zaufania do `Google` i `Facebooka`,
- system ma wiedzieć, czy e-mail zwrócony przez providera nadaje się do automatycznego utworzenia konta albo automatycznego linkowania,
- jeśli provider nie daje wystarczającego poziomu pewności, nie robimy automatycznego połączenia z istniejącym kontem.

To chroni nas przed cichym przejęciem konta przez błędne dopasowanie po e-mailu.

## Co Gdy Provider Nie Zwróci E-Maila

To jest edge case, którego nie wolno pominąć.

Jeśli `Google` albo `Facebook` nie zwróci e-maila:

- nie tworzymy automatycznie nowego konta,
- nie próbujemy zgadywać dopasowania,
- nie tworzymy konta „tymczasowego” bez jasnej tożsamości.

Najbezpieczniejszy kierunek:

- jeśli provider nie zwraca e-maila i nie ma już istniejącego linku po stabilnym ID providera, użytkownik trafia do kontrolowanego kroku uzupełnienia e-maila albo do bezpiecznego komunikatu, że logowanie tą metodą nie może zostać dokończone automatycznie.

## Social Login A Weryfikacja E-Mail

Social login musi być spójny z naszym obecnym flow `verify-email`.

Na dziś:

- konto lokalne po rejestracji trafia na `/verify-email`,
- lokalne logowanie działa po `e-mail + hasło`,
- użytkownik zablokowany jest odrzucany przy logowaniu.

Przy social loginie trzeba doprecyzować:

- kiedy provider jest na tyle zaufany, że możemy oznaczyć konto jako zweryfikowane,
- kiedy mimo logowania społecznościowego dalej wymagamy naszej lokalnej weryfikacji e-mail,
- że konto tymczasowe moderatorskie najpierw musi zostać przejęte i dopiero potem może wejść w normalny flow docelowej weryfikacji e-mail,
- że zablokowane konto ma zostać odrzucone niezależnie od metody logowania.

Najważniejsza zasada:

- social login nie może omijać blokady konta,
- a polityka weryfikacji e-mail musi być jawna i jednolita.

## Edge Case'y, Które Muszą Być Obsłużone

Poniższe przypadki muszą wejść do planu i do testów:

1. istniejące konto lokalne loguje się pierwszy raz przez `Google` z tym samym e-mailem i nie powstaje duplikat,
2. istniejące konto lokalne loguje się pierwszy raz przez `Facebook` z tym samym e-mailem i nie powstaje duplikat,
3. konto utworzone przez moderatora łączy się z `Google` albo `Facebookiem` bez utraty 90-dniowego dostępu,
4. podpięcie providera do konta moderatorskiego nie zużywa dodatkowego slotu z limitu `30`,
5. provider nie zwraca e-maila i system nie tworzy ślepego konta,
6. provider próbuje zalogować użytkownika do konta zablokowanego i dostęp zostaje odrzucony,
7. użytkownik bez przypisanej kategorii po social loginie trafia do kroku wyboru kategorii, a nie prosto do nauki,
8. użytkownik bez aktywnego prawa dostępu po social loginie trafia do paywalla, a nie do pełnej nauki,
9. użytkownik z wygasłym dostępem moderatorskim może się zalogować, ale nie powinien mieć pełnego wejścia do produktu bez dalszej decyzji systemu,
10. konto tymczasowe moderatorskie przy pierwszym logowaniu nie wpada od razu do produktu, tylko wymusza podanie prawdziwego e-maila i ustawienie nowego hasła,
11. użytkownik próbuje przejąć konto tymczasowe e-mailem, który już istnieje w systemie, i system nie robi cichego merge,
12. konto tymczasowe nie wysyła resetu hasła ani weryfikacji na techniczny wewnętrzny e-mail,
13. provider jest już podpięty do innego użytkownika i system nie pozwala na przejęcie konta,
14. e-mail providera zmienia się po czasie, ale istniejący link po stabilnym ID providera dalej prowadzi do właściwego konta,
15. callback OAuth uruchomi się drugi raz albo zostanie odświeżony i logika pozostanie idempotentna,
16. użytkownik zaczyna flow z `register`, ale po callbacku musi przejść przez te same reguły co po kliknięciu z `login`,
17. powrót z checkoutu po social rejestracji aktywuje właściwe istniejące konto, a nie tworzy kolejnego,
18. ręczne konto moderatorskie z innym e-mailem niż e-mail providera nie zostaje automatycznie połączone bez bezpiecznej ścieżki,
19. użytkownik nie może odpiąć ostatniej działającej metody logowania i zostać z kontem bez możliwości wejścia,
20. użytkownik ma jednocześnie aktywny zakup i aktywny grant moderatorski, a system poprawnie wybiera źródło wiodące bez utraty dostępu,
21. pełne konto utworzone przez moderatora nie wchodzi do produktu bez zmiany hasła startowego i bez weryfikacji e-mail,
22. moderator próbuje utworzyć konto na e-mail, który już istnieje, i system nie tworzy duplikatu,
23. nieprzejęte konto tymczasowe wygasa provisioningowo po `14 dniach` i zwalnia slot moderatora,
24. wygaśnięcie samego 90-dniowego dostępu nie zwalnia automatycznie slotu moderatora, jeśli konto nadal pozostaje przypisane do jego puli,
25. moderator nie może ponownie podejrzeć starego hasła startowego w panelu i musi wygenerować nowe,
26. po odebraniu roli moderatora wcześniej utworzone konta pozostają audytowalne i mogą zostać później przypisane innemu moderatorowi przez administratora.

## Telemetria I Audyt

To też warto zapisać od razu, bo później zwykle się o tym zapomina.

System powinien rozróżniać w logach i analityce:

- rejestrację lokalną,
- logowanie lokalne,
- logowanie przez `Google`,
- logowanie przez `Facebook`,
- konto utworzone przez moderatora,
- konto tymczasowe utworzone przez moderatora,
- przejęcie konta tymczasowego przez użytkownika,
- ręczne podpięcie providera do istniejącego konta.

To jest ważne dla:

- debugowania,
- bezpieczeństwa,
- analizowania skuteczności onboardingu,
- pilnowania limitów moderatorskich,
- rozwiązywania sporów o to, skąd wzięło się dane konto i jego dostęp.

## Wyjątki Od Blokady Kategorii

Twarda blokada jednej kategorii ma obowiązywać zwykłego użytkownika uczącego się do egzaminu. Nie powinna blokować pracy zespołu ani kont technicznych.

Docelowe wyjątki od blokady:

- `administrator` ma dostęp do wszystkich kategorii,
- `moderator` ma dostęp do wszystkich kategorii,
- `konto testowe` ma dostęp do wszystkich kategorii.

W praktyce oznacza to:

- kursant ma jedną stałą kategorię nauki,
- konta wewnętrzne mogą przełączać kategorię i testować różne flow,
- `target_category_id` dla kont wewnętrznych może pełnić rolę kategorii domyślnej albo ostatnio używanej, a nie twardej blokady.

To rozróżnienie jest ważne, bo `rola użytkownika` i `zakres dostępu do kategorii` nie są tym samym.

Docelowa zasada powinna więc brzmieć:

- kursant podlega blokadzie jednej kategorii,
- administrator, moderator i konto testowe są wyjątkami systemowymi od tej blokady.

## Stan Na Dziś

Obecna rejestracja działa już poprawnie jako flow konta, ale nie zbiera jeszcze kategorii nauki.

Backend:

- `app/Http/Controllers/Auth/RegisteredUserController.php`

Frontend:

- `resources/js/Pages/Auth/Register.vue`
- `resources/js/Pages/Auth/VerifyEmail.vue`

Na dziś flow jest takie:

1. użytkownik podaje `name`, `email`, `password` i `password_confirmation`,
2. tworzony jest `User`,
3. tworzony jest profil produktu,
4. odpalany jest event `Registered`,
5. użytkownik jest automatycznie logowany,
6. sesja jest regenerowana,
7. zapisywany jest wpis IP dla źródła `rejestracja`,
8. użytkownik trafia na `verification.notice`.

To znaczy, że baza pod rejestrację i profil już istnieje, ale sama kategoria nie jest jeszcze częścią formularza.

## Aktualny Flow Self-Service W Kodzie

Na dziś faktyczny flow zwykłego użytkownika wygląda tak:

1. użytkownik wchodzi na `/register`,
2. wysyła formularz z `name`, `email`, `password`, `password_confirmation`,
3. backend tworzy `users`,
4. backend tworzy `user_profiles` z domyślnym `tier = free` i `target_category_id = null`,
5. odpalany jest event `Registered`,
6. użytkownik jest automatycznie logowany,
7. sesja jest regenerowana,
8. zapisywany jest wpis IP dla źródła `rejestracja`,
9. użytkownik trafia na `/verify-email`,
10. po kliknięciu linku weryfikacyjnego trafia na `/dashboard?verified=1`,
11. `/dashboard` nie ma własnej logiki produktowej i od razu przekierowuje na `/nauka`,
12. od tego momentu użytkownik ma pełne wejście do tras produktu, jeśli spełnia tylko `auth + verified`.

Najważniejszy praktyczny wniosek:

- dzisiejszy flow nie zatrzymuje użytkownika po weryfikacji na wyborze kategorii,
- dzisiejszy flow nie zatrzymuje użytkownika po weryfikacji na zakupie,
- dzisiejszy flow nie ma jeszcze centralnego resolvera stanu konta po logowaniu.

## Gdzie Dziś Jest Faktyczna Blokada

Na dziś istnieje tylko jedna realna blokada wejścia do produktu dla zwykłego użytkownika:

- użytkownik musi być zalogowany,
- użytkownik musi mieć zweryfikowany e-mail.

To jest egzekwowane przez middleware:

- `auth`,
- `verified`.

Ta blokada obejmuje dziś cały główny obszar produktu:

- `/dashboard`,
- `/nauka`,
- `/nauka/ranking`,
- `/trener-pamieci`,
- analitykę zalogowanego użytkownika,
- tworzenie i obsługę sesji nauki,
- rankingowe API,
- review queue API,
- dashboard API,
- profile i endpointy produktowe pod `/api/v1`.

To znaczy, że dla zwykłego self-service usera dzisiejsza „bramka” stoi na weryfikacji e-mail, a nie na dostępie płatnym.

## Gdzie Dziś Nie Ma Paywalla

Na dziś nie ma jeszcze prawdziwego paywalla produktu.

W praktyce oznacza to:

- nie ma osobnego modelu aktywnego prawa dostępu do produktu,
- nie ma middleware, które sprawdza zakup albo ręcznie nadany dostęp,
- nie ma centralnej trasy typu `aktywuj dostęp`, która stoi pomiędzy logowaniem a wejściem do nauki,
- `user_profiles.tier` nie blokuje dziś tras produktu,
- publiczny `/cennik` nie jest częścią autoryzacji wejścia do produktu,
- `/cennik` nie jest konsultowany po rejestracji ani po logowaniu,
- po samej weryfikacji e-mail zwykły użytkownik trafia już do produktu.

To jest ważne, bo dziś system traktuje:

- `założenie konta`,
- `zweryfikowanie e-maila`,
- `prawo do korzystania z produktu`

jakby były prawie tym samym etapem, a docelowo nie mogą nim być.

## Gdzie Powinna Stanąć Docelowa Brama Dostępu

Najbezpieczniejszy model jest taki:

- publiczny `/cennik` zostaje stroną marketingowo-sprzedażową,
- właściwy paywall działa jako osobny, wewnętrzny krok zalogowanego użytkownika,
- ta brama stoi po uwierzytelnieniu i po podstawowym uporządkowaniu stanu konta,
- do produktu wpuszcza dopiero po pozytywnej decyzji o aktywnym prawie dostępu.

Docelowa kolejność dla zwykłego użytkownika self-service powinna wyglądać tak:

1. rejestracja albo logowanie,
2. jeśli potrzeba: przejęcie konta tymczasowego,
3. jeśli potrzeba: weryfikacja e-mail,
4. jeśli potrzeba: wybór kategorii,
5. sprawdzenie aktywnego prawa dostępu,
6. jeśli brak dostępu: wewnętrzny ekran paywalla / aktywacji dostępu,
7. po aktywacji albo rozpoznaniu wyjątku: wejście do produktu.

Najważniejsza decyzja architektoniczna:

- paywall nie powinien być zaszyty w samym widoku `/nauka`,
- paywall nie powinien być tylko linkiem do `/cennik`,
- paywall powinien być centralną bramą dostępu, przez którą przechodzi każdy zwykły użytkownik przed wejściem do produktu.

Dodatkowa decyzja techniczna:

- `/dashboard` nie powinien docelowo pozostać prostym przekierowaniem do `/nauka`,
- `/dashboard` powinien stać się wejściem do wspólnego resolvera post-auth albo powinien przekierowywać do osobnej trasy, która ten resolver uruchamia,
- to właśnie w tym miejscu system powinien decydować: `przejęcie konta`, `verify-email`, `wybór kategorii`, `paywall`, `produkt`.

## Co Powinno Być Za Bramą Dostępu

Za docelową bramą dostępu powinny znaleźć się wszystkie funkcje, które są realnym korzystaniem z produktu.

To obejmuje co najmniej:

- `/dashboard`, jeśli dalej ma być wejściem do produktu,
- `/nauka`,
- `/nauka/ranking`,
- `/trener-pamieci`,
- strony analityki użytkownika,
- tworzenie sesji nauki,
- odpowiadanie na pytania,
- kończenie sesji,
- review queue,
- ranking i jego API,
- dashboard API,
- API sesji nauki,
- API analityki użytkownika.

Najważniejsza zasada:

- user bez aktywnego prawa dostępu nie powinien móc ani wejść na ekran nauki, ani odpalić produktu bezpośrednio przez API.

## Co Nie Powinno Być Blokowane Przez Paywall

Brama dostępu nie powinna odcinać użytkownika od podstawowych operacji na koncie i od kroków potrzebnych do dokończenia onboardingu.

Bez aktywnego zakupu albo ręcznego grantu użytkownik nadal powinien móc:

- wejść na ekran weryfikacji e-mail,
- przejąć konto tymczasowe,
- wybrać kategorię, jeśli jeszcze jej nie ma,
- wejść na ekran aktywacji dostępu / paywalla,
- zalogować się i wylogować,
- zresetować hasło,
- uzupełnić dane wymagane do wejścia do produktu,
- wejść na publiczny `/cennik`,
- wejść na publiczne treści marketingowe i informacyjne.

To jest ważne, bo paywall ma blokować `korzystanie z produktu`, a nie `obsługę własnego konta`.

## Źródła Aktywnego Prawa Dostępu

Żeby później nie pomylić modelu danych z logiką produktu, trzeba nazwać wprost wszystkie legalne źródła aktywnego dostępu do produktu.

Docelowo aktywne prawo dostępu powinno móc pochodzić z jednego z tych źródeł:

- aktywny zakup self-service,
- aktywny grant moderatorski na 90 dni,
- wyjątek systemowy dla kont wewnętrznych, takich jak `administrator`, `moderator`, `konto testowe`.

Docelowo brak prawa dostępu oznacza:

- konto może istnieć,
- konto może być zalogowane,
- konto może mieć zweryfikowany e-mail,
- konto może mieć przypisaną kategorię,
- ale konto nie może wejść do produktu i musi trafić na ekran aktywacji dostępu.

Najważniejsza zasada:

- brama dostępu nie powinna zgadywać prawa dostępu po samym `tier`,
- brama dostępu nie powinna zgadywać prawa dostępu po samym `is_admin`,
- brama dostępu powinna dostać jednoznaczną odpowiedź, czy konto ma aktywne prawo wejścia do produktu i z jakiego źródła ono pochodzi.

## Priorytet Źródeł Dostępu

Sam fakt, że konto może mieć kilka źródeł dostępu, nie wystarczy. System musi jeszcze wiedzieć, które źródło ma pierwszeństwo przy decyzji o wejściu do produktu.

Docelowa kolejność pierwszeństwa powinna być taka:

1. blokada konta zawsze wygrywa nad wszystkim,
2. wyjątek systemowy `administrator / moderator / konto testowe`,
3. aktywny zakup self-service,
4. aktywny grant moderatorski.

Najważniejsza zasada:

- jeśli istnieje co najmniej jedno aktywne legalne źródło dostępu i konto nie jest zablokowane, użytkownik może wejść do produktu,
- ale system powinien mimo tego zapisać, które źródło jest aktualnie źródłem wiodącym dla UI, audytu i debugowania.

Docelowa reguła dla zbiegu źródeł:

- jeśli użytkownik ma jednocześnie aktywny zakup i aktywny grant moderatorski, dostęp pozostaje aktywny,
- wiodącym źródłem dostępu powinien być zakup,
- jeśli zakup wygaśnie, a grant moderatorski nadal jest aktywny, dostęp powinien dalej działać z grantu,
- jeśli grant wygaśnie, a zakup nadal jest aktywny, dostęp powinien dalej działać z zakupu.

## Kiedy Wybrać Operatora Płatności

Na dziś nie wybraliśmy jeszcze operatora płatności i ten dokument nie powinien zakładać konkretnej integracji typu `Stripe`, `Przelewy24`, `Tpay` czy innej bramki.

To nie jest jeszcze problem, pod warunkiem że architektura produktu zostanie rozdzielona poprawnie:

- `prawo dostępu do produktu` projektujemy już teraz,
- `bramę dostępu` projektujemy już teraz,
- `checkout` i `operator płatności` dokładamy później jako jedno ze źródeł aktywacji dostępu.

Najważniejsza zasada:

- operator płatności nie może być fundamentem auth flow,
- operator płatności ma być tylko jednym ze sposobów nadania aktywnego prawa dostępu zwykłemu użytkownikowi self-service.

## Najpóźniejszy Bezpieczny Moment Na Wybór Operatora

Decyzja o operatorze płatności musi zapaść:

- przed rozpoczęciem sprintu checkoutu,
- przed implementacją automatycznej aktywacji dostępu po płatności,
- przed budową webhooków, callbacków i logiki powrotu z płatności.

Nie musi zapaść wcześniej.

To oznacza, że możemy bezpiecznie zaplanować i wdrażać wcześniej:

- twardą kategorię nauki,
- rejestrację,
- verify-email,
- flow przejęcia konta tymczasowego,
- wyjątki moderatorskie i konta wewnętrzne,
- model aktywnego prawa dostępu,
- wygasanie dostępu moderatorskiego,
- bramę dostępu do produktu,
- wspólny resolver decyzji po logowaniu.

W praktyce najlepszy moment na podjęcie decyzji o operatorze to:

- koniec etapu `auth + access`,
- początek etapu `checkout + aktywacja dostępu`.

## Co Musi Być Gotowe Przed Wyborem Operatora

Zanim wybierzemy operatora, powinniśmy mieć domknięte decyzje produktowe i systemowe, które nie zależą od konkretnej bramki.

To obejmuje co najmniej:

1. definicję, kto i kiedy ma aktywne prawo dostępu do produktu,
2. definicję wyjątków od zakupu, czyli `administrator`, `moderator`, `konto testowe` i grant moderatorski,
3. decyzję, które trasy i API są za bramą dostępu,
4. decyzję, które ekrany pozostają dostępne bez zakupu,
5. wspólny resolver po logowaniu,
6. decyzję, jak wygląda stan `brak dostępu`, `dostęp aktywny`, `dostęp wygasł`, `dostęp nadany ręcznie`,
7. decyzję, czy zwykły dostęp self-service jest jednorazowy czasowy, odnawialny czy później rozszerzalny o różne warianty,
8. decyzję, co dokładnie użytkownik widzi na ekranie aktywacji dostępu przed wejściem do produktu.

Jeśli te rzeczy nie będą gotowe wcześniej, wybór operatora zacznie nam mieszać warstwy produktu z warstwą płatności.

## Jakie Pytania Trzeba Zamknąć Przed Sprintem Płatności

Przed sprintem checkoutu musimy odpowiedzieć sobie na konkretne pytania biznesowe i techniczne.

Pytania biznesowe:

1. czy dostęp self-service jest jednorazowy czy odnawialny,
2. czy dostęp ma określony czas trwania,
3. czy chcemy jeden plan, czy kilka planów,
4. czy chcemy promocje, kupony albo kody rabatowe,
5. czy potrzebujemy faktur,
6. czy sprzedajemy tylko w Polsce, czy szerzej,
7. czy płatność ma aktywować dostęp od razu automatycznie.

Pytania systemowe:

1. jaki jest docelowy stan konta po udanej płatności,
2. jaki jest docelowy stan konta po nieudanej płatności,
3. co robimy, gdy użytkownik wróci z checkoutu, ale system nie dostał jeszcze potwierdzenia płatności,
4. czy dostęp aktywujemy po powrocie użytkownika, czy dopiero po potwierdzeniu serwerowym,
5. jak zachowuje się system po wygaśnięciu dostępu,
6. czy cofnięcie płatności albo zwrot ma automatycznie wyłączyć dostęp,
7. jakie logi i audyt musimy zachować dla aktywacji dostępu po zakupie.

Pytania o samą metodę płatności:

1. jakie metody muszą być dostępne na starcie,
2. czy priorytetem są szybkie płatności lokalne,
3. czy potrzebujemy kart,
4. czy potrzebujemy BLIK-a,
5. czy potrzebujemy płatności cyklicznych już w pierwszej wersji.

## Czego Nie Kodować Przed Wyborem Operatora

Żeby nie zamknąć się za wcześnie w złym kierunku, przed wyborem operatora nie powinniśmy budować na sztywno:

- finalnego formularza checkoutu,
- webhooków produkcyjnych,
- callbacków zależnych od konkretnej bramki,
- stanów transakcji zależnych od konkretnego API operatora,
- automatycznej aktywacji dostępu opartej o szczegóły jednej integracji.

Możemy natomiast bezpiecznie przygotować:

- ekran aktywacji dostępu,
- logikę blokady wejścia do produktu,
- model aktywnego prawa dostępu,
- model źródła dostępu,
- wyjątki moderatorskie,
- stany wygasania i braku dostępu.

## Docelowy Resolver Decyzji Po Logowaniu

Żeby dało się to później rozplanować na sprinty bez chaosu, trzeba zapisać twardo, jakie decyzje ma podejmować system po każdym skutecznym logowaniu.

Docelowy resolver po uwierzytelnieniu powinien odpowiadać na pytania w tej kolejności:

1. czy konto jest zablokowane,
2. czy konto jest tymczasowe i wymaga przejęcia,
3. czy konto wymaga obowiązkowej zmiany hasła startowego,
4. czy konto spełnia zasady weryfikacji e-mail,
5. czy konto ma przypisaną kategorię,
6. czy konto ma aktywne prawo dostępu do produktu,
7. czy konto należy do wyjątku systemowego, który omija paywall,
8. dokąd dokładnie użytkownik ma zostać skierowany.

Docelowe możliwe wyniki tej decyzji powinny być jawnie ograniczone do kilku ekranów:

- `przejęcie konta`,
- `ustawienie własnego hasła`,
- `verify-email`,
- `wybór kategorii`,
- `aktywuj dostęp / wybierz plan`,
- `produkt`,
- `odrzucenie dostępu` dla kont zablokowanych.

To ma być jedna wspólna logika dla:

- rejestracji lokalnej,
- logowania lokalnego,
- Google,
- Facebooka,
- kont moderatorskich pełnych,
- kont moderatorskich tymczasowych.

Jednocześnie na dziś nie ma jeszcze prawdziwego paywalla produktu:

- trasy nauki działają na middleware `auth` i `verified`,
- nie ma jeszcze osobnego middleware ani reguły sprawdzającej aktywne prawo dostępu do produktu,
- publiczny `/cennik` istnieje jako strona placeholder,
- `user_profiles.tier` istnieje, ale dziś pełni bardziej rolę etykiety niż realnego mechanizmu autoryzacji dostępu.

To oznacza, że `publiczny cennik` i `wewnętrzny paywall` to dwa różne byty, które dopiero muszą zostać rozdzielone w implementacji:

- `/cennik` ma sprzedawać i informować,
- wewnętrzna brama dostępu ma autoryzować wejście do produktu.

Na dziś nie ma też jeszcze wdrożonego social loginu:

- w repo nie ma jeszcze gotowej warstwy `Google` / `Facebook` do auth,
- nie ma osobnych tras redirect/callback dla providerów,
- ekran logowania i rejestracji nie mają jeszcze przycisków social loginu.

To znaczy, że obecny system nie rozróżnia jeszcze poprawnie:

- użytkownika zarejestrowanego bez zakupu,
- użytkownika z aktywnym dostępem po zakupie,
- użytkownika dodanego ręcznie przez moderatora z dostępem bez zakupu.

Nie ma też jeszcze modelu:

- konta tymczasowego utworzonego przez moderatora,
- obowiązkowego przejęcia konta przy pierwszym logowaniu,
- zamiany technicznego e-maila wewnętrznego na prawdziwy e-mail użytkownika.

## Jak Dziś Traktowana Jest Kategoria

Na dziś system traktuje kategorię bardziej jako preferencję niż jako niezmienny kontrakt użytkownika.

Najważniejsze miejsce:

- `database/migrations/2026_03_19_020000_create_user_profiles_table.php`
- `app/Support/UserProfileService.php`

Obecny model:

- `user_profiles.target_category_id` jest `nullable`,
- profil startowy dostaje domyślnie `target_category_id = null`,
- jeśli użytkownik nie ma ustawionej kategorii, system bierze pierwszą aktywną kategorię publiczną.

To oznacza, że twarda blokada kategorii nie jest dziś zasadą systemową.

## Role I Dostępy Na Dziś

Aktualny model użytkownika jest prosty i nie pokrywa jeszcze docelowych wyjątków produktowych.

Najważniejsze miejsca:

- `app/Models/User.php`
- `app/Filament/Resources/Users/Schemas/UserForm.php`
- `app/Filament/Resources/Users/Schemas/UserInfolist.php`

Na dziś system zna praktycznie tylko:

- `Administrator` przez flagę `is_admin`,
- zwykłego użytkownika, w panelu opisywanego jako `Kursant`.

Na dziś nie mamy jeszcze:

- osobnej roli `moderator`,
- osobnego typu `konto testowe`,
- osobnego mechanizmu typu `ten użytkownik ma pełny dostęp do wszystkich kategorii, ale nie jest administratorem`.

Nie mamy też jeszcze osobnego modelu:

- `użytkownik ma dostęp do produktu po zakupie`,
- `użytkownik ma dostęp do produktu po ręcznym nadaniu przez moderatora`.

To znaczy, że nowa polityka dostępu do kategorii nie powinna być dopięta wyłącznie do `is_admin`. Potrzebujemy osobnego, czytelnego modelu wyjątków.

## Nadanie Roli Moderatora

Na dziś w panelu administracyjnym nie ma jeszcze prawdziwej roli `moderator`.

Obecny stan kodu oznacza:

- użytkownik może być zwykłym użytkownikiem,
- albo może mieć `is_admin = true`,
- tylko `is_admin = true` daje dziś wejście do panelu Filament,
- obecny formularz użytkownika pozwala nadać tylko status `Administrator`, a nie osobną rangę `Moderator`.

Najważniejszy wniosek:

- moderator nie może być docelowo realizowany jako `is_admin = true`,
- bo wtedy dostałby pełny panel administracyjny,
- a my chcemy dla niego osobny, ograniczony i personalny panel pracy.

## Docelowy Model Nadawania Moderatora

Docelowo administrator powinien móc nadać użytkownikowi rolę `moderator` w panelu administracyjnym.

Ta operacja nie powinna oznaczać:

- nadania pełnego admina,
- włączenia pełnego dostępu do wszystkich zasobów administracyjnych,
- otwarcia wspólnego panelu operatorów.

Ta operacja powinna oznaczać:

- nadanie roli `moderator`,
- nadanie dostępu do osobnego panelu moderatora,
- utworzenie albo aktywowanie jego personalnej puli kont,
- ustawienie domyślnej puli `30`, z możliwością zwiększenia przez administratora.

## Rekomendowany Flow Nadania Moderatora

Docelowy flow powinien wyglądać tak:

1. administrator loguje się do pełnego panelu admina,
2. administrator tworzy nowe konto albo otwiera istniejące konto użytkownika,
3. administrator wybiera akcję lub formularz typu `nadaj rolę moderatora`,
4. system ustawia użytkownikowi rolę `moderator`,
5. system nadaje dostęp do personalnego panelu moderatora,
6. system ustawia domyślną pulę kont moderatorskich na `30`,
7. administrator może opcjonalnie zwiększyć tę pulę jeszcze na etapie nadania roli albo później,
8. użytkownik loguje się już jako moderator i widzi wyłącznie swój panel moderatorski.

Najważniejsza zasada:

- nadanie roli moderatora ma być osobną, świadomą decyzją administracyjną,
- nie powinno wynikać z przypadkowego zaznaczenia `is_admin`,
- nie powinno być domyślnym efektem zwykłego utworzenia użytkownika.

## Gdzie Powinna Być Ta Operacja W Panelu

Najlepsze miejsce dla tej operacji to pełny panel administracyjny.

To oznacza:

- tylko administrator może nadać albo odebrać rolę `moderator`,
- moderator nie może sam nadać sobie tej roli,
- moderator nie może awansować innych użytkowników,
- moderator nie może zwiększać własnej puli kont.

Sama operacja może być zaprojektowana jako:

- sekcja `Rola i dostęp operacyjny` w edycji użytkownika,
- albo osobna akcja administracyjna typu `Nadaj rolę moderatora`.

Najważniejsze jest nie to, czy będzie to toggle, select czy osobna akcja, ale to, żeby:

- było to jawne,
- było audytowalne,
- było dostępne tylko dla administratora,
- i nie mieszało się z prostym statusem `Administrator`.

## Co Powinno Się Stać Po Nadaniu Roli

Po skutecznym nadaniu roli `moderator` system powinien automatycznie ustawić stan początkowy tego konta.

Minimalny docelowy efekt:

- użytkownik ma rolę `moderator`,
- użytkownik nie jest pełnym administratorem, jeśli nie dostał osobno roli `admin`,
- użytkownik dostaje dostęp do panelu moderatora,
- użytkownik dostaje własną pulę kont moderatorskich,
- domyślna pula wynosi `30`,
- administrator może ją zwiększyć,
- konto moderatora działa na własnym, personalnym zakresie danych.

## Odebranie Roli Moderatora

Trzeba od razu zapisać też odwrotny flow, żeby później nie zostawić pół-żywych uprawnień.

Jeśli administrator odbiera użytkownikowi rolę `moderator`:

- użytkownik traci dostęp do panelu moderatora,
- użytkownik nie może tworzyć nowych kont moderatorskich,
- historia wcześniej utworzonych kont pozostaje w systemie do audytu,
- system nie powinien tracić informacji, który moderator utworzył dane konto,
- administrator nadal może zarządzać skutkami wcześniejszych działań tego moderatora.

Najważniejsza zasada:

- odebranie roli nie może niszczyć historii operacyjnej,
- ma wyłączać przyszłe uprawnienia, a nie zacierać ślad.

Dodatkowa zasada operacyjna:

- konta utworzone wcześniej przez tego moderatora nie powinny „znikać” z systemu,
- po odebraniu roli ich bieżąca opieka przechodzi domyślnie pod administrację,
- administrator może później świadomie przypisać takie konta innemu moderatorowi,
- przeniesienie opieki nie powinno zmieniać historii `kto utworzył konto`, tylko ewentualnie osobno zapisywać `kto aktualnie opiekuje się kontem`.

## Dodawanie Użytkownika Z Panelu Na Dziś

Obecny panel użytkowników potrafi tworzyć konto, ale nie zarządza jeszcze prawem dostępu do produktu.

Najważniejsze miejsca:

- `app/Filament/Resources/Users/UserResource.php`
- `app/Filament/Resources/Users/Pages/CreateUser.php`
- `app/Filament/Resources/Users/Schemas/UserForm.php`

Na dziś formularz tworzenia użytkownika obejmuje głównie:

- dane konta,
- hasło,
- weryfikację maila,
- flagę `is_admin`.

Na dziś nie obejmuje jeszcze:

- przypisania kategorii na starcie,
- nadania płatnego dostępu,
- nadania bezpłatnego dostępu operatorskiego przez moderatora,
- pełnego flow moderatorskiego z nadaniem loginu i hasła użytkownikowi,
- flow tymczasowego konta moderatorskiego bez znajomości docelowego e-maila,
- obowiązkowego przejęcia konta przez użytkownika przy pierwszym logowaniu,
- limitu liczby kont tworzonych przez moderatora,
- rozróżnienia źródła dostępu typu `zakup` kontra `nadanie ręczne`.

## Gdzie Moderator Powinien Tworzyć Konta

Tego flow nie powinniśmy docelowo osadzać w zwykłym formularzu `Create User`.

Rekomendowana decyzja architektoniczna:

- moderator tworzy konta w osobnym panelu backoffice,
- technicznie ten panel może nadal być zbudowany w Filament,
- ale powinien być osobnym zasobem albo osobną sekcją roboczą, a nie zwykłym `Users/Create`.

Najlepszy docelowy model:

- `administrator` ma pełny panel użytkowników i pełny widok danych,
- `moderator` ma ograniczony panel operacyjny tylko do flow tworzenia i obsługi swoich kont,
- zwykłe zarządzanie wszystkimi użytkownikami i flow moderatorskie nie powinny być tym samym ekranem.

Najważniejszy powód:

- tworzenie zwykłego użytkownika systemowego i tworzenie konta moderatorskiego to dwa różne procesy biznesowe,
- mają inne pola, inne ograniczenia, inne statusy i inne skutki dla dostępu do produktu.

## Czego Nie Powinien Być Częścią Zwykły Create User

Flow moderatorski nie powinien być tylko kilkoma dodatkowymi checkboxami w zwykłym formularzu użytkownika.

Nie powinniśmy doklejać tego do standardowego `Create User` w postaci:

- `czy to konto moderatora`,
- `czy nadać 90 dni`,
- `czy konto tymczasowe`,
- `czy policzyć do limitu 30`,
- `czy wymaga przejęcia`.

To prowadziłoby do wymieszania:

- zarządzania globalnym kontem użytkownika,
- provisioningiem ucznia przez moderatora,
- modelem dostępu do produktu,
- i logiką limitów operatorskich.

Docelowa zasada powinna być twarda:

- `Create User` służy do zwykłego zarządzania użytkownikami przez administrację,
- osobny workflow moderatorski służy do tworzenia kont uczniów przez moderatora.

## Docelowy Panel Moderatora

Moderator powinien mieć własny, ograniczony panel roboczy.

Ten panel powinien być projektowany wokół zadania:

- `utwórz i obsłuż konto ucznia`.

Minimalny zakres widoków moderatorskich:

1. lista kont utworzonych przez moderatora,
2. licznik wykorzystania limitu `30`,
3. formularz utworzenia nowego konta,
4. ekran szczegółów konta moderatorskiego,
5. podstawowe akcje operacyjne na koncie już utworzonym.

Lista kont moderatorskich powinna pokazywać co najmniej:

- imię lub nazwę użytkownika,
- kategorię nauki,
- typ konta `pełne` albo `tymczasowe`,
- status `wymaga przejęcia / aktywne / wygasłe / zablokowane`,
- źródło dostępu,
- datę końca dostępu,
- informację, który moderator utworzył konto.

## Zakres Formularza Moderatorskiego

Formularz moderatora powinien prowadzić użytkownika panelu przez prosty flow operacyjny, a nie przez surowy formularz modelu `users`.

Docelowy formularz moderatorski powinien obejmować co najmniej:

1. wybór kategorii nauki,
2. wybór trybu `znam e-mail` albo `nie znam e-maila`,
3. pole e-mail tylko wtedy, gdy moderator zna e-mail użytkownika,
4. nadanie hasła startowego albo tymczasowego,
5. automatyczne przypisanie dostępu na 90 dni,
6. automatyczne oznaczenie źródła dostępu jako grant moderatorski,
7. automatyczne oznaczenie, czy konto wymaga przejęcia.

Moderator nie powinien w tym miejscu ręcznie decydować o rzeczach, które muszą być stałą polityką systemu, takich jak:

- długość dostępu `90 dni`,
- to, czy konto liczy się do limitu,
- to, czy konto tymczasowe wymaga przejęcia.

To powinno wynikać z samego typu flow, a nie z ręcznego klikania wyjątków.

## Jakie Akcje Powinien Mieć Moderator Po Utworzeniu Konta

Po utworzeniu konta moderator powinien móc wykonać tylko te akcje, które są potrzebne do prowadzenia swojego procesu end-to-end.

Minimalny zestaw akcji moderatorskich:

- zobacz status danych startowych bez ujawniania starego hasła,
- sprawdź, czy konto zostało już przejęte,
- sprawdź, kiedy wygasa dostęp,
- przedłuż albo odnowienie dostępu, jeśli taka operacja będzie dopuszczona biznesowo,
- zablokuj dalsze użycie danych startowych, jeśli zajdzie potrzeba,
- wygeneruj nowe hasło tymczasowe albo startowe, jeśli zajdzie potrzeba,
- przekaż użytkownikowi ponownie bezpieczne instrukcje logowania.

Jeśli dopuścimy później dodatkowe akcje, trzeba je planować osobno. Na dziś nie należy zakładać, że moderator ma być małym administratorem użytkownika.

Najważniejsza zasada bezpieczeństwa:

- hasło startowe albo tymczasowe powinno być pokazane moderatorowi tylko raz przy utworzeniu albo ponownym wygenerowaniu,
- system nie powinien pozwalać na późniejsze ponowne podejrzenie tego samego hasła w panelu,
- późniejsza pomoc użytkownikowi powinna polegać na wygenerowaniu nowego hasła tymczasowego, a nie na odsłanianiu starego.

## Zakres Widoczności Moderatora

Moderator nie powinien widzieć całej bazy użytkowników ani pełnego panelu administracyjnego.

Docelowa zasada:

- moderator widzi tylko konta utworzone przez siebie,
- moderator nie widzi kont innych moderatorów nawet w trybie tylko do odczytu,
- panel moderatora ma być panelem personalnym i indywidualnym, a nie wspólną listą operatorską,
- administrator może widzieć całość i ewentualnie nadpisywać działania moderatora,
- konto testowe nie powinno automatycznie mieć takiego samego panelu jak moderator.

To jest ważne nie tylko dla porządku UX, ale też dla bezpieczeństwa operacyjnego i późniejszego audytu.

Najważniejsza reguła dostępu dla moderatora:

- moderator pracuje wyłącznie na własnej puli kont,
- jego licznik `30` dotyczy wyłącznie kont utworzonych przez niego,
- system nie powinien mieszać jego widoku z kontami tworzonymi przez innych moderatorów.

## Flow Moderatora

Moderator ma obsługiwać takie konto od początku do końca.

Docelowo powinny istnieć dwa warianty:

1. `konto pełne`, jeśli moderator zna e-mail użytkownika,
2. `konto tymczasowe`, jeśli moderator nie zna e-maila użytkownika.

Niezależnie od wariantu:

- konto tworzone przez moderatora nie powinno wpuszczać użytkownika do produktu na samym haśle startowym bez dalszych zabezpieczeń,
- moderator nie powinien znać docelowego hasła użytkownika po zakończeniu onboardingu,
- system powinien wymuszać domknięcie własności konta przez użytkownika przed pełnym wejściem do produktu.

### Wariant 1: Konto Pełne

1. moderator tworzy konto użytkownika z panelu,
2. moderator wpisuje docelowy e-mail użytkownika,
3. system generuje hasło startowe,
4. moderator przypisuje kategorię nauki,
5. moderator nadaje dostęp do produktu bez zakupu,
6. dostęp działa przez 90 dni,
7. system wysyła użytkownikowi mail startowy z loginem, hasłem startowym, kategorią i podpisanym linkiem weryfikacyjnym,
8. moderator widzi login i hasło startowe tylko raz jako kopię awaryjną, gdy mail nie dojdzie albo użytkownik zgubi wiadomość,
9. użytkownik klika link weryfikacyjny z maila i w razie potrzeby loguje się hasłem startowym,
10. system potwierdza e-mail standardowym mechanizmem signed route,
11. przy pierwszym wejściu użytkownik musi ustawić własne hasło,
12. dopiero po zmianie hasła i weryfikacji e-mail użytkownik może wejść do produktu.

Doprecyzowanie bezpieczeństwa:

- wysłanie maila startowego nie oznacza automatycznego potwierdzenia e-maila,
- e-mail jest potwierdzony dopiero po kliknięciu podpisanego linku,
- system nie powinien wysyłać osobnego drugiego maila weryfikacyjnego, jeśli użytkownik potwierdzi adres linkiem z maila startowego,
- jeśli mail nie dotarł albo hasło przepadło, moderator może wygenerować nowe hasło startowe i wysłać nową wiadomość,
- regeneracja jest dozwolona tylko do momentu, w którym konto nadal ma `requires_password_change = true`,
- po ustawieniu własnego hasła przez użytkownika moderator nie może resetować hasła w tym flow; użytkownik korzysta wtedy ze standardowego resetu hasła.

Szczegółowy plan wdrożenia i testów: `docs/MODERATOR-ACCOUNT-EMAIL-START-FLOW.md`.

### Wariant 2: Konto Tymczasowe

1. moderator tworzy konto użytkownika z panelu,
2. system generuje wewnętrzny techniczny e-mail, który pełni rolę tymczasowego loginu,
3. moderator nadaje hasło tymczasowe,
4. moderator przypisuje kategorię nauki,
5. moderator nadaje dostęp do produktu bez zakupu,
6. konto otrzymuje status `wymaga przejęcia`,
7. użytkownik loguje się tymczasowymi danymi od moderatora,
8. przed wejściem do produktu użytkownik musi podać prawdziwy e-mail,
9. użytkownik musi ustawić własne hasło,
10. system sprawdza unikalność e-maila i dopiero wtedy przypisuje go do konta,
11. system wysyła standardową weryfikację e-mail,
12. dopiero po przejęciu konta użytkownik przechodzi do dalszego flow produktu.

Najważniejsza zasada operacyjna:

- jeśli konto jest tymczasowe, moderator nie musi znać e-maila,
- ale użytkownik nie powinien wejść do pełnej nauki bez przejęcia konta i ustawienia prawdziwego e-maila.

To oznacza, że moderatorski flow jest osobną ścieżką provisioningu konta, a nie tylko wariantem zwykłej rejestracji.

## Kolizja Z Istniejącym Kontem Przy Flow Moderatora

Flow moderatorski nie może tworzyć duplikatu tylko dlatego, że moderator wpisał e-mail, który już istnieje w systemie.

Docelowa zasada:

- jeśli moderator próbuje utworzyć konto pełne na e-mail, który już istnieje, system nie tworzy nowego konta,
- system zatrzymuje flow i przechodzi do kontrolowanej ścieżki obsługi istniejącego konta,
- ta ścieżka powinna pozwalać na bezpieczne rozstrzygnięcie, czy istniejące konto ma dostać grant dostępu i czy ma zostać przypisane do obsługi moderatorskiej,
- jeśli polityka widoczności nie pozwala moderatorowi samodzielnie pracować na istniejącym koncie, przypadek powinien zostać przekazany administratorowi.

Najważniejsza zasada:

- brak duplikatów jest ważniejszy niż szybkość utworzenia nowego konta,
- moderator nie powinien móc „ominąć” kolizji e-mailowej przez powtórne tworzenie drugiego użytkownika.

## Limit Kont Moderatora

Każdy moderator ma domyślną możliwość utworzenia maksymalnie `30` takich kont.

Ten limit powinien być traktowany jako twarda część logiki produktu.

To oznacza, że system docelowo powinien:

- wiedzieć, który moderator utworzył dane konto,
- rozróżniać konto utworzone standardowo od konta utworzonego przez moderatora,
- rozróżniać konto pełne moderatorskie od konta tymczasowego moderatorskiego,
- pilnować limitu puli kont przypisanego do moderatora.

Dodatkowa zasada administracyjna:

- administrator powinien móc zwiększyć pulę kont konkretnego moderatora ponad domyślne `30`,
- zwiększenie puli musi być jawną decyzją administracyjną, a nie ręcznym obejściem logiki,
- system powinien rozróżniać `domyślny limit` od `aktualnie przyznanej puli`,
- moderator nadal widzi tylko własną pulę i własny licznik wykorzystania.

## Jak Działa Zużywanie I Zwalnianie Puli Moderatora

Sama liczba `30` nie wystarczy. Trzeba też zdefiniować, kiedy slot jest zajęty i kiedy wraca do puli.

Rekomendowana zasada:

- pula moderatora działa jak liczba aktywnie przypisanych kont moderatorskich,
- slot zajmuje się wtedy, gdy konto zostaje utworzone i przypisane do moderatora,
- slot nie powinien być liczony jako „ile razy moderator kiedykolwiek coś utworzył”.

Slot powinien zostać zwolniony tylko w jasno określonych przypadkach:

- konto zostanie trwale zamknięte zgodnie z polityką systemu,
- konto zostanie jawnie odpięte od moderatora przez administratora,
- konto zostanie przeniesione do innego moderatora,
- konto tymczasowe wygaśnie bez przejęcia i system je zamknie jako nieaktywne provisioningowo.

Rekomendowana zasada dla kont tymczasowych:

- nieprzejęte konto tymczasowe nie powinno wisieć bez końca,
- jeśli nie zostanie przejęte w ciągu `14 dni`, powinno automatycznie wygasnąć jako rekord provisioningowy,
- po takim wygaśnięciu slot powinien wrócić do puli moderatora.

Najważniejsza zasada:

- wygaśnięcie samego 90-dniowego dostępu nie powinno automatycznie zwalniać slotu, jeśli konto nadal pozostaje aktywnie przypisanym kontem moderatora,
- zwolnienie slotu powinno wynikać z jawnej zmiany statusu opieki nad kontem, a nie wyłącznie z wygaśnięcia prawa dostępu.

## Gdzie Użytkownik Może Dziś Zmieniać Kategorię

Na dziś użytkownik może realnie zmieniać kategorię samodzielnie w kilku miejscach.

Web:

- `app/Http/Controllers/ProfileController.php`
- `app/Http/Requests/UpsertUserProductProfileRequest.php`
- `resources/js/Pages/Profile/Partials/UpdateStudyPreferencesForm.vue`

API:

- `app/Http/Controllers/MeProfileController.php`
- `app/Http/Requests/UpsertUserProductProfileRequest.php`

Szybkie przełączanie w aplikacji:

- `resources/js/Components/SiteHeader.vue`
- `resources/js/Pages/Session/Index.vue`

W praktyce oznacza to, że obecny produkt zakłada swobodne przestawianie `target_category_id`, a nie jednorazowy wybór na starcie.

## Co Aplikacja Współdzieli Globalnie

Kategoria użytkownika jest dziś częścią wspólnego kontekstu całej aplikacji, ale razem z pełną listą wszystkich publicznych kategorii.

Główne miejsca:

- `app/Support/StudyContextService.php`
- `app/Http/Middleware/HandleInertiaRequests.php`

Na dziś współdzielamy do frontu:

- `studyContext.targetCategoryId`,
- `studyContext.reviewDueCount`,
- `studyContext.visualExplanationsEnabled`,
- `studyContext.visualExplanationsMode`,
- `studyContext.categories`.

To ma duże znaczenie, bo front nie dostaje tylko jednej przypisanej kategorii użytkownika. Dostaje całą listę kategorii publicznych i może na niej budować przełączniki, dropdowny i różne warianty wejścia do nauki.

## Backend Nadal Przyjmuje Dowolną Kategorię

To jest najważniejszy wniosek techniczny: samo schowanie przełączników w UI nie wystarczy.

Sesje nauki:

- `app/Http/Requests/StudySessionStoreRequest.php`
- `app/Http/Controllers/StudySessionController.php`
- `app/Http/Requests/ApiStudySessionStoreRequest.php`
- `app/Http/Controllers/ApiStudySessionController.php`

Ranking:

- `app/Http/Requests/JoinRankedQueueRequest.php`
- `app/Http/Controllers/ApiRankedQueueController.php`
- `app/Support/RankedMatchService.php`

Na dziś backend przyjmuje `license_category_id` albo `category_id` z requestu. To znaczy, że nawet jeśli usuniemy dropdown z interfejsu, użytkownik lub frontend nadal może wysłać inną kategorię, jeśli nie dołożymy twardej reguły po stronie serwera.

## Powiązania W Bazie

Twarda kategoria nauki nie dotyka tylko `user_profiles`. W praktyce dotyka całego śladu nauki użytkownika.

Najważniejsze tabele:

- `user_profiles.target_category_id`
- `study_sessions.license_category_id`
- `user_question_progress.question_id` powiązane pośrednio z `questions.license_category_id`
- `ranked_queue_entries.license_category_id`
- `ranked_matches.license_category_id`

Do tego dochodzą dane zależne od sesji i meczów, na przykład odpowiedzi użytkownika, historia sesji, kolejka rankingowa i rating.

Wniosek jest prosty: zmiana kategorii po czasie nie jest tylko zmianą jednego pola w profilu. To decyzja, która może wymagać wyczyszczenia większej części danych użytkownika.

## Funkcje Zależne Od Mieszanej Historii Kategorii

Dziś kilka istotnych funkcji nadal patrzy szerzej na aktywność użytkownika niż tylko na jedną kategorię z profilu.

Najważniejsze miejsca:

- `app/Http/Controllers/ReviewQueueController.php`
- `app/Support/DashboardMetricsService.php`
- `app/Support/UserReadinessService.php`
- `app/Support/HardQuestionService.php`
- `app/Support/CategoryAnalyticsService.php`
- `app/Http/Controllers/CategoryAnalyticsPageController.php`
- `app/Http/Controllers/CategoryAnalyticsController.php`

Szczególnie ważny jest ten szczegół:

- `app/Support/StudyContextService.php` liczy dziś `dueReviewCount` dla wszystkich zaległych pytań użytkownika, nie tylko dla jednej kategorii.

To oznacza, że jeśli administracja zmieni użytkownikowi `target_category_id`, ale zostawi stare dane nauki, to stare ślady mogą dalej wracać w:

- liczniku powtórek,
- statystykach,
- trudnych pytaniach,
- gotowości użytkownika,
- dashboardzie,
- analityce kategorii.

## Ranking Wymaga Osobnej Uwagi

Ranking nie jest dziś jeszcze w pełni spięty z kategorią z profilu użytkownika.

Najważniejsze miejsce:

- `app/Http/Controllers/RankedSessionPageController.php`

Na dziś strona rankingowa wybiera domyślnie kategorię `B`, a nie kategorię wynikającą z `target_category_id`.

To oznacza, że przy wdrażaniu twardej kategorii trzeba zdecydować wprost:

- czy ranking ma zawsze podążać za przypisaną kategorią użytkownika,
- czy ranking ma być ograniczony tylko do wybranych kategorii,
- czy ranking ma być czasowo wyłączony dla kategorii, których jeszcze nie wspiera.

Bez tej decyzji można łatwo wprowadzić niespójność między zwykłą nauką a trybem rankingowym.

Dodatkowa zasada produktowa dla kont wewnętrznych:

- konta testowe nie powinny zaśmiecać publicznych statystyk, rankingów i produkcyjnej analityki,
- jeśli dostają pełny dostęp do kategorii, powinny być rozpoznawalne przez system jako konta specjalne.

## Brak Narzędzia Admina Do Zmiany Kategorii

Polityka produktowa zakłada zmianę kategorii przez administrację, ale obecnie nie mamy jeszcze gotowego panelu do takiej operacji.

Filament:

- `app/Filament/Resources/Users/Schemas/UserInfolist.php`
- `app/Filament/Resources/Users/Schemas/UserForm.php`

Na dziś:

- infolist użytkownika pokazuje kategorię z profilu,
- formularz admina nie daje normalnej edycji `target_category_id`,
- nie ma jeszcze jawnej akcji typu `zmień kategorię i zresetuj naukę`.

## Najbezpieczniejszy Kierunek Techniczny

Na bazie obecnego kodu najbardziej spójny kierunek wygląda tak:

1. dodać obowiązkowy wybór kategorii do formularza rejestracji,
2. zapisywać wybraną kategorię od razu do `user_profiles.target_category_id`,
3. po rejestracji nie wpuszczać zwykłego użytkownika do produktu bez aktywnego prawa dostępu,
4. rozdzielić model `plan/tier` od modelu `prawo dostępu`,
5. dodać osobny model wyjątków dla kont wewnętrznych i ręcznie nadanych dostępów, zamiast opierać wszystko wyłącznie na `is_admin`,
6. dodać model tymczasowego konta moderatorskiego z obowiązkowym przejęciem konta przy pierwszym logowaniu,
7. przestać traktować kategorię jako zwykłą preferencję użytkownika,
8. usunąć samodzielną zmianę kategorii z normalnego UI i publicznego API,
9. wymusić po stronie backendu, że zwykły użytkownik uruchamia sesje nauki i ranking tylko w przypisanej kategorii oraz tylko z aktywnym prawem dostępu,
10. pozwolić administratorowi, moderatorowi, kontu testowemu i użytkownikowi z ręcznie nadanym dostępem działać zgodnie z przypisanym zakresem bez wymogu zakupu,
11. dodać administracyjną ścieżkę zmiany kategorii,
12. przy zmianie przez admina dopuścić reset danych nauki, jeśli to jest najbezpieczniejsze dla systemu.

## Rekomendowany Model Dostępu Do Produktu

Najbezpieczniejszy model powinien rozdzielić trzy scenariusze wejścia:

- `rejestracja własna`:
  - konto powstaje,
  - użytkownik potwierdza e-mail,
  - bez zakupu nie dostaje pełnego dostępu do produktu.
- `zakup`:
  - użytkownik dostaje aktywne prawo dostępu do produktu,
  - może korzystać z nauki w swojej przypisanej kategorii.
- `nadanie przez moderatora`:
  - użytkownik dostaje aktywne prawo dostępu bez zakupu,
  - dostęp jest nadawany na 90 dni,
  - konto jest tworzone przez moderatora end-to-end,
  - moderator nadaje użytkownikowi dane startowe do pierwszego logowania,
  - jeśli moderator nie zna e-maila, konto może zacząć jako konto tymczasowe,
  - źródłem dostępu nie jest płatność, tylko decyzja operatorska.

Najważniejsza zasada:

- `brak zakupu` nie może automatycznie oznaczać `brak dostępu` dla wszystkich,
- bo użytkownik utworzony przez moderatora ma być legalnym wyjątkiem.

## Moderatorowy Wyjątek Dostępu

To trzeba zapisać bardzo precyzyjnie, żeby później niczego nie popsuć:

- moderator może dodać użytkownika, który nie musi kupować dostępu,
- taki użytkownik powinien mieć aktywne prawo dostępu od początku albo od momentu ręcznego nadania,
- taki dostęp powinien wygasać po 90 dniach, jeśli nie zostanie przedłużony albo zastąpiony zakupem,
- dla konta tymczasowego najbezpieczniej liczyć te 90 dni od chwili przejęcia konta przez użytkownika, a nie od samego technicznego utworzenia rekordu przez moderatora,
- moderator powinien móc utworzyć takie konto tylko w ramach swojego limitu `30` kont,
- system powinien rozróżniać, że dostęp pochodzi z nadania operatorskiego, a nie z checkoutu,
- cofnięcie takiego dostępu nie powinno wymagać symulowania anulowania płatności.

Dodatkowa zasada bezpieczeństwa:

- moderator nie powinien znać docelowego hasła użytkownika po przejęciu konta,
- dlatego konto tymczasowe powinno wymuszać ustawienie nowego hasła przez użytkownika przy pierwszym logowaniu,
- i z tego samego powodu także pełne konto utworzone przez moderatora powinno wymuszać zmianę hasła startowego przy pierwszym logowaniu.

Dodatkowa zasada weryfikacyjna:

- pełne konto utworzone przez moderatora nie powinno omijać weryfikacji e-mail,
- użytkownik powinien wejść do produktu dopiero po zmianie hasła startowego i po przejściu standardowej weryfikacji e-mail,
- wyjątek od tej zasady nie powinien należeć do moderatora i ewentualnie może być tylko jawną decyzją administracyjną, jeśli kiedyś świadomie go zaprojektujemy.

To oznacza, że logika produktu nie może pytać tylko:

- `czy user ma tier premium?`

Powinna umieć odpowiedzieć na pytanie:

- `czy user ma aktywne prawo do korzystania z produktu i skąd ono pochodzi?`

## Rekomendowana Matryca Dostępu

Najprostsza i najbardziej czytelna polityka wygląda tak:

- `Kursant` wybiera kategorię przy rejestracji, ma zablokowaną naukę do jednej kategorii i bez aktywnego prawa dostępu nie powinien wejść do pełnej nauki.
- `Administrator` ma dostęp do wszystkich kategorii, może przełączać kategorię i ma dostęp do panelu admina.
- `Moderator` ma dostęp do wszystkich kategorii i może przełączać kategorię, ale nie musi automatycznie mieć pełnego zestawu uprawnień administracyjnych niezwiązanych z moderacją.
- `Konto testowe` ma dostęp do wszystkich kategorii i może przełączać kategorię, ale nie powinno automatycznie znaczyć `admin` i powinno dać się wyłączyć z rankingu, statystyk i analityki.
- `Użytkownik dodany przez moderatora` ma aktywne prawo dostępu bez zakupu przez 90 dni, nie powinno to automatycznie robić z niego administratora ani konta testowego, a konto może zostać utworzone jako pełne albo tymczasowe.
- `Moderator` powinien mieć możliwość utworzenia maksymalnie `30` użytkowników w tym specjalnym flow i odpowiada za ich utworzenie end-to-end, łącznie z nadaniem danych startowych i doprowadzeniem użytkownika do przejęcia konta, jeśli konto było tymczasowe.

## Rekomendacja Dla Zmiany Przez Admina

Najbezpieczniejsza operacyjnie polityka jest twarda:

- zmiana kategorii przez admina może wyczyścić dane nauki użytkownika,
- nie obiecujemy zachowania postępów po zmianie kategorii,
- priorytetem jest spójność systemu i brak mieszania danych między kategoriami.

Na dziś to jest rozsądniejsze niż próba delikatnego przenoszenia części danych, bo obecny model aplikacji był budowany pod świat, w którym kategorie są widoczne szeroko i mogą się przeplatać.

## Co Trzeba Będzie Zmienić Przy Wdrożeniu

Zakres wdrożenia nie ogranicza się do formularza rejestracji.

Do zmiany będą co najmniej:

- formularz rejestracji i walidacja backendowa,
- domyślne tworzenie profilu użytkownika,
- model ról i wyjątków dostępu do kategorii,
- model prawa dostępu do produktu i źródła tego dostępu,
- model powiązań konta z providerami logowania społecznościowego,
- model tymczasowego konta moderatorskiego i obowiązkowego przejęcia konta przy pierwszym logowaniu,
- profile web i API,
- header i ekran nauki,
- paywall po rejestracji i warunki wejścia do produktu,
- flow moderatorskiego tworzenia kont i limit `30` kont na moderatora,
- trasy redirect/callback dla `Google` i `Facebooka`,
- UI logowania i rejestracji z przyciskami providerów,
- logika linkowania istniejących kont i zabezpieczenia przed duplikatami,
- requesty i kontrolery tworzące sesje,
- requesty i kontrolery rankingu,
- administracja użytkownikiem,
- testy feature i testy UI pod obecny model wielokategorialny.

## Jak Używać Dokumentu Do Sprintów

Ten dokument powinien służyć jako dokument źródłowy dla backlogu.

Przy planowaniu sprintów każdą zmianę należy przypisać do jednego z obszarów:

- `auth`,
- `role i panele`,
- `kategoria nauki`,
- `prawo dostępu`,
- `moderator`,
- `social login`,
- `płatności`,
- `testy i migracja danych`.

Każdy sprint powinien mieć:

1. zakres funkcjonalny,
2. zakres techniczny,
3. jasne kryteria akceptacji,
4. listę testów automatycznych,
5. listę manualnych scenariuszy do sprawdzenia.

Status prac warto prowadzić bezpośrednio przy sprintach:

- `[ ]` nie zaczęte,
- `[~]` w trakcie,
- `[x]` zakończone i zweryfikowane.

## Definicja Ukończenia Dla Każdego Sprintu

Sprint nie powinien być uznany za zamknięty tylko dlatego, że kod został napisany.

Minimalna definicja ukończenia:

- kod jest wdrożony lokalnie i przechodzi testy,
- migracje są odwracalne albo świadomie nieodwracalne i opisane,
- krytyczne flow ma test feature albo test integracyjny,
- ręczny scenariusz użytkownika został przechodzony w przeglądarce albo w panelu,
- dokumentacja została zaktualizowana, jeśli decyzja produktowa się zmieniła,
- nie ma znanego obejścia backendu przez bezpośrednie wywołanie API.

## Proponowane Epiki

Docelowy zakres najlepiej rozbić na epiki, które można później zamienić na zadania.

`EPIC-01: Model ról i kont`

- rola `kursant`,
- rola `moderator`,
- rola `administrator`,
- typ albo flaga `konto testowe`,
- odebranie i nadanie roli moderatora,
- osobne uprawnienia paneli.

`EPIC-02: Model prawa dostępu`

- aktywne prawo dostępu,
- źródło dostępu,
- priorytet źródeł dostępu,
- wygasanie dostępu,
- grant moderatorski,
- wyjątki systemowe.

`EPIC-03: Resolver po logowaniu`

- wspólna decyzja po każdym logowaniu,
- przejęcie konta tymczasowego,
- wymuszona zmiana hasła startowego,
- weryfikacja e-mail,
- wybór kategorii,
- paywall,
- wejście do produktu.

`EPIC-04: Twarda kategoria nauki`

- wybór kategorii przy rejestracji,
- blokada zmiany kategorii przez kursanta,
- backendowe wymuszenie przypisanej kategorii,
- administracyjna zmiana kategorii,
- reset danych nauki przy zmianie kategorii.

`EPIC-05: Panel moderatora`

- osobny personalny panel,
- lista tylko własnych kont,
- licznik puli,
- tworzenie kont,
- statusy kont,
- bezpieczne dane startowe.

`EPIC-06: Konta tworzone przez moderatora`

- konto pełne,
- konto tymczasowe,
- przejęcie konta,
- zmiana hasła startowego,
- weryfikacja e-mail,
- wygasanie nieprzejętych kont po `14 dniach`,
- dostęp `90 dni`.

`EPIC-07: Social login`

- Google,
- Facebook,
- resolver OAuth,
- linkowanie kont,
- brak duplikatów,
- ręczne podpinanie i odpinanie providerów.

`EPIC-08: Płatności`

- wybór operatora płatności,
- checkout,
- powrót z płatności,
- webhooki,
- aktywacja dostępu po zakupie,
- obsługa nieudanej płatności i zwrotów.

`EPIC-09: Testy, audyt i telemetria`

- testy regresji auth,
- testy dostępu do API,
- testy panelu moderatora,
- testy social loginu,
- testy płatności,
- audyt zdarzeń i źródeł dostępu.

## Proponowana Kolejność Sprintów

Poniższy podział jest rekomendowaną kolejnością. Nie zakłada jeszcze wyboru operatora płatności.

### Sprint 1: Fundament Ról, Stanu Konta I Dostępu

Status: `[x]`

Cel:

- przygotować model danych, który pozwoli odróżnić rolę, kategorię, stan konta i prawo dostępu.

Zakres:

- dodać docelowy model roli użytkownika albo równoważny mechanizm uprawnień,
- dodać rozróżnienie `administrator`, `moderator`, `kursant`, `konto testowe`,
- dodać model aktywnego prawa dostępu i źródła dostępu,
- dodać pola albo tabele potrzebne do kont tymczasowych i wymuszonej zmiany hasła startowego,
- dodać model puli moderatora z domyślnym limitem `30`.

Kryteria akceptacji:

- system potrafi odpowiedzieć, czy użytkownik jest administratorem, moderatorem, kursantem albo kontem testowym,
- system potrafi odpowiedzieć, czy użytkownik ma aktywne prawo dostępu i z jakiego źródła,
- admin może zwiększyć pulę moderatora w modelu danych,
- testy pokrywają priorytet źródeł dostępu.

Wynik po realizacji:

- dodano role `admin`, `moderator`, `student` przy zachowaniu starego `is_admin`,
- dodano flagi konta testowego, konta tymczasowego i wymuszonej zmiany hasła,
- dodano `moderator_quota` z domyślnym limitem `30`,
- dodano model `ProductAccessGrant` dla źródeł `system`, `purchase`, `moderator_grant`, `manual`,
- dodano `ProductAccessResolver`, który rozstrzyga aktywny dostęp i priorytet źródeł,
- dodano testy dla ról, kont systemowych, blokady, priorytetu zakupu nad grantem moderatora i puli moderatora.

Nie wchodzi w zakres:

- checkout,
- social login,
- pełny panel moderatora.

### Sprint 2: Resolver Po Logowaniu I Brama Dostępu

Status: `[x]`

Cel:

- stworzyć jedną centralną decyzję po logowaniu i zablokować produkt dla użytkownika bez aktywnego dostępu.

Zakres:

- dodać wspólny resolver post-auth,
- zmienić `/dashboard`, żeby nie był prostym redirectem do `/nauka`,
- dodać wewnętrzny ekran `aktywuj dostęp / wybierz plan`,
- dodać middleware albo równoważną bramę dostępu dla tras produktu i API,
- przepuścić przez bramę wyjątki systemowe i aktywne granty.

Kryteria akceptacji:

- zwykły zweryfikowany użytkownik bez dostępu nie wejdzie na `/nauka`,
- ten sam użytkownik nie uruchomi sesji przez API,
- użytkownik z aktywnym grantem albo wyjątkiem systemowym wejdzie do produktu,
- konto zablokowane nie wejdzie niezależnie od źródła dostępu.

Wynik po realizacji:

- `/dashboard` używa wspólnej decyzji post-auth i kieruje użytkownika do produktu albo na aktywację dostępu,
- dodano wewnętrzny ekran `/aktywuj-dostep`,
- dodano middleware `product.access` dla tras produktu i produktowego API,
- brama przepuszcza aktywne granty oraz wyjątki systemowe: administrator, moderator i konto testowe,
- brama blokuje zwykłego kursanta bez dostępu na web i API,
- zablokowane konto nie przechodzi przez produkt nawet z aktywnym grantem,
- testy pokrywają web paywall, API paywall, grant zakupowy, wyjątek systemowy i blokadę konta.

Nie wchodzi w zakres:

- realna płatność,
- providerzy Google i Facebook.

### Sprint 3: Twarda Kategoria Dla Kursanta

Status: `[x]`

Cel:

- zrobić z kategorii stały kontrakt kursanta, a nie zwykłą preferencję.

Zakres:

- dodać obowiązkowy wybór kategorii przy rejestracji albo w pierwszym kroku po rejestracji,
- zapisać kategorię do profilu użytkownika,
- usunąć albo ukryć samodzielną zmianę kategorii dla kursanta,
- wymusić kategorię po backendzie w sesjach nauki,
- wymusić kategorię po backendzie w rankingu albo czasowo zablokować ranking tam, gdzie nie jest gotowy,
- dostosować globalny `studyContext`.

Kryteria akceptacji:

- kursant nie może rozpocząć nauki w innej kategorii niż przypisana,
- bezpośrednie requesty z inną kategorią są odrzucane albo ignorowane zgodnie z polityką,
- admin, moderator i konto testowe nadal mogą pracować na wszystkich kategoriach,
- testy pokrywają UI i API.

Wynik po realizacji:

- formularz rejestracji wymaga wyboru kategorii prawa jazdy,
- wybrana kategoria zapisuje się w `user_profiles.target_category_id` jako stały cel nauki,
- kursant z przypisaną kategorią widzi w globalnym `studyContext` tylko tę kategorię,
- kursant bez przypisanej kategorii może ustawić ją pierwszy raz, a później zmiana jest blokowana,
- samodzielna zmiana kategorii w profilu i pasku nauki jest ukryta albo zablokowana dla kursanta,
- backend odrzuca start sesji web/API i wejście do kolejki rankingowej w innej kategorii,
- analityka kategorii, trener pamięci i dashboard używają kategorii dostępnych dla danego typu konta,
- administrator, moderator i konto testowe zachowują dostęp do wszystkich kategorii.

Nie wchodzi w zakres:

- adminowa zmiana kategorii z resetem danych, jeśli nie zmieści się w sprincie.

### Sprint 4: Panel Administratora Dla Ról I Kategorii

Status: `[x]`

Cel:

- dać administratorowi kontrolę nad rolami, pulą moderatora i zmianą kategorii.

Zakres:

- dodać nadawanie i odbieranie roli moderatora,
- dodać zwiększanie puli moderatora,
- dodać widoczność aktualnej puli i jej wykorzystania,
- dodać administracyjną zmianę kategorii kursanta,
- dodać politykę resetu danych nauki przy zmianie kategorii,
- audytować operacje admina.

Kryteria akceptacji:

- tylko administrator nadaje i odbiera rolę moderatora,
- moderator nie może zwiększyć własnej puli,
- zmiana kategorii przez admina nie zostawia niespójnych danych nauki,
- historia operacji jest audytowalna.

Zrealizowane decyzje techniczne:

- role `kursant`, `moderator`, `administrator`, flaga konta testowego i pula moderatora są edytowane w panelu administratora,
- panel pokazuje wykorzystanie puli moderatora jako `użyte / limit` oraz liczbę pozostałych kont,
- moderator nie dostaje dostępu do pełnego panelu administratora, więc nie może sam zwiększyć swojej puli,
- administracyjna zmiana kategorii kursanta jest obsługiwana przez centralny serwis `AdminUserAccountService`,
- polityka resetu jest obligatoryjna przy zmianie kategorii: system usuwa sesje nauki, odpowiedzi z sesji, progres pytań, wpisy kolejki rankingowej i ranking gracza, a w profilu zeruje serię nauki oraz ostatni dzień nauki,
- operacje zmiany ustawień administracyjnych i zmiany kategorii zapisują wpisy w `audit_logs`.

Nie wchodzi w zakres:

- personalny panel moderatora.

### Sprint 5: Personalny Panel Moderatora

Status: `[x]`

Cel:

- stworzyć indywidualny panel moderatora bez dostępu do pełnej administracji.

Zakres:

- dodać osobny panel albo osobną sekcję moderatorską,
- ograniczyć widoczność wyłącznie do kont utworzonych przez danego moderatora,
- pokazać licznik wykorzystania puli,
- pokazać listę własnych kont,
- pokazać statusy `wymaga przejęcia`, `aktywne`, `wygasłe`, `zablokowane`,
- nie pokazywać kont innych moderatorów.

Kryteria akceptacji:

- moderator nie widzi pełnej listy użytkowników,
- moderator nie widzi kont innych moderatorów,
- moderator widzi wyłącznie własną pulę,
- administrator nadal widzi całość.

Zrealizowane decyzje techniczne:

- dodano osobną trasę `/moderator/konta` jako personalny panel moderatora,
- dodano middleware `moderator.panel`, który wpuszcza wyłącznie moderatora albo administratora,
- moderator po wejściu w panel widzi tylko konta z `moderator_owner_id` równym jego `id`,
- administrator może użyć tego samego widoku jako przeglądu wszystkich kont moderatorskich, a pełną administrację nadal obsługuje panel `/admin`,
- panel pokazuje limit puli, wykorzystanie, liczbę pozostałych kont i podsumowanie statusów,
- status konta jest liczony centralnie: `wymaga przejęcia`, `aktywne`, `wygasłe`, `zablokowane`,
- `/dashboard` kieruje moderatora do panelu moderatora, zamiast do zwykłego panelu nauki,
- tworzenie kont pełnych i tymczasowych pozostaje w `Sprincie 6`.

Nie wchodzi w zakres:

- pełny flow tworzenia kont, jeśli panel ma być najpierw szkieletem.

### Sprint 6: Tworzenie Kont Przez Moderatora

Status: `[x]`

Cel:

- umożliwić moderatorowi tworzenie kont pełnych i tymczasowych zgodnie z polityką dostępu.

Zakres:

- formularz `znam e-mail`,
- formularz `nie znam e-maila`,
- generowanie technicznego loginu dla kont tymczasowych,
- hasło startowe pokazane tylko raz,
- wymuszona zmiana hasła przy pierwszym logowaniu,
- standardowa weryfikacja e-mail dla kont pełnych,
- przejęcie konta tymczasowego przez użytkownika,
- dostęp `90 dni`,
- wygasanie nieprzejętych kont tymczasowych po `14 dniach`,
- kolizja z istniejącym e-mailem bez tworzenia duplikatu.

Kryteria akceptacji:

- moderator nie może utworzyć ponad swoją pulę,
- istniejący e-mail nie tworzy duplikatu,
- pełne konto nie wejdzie do produktu bez zmiany hasła i weryfikacji e-mail,
- tymczasowe konto nie wejdzie do produktu bez przejęcia,
- stare hasło startowe nie jest ponownie widoczne.

Zrealizowane decyzje techniczne:

- panel `/moderator/konta` pozwala moderatorowi utworzyć konto pełne `znam e-mail` albo konto tymczasowe `nie znam e-maila`,
- formularz tworzenia konta jest dostępny tylko dla moderatora i blokowany po wykorzystaniu puli,
- konto pełne wymaga unikalnego e-maila, wybranej kategorii i zmiany hasła startowego przy pierwszym logowaniu,
- konto tymczasowe dostaje techniczny login `@moderator.local`, termin przejęcia po `14 dniach` i nie wymaga znajomości prawdziwego e-maila na etapie tworzenia,
- hasło startowe jest generowane automatycznie, przechowywane wyłącznie jako hash i pokazywane moderatorowi tylko w następnym widoku po utworzeniu konta,
- każde konto utworzone przez moderatora dostaje grant `moderator_grant` na `90 dni`,
- resolver dostępu blokuje konta tymczasowe bez przejęcia i konta wymagające zmiany hasła przed sprawdzaniem grantów produktu,
- przejęcie konta tymczasowego wymaga prawdziwego e-maila, aktualnego hasła startowego i ustawienia nowego hasła,
- po przejęciu konta system wysyła standardową weryfikację e-mail,
- wygasłe konto tymczasowe nie może się zalogować,
- kolizje e-maili są blokowane przed utworzeniem duplikatu, z normalizacją wielkości liter,
- kluczowe operacje zapisują audyt `moderator.account_created`, `user.start_password_changed` i `moderator.temporary_account_claimed`.

Nie wchodzi w zakres:

- Google i Facebook.

### Sprint 6A: Mail Startowy I Regeneracja Hasła Moderatora

Status: `[x]`

Cel:

- uspójnić konto pełne `znam e-mail` tak, żeby użytkownik automatycznie dostał dane startowe na skrzynkę, a moderator miał kontrolowany fallback bez możliwości podejrzenia starego hasła.

Zakres:

- osobna notyfikacja mailowa dla pełnego konta moderatorskiego,
- mail zawiera login, hasło startowe, kategorię, link logowania i podpisany link weryfikacyjny,
- mail startowy nie zastępuje globalnego `VerifyEmail` i nie zmienia zwykłej rejestracji,
- kliknięcie linku weryfikacyjnego z maila startowego potwierdza e-mail standardowym signed route,
- panel nadal pokazuje login i hasło tylko raz jako kopię awaryjną,
- panel pokazuje, czy mail startowy został wysłany,
- jeśli mail nie dotrze albo hasło przepadnie, moderator może wygenerować nowe hasło startowe i wysłać nowy mail,
- regeneracja jest dostępna tylko dla pełnych kont własnej puli z `requires_password_change = true`,
- po zmianie hasła przez użytkownika moderator nie może już regenerować hasła w tym flow,
- standardowy reset hasła po skutecznym ustawieniu nowego hasła czyści `requires_password_change`.

Kryteria akceptacji:

- pełne konto moderatora dostaje mail startowy,
- konto tymczasowe moderatora nie dostaje maila startowego,
- wysłanie maila nie oznacza automatycznej weryfikacji e-maila,
- e-mail zostaje potwierdzony dopiero po kliknięciu podpisanego linku,
- standardowa rejestracja nadal wysyła zwykły mail weryfikacyjny,
- social login nadal zachowuje dotychczasową politykę zaufania e-maila,
- niezaufany social nadal używa standardowej weryfikacji e-mail,
- social login nie niszczy grantu moderatorskiego,
- stare hasło startowe nie jest możliwe do podejrzenia ani po utworzeniu, ani po regeneracji,
- po regeneracji stare hasło nie działa, nowe hasło działa,
- moderator nie może regenerować hasła konta innego moderatora,
- student nie ma dostępu do endpointu regeneracji,
- audyt nie zapisuje hasła, tokenów ani podpisanych linków.

Dokument wdrożeniowy:

- `docs/MODERATOR-ACCOUNT-EMAIL-START-FLOW.md`.

### Sprint 7: Social Login I Łączenie Kont

Status: `[x]`

Cel:

- dodać Google i Facebook bez omijania kategorii, paywalla i wyjątków moderatorskich.

Zakres:

- redirect i callback dla Google,
- redirect i callback dla Facebooka,
- wspólny resolver konta po OAuth,
- tabela powiązań providerów,
- linkowanie po stabilnym ID providera,
- bezpieczne pierwsze dopasowanie po e-mailu,
- ręczne podpinanie i odpinanie providera,
- obsługa braku e-maila od providera.

Kryteria akceptacji:

- social login nie tworzy duplikatu dla istniejącego konta,
- social login nie omija paywalla,
- social login nie omija wyboru kategorii,
- konto moderatorskie zachowuje grant i kategorię po podpięciu providera,
- nie można odpiąć ostatniej metody logowania.

Zrealizowane decyzje techniczne:

- dodano własny cienki klient OAuth dla providerów `google` i `facebook`, bez dokładania zależności Composer,
- konfiguracja providerów znajduje się w `config/services.php` i `.env.example`,
- dodano tabelę `user_social_accounts` z unikalnym powiązaniem `provider + provider_user_id` oraz `user_id + provider`,
- dodano flagę `password_login_enabled`, żeby odróżnić konto z realną metodą hasłową od konta utworzonego tylko przez social login,
- nowe konto społecznościowe może powstać tylko wtedy, gdy użytkownik wybrał kategorię nauki przed rejestracją,
- konto utworzone przez social login dostaje zablokowaną kategorię i nie dostaje automatycznego dostępu do produktu,
- istniejące konto jest linkowane po e-mailu bez tworzenia duplikatu,
- callback OAuth przechodzi przez standardowy resolver po logowaniu, więc nie omija paywalla, blokad konta, weryfikacji stanu i wyjątków moderatorskich,
- brak e-maila, brak stabilnego ID providera albo niepotwierdzony e-mail od providera blokuje logowanie społecznościowe,
- profil użytkownika pokazuje Google i Facebook jako metody logowania do podpięcia albo odpięcia,
- nie można odpiąć ostatniego providera, jeśli konto nie ma aktywnego logowania hasłem,
- ustawienie albo reset hasła ponownie aktywuje lokalną metodę logowania,
- trasy social loginu i profilu zostały dopisane do grupy Ziggy `app`, żeby frontend mógł bezpiecznie używać `route(...)`,
- operacje społecznościowe zapisują audyt `social.account_registered`, `social.account_linked` i `social.account_unlinked`.

Nie wchodzi w zakres:

- checkout.

### Sprint 8: Wybór Operatora I Checkout

Status: `[~]`

Cel:

- podpiąć wybraną metodę płatności jako źródło aktywnego prawa dostępu.

Warunek startu:

- operator płatności jest wybrany,
- decyzje z sekcji `Jakie Pytania Trzeba Zamknąć Przed Sprintem Płatności` są zamknięte.

Stan po wejściu w sprint:

- finalny operator płatności nadal nie jest wybrany,
- dlatego realizujemy bezpieczny fundament checkoutu niezależny od operatora,
- integracja produkcyjna z konkretną bramką, webhooki operatora i polityka zwrotów pozostają otwarte.

Decyzje produktowe zamknięte:

- na start mają istnieć trzy warianty dostępu czasowego,
- `Dostęp na 1 miesiąc`,
- `Dostęp na 3 miesiące`,
- `Dostęp na rok`,
- dostęp self-service jest więc czasowy i wynika z wybranego planu,
- ceny brutto ustawiamy uczciwie względem rynku i naszej przewagi technologicznej: `39 zł`, `69 zł`, `149 zł`.

Decyzja cenowa po analizie konkurencji:

- najtańsze aplikacje testowe są zwykle w przedziale około `15-39 zł` za dostęp miesięczny albo trzymiesięczny,
- segment premium z wykładami live, silną marką i zapleczem OSK potrafi kosztować około `79-199 zł`,
- nie konkurujemy ceną z najtańszymi aplikacjami, bo produkt ma mocniejsze narzędzia nauki, powtórki, statystyki, analizę błędów i tryb rankingowy,
- nie ustawiamy też cen jak pełne kursy z wykładami live, bo na tym etapie sprzedajemy dostęp do technologicznej platformy nauki, a nie pełny pakiet instruktorski,
- rekomendowany plan sprzedażowy to `Dostęp na 3 miesiące` za `69 zł`, bo daje najlepszy balans czasu, ceny i wartości.

Zakres:

- finalny ekran planów,
- checkout,
- powrót z płatności,
- webhooki,
- aktywacja dostępu po zakupie,
- obsługa płatności nieudanej,
- obsługa zwrotu albo cofnięcia płatności, jeśli wymagane,
- audyt źródła dostępu `zakup`.

Kryteria akceptacji:

- udana płatność aktywuje właściwe konto,
- powrót z checkoutu nie tworzy duplikatu,
- brak webhooka nie daje fałszywego dostępu,
- zwrot albo cofnięcie płatności działa zgodnie z ustaloną polityką,
- grant moderatorski i zakup poprawnie współistnieją.

Zrealizowane decyzje techniczne:

- dodano model planów `product_plans`, który pozwala mieć wiele aktywnych planów bez wiązania ich z konkretnym operatorem,
- dodano model zamówień `purchase_orders` z publicznym identyfikatorem, statusem, providerem, kwotą, walutą i czasem dostępu,
- dodano powiązanie `purchase_order_id` w `product_access_grants`, żeby grant `purchase` był audytowalnie połączony z zamówieniem,
- dodano publiczny ekran `/cennik`, który pokazuje trzy komponenty planów: `1 miesiąc`, `3 miesiące`, `rok`,
- `/cennik` jest renderowany jako publiczny widok Blade na layoucie SEO, żeby treść, meta description, canonical, Open Graph i JSON-LD były widoczne bez uruchamiania JavaScriptu,
- checkout jest aktywny tylko dla planów realnie dostępnych w bazie z ceną; warianty bez ustalonej ceny są pokazane jako przygotowane, ale nie prowadzą jeszcze do płatności,
- użytkownik bez aktywnego dostępu może utworzyć zamówienie w statusie `pending`,
- samo wejście na checkout albo powrót do zamówienia nie aktywuje dostępu,
- dostęp aktywuje dopiero serwerowe potwierdzenie płatności przez `ProductCheckoutService::markPaid`,
- lokalnie dostępny jest provider `sandbox`, który symuluje potwierdzenie płatności do czasu wyboru finalnej bramki,
- potwierdzenie tej samej płatności jest idempotentne i nie tworzy wielu grantów,
- użytkownik nie może podejrzeć ani opłacić zamówienia innego użytkownika,
- konto z już aktywnym dostępem omija checkout i wraca do produktu,
- operacje zapisują audyt `purchase.order_created`, `purchase.order_paid` i `purchase.order_canceled`.

Nie wchodzi jeszcze w zakres:

- produkcyjna integracja z wybranym operatorem,
- produkcyjne webhooki konkretnej bramki,
- faktury i dane rozliczeniowe,
- automatyczne zwroty i cofnięcie dostępu po chargebacku.

### Sprint 9: Stabilizacja, Migracja I Regresja

Status: `[x]`

Cel:

- posprzątać stary model wielokategorialny i przygotować rollout bez niespodzianek.

Zakres:

- migracja istniejących użytkowników bez kategorii,
- decyzja, co zrobić z istniejącymi postępami wielu kategorii,
- aktualizacja testów opartych o stary model przełączania kategorii,
- regresja tras produktu i API,
- regresja panelu admina,
- regresja panelu moderatora,
- testy końcowego flow self-service i moderatorskiego,
- checklisty release.

Decyzja migracyjna dla lokalnej bazy:

- bazę użytkowników czyścimy z kont testowych i ich historii nauki,
- zawsze zostają `smoke@local.test` i `test-user@local.test`,
- dodatkowo zostaje każde konto powiązane z treścią redakcyjną, żeby nie zerwać autorstwa, recenzji ani historii edycji materiałów,
- w aktualnej bazie redakcyjnie chronione są też `admin@example.com` i `test-admin@local.test`,
- konta usuwane tracą swoje profile, sesje nauki, postępy, kolejki rankingowe, zamówienia, granty, social accounts i historię IP zgodnie z relacjami kaskadowymi,
- wpisy audytowe powiązane wyłącznie z usuwanymi kontami mogą zostać usunięte albo wyzerowane zgodnie z kluczami obcymi, bo nie są potrzebne do dalszej pracy.

Wynik lokalnego czyszczenia:

- usunięto `39` zbędnych kont testowych,
- usunięto powiązane wpisy audytowe wykonywane przez usuwane konta,
- w bazie lokalnej zostały `4` konta: `smoke@local.test`, `test-user@local.test`, `admin@example.com`, `test-admin@local.test`,
- `admin@example.com` zostało zachowane, bo jest przypięte do `question_explanation_assets`,
- `test-admin@local.test` zostało zachowane, bo jest przypięte do `question_explanation_annotations`,
- nie wykryto kont użytkowników przypiętych jako recenzenci znaków drogowych,
- autorzy znaków drogowych pozostają w osobnej tabeli `content_authors`, więc czyszczenie użytkowników ich nie narusza.

Wynik audytu po czyszczeniu:

- wszystkie `4` pozostawione konta mają rekord profilu,
- `test-user@local.test` zostaje zwykłym kursantem z aktywnym dostępem zakupowym i przypisaną kategorią,
- `admin@example.com`, `smoke@local.test` i `test-admin@local.test` są kontami systemowymi, więc mogą mieć dostęp do wszystkich kategorii,
- brak zwykłych kont bez kategorii po lokalnym czyszczeniu,
- wygasłe, nieprzejęte konto tymczasowe moderatora nie liczy się już do wykorzystanej puli i zwalnia slot.

Wynik regresji Sprintu 9:

- `68` testów i `461` asercji zakończone sukcesem,
- pakiet objął rejestrację, logowanie, social login, twardą kategorię, paywall, resolver dostępu, panel moderatora, panel admina i checkout,
- dodano regresję dla odebrania roli moderatora przez admina,
- dodano regresję dla zachowania grantu moderatorskiego po social login,
- dodano regresję dla zwalniania slotu przez wygasłe, nieprzejęte konto tymczasowe.

Dodatkowa stabilizacja shelli po regresji UI:

- `/cennik` z publicznego headera i footera Vue wymusza pełną nawigację dokumentu, bo publiczny cennik jest widokiem Blade/SEO, a nie stroną Inertia,
- wylogowanie w headerze Vue działa przez klasyczny formularz `POST` z tokenem CSRF,
- `GET /logout` nie wykonuje wylogowania; pokazuje bezpieczny ekran
  potwierdzenia, a zakończenie sesji odbywa się dopiero przez `POST /logout`.

Kryteria akceptacji:

- istniejące konta mają spójny stan kategorii i dostępu,
- produkt nie ma publicznego obejścia bramy dostępu,
- testy pokrywają rejestrację, logowanie, kategorię, paywall, moderatora, social login i zakup,
- dokumentacja jest zgodna z rzeczywistym zachowaniem aplikacji.

## Tracker Postępu

Ten tracker można aktualizować przy planowaniu i po każdym sprincie.

- [x] `Sprint 1` fundament ról, stanu konta i dostępu
- [x] `Sprint 2` resolver po logowaniu i brama dostępu
- [x] `Sprint 3` twarda kategoria dla kursanta
- [x] `Sprint 4` panel administratora dla ról i kategorii
- [x] `Sprint 5` personalny panel moderatora
- [x] `Sprint 6` tworzenie kont przez moderatora
- [x] `Sprint 6A` mail startowy i regeneracja hasła moderatora
- [x] `Sprint 7` social login i łączenie kont
- [~] `Sprint 8` wybór operatora i checkout
- [x] `Sprint 9` stabilizacja, migracja i regresja

## Matryca Weryfikacji Postępu

Poniższe scenariusze powinny być używane jako główna lista kontroli rozwoju.

- [x] zwykły użytkownik po rejestracji musi potwierdzić e-mail,
- [x] zwykły użytkownik musi mieć przypisaną kategorię,
- [x] zwykły użytkownik bez dostępu trafia na ekran aktywacji dostępu,
- [x] zwykły użytkownik bez dostępu nie uruchamia nauki przez API,
- [x] zwykły użytkownik nie może zmienić kategorii samodzielnie,
- [x] administrator może nadać i odebrać rolę moderatora,
- [x] administrator może zwiększyć pulę moderatora,
- [x] moderator widzi tylko własne konta,
- [x] moderator nie widzi kont innych moderatorów,
- [x] moderator nie tworzy kont ponad swoją pulę,
- [x] konto pełne moderatora wymaga zmiany hasła i weryfikacji e-mail,
- [x] konto pełne moderatora wysyła mail startowy z loginem, hasłem startowym i podpisanym linkiem weryfikacyjnym,
- [x] mail startowy moderatora nie zmienia globalnego `VerifyEmail` ani standardowej rejestracji,
- [x] mail startowy moderatora nie jest wysyłany dla kont tymczasowych `@moderator.local`,
- [x] moderator może wygenerować nowe hasło startowe tylko przed przejęciem konta przez użytkownika,
- [x] regeneracja hasła startowego unieważnia stare hasło i pokazuje nowe hasło tylko raz,
- [x] standardowy reset hasła czyści `requires_password_change`,
- [x] konto tymczasowe wymaga przejęcia przez użytkownika,
- [x] konto tymczasowe nieprzejęte przez `14 dni` wygasa i zwalnia slot,
- [x] hasło startowe nie jest później możliwe do podejrzenia,
- [x] istniejący e-mail w flow moderatora nie tworzy duplikatu,
- [x] social login nie omija paywalla,
- [x] social login nie omija kategorii,
- [x] social login nie niszczy grantu moderatorskiego,
- [x] zakup i grant mogą współistnieć bez utraty dostępu,
- [x] konto zablokowane nie wchodzi do produktu niezależnie od źródła dostępu.

## Zależności Między Sprintami

Niektórych sprintów nie warto zaczynać przed domknięciem wcześniejszych etapów.

Twarde zależności:

- `Sprint 2` wymaga fundamentu dostępu ze `Sprintu 1`,
- `Sprint 3` wymaga rozróżnienia wyjątków systemowych ze `Sprintu 1`,
- `Sprint 5` wymaga roli moderatora ze `Sprintu 4`,
- `Sprint 6` wymaga panelu moderatora ze `Sprintu 5`,
- `Sprint 7` wymaga resolvera po logowaniu ze `Sprintu 2`,
- `Sprint 8` wymaga modelu prawa dostępu ze `Sprintu 1` i bramy dostępu ze `Sprintu 2`,
- `Sprint 9` powinien zamknąć cały zakres przed releasem.

Możliwe prace równoległe:

- projekt UI ekranu aktywacji dostępu może iść równolegle do `Sprintu 1`,
- przygotowanie publicznego `/cennik` może iść równolegle do wcześniejszych sprintów,
- decyzja o operatorze płatności może być przygotowywana równolegle, ale implementacja checkoutu powinna zaczekać do `Sprintu 8`,
- testy regresji można dopisywać od pierwszego sprintu, zamiast zostawiać je na koniec.

## Decyzje Otwarte W Sprincie Płatności

Te tematy nie blokują fundamentu checkoutu, ale muszą zostać zamknięte przed produkcyjną integracją operatora.

- [ ] wybrany operator płatności,
- [x] typ dostępu self-service: czasowy dostęp wynikający z planu,
- [x] liczba planów na start: trzy warianty czasowe,
- [x] czas trwania dostępu po zakupie: `1 miesiąc`, `3 miesiące`, `rok`,
- [x] ceny brutto dla planów: `1 miesiąc` = `39 zł`, `3 miesiące` = `69 zł`, `rok` = `149 zł`,
- [ ] faktury i dane rozliczeniowe,
- [ ] obsługa BLIK, kart i szybkich przelewów,
- [ ] polityka zwrotów i cofnięcia dostępu,
- [~] komunikaty po płatności udanej i oczekującej są obsłużone w sandboxie, nieudana płatność zależy jeszcze od operatora.

## Wniosek

Twarda kategoria nauki jest możliwa i produktowo ma sens, ale nie jest to kosmetyczna poprawka. To zmiana modelu działania aplikacji.

Najważniejszy wniosek brzmi:

- jeśli chcemy naprawdę zablokować użytkownika do jednej kategorii, musimy zrobić to jako regułę systemową w rejestracji, UI, API, sesjach i administracji,
- blokada powinna obejmować kursanta, ale nie może utrudniać pracy administratorowi, moderatorowi ani kontu testowemu,
- wyjątki od blokady powinny być opisane jawnie w modelu dostępu, a nie dopinane przypadkowo do samego `is_admin`,
- rejestracja własna i prawo dostępu do produktu muszą zostać rozdzielone, bo samo założenie konta nie powinno już oznaczać pełnej nauki,
- użytkownik dodany przez moderatora musi być jawnym wyjątkiem od wymogu zakupu i ten wyjątek powinien mieć własną ścieżkę w modelu danych oraz logice dostępu,
- moderatorski wyjątek to nie tylko grant dostępu, ale osobny flow provisioningu konta z limitem `30` kont na moderatora, z możliwością utworzenia konta tymczasowego oraz z obowiązkowym przejęciem takiego konta przez użytkownika,
- jeśli administracja ma móc zmieniać kategorię, najbezpieczniejsza jest polityka resetu danych nauki,
- samo schowanie dropdownów nie rozwiąże problemu.

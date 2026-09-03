# Ficzer "Zaproś znajomego" — specyfikacja koncepcyjna

Status: implementacja MVP w toku  
Branch roboczy: `feature/invite-friend`  
Wersja: 2.3  
Data: 2026-05-26

Aktualny stan: fundament danych, serwisy reguł, generowanie linku/kodu, akceptacja zaproszenia, dostęp gościa, automatyczne wygaszanie, konwersja gościa po zakupie własnego planu, efekty refundu właściciela, podstawowy UI w profilu, landing zaproszenia, CTA po checkout sukcesie, CTA na `/nauka`, mail refundowy dla gościa oraz read-only podgląd zaproszeń w Filamencie są zaimplementowane na branchu. Refund ma już centralny serwis aplikacyjny, ale nadal wymaga podpięcia do docelowego webhooka/operatora płatności, gdy wybierzemy realny proces płatniczy.

## Cel biznesowy

Ficzer ma nagrodzić użytkowników lojalnych planów możliwością wspólnej nauki ze znajomym. Nie budujemy funkcji społecznościowej, czatu ani relacji znajomych w aplikacji. Zakres jest wąski: właściciel wybranego płatnego planu może udostępnić jeden dodatkowy dostęp Premium.

## Plany uprawniające

Prawo do zaproszenia mają wyłącznie właściciele aktywnych płatnych planów:

- `start-90` — 3 miesiące / spokojna nauka,
- `start-365` — rok / Premium.

Plan miesięczny `start-30` nie daje prawa do zaproszenia.

Prawo do zapraszania nie wynika z dostępu systemowego, konta testowego, roli admina, roli moderatora ani dostępu gościa. Musi wynikać z aktywnego grantu zakupowego (`ProductAccessGrant` ze źródłem `purchase`) powiązanego z zakupem planu `start-90` albo `start-365`.

## Decyzje doprecyzowane

### Link i kod zapraszają do slotu, nie do konkretnego e-maila

Zaproszenie nie jest przypisane do adresu e-mail konkretnej osoby. Właściciel generuje link i odpowiadający mu kod, a następnie sam decyduje, gdzie je wyśle: SMS, Messenger, e-mail, rozmowa prywatna itp.

Konsekwencja: kto pierwszy poprawnie zaakceptuje aktywny link albo kod i spełni warunki dostępu, ten zajmuje slot gościa. Jeśli link wycieknie poza zamierzony kanał, może zostać użyty przez inną osobę. Jest to świadomy kompromis na rzecz prostoty i niskiego tarcia.

### Istniejące konto może przyjąć zaproszenie

Zaproszenie może przyjąć:

- nowy użytkownik podczas rejestracji,
- istniejący użytkownik bez aktywnego Premium,
- istniejący użytkownik z wygasłym dostępem.

Zaproszenia nie może przyjąć użytkownik, który ma już jakikolwiek aktywny dostęp Premium: własny płatny, systemowy liczący się jako pełny dostęp produktowy, moderatorski albo aktywny dostęp jako gość innej osoby.

Komunikat dla gościa:

> Posiadasz już aktywny dostęp Premium. Nie możesz przyjąć tego zaproszenia.

### Refund jest osobnym procesem biznesowym

Aktualny kod ma `PurchaseOrder::STATUS_REFUNDED` i pole `refunded_at`, ale refund musi być obsłużony przez jeden kontrolowany proces aplikacyjny. Ten proces powinien jednocześnie:

- oznaczyć zamówienie właściciela jako zwrócone,
- unieważnić wszystkie oczekujące zaproszenia właściciela,
- skrócić aktywny dostęp gościa do 7 dni od momentu przetworzenia refundu,
- zapisać audyt zdarzenia.

Definicja przetworzenia refundu: moment zatwierdzenia zwrotu przez system płatności albo operatora wewnętrznego.

Komunikat dla gościa:

> Niestety, osoba zapraszająca Cię dokonała zwrotu pieniędzy za swój plan. Z tego powodu Twój dostęp jako gościa wygasa za 7 dni — dokładnie [data wygaśnięcia]. Dziękujemy, że skorzystałeś/aś z możliwości wspólnej nauki.

### Anulowanie mapujemy na obecny model jednorazowego dostępu

Aktualny system wygląda jak jednorazowy zakup dostępu czasowego, a nie klasyczna odnawialna subskrypcja. Dlatego "koniec bieżącego okresu rozliczeniowego" oznacza obecnie `expires_at` aktywnego `ProductAccessGrant` właściciela.

Jeśli w przyszłości pojawią się prawdziwe subskrypcje z auto-odnawianiem, źródło daty można będzie zmienić na bieżący okres z obiektu subskrypcji.

### Akceptacja musi być transakcyjna

Akceptacja zaproszenia jest krytycznym punktem współbieżności. Musi działać w transakcji i blokować właściwe rekordy tak, żeby dwie osoby nie mogły jednocześnie zająć jednego slotu.

Minimalna zasada:

- zablokować rekord zaproszenia,
- zablokować aktywne/pending zaproszenia właściciela albo inny rekord reprezentujący slot,
- ponownie sprawdzić, czy właściciel nadal ma wolny slot,
- ponownie sprawdzić, czy gość nie ma aktywnego Premium,
- utworzyć dostęp gościa,
- oznaczyć przyjęte zaproszenie jako `accepted`,
- unieważnić pozostałe pending zaproszenia właściciela.

## Zasady działania

### Jeden aktywny gość

Właściciel planu może mieć maksymalnie jednego aktywnego gościa naraz.

### Limit pending zaproszeń

Właściciel może mieć maksymalnie 10 aktywnych oczekujących zaproszeń jednocześnie.

Komunikat błędu dla właściciela:

> Osiągnąłeś limit oczekujących zaproszeń (maksymalnie 10). Poczekaj, aż znajomy zaakceptuje jedno z nich, lub unieważnij stare zaproszenie w panelu, aby móc wygenerować nowe.

### Niezmienność po akceptacji

Do momentu akceptacji właściciel może unieważnić oczekujące zaproszenie. Po akceptacji slot jest trwale zajęty do końca okresu dostępu gościa. Nie można zamienić gościa na inną osobę.

### Czas dostępu gościa

W momencie akceptacji zaproszenia system zapisuje `guest_access_expires_at` jako aktualny koniec dostępu właściciela. Ta data jest później stała i nie przedłuża się automatycznie, nawet jeśli właściciel przedłuży swój plan.

Przykład: właściciel ma plan ważny do 31 grudnia 2026. Gość akceptuje zaproszenie 1 grudnia 2026. Dostęp gościa kończy się 31 grudnia 2026.

### Przedłużenie planu właściciela

Przedłużenie planu przez właściciela nie przedłuża automatycznie dostępu aktualnego gościa. Po wygaśnięciu dostępu gościa slot staje się wolny i właściciel może wygenerować nowe zaproszenie.

### Refund

Refund właściciela:

- natychmiast unieważnia pending zaproszenia,
- skraca aktywny dostęp gościa do 7 dni od przetworzenia refundu,
- po 7 dniach gość traci dostęp.

### Brak równoległych aktywnych dostępów

Użytkownik nie może przyjąć zaproszenia, jeśli ma aktywny dostęp Premium z dowolnego źródła. Link/kod pozostaje pending i może zostać użyty przez inną osobę, dopóki nie wygaśnie albo nie zostanie unieważniony.

### Gość nie może zapraszać

Prawo do zapraszania mają tylko właściciele płatnych planów `start-90` i `start-365`. Dostęp gościa nie daje prawa do wygenerowania kolejnego zaproszenia.

### Nie można zaprosić aktywnego gościa

Jeśli użytkownik jest aktualnie aktywnym gościem w czyimś planie, nie może przyjąć kolejnego zaproszenia.

## Flow użytkownika

## Umiejscowienie w aplikacji

### Główne miejsce zarządzania

Główne miejsce dla właściciela planu: konto użytkownika / sekcja dostępu Premium.

Proponowana ścieżka w UI:

- Konto,
- Dostęp Premium,
- Zaproś znajomego.

W tej sekcji właściciel powinien widzieć:

- czy jego aktualny plan daje prawo do zaproszenia,
- czy slot gościa jest wolny,
- ile ma aktywnych pending zaproszeń,
- przycisk "Wygeneruj zaproszenie",
- wygenerowany link i kod,
- akcje "Kopiuj link" i "Kopiuj kod",
- listę pending zaproszeń,
- możliwość unieważnienia pending zaproszenia,
- informację o zaakceptowanym gościu i dacie końca jego dostępu.

Przykład statusu po akceptacji:

> Slot zajęty do 31.12.2026.

Link nie powinien generować się automatycznie po zakupie planu. Właściciel powinien świadomie kliknąć "Wygeneruj zaproszenie", ponieważ link/kod działa na zasadzie "kto pierwszy poprawnie zaakceptuje, ten zajmuje slot".

### Skrót na ekranie nauki

Na ekranie `/nauka` można pokazać mały, nienachalny box tylko użytkownikom z kwalifikującym planem `start-90` albo `start-365`.

Przykładowa treść:

> Masz możliwość zaproszenia jednej osoby do wspólnej nauki.

CTA:

> Zaproś znajomego

Przycisk powinien prowadzić do głównej sekcji zarządzania zaproszeniami w koncie, a nie generować link od razu.

### Komunikat po zakupie

Na ekranie sukcesu checkoutu dla planów `start-90` i `start-365` warto pokazać informację:

> Twój plan pozwala zaprosić jedną osobę do wspólnej nauki.

CTA:

> Przejdź do zaproszenia

Ten przycisk również prowadzi do sekcji zarządzania zaproszeniami.

### Wejście dla osoby zaproszonej

Osoba zaproszona może wejść do flow dwoma sposobami:

- przez link, np. `/zaproszenie/{token}`,
- przez ręczne wpisanie kodu na stronie `/zaproszenie`.

Na ekranie logowania/rejestracji warto dodać mały link:

> Mam kod zaproszenia

Ten link prowadzi do strony wpisania kodu. Jest to fallback dla sytuacji, w których ktoś dostał kod SMS-em, przez rozmowę albo link nie otworzył się poprawnie.

### Generowanie zaproszenia

Właściciel w panelu generuje zaproszenie, jeśli:

- ma aktywny kwalifikujący plan,
- nie ma aktywnego gościa,
- ma mniej niż 10 pending zaproszeń.

System zapisuje rekord `pending`, generuje link i kod.

### Landing zaproszenia

Osoba z linkiem trafia na dedykowaną stronę z informacjami:

- kto zaprasza,
- co dostaje,
- do kiedy dostęp będzie ważny,
- ostrzeżenie, że nie może mieć aktywnego Premium ani być gościem innej osoby.

### Akceptacja

Gość klika "Akceptuj i zacznij", a następnie rejestruje się albo loguje. Po poprawnym uwierzytelnieniu system próbuje zaakceptować zaproszenie w transakcji.

Jeśli slot jest już zajęty, pokazujemy:

> Niestety, to zaproszenie jest już nieaktywne. Slot został zajęty przez innego znajomego.

### Wygaśnięcie zaproszenia

Pending zaproszenie wygasa po 14 dniach od wygenerowania. Status przechodzi na `expired` i zwalnia miejsce w limicie 10 oczekujących zaproszeń.

## Aktualne punkty integracji w kodzie

Obszar dostępu Premium jest obecnie oparty o:

- `App\Models\ProductPlan`,
- `App\Models\PurchaseOrder`,
- `App\Models\ProductAccessGrant`,
- `App\Support\ProductAccessResolver`,
- middleware `product.access`,
- trasy checkout i `/aktywuj-dostep`.

Najbardziej naturalnym sposobem wdrożenia jest potraktowanie gościa jako użytkownika z normalnym rekordem `ProductAccessGrant`, ale z osobnym źródłem, np. `invitation_guest`. Dzięki temu istniejące middleware i większość mechaniki dostępu mogą pozostać wspólne.

## Wnioski z głębokiego przebiegu kodu

### Dostęp Premium

Aktualny dostęp do produktu rozstrzyga `App\Support\ProductAccessResolver`. Kolejność źródeł jest obecnie:

1. blokady konta, konto tymczasowe, wymuszona zmiana hasła,
2. dostęp systemowy dla admina/moderatora/konta testowego,
3. aktywny zakup (`purchase`),
4. dostęp moderatorski (`moderator_grant`),
5. brak dostępu.

Dla tego ficzera trzeba dodać nowe źródło dostępu, np. `ProductAccessGrant::SOURCE_INVITATION_GUEST = 'invitation_guest'`.

Rekomendowana kolejność po zmianie:

1. blokady konta,
2. system,
3. purchase,
4. moderator grant,
5. invitation guest,
6. missing access.

Taka kolejność pozwala nadal preferować własny zakup nad statusem gościa, jeśli w przyszłości dopuścimy zakup przez aktywnego gościa.

### Zakupy i przedłużenia

Obecny checkout (`CheckoutController::store`) blokuje start zakupu, jeśli `ProductAccessResolver` zwraca aktywny dostęp. To oznacza, że w dzisiejszym kodzie aktywny użytkownik nie może przedłużyć planu przed końcem obecnego dostępu.

Konsekwencja dla specyfikacji:

- scenariusz "przedłużenie planu właściciela" jest biznesowo opisany, ale technicznie nie istnieje jeszcze w aplikacji,
- dostęp gościa i tak powinien zapisywać własne `guest_access_expires_at` na sztywno,
- późniejsze wdrożenie prawdziwych odnowień nie powinno automatycznie przedłużać istniejącego gościa.

Decyzja wdrożeniowa:

> Aktywny gość może kupić własny plan przed końcem dostępu gościa.

Po opłaceniu własnego planu:

- aktywny grant `invitation_guest` zostaje zakończony,
- zaakceptowane zaproszenie dostaje status techniczny `converted`,
- slot pierwotnego właściciela zwalnia się od razu,
- użytkownik działa dalej na własnym płatnym grancie `purchase`.

To realizuje biznesową intencję, że osoba zaproszona może zostać płacącym właścicielem planu, a jednocześnie nie tworzymy dwóch równoległych aktywnych dostępów Premium.

### Refundy

W modelu istnieją `PurchaseOrder::STATUS_REFUNDED` i `refunded_at`, ale nie ma osobnego serwisu refundów ani webhooka operatora płatności. Refund zaproszenia nie powinien być rozproszony po kontrolerach.

Potrzebny będzie jeden proces aplikacyjny, np. `ProductRefundService`, który:

- blokuje zamówienie `lockForUpdate()`,
- oznacza zakup jako refunded,
- skraca aktywny dostęp gościa do 7 dni,
- unieważnia pending zaproszenia właściciela,
- zapisuje audyt.

Status implementacji: dodany został `App\Support\ProductRefundService`. To jest docelowy punkt wejścia dla webhooka płatności albo ręcznej akcji operatora. Serwis oznacza zamówienie jako `refunded`, cofa grant zakupowy właściciela, wywołuje efekty zaproszeń i zapisuje audyt.

### Auth flow zaproszonego

Standardowy login używa `redirect()->intended(...)`, więc może wrócić do zaproszenia po zalogowaniu.

Rejestracja (`RegisteredUserController::store`) kończy się przekierowaniem na `verification.notice`, a social login (`SocialAuthController::callback`) kończy się przekierowaniem na dashboard. Dlatego dla zaproszeń potrzebny jest jawny mechanizm sesyjny:

- landing zaproszenia zapisuje w sesji `friend_invitation.pending_id`,
- po rejestracji/loginie/OAuth aplikacja potrafi wrócić do ekranu akceptacji,
- aktywacja dostępu gościa następuje dopiero po uwierzytelnieniu i weryfikacji warunków.

Wymóg `verified`: akceptacja zaproszenia powinna wymagać zweryfikowanego adresu e-mail, tak jak reszta pełnego dostępu do produktu.

### Profil i UI właściciela

Profil (`Profile/Edit.vue`) ma już strukturę sekcji:

- dane konta,
- hasło,
- logowanie,
- usunięcie konta.

Najbezpieczniej dodać nową sekcję:

- dostęp Premium,
- zaproś znajomego.

Backendowo można to obsłużyć osobnym kontrolerem i payloadem, bez powiększania `ProfileController` o całą logikę zaproszeń.

### Testy i baza

Testy używają SQLite z osobną bazą na test. Produkcja działa na PostgreSQL.

Częściowe unikalne indeksy są bardzo przydatne do twardego zabezpieczenia:

- jeden zaakceptowany aktywny gość na właściciela,
- jeden aktywny status gościa na użytkownika.

Trzeba jednak pisać migracje tak, żeby testy SQLite nadal przechodziły. Jeśli użyjemy `DB::statement()` dla indeksów częściowych, trzeba uwzględnić kompatybilność SQLite albo warunkować SQL per driver.

## Proponowana struktura danych

Nowa tabela: `friend_invitations`.

Proponowane pola:

- `id`,
- `public_id` — publiczny identyfikator do URL-i,
- `owner_user_id` — właściciel płatnego planu,
- `owner_product_access_grant_id` — grant właściciela, z którego wynika prawo zapraszania,
- `owner_purchase_order_id` — zamówienie właściciela, jeśli istnieje,
- `product_plan_id` — plan właściciela w momencie wygenerowania,
- `status` — `pending`, `accepted`, `revoked`, `expired`, `converted`,
- `token_hash` — hash tokenu linku,
- `code_hash` — hash kodu ręcznego,
- `display_code_last4` — opcjonalny skrót do UI/audytu,
- `expires_at` — wygaśnięcie pending zaproszenia,
- `accepted_at`,
- `accepted_by_user_id` — użytkownik, który zajął slot,
- `guest_product_access_grant_id` — grant dostępu gościa,
- `guest_access_starts_at`,
- `guest_access_expires_at`,
- `refund_processed_at`,
- `refund_buffer_expires_at`,
- `converted_at`,
- `converted_purchase_order_id`,
- `revoked_at`,
- `revoked_reason`,
- `created_at`,
- `updated_at`.

Do `ProductAccessGrant` warto dodać nowe źródło:

- `invitation_guest`.

Opcjonalnie można dodać pole `metadata` do `ProductAccessGrant`, ale nie jest to konieczne, jeśli relacja z zaproszeniem jest jednoznaczna przez `friend_invitations.guest_product_access_grant_id`.

## Indeksy i ograniczenia do rozważenia

Potrzebne będą indeksy po:

- `owner_user_id`,
- `status`,
- `expires_at`,
- `accepted_by_user_id`,
- `guest_product_access_grant_id`,
- `owner_product_access_grant_id`.

Do rozważenia na poziomie bazy:

- częściowy unikalny indeks: jeden aktywnie zaakceptowany gość na właściciela,
- częściowy unikalny indeks: jeden aktywny grant gościa na użytkownika,
- unikalność `public_id`,
- unikalność hashy tokenu i kodu.

Ponieważ projekt używa PostgreSQL na produkcji, częściowe indeksy są dostępne i warto ich użyć dla twardego zabezpieczenia race condition.

## Otwarte decyzje przed implementacją

- Czy admin/moderator może ręcznie unieważnić zaakceptowanego gościa, czy tylko pending zaproszenia?
- Czy właściciel widzi e-mail/imie zaakceptowanego gościa po akceptacji?
- Czy gość widzi w UI, kto go zaprosił i do kiedy ma dostęp?
- Czy po wygaśnięciu dostępu gościa wysyłamy mail do właściciela, że slot jest wolny?
- Czy kod ręczny ma być krótki i przyjazny, np. 8 znaków, czy dłuższy i trudniejszy do zgadnięcia?
- Decyzja: aktywny gość może kupić własny plan przed końcem dostępu gościa. Zakup zastępuje status gościa, kończy grant `invitation_guest`, ustawia zaproszenie jako `converted` i zwalnia slot pierwotnego właściciela od razu.

## Plan implementacji

### Faza 1 — Fundament danych

Zakres:

- dodać model `FriendInvitation`,
- dodać migrację tabeli `friend_invitations`,
- dodać statusy: `pending`, `accepted`, `revoked`, `expired`, `converted`,
- dodać źródło `ProductAccessGrant::SOURCE_INVITATION_GUEST`,
- dodać relacje w modelach `User`, `ProductAccessGrant`, `ProductPlan`, `PurchaseOrder` tam, gdzie są potrzebne,
- dodać fabrykę `FriendInvitationFactory`.

W tej fazie nie budujemy UI.

### Faza 2 — Serwis reguł i eligibility

Nowy serwis, np. `FriendInvitationEligibilityService`, powinien odpowiadać na pytania:

- czy właściciel ma kwalifikujący aktywny zakup,
- czy plan właściciela to `start-90` albo `start-365`,
- czy właściciel ma wolny slot gościa,
- ile ma pending zaproszeń,
- czy może wygenerować kolejne zaproszenie,
- czy wskazany użytkownik może przyjąć zaproszenie.

Ten serwis nie powinien tworzyć rekordów. Ma tylko oceniać stan i zwracać decyzje z kodem powodu.

### Faza 3 — Generowanie i zarządzanie pending zaproszeniami

Nowy serwis, np. `FriendInvitationService`, powinien obsłużyć:

- generowanie tokenu linku,
- generowanie kodu ręcznego,
- hashowanie tokenu i kodu,
- tworzenie zaproszenia `pending`,
- limit 10 pending zaproszeń,
- ręczne unieważnienie pending zaproszenia przez właściciela,
- automatyczne wygaszanie pending po 14 dniach.

Potrzebne trasy właściciela pod `auth` + `verified`:

- `POST /profile/zaproszenia`,
- `DELETE /profile/zaproszenia/{invitation}`.

Widok listy i stanu zaproszeń jest częścią istniejącej strony `GET /profile`, a logika mutacji pozostaje w osobnym kontrolerze `FriendInvitationOwnerController`.

### Faza 4 — Landing i kod dla osoby zaproszonej

Publiczne wejścia:

- `GET /zaproszenie/{token}`,
- `GET /zaproszenie`,
- `POST /zaproszenie/kod`.

Landing pokazuje:

- imię zapraszającego,
- datę potencjalnego końca dostępu,
- informację o ograniczeniach,
- CTA do akceptacji.

Kod ręczny powinien rozwiązywać się do tego samego rekordu zaproszenia co link. Nie zapisujemy tokenów ani kodów jawnie w bazie.

### Faza 5 — Akceptacja po auth

Akceptacja jest osobnym endpointem za `auth` + `verified`:

- `GET /zaproszenie/potwierdz` — ekran pending zaproszenia po zalogowaniu,
- `POST /zaproszenie/potwierdz` — akceptacja zaproszenia zapisanego w sesji.

Serwis akceptacji musi działać w transakcji:

1. zablokować rekord zaproszenia,
2. zablokować kwalifikujący grant właściciela,
3. sprawdzić status i termin zaproszenia,
4. sprawdzić wolny slot właściciela,
5. sprawdzić brak aktywnego Premium u gościa,
6. utworzyć `ProductAccessGrant` typu `invitation_guest`,
7. zapisać `guest_access_expires_at`,
8. oznaczyć zaproszenie jako `accepted`,
9. unieważnić pozostałe pending zaproszenia właściciela.

To jest najważniejsza faza bezpieczeństwa.

### Faza 6 — Integracja z dostępem Premium

Zmiany:

- `ProductAccessResolver` rozpoznaje aktywny grant `invitation_guest`,
- middleware `product.access` działa bez dodatkowych wyjątków,
- UI i payloady potrafią rozróżnić właściciela płatnego planu od gościa,
- gość nie dostaje prawa do generowania zaproszeń.

W tej fazie trzeba szczególnie uważać na checkout. Aktywny gość może kupić plan, więc `CheckoutController::store` nie może traktować `invitation_guest` jako blokady zakupu. Po potwierdzeniu płatności zakup musi zakończyć grant gościa, oznaczyć zaproszenie jako `converted` i zwolnić slot pierwotnego właściciela.

### Faza 7 — UI właściciela

Miejsce:

- profil / dostęp Premium / zaproś znajomego,
- lekki CTA na `/nauka`,
- informacja po checkout sukcesie dla planów `start-90` i `start-365`.

UI właściciela powinno obsłużyć stany:

- plan niekwalifikujący,
- brak aktywnego planu,
- wolny slot,
- pending zaproszenia,
- limit 10 pending,
- slot zajęty,
- gość po refundzie w 7-dniowym buforze,
- slot zwolniony po wygaśnięciu gościa.

Status MVP: podstawowa sekcja profilu, generowanie linku/kodu, kopiowanie, lista pending, unieważnianie pending, stan aktywnego gościa, CTA po checkout sukcesie oraz małe CTA na `/nauka` są zaimplementowane.

### Faza 8 — Refund i wygaszanie

Potrzebne:

- serwis refundu,
- komenda/scheduler wygaszająca pending zaproszenia po 14 dniach — zrobione: `ops:expire-friend-invitations`,
- komenda/scheduler kończąca dostęp gościa po `guest_access_expires_at` — zrobione: `ops:expire-friend-invitations`,
- logika skracania dostępu gościa po refundzie — zrobione jako `FriendInvitationService::applyOwnerRefundEffects`,
- maile/powiadomienia dla gościa i opcjonalnie właściciela.

Jeśli na tym etapie nadal nie ma realnego operatora płatności, refund można wdrożyć jako metodę serwisową i przetestować ją bez webhooks.

Status implementacji: refund jest obsługiwany przez `ProductRefundService`, a aktywny gość dostaje mail `FriendInvitationGuestAccessShortenedAfterRefund` z datą końca 7-dniowego buforu. Wciąż brakuje realnego webhooka/operatora, który wywoła ten serwis w produkcji.

### Faza 9 — Audyt i panel administracyjny

Minimalnie warto logować akcje:

- `friend_invitation.created`,
- `friend_invitation.revoked`,
- `friend_invitation.accepted`,
- `friend_invitation.expired`,
- `friend_invitation.converted_to_purchase`,
- `friend_invitation.guest_access_shortened_after_refund`.

Panel admina nie musi mieć akcji mutujących w pierwszym MVP, ale przy dostępie Premium warto mieć przynajmniej możliwość odczytu zaproszeń w kontekście użytkownika.

Status implementacji: dodany został read-only resource Filament `FriendInvitationResource` z listą, filtrami statusów i widokiem szczegółów zaproszenia. Nie ma tam ręcznych akcji zmiany statusu, żeby nie rozjechać rekordu zaproszenia z grantem dostępu.

## Plan testów

## Stan implementacji na 2026-05-26

Zrobione:

- tabela `friend_invitations`, model, factory i relacje w `User`, `ProductAccessGrant`, `ProductPlan`, `PurchaseOrder`,
- źródło dostępu `ProductAccessGrant::SOURCE_INVITATION_GUEST`,
- `FriendInvitationEligibilityService`,
- `FriendInvitationService` dla generowania, wyszukiwania, akceptacji, unieważniania pending, wygaszania starych rekordów i konwersji gościa po zakupie własnego planu,
- komenda `ops:expire-friend-invitations` oraz harmonogram produkcyjny do cyklicznego wygaszania pending i wygasłych dostępów gościa,
- `FriendInvitationService::applyOwnerRefundEffects` unieważnia pending po refundzie i skraca aktywnego gościa do maksymalnie 7 dni,
- `ProductRefundService` oznacza zakup jako zwrócony, cofa grant zakupowy właściciela i uruchamia efekty refundowe zaproszeń,
- integracja `ProductAccessResolver` z grantem gościa,
- checkout pozwala aktywnemu gościowi kupić własny plan, a opłacenie zamówienia kończy grant gościa i zwalnia slot właściciela,
- publiczne wejścia `/zaproszenie/{token}`, `/zaproszenie`, `/zaproszenie/kod`,
- sesyjny powrót do zaproszenia po logowaniu i po weryfikacji e-maila,
- sekcja "Zaproś znajomego" w profilu,
- CTA po sukcesie checkoutu dla planów `start-90` i `start-365`,
- CTA na `/nauka` dla właścicieli kwalifikujących planów bez aktywnego gościa,
- link "Wpisz kod zaproszenia" w drawerach logowania i rejestracji,
- mail do gościa po refundzie planu właściciela,
- read-only podgląd zaproszeń w panelu administracyjnym Filament,
- testy feature dla reguł, generowania, manualnego kodu, akceptacji, konwersji po zakupie oraz sąsiednich obszarów dostępu/checkoutu.

Do zrobienia:

- podpięcie `ProductRefundService::refund` pod docelowy webhook płatności albo ręczną akcję operatora,
- opcjonalny mail do właściciela po wygaśnięciu gościa i zwolnieniu slotu,
- opcjonalne powiadomienie o naturalnym wygaśnięciu dostępu gościa,
- decyzja, czy w adminie kiedykolwiek dopuszczamy ręczne akcje na zaproszeniach, czy panel zostaje tylko do odczytu.

### Weryfikacja przed merge/deploy 2026-05-26

Status: gotowe do merge po lokalnej walidacji.

Wykonane sprawdzenia:

- `pint` dla zmienionych plików PHP,
- `git diff --check`,
- `php artisan test tests/Feature/FriendInvitationTest.php tests/Feature/SessionPageTest.php tests/Feature/PurchaseCheckoutTest.php tests/Feature/ProductAccessResolverTest.php tests/Feature/ProductAccessGateTest.php`,
- dodatkowy test refundu: `php artisan test tests/Feature/FriendInvitationTest.php --filter=refund`,
- `npm run build`,
- lokalny browser check `/nauka`: CTA "Zaproś znajomego" widoczne i prowadzi do `/profile#zapros-znajomego`.

Uwaga produkcyjna: deploy wymaga migracji `2026_05_26_120000_create_friend_invitations_table.php`. Migracja tworzy nową tabelę i indeksy pod zaproszenia; nie zmienia istniejących tabel danych użytkowników poza wcześniejszymi zmianami modelowymi w kodzie.

### Testy reguł

- właściciel `start-30` nie może zapraszać,
- właściciel `start-90` może zapraszać,
- właściciel `start-365` może zapraszać,
- system/admin/moderator/test account bez zakupu nie może zapraszać,
- gość nie może zapraszać,
- właściciel z aktywnym gościem nie może wygenerować skutecznego kolejnego slotu,
- limit 10 pending blokuje kolejne zaproszenie,
- revoked/expired pending zwalnia limit.

### Testy akceptacji

- nowy użytkownik po rejestracji może przyjąć zaproszenie,
- istniejący użytkownik bez aktywnego dostępu może przyjąć zaproszenie,
- użytkownik z aktywnym zakupem nie może przyjąć zaproszenia,
- aktywny gość nie może przyjąć kolejnego zaproszenia,
- zaakceptowanie jednego pending unieważnia pozostałe pending właściciela,
- wygasłe zaproszenie nie działa,
- revoked zaproszenie nie działa,
- ten sam link kliknięty drugi raz pokazuje stan nieaktywny.

### Testy dostępu

- grant `invitation_guest` daje dostęp do `product.access`,
- wygasły grant gościa nie daje dostępu,
- revoked grant gościa nie daje dostępu,
- gość nie widzi prawa do generowania zaproszeń.

### Testy checkout/refund

- aktywny gość może albo nie może kupić plan zgodnie z podjętą decyzją,
- zakup własnego planu przez gościa nie tworzy dwóch aktywnych dostępów, jeśli przyjmiemy rekomendację zastępowania gościa,
- refund właściciela skraca dostęp gościa do 7 dni,
- refund właściciela unieważnia pending zaproszenia,
- zwykłe anulowanie pending zamówienia nie wpływa na istniejącego gościa.

### Testy UI/Inertia

- profil pokazuje sekcję zaproszeń właścicielowi kwalifikującego planu,
- profil pokazuje brak uprawnień przy planie miesięcznym,
- `/nauka` pokazuje CTA tylko kwalifikującym właścicielom,
- checkout success pokazuje CTA tylko dla `start-90` i `start-365`,
- login/rejestracja ma wejście "Mam kod zaproszenia",
- landing zaproszenia pokazuje datę końca potencjalnego dostępu.

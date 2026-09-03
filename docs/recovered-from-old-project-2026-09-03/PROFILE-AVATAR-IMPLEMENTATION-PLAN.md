# Zdjecie profilowe uzytkownika - dokumentacja i plan wdrozenia

Status: implementacja gotowa lokalnie, oczekuje na commit, merge i deploy
Branch: `codex/profile-avatar-audit`
Data: 2026-06-12

## Cel

Chcemy dodac mozliwosc ustawienia okraglego zdjecia profilowego przez uzytkownika na stronie:

```text
/profile
```

Zdjecie ma byc widoczne w miejscach, gdzie dzisiaj pokazujemy inicjaly uzytkownika. Funkcja ma byc bezpieczna, spójna z obecnym systemem logowania i nie moze naruszyc flow rejestracji, OAuth, profilu ani panelu nauki.

Najwazniejsza decyzja produktowa:

```text
Wlasny upload uzytkownika zawsze wygrywa nad zdjeciem pobranym z Google/Facebook.
```

## Status prac

- [x] Utworzono branch roboczy: `codex/profile-avatar-audit`.
- [x] Przeanalizowano aktualny model `User`.
- [x] Przeanalizowano aktualny model `UserProfile`.
- [x] Przeanalizowano integracje Google/Facebook OAuth.
- [x] Potwierdzono, ze Google i Facebook avatar jest juz pobierany i zapisywany w `user_social_accounts.avatar_url`.
- [x] Przeanalizowano glowne menu Vue/Inertia.
- [x] Przeanalizowano publiczny header Blade.
- [x] Przeanalizowano strone `/profile`.
- [x] Przeanalizowano API `/api/v1/me/profile`.
- [x] Przeanalizowano panel admina uzytkownikow.
- [x] Ustalono priorytet zrodel avatara.
- [x] Utworzono ten dokument wdrozeniowy.
- [x] Potwierdzono decyzje: admin moze usuwac zdjecia uzytkownikow naruszajace regulamin.
- [x] Potwierdzono decyzje: MVP bez edytora kadrowania, tylko okragly `object-cover`.
- [x] Potwierdzono decyzje: MVP trzyma uploady lokalnie na dysku `public`.
- [x] Implementacja migracji.
- [x] Implementacja helpera/resolvera avatara.
- [x] Implementacja endpointow upload/remove.
- [x] Implementacja UI na `/profile`.
- [x] Podpiecie avatara do `auth.user`.
- [x] Podpiecie avatara do headera Vue.
- [x] Podpiecie avatara do publicznego headera Blade.
- [x] Rozszerzenie API `/api/v1/me/profile`.
- [x] Podpiecie pod admin panel: akcja usuniecia zdjecia.
- [x] Testy backendowe.
- [x] Build frontendu.
- [x] Manualne QA uploadu: wybor pliku i submit dzialaja.
- [x] Manualne QA publicznego URL-a avatara: `/storage/profile-avatars/...` zwraca `200 OK`.
- [ ] Commit, merge i deploy.

## Aktualny stan kodu

### Konto uzytkownika

Model:

```text
app/Models/User.php
```

Aktualnie `users` nie ma pol dla zdjecia profilowego.

Rekomendacja: avatar powinien byc przypisany do `users`, nie do `user_profiles`.

Powod:

- `User` reprezentuje konto i tozsamosc logowania,
- `UserProfile` jest u nas profilem produktowym/nauki: kategoria, seria nauki, ustawienia,
- avatar jest potrzebny globalnie w menu, profilu, API i potencjalnie adminie.

### Profil nauki

Model:

```text
app/Models/UserProfile.php
```

Nie dodajemy tutaj avatara. To ogranicza ryzyko pomieszania profilu konta z profilem nauki.

### OAuth Google/Facebook

Pliki:

```text
app/Support/SocialAuthProviderClient.php
app/Support/SocialProviderUser.php
app/Support/SocialAccountService.php
app/Models/UserSocialAccount.php
```

Stan:

- Google payload `picture` jest mapowany na `SocialProviderUser->avatarUrl`.
- Facebook `picture.data.url` jest mapowany na `SocialProviderUser->avatarUrl`.
- `SocialAccountService` zapisuje URL w `user_social_accounts.avatar_url`.

Wniosek: nie trzeba budowac importu z Google od zera. Trzeba tylko zdecydowac, jak ten URL ma byc uzywany w profilu uzytkownika.

## Zasada wyboru avatara

Priorytet:

1. `users.avatar_path` - wlasne zdjecie wgrane przez uzytkownika.
2. `user_social_accounts.avatar_url` - fallback z Google/Facebook, jesli uzytkownik nie ma wlasnego uploadu.
3. Inicjaly uzytkownika - fallback koncowy.

Wlasny upload nie moze byc nadpisywany przez kolejne logowanie przez Google.

Jesli uzytkownik usunie wlasne zdjecie, system moze znowu pokazac avatar z Google/Facebook, o ile konto social ma zapisany `avatar_url`.

## Proponowany model danych

Migracja na `users`:

| Pole | Typ | Cel |
| --- | --- | --- |
| `avatar_path` | string nullable | Sciezka do lokalnie wgranego zdjecia uzytkownika. |
| `avatar_uploaded_at` | timestamp nullable | Informacja kiedy uzytkownik ustawil wlasne zdjecie. |
| `avatar_social_fallback_disabled_at` | timestamp nullable | Admin moze wylaczyc pokazywanie avatara z Google/Facebook, jesli lamie regulamin. |

Opcjonalnie, jesli chcemy miec wiecej kontroli:

| Pole | Typ | Cel |
| --- | --- | --- |
| `avatar_disk` | string nullable | Dysk storage. Domyslnie `public`. |

Rekomendacja MVP:

```text
avatar_path + avatar_uploaded_at + avatar_social_fallback_disabled_at
```

`avatar_disk` mozna pominac, jesli z gory ustalimy, ze profile avatars trzymamy na dysku `public`.

## Storage

Rekomendowany katalog:

```text
storage/app/public/profile-avatars/{user_id}/avatar.{ext}
```

Publiczny URL bedzie szedl przez:

```text
/storage/profile-avatars/{user_id}/avatar.{ext}
```

Warunek deploymentowy:

```text
php artisan storage:link
```

Na produkcji ten link powinien juz istniec, ale przy wdrozeniu trzeba to sprawdzic.

W lokalnym Dockerze trzeba pamietac, ze Nginx (`web`) serwuje pliki statyczne,
a PHP (`app`) zapisuje uploady do `storage/app/public`. Dlatego lokalna
konfiguracja musi spelniac dwa warunki:

- `public/storage` powinien byc linkiem wzglednym do `../storage/app/public`,
- kontener `web` musi miec zamontowane `./storage/app/public` jako read-only.

Bez tego upload konczy sie sukcesem, ale obraz po odswiezeniu strony jest
zepsuty, bo URL `/storage/profile-avatars/...` zwraca `403 Forbidden`.

## Walidacja uploadu

Minimalne zasady:

- maksymalny rozmiar: 2 MB,
- tylko obrazy rastrowe,
- dozwolone typy: JPG, PNG, WEBP,
- nie przyjmujemy SVG jako avatara,
- plik musi przejsc walidacje Laravel `image`,
- nazwa pliku nie moze pochodzic bezposrednio od uzytkownika.

Powod odrzucenia SVG:

- SVG moze zawierac niepozadane skrypty lub linki,
- avatar ma byc prostym obrazem, nie dokumentem wektorowym.

## Kadrowanie i ksztalt

MVP:

- frontend wyswietla avatar jako kolo przez `rounded-full`,
- obraz ma `object-cover`,
- nie wprowadzamy jeszcze edytora kadrowania.

Opcja pozniejsza:

- automatyczne przycinanie do kwadratu po stronie serwera,
- generowanie wariantu 256x256,
- osobny UI kadrowania.

Nie rekomenduje zaczynac od edytora crop, bo podnosi zlozonosc i ryzyko, a nie jest konieczny do pierwszej wersji.

## Miejsca wyswietlania

### 1. Globalny header Vue/Inertia

Plik:

```text
resources/js/Components/SiteHeader.vue
```

Obecnie:

- liczy `userInitials`,
- pokazuje kolo z inicjalami w menu konta.

Docelowo:

- jesli `auth.user.avatar_url` istnieje, pokazuje `<img>`,
- jesli nie istnieje, pokazuje inicjaly jak dzis.

### 2. Publiczny header Blade

Plik:

```text
resources/views/components/site/public-header.blade.php
```

Obecnie:

- liczy inicjaly po stronie Blade,
- pokazuje kolo z inicjalami.

Docelowo:

- uzyc tego samego resolvera avatara po stronie PHP,
- jesli avatar istnieje, pokazac `<img>`,
- fallback na inicjaly zostaje.

To jest wazne, bo czesc publicznych stron nie jest renderowana przez Vue/Inertia.

### 3. `/profile`

Pliki:

```text
resources/js/Pages/Profile/Edit.vue
resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.vue
```

Docelowo:

- dodac sekcje `Zdjecie profilowe`,
- pokazac aktualny avatar,
- dodac przycisk `Wgraj zdjecie`,
- dodac przycisk `Usun zdjecie`, jesli uzytkownik ma wlasny upload,
- jasno opisac, ze jesli nie ma wlasnego zdjecia, mozemy pokazac zdjecie z Google.

Nie mieszamy uploadu zdjecia z formularzem zmiany e-maila, bo tam jest osobna logika hasla/potwierdzen.

### 4. Inertia shared props

Plik:

```text
app/Http/Middleware/HandleInertiaRequests.php
```

Obecnie `auth.user` ma:

```text
id, name, email, email_verified_at, is_admin
```

Docelowo dodac:

```text
avatar_url
avatar_initials
has_uploaded_avatar
```

`avatar_initials` mozna liczyc po froncie, ale lepiej miec spójny fallback z backendu dla Vue i Blade.

### 5. API profilu

Plik:

```text
app/Http/Controllers/MeProfileController.php
```

Docelowo dodac do payloadu:

```text
avatar_url
has_uploaded_avatar
```

Powod: jesli pozniej bedziemy miec mobile/API albo osobne widoki JS, nie bedziemy dodawac tego drugi raz.

### 6. Panel admina

Pliki:

```text
app/Filament/Resources/Users/Schemas/UserForm.php
app/Filament/Resources/Users/Schemas/UserInfolist.php
app/Filament/Resources/Users/Tables/UsersTable.php
```

MVP moze pominac edycje avatara przez admina.

Rekomendacja docelowa:

- w infolist pokazac aktualny avatar,
- w tabeli opcjonalnie maly podglad przy koncie,
- dodac akcje admina `Usun zdjecie profilowe`, jesli obraz jest niestosowny.

Nie rekomenduje w MVP dawac adminowi pelnego uploadu zdjec za uzytkownika, chyba ze pojawi sie konkretny przypadek biznesowy.

## Endpointy

Rekomendowany rozdzial od zwyklego `PATCH /profile`:

```text
POST /profile/avatar
DELETE /profile/avatar
```

Powod:

- upload pliku wymaga `multipart/form-data`,
- obecny `PATCH /profile` ma delikatna logike zmiany e-maila i hasla,
- rozdzielenie zmniejsza ryzyko regresji w auth.

Proponowane nazwy route:

```text
profile.avatar.store
profile.avatar.destroy
```

## Serwis/helper

Rekomendowany helper:

```text
app/Support/UserAvatarPresenter.php
```

Odpowiedzialnosc:

- zwrocic URL wlasnego avatara,
- jesli brak, zwrocic social avatar,
- jesli brak, zwrocic null,
- policzyc inicjaly,
- zwrocic flage `has_uploaded_avatar`.

Nie rozrzucamy tej logiki po kontrolerach i Blade.

## Google avatar - decyzja MVP

Na start:

- uzywamy `user_social_accounts.avatar_url` jako fallback,
- nie pobieramy pliku z Google na nasz serwer,
- nie nadpisujemy nim wlasnego uploadu.

Plusy:

- mniejsze ryzyko,
- mniej kodu,
- wykorzystujemy dane, ktore juz zapisujemy,
- szybkie wdrozenie.

Minusy:

- obraz jest z zewnetrznego hosta,
- URL moze sie zmienic,
- przegladarka uzytkownika pobiera obraz z Google.

Docelowo mozna rozwazyc lokalne cache'owanie avatara z Google, ale dopiero po MVP.

## Otwarte decyzje przed implementacja

### 1. Czy uzytkownik moze usunac wlasny avatar?

Rekomendacja: tak.

Po usunieciu przez uzytkownika:

- kasujemy lokalny plik,
- ustawiamy `avatar_path = null`,
- jesli konto ma Google avatar, pokazujemy Google fallback,
- jesli nie ma Google avatar, pokazujemy inicjaly.

Po usunieciu przez admina:

- kasujemy lokalny plik,
- ustawiamy `avatar_path = null`,
- ustawiamy `avatar_social_fallback_disabled_at`,
- nie pokazujemy juz avatara z Google/Facebook jako fallbacku.

### 2. Czy pokazujemy Google avatar od razu po social rejestracji?

Rekomendacja: tak, ale tylko jako fallback.

Uzytkownik powinien czuc, ze konto jest "jego", a my i tak nie zapisujemy tego jako wlasnego uploadu.

### 3. Czy admin ma miec mozliwosc usuniecia avatara?

Rekomendacja: dodac w drugim kroku albo od razu, jesli chcemy domknac moderacje.

Minimalne MVP moze dzialac bez tego, ale z punktu widzenia operacyjnego przy publicznych/rankingowych profilach warto miec szybka akcje `Usun zdjecie`.

### 4. Czy avatar ma byc widoczny w rankingach?

Na razie nie.

Ranking ma osobna logike wizualna i obecnie nie jest glownym miejscem profilu. Wprowadzenie avatara do rankingu moze byc osobnym etapem, bo dotyka multiplayer/rywalizacji i wymaga dodatkowego QA.

### 5. Czy robimy crop po stronie serwera?

Nie w MVP.

Na start wystarczy `object-cover` i okragla ramka. Serwerowe crop/resize mozna dodac pozniej, jesli realne uploady beda wygladac zle albo beda za ciezkie.

### 6. Czy pliki trzymamy lokalnie czy w R2?

Rekomendacja MVP: lokalny `public`.

Powod:

- avatar jest maly,
- deployment jest prostszy,
- obecny upload profilu nie potrzebuje presign flow,
- latwiej testowac.

Docelowo, jesli media uzytkownikow urosna, mozemy przeniesc na R2.

## Ryzyka i zabezpieczenia

| Ryzyko | Zabezpieczenie |
| --- | --- |
| Upload zlosliwego pliku | Walidacja `image`, MIME whitelist, brak SVG. |
| Nadpisanie custom avatara przez Google | Priorytet `avatar_path > social avatar`. |
| Stare pliki zostaja po podmianie | Przy nowym uploadzie usuwac poprzedni plik. |
| Brak `storage:link` na produkcji | Check deploymentowy. |
| Lokalny Nginx nie widzi `storage/app/public` | Docker `web` montuje `./storage/app/public`, a `app` tworzy `storage:link --relative --force`. |
| Rozjazd Vue i Blade | Wspolny backendowy presenter avatara. |
| Regresja zmiany e-maila | Osobne endpointy avatara, nie mieszamy z `PATCH /profile`. |
| Duze pliki | Limit 2 MB, pozniej opcjonalny resize. |
| Zewnetrzny Google URL | Tylko fallback, custom upload lokalny. |

## Plan implementacji

### Etap 1 - backend danych

- [ ] Dodac migracje `avatar_path`, `avatar_uploaded_at` do `users`.
- [ ] Dodac pola do `$fillable` w `User`, jesli bedzie potrzebne masowe wypelnianie.
- [ ] Dodac helper/presenter `UserAvatarPresenter`.
- [ ] Dodac metody pomocnicze w `User` tylko jesli beda lekkie i bez logiki storage.

### Etap 2 - endpointy profilu

- [ ] Dodac `ProfileAvatarController`.
- [ ] Dodac `POST /profile/avatar`.
- [ ] Dodac `DELETE /profile/avatar`.
- [ ] Walidowac plik.
- [ ] Zapisywac plik w `profile-avatars/{user_id}`.
- [ ] Usuwac poprzedni lokalny avatar przy podmianie.
- [ ] Usuwac lokalny avatar przy `DELETE`.

### Etap 3 - propsy i API

- [ ] Rozszerzyc `HandleInertiaRequests`.
- [ ] Rozszerzyc `resources/js/types/index.d.ts`.
- [ ] Rozszerzyc `MeProfileController`.

### Etap 4 - UI

- [ ] Dodac wspolny komponent Vue, np. `UserAvatar.vue`.
- [ ] Podmienic kolo inicjalow w `SiteHeader.vue`.
- [ ] Podmienic kolo inicjalow w `public-header.blade.php`.
- [ ] Dodac sekcje uploadu na `/profile`.
- [ ] Dodac komunikaty sukcesu/bledu.

### Etap 5 - admin

Decyzja do potwierdzenia:

- [ ] MVP bez admina.
- [ ] Albo: infolist + akcja usuniecia avatara.

### Etap 6 - testy

Minimalne testy:

- [ ] Uzytkownik moze wgrac poprawny avatar.
- [ ] Uzytkownik nie moze wgrac pliku niebedacego obrazem.
- [ ] Uzytkownik nie moze wgrac za duzego pliku.
- [ ] Podmiana avatara usuwa stary plik.
- [ ] Usuniecie avatara usuwa plik i wraca do fallbacku.
- [ ] Google avatar jest widoczny jako fallback.
- [ ] Wlasny avatar wygrywa nad Google avatar.
- [ ] `auth.user.avatar_url` jest dostepny w Inertia.
- [ ] API `/api/v1/me/profile` zwraca avatar.

### Etap 7 - QA manualne

Sprawdzic:

- [ ] `/profile` - upload, podmiana, usuniecie.
- [ ] Header na stronach Inertia, np. `/nauka`.
- [ ] Header na publicznych stronach Blade, np. `/przepisy` albo strona pytania.
- [ ] Konto Google-only bez wlasnego uploadu.
- [ ] Konto z haslem bez social avatara.
- [ ] Konto z wlasnym uploadem i podpietym Google.
- [ ] Mobile header.
- [ ] Logout/login po zmianie avatara.

## Deployment checklist

Przed deployem:

- [ ] `php artisan test` albo testy feature zwiazane z profilem/auth.
- [ ] `npm run build`.
- [ ] `php artisan migrate --force` na produkcji.
- [ ] Sprawdzic, czy istnieje link public storage:

```bash
php artisan storage:link
```

- [ ] `php artisan optimize:clear`.
- [ ] `php artisan config:cache`.
- [ ] Deploy assets frontendu.
- [ ] Smoke test na produkcji:
  - wejsc na `/profile`,
  - wgrac avatar testowy,
  - zobaczyc avatar w menu,
  - usunac avatar,
  - sprawdzic fallback.

## Rollback

Jesli problem dotyczy tylko frontendu:

- cofnac build/assets do poprzedniej wersji,
- kod backendowy moze zostac, bo nowe pola sa nullable.

Jesli problem dotyczy uploadu/storage:

- tymczasowo ukryc UI uploadu,
- zostawic migracje,
- avatar fallback w menu moze nadal dzialac przez inicjaly/Google.

Nie rekomenduje rollbacku migracji na produkcji, jesli uzytkownicy zdazyli wgrac zdjecia.

## Kryteria gotowosci

Ficzer uznajemy za gotowy, gdy:

- uzytkownik moze dodac i usunac swoje zdjecie na `/profile`,
- header Vue i header Blade pokazuja ten sam avatar,
- Google avatar dziala jako fallback,
- wlasny upload wygrywa nad Google,
- usuniecie wlasnego uploadu wraca do Google/inicjalow,
- nie ma regresji w zmianie e-maila, zmianie hasla i OAuth,
- build frontendu przechodzi,
- testy profilu/auth przechodza.

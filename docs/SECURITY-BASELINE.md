# Security Baseline

## 1. Cel dokumentu

Ten dokument definiuje minimalny standard bezpieczenstwa dla projektu.

Jego celem jest:

- ustalenie, co jest wymagane od pierwszej wersji produkcyjnej,
- ograniczenie ryzyka prostych, kosztownych bledow,
- przygotowanie sciezki dojrzewania bezpieczenstwa od MVP do V2.

To nie jest dokument compliance. To jest praktyczny baseline bezpieczenstwa.

## 2. Zasady nadrzedne

1. Najpierw eliminujemy banalne ryzyka, potem rozwazamy bardziej zaawansowane mechanizmy.
2. Sekrety nigdy nie trafiaja do repo.
3. Multimedia publiczne sa odseparowane od danych prywatnych.
4. Dane uzytkownikow sa widoczne tylko w granicach autoryzacji.
5. Backend ufa tylko sobie, nie frontendowi.
6. Kazda operacja administracyjna powinna byc ograniczona i docelowo audytowalna.

## 3. Zakres

Baseline obejmuje:

- VPS,
- system operacyjny,
- reverse proxy,
- aplikacje,
- baze danych,
- Cloudflare,
- R2,
- sekrety,
- logowanie i audyt,
- przejscie do V2.

## 4. System i dostep do serwera

Minimalny standard:

- SSH keys only,
- brak logowania haslem,
- brak logowania root bez potrzeby,
- osobny user do aplikacji,
- firewall wlaczony,
- tylko porty `22`, `80`, `443` publicznie,
- PostgreSQL niewystawiony publicznie,
- aktualizacje security regularnie.

## 5. Sekrety

Sekrety obejmuja:

- klucze aplikacji,
- hasla do bazy,
- tokeny Cloudflare,
- klucze do R2,
- sekrety auth,
- SMTP lub e-mail provider credentials.

Zasady:

- nie przechowujemy sekretow w repo,
- nie wysylamy ich w logach,
- nie trzymamy ich w kodzie,
- rotujemy je po incydencie lub podejrzeniu wycieku,
- rozdzielamy sekrety per srodowisko.

## 6. Reverse proxy i edge

Cloudflare oraz reverse proxy powinny:

- terminowac TLS poprawnie,
- wymuszac HTTPS,
- odrzucac oczywiscie zly ruch,
- nie ujawniac zbednych naglowkow i informacji o originie.

Dodatkowo:

- narzedzia wewnetrzne nie powinny byc publiczne bez potrzeby,
- w V2 preferowany jest Access/Tunnel dla narzedzi operacyjnych.

## 7. Aplikacja

Minimalny standard aplikacyjny:

- walidacja danych wejsciowych,
- autoryzacja po stronie backendu,
- brak zaufania do roli przeslanej z frontendu,
- jawna kontrola dostepu do endpointow admin,
- rate limiting dla endpointow wrazliwych,
- bezpieczna obsluga bledow bez wycieku stosu i sekretow.

## 8. Auth i session security

Minimalne wymagania:

- bezpieczny mechanizm sesji first-party dla web app,
- wygasanie sesji,
- ochrona CSRF dla requestow zmieniajacych stan,
- cookies oznaczone jako `HttpOnly`, `Secure` i z sensownym `SameSite`,
- ochrona endpointow `me`, `sessions`, `admin`,
- rozroznienie user/admin/B2B roles po backendzie.

W V2:

- role organizacyjne,
- mocniejsza kontrola membership,
- przygotowanie pod SSO.

## 9. Baza danych

Minimalny standard bazy:

- brak publicznego dostepu z Internetu,
- zasada najmniejszych uprawnien,
- osobny user aplikacyjny,
- brak pracy na superuserze z poziomu aplikacji,
- backup szyfrowany lub bezpiecznie przechowywany,
- restore testowany.

Polityki autoryzacji w aplikacji, poprawne scope zapytan i restrykcyjny dostep do danych powinny chronic dane uzytkownikow.

## 10. Media i R2

Najwazniejsza zasada:

- media publiczne to nie to samo co dane prywatne.

Dlatego:

- publiczne assety pytan moga byc publiczne,
- prywatne eksporty, uploady robocze i backupy nie moga byc publiczne,
- bucket namespace powinien rozdzielac public i private,
- uploady powinny isc przez presigned URLs lub backend.

Nie robimy:

- publicznego dostepu do prywatnych eksportow,
- tych samych bucket paths dla produkcji i admin importow,
- przechowywania tajnych plikow pod publicznym hostem mediow.

## 11. Upload security

Kazdy upload powinien miec:

- walidacje typu MIME,
- walidacje rozmiaru,
- ograniczenie do dozwolonych rodzajow plikow,
- ograniczony czas waznosci upload URL,
- zapis metadanych po stronie backendu.

Jesli plik nie przechodzi walidacji:

- nie zapisujemy go jako aktywny asset,
- nie publikujemy go publicznie.

## 12. Logi i dane wrazliwe

Nie logujemy:

- hasel,
- tokenow,
- sekretow,
- danych sesyjnych w pelnej formie,
- danych prywatnych bez potrzeby.

Logujemy:

- bledy aplikacji,
- bledy auth,
- operacje administracyjne,
- proby dostepu do wrazliwych endpointow,
- powod odrzuconych requestow tam, gdzie to bezpieczne.

## 13. Minimalne naglowki i polityki

Nalezy zapewnic co najmniej:

- HTTPS only,
- sensowna polityke cookies, jesli cookies sa uzywane,
- `X-Content-Type-Options: nosniff`,
- `Referrer-Policy`,
- `Content-Security-Policy` w wersji adekwatnej do frontend stacku,
- brak debug headerow w produkcji.

## 14. Admin i operacje

Endpointy administracyjne:

- nie moga byc dostepne dla zwyklych userow,
- powinny miec mocniejsze limity,
- powinny miec dodatkowe logowanie zdarzen,
- w V2 powinny byc audytowalne.

Minimalny standard MVP, ktory warto utrzymac juz teraz:

- udane mutacje administracyjne dla tresci i mediow powinny zapisac trwaly wpis w `audit_logs`,
- wpis powinien zawierac co najmniej aktora, akcje, encje, czas i `request_id`,
- audit log nie moze przechowywac tokenow, sekretow ani podpisanych URL-i uploadu.

Dostep operacyjny do serwera:

- tylko dla upowaznionych osob,
- tylko przez klucze,
- bez wspoldzielonych kont uzytkownikow.

## 15. Kopie zapasowe i dane po incydencie

Backup sam w sobie nie rozwiazuje problemu bezpieczenstwa.

Musimy miec:

- backup,
- kontrolowany dostep do backupu,
- test restore,
- plan reakcji po incydencie.

Jesli sekret wycieknie:

- rotacja sekretu,
- ocena skutkow,
- przeglad logow,
- ewentualna zmiana dostepow.

## 16. Minimalna procedura incydentu

Przy podejrzeniu incydentu:

1. ograniczyc dalszy impact,
2. nie niszczyc sladow,
3. zabezpieczyc logi,
4. zmienic zagrozone sekrety,
5. ocenic zakres problemu,
6. przywrocic bezpieczny stan,
7. zapisac postmortem i dzialania naprawcze.

## 17. V2: rozszerzenie baseline

W V2 dokladamy:

- audit log,
- rozdzielenie stref public/admin/internal,
- lepsza izolacje tenantow,
- Access/Tunnel dla narzedzi wewnetrznych,
- role organizacyjne i mocniejsza autoryzacje,
- bardziej formalna retencje danych,
- przygotowanie pod SSO i wymagania enterprise.

## 18. Rzeczy, ktore sa mile, ale nie sa warunkiem startu

Nie blokujemy MVP przez brak:

- rozbudowanego SIEM,
- pelnego PAM,
- SOC2 procesu,
- zero trust na kazdym poziomie,
- zaawansowanego WAF tuning.

To nie znaczy, ze te rzeczy sa niewazne. To znaczy, ze nie sa pierwszym warunkiem uruchomienia produktu.

## 19. Czerwone linie

Nie akceptujemy:

- sekretow w repo,
- bazy wystawionej publicznie,
- publicznych prywatnych backupow,
- endpointow admin bez autoryzacji,
- produkcji bez backupu,
- produkcji bez planu restore,
- wrzucania mediow do PostgreSQL,
- zaufania do roli przesylanej przez frontend.

## 20. Finalna rekomendacja

Profesjonalny baseline bezpieczenstwa dla tego projektu oznacza:

- prosty, ale twardy standard od MVP,
- separacje publicznych mediow od danych prywatnych,
- bezpieczne zarzadzanie sekretami,
- ograniczony dostep do serwera i bazy,
- gotowosc do dojrzalszego modelu w V2 bez rewolucji.

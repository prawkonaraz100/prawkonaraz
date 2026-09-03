# OSK V2.12 - Etap 6: zrodlo wzoru karty PAPER

**Status:** zrodlo prawne zapisane lokalnie. Etap 6A dodal wersjonowane dane
robocze z tego zrodla, ale nie jest to jeszcze zatwierdzony generator,
formalna karta ani zgoda na wystawianie dokumentu. Szczegoly granicy 6A sa w
[notatce danych roboczych](./OSK_V2_12_STAGE_6A_TRAINING_CARD_DATA_DRAFT.md).

## Co zapisano

W repozytorium znajduje sie wierny, dwustronicowy wyciag z zalacznika nr 3 do
rozporzadzenia:

- [karta-przeprowadzonych-zajec-zalacznik-3-dz-u-2018-1885.pdf](./assets/legal-templates/karta-przeprowadzonych-zajec-zalacznik-3-dz-u-2018-1885.pdf)
- zrodlo: [Dz. U. 2018 poz. 1885 - tekst oficjalny](https://api.sejm.gov.pl/eli/acts/DU/2018/1885/text.pdf)
- strony zrodlowego PDF: 26-27 (numeracja pliku, od 1)
- pobrano i zweryfikowano: 2026-08-30
- SHA-256 zrodlowego aktu: `e4f1a11db08b3b22be3c392fae12f991157c48274ca5f112f9f7d134994b9e85`
- SHA-256 zapisanego wyciagu: `1e46c695ad4eff11878f15ee96a2f3efce9816f364adf296019a3872b622ce57`

To jest wzor **"Karty przeprowadzonych zajec stosowanej na kursie dla
kandydatow na kierowcow lub motorniczych"**. Zawiera pierwsza strone z danymi
kursanta i wpisami 1-20 oraz druga strone z wpisami 21-50 i polami pozytywnego
wyniku egzaminu wewnetrznego.

## Granica prawna i produktowa

1. Zalacznik nr 3 wskazuje format **A5 lub A4**. Wczesniejsze sformulowanie
   "canonical A4" bylo decyzja robocza projektu, a nie jedynym formatem
   nakazanym przez przepis.
2. Asset zachowuje strony z oficjalnej publikacji. Nie jest wlasnym formularzem
HTML/CSS ani materialem z brandingiem PrawkoNaRaz.
3. Sam asset nie daje aplikacji prawa do automatycznego wystawiania formalnej
   karty. Wciaz brakuje modelu danych, przegladu OSK, procesu podpisow,
   immutable eksportu oraz formalnego egzaminu wewnetrznego.
4. Dokument jest zrodlem do dalszego zatwierdzenia przez wlasciciela procesu
   prawnego/compliance. Dopiero wtedy mozna zdecydowac, czy operacyjny PDF
   bedzie korzystac z zatwierdzonego wariantu A4 czy A5 i w jaki sposob beda
   naniesione dane.

## Retencja formalnej karty

Par. 18 rozporzadzenia dotyczy formalnej karty przeprowadzonych zajec, a nie
samego assetu ani roboczych snapshotow Etapu 6A:

1. par. 18 ust. 1 przewiduje 10-letnie przechowywanie rejestru prowadzonych
   zajec;
2. par. 18 ust. 2 przewiduje przechowywanie karty oraz kopii karty osoby,
   ktora przerwala kurs, przez 24 miesiace od dnia ostatniego wpisu, a potem
   ich zniszczenie;
3. par. 18 ust. 3 wymaga wpisania liczby godzin do rejestru przed zniszczeniem
   karty.

Dlatego techniczne 90 dni dla zastapionych rewizji roboczych 6A nie jest
"okresem ustawowym". Pelna polityka dla formalnego dokumentu i powiazanego
rejestru zostanie zaakceptowana dopiero w Etapie 6B.

## Konsekwencja dla Etapu 6

Najpierw budujemy tylko warstwe robocza:

```text
System Evidence
  -> TrainingCardDataDraft
  -> review pracownika OSK
  -> accepted documentation data
```

Dopiero po zatwierdzeniu formatu i podpisow mozna dodac:

```text
accepted documentation data
  -> immutable PaperTrainingCardExport
  -> PDF
  -> wydruk i podpisy
```

Nie nazywamy roboczego modelu `OfficialTrainingCard` i nie pokazujemy go
kursantowi jako dokumentu urzedowego.

## Zrodla

- [Ustawa o kierujacych pojazdami - tekst jednolity, Dz. U. 2025 poz. 1226](https://api.sejm.gov.pl/eli/acts/DU/2025/1226/text.pdf) - art. 23 i 27.
- [Rozporzadzenie w sprawie szkolenia osob ubiegajacych sie o uprawnienia do kierowania pojazdami, Dz. U. 2018 poz. 1885](https://api.sejm.gov.pl/eli/acts/DU/2018/1885/text.html) - w szczegolnosci par. 6, par. 8, par. 13-14, par. 18 i zalacznik nr 3.

To jest notatka projektowo-compliance, a nie samodzielna opinia prawna.

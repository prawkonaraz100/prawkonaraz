# PrawkoNaRaz — Classic Quiz V1
## Niezmienny kontrakt wizualny i techniczny

**Wersja szablonu:** `classic-quiz-v1`
**Film referencyjny:** `pytanie987_wariant1_quiz.mp4`
**Cel:** następny agent ma zaimplementować ten sam film, a nie projektować nowy wygląd.

> Oryginalny kod renderujący prototyp nie został zachowany razem z plikiem MP4. Niniejszy dokument jest rekonstrukcją wykonaną klatka po klatce z gotowego filmu. Wymiary, kolory, klatki, kodeki i pozycje opisane jako „zmierzone” pochodzą bezpośrednio z pliku. Parametry tła, których MP4 nie zapisuje jako metadanych, zostały odtworzone przez porównanie pikseli i od tej chwili są kanonicznymi wartościami szablonu.

---

# 1. Zasada nadrzędna

Agent wdrażający pipeline:

1. **nie zmienia wyglądu** `classic-quiz-v1`;
2. **nie dobiera nowych kolorów, fontów, odstępów ani animacji**;
3. korzysta z `classic-quiz-v1.tokens.json`;
4. korzysta z `ClassicQuizV1.constants.ts`;
5. porównuje wynik z klatkami w `reference-frames/`;
6. w przypadku potrzeby innego układu tworzy nową wersję, np. `classic-quiz-v2`, zamiast modyfikować V1.

---

# 2. Układ współrzędnych

Cały projekt został zmierzony w bazowym układzie:

```text
szerokość: 720 px
wysokość: 1280 px
proporcje: 9:16
FPS: 24
liczba klatek: 528
czas obrazu: 22,000 s
czas ścieżki audio: 22,039002 s
```

Punkt `(0, 0)` znajduje się w lewym górnym rogu.

Dla renderu 1080 × 1920:

```ts
const scale = 1080 / 720; // 1.5
```

Każde `x`, `y`, `width`, `height`, `radius`, `borderWidth` i `fontSize` należy pomnożyć przez `scale`.

**Nie przeliczaj ręcznie poszczególnych elementów.** Cała kompozycja powinna działać w logicznym canvasie 720 × 1280 i zostać przeskalowana jednolicie.

---

# 3. Parametry pliku wynikowego

Zmierzono z referencyjnego MP4:

| Parametr | Wartość |
|---|---:|
| Kontener | MP4 |
| Kodek obrazu | H.264 / AVC |
| Profil | High |
| Pixel format | `yuv420p` |
| Rozdzielczość | 720 × 1280 |
| FPS | 24 |
| Liczba klatek | 528 |
| Kodek audio | AAC-LC |
| Próbkowanie audio | 44 100 Hz |
| Kanały | stereo |
| Głośność zintegrowana | około −16,3 LUFS |
| True peak | około −1,2 dBFS |

Docelowy render 1080 × 1920 zachowuje 24 FPS i tę samą liczbę klatek.

---

# 4. Paleta kolorów

Stosować dokładnie:

| Token | HEX | RGB | Zastosowanie |
|---|---|---|---|
| `surfaceNavy` | `#0A111F` | 10, 17, 31 | główne ciemne panele |
| `buttonNavy` | `#1D2739` | 29, 39, 57 | wnętrze przycisków TAK/NIE |
| `pillNavy` | `#101930` | 16, 25, 48 | kapsułka „EGZAMIN • TAK/NIE” |
| `buttonBorder` | `#63748A` | 99, 116, 138 | obrys przycisków |
| `brandYellow` | `#F8CB14` | 248, 203, 20 | akcent marki, pasek postępu |
| `dangerRed` | `#ED4341` | 237, 67, 65 | badge, licznik, A-10 |
| `successGreen` | `#21C45D` | 33, 196, 93 | poprawna odpowiedź, A-9 |
| `white` | `#FFFFFF` | 255, 255, 255 | tekst i ramki |
| `mutedText` | `#CBD4E1` | 203, 212, 225 | tekst pomocniczy |
| `progressTrack` | `#313F54` | 49, 63, 84 | tło paska postępu |
| `assetSignYellow` | `#FFF400` | 255, 244, 0 | kolor wewnątrz grafik znaków |

Nie stosować gradientów, cieni, poświat ani tekstowego obrysu.

---

# 5. Typografia

## 5.1 Font

```text
Rodzina: Lato
Podstawowa odmiana: Lato Black
Podstawowa waga CSS: 900
Licznik: Lato Heavy, waga 800
Fallback: Arial Black, Arial, sans-serif
Letter spacing: 0
Text shadow: brak
```

Dopasowanie kształtów liter do filmu referencyjnego wskazuje na **Lato Black**. Font musi być jawnie załadowany przed renderem. Nie polegać na przypadkowym foncie systemowym.

## 5.2 Zasada pozycjonowania tekstu

- wszystkie główne napisy są wyśrodkowane;
- napisy nie mają cienia;
- większość napisów jest wersalikami;
- nie stosować automatycznego `letter-spacing`;
- używać ręcznie ustalonych podziałów wierszy;
- dla pytania zmniejszać font tylko wtedy, gdy tekst nie mieści się w polu.

---

# 6. Warstwy globalne

Kolejność od dołu:

```text
1. BackgroundImage
2. BackgroundDarkening
3. Aktualna scena
4. Logo PrawkoNaRaz
5. Pasek postępu
```

Logo oraz pasek postępu pozostają widoczne przez cały film.

---

# 7. Tło

Tłem każdej sceny jest ten sam obraz, który występuje jako medium pytania.

Kanoniczne parametry:

```text
fit: cover
object-position: 68% 50%
blur: 40 px w układzie 720 × 1280
brightness: 60%
overscan scale: 1.08
```

Przykład CSS:

```tsx
<Img
  src={source}
  style={{
    position: 'absolute',
    inset: 0,
    width: '100%',
    height: '100%',
    objectFit: 'cover',
    objectPosition: '68% 50%',
    filter: 'blur(40px) brightness(0.60)',
    transform: 'scale(1.08)',
  }}
/>
```

Nie dodawać dodatkowego gradientu. Tło jest nieruchome.

---

# 8. Logo

Widoczny obszar w filmie:

```text
x: 32–223
y: 31–49
```

Kontener:

```text
x: 30
y: 27
width: 198
height: 25
```

Składa się z dwóch napisów:

| Fragment | Pozycja rysowania | Font | Kolor |
|---|---|---:|---|
| `PRAWKO` | x=30, y=25 | Lato Black 23 px | `#FFFFFF` |
| `NARAZ.PL` | x=138, y=31 | Lato Black 17 px | `#F8CB14` |

Nie zastępować tego napisu nowym logotypem bez osobnej wersji szablonu.

---

# 9. Pasek postępu

```text
x: 28
y: 1257
width: 665
height: 8
border-radius: 4
track: #313F54
fill: #F8CB14
```

Wypełnienie:

```ts
const fillWidth = Math.round(665 * currentFrame / 528);
```

Ruch wyłącznie liniowy. Pasek jest jedynym stale animowanym elementem poza zmianą cyfr licznika.

---

# 10. Timeline klatka po klatce

Zakresy mają postać `[start, end)`, czyli klatka końcowa nie należy do sceny.

| Scena | Klatki | Początek | Długość |
|---|---:|---:|---:|
| Intro | 0–95 | 0,000 s | 4,000 s |
| Pytanie | 96–216 | 4,000 s | 5,0417 s |
| Licznik 3 | 217–236 | 9,0417 s | 0,8333 s |
| Licznik 2 | 237–256 | 9,8750 s | 0,8333 s |
| Licznik 1 | 257–276 | 10,7083 s | 0,8333 s |
| Wyjście licznika | 277–280 | 11,5417 s | 0,1667 s |
| Odpowiedź | 281–339 | 11,7083 s | 2,4583 s |
| Wyjaśnienie A-10 | 340–448 | 14,1667 s | 4,5417 s |
| Porównanie A-9/A-10 | 449–527 | 18,7083 s | 3,2917 s |

Przejścia między scenami są **twardymi cięciami**. Nie stosować fade, slide, spring, zoom ani crossfade.

---

# 11. Drzewo komponentów

```tsx
<ClassicQuizV1>
  <BlurredQuestionBackground />
  <SceneSwitch>
    <IntroScene />
    <QuestionScene />
    <CountdownScene digit={3 | 2 | 1} />
    <AnswerScene />
    <ExplanationScene />
    <ComparisonScene />
  </SceneSwitch>
  <PrawkoNaRazWordmark />
  <LinearProgress />
</ClassicQuizV1>
```

Komponenty nie mogą samodzielnie zmieniać układu w zależności od „gustu” agenta.

---

# 12. Scena Intro

## Panel

```text
x: 35
y: 310
width: 651
height: 631
radius: 36
fill: #0A111F
```

## Badge pytania

```text
x: 66
y: 350
width: 190
height: 38
radius: 19
fill: #ED4341
```

Tekst:

```text
PYTANIE 987
Lato Black
24 px
#FFFFFF
wyśrodkowany
```

## Nagłówek

Linia 1:

```text
MASZ 3
font: Lato Black 72 px
kolor: #F8CB14
widoczny górny piksel: y=464
środek: x=360
```

Linia 2:

```text
SEKUNDY
font: Lato Black 72 px
kolor: #F8CB14
widoczny górny piksel: y=522
środek: x=360
```

## Prompt

```text
TAK czy NIE?
font: Lato Black 58 px
kolor: #FFFFFF
widoczny górny piksel: y=635
środek: x=360
```

## Okrąg z cyfrą

```text
centerX: 360
centerY: 802
radius: 96
stroke: 4 px #FFFFFF
fill: transparent
```

Cyfra:

```text
3
font: Lato Heavy 86 px
kolor: #FFFFFF
widoczny bbox: x=341, y=776, width=43, height=64
```

---

# 13. Scena pytania

## Ramka medium

```text
x: 28
y: 112
width: 664
height: 377
radius: 26
border: 3 px #FFFFFF
```

Wewnętrzny obraz:

```text
x: 30
y: 115
width: 660
height: 371
fit: cover
```

## Kapsułka kategorii

```text
x: 42
y: 510
width: 252
height: 38
radius: 19
fill: #101930
```

Tekst:

```text
EGZAMIN ● TAK/NIE
Lato Black 22 px
#FFFFFF
```

## Treść pytania

Pole:

```text
x: 56
y: 577
width: 608
height: 132
```

Typografia:

```text
Lato Black 38 px
line-height: 41 px
max 3 linie
minimum: 30 px
kolor: #FFFFFF
wyrównanie: center
```

Dokładny podział pytania 987:

```text
Czy jesteś ostrzegany o przejeździe
kolejowym wyposażonym w
półzapory?
```

Widoczne zakresy wierszy:

```text
wiersz 1: y=585–618
wiersz 2: y=626–659
wiersz 3: y=667–700
```

### Reguła dla nowych pytań

1. Zachowaj literalną treść.
2. Zacznij od 38 px i 41 px line-height.
3. Zmniejszaj font o 1 px do minimum 30 px.
4. Maksymalnie trzy linie.
5. Jeżeli tekst nadal się nie mieści, pipeline ma zwrócić błąd wymagający ręcznego pola `questionDisplayLines`.
6. Nie przesuwaj przycisków i nie twórz nowego layoutu.

## Przyciski

Lewy:

```text
x: 55
y: 846
width: 281
height: 120
radius: 28
fill: #1D2739
border: 3 px #63748A
```

Prawy:

```text
x: 385
y: 846
width: 281
height: 120
radius: 28
fill: #1D2739
border: 3 px #63748A
```

Tekst:

```text
Lato Black 48 px
#FFFFFF
```

---

# 14. Licznik 3–2–1

Scena pytania pozostaje bez zmian. Na niej pojawia się licznik.

Okrąg zewnętrzny:

```text
x: 277
y: 932
width: 167
height: 167
fill: #FFFFFF
```

Okrąg wewnętrzny:

```text
x: 282
y: 938
width: 156
height: 156
fill: #ED4341
```

Środek:

```text
x: 360
y: 1016
```

Cyfra:

```text
Lato Black 94 px
#FFFFFF
```

Zmiana cyfr jest twarda:

```text
3: klatki 217–236
2: klatki 237–256
1: klatki 257–276
```

Nie stosować pulsowania ani sprężyny.

---

# 15. Scena odpowiedzi

Ramka medium jest przesunięta o 5 px wyżej niż w scenie pytania:

```text
x: 28
y: 107
width: 664
height: 377
radius: 26
border: 3 px #FFFFFF
```

Zielona karta:

```text
x: 56
y: 690
width: 610
height: 300
radius: 36
fill: #21C45D
```

Etykieta:

```text
PRAWIDŁOWA ODPOWIEDŹ
Lato Black 30 px
kolor: #0A111F
widoczny top: y=735
```

Odpowiedź:

```text
NIE
Lato Black 111 px
kolor: #FFFFFF
widoczny bbox: x=276, y=828, width=173, height=82
```

---

# 16. Scena wyjaśnienia A-10

Główny panel:

```text
x: 35
y: 115
width: 651
height: 996
radius: 36
fill: #0A111F
```

Karta grafiki:

```text
x: 180
y: 160
width: 360
height: 420
radius: 28
fill: #FFFFFF
```

Obszar grafiki znaku:

```text
x: 200
y: 210
width: 320
height: 320
fit: contain
```

Badge:

```text
x: 66
y: 636
width: 122
height: 48
radius: 18
fill: #ED4341
```

Tekst badge:

```text
A-10
Lato Black 38 px
#FFFFFF
```

Tytuł:

```text
PRZEJAZD KOLEJOWY
Lato Black 42 px
#FFFFFF
widoczny top: y=732
```

Główna informacja:

```text
BEZ ZAPÓR
Lato Black 64 px
#F8CB14
widoczny top: y=790
```

Notatka:

```text
Nie ma zapór ani półzapór.
Lato Black 34 px
#CBD4E1
widoczny top: y=908
```

---

# 17. Scena porównania znaków

Panel:

```text
x: 30
y: 105
width: 661
height: 1026
radius: 34
fill: #0A111F
```

Tytuł:

```text
NIE POMYL ZNAKÓW
Lato Black 38 px
#FFFFFF
widoczny top: y=113
```

Karty znaków:

```text
lewa:  x=55,  y=180, width=260, height=310, radius=20
prawa: x=405, y=180, width=260, height=310, radius=20
fill: #FFFFFF
padding grafiki: 14 px
```

Badge A-9:

```text
x: 120
y: 526
width: 94
height: 44
radius: 18
fill: #21C45D
tekst: A-9, Lato Black 32 px, #FFFFFF
```

Badge A-10:

```text
x: 460
y: 526
width: 114
height: 44
radius: 18
fill: #ED4341
tekst: A-10, Lato Black 32 px, #FFFFFF
```

Podpisy:

```text
Z ZAPORAMI
centerX: 185
widoczny top: y=624
Lato Black 32 px
#FFFFFF

BEZ ZAPÓR
centerX: 535
widoczny top: y=624
Lato Black 32 px
#FFFFFF
```

Żółta karta pamięciowa:

```text
x: 56
y: 746
width: 610
height: 244
radius: 30
fill: #F8CB14
```

Główny tekst:

```text
PÓŁZAPORY = A-9
Lato Black 54 px
#0A111F
```

Podtytuł:

```text
W pytaniu jest A-10 → NIE
Lato Black 34 px
#0A111F
```

---

# 18. Ruch i animacje

W referencyjnym filmie zawartość poszczególnych scen jest statyczna.

Stosować:

```text
twarde cięcia scen
statyczne obrazy
statyczne panele
statyczne napisy
liniowy pasek postępu
twarda zmiana cyfr 3 → 2 → 1
```

Nie stosować:

```text
zoomu obrazu
Ken Burns
fade
slide
spring
bounce
cieni
paralaksy
animowanych gradientów
```

---

# 19. Audio

Film nie ma stałego podkładu muzycznego. Ścieżka składa się z narracji, odliczania i krótkiego sygnału potwierdzenia.

Zmierzona aktywność audio:

| Od | Do | Funkcja |
|---:|---:|---|
| 0,3546 | 1,4283 | intro, fraza 1 |
| 1,7458 | 2,4229 | intro, fraza 2 |
| 2,7491 | 3,4168 | intro, fraza 3 |
| 4,1223 | 8,5084 | odczyt pytania |
| 9,0824 | 9,4302 | „trzy” |
| 9,9082 | 10,2164 | „dwa” |
| 10,6599 | 11,0890 | „jeden” |
| 11,6894 | 13,1188 | poprawna odpowiedź |
| 13,3551 | 13,5517 | krótki sygnał |
| 14,1657 | 15,3271 | wyjaśnienie, fraza 1 |
| 15,6422 | 18,0160 | wyjaśnienie, fraza 2 |
| 18,7280 | 20,9777 | porównanie |

Kanoniczny skrypt do przyszłych renderów:

```text
Masz trzy sekundy.
Tak czy nie?
Zaczynamy.

Czy w przedstawionej sytuacji jesteś ostrzegany o przejeździe
kolejowym wyposażonym w półzapory?

Trzy.
Dwa.
Jeden.

Prawidłowa odpowiedź: nie.

A dziesięć.
Przejazd kolejowy bez zapór.

Półzapory oznacza znak A dziewięć.
```

Ten tekst jest odtworzonym kontraktem produkcyjnym. Oryginalny plik projektu TTS nie został zachowany.

Normalizacja:

```text
integrated loudness: około −16 LUFS
true peak: maksymalnie −1 dBFS
```

---

# 20. Dane wejściowe komponentu

Minimalny kontrakt:

```ts
type ClassicQuizV1Props = {
  questionId: number;
  questionLiteral: string;
  questionDisplayLines: [string, string?, string?];

  answer: 'TAK' | 'NIE';

  sourceMedia: string;
  backgroundFocalXPercent: number; // dla pytania 987: 68

  explanation: {
    asset: string;
    badge: string;
    title: string;
    keyText: string;
    note: string;
  };

  comparison: {
    title: string;
    left: {
      asset: string;
      badge: string;
      label: string;
      color: 'green' | 'red';
    };
    right: {
      asset: string;
      badge: string;
      label: string;
      color: 'green' | 'red';
    };
    memoryMain: string;
    memorySub: string;
  };

  voiceover: string;
};
```

Jeżeli pytanie nie pasuje do struktury porównania dwóch elementów, nie wolno deformować V1. Należy utworzyć nowy szablon.

---

# 21. Walidacja wizualna

Agent ma wyrenderować klatki:

```text
0,2 s
4,0 s
9,2 s
12,0 s
14,5 s
20,5 s
```

i porównać je z katalogiem `reference-frames`.

Minimalne kryteria:

```text
identyczna geometria paneli: tolerancja ±2 px
identyczna pozycja tekstów: tolerancja ±2 px
kolory płaskich powierzchni: różnica maks. 3 wartości RGB
font: Lato Black / Lato Heavy
brak dodatkowych efektów
dokładne granice scen
dokładne 528 klatek
```

Zalecany test obrazowy:

```text
SSIM >= 0,97 dla całej klatki
lub
pixel-diff MAE <= 6
```

Różnice w samym źródłowym obrazie i antyaliasingu fontu mogą wymagać osobnej maski tolerancji.

---

# 22. Zakazane samowolne zmiany

Bez nowego numeru wersji nie wolno zmieniać:

- rozdzielczości logicznego canvasu;
- 24 FPS;
- długości 528 klatek;
- fontu;
- palety;
- wymiarów paneli;
- promieni narożników;
- umiejscowienia logo;
- przycisków;
- paska postępu;
- kolejności scen;
- sposobu zmiany cyfr;
- twardych cięć;
- układu sceny porównawczej.

---

# 23. Pliki będące częścią kontraktu

```text
CLASSIC_QUIZ_V1_DESIGN_SPEC.md
classic-quiz-v1.tokens.json
ClassicQuizV1.constants.ts
reference-frames/
  00-intro-0.2s.png
  01-question-4.0s.png
  02-countdown-9.2s.png
  03-answer-12.0s.png
  04-explanation-14.5s.png
  05-comparison-20.5s.png
```

Agent powinien skopiować cały katalog do repozytorium, np.:

```text
docs/video-templates/classic-quiz-v1/
```

i traktować go jako źródło prawdy.

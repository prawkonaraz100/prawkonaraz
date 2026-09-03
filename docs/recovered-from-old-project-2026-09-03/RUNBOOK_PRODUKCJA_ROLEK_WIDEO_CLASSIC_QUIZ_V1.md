# RUNBOOK: PRODUKCJA ROLEK WIDEO — CLASSIC QUIZ V1

## Cel i punkt wejścia dla agenta

To jest kanoniczny runbook do produkowania pionowych rolek edukacyjnych PrawkoNaRaz
w zatwierdzonym formacie Classic Quiz V1. Gdy zadanie mówi „utwórz rolkę”, „przygotuj
quiz wideo” albo „wygeneruj rolkę z publicznego pytania”, zacznij właśnie tutaj.

Implementacja pipeline'u znajduje się w:

    tools/video/produkcja-rolek-video-classic-quiz-v1

W tym formacie stałe są: layout, kolory, font, animacje, sekwencja scen, timing
i jakość pliku. Zmieniają się wyłącznie dane pytania, assety oraz tekst lektora.

## Kiedy używać tego blueprintu

Użyj Classic Quiz V1 tylko dla publicznego pytania TAK/NIE, którego wyjaśnienie można
pokazać przez główny znak oraz krótkie porównanie dwóch znaków. Jeżeli pytanie nie
mieści się w tym układzie, nie zmieniaj geometrii szablonu — przygotuj osobny wariant
formatu.

## Szybki proces produkcyjny

1. Wejdź do katalogu pipeline'u:

       cd tools/video/produkcja-rolek-video-classic-quiz-v1

2. Utwórz paczkę nowej rolki, na przykład dla pytania 941:

       npm run new-content -- -ContentId p941 -QuestionNumber 941

3. Uzupełnij plik content/p941.json na podstawie publicznego pytania:

   - obraz pytania i znaki umieść w public/;
   - zachowaj dokładnie trzy linie pytania;
   - wpisz poprawną odpowiedź TAK albo NIE;
   - przygotuj krótkie wyjaśnienie i porównanie znaków;
   - w question-short podaj naturalną, skróconą narrację pytania.

4. Wygeneruj voice-over ElevenLabs:

       npm run voiceover -- -ContentId p941

5. Wyrenderuj finalny MP4:

       npm run render -- -ContentId p941

6. Uruchom bramkę jakości:

       npm run qa -- -ContentId p941

## Co pipeline chroni automatycznie

- dokładnie trzy linie pytania i limity tekstu dla każdego pola;
- obecność wymaganych obrazów i znaków;
- jedenaście stałych sekwencji narratora oraz ich okna czasowe;
- odświeżenie tylko zmienionego fragmentu audio po edycji tekstu;
- finalny format 1080 × 1920, 24 fps, 528 klatek, H.264 High i AAC-LC;
- miks lektora w zakresie od -17 do -15 LUFS, bez clippingu.

## Definition of done

Rolka jest gotowa dopiero, gdy komenda qa zakończy się powodzeniem. Finalny plik
znajduje się w:

    tools/video/produkcja-rolek-video-classic-quiz-v1/output/

Szczegółowy kontrakt JSON, limity treści i opcjonalny podgląd 720 × 1280 znajdują
się w README wewnątrz katalogu pipeline'u.

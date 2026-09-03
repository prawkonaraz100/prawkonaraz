from __future__ import annotations

import json
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = (
    ROOT
    / "storage"
    / "app"
    / "manual"
    / "question-explanation-overlap-only-quality-overrides.json"
)
SUMMARY_PATH = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "overlap-only-review"
    / "quality-overrides-summary.json"
)


OVERRIDES: dict[str, str] = {
    "6047": "Nie. Znak D-9 oznacza autostrade, a na autostradzie nie wolno poruszac sie czterokolowcem lekkim. Dopuszczone sa tam tylko pojazdy, ktore na rownej jezdni moga rozwinac co najmniej 40 km/h, z wyjatkiem czterokolowca.",
    "6014": "Tak. Znak A-32 ostrzega o mozliwym oszronieniu albo gololedzi, a wiec o ryzyku utraty przyczepnosci. Taki znak nakazuje zwiekszyc ostroznosc i dostosowac predkosc do warunkow.",
    "13132": "Nie. Pieszy jest po drugiej stronie wysepki, a przy drodze dwujezdniowej kazde przejscie traktuje sie odrebnie. W tej chwili nie znajduje sie na przejsciu, przez ktore przejezdzasz, wiec nie musisz mu ustepowac pierwszenstwa.",
    "7355": "Tak. Znak A-7 uprzedza, ze zblizasz sie do skrzyzowania z droga z pierwszenstwem. Pojazd nadjezdzajacy z prawej strony jedzie droga uprzywilejowana, dlatego musisz mu ustapic.",
    "1431": "Nie. Obok linii ciaglej wyznaczajacej krawedz jezdni nie wolno zatrzymac pojazdu ani na jezdni, ani na poboczu. Samo pobocze nie tworzy tu legalnego miejsca zatrzymania.",
    "7365": "Tak. Sygnalizator kierunkowy S-3e zezwala na wjazd dopiero przy zielonej strzalce. Skoro zielony sygnal nie jest nadawany, aktualny sygnal zabrania wjazdu za sygnalizator.",
    "6081": "Nie. O wjezdzie decyduje tutaj osoba kierujaca ruchem, a jej sygnaly maja pierwszenstwo przed sygnalizacja i znakami. Po podniesieniu reki, a nastepnie przy ustawieniu przodem lub tylem do nadjezdzajacych, wjazd na skrzyzowanie jest zabroniony.",
    "1462": "Nie. Obok linii ciaglej wyznaczajacej krawedz jezdni nie wolno zatrzymac ani parkowac pojazdu na jezdni i na poboczu. Dlatego nie mozesz tu pozostawic pojazdu na poboczu.",
    "10237": "Nie. Obok linii ciaglej wyznaczajacej krawedz jezdni nie wolno zatrzymac pojazdu ani na jezdni, ani na poboczu. W tej sytuacji zatrzymanie na poboczu jest zabronione.",
    "6513": "Poprawna jest odpowiedz C. Obowiazkowym wyposazeniem motoroweru jest co najmniej jedno swiatlo barwy bialej z przodu, obok m.in. hamulcow, sygnalu dzwiekowego i tylnego odblasku. Pozostale odpowiedzi nie wskazuja wymaganego elementu.",
    "4474": "Nie. Pojazd z lewej strony wyjezdza z posesji, czyli wlacza sie do ruchu. To on ma obowiazek zachowac szczegolna ostroznosc i ustapic pierwszenstwa pojazdom juz poruszajacym sie droga.",
    "1107": "Tak. Znaki C-12 i A-7 oznaczaja, ze pierwszenstwo ma pojazd znajdujacy sie juz na rondzie. Skoro pojazd nadjezdzajacy z lewej strony jest na skrzyzowaniu, musisz ustapic mu pierwszenstwa.",
    "1171": "Tak. Znaki C-12 i A-7 oznaczaja, ze pierwszenstwo ma pojazd znajdujacy sie juz na rondzie. Skoro pojazd nadjezdzajacy z lewej strony jest na skrzyzowaniu, musisz ustapic mu pierwszenstwa.",
    "1395": "Tak. Mozesz kontynuowac wyprzedzanie, bo masz odpowiednia widocznosc i dostatecznie duzo miejsca, a manewr nie utrudni ruchu innym uczestnikom. Pojazd jadacy przed Toba nie sygnalizuje tez zamiaru wyprzedzania, skretu ani zmiany pasa.",
    "1561": "Nie. Zachowanie pieszego wskazuje, ze moze wejsc na jezdnie, dlatego musisz stosowac zasade ograniczonego zaufania. Trzeba obserwowac go szczegolnie uwaznie i byc gotowym do zwolnienia albo zatrzymania.",
    "1829": "Nie. Pojazd z lewej strony wyjezdza z posesji, czyli wlacza sie do ruchu. To on ma obowiazek zachowac szczegolna ostroznosc i ustapic pierwszenstwa pojazdom juz poruszajacym sie droga.",
    "2391": "Tak. Zblizajac sie do przejscia dla pieszych musisz zachowac szczegolna ostroznosc i ustapic pierwszenstwa pieszemu znajdujacemu sie na przejsciu albo wchodzacemu na nie. W razie potrzeby trzeba tez zatrzymac pojazd, by umozliwic mu bezpieczne przejscie.",
    "2922": "Nie. O pierwszenstwie decyduja tu znaki D-1 i tabliczka T-6a pokazujaca rzeczywisty przebieg drogi z pierwszenstwem. Jadac na wprost opuszczasz droge uprzywilejowana, dlatego musisz ustapic pojazdowi nadjezdzajacemu z prawej strony.",
    "2945": "Nie. Na zakrecie oznaczonym znakiem A-3 oraz przy linii P-3 nie wolno rozpoczynac wyprzedzania. W tej sytuacji ograniczona widocznosc i oznakowanie wykluczaja bezpieczne wykonanie manewru.",
    "3362": "Nie. Na zakrecie oznaczonym znakiem A-3 oraz przy linii P-3 nie wolno rozpoczynac wyprzedzania. W tej sytuacji ograniczona widocznosc i oznakowanie wykluczaja bezpieczne wykonanie manewru.",
    "3466": "Nie. O pierwszenstwie decyduja tu znaki D-1 i tabliczka T-6a pokazujaca rzeczywisty przebieg drogi z pierwszenstwem. Jadac na wprost opuszczasz droge uprzywilejowana, dlatego musisz ustapic pojazdowi nadjezdzajacemu z prawej strony.",
    "4358": "Nie. Na rondzie oznaczonym znakami C-12 i A-7 pierwszenstwo ma pojazd znajdujacy sie juz na skrzyzowaniu. Nie masz wiec pierwszenstwa przed pojazdem nadjezdzajacym z lewej strony.",
    "6108": "Tak. Znaki C-12 i A-7 oznaczaja, ze pierwszenstwo ma pojazd znajdujacy sie juz na rondzie. Dlatego masz pierwszenstwo przed pojazdem wjezdzajacym z prawej strony.",
    "6112": "Tak. Znak A-6a i widoczne oznakowanie wskazuja, ze poruszasz sie droga z pierwszenstwem, a pojazd z prawej strony jedzie droga podporzadkowana. Jadac na wprost masz wiec pierwszenstwo przejazdu.",
    "7280": "Nie. Znak B-20 STOP nakazuje zatrzymac sie przed wjazdem na skrzyzowanie i upewnic, ze nie utrudnisz ruchu na drodze z pierwszenstwem. Nie wolno tu skrecic w prawo bez zatrzymania.",
    "7352": "Tak. Znaki C-12 i A-7 oznaczaja, ze pierwszenstwo ma pojazd znajdujacy sie juz na rondzie. Skoro pojazd nadjezdzajacy z lewej strony jest na skrzyzowaniu, musisz ustapic mu pierwszenstwa.",
    "7357": "Tak. Znaki C-12 i A-7 oznaczaja, ze pierwszenstwo ma pojazd znajdujacy sie juz na rondzie. Skoro pojazd nadjezdzajacy z lewej strony jest na skrzyzowaniu, musisz ustapic mu pierwszenstwa.",
    "8064": "Nie. Znak A-9 ostrzega o przejezdzie kolejowym z zaporami albo polzaporami, a nie o przejezdzie bez zapor. Widoczny slupek G-1b tylko potwierdza, ze zblizasz sie do takiego przejazdu.",
    "8075": "Tak. Znak A-9 ostrzega o przejezdzie kolejowym z zaporami albo polzaporami. Widoczny slupek G-1b potwierdza, ze zblizasz sie do tego przejazdu.",
    "8101": "Nie. Obok linii ciaglej wyznaczajacej krawedz jezdni nie wolno zatrzymac pojazdu ani na jezdni, ani na poboczu. Samo pobocze nie tworzy tu legalnego miejsca zatrzymania.",
    "10122": "Tak. To znak A-6c, ktory ostrzega o skrzyzowaniu z droga podporzadkowana po lewej stronie. Gruba linia na znaku wskazuje przebieg drogi z pierwszenstwem.",
    "10933": "Tak. Wyprzedzanie na skrzyzowaniu jest dozwolone, gdy ruch jest kierowany albo gdy jest to skrzyzowanie o ruchu okreznym. Tutaj zachodza te warunki, dlatego manewr jest dopuszczalny.",
    "11108": "Nie. Obok linii ciaglej wyznaczajacej krawedz jezdni nie wolno zatrzymac pojazdu ani na jezdni, ani na poboczu. W tej sytuacji zatrzymanie na poboczu jest zabronione.",
    "13109": "Nie. Do przejscia dla pieszych zblizasz sie z pieszym znajdujacym sie na przejsciu albo wchodzacym na nie, dlatego nie mozesz po prostu kontynuowac jazdy. Najpierw musisz zachowac szczegolna ostroznosc i ustapic mu pierwszenstwa."
}


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def main() -> None:
    payload = [
        {
            "external_id": external_id,
            "resolved_explanation": explanation,
        }
        for external_id, explanation in sorted(OVERRIDES.items(), key=lambda item: int(item[0]))
    ]

    summary = {
        "output_path": str(OUTPUT_PATH),
        "resolution_count": len(payload),
        "external_ids": [item["external_id"] for item in payload],
    }

    write_json(OUTPUT_PATH, payload)
    write_json(SUMMARY_PATH, summary)
    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

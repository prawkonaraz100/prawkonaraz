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
    / "question-explanation-overlap-only-media-mismatch.json"
)
SUMMARY_PATH = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "overlap-only-review"
    / "media-mismatch-summary.json"
)


RESOLUTIONS: dict[str, str] = {
    "99": "Tak. Polecenie zatrzymania moze dawac m.in. osoba nadzorujaca bezpieczne przejscie dzieci przez jezdnie. Gdy pokazuje tarcze STOP albo daje rownowazny sygnal, masz obowiazek zatrzymac pojazd.",
    "100": "Tak. Polecenie zatrzymania moze dawac m.in. osoba nadzorujaca bezpieczne przejscie dzieci przez jezdnie. Gdy pokazuje tarcze STOP albo daje rownowazny sygnal, masz obowiazek zatrzymac pojazd.",
    "480": "Nie. Czerwony sygnal z zoltym nadal oznacza zakaz wjazdu za sygnalizator. Wolno ruszyc dopiero po zapaleniu sygnalu zielonego.",
    "1672": "Tak. Mijajac pracownikow drogowych musisz zmniejszyc predkosc i zachowac bezpieczny odstep. To sytuacja wymagajaca szczegolnej ostroznosci.",
    "2345": "Tak. Polecenie zatrzymania moze dawac m.in. osoba nadzorujaca bezpieczne przejscie dzieci przez jezdnie. Gdy pokazuje tarcze STOP albo daje rownowazny sygnal, masz obowiazek zatrzymac pojazd.",
    "2387": "Tak. Polecenie zatrzymania moze dawac m.in. osoba nadzorujaca bezpieczne przejscie dzieci przez jezdnie. Gdy pokazuje tarcze STOP albo daje rownowazny sygnal, masz obowiazek zatrzymac pojazd.",
    "2492": "Tak. Polecenie zatrzymania moze dawac m.in. osoba nadzorujaca bezpieczne przejscie dzieci przez jezdnie. Gdy pokazuje tarcze STOP albo daje rownowazny sygnal, masz obowiazek zatrzymac pojazd.",
    "2493": "Tak. Polecenie zatrzymania moze dawac m.in. osoba nadzorujaca bezpieczne przejscie dzieci przez jezdnie. Gdy pokazuje tarcze STOP albo daje rownowazny sygnal, masz obowiazek zatrzymac pojazd.",
    "3105": "Tak. Sygnalizator kierunkowy S-3e zezwala na wjazd dopiero przy zielonej strzalce. Skoro zielony sygnal nie jest nadawany, aktualny sygnal zabrania wjazdu za sygnalizator.",
    "3941": "Nie. Czerwony sygnal z zoltym nadal oznacza zakaz wjazdu za sygnalizator. Wolno ruszyc dopiero po zapaleniu sygnalu zielonego.",
    "4450": "Nie. Pozostawienie pojazdu z kluczykami w stacyjce i otwartymi drzwiami nie zabezpiecza go przed uruchomieniem przez osobe niepowolana. W czasie postoju musisz odpowiednio zabezpieczyc pojazd.",
    "6046": "Nie. Znak D-9 oznacza autostrade, a na autostradzie nie wolno poruszac sie czterokolowcem lekkim. Dopuszczone sa tam tylko pojazdy, ktore na rownej jezdni moga rozwinac co najmniej 40 km/h, z wyjatkiem czterokolowca.",
    "6078": "Nie. Nie wolno wjezdzac na przejazd kolejowy, gdy zapory sa opuszczone, zaczely sie opuszczac albo nie zakonczyly podnoszenia. Sama nietypowa pozycja zapor nie daje prawa do wjazdu.",
    "7285": "Tak. Znak D-2 oznacza koniec drogi z pierwszenstwem, a znak A-7 uprzedza o skrzyzowaniu z droga z pierwszenstwem. Jestes wiec informowany o koncu drogi uprzywilejowanej.",
    "10258": "Nie. Obok linii ciaglej wyznaczajacej krawedz jezdni nie wolno zatrzymac ani parkowac pojazdu na jezdni i na poboczu. Dlatego nie mozesz tu zaparkowac na poboczu.",
    "10454": "Nie. Zielona strzalka przy czerwonym sygnale pozwala skrecic dopiero po zatrzymaniu pojazdu przed sygnalizatorem i upewnieniu sie, ze nie utrudnisz ruchu. Bez zatrzymania manewr jest niedozwolony.",
    "10852": "Tak. Podczas wyprzedzania zawsze musisz zachowac szczegolna ostroznosc i bezpieczny odstep od wyprzedzanego uczestnika ruchu. Sam manewr wyprzedzania z definicji wymaga takiej uwagi.",
    "10918": "Nie. Czerwony sygnal na sygnalizatorze S-1 zakazuje wjazdu za sygnalizator. W tej sytuacji nie mozesz jechac na wprost przez skrzyzowanie.",
    "12556": "Tak. Postoj za slupkiem wskaznikowym przed przejazdem kolejowym jest zabroniony. Zakaz obejmuje odcinek od przejazdu do slupka z jedna kreska po obu stronach drogi.",
    "12640": "Nie. Linia pojedyncza ciagla P-2 oddziela pasy ruchu w tym samym kierunku i zakazuje jej przejezdzania. W tym miejscu nie wolno wiec zmienic pasa ruchu.",
    "12737": "Tak. Znak A-32 ostrzega o mozliwym oszronieniu albo gololedzi, a wiec o ryzyku utraty przyczepnosci. Taki znak nakazuje zwiekszyc ostroznosc i dostosowac predkosc do warunkow.",
    "13108": "Tak. Polecenie zatrzymania moze dawac m.in. osoba nadzorujaca bezpieczne przejscie dzieci przez jezdnie. Gdy pokazuje tarcze STOP albo daje rownowazny sygnal, masz obowiazek zatrzymac pojazd."
}


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def main() -> None:
    payload = [
        {"external_id": external_id, "resolved_explanation": explanation}
        for external_id, explanation in sorted(RESOLUTIONS.items(), key=lambda item: int(item[0]))
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

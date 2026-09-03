from __future__ import annotations

import json
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
SOURCE_PATH = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "ambiguous-overlap-review"
    / "generated-resolutions-safe.json"
)
OUTPUT_PATH = (
    ROOT
    / "storage"
    / "app"
    / "manual"
    / "question-explanation-ambiguous-overlap-curated.json"
)
SUMMARY_PATH = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "ambiguous-overlap-review"
    / "curated-resolutions-summary.json"
)


OVERRIDES: dict[str, str] = {
    "1413": (
        "Tak. Mozesz kontynuowac wyprzedzanie, bo masz odpowiednia widocznosc i "
        "dostatecznie duzo miejsca, a manewr nie utrudni ruchu innym uczestnikom. "
        "Pojazd jadacy przed Toba nie sygnalizuje tez zamiaru wyprzedzania, skretu "
        "ani zmiany pasa."
    ),
    "3828": (
        "Nie. O pierwszenstwie decyduja tu znaki D-1 i tabliczka T-6a pokazujaca "
        "rzeczywisty przebieg drogi z pierwszenstwem. Jadac na wprost opuszczasz droge "
        "uprzywilejowana, dlatego musisz ustapic pojazdowi nadjezdzajacemu z prawej strony."
    ),
    "4203": (
        "Nie. W tej sytuacji musisz obserwowac pieszego i zachowac zasade ograniczonego "
        "zaufania, bo jego zachowanie moze byc nieprzewidywalne. Trzeba byc gotowym do "
        "zwolnienia albo zatrzymania pojazdu."
    ),
    "4395": (
        "Nie. Swiatel drogowych wolno uzywac tylko wtedy, gdy nie oslepiaja innych "
        "kierujacych. Tutaj poruszasz sie po oswietlonej drodze i mijasz pojazdy z "
        "przeciwka, wiec musisz pozostac przy swiatlach mijania."
    ),
    "6222": (
        "Nie. Podczas bycia wyprzedzanym nie wolno oslepiac innych kierujacych swiatlami "
        "drogowymi. W tej sytuacji trzeba uzywac swiatel mijania."
    ),
    "6225": (
        "Tak. Jezeli jedziesz na swiatlach drogowych i inny pojazd Cie wyprzedza, masz "
        "obowiazek przelaczyc je na swiatla mijania. Chodzi o to, zeby nie oslepiac "
        "kierujacego jadacego przed Toba."
    ),
    "6260": (
        "Tak. Zblizajac sie do przejscia dla pieszych musisz zachowac szczegolna ostroznosc "
        "i ustapic pierwszenstwa pieszemu znajdujacemu sie na przejsciu albo wchodzacemu na nie. "
        "W razie potrzeby trzeba tez zatrzymac pojazd, by umozliwic przejscie osobie o "
        "ograniczonej sprawnosci ruchowej."
    ),
    "7329": (
        "Nie. Znak B-20 STOP nakazuje zatrzymac sie przed wjazdem na skrzyzowanie i upewnic, "
        "ze nie utrudnisz ruchu na drodze z pierwszenstwem. Nie wolno tu skrecic w prawo bez zatrzymania."
    ),
    "7350": (
        "Tak. Znaki C-12 i A-7 oznaczaja, ze pierwszenstwo ma pojazd znajdujacy sie juz na "
        "rondzie. Skoro jestes na skrzyzowaniu o ruchu okreznym, masz pierwszenstwo przed "
        "pojazdem wjezdzajacym z prawej strony."
    ),
    "7351": (
        "Nie. Na rondzie oznaczonym znakami C-12 i A-7 pierwszenstwo ma pojazd znajdujacy sie "
        "juz na skrzyzowaniu. To oznacza, ze nie masz pierwszenstwa przed pojazdem "
        "nadjezdzajacym z lewej strony."
    ),
    "7428": (
        "Nie. Sygnalu dzwiekowego wolno uzyc tylko wtedy, gdy trzeba ostrzec o bezposrednim "
        "niebezpieczenstwie. W tej sytuacji nie ma podstaw do uzycia klaksonu."
    ),
    "8062": (
        "Nie. To znak A-6c, ktory ostrzega o skrzyzowaniu z droga podporzadkowana po lewej "
        "stronie, a nie o wlocie drogi jednokierunkowej. Gruba linia na znaku wskazuje przebieg "
        "drogi z pierwszenstwem."
    ),
    "8306": (
        "Nie. Znak A-9 ostrzega o przejezdzie kolejowym z zaporami albo polzaporami, a nie o "
        "przejezdzie bez zapor. Slupek G-1b tylko potwierdza, ze zblizasz sie do takiego przejazdu."
    ),
    "8317": (
        "Tak. Znak A-9 ostrzega o przejezdzie kolejowym z zaporami albo polzaporami. Widoczny "
        "slupek G-1b potwierdza, ze zblizasz sie do tego przejazdu."
    ),
    "10912": (
        "Nie. Znak D-2 oznacza koniec drogi z pierwszenstwem, a znak A-7 uprzedza, ze zblizasz "
        "sie do skrzyzowania z droga z pierwszenstwem. To znaczy, ze w tej sytuacji nie jedziesz "
        "juz droga uprzywilejowana."
    ),
    "10950": (
        "Tak. Wyprzedzanie na skrzyzowaniu jest dozwolone, gdy ruch jest kierowany albo gdy "
        "jest to skrzyzowanie o ruchu okreznym. Tutaj zachodza te warunki, dlatego manewr jest dopuszczalny."
    ),
    "13383": (
        "Tak. To znak A-6c, ktory ostrzega o skrzyzowaniu z droga podporzadkowana po lewej stronie. "
        "Gruba linia na znaku wskazuje przebieg drogi z pierwszenstwem."
    ),
    "13449": (
        "Tak. Znak A-7 razem z tabliczka T-6c pokazuje, ze zblizasz sie do skrzyzowania droga "
        "podporzadkowana. Oznacza to, ze musisz ustapic pierwszenstwa pojazdom jadacym droga glowna."
    ),
    "13782": (
        "Nie. Na drodze ekspresowej minimalny odstep zalezy od predkosci i wynosi co najmniej "
        "polowe jej wartosci wyrazonej w metrach. To oznacza, ze wymagany odstep nie zawsze wynosi 100 m."
    ),
}


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def main() -> None:
    items = load_json(SOURCE_PATH)
    override_count = 0

    for item in items:
        external_id = str(item["external_id"])
        override = OVERRIDES.get(external_id)
        if override is None:
            continue

        item["resolved_explanation"] = override
        override_count += 1

    summary = {
        "source_path": str(SOURCE_PATH),
        "output_path": str(OUTPUT_PATH),
        "resolution_count": len(items),
        "override_count": override_count,
        "overridden_external_ids": sorted(OVERRIDES.keys(), key=int),
    }

    write_json(OUTPUT_PATH, items)
    write_json(SUMMARY_PATH, summary)
    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

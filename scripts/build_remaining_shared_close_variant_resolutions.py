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
    / "question-explanation-remaining-close-variants.json"
)
SUMMARY_PATH = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "overlap-only-review"
    / "remaining-shared-conflicts"
    / "selected-close-variants-summary.json"
)


RESOLUTIONS: dict[str, str] = {
    "4364": "Wraz ze wzrostem predkosci kat widzenia kierujacego sie zaweza, dlatego trudniej dostrzec to, co dzieje sie po bokach drogi. Im szybciej jedziesz, tym wezszy zakres otoczenia obejmujesz wzrokiem.",
    "6644": "Przy zlamaniu otwartym trzeba unieruchomic dwa sasiednie stawy, aby ograniczyc ruch uszkodzonej konczyny. Rownoczesnie nalezy zabezpieczyc rane i wezwac pomoc.",
    "6738": "Do silnie krwawiacej rany stosuje sie opatrunek uciskowy wykonany z gazy lub innego czystego materialu oraz bandaża. Celem jest jak najszybsze zatamowanie krwawienia.",
    "9067": "ASR najlepiej pomaga przy gwaltownym ruszaniu na sliskiej nawierzchni, bo ogranicza poslizg kol napedzanych. Ten uklad poprawia trakcje wlasnie przy ruszaniu, a nie podczas zwyklej jazdy ze stala przyczepnoscia.",
    "10961": "W tej sytuacji policjant odstapi od zatrzymania prawa jazdy, bo przekroczenie predkosci bylo zwiazane z ratowaniem ludzkiego zycia. Ten wyjatek uchyla standardowa sankcje za przekroczenie o ponad 50 km/h w obszarze zabudowanym.",
    "11463": "Jesli pomylisz zjazd na drodze o wzmozonym ruchu, jedz dalej do miejsca, w ktorym bedzie mozliwe bezpieczne zatrzymanie pojazdu. Nie wolno gwaltownie hamowac ani cofac, bo stwarza to zagrozenie dla innych uczestnikow ruchu.",
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

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
    / "question-explanation-remaining-close-variants-second-pass.json"
)
SUMMARY_PATH = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "overlap-only-review"
    / "remaining-shared-conflicts"
    / "remaining-close-variants-second-pass-summary.json"
)


RESOLUTIONS: dict[str, str] = {
    "6708": "Poprawna jest odpowiedz A, czyli ciagnik rolniczy. W tym pytaniu trzeba wskazac pojazd, ktorym wolno kierowac na podstawie kategorii T, a nie zestaw pojazdow opisany dodatkowym warunkiem.",
    "6717": "Poziom oleju jest nieprawidlowy nie tylko wtedy, gdy spada ponizej minimum, ale takze wtedy, gdy przekracza dopuszczalne maksimum. Jesli slad na bagnecie znajduje sie powyzej znaku MAX, poziom oleju wymaga korekty.",
    "10886": "Poprawna jest odpowiedz A. Jezeli pozwolenie czasowe zostalo zatrzymane, ale pokwitowanie nadal uprawnia do uzywania pojazdu, to wlasnie to pokwitowanie trzeba miec przy sobie i okazywac podczas kontroli. Nie chodzi tutaj o dowod rejestracyjny, tylko o dokument zastepujacy zatrzymane pozwolenie czasowe.",
    "10888": "Poprawna jest odpowiedz A. Jezeli pozwolenie czasowe zostalo zatrzymane, ale pokwitowanie nadal pozwala uzywac pojazdu, to ten dokument nalezy miec przy sobie podczas kontroli drogowej. W tym pytaniu chodzi o pokwitowanie zatrzymania pozwolenia czasowego, a nie dowodu rejestracyjnego.",
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

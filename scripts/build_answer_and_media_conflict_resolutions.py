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
    / "question-explanation-answer-and-media-conflicts.json"
)
SUMMARY_PATH = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "overlap-only-review"
    / "remaining-shared-conflicts"
    / "answer-and-media-conflicts-summary.json"
)


RESOLUTIONS: dict[str, str] = {
    "3415": "Nie. Sygnaly osoby kierujacej ruchem maja pierwszenstwo przed sygnalizacja i znakami. Gdy policjant stoi do Ciebie przodem lub tylem z rozlozonymi ramionami, nie wolno wjechac na skrzyzowanie.",
    "3531": "Nie. W tej sytuacji nic nie nakazuje bezwzglednego zatrzymania pojazdu. Zatrzymanie jest konieczne tylko wtedy, gdy wynika z sygnalu, znaku STOP albo z sytuacji na drodze, a tutaj wystarczy zachowac ostroznosc i dostosowac jazde do warunkow.",
    "4205": "Tak. W tej sytuacji zawracanie jest dozwolone, bo sygnalizacja zezwala na manewr z zajmowanego pasa, a oznakowanie nie wprowadza zakazu zawracania. Manewr trzeba wykonac bezpiecznie i z uwzglednieniem innych uczestnikow ruchu.",
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

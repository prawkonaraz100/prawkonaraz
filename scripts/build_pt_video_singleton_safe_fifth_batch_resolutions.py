from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-video-singleton-safe-fifth-batch.json"


RESOLUTIONS = {
    "13121": "Poprawna jest odpowiedź Nie. W przypadku tramwaju samo wchodzenie pieszego na przejście nie oznacza jeszcze obowiązku ustąpienia pierwszeństwa w takim samym zakresie jak dla innych pojazdów. Motorniczy musi jednak stale obserwować pieszego i być gotowy do zatrzymania, gdy ten znajdzie się na torze jazdy.",
}


def main() -> None:
    payload = [
        {"external_id": external_id, "resolved_explanation": explanation}
        for external_id, explanation in sorted(RESOLUTIONS.items(), key=lambda item: int(item[0]))
    ]

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    print(
        json.dumps(
            {
                "resolution_count": len(payload),
                "output_path": str(OUTPUT_PATH),
            },
            ensure_ascii=False,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()

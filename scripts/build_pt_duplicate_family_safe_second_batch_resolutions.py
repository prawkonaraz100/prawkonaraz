from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-duplicate-family-safe-second-batch.json"


RESOLUTIONS = {
    "4049": "Poprawna jest odpowiedź Tak. Jeżeli obok jedzie wolniejszy pojazd i odstęp robi się zbyt mały, trzeba zmniejszyć prędkość, aby zachować bezpieczną odległość. Na nagraniu widać właśnie sytuację, w której tempo jazdy trzeba dostosować do otoczenia.",
    "4545": "Poprawna jest odpowiedź Tak. W tej sytuacji wykonujesz manewr wyprzedzania, bo przejeżdżasz obok wolniej jadącego pojazdu poruszającego się w tym samym kierunku. To nie jest omijanie przeszkody stojącej, lecz mijanie pojazdu będącego w ruchu.",
    "13107": "Poprawna jest odpowiedź Tak. Na nagraniu zbliżasz się do miejsca, w którym może dojść do konfliktu z innymi uczestnikami ruchu, dlatego trzeba zwiększyć uwagę tak, by móc odpowiednio szybko zareagować. W takich warunkach szczególna ostrożność jest obowiązkowa.",
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

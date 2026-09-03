from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-image-singleton-safe-fifth-batch.json"


RESOLUTIONS = {
    "2712": "Poprawna jest odpowiedź Nie. To oznakowanie poziome nie wyznacza miejsca, w którym pojazdy z prawej strony mogą legalnie wjechać na wydzielone torowisko. Motorniczy nie powinien więc odczytywać go jako zapowiedzi takiego manewru.",
    "2722": "Poprawna jest odpowiedź Nie. W tej sytuacji o ruchu tramwaju nie rozstrzyga sam znak „ustąp pierwszeństwa”, lecz właściwe oznakowanie i sygnały odnoszące się do torowiska. Motorniczy powinien ocenić sytuację według sygnałów przeznaczonych dla jego toru jazdy.",
    "3486": "Poprawna jest odpowiedź Tak. Taka sytuacja na skrzyżowaniu wymaga od motorniczego gotowości do wjazdu, ale z zachowaniem pełnej obserwacji otoczenia. Trzeba być przygotowanym do kontynuowania jazdy, gdy tylko będzie to dozwolone i bezpieczne.",
    "4538": "Poprawna jest odpowiedź Tak. Znak umieszczony pod sygnalizacją oznacza zakaz wjazdu na zwrotnicę oraz obowiązek zatrzymania i sprawdzenia prawidłowego położenia iglic. Motorniczy nie może przejechać dalej bez upewnienia się, że zwrotnica jest ustawiona właściwie.",
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

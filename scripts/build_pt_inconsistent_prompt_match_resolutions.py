from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-inconsistent-prompt-match-resolutions.json"


RESOLUTIONS = {
    "2571": "Tak. Jedziesz po torowisku w obszarze zabudowanym, obok zaparkowanych pojazdów i zabudowy, która ogranicza przewidywanie zachowań innych uczestników ruchu. W takiej sytuacji trzeba uważnie obserwować otoczenie i być gotowym do zmniejszenia prędkości lub zatrzymania.",
    "6978": "Tak. W tej sytuacji pierwszeństwo wynika z organizacji ruchu i przebiegu drogi, a nie z samej zasady prawej ręki. Pojazd nadjeżdżający z prawej strony nie ma tu pierwszeństwa przed Tobą, więc możesz jechać dalej z zachowaniem szczególnej ostrożności.",
    "6980": "Nie. W tej sytuacji nie masz obowiązku ustąpić pierwszeństwa pojazdowi nadjeżdżającemu z prawej strony, ponieważ o pierwszeństwie nie decyduje tu wyłącznie sam kierunek nadjeżdżania, lecz układ drogi i obowiązujące oznakowanie. Mimo to trzeba uważnie obserwować ruch i zachować ostrożność.",
    "6981": "Tak. W tej sytuacji masz pierwszeństwo przed pojazdem nadjeżdżającym z prawej strony, bo wynika ono z organizacji ruchu i przebiegu drogi. Sama zasada prawej ręki nie ma tu pierwszeństwa przed oznakowaniem i układem skrzyżowania.",
    "7033": "Tak. Zbliżasz się do przejścia dla pieszych i przejazdu dla rowerów, na którym są już niechronieni uczestnicy ruchu. Taka sytuacja zawsze wymaga szczególnej ostrożności, uważnej obserwacji otoczenia i gotowości do ustąpienia pierwszeństwa.",
}


def main() -> None:
    payload = [
        {"external_id": external_id, "resolved_explanation": explanation}
        for external_id, explanation in sorted(RESOLUTIONS.items(), key=lambda item: int(item[0]))
    ]

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    print(json.dumps({"resolution_count": len(payload), "output_path": str(OUTPUT_PATH)}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

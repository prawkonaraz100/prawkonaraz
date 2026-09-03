from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-image-singleton-safe-second-batch.json"


RESOLUTIONS = {
    "2519": "Poprawna jest odpowiedź Tak. W tej sytuacji sygnał dla tramwaju dopuszcza jazdę w lewo, więc możesz wykonać taki manewr. Ograniczenia dotyczące pozostałych pojazdów nie zmieniają uprawnienia wynikającego z sygnału przeznaczonego dla tramwaju.",
    "2704": "Poprawna jest odpowiedź Nie. Jeśli dla tramwaju nadawany jest sygnał zezwalający na jazdę, nie musisz czekać na zielone światło ogólne dla innych pojazdów. O tym, czy możesz jechać, decyduje tu sygnał przeznaczony właśnie dla tramwaju.",
    "2705": "Poprawna jest odpowiedź Nie. Przy takim układzie świateł nie masz prawa wjechać na skrzyżowanie, ponieważ sygnał dla tramwaju zabrania jazdy mimo że dla innych uczestników ruchu może być nadawany sygnał zielony. Motorniczy musi kierować się sygnalizatorem przeznaczonym dla tramwajów.",
    "2719": "Poprawna jest odpowiedź Nie. Po chwilowym zatrzymaniu wymuszonym przez pieszego, który wtargnął w miejscu niedozwolonym, nie włączasz się na nowo do ruchu. Kontynuujesz jazdę w tym samym strumieniu, tylko po usunięciu chwilowej przeszkody.",
    "2723": "Poprawna jest odpowiedź Tak. W tej sytuacji masz obowiązek zatrzymać tramwaj przed linią warunkowego zatrzymania. Dopiero po ocenie sytuacji i upewnieniu się, że możesz jechać bezpiecznie, wolno kontynuować jazdę.",
    "2740": "Poprawna jest odpowiedź Nie. Nie wolno ruszyć, dopóki pieszy znajduje się jeszcze na przejściu dla pieszych i nie opuścił strefy zagrożenia. Motorniczy musi zachować bezpieczeństwo pieszego nawet wtedy, gdy przejście wydaje się już prawie wolne.",
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

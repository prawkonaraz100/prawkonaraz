from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-media-safe-third-batch.json"


RESOLUTIONS = {
    "2738": "Poprawna jest odpowiedź Tak. Widoczny znak ostrzegawczy nakazuje zachować szczególną ostrożność, bo informuje o miejscu, w którym może pojawić się dodatkowe zagrożenie. Motorniczy powinien obserwować tor jazdy i otoczenie tak, aby móc szybko zareagować.",
    "3469": "Poprawna jest odpowiedź Tak. Linia wyznaczona przez prostokąty wskazuje miejsce, przed którym tramwaj ma się zatrzymać przed sygnalizatorem. W tej sytuacji nie wolno przejechać dalej aż do samego sygnału.",
    "4003": "Poprawna jest odpowiedź Tak. Omiijając pojazd do nauki jazdy, trzeba zachować szczególną ostrożność, bo kierujący takim pojazdem może wykonać manewr wolniej albo mniej przewidywalnie. Dlatego trzeba obserwować jego tor jazdy i być gotowym do reakcji.",
    "4034": "Poprawna jest odpowiedź Nie. Linia z prostokątów wyznacza miejsce zatrzymania tramwaju przed sygnalizatorem, więc nie masz prawa dojechać do samego sygnału, przejeżdżając przez tę linię. Najpierw trzeba zatrzymać się we wskazanym miejscu.",
    "4050": "Poprawna jest odpowiedź Tak. Podczas omijania zawsze trzeba zachować bezpieczny odstęp od omijanego pojazdu lub przeszkody. Na pokazanym odcinku jest to szczególnie ważne, bo miejsca jest niewiele, a sytuacja może szybko się zmienić.",
    "4068": "Poprawna jest odpowiedź Tak. Znak widoczny na pierwszym planie ostrzega o miejscu, w którym należy spodziewać się rowerzystów. Motorniczy powinien więc zwiększyć uwagę i obserwować otoczenie torowiska oraz przejazdu.",
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

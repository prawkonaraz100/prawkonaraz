from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-video-singleton-safe-second-batch.json"


RESOLUTIONS = {
    "2527": "Poprawna jest odpowiedź Tak. W tej sytuacji motorniczy ma obowiązek zachować szczególną ostrożność, ponieważ przejazd odbywa się w miejscu, gdzie tor jazdy krzyżuje się z innymi kierunkami ruchu i może pojawić się pieszy lub pojazd. Trzeba obserwować otoczenie i być gotowym do reakcji.",
    "2697": "Poprawna jest odpowiedź Tak. Widok torowiska prowadzonego tuż przy jezdni i możliwego ruchu pojazdów obok oznacza konieczność szczególnej ostrożności. Motorniczy powinien stale obserwować otoczenie i reagować odpowiednio wcześnie.",
    "2718": "Poprawna jest odpowiedź Tak. Włączając się do ruchu tramwajem, trzeba obserwować tor, na który zamierzasz wjechać, aby nie doprowadzić do konfliktu z innym pojazdem szynowym. Bez upewnienia się, że tor jest wolny, nie wolno kontynuować manewru.",
    "2720": "Poprawna jest odpowiedź Tak. Włączając się do ruchu, masz obowiązek ustąpić pierwszeństwa innemu tramwajowi, który już się porusza. Na nagraniu widać właśnie sytuację, w której najpierw trzeba przepuścić tramwaj będący w ruchu.",
    "2739": "Poprawna jest odpowiedź Tak. Wyjeżdżając z zajezdni i włączając się do ruchu, motorniczy ma obowiązek zachować szczególną ostrożność. To on włącza się do istniejącego ruchu, więc musi dokładnie ocenić sytuację przed dalszą jazdą.",
    "3492": "Poprawna jest odpowiedź Nie. Nie wolno przerywać sygnalizowania zamiaru zmiany kierunku jazdy zaraz po rozpoczęciu manewru. Kierunkowskaz powinien pozostać włączony tak długo, aż manewr zostanie zakończony i nie będzie wprowadzał innych w błąd.",
    "3493": "Poprawna jest odpowiedź Tak. Zmieniając tor jazdy w tej sytuacji, masz obowiązek użyć kierunkowskazu, bo wykonujesz zmianę kierunku jazdy widoczną dla innych uczestników ruchu. Sygnał powinien być nadany odpowiednio wcześnie.",
    "3499": "Poprawna jest odpowiedź Tak. W tej sytuacji trzeba liczyć się z tym, że kierowca może wjechać na torowisko i zablokować przejazd. Motorniczy powinien obserwować ruch obok torowiska i być przygotowany do zwolnienia albo zatrzymania.",
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

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-video-singleton-safe-third-batch.json"


RESOLUTIONS = {
    "2526": "Poprawna jest odpowiedź Nie. Nie włączasz się tu do ruchu z postoju poza jezdnią czy zajezdni, tylko kontynuujesz jazdę w istniejącym strumieniu ruchu i czekasz na możliwość przejazdu przez skrzyżowanie. Samo zatrzymanie przed sygnałem nie oznacza jeszcze włączania się do ruchu.",
    "3199": "Poprawna jest odpowiedź Tak. Jeżeli na torowisku poprzecznym porusza się tramwaj mający pierwszeństwo, musisz go przepuścić przed wjazdem na skrzyżowanie. Motorniczy powinien ocenić przebieg torów i ustąpić pojazdowi szynowemu, który ma pierwszeństwo.",
    "3913": "Poprawna jest odpowiedź Tak. Sytuacja wymaga zarówno ustąpienia pierwszeństwa, jak i szczególnej ostrożności, bo tor jazdy przecina się z innymi kierunkami ruchu. Zanim wjedziesz dalej, trzeba upewnić się, że przejazd będzie bezpieczny.",
    "3914": "Poprawna jest odpowiedź Nie. Samochód z prawej strony nie ma tu pierwszeństwa, bo o kolejności przejazdu nie decyduje wyłącznie zasada prawej ręki, lecz także oznakowanie i przebieg torowiska. Motorniczy nie powinien zakładać pierwszeństwa auta tylko na podstawie jego położenia.",
    "4029": "Poprawna jest odpowiedź Nie. Nie wolno wjeżdżać za sygnalizator, jeśli przewidujesz, że nie zdołasz opuścić skrzyżowania przed końcem nadawanego sygnału. Motorniczy nie może blokować torowiska ani skrzyżowania.",
    "7009": "Poprawna jest odpowiedź Tak. Jeśli warunki na drodze pozwalają zachować bezpieczny odstęp od rowerzysty i nie ma zakazu wynikającego z oznakowania lub sytuacji, możesz wykonać wyprzedzanie. Trzeba jednak cały czas kontrolować tor jazdy rowerzysty i własny zapas miejsca.",
    "13102": "Poprawna jest odpowiedź Tak. Na nagraniu widać sytuację, w której przed wjazdem przez torowisko trzeba ustąpić pierwszeństwa pojazdom jadącym drogą poprzeczną. Motorniczy nie może zakładać pierwszeństwa tylko dlatego, że porusza się po szynach.",
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

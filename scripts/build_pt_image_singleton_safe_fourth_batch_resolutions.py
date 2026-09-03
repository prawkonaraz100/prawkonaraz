from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-image-singleton-safe-fourth-batch.json"


RESOLUTIONS = {
    "4014": "Poprawna jest odpowiedź Tak. Znak ostrzegawczy ustawiony na torze jazdy dotyczy także motorniczego, bo ma uprzedzić o zagrożeniu na odcinku, po którym porusza się tramwaj. Motorniczy nie może go ignorować tylko dlatego, że prowadzi pojazd szynowy.",
    "4022": "Poprawna jest odpowiedź Tak. Znak umieszczony na przewodzie trakcyjnym informuje motorniczego, że zbliża się do skrzyżowania z sygnalizacją wzbudzaną przez tramwaj. To oznakowanie służy właśnie prowadzącemu tramwaj.",
    "4030": "Poprawna jest odpowiedź Nie. Skręt w lewo na takim skrzyżowaniu zawsze wymaga szczególnej ostrożności, bo tor jazdy przecina się z innymi kierunkami ruchu. Samo dopuszczenie manewru nie zwalnia motorniczego z obserwacji otoczenia.",
    "4033": "Poprawna jest odpowiedź Tak. Układ jezdni i torowiska pokazuje, że z lewej strony istnieje możliwość wjazdu pojazdów na torowisko. Motorniczy powinien przewidywać taki manewr i kontrolować tę stronę.",
    "4045": "Poprawna jest odpowiedź Tak. Za widocznym znakiem trzeba liczyć się także z pojazdami nadjeżdżającymi z prawej strony, jeśli w tym miejscu mogą uczestniczyć w konflikcie ruchu. Motorniczy musi więc ocenić sytuację na całym skrzyżowaniu, a nie tylko przed sobą.",
    "4546": "Poprawna jest odpowiedź Nie. Pieszy znajdujący się na przejściu lub wchodzący na nie korzysta z ochrony i motorniczy nie ma wobec niego pierwszeństwa. Najpierw trzeba zapewnić mu bezpieczne przejście.",
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

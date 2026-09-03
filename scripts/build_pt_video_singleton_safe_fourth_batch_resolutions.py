from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-video-singleton-safe-fourth-batch.json"


RESOLUTIONS = {
    "13106": "Poprawna jest odpowiedź Nie. Samo zbliżanie się pieszego do przejścia nie oznacza jeszcze obowiązku ustąpienia pierwszeństwa przez tramwaj. Motorniczy musi jednak obserwować pieszego i być gotowy do reakcji, gdy ten zacznie wchodzić na przejście.",
    "2524": "Poprawna jest odpowiedź Nie. W tej sytuacji nie masz obowiązku ustąpić pierwszeństwa pojazdom jadącym drogą poprzeczną, bo o kolejności przejazdu decyduje tu oznakowanie i przebieg torowiska. Sam fakt istnienia drogi poprzecznej nie odbiera tramwajowi pierwszeństwa.",
    "3194": "Poprawna jest odpowiedź Tak. W tej sytuacji tor jazdy tramwaju ma pierwszeństwo przed pojazdami poruszającymi się drogą poprzeczną. Motorniczy powinien jednak mimo to obserwować otoczenie i być gotowy do reakcji na błąd innych kierujących.",
    "3477": "Poprawna jest odpowiedź Nie. W tym miejscu nie masz pierwszeństwa przed tramwajem, który mógłby nadjechać z lewej strony. Przed wjazdem trzeba ocenić układ torów i przepuścić pojazd szynowy mający pierwszeństwo.",
    "3923": "Poprawna jest odpowiedź Tak. Skręcając w lewo, masz obowiązek odpowiednio wcześnie zasygnalizować zamiar zmiany kierunku jazdy. Taki sygnał pozwala innym uczestnikom ruchu właściwie ocenić Twój manewr.",
    "4015": "Poprawna jest odpowiedź Nie. Te znaki nie zawsze nakazują pełne zatrzymanie tramwaju. Wskazują obowiązek ustąpienia pierwszeństwa, a zatrzymanie jest konieczne tylko wtedy, gdy wymaga tego sytuacja lub inne oznakowanie.",
    "4035": "Poprawna jest odpowiedź Nie. Linia złożona z trójkątów oznacza miejsce ustąpienia pierwszeństwa, a nie bezwzględny obowiązek zatrzymania. Zatrzymanie jest potrzebne tylko wtedy, gdy sytuacja tego wymaga.",
    "4047": "Poprawna jest odpowiedź Nie. W tym miejscu przecięcia torów nie masz pierwszeństwa przed tramwajem nadjeżdżającym z lewej strony. O kolejności przejazdu decyduje układ torów i zasady pierwszeństwa, więc nie wolno zakładać swojego uprzywilejowania.",
    "4048": "Poprawna jest odpowiedź Nie. Gdy Twój tramwaj jest wyprzedzany, nie wolno zwiększać prędkości. Taki manewr utrudnia wyprzedzanie i może stworzyć zagrożenie.",
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

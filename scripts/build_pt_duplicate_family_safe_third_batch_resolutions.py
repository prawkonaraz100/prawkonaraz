from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-duplicate-family-safe-third-batch.json"


RESOLUTIONS = {
    "2672": "Poprawna jest odpowiedź Nie. Zakaz wynikający z tego znaku nie zostaje odwołany na najbliższym skrzyżowaniu dróg. W ruchu tramwajowym obowiązuje on dalej i wygasa dopiero przy najbliższym skrzyżowaniu torów lub ich rozwidleniu, chyba że wcześniej pojawi się inne oznakowanie.",
    "2688": "Poprawna jest odpowiedź Tak. Zakaz wyrażony tym znakiem zostaje odwołany na najbliższym skrzyżowaniu torów albo ich rozwidleniu. To właśnie takie miejsce kończy obowiązywanie tego zakazu w pokazanej sytuacji.",
    "2699": "Poprawna jest odpowiedź Nie. Widoczny sygnał dla tramwaju nie oznacza jeszcze bezwzględnego obowiązku zatrzymania w każdych okolicznościach. Jest to sygnał ostrzegawczy, który nakazuje przygotować się do możliwej zmiany na sygnał zakazujący dalszej jazdy.",
    "2703": "Poprawna jest odpowiedź Tak. Ten sygnał ostrzega, że za chwilę może zapalić się sygnał w kształcie kreski poziomej, czyli sygnał zakazujący jazdy. Motorniczy powinien więc przygotować się do zatrzymania tramwaju.",
    "3484": "Poprawna jest odpowiedź Tak. Gdy policjant stoi bokiem do nadjeżdżającego tramwaju, tramwaj może jechać także na wprost. W tej sytuacji sygnał dawany przez osobę kierującą ruchem pozwala kontynuować jazdę w tym kierunku.",
    "3485": "Poprawna jest odpowiedź Tak. Przy takim ustawieniu policjanta tramwaj może również skręcić w lewo. Dla tramwaju sygnały osoby kierującej ruchem dopuszczają tu więcej kierunków jazdy niż dla zwykłych pojazdów.",
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

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-media-safe-first-batch.json"
FLAGGED_CONFLICTS_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-media-review" / "flagged-conflicts.json"


RESOLUTIONS = {
    "2163": "Poprawna jest odpowiedź A. W przypadku krwotoku z nosa należy ucisnąć skrzydełka nosa i pochylić głowę do przodu, aby ograniczyć krwawienie i nie dopuścić do spływania krwi do dróg oddechowych. Odchylanie głowy do tyłu byłoby błędem.",
    "2180": "Poprawna jest odpowiedź A. Złamanie żeber najczęściej objawia się bólem nasilającym się przy głębokim oddychaniu i ruchu, bo wtedy uszkodzona okolica klatki piersiowej pracuje najmocniej. Kulawizna czy ból brzucha nie wskazują typowo na taki uraz.",
    "2188": "Poprawna jest odpowiedź A. Przy zamkniętym złamaniu kończyny dolnej i silnym bólu należy ograniczyć ruch poszkodowanego i czekać na zespół ratownictwa medycznego. Podawanie leków albo samodzielne układanie kończyny mogłoby pogorszyć stan poszkodowanego.",
    "4551": "Poprawna jest odpowiedź B. Na ilustracji widać zwrotnicę i sygnalizator jej ustawienia, ale przed wjazdem motorniczy powinien sprawdzić jedno i drugie: wskazanie sygnalizatora oraz rzeczywiste przyleganie iglic. Sam sygnalizator nie daje pełnej pewności bezpiecznego przejazdu.",
    "4552": "Poprawna jest odpowiedź A. Widoczny sygnalizator informuje o przestawieniu zwrotnicy w prawo, ale motorniczy powinien dodatkowo spojrzeć na iglice i potwierdzić ich prawidłowe ułożenie. Nie wolno opierać się wyłącznie na samym sygnale świetlnym.",
    "7070": "Poprawna jest odpowiedź A. Na zdjęciu widać układ pedałów, a hamowanie służbowe wykonuje się przez płynne wciśnięcie pedału hamulca tylko do ogranicznika, tak aby zatrzymać tramwaj w zamierzonym miejscu. Przekraczanie ogranicznika albo zwalnianie czuwaka oznacza już inny tryb hamowania.",
    "7113": "Poprawna jest odpowiedź C. To samo stanowisko sterowania służy tu do nagłego hamowania: trzeba wcisnąć pedał hamulca poza ogranicznik i jednocześnie zwolnić dźwignię czuwaka. Samo mocniejsze naciśnięcie hamulca bez zwolnienia czuwaka nie odpowiada tej procedurze.",
}

FLAGGED_CONFLICTS = [
    {
        "external_ids": ["4104", "4105"],
        "reason": "Oba rekordy mają ten sam prompt i ten sam obraz, ale różne poprawne odpowiedzi. Wymagają ręcznej weryfikacji źródła przed napisaniem wyjaśnienia.",
        "media_fingerprint": "full.132469d96fee.jpg",
    }
]


def main() -> None:
    payload = [
        {"external_id": external_id, "resolved_explanation": explanation}
        for external_id, explanation in sorted(RESOLUTIONS.items(), key=lambda item: int(item[0]))
    ]

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    FLAGGED_CONFLICTS_PATH.parent.mkdir(parents=True, exist_ok=True)
    FLAGGED_CONFLICTS_PATH.write_text(json.dumps(FLAGGED_CONFLICTS, ensure_ascii=False, indent=2), encoding="utf-8")

    print(
        json.dumps(
            {
                "resolution_count": len(payload),
                "flagged_conflict_count": len(FLAGGED_CONFLICTS),
                "output_path": str(OUTPUT_PATH),
                "flagged_conflicts_path": str(FLAGGED_CONFLICTS_PATH),
            },
            ensure_ascii=False,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()

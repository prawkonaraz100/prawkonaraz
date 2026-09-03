from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-duplicate-family-safe-first-batch.json"


RESOLUTIONS = {
    "2737": "Poprawna jest odpowiedź Tak. Widoczny znak ostrzegawczy informuje o robotach drogowych, więc nakazuje zachować szczególną ostrożność i dobrać prędkość tak, aby móc szybko zareagować. W takim miejscu sytuacja na drodze może zmienić się nagle.",
    "3460": "Poprawna jest odpowiedź Nie. Ograniczenie prędkości umieszczone pod tym znakiem nie przestaje obowiązywać automatycznie wraz z wyjazdem z obszaru robót. Obowiązuje ono do miejsca odwołania albo do najbliższego skrzyżowania lub rozwidlenia torów, jeśli inne znaki nie stanowią inaczej.",
    "4016": "Poprawna jest odpowiedź Tak. Znak ostrzegawczy z tabliczką wskazuje, że roboty drogowe rozpoczną się w odległości nie większej niż 100 metrów. Oznacza to konieczność przygotowania się na utrudnienia już za chwilę.",
    "4018": "Poprawna jest odpowiedź Tak. Ograniczenie prędkości widoczne na znaku obowiązuje do najbliższego skrzyżowania albo przecięcia czy rozwidlenia torów, o ile wcześniej nie zostanie odwołane innym znakiem. Nie wolno samodzielnie uznać, że przestaje obowiązywać wcześniej.",
    "4106": "Poprawna jest odpowiedź B. Znak zakazu z podaną wartością prędkości oznacza, że na tym odcinku motorniczy nie może jechać szybciej, niż wskazuje znak. To ograniczenie obowiązuje niezależnie od tego, że tor może wydawać się wolny.",
    "4568": "Poprawna jest odpowiedź Tak. Ograniczenie prędkości określone tym znakiem obowiązuje motorniczego do najbliższego skrzyżowania albo przecięcia czy rozwidlenia torów, chyba że wcześniej zostanie zmienione innym oznakowaniem. Tak właśnie należy odczytywać ten zakaz w ruchu tramwajowym.",
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

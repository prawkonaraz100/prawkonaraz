from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-image-singleton-safe-first-batch.json"


RESOLUTIONS = {
    "13105": "Poprawna jest odpowiedź Tak. Tylne światła przeciwmgłowe wolno stosować tylko wtedy, gdy widoczność jest ograniczona do 50 metrów lub mniej. Jeśli widoczność jest większa, trzeba je wyłączyć, żeby nie oślepiać innych uczestników ruchu.",
    "2513": "Poprawna jest odpowiedź Tak. Znaki ostrzegawcze zawsze zobowiązują kierującego do zachowania szczególnej ostrożności, bo informują o możliwym zagrożeniu na drodze lub torowisku. Motorniczy musi być gotowy do szybkiej reakcji na rozwój sytuacji.",
    "2514": "Poprawna jest odpowiedź Tak. Kierujący tramwajem ma obowiązek stosować się do znaków ostrzegawczych tak samo jak do innych znaków drogowych. Ich zadaniem jest uprzedzić o zagrożeniu i wymusić ostrożniejszą jazdę.",
    "2660": "Poprawna jest odpowiedź Tak. Widoczny znak ostrzega o miejscu szczególnie uczęszczanym przez dzieci. Oznacza to konieczność zwiększenia uwagi i gotowości do natychmiastowej reakcji.",
    "2669": "Poprawna jest odpowiedź Tak. Znak ostrzegawczy pokazany na ilustracji informuje, że pojazdy mogą wjeżdżać na torowisko. Motorniczy powinien więc obserwować otoczenie i liczyć się z możliwością nagłego pojawienia się pojazdu na torze.",
    "2702": "Poprawna jest odpowiedź Tak. Sygnały świetlne mają pierwszeństwo przed znakami drogowymi regulującymi pierwszeństwo przejazdu. Gdy działa sygnalizacja, to właśnie do niej trzeba dostosować sposób jazdy.",
    "3098": "Poprawna jest odpowiedź Tak. Przejeżdżając obok tramwaju stojącego na przystanku nieprzylegającym do chodnika, trzeba zachować szczególną ostrożność. Pasażerowie mogą wchodzić na jezdnię lub torowisko, dlatego sytuacja wymaga zwiększonej uwagi.",
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

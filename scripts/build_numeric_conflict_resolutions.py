from __future__ import annotations

import json
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = (
    ROOT
    / "storage"
    / "app"
    / "manual"
    / "question-explanation-numeric-conflicts.json"
)
SUMMARY_PATH = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "overlap-only-review"
    / "remaining-shared-conflicts"
    / "numeric-conflicts-summary.json"
)


RESOLUTIONS: dict[str, str] = {
    "3617": "Poprawna jest odpowiedz A, czyli 140 km/h. Widoczny znak oznacza autostrade, a dla samochodu osobowego maksymalna dopuszczalna predkosc na autostradzie wynosi wlasnie 140 km/h.",
    "3618": "Poprawna jest odpowiedz A, czyli 90 km/h. Widoczny znak oznacza wyjazd z obszaru zabudowanego, a po opuszczeniu obszaru zabudowanego samochodem osobowym wolno jechac maksymalnie 90 km/h, o ile znaki nie stanowia inaczej.",
    "3620": "Poprawna jest odpowiedz A, czyli 20 km/h. Widoczny znak oznacza strefe zamieszkania, a w takiej strefie obowiazuje bardzo niski limit predkosci - maksymalnie 20 km/h.",
    "6604": "Poprawna jest odpowiedz A. Prawo jazdy kategorii A1 uprawnia miedzy innymi do kierowania motocyklem trojkolowym o mocy nieprzekraczajacej 15 kW. Nie sprowadza sie tylko do uprawnienia na motorower.",
    "6608": "Poprawna jest odpowiedz A. Prawo jazdy kategorii A2 obejmuje rowniez motocykl trojkolowy o mocy nieprzekraczajacej 15 kW. Dlatego w tym pytaniu nieprawidlowa jest odpowiedz ograniczajaca to uprawnienie jedynie do motoroweru.",
    "6730": "Poprawna jest odpowiedz A, czyli 999. W tym pytaniu chodzi o bezposrednie wezwanie karetki pogotowia ratunkowego, a numer 999 jest numerem tej sluzby. To on jest tutaj wskazany jako wlasciwy.",
    "6739": "Poprawna jest odpowiedz A, czyli 80 km/h. Po minięciu tego znaku samochodem ciezarowym o dopuszczalnej masie calkowitej powyzej 3,5 t nie wolno jechac szybciej niz 80 km/h na tej drodze.",
    "11517": "Poprawna jest odpowiedz A. Ladunek wystajacy ponad 0,5 m poza tylna plaszczyzne obrysu samochodu ciezarowego trzeba oznakowac elementem z pasami bialymi i czerwonymi o lacznej powierzchni co najmniej 1000 cm2. Taki sposob oznakowania spelnia wymog widocznosci ladunku od tylu.",
    "11518": "Poprawna jest odpowiedz A. Ladunek wystajacy z tylu moze byc oznakowany pasami bialymi i czerwonymi o wymaganej powierzchni umieszczonymi bezposrednio na ladunku. Najwazniejsze jest poprawne oznaczenie wystajacej czesci zgodnie z wymaganymi barwami i powierzchnia.",
}


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def main() -> None:
    payload = [
        {"external_id": external_id, "resolved_explanation": explanation}
        for external_id, explanation in sorted(RESOLUTIONS.items(), key=lambda item: int(item[0]))
    ]
    summary = {
        "output_path": str(OUTPUT_PATH),
        "resolution_count": len(payload),
        "external_ids": [item["external_id"] for item in payload],
    }
    write_json(OUTPUT_PATH, payload)
    write_json(SUMMARY_PATH, summary)
    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

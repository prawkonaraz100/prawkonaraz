from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-image-singleton-safe-third-batch.json"


RESOLUTIONS = {
    "13101": "Poprawna jest odpowiedź Tak. Widzisz oznakowanie wskazujące, że zbliżasz się do przecięcia z torowiskiem mającym pierwszeństwo przejazdu. Motorniczy powinien zakładać, że w takim miejscu o pierwszeństwie decyduje właśnie układ torowiska i towarzyszące mu oznakowanie.",
    "2716": "Poprawna jest odpowiedź Tak. Układ jezdni i wyznaczone miejsce przecięcia z wydzielonym torowiskiem oznaczają, że kierujący samochodami mogą tu na nie wjechać. Motorniczy powinien obserwować prawą stronę i być gotowy do zwolnienia albo zatrzymania.",
    "3496": "Poprawna jest odpowiedź Tak. Jeżeli pojazd znalazł się już na skrzyżowaniu i opuszcza je, trzeba umożliwić mu dokończenie manewru zamiast go blokować. Dotyczy to także tramwaju zbliżającego się do skrzyżowania.",
    "3502": "Poprawna jest odpowiedź Tak. Gdy pieszy nadal znajduje się na przejściu, motorniczy musi zaczekać, aż opuści tor jazdy i strefę zagrożenia. Dopiero wtedy wolno bezpiecznie kontynuować przejazd.",
    "3917": "Poprawna jest odpowiedź Tak. Znak ostrzegający o oszronieniu jezdni informuje o możliwości pogorszenia przyczepności i wydłużenia drogi hamowania. Dla motorniczego oznacza to obowiązek zachowania szczególnej ostrożności.",
    "4002": "Poprawna jest odpowiedź Nie. Linia ciągła wyznacza granicę, której inny pojazd nie powinien przekraczać, więc nie zezwala na wjazd na torowisko. Dopuszczenie takiego manewru musiałoby wynikać z innego, wyraźnego oznakowania.",
    "4008": "Poprawna jest odpowiedź Tak. Uniesiona ręka policjanta oznacza, że za chwilę nastąpi zmiana sygnału albo sposobu kierowania ruchem. Motorniczy powinien wtedy zachować czujność i przygotować się do reakcji.",
    "4010": "Poprawna jest odpowiedź Tak. Takie oznakowanie ostrzega o miejscu, w którym na przejazd może wjechać osoba jadąca rowerem. Dlatego motorniczy musi być przygotowany na pojawienie się rowerzysty.",
    "4012": "Poprawna jest odpowiedź Nie. Ten znak ostrzegawczy nie nadaje tramwajowi pierwszeństwa przejazdu, tylko informuje o szczególnym miejscu lub zagrożeniu związanym z ruchem tramwajów. O pierwszeństwie decydują inne znaki, sygnały albo ogólne zasady ruchu.",
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

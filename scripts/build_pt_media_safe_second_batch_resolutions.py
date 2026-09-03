from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-media-safe-second-batch.json"


RESOLUTIONS = {
    "2616": "Poprawna jest odpowiedź Nie. Widoczny znak ostrzega o miejscu częstego przechodzenia dzieci, ale nie oznacza, że dzieci są tu przeprowadzane przez uprawnioną osobę. Na ilustracji widać zwykłe przejście w rejonie, gdzie trzeba zachować szczególną ostrożność.",
    "2617": "Poprawna jest odpowiedź Tak. Znak ostrzegający o dzieciach przy przejściu oznacza, że w tym miejscu trzeba spodziewać się częstego pojawiania się dzieci na drodze lub torowisku. Dlatego motorniczy powinien zwiększyć uwagę i być gotowy do reakcji.",
    "3507": "Poprawna jest odpowiedź Tak. Na przejeździe dla rowerzystów, na którym ruch jest kierowany, wyprzedzanie jest dopuszczalne. Sama obecność przejazdu rowerowego nie tworzy tu bezwzględnego zakazu, jeśli ruchem steruje sygnalizacja lub uprawniona organizacja ruchu.",
    "3910": "Poprawna jest odpowiedź Nie. Na pokazanym przejeździe rowerowym tramwaj nie ma pierwszeństwa przed rowerzystą tylko z tego powodu, że jest pojazdem szynowym. W takiej sytuacji trzeba uwzględnić zasady obowiązujące na przejeździe i zachować szczególną ostrożność.",
    "4004": "Poprawna jest odpowiedź Tak. Na zdjęciu widać znak ustąp pierwszeństwa ustawiony przed rondem, więc wjeżdżając na takie skrzyżowanie trzeba ustąpić pojazdom, które już się na nim znajdują. Sam fakt poruszania się tramwajem nie znosi tego obowiązku w tej sytuacji.",
    "4007": "Poprawna jest odpowiedź Tak. Widoczna linia łamana przy krawędzi jezdni wyznacza przystanek, więc przejeżdżając obok takiego miejsca trzeba zachować szczególną ostrożność ze względu na pasażerów i możliwe wtargnięcie pieszych. To miejsce wymaga zwiększonej uwagi.",
    "4011": "Poprawna jest odpowiedź Nie. Skoro przed wjazdem na rondo widoczny jest znak ustąp pierwszeństwa, motorniczy nie ma tu pierwszeństwa przed pojazdami już znajdującymi się na skrzyżowaniu. Najpierw trzeba ustąpić, a dopiero potem wjechać bezpiecznie na rondo.",
    "4540": "Poprawna jest odpowiedź Tak. Widoczna na jezdni linia łamana wyznacza miejsce przystanku dla tramwajów, autobusów lub trolejbusów. To oznakowanie wskazuje strefę, w której należy liczyć się z ruchem pasażerów.",
    "4543": "Poprawna jest odpowiedź Nie. Linia łamana widoczna przy krawędzi jezdni nie wyznacza miejsca parkingowego dla osób z niepełnosprawnościami, lecz obszar przystanku. Na takim odcinku należy spodziewać się zatrzymywania pojazdów komunikacji zbiorowej i ruchu pieszych.",
    "6975": "Poprawna jest odpowiedź Tak. Znak ostrzegający o skrzyżowaniu równorzędnym nie odbiera tramwajowi pierwszeństwa wynikającego z zasad ruchu pojazdów szynowych, dlatego skręcając w lewo masz pierwszeństwo przed pojazdami silnikowymi. Trzeba jednak zachować szczególną ostrożność.",
    "6976": "Poprawna jest odpowiedź Nie. Na skrzyżowaniu równorzędnym oznaczonym tym znakiem tramwaj nie ma obowiązku ustępować pierwszeństwa pojazdom silnikowym nadjeżdżającym z prawej strony. Pierwszeństwo tramwaju wynika tu z zasad ruchu pojazdów szynowych.",
    "6977": "Poprawna jest odpowiedź Nie. Zbliżając się tramwajem do skrzyżowania równorzędnego oznaczonego tym znakiem nie masz obowiązku ustępować pierwszeństwa pojazdom silnikowym nadjeżdżającym z lewej strony. Mimo to trzeba obserwować otoczenie i w razie potrzeby reagować odpowiednio wcześnie.",
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

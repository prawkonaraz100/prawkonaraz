from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-video-safe-first-batch.json"


RESOLUTIONS = {
    "2561": "Poprawna jest odpowiedź Tak. Skręcanie w prawo na skrzyżowaniu wymaga szczególnej ostrożności, bo trzeba jednocześnie obserwować tor jazdy, sygnalizację oraz innych uczestników ruchu. Manewr wykonujesz w miejscu, gdzie łatwo o konflikt z innym pojazdem lub pieszym.",
    "2564": "Poprawna jest odpowiedź Tak. Na nagraniu wykonujesz zmianę kierunku jazdy, więc masz obowiązek odpowiednio wcześniej ją zasygnalizować. Dzięki temu inni uczestnicy ruchu wiedzą, jaki manewr zamierzasz wykonać.",
    "2568": "Poprawna jest odpowiedź Tak. Po zakończeniu skrętu trzeba niezwłocznie wyłączyć kierunkowskaz, żeby nie wprowadzać innych uczestników ruchu w błąd. Pozostawienie włączonego sygnału mogłoby sugerować kolejny manewr.",
    "2611": "Poprawna jest odpowiedź Tak. Zbliżając się do przejścia i miejsca, gdzie mogą pojawić się piesi, motorniczy ma obowiązek zmniejszyć prędkość, a w razie potrzeby zatrzymać tramwaj. Trzeba dobrać jazdę tak, aby móc bezpiecznie zareagować na rozwój sytuacji.",
    "2612": "Poprawna jest odpowiedź Nie. Sam fakt, że tramwaj jest pojazdem szynowym, nie daje mu bezwzględnego pierwszeństwa przed pieszymi na przejściu. W takiej sytuacji trzeba obserwować przejście i reagować tak, aby nie stworzyć zagrożenia dla pieszego.",
    "2728": "Poprawna jest odpowiedź Tak. W tej sytuacji zmieniasz kierunek jazdy zgodnie z przebiegiem toru, więc zamiar trzeba zasygnalizować. Sygnalizowanie manewru uprzedza innych uczestników ruchu o planowanym torze przejazdu.",
    "4031": "Poprawna jest odpowiedź Tak. Na nagraniu dochodzi do zmiany kierunku jazdy, dlatego motorniczy ma obowiązek ją sygnalizować. Wcześniejsze użycie kierunkowskazu pozwala innym właściwie ocenić sytuację.",
    "4036": "Poprawna jest odpowiedź Tak. Linia złożona z trójkątów oznacza obowiązek ustąpienia pierwszeństwa, więc jeśli drogą poprzeczną nadjeżdża pojazd, trzeba zatrzymać tramwaj i ustąpić. Nie wolno wjechać na przecięcie torów, wymuszając pierwszeństwo.",
    "4044": "Poprawna jest odpowiedź Tak. Na tym skrzyżowaniu motorniczy ma obowiązek ustąpić pierwszeństwa pojazdom jadącym drogą poprzeczną. Wskazuje na to sposób oznakowania i przebieg toru widoczny na nagraniu.",
    "13118": "Poprawna jest odpowiedź Tak. Widzisz przejście dla pieszych, inny tramwaj i otoczenie, w którym sytuacja może szybko się zmienić, dlatego trzeba zwiększyć uwagę. Tylko zachowanie szczególnej ostrożności pozwala odpowiednio wcześnie zareagować.",
    "13164": "Poprawna jest odpowiedź Tak. W rejonie przejścia dla pieszych masz obowiązek dobrać prędkość tak, aby nie narazić pieszego na niebezpieczeństwo. Jeśli sytuacja tego wymaga, trzeba wyraźnie zwolnić i być przygotowanym do zatrzymania.",
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

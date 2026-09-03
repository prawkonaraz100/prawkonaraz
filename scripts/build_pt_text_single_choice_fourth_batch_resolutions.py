from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-text-single-choice-fourth-batch.json"
ANOMALY_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-only-manual-review" / "flagged-anomalies.json"


RESOLUTIONS = {
    "4131": "Poprawna jest odpowiedź A. Jeżeli główki szyn są zalane wodą na znacznym odcinku, motorniczy powinien zatrzymać tramwaj. W takich warunkach przejazd, nawet powolny, może być niebezpieczny dla ruchu i dla samego pojazdu.",
    "4132": "Poprawna jest odpowiedź C. Gdy w rowkach szyn znajdują się kamienie, trzeba je usunąć przed przejechaniem tego odcinka. Próba przejazdu z kamieniami w torze może doprowadzić do uszkodzeń albo zagrożenia wykolejeniem.",
    "4136": "Poprawna jest odpowiedź B. Wewnętrzne lusterko w tramwaju powinno być ustawione tak, aby motorniczy widział przestrzeń wagonu w pobliżu drzwi. To pomaga kontrolować bezpieczeństwo pasażerów podczas postoju i ruszania.",
    "4138": "Poprawna jest odpowiedź B. Jeżeli pasażerowie nie odsunęli się od krawędzi przystanku, jazdę można rozpocząć tylko pod warunkiem zachowania szczególnej ostrożności i stałej obserwacji sytuacji. Pośpiech ani lekceważenie ryzyka nie są tu dopuszczalne.",
    "4141": "Poprawna jest odpowiedź B. Zwiększenie prędkości podczas pokonywania łuku jest dopuszczalne dopiero wtedy, gdy ostatni wagon opuści łuk. Wcześniejsze przyspieszanie mogłoby spowodować niebezpieczne siły działające na skład.",
    "4143": "Poprawna jest odpowiedź C. Motorniczemu w żadnej sytuacji nie wolno podczas jazdy wyłączać blokady jazdy, bo układ ten chroni przed ruszeniem z otwartymi drzwiami lub inną niebezpieczną sytuacją. Żądanie pasażera ani tłok w pojeździe tego nie usprawiedliwiają.",
    "4144": "Poprawna jest odpowiedź B. Każdy tramwaj liniowy powinien być wyposażony w układ hamowania roboczego, który służy do normalnego prowadzenia pojazdu i zatrzymywania go w ruchu. Hamulec ręczny nie zastępuje tego systemu.",
    "4145": "Poprawna jest odpowiedź C. Jeżeli po załączeniu jazdy tramwaj nie rusza, w pierwszej kolejności trzeba sprawdzić sygnalizację zamknięcia drzwi. Niedomknięte drzwi blokują jazdę i są najczęstszą prostą przyczyną takiej sytuacji.",
    "4147": "Poprawna jest odpowiedź B. Jeżeli po załączeniu jazdy nie nastąpi odhamowanie hamulców szczękowych, należy odpiąć luzowniki. Kontynuowanie jazdy byłoby niebezpieczne, a odpowiedź o bezradnym oczekiwaniu na służby nie opisuje właściwego pierwszego działania.",
    "4148": "Poprawna jest odpowiedź A. Wciśnięcie pedału albo wychylenie dźwigni hamulca elektrodynamicznego za ogranicznik powoduje włączenie hamulców szynowych. Nie służy to do płynnego zwiększania skuteczności hamowania elektrodynamicznego.",
    "4149": "Poprawna jest odpowiedź A. Zanik rozruchu najczęściej jest spowodowany brakiem zasilania tramwaju, na przykład zanikiem napięcia w sieci albo zatrzymaniem pojazdu na izolatorze. Oświetlenie wnętrza czy pogoda nie są tu typową główną przyczyną.",
    "4167": "Poprawna jest odpowiedź B. Aby zmniejszyć zaparowanie przedniej szyby podczas jazdy w deszczu, należy włączyć klimatyzację z nadmuchem na szybę. Sama wycieraczka usuwa wodę z zewnątrz, ale nie rozwiązuje problemu pary wewnątrz pojazdu.",
    "4169": "Poprawna jest odpowiedź B. Prawe zewnętrzne lusterko powinno być ustawione tak, aby widoczny był bok tramwaju i przestrzeń za pojazdem. Tylko wtedy motorniczy może prawidłowo oceniać sytuację przy ruszaniu i zmianie położenia względem otoczenia.",
    "4170": "Poprawna jest odpowiedź C. W strefie zamieszkania dopuszczalna prędkość wynosi 20 km/h, dlatego także kierujący tramwajem musi się do niej dostosować. Wyższe wartości byłyby naruszeniem zasad ruchu w tej strefie.",
    "4233": "Poprawna jest odpowiedź A. Aby jechać z prędkością bezpieczną, trzeba utrzymywać taką prędkość, która pozwoli uniknąć najechania na poprzedzający pojazd i zareagować na sytuację na drodze. Same znaki czy ogólne limity nie wystarczą, jeśli warunki są trudniejsze.",
    "4547": "Poprawna jest odpowiedź C. Gdy natężenie ruchu narasta, motorniczy powinien zwolnić i zwiększyć odstęp od poprzedzającego tramwaju, aby ograniczyć ryzyko zdarzenia. Próba nadrabiania czasu większą prędkością działałaby odwrotnie.",
    "4548": "Poprawna jest odpowiedź B. Zjawisko pocenia się szyn polega na osadzaniu się wilgoci na wychłodzonych szynach, szczególnie jesienią. Taka cienka warstwa wilgoci pogarsza przyczepność i zwiększa ryzyko poślizgu.",
    "4549": "Poprawna jest odpowiedź A. Gdy zaczyna padać deszcz, należy stosować łagodniejszy rozruch, bo przyczepność kół do szyn pogarsza się. Bardziej energiczne ruszanie zwiększałoby ryzyko poślizgu.",
    "4553": "Poprawna jest odpowiedź C. Prawidłowa technika kierowania tramwajem polega na łagodnym rozruchu, możliwie długim wybiegu i łagodnym hamowaniu. To zapewnia bezpieczeństwo, płynność jazdy i mniejsze zużycie pojazdu.",
    "4601": "Poprawna jest odpowiedź A. Jeżeli po wypadku wokół tramwaju zbiera się paliwo z uszkodzonych pojazdów, trzeba odłączyć pantograf od sieci i przeprowadzić ewakuację pasażerów w bezpieczne miejsce. Pozostawienie ludzi w zagrożonej strefie byłoby bardzo niebezpieczne.",
    "4602": "Poprawna jest odpowiedź A. W razie pożaru instalacji elektrycznej trzeba zatrzymać pojazd, otworzyć drzwi do ewakuacji, odłączyć pantograf od sieci i rozpocząć gaszenie. Dojazd do przystanku albo ucieczka bez działania narażałyby pasażerów na większe ryzyko.",
    "7072": "Poprawna jest odpowiedź C. Przyczyną nieprzylegania iglic zwrotnicy może być zalegający śnieg, który blokuje prawidłowe ustawienie elementów zwrotnicy. Sama obecność wody albo przedmiotów w międzytorzu nie opisuje tu właściwej odpowiedzi.",
}

NEW_ANOMALIES = [
    {
        "external_id": "4146",
        "reason": "Pytanie wygląda na merytorycznie podejrzane: odpowiedź wskazuje, że po włączeniu baterii woltomierz powinien pokazywać wartość większą niż wartość określona dla typu tramwaju. Wymaga weryfikacji źródła lub dokumentacji technicznej przed napisaniem wyjaśnienia.",
    }
]


def load_existing_anomalies() -> list[dict[str, str]]:
    if not ANOMALY_PATH.exists():
        return []

    content = json.loads(ANOMALY_PATH.read_text(encoding="utf-8"))
    if not isinstance(content, list):
        return []

    rows: list[dict[str, str]] = []
    for item in content:
        if not isinstance(item, dict):
            continue
        external_id = str(item.get("external_id", "")).strip()
        reason = str(item.get("reason", "")).strip()
        if external_id and reason:
            rows.append({"external_id": external_id, "reason": reason})

    return rows


def write_anomalies(rows: list[dict[str, str]]) -> None:
    deduped: dict[str, dict[str, str]] = {}
    for row in rows:
        deduped[row["external_id"]] = row

    ordered = [deduped[key] for key in sorted(deduped, key=lambda value: int(value))]
    ANOMALY_PATH.parent.mkdir(parents=True, exist_ok=True)
    ANOMALY_PATH.write_text(json.dumps(ordered, ensure_ascii=False, indent=2), encoding="utf-8")


def main() -> None:
    payload = [
        {"external_id": external_id, "resolved_explanation": explanation}
        for external_id, explanation in sorted(RESOLUTIONS.items(), key=lambda item: int(item[0]))
    ]

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    anomalies = load_existing_anomalies() + NEW_ANOMALIES
    write_anomalies(anomalies)

    print(
        json.dumps(
            {
                "resolution_count": len(payload),
                "anomaly_count": len({row["external_id"] for row in anomalies}),
                "output_path": str(OUTPUT_PATH),
                "anomaly_path": str(ANOMALY_PATH),
            },
            ensure_ascii=False,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()

from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-text-single-choice-second-batch.json"
ANOMALY_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-only-manual-review" / "flagged-anomalies.json"


RESOLUTIONS = {
    "4075": "Poprawna jest odpowiedź C. W razie długotrwałych utrudnień w ruchu pasażerów można wypuścić tylko w miejscu, w którym nie stwarza to zagrożenia ani dla nich, ani dla ruchu drogowego. Nie wolno robić tego w dowolnym miejscu, ale też nie zawsze trzeba czekać wyłącznie do przystanku.",
    "4076": "Poprawna jest odpowiedź C. Aby bezpiecznie wykonać elektryczne połączenie wagonów, trzeba opuścić pantografy i odłączyć akumulatory. Samo opuszczenie pantografów albo samo odłączenie akumulatorów nie daje pełnego zabezpieczenia.",
    "4077": "Poprawna jest odpowiedź B. Przed wyjściem z kabiny na jezdnię poza przystankiem motorniczy powinien zabezpieczyć tramwaj i upewnić się, że może bezpiecznie opuścić kabinę. Samo wyłączenie przetwornicy albo świateł nie rozwiązuje kwestii bezpieczeństwa na jezdni.",
    "4078": "Poprawna jest odpowiedź B. Jeżeli w czasie rozruchu dzwoni dzwonek wewnętrzny i nie ma sygnalizacji zamkniętych drzwi, przyczyną może być awaryjne otwarcie drzwi przez pasażera. Otwarte okno czy bezpiecznik przetwornicy nie tłumaczą wprost braku sygnału zamknięcia drzwi.",
    "4079": "Poprawna jest odpowiedź B. W czasie burzy z wyładowaniami atmosferycznymi wnętrze tramwaju stanowi dla pasażerów bezpieczne miejsce, dlatego należy kontynuować jazdę. Zatrzymywanie pojazdu i wypuszczanie pasażerów mogłoby narazić ich na większe ryzyko.",
    "4080": "Poprawna jest odpowiedź A. Przez odcinek z obniżoną siecią trakcyjną należy przejeżdżać wolniej na całej jego długości, aby nie uszkodzić odbieraka prądu ani sieci. Zwiększanie prędkości albo utrzymywanie jej bez względu na warunki byłoby niewłaściwe.",
    "4081": "Poprawna jest odpowiedź A. Zbliżając się do skrzyżowania motorniczy powinien jechać na wybiegu albo hamując, aby zachować kontrolę nad pojazdem i móc bezpiecznie zareagować. Przyspieszanie lub jazda z maksymalną prędkością byłaby nieprawidłowa.",
    "4082": "Poprawna jest odpowiedź C. Bezpieczny przejazd przez zwrotnicę jest możliwy wtedy, gdy iglice prawidłowo przylegają. Sam sygnał zwrotnicy nie zastępuje oceny stanu mechanicznego, a pośpiech nie może usprawiedliwiać pominięcia kontroli.",
    "4083": "Poprawna jest odpowiedź B. Przejazd przez zwrotnice i krzyżownice wymaga zmniejszenia prędkości, bo są to miejsca szczególnie wrażliwe na wykolejenie i uszkodzenia infrastruktury. Zwiększanie prędkości albo brak reakcji byłby błędem.",
    "4084": "Poprawna jest odpowiedź C. Wjeżdżając w ostry łuk toru trzeba zmniejszyć prędkość, aby utrzymać bezpieczny tor jazdy i nie przeciążać pojazdu. Zachowanie dotychczasowej prędkości albo jej zwiększanie zwiększa ryzyko zagrożenia.",
    "4085": "Poprawna jest odpowiedź C. Ustawienie fotela motorniczego ma kluczowy wpływ na prawidłową obsługę urządzeń sterujących, ponieważ decyduje o wygodzie, zasięgu i precyzji ruchów. Nie jest to detal bez znaczenia.",
    "4086": "Poprawna jest odpowiedź A. Po wystąpieniu poślizgu kół podczas rozruchu należy przerwać rozruch i ponowić go łagodniej, aby odzyskać przyczepność. Mocniejsze przyspieszanie tylko pogłębiłoby problem.",
    "4087": "Poprawna jest odpowiedź B. Gdy pojazd nagle znajdzie się na torach bezpośrednio przed tramwajem, trzeba zdecydowanie hamować i próbować uniknąć zderzenia. Zbyt łagodne hamowanie albo rezygnacja z reakcji byłyby niebezpieczne.",
    "4088": "Poprawna jest odpowiedź A. Rozruch tramwaju trzeba prowadzić tak, aby nie doprowadzić do poślizgu kół. Nadmiernie gwałtowne przyspieszanie nie jest prawidłową techniką jazdy.",
    "4089": "Poprawna jest odpowiedź B. Do łagodnego zatrzymania tramwaju na przystanku wykorzystuje się hamulec elektrodynamiczny, a w końcowej fazie hamulec postojowy. Hamulec szynowy służy do innych, bardziej awaryjnych sytuacji.",
    "4090": "Poprawna jest odpowiedź B. Łuk elektryczny powstający przy przejeżdżaniu przez izolator z załączoną jazdą może uszkodzić pantograf. Nie jest to typowa przyczyna spadku napięcia baterii ani uszkodzenia odgromnika.",
    "4091": "Poprawna jest odpowiedź A. Motorniczy zawsze ma obowiązek dostosować prędkość do warunków na drodze i sytuacji ruchowej. Nie dotyczy to tylko wybranych miejsc, takich jak przejścia dla pieszych.",
    "4092": "Poprawna jest odpowiedź A. Jeżeli drzwi nie chcą się zamknąć i wymagają fachowej naprawy, pasażerowie muszą opuścić tramwaj, a pojazd powinien wrócić pusty do zajezdni. Kontynuowanie jazdy z otwartymi drzwiami byłoby niedopuszczalne.",
    "4093": "Poprawna jest odpowiedź B. Dojeżdżając do zanieczyszczonej zwrotnicy trzeba się zatrzymać i oczyścić ją przed najechaniem. Ignorowanie zabrudzenia grozi nieprawidłowym ustawieniem zwrotnicy i niebezpiecznym przejazdem.",
    "4094": "Poprawna jest odpowiedź B. Po ruszeniu motorniczy powinien rozpędzić tramwaj do potrzebnej prędkości, a następnie możliwie długo jechać na wybiegu. To element prawidłowej i płynnej techniki jazdy.",
    "4096": "Poprawna jest odpowiedź B. Pod izolatorami sekcyjnymi należy przejeżdżać na wybiegu lub na hamowaniu, aby nie powodować łuku elektrycznego. Jazda na rozruchu w takim miejscu mogłaby uszkodzić odbierak i sieć trakcyjną.",
    "4097": "Poprawna jest odpowiedź B. Wciśnięcie czuwaka podczas postoju umożliwia rozruch tramwaju, bo potwierdza gotowość motorniczego do jazdy. Nie służy ono do uruchamiania oświetlenia ani opuszczania hamulców szynowych.",
    "4098": "Poprawna jest odpowiedź B. Na obszarze zabudowanym w godzinach 5:00-23:00, o ile znaki nie stanowią inaczej, maksymalna dopuszczalna prędkość tramwaju wynosi 50 km/h. Pozostałe wartości są nieprawidłowe dla tej ogólnej zasady.",
    "4099": "Poprawna jest odpowiedź B. Holując uszkodzony tramwaj trzeba liczyć się z wydłużeniem drogi hamowania, bo masa zestawu rośnie i pogarsza się skuteczność zatrzymania. Nie można zakładać jej skrócenia.",
}


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

    write_anomalies(load_existing_anomalies())

    print(
        json.dumps(
            {
                "resolution_count": len(payload),
                "anomaly_count": len(load_existing_anomalies()),
                "output_path": str(OUTPUT_PATH),
                "anomaly_path": str(ANOMALY_PATH),
            },
            ensure_ascii=False,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()

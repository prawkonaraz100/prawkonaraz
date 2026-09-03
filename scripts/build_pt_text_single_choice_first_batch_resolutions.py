from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-text-single-choice-first-batch.json"
ANOMALY_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-only-manual-review" / "flagged-anomalies.json"


RESOLUTIONS = {
    "2635": "Poprawna jest odpowiedź A. Skład dwuwagonowy musi być wyposażony w jedną gaśnicę w każdym wagonie, aby środki gaśnicze były dostępne niezależnie od miejsca zagrożenia. Jedna gaśnica na cały skład byłaby niewystarczająca, a wymóg dwóch gaśnic w każdym wagonie nie wynika z tej zasady.",
    "2684": "Poprawna jest odpowiedź A. Kierujący tramwajem ma obowiązek mieć przy sobie i okazywać na żądanie uprawnionego organu dokument stwierdzający dopuszczenie tramwaju do ruchu. To, że dokument może być przechowywany w zajezdni, nie zwalnia z obowiązków podczas kontroli.",
    "2686": "Poprawna jest odpowiedź A. Do kontroli dokumentów kierującego tramwajem uprawniony jest policjant lub strażnik miejski. Nie każdy funkcjonariusz dowolnej służby ma takie uprawnienie, a odpowiedź ograniczająca je wyłącznie do policjanta jest zbyt wąska.",
    "2797": "Poprawna jest odpowiedź A. Hamulce szynowe są urządzeniem bezpieczeństwa i po awarii przetwornicy mogą nadal zadziałać. Ich użycie nie jest uzależnione od tego, czy działa zasilanie z drugiego wagonu.",
    "3943": "Poprawna jest odpowiedź C. Jeżeli tramwaj z hamulcem szczękowym toczy się mimo wciśniętego hamulca i świecącej kontrolki hamulców postojowych, może to świadczyć o zużyciu okładzin ciernych. Sama liczba pasażerów ani napięcie ładowania akumulatorów nie tłumaczy takiego objawu wprost.",
    "3976": "Poprawna jest odpowiedź B. Motorniczy powinien hamować w sposób niepowodujący zagrożenia bezpieczeństwa ruchu i dostosowany do sytuacji. Każdorazowe używanie hamulców szynowych albo piasecznicy nie jest prawidłową zasadą jazdy.",
    "3983": "Poprawna jest odpowiedź A. Gdy pada deszcz albo na torowisku leżą liście, trzeba zmniejszyć prędkość i dostosować ją do warunków, bo pogarsza się przyczepność i wydłuża droga hamowania. Utrzymywanie prędkości zbliżonej do maksymalnej byłoby niebezpieczne.",
    "4009": "Poprawna jest odpowiedź B. Wjeżdżając na skrzyżowanie równorzędne trzeba obserwować całe skrzyżowanie i jego otoczenie, bo zagrożenie może pojawić się z różnych kierunków. Patrzenie wyłącznie na wprost albo tylko w prawo jest niewystarczające.",
    "4013": "Poprawna jest odpowiedź B. Przez miejsce z obniżoną siecią trakcyjną trzeba przejeżdżać wolniej, bo zbyt szybki przejazd może uszkodzić odbierak prądu i samą trakcję. Echo czy sam problem z hamowaniem nie jest tu zasadniczą przyczyną.",
    "4038": "Poprawna jest odpowiedź C. Wyszczerbiona wkładka grafitowa pantografu może powodować przerwy w dopływie prądu wysokiego napięcia do wagonu, bo pogarsza kontakt z siecią trakcyjną. Uszkodzona bateria czy dzwonek zewnętrzny nie są tu bezpośrednią przyczyną.",
    "4039": "Poprawna jest odpowiedź B. Wyszczerbienie grafitowej wkładki pantografu może spowodować nadmierne kołysanie, a nawet zerwanie sieci trakcyjnej. Taka usterka nie poprawia pracy pantografu ani nie oznacza po prostu większego poboru prądu.",
    "4040": "Poprawna jest odpowiedź B. Hamulec mechaniczny, czyli postojowy, wykorzystuje się w końcowej fazie hamowania oraz podczas postoju pojazdu. Nie służy on do pracy we wszystkich fazach hamowania.",
    "4041": "Poprawna jest odpowiedź B. Działanie hamulca postojowego powoduje docisk sprężyny, dlatego hamulec ten działa także po odłączeniu zasilania. Sama cewka elektromagnetyczna ani układ dźwigni nie są tu właściwą odpowiedzią.",
    "4042": "Poprawna jest odpowiedź A. Sprawność hamulców elektrodynamicznych zależy od sprawności silników trakcyjnych, bo to one uczestniczą w tym sposobie hamowania. Nie są one niezależne od systemów tramwaju.",
    "4043": "Poprawna jest odpowiedź B. Aby zluzować hamulce postojowe do holowania uszkodzonego tramwaju, trzeba odpiąć mechanicznie lub elektrycznie luzowniki. Samo wyłączenie wagonu albo ustawienie nawrotnika w położenie neutralne nie wystarczy.",
    "4059": "Poprawna jest odpowiedź B. Hamulec postojowy jest hamulcem pasywnym, bo działa także po wyłączeniu tramwaju i zabezpiecza pojazd przed samoczynnym ruszeniem. Nie wymaga aktywnego działania po załączeniu pojazdu.",
    "4063": "Poprawna jest odpowiedź A. Zatrzymanie wagonu w miejscu styku pantografu z izolatorem może spowodować zanik rozruchu i pracy przetwornicy, bo odbiór energii z sieci trakcyjnej zostaje przerwany. Nie jest to typowa przyczyna zadziałania hamulców szynowych.",
    "4065": "Poprawna jest odpowiedź B. Motorniczy może wpuszczać albo wypuszczać pasażerów tylko znajdując się na przystanku. Nie wolno robić tego poza przystankiem na samą prośbę pasażera.",
    "4066": "Poprawna jest odpowiedź A. Oprócz oznakowania liniowego wymaganym oznaczeniem tramwaju jest numer taborowy. Tramwaj nie musi mieć tablicy rejestracyjnej jak samochód, a dodatkowe logo czy nazwa miasta nie zastępują wymaganego oznaczenia.",
    "4067": "Poprawna jest odpowiedź B. Pasażerów wolno wpuszczać i wypuszczać na przystankach, bo tylko tam jest do tego przewidziane bezpieczne miejsce. Nie wolno robić tego w dowolnym miejscu trasy.",
    "4069": "Poprawna jest odpowiedź A. Kierując tramwajem masz obowiązek posiadać przy sobie pozwolenie na kierowanie tym pojazdem i okazać je podczas kontroli. Dokument od pracodawcy nie zastępuje tego obowiązku.",
    "4071": "Poprawna jest odpowiedź B. Przed wymianą przepalonego bezpiecznika topikowego wysokiego napięcia trzeba opuścić pantograf, aby odłączyć pojazd od sieci trakcyjnej. Samo wyłączenie przetwornicy albo założenie rękawic nie daje pełnego zabezpieczenia.",
    "4072": "Poprawna jest odpowiedź A. Po zamknięciu drzwi motorniczy powinien uważnie obserwować otoczenie dookoła pojazdu i dopiero potem podać sygnał odjazdu dzwonkiem. Sam dzwonek albo samo spojrzenie na kontrolkę drzwi nie wystarczą.",
    "4073": "Poprawna jest odpowiedź C. Podczas ręcznego przestawiania zwrotnicy motorniczy powinien stać przodem do tramwaju, aby stale kontrolować jego położenie i zachować bezpieczeństwo. Ustawienie tyłem do pojazdu byłoby nieprawidłowe.",
}


ANOMALIES = [
    {
        "external_id": "2649",
        "reason": "Pytanie wygląda na merytorycznie niespójne: prompt mówi o zmniejszeniu ryzyka poślizgu, a wskazana poprawna odpowiedź brzmi 'Gołoledź'. Wymaga weryfikacji źródła przed napisaniem wyjaśnienia.",
    }
]


def main() -> None:
    payload = [
        {"external_id": external_id, "resolved_explanation": explanation}
        for external_id, explanation in sorted(RESOLUTIONS.items(), key=lambda item: int(item[0]))
    ]

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    ANOMALY_PATH.parent.mkdir(parents=True, exist_ok=True)
    ANOMALY_PATH.write_text(json.dumps(ANOMALIES, ensure_ascii=False, indent=2), encoding="utf-8")

    print(
        json.dumps(
            {
                "resolution_count": len(payload),
                "anomaly_count": len(ANOMALIES),
                "output_path": str(OUTPUT_PATH),
                "anomaly_path": str(ANOMALY_PATH),
            },
            ensure_ascii=False,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()

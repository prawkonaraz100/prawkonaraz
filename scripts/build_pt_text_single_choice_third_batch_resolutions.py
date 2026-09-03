from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-text-single-choice-third-batch.json"
ANOMALY_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-only-manual-review" / "flagged-anomalies.json"


RESOLUTIONS = {
    "4101": "Poprawna jest odpowiedź B. Zmniejszenie prędkości podczas przejazdu przez zwrotnicę jest obowiązkowe, bo chroni przed wykolejeniem i uszkodzeniem infrastruktury. Nie zależy to wyłącznie od szczególnego rodzaju zwrotnicy ani od opóźnienia w ruchu.",
    "4107": "Poprawna jest odpowiedź A. Użycie piasecznic podczas rozruchu na śliskich szynach poprawia przyczepność i zmniejsza poślizg kół, dzięki czemu ułatwia ruszanie. Nie utrudnia rozruchu ani nie pozostaje bez wpływu.",
    "4108": "Poprawna jest odpowiedź C. Tramwaj powinien być wyposażony w gaśnicę śniegową lub proszkową, bo taki środek gaśniczy nadaje się do zagrożeń występujących w pojeździe. Gaśnice pianowe i wodne nie są tu właściwym wyborem.",
    "4109": "Poprawna jest odpowiedź C. Jazda bez osłony sprzęgów międzywagonowych od strony drzwi pasażerskich jest dopuszczalna tylko wtedy, gdy sprzęgi są odpowiednio oznaczone zakazem wchodzenia między wagony. Nie oznacza to ani całkowitego zakazu, ani pełnej dowolności.",
    "4110": "Poprawna jest odpowiedź C. Światła awaryjne służą do sygnalizowania obecności tramwaju unieruchomionego z powodu uszkodzenia. Nie używa się ich do podziękowania ani rutynowo przy każdym postoju na przystanku.",
    "4111": "Poprawna jest odpowiedź B. Z uszkodzonym zewnętrznym dzwonkiem ostrzegawczym nie wolno wyjechać z zajezdni w żadnym wypadku, bo to element istotny dla bezpieczeństwa ruchu. Sama adnotacja o usterce w dokumentach nie usuwa tego zakazu.",
    "4112": "Poprawna jest odpowiedź C. Funkcję hamulców awaryjnych w tramwaju pełnią hamulce szynowe, bo są przeznaczone do szybkiego i zdecydowanego zatrzymania pojazdu. Hamulce elektrodynamiczne i szczękowe nie pełnią tej roli w takim znaczeniu.",
    "4113": "Poprawna jest odpowiedź B. Obwód blokady jazdy, czyli tak zwana zielona linia, uniemożliwia jazdę tramwaju z otwartymi albo niedomkniętymi drzwiami. Nie służy do sterowania ruchem między tramwajami ani do otwierania drzwi przez pasażerów.",
    "4114": "Poprawna jest odpowiedź B. Użycie piasecznic podczas hamowania na śliskich szynach skraca drogę hamowania, bo zmniejsza poślizg kół. Nie wydłuża jej i nie jest obojętne dla skuteczności hamowania.",
    "4115": "Poprawna jest odpowiedź C. Motorniczy nie reguluje siły hamowania hamulców szynowych, bo po ich uruchomieniu działają one z określoną skutecznością wynikającą z konstrukcji układu. Nie ma tu płynnej regulacji jak w innych rodzajach hamowania.",
    "4117": "Poprawna jest odpowiedź B. Odstęp od poprzedzającego tramwaju powinien być taki, aby można było bezkolizyjnie zatrzymać pojazd w każdej chwili. Sama stała wartość w metrach albo odwołanie do przepisów wewnętrznych nie wystarczą jako ogólna zasada.",
    "4118": "Poprawna jest odpowiedź C. Zwolnienie czuwaka podczas jazdy wywołuje hamowanie awaryjne, obejmujące hamulec szynowy, szczękowy i minimalne hamowanie elektrodynamiczne. Nie uruchamia wyłącznie jednego rodzaju hamulców.",
    "4119": "Poprawna jest odpowiedź B. Wjeżdżając na przystanek trzeba hamować płynnie, ale jednocześnie zdecydowanie, aby zatrzymanie było bezpieczne i komfortowe dla pasażerów. Gwałtowne hamowanie albo użycie wszystkich hamulców naraz nie jest właściwą techniką.",
    "4120": "Poprawna jest odpowiedź A. Po wyprowadzeniu tramwaju z poślizgu należy hamować z mniejszą siłą, aby nie doprowadzić ponownie do zablokowania kół. Trzeba odzyskać przyczepność, a nie wywoływać kolejny poślizg.",
    "4121": "Poprawna jest odpowiedź A. Ustalając odstęp od poprzedzającego tramwaju trzeba zakładać, że może on w każdej chwili gwałtownie zahamować. To właśnie taki zapas bezpieczeństwa pozwala uniknąć najechania.",
    "4122": "Poprawna jest odpowiedź B. Bezpieczny odstęp od poprzedzającego tramwaju zależy między innymi od aktualnych warunków atmosferycznych, bo wpływają one na przyczepność i drogę hamowania. Nie zależy natomiast od rozkładu jazdy ani liczby przystanków.",
    "4123": "Poprawna jest odpowiedź A. Prędkość tramwaju trzeba dostosować przede wszystkim do stanu torowiska i widoczności drogi, bo od tych czynników zależy możliwość bezpiecznego zatrzymania. Moc silników ani liczba torów nie są tu najważniejsze.",
    "4124": "Poprawna jest odpowiedź B. Zbliżając się do kałuży na torowisku trzeba przejechać z jak najmniejszą prędkością, aby nie pogorszyć warunków prowadzenia pojazdu i nie uszkodzić infrastruktury. Maksymalna prędkość byłaby błędem.",
    "4125": "Poprawna jest odpowiedź C. Śliska substancja powstała z wilgotnych liści zwiększa ryzyko poślizgu, bo pogarsza przyczepność kół do szyn. Nie zmniejsza tego ryzyka ani nie pozostaje bez wpływu.",
    "4126": "Poprawna jest odpowiedź B. Liście na torowisku zmniejszają przyczepność, dlatego utrudniają rozruch i hamowanie tramwaju. Nie poprawiają prowadzenia pojazdu.",
    "4127": "Poprawna jest odpowiedź B. Początkowa faza opadów stwarza szczególne zagrożenie poślizgu podczas rozruchu i hamowania, bo zanieczyszczenia na torach tworzą śliską warstwę. Nie poprawia to przyczepności.",
    "4128": "Poprawna jest odpowiedź A. Opady deszczu najmocniej wpływają na rozruch i hamowanie w początkowej fazie, gdy nawierzchnia staje się szczególnie śliska. Później warunki mogą się częściowo ustabilizować.",
    "4129": "Poprawna jest odpowiedź C. Ryzyko poślizgu najbardziej zwiększa zaleganie mokrych liści na torowisku, bo tworzą one bardzo śliską warstwę. Samo nagrzanie szyn przez słońce ani kurz nie daje tu takiego efektu.",
    "4130": "Poprawna jest odpowiedź C. Najtrudniejsze warunki do jazdy tramwajem występują zwykle jesienią, gdy na torach pojawiają się wilgoć, mokre liście i zjawisko pocenia się szyn. To właśnie wtedy ryzyko poślizgu szczególnie rośnie.",
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

    anomalies = load_existing_anomalies()
    write_anomalies(anomalies)

    print(
        json.dumps(
            {
                "resolution_count": len(payload),
                "anomaly_count": len(anomalies),
                "output_path": str(OUTPUT_PATH),
                "anomaly_path": str(ANOMALY_PATH),
            },
            ensure_ascii=False,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()

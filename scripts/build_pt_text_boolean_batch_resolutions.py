from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-pt-text-boolean-batch.json"
ANOMALY_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-only-manual-review" / "flagged-anomalies.json"


RESOLUTIONS = {
    "2595": "Poprawna jest odpowiedź Nie. Podczas cofania tramwajem motorniczy nie ma pierwszeństwa przed innymi uczestnikami ruchu i musi wykonywać manewr szczególnie ostrożnie. To on odpowiada za bezpieczne przeprowadzenie cofania.",
    "2619": "Poprawna jest odpowiedź Tak. Gaśnica proszkowa nadaje się do tłumienia palącego się paliwa, dlatego może być właściwym środkiem po kolizji z samochodem. Ważne jest jednak zachowanie bezpieczeństwa i odcięcie źródeł zagrożenia.",
    "2620": "Poprawna jest odpowiedź Nie. Kierujący tramwajem nie może opuścić miejsca wypadku z rannym tylko po to, by nie powodować utrudnień w ruchu. Najpierw trzeba wykonać obowiązki związane z bezpieczeństwem i pomocą poszkodowanym.",
    "2621": "Poprawna jest odpowiedź Tak. Jako uczestnik wypadku masz obowiązek podjąć działania służące zapewnieniu bezpieczeństwa w miejscu zdarzenia, aby ograniczyć dalsze zagrożenie dla ludzi i ruchu. To podstawowy obowiązek po wypadku.",
    "2756": "Poprawna jest odpowiedź Tak. Motorniczego obowiązują takie same normy dopuszczalnej zawartości alkoholu we krwi jak innych kierujących pojazdami. Nie ma tu odrębnych, łagodniejszych zasad.",
    "2757": "Poprawna jest odpowiedź Nie. Jeżeli w ulotce leku jest wyraźny zakaz prowadzenia pojazdów, motorniczy nie może przyjąć go przed rozpoczęciem pracy i kierować tramwajem. Bezpieczeństwo wymaga bezwzględnego stosowania takiego ostrzeżenia.",
    "2758": "Poprawna jest odpowiedź Tak. Przed rozpoczęciem pracy motorniczy powinien sprawdzić, czy przyjmowane leki nie mają przeciwwskazań do prowadzenia pojazdów. To element odpowiedzialnego przygotowania do służby.",
    "2759": "Poprawna jest odpowiedź Tak. Kierowanie tramwajem pod wpływem środków działających podobnie do alkoholu jest zabronione, ponieważ obniża sprawność psychofizyczną kierującego. Taki stan zagraża bezpieczeństwu pasażerów i innych uczestników ruchu.",
    "2782": "Poprawna jest odpowiedź Tak. Opuszczając tramwaj podczas postoju na pętli, motorniczy powinien zabezpieczyć pojazd przed uruchomieniem przez osoby niepowołane. To podstawowy element zabezpieczenia pojazdu na postoju.",
    "2801": "Poprawna jest odpowiedź Tak. Dzieci bawiące się w pobliżu torów mogą zachować się nagle i nieprzewidywalnie, dlatego trzeba liczyć się z koniecznością zmniejszenia prędkości albo zatrzymania pojazdu. Szczególna ostrożność jest tu obowiązkowa.",
    "2802": "Poprawna jest odpowiedź Nie. W czasie długotrwałej awarii nie wolno pozwolić pasażerom opuścić tramwaju, jeśli mogłoby to utrudnić ruch albo narazić ich na niebezpieczeństwo. Ewakuacja może odbywać się tylko w warunkach bezpiecznych.",
    "2805": "Poprawna jest odpowiedź Tak. Pasażer uczestniczący w wypadku ma prawo żądać danych personalnych motorniczego, a motorniczy ma obowiązek je podać. To element prawidłowego postępowania po zdarzeniu.",
    "2806": "Poprawna jest odpowiedź Tak. Po wypadku pasażer uczestniczący w zdarzeniu ma prawo otrzymać dane firmy przewozowej oraz ubezpieczyciela tramwaju. Takie informacje są potrzebne do dalszego dochodzenia roszczeń i ustaleń.",
    "2807": "Poprawna jest odpowiedź Tak. Jako motorniczy masz obowiązek udzielić niezbędnej pomocy ofiarom wypadku niezależnie od tego, czy przeszedłeś specjalne szkolenie. Nie wolno biernie pozostawić poszkodowanych bez reakcji.",
    "2812": "Poprawna jest odpowiedź Tak. Zmęczenie może wywoływać skutki podobne do działania alkoholu, takie jak gorsza koncentracja i wydłużony czas reakcji. Dlatego stan psychofizyczny motorniczego ma bezpośredni wpływ na bezpieczeństwo jazdy.",
    "3109": "Poprawna jest odpowiedź Nie. Uczestnicząc w wypadku z zabitymi lub rannymi nie wolno oddalić się z miejsca zdarzenia tylko dlatego, że przybyły służby nadzoru ruchu. Najpierw trzeba wykonać obowiązki wynikające z przepisów i poleceń uprawnionych służb.",
    "3110": "Poprawna jest odpowiedź Nie. Przy wypadku z zabitymi lub rannymi nie wolno samodzielnie usuwać skutków zdarzenia, sprzątać miejsca ani przygotowywać tramwaju do holowania bez odpowiedniej decyzji. Najpierw należy zabezpieczyć miejsce i umożliwić działania służbom.",
    "3991": "Poprawna jest odpowiedź Nie. Motorniczemu nie wolno podczas prowadzenia tramwaju korzystać z telefonu trzymanego w ręku, ponieważ odrywa to uwagę od kierowania. Taki sposób używania telefonu jest zabroniony.",
    "3992": "Poprawna jest odpowiedź Tak. Korzystanie z telefonu w czasie kierowania jest dopuszczalne, jeśli nie wymaga trzymania aparatu w ręku i nie ogranicza panowania nad pojazdem. Warunkiem jest zachowanie pełnej kontroli nad jazdą.",
    "3993": "Poprawna jest odpowiedź Tak. Uczestnicząc w wypadku z rannymi masz obowiązek niezwłocznie zatrzymać tramwaj, o ile można to zrobić bez stwarzania dodatkowego zagrożenia. To pierwszy krok do dalszej pomocy i zabezpieczenia miejsca zdarzenia.",
    "3994": "Poprawna jest odpowiedź Tak. Jeżeli w zdarzeniu nie ma rannych ani zabitych, należy w miarę możliwości usunąć tramwaj z miejsca wypadku, aby nie blokować ruchu. Robi się to jednak tylko wtedy, gdy można to zrobić bezpiecznie.",
    "3995": "Poprawna jest odpowiedź Tak. Pasażer uczestniczący w wypadku może żądać danych personalnych motorniczego, a motorniczy ma obowiązek je podać. Ułatwia to późniejsze wyjaśnienie przebiegu zdarzenia i kwestie formalne.",
    "3996": "Poprawna jest odpowiedź Nie. Jeżeli w wypadku nie ma zabitych ani rannych, nie ma obowiązku wzywania policji tylko z tego powodu. Najważniejsze jest zabezpieczenie miejsca i wymiana potrzebnych danych stron zdarzenia.",
    "3997": "Poprawna jest odpowiedź Tak. Gdy w wypadku są osoby zabite lub ranne, trzeba powiadomić policję i wezwać zespół ratownictwa medycznego. To podstawowy obowiązek uczestnika takiego zdarzenia.",
    "4006": "Poprawna jest odpowiedź Nie. W czasie kierowania tramwajem nie wolno rozmawiać przez telefon wymagający trzymania słuchawki albo mikrofonu w ręku. Ogranicza to możliwość bezpiecznego prowadzenia pojazdu.",
    "4023": "Poprawna jest odpowiedź Tak. Przed ręcznym przełożeniem zwrotnicy motorniczy powinien odpowiednio zaparkować i zabezpieczyć tramwaj. Nie wolno wychodzić do zwrotnicy bez uprzedniego unieruchomienia pojazdu.",
    "4024": "Poprawna jest odpowiedź Nie. Podczas postoju na pętli nie wolno opuścić tramwaju bez jego zabezpieczenia. Pojazd musi być chroniony przed nieuprawnionym uruchomieniem i przypadkowym stoczeniem.",
    "4025": "Poprawna jest odpowiedź Nie. Motorniczy nie ma prawa prowadzić tramwaju w stanie po użyciu alkoholu nawet wtedy, gdy subiektywnie czuje się dobrze. O zakazie decyduje stan organizmu, a nie własne odczucie.",
    "4026": "Poprawna jest odpowiedź Nie. Motorniczy, licząc wszystkie formy zatrudnienia, nie może odpoczywać krócej niż 11 godzin na dobę. Minimalny odpoczynek dobowy jest konieczny dla bezpieczeństwa pracy i ruchu.",
    "4027": "Poprawna jest odpowiedź Tak. Rozmowa przez telefon w czasie kierowania jest dopuszczalna tylko wtedy, gdy motorniczy nie trzyma słuchawki ani mikrofonu w ręku. Musi przy tym zachować pełną kontrolę nad pojazdem.",
    "4032": "Poprawna jest odpowiedź Tak. Motorniczemu wykonującemu przewozy pasażerskie przysługuje co najmniej 35 godzin nieprzerwanego odpoczynku tygodniowego. Odpoczynek ten ma chronić kierującego przed przemęczeniem.",
    "4054": "Poprawna jest odpowiedź Nie. Obowiązek zachowania odległości co najmniej 1 metra przy omijaniu nie występuje w każdym przypadku w tej samej postaci. Zawsze trzeba jednak zachować taki odstęp, który zapewni bezpieczeństwo w konkretnej sytuacji.",
    "4055": "Poprawna jest odpowiedź Nie. Jeśli motorniczy nie ma możliwości obserwacji drogi za tramwajem, nie może cofać bez zapewnienia sobie pomocy innej osoby. Cofanie bez kontroli przestrzeni za pojazdem stwarza zagrożenie.",
    "4056": "Poprawna jest odpowiedź Nie. Gdy motorniczy źle przełożył zwrotnicę i zauważył to już za nią, nie wolno mu wycofać tramwaju bez sprawdzenia, czy manewr będzie bezpieczny. Każde cofanie musi być wykonane bez ryzyka dla otoczenia.",
    "4057": "Poprawna jest odpowiedź Tak. Omijając pojazd przewożący zorganizowaną grupę dzieci, z którego dzieci wysiadają, trzeba zachować szczególną ostrożność. Dzieci mogą wejść na tor lub jezdnię nagle i nieprzewidywalnie.",
    "4173": "Poprawna jest odpowiedź Nie. Sam fakt, że motorniczy przebywa w kabinie, nie oznacza obowiązku trzymania wszystkich drzwi zamkniętych ze względu na zabezpieczenie pojazdu. Decydują o tym warunki postoju i bezpieczeństwo obsługi pasażerów, a nie bezwzględna zasada zamykania wszystkich drzwi.",
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

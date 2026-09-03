from __future__ import annotations

import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
REMAINING_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "shared_review_overlap_only.json"
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-single-choice-hard-conflicts.json"


RESOLUTIONS = {
    "3651": "Poprawna jest odpowiedź A. Na długość drogi zatrzymania bezpośrednio wpływa czas reakcji kierującego, bo od niego zależy odcinek przejechany od chwili zauważenia zagrożenia do rozpoczęcia hamowania. Masa pasażerów ani sama masa pojazdu nie są tu właściwą odpowiedzią spośród podanych wariantów.",
    "3723": "Poprawna jest odpowiedź A. Trójkąt ostrzegawczy należy do obowiązkowego wyposażenia samochodu osobowego i służy do ostrzegania o unieruchomionym pojeździe. Apteczka i koło zapasowe nie są obowiązkowym wyposażeniem każdego samochodu osobowego.",
    "6361": "Poprawna jest odpowiedź A. Światła drogowe trzeba przełączyć na światła mijania, gdy z przeciwka nadjeżdża inny pojazd i istnieje ryzyko oślepienia kierującego. Sam deszcz albo sam wjazd do obszaru zabudowanego nie są tą konkretną przesłanką z podanych odpowiedzi.",
    "6410": "Poprawna jest odpowiedź B. Ostrzegawczy trójkąt odblaskowy stanowi obowiązkowe wyposażenie każdego samochodu osobowego i trzeba go używać przy oznaczaniu postoju pojazdu w sytuacjach wymaganych przepisami. Podnośnik i kamizelka odblaskowa nie należą do obowiązkowego wyposażenia każdego auta osobowego.",
    "6411": "Poprawna jest odpowiedź A. Ostrzegawczy trójkąt odblaskowy należy do obowiązkowego wyposażenia samochodu osobowego. Komplet zapasowych żarówek ani zestaw głośnomówiący nie są obowiązkowym wyposażeniem każdego takiego pojazdu.",
    "6430": "Poprawna jest odpowiedź B. Prawo jazdy kategorii B1 uprawnia do kierowania czterokołowcem. Motocykl z wózkiem bocznym ani dowolny pojazd samochodowy o masie własnej 650 kg nie mieszczą się w tym uprawnieniu.",
    "6443": "Poprawna jest odpowiedź C. Nieprawidłowo ustawione reflektory świateł mijania mogą oślepiać kierujących nadjeżdżających z przeciwka i stwarzać zagrożenie w ruchu. Zwiększone zużycie paliwa ani żarówek nie jest tu istotą problemu.",
    "6481": "Poprawna jest odpowiedź B. Za znakiem B-25 zakaz wyprzedzania nie wolno wyprzedzać pojazdów silnikowych wielośladowych, ale można wyprzedzić pojazd jednośladowy. Dlatego spośród podanych odpowiedzi wolno wyprzedzić motocykl jednośladowy.",
    "6494": "Poprawna jest odpowiedź B. Znak B-25 zabrania wyprzedzania pojazdów silnikowych wielośladowych, ale nie obejmuje pojazdów jednośladowych. Dlatego kierując czterokołowcem lekkim możesz w tej sytuacji wyprzedzić motocykl jednośladowy.",
    "6508": "Poprawna jest odpowiedź A. Motorower musi być wyposażony między innymi w sygnał dźwiękowy o nieprzeraźliwym dźwięku. Gaśnica i apteczka doraźnej pomocy nie stanowią obowiązkowego wyposażenia każdego motoroweru.",
    "6510": "Poprawna jest odpowiedź C. Obowiązkowym wyposażeniem motoroweru jest co najmniej jedno lusterko wsteczne z lewej strony, aby kierujący mógł obserwować sytuację za pojazdem. Trójkąt ostrzegawczy ani gaśnica nie są tu wymaganym wyposażeniem.",
    "6617": "Poprawna jest odpowiedź B. Prawo jazdy kategorii AM uprawnia do kierowania motorowerem oraz czterokołowcem lekkim. Spośród podanych odpowiedzi tylko motorower jest więc właściwą odpowiedzią.",
    "6881": "Poprawna jest odpowiedź A. Kierując autobusem musisz jechać z prędkością zapewniającą panowanie nad pojazdem, z uwzględnieniem warunków ruchu, stanu drogi i widoczności. Sama prędkość dopuszczalna albo wynikająca z ogranicznika nie zwalnia z tego obowiązku.",
    "6893": "Poprawna jest odpowiedź A. Światła drogowe trzeba przełączyć na mijania, gdy z przeciwka nadjeżdża inny pojazd i mógłby zostać oślepiony. Opady deszczu ani sam wjazd do obszaru zabudowanego nie są tą konkretną odpowiedzią spośród podanych wariantów.",
    "7625": "Poprawna jest odpowiedź B. Prawo jazdy kategorii T uprawnia do kierowania ciągnikiem rolniczym lub pojazdem wolnobieżnym, a także odpowiednimi zespołami tych pojazdów z przyczepami. Samochód ciężarowy ani lekki pojazd samochodowy nie należą do tej kategorii.",
    "8961": "Poprawna jest odpowiedź B. Na drogę hamowania bezpośrednio wpływa stan hamulców, bo od sprawności układu hamulcowego zależy skuteczność wytracania prędkości. Oznakowanie poziome ani pionowe nie decyduje o długości drogi hamowania pojazdu.",
    "9070": "Poprawna jest odpowiedź C. Układ ESP pomaga utrzymać właściwy tor jazdy, zwłaszcza gdy podczas pokonywania zakrętów pojawia się ryzyko poślizgu lub utraty stabilności pojazdu. Nie jest to system służący do utrzymywania stałej prędkości ani do samego ruszania pod górę.",
    "10077": "Poprawna jest odpowiedź B. Ekonomiczna jazda polega między innymi na płynnym prowadzeniu pojazdu i unikaniu gwałtownych przyspieszeń oraz hamowań, bo to ogranicza zużycie paliwa. Częste używanie hamulca roboczego ani niepotrzebna zmiana biegów nie są cechą takiej jazdy.",
    "10843": "Poprawna jest odpowiedź B. Jeżeli z prawa jazdy wynika obowiązek prowadzenia pojazdu z blokadą alkoholową, kierujący powinien mieć przy sobie dokument potwierdzający kalibrację tej blokady. To właśnie ten dokument trzeba okazać przy kontroli w opisanej sytuacji.",
    "10890": "Poprawna jest odpowiedź B. Kierując na terytorium Rzeczypospolitej pojazdem zarejestrowanym za granicą, musisz mieć przy sobie dokument potwierdzający obowiązkowe ubezpieczenie OC tego pojazdu. Dowód własności ani potwierdzenie opłaty skarbowej nie spełniają tego obowiązku.",
    "10891": "Poprawna jest odpowiedź B. W tej sytuacji dokumentem, który trzeba mieć przy sobie, jest dowód opłacenia składki za obowiązkowe ubezpieczenie odpowiedzialności cywilnej pojazdu. Dokument opłaty za autostrady ani profesjonalny dowód rejestracyjny nie są tu właściwą odpowiedzią spośród podanych wariantów.",
    "10892": "Poprawna jest odpowiedź B. Jeżeli nie chodzi o wydane w kraju prawo jazdy, dokumentem potwierdzającym uprawnienie do kierowania może być inny ważny dokument stwierdzający to uprawnienie, na przykład odpowiedni dokument zagraniczny. Świadectwo kwalifikacji nie zastępuje takiego dokumentu, a odpowiedź o krajowym prawie jazdy nie pasuje do treści pytania.",
    "10893": "Poprawna jest odpowiedź B. Jeżeli prawo jazdy zostało zatrzymane, podczas kontroli okazujesz pokwitowanie jego zatrzymania, o ile nadal upoważnia ono do kierowania pojazdem. Orzeczenie lekarskie ani psychologiczne nie zastępuje tego dokumentu.",
    "10894": "Poprawna jest odpowiedź B. Pokwitowanie zatrzymania prawa jazdy, ważne w okresie, w którym upoważnia do kierowania, jest dokumentem, który należy okazać przy kontroli w opisanej sytuacji. Same orzeczenia psychologiczne albo lekarskie nie dają tego uprawnienia.",
    "11310": "Poprawna jest odpowiedź A. Zaśnieżona lub oblodzona jezdnia wyraźnie zwiększa ryzyko poślizgu, bo zmniejsza przyczepność opon do nawierzchni. Nie powoduje zwiększenia przyczepności ani skrócenia drogi hamowania.",
    "11315": "Poprawna jest odpowiedź B. Na oblodzonej nawierzchni nagłe zwiększenie prędkości łatwo prowadzi do utraty przyczepności i poślizgu. Jazda na trzecim biegu czy sama zmiana biegu na niższy nie opisują tu głównego zagrożenia spośród podanych odpowiedzi.",
    "11316": "Poprawna jest odpowiedź C. Gwałtowne skręcenie kierownicy na oblodzonej jezdni może szybko doprowadzić do utraty przyczepności i poślizgu. Jazda ze stałą prędkością lub hamowanie silnikiem są w takich warunkach bezpieczniejsze niż nagły ruch kierownicą.",
    "11317": "Poprawna jest odpowiedź A. Utrzymywanie zbyt wysokiej prędkości, zwłaszcza na zakręcie, znacząco zwiększa ryzyko poślizgu na oblodzonej nawierzchni. Jazda ze stałą prędkością albo na drugim biegu sama w sobie nie jest tu przyczyną utraty przyczepności.",
    "11515": "Poprawna jest odpowiedź B. Światła drogowe trzeba przełączyć na mijania zawsze wtedy, gdy mogą oślepić kierującego pojazdem poprzedzającym. Sama obecność pojazdu przed Tobą nie wystarcza, jeżeli nie ma ryzyka oślepienia, a pieszy nie jest tą przesłanką z pytania.",
}


def main() -> None:
    remaining = sorted((str(item["external_id"]) for item in json.loads(REMAINING_PATH.read_text(encoding="utf-8"))), key=int)
    current = sorted(RESOLUTIONS.keys(), key=int)

    if set(remaining) != set(current):
        missing = sorted(set(remaining) - set(current), key=int)
        extra = sorted(set(current) - set(remaining), key=int)
        raise SystemExit(
            "Resolution set does not match current remaining hard conflicts. "
            f"Missing: {missing or '[]'} Extra: {extra or '[]'}"
        )

    payload = [
        {"external_id": external_id, "resolved_explanation": RESOLUTIONS[external_id]}
        for external_id in current
    ]

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUTPUT_PATH.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    print(json.dumps({"resolution_count": len(payload), "output_path": str(OUTPUT_PATH)}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

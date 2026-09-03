from __future__ import annotations

import json
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-boolean-conflicts.json"


RESOLUTIONS: dict[str, str] = {
    "10118": "Tak. Zblizasz sie do skrzyzowania, na ktorym pierwszenstwo nie wynika z samej zasady prawej reki, tylko z oznakowania. Widoczne znaki zapowiadaja uklad drogi z pierwszenstwem i drogi podporzadkowanej.",
    "10154": "Nie. W tej sytuacji nie masz obowiazku ustepowac pojazdowi nadjezdzajacemu z prawej strony, bo o pierwszenstwie nie decyduje tu sama zasada prawej reki. Z ukladu skrzyzowania i oznakowania wynika, ze ten pojazd nie ma przed Toba pierwszenstwa.",
    "10278": "Nie. Skrecajac w lewo przecinasz tor jazdy pojazdu nadjezdzajacego z przeciwka, ktory jedzie na wprost. W takiej sytuacji to Ty masz obowiazek ustapic mu pierwszenstwa.",
    "10285": "Nie. W tej sytuacji pojazd nadjezdzajacy z lewej strony nie ma przed Toba pierwszenstwa. O pierwszenstwie rozstrzyga tu oznakowanie i przebieg drogi, a nie sam kierunek, z ktorego nadjezdza inny pojazd.",
    "10857": "Tak. Sygnalizacja zezwala na jazde na wprost, a z zajmowanego pasa mozesz przejechac przez skrzyzowanie w tym kierunku. Nie widac tu znaku ani sygnalu, ktory zabranialby takiego manewru.",
    "10908": "Tak. Skrecajac w prawo musisz ustapic pierwszenstwa pojazdom, ktorym przecinasz tor jazdy. Jezeli z lewej strony nadjezdzaja pojazdy majace pierwszenstwo na jezdni, na ktora wjezdzasz, nie wolno im zajechac drogi.",
    "11212": "Nie. Sama jazda po luku drogi przy dobrej widocznosci nie oznacza jeszcze obowiazku zastosowania zasady ograniczonego zaufania. Te zasade stosuje sie wtedy, gdy konkretne okolicznosci wskazuja na realne ryzyko nieprawidlowego zachowania innego uczestnika ruchu.",
    "11505": "Nie. W tej sytuacji nie masz obowiazku ustepowac pojazdowi z prawej strony, bo nie on ma tutaj pierwszenstwo. O pierwszenstwie rozstrzygaja uklad skrzyzowania i oznakowanie widoczne przy dojezdzie.",
    "1163": "Tak. W tej sytuacji masz obowiazek ustapic pojazdowi nadjezdzajacemu z lewej strony, bo to on ma pierwszenstwo wynikajace z organizacji ruchu. Sam fakt, ze jedziesz na wprost, nie daje Ci tutaj pierwszenstwa.",
    "1172": "Nie. W tej sytuacji pojazd z prawej strony nie ma przed Toba pierwszenstwa. O tym, kto przejezdza pierwszy, decyduje tu oznakowanie i przebieg drogi z pierwszenstwem.",
    "12564": "Nie. Sam dojazd do przejazdu kolejowego nie oznacza jeszcze obowiazku zatrzymania pojazdu. Zatrzymanie jest konieczne dopiero wtedy, gdy zabrania tego sygnalizacja, zapory albo sytuacja na przejezdzie nie pozwala bezpiecznie go opuscic.",
    "12825": "Tak. W przedstawionej sytuacji warunki pozwalaja na wyprzedzenie pojazdu szynowego. Manewr jest dopuszczalny, o ile nie zabrania go oznakowanie i mozna go wykonac bezpiecznie dla wszystkich uczestnikow ruchu.",
    "13137": "Tak. Zblizasz sie do przejscia dla pieszych, a pieszy znajduje sie na nim albo wchodzi na nie. W takiej sytuacji masz obowiazek ustapic mu pierwszenstwa.",
    "13154": "Tak. Sygnaly i polecenia osoby kierujacej ruchem maja pierwszenstwo przed sygnalizacja swietlna. Skoro policjant zezwala na jazde, mozesz kontynuowac manewr mimo widocznego czerwonego swiatla.",
    "13226": "Tak. W warunkach normalnej przejrzystosci powietrza wolno jechac z wlaczonymi swiatlami do jazdy dziennej zamiast swiatel mijania. Sama zima lub snieg na poboczu nie oznaczaja jeszcze, ze przejrzystosc powietrza jest zmniejszona.",
    "2241": "Tak. Sygnaly osoby kierujacej ruchem sa wazniejsze od swiatel i znakow. Jezeli policjant dopuszcza ten kierunek jazdy, mozesz skrecic w prawo.",
    "2932": "Nie. W tej sytuacji nie masz pierwszenstwa przed pojazdem nadjezdzajacym z prawej strony. To Ty musisz dostosowac sie do ukladu pierwszenstwa wynikajacego z oznakowania i przebiegu drogi.",
    "2942": "Tak. W tej sytuacji warunki do wyprzedzania sa spelnione: oznakowanie nie zakazuje manewru, a widocznosc pozwala wykonac go bezpiecznie. Przed rozpoczeciem wyprzedzania trzeba jeszcze upewnic sie, ze nie utrudnisz ruchu innym uczestnikom.",
    "2943": "Tak. W tej sytuacji mozesz wyprzedzic pojazd jadacy przed Toba, poniewaz oznakowanie nie zabrania tego manewru, a warunki na drodze pozwalaja wykonac go bezpiecznie. Manewr trzeba wykonac dopiero po upewnieniu sie, ze jest wystarczajaco duzo miejsca i widocznosci.",
    "3040": "Nie. Pokazany znak nie oznacza wyjazdu z obszaru zabudowanego. Wyjazd z obszaru zabudowanego oznacza znak D-43, a tutaj widoczne jest inne oznakowanie miejscowosci.",
    "3120": "Nie. W tej sytuacji nastepnym sygnalem nie bedzie sygnal czerwony. Widoczna sekwencja sygnalizacji prowadzi do sygnalu zezwalajacego, a nie do ponownego nadania samego czerwonego swiatla.",
    "3235": "Nie. W tej sytuacji pojazd z prawej strony nie ma pierwszenstwa przed Toba. O tym, kto przejezdza pierwszy, rozstrzygaja widoczne znaki i przebieg drogi z pierwszenstwem.",
    "3553": "Nie. Skrecajac w prawo nie masz tu obowiazku ustepowac pojazdowi nadjezdzajacemu z lewej strony. O pierwszenstwie decyduje w tej sytuacji organizacja ruchu i tor jazdy, a nie sam kierunek, z ktorego nadjezdza inny pojazd.",
    "4381": "Nie. Sygnaly osoby kierujacej ruchem maja pierwszenstwo przed sygnalizacja i znakami. W tej sytuacji policjant nie zezwala na skret w lewo, dlatego takiego manewru wykonac nie wolno.",
    "6073": "Tak. W tej sytuacji to Ty masz pierwszenstwo przed tramwajem, bo wynika ono z nadawanego sygnalu lub organizacji ruchu na skrzyzowaniu. Sama obecnosc torowiska nie daje tramwajowi automatycznie pierwszenstwa.",
    "6074": "Tak. Sygnal zezwala na wjazd na skrzyzowanie, a sytuacja za nim pozwala je opuscic bez blokowania ruchu. Nie ma tu przeszkody, ktora nakazywalaby pozostac przed linia zatrzymania.",
    "6075": "Tak. W tej sytuacji wolno wjechac na skrzyzowanie, bo sygnalizacja zezwala na jazde, a za skrzyzowaniem jest miejsce do dalszego przejazdu. Nie zachodzi tu sytuacja, w ktorej wjazd zablokowalby ruch innym uczestnikom.",
    "6077": "Tak. W tej sytuacji mozesz zawrocic, bo sygnalizacja i oznakowanie nie wprowadzaja zakazu tego manewru. Zajmowany pas i dozwolony kierunek jazdy pozwalaja wykonac zawracanie bez naruszania przepisow.",
    "6083": "Tak. Na tym skrzyzowaniu zawracanie jest dopuszczalne, bo nie ma sygnalu ani znaku zakazujacego tego manewru, a kierunek z pasa ruchu na to pozwala. Trzeba jedynie wykonac go bezpiecznie i z uwzglednieniem innych uczestnikow ruchu.",
    "6193": "Tak. W przedstawionej sytuacji warunki do wyprzedzania sa zachowane: oznakowanie nie zabrania manewru, a widocznosc pozwala przeprowadzic go bezpiecznie. Przed rozpoczeciem wyprzedzania trzeba jeszcze upewnic sie, ze nie stworzysz zagrozenia dla innych uczestnikow ruchu.",
    "7369": "Tak. W tej sytuacji zawracanie na skrzyzowaniu jest dozwolone, bo sygnalizacja i oznakowanie nie wprowadzaja zakazu takiego manewru. Zajmowany pas i dopuszczony kierunek ruchu pozwalaja wykonac go zgodnie z przepisami.",
    "7407": "Tak. W tej sytuacji masz prawo wyprzedzic pojazd jadacy przed Toba, bo oznakowanie i warunki na drodze na to pozwalaja. Manewr wolno wykonac dopiero po upewnieniu sie, ze masz odpowiednia widocznosc i nie zajedziesz drogi innym pojazdom.",
    "941": "Nie. W tej sytuacji nie masz obowiazku ustepowac pierwszenstwa pojazdowi nadjezdzajacemu z prawej strony, bo nie on ma tutaj pierwszenstwo przejazdu. O tym, kto przejezdza pierwszy, rozstrzygaja oznakowanie i przebieg drogi na skrzyzowaniu.",
}


def build_payload() -> list[dict[str, Any]]:
    return [
        {
            "external_id": external_id,
            "resolved_explanation": explanation,
        }
        for external_id, explanation in sorted(RESOLUTIONS.items(), key=lambda item: int(item[0]))
    ]


def main() -> None:
    payload = build_payload()
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

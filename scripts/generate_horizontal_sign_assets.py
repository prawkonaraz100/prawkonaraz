from __future__ import annotations

import argparse
from pathlib import Path

from PIL import Image


ROOT = Path(__file__).resolve().parents[1]
WEBP_DIR = ROOT / "public" / "traffic-signs" / "signs" / "horizontal"
CUTOUT_DIR = ROOT / "public" / "traffic-signs" / "sign-cutouts"

WEBP_SIZE = 1200
CUTOUT_SIZE = 617

SIGNS = {
    "p-3-linia-jednostronnie-przekraczalna": "P-3 Linia jednostronnie przekraczalna.jpg",
    "p-4-linia-podwojna-ciagla": "P-4 Linia podwójna ciągła.jpg",
    "p-7a-linia-krawedziowa-przerywana": "P-7a Linia krawędziowa przerywana .jpg",
    "p-7b-linia-krawedziowa-ciagla": "P-7b Linia krawędziowa ciągła.jpg",
    "p-8a-strzalka-kierunkowa-na-wprost": "P-8a Strzałka kierunkowa na wprost.jpg",
    "p-8b-strzalka-kierunkowa-do-skrecania": "P-8b Strzałka kierunkowa do skręcania.jpg",
    "p-8c-strzalka-kierunkowa-do-zawracania": "P-8c  Strzałka kierunkowa do zawracania.jpg",
    "p-9-strzalka-naprowadzajaca-w-lewo": "P-9 strzałka naprowadzająca w lewo.jpg",
    "p-9b-strzalka-naprowadzajaca-w-prawo": "P-9b strzałka naprowadzająca w prawo.jpg",
    "p-10-przejscie-dla-pieszych": "P-10 Przejście dla pieszych.jpg",
    "p-12-linia-bezwzglednego-zatrzymania-stop": "P-12 Linia bezwzględnego zatrzymania - stop.jpg",
    "p-13-linia-warunkowego-zatrzymania-zlozona-z-trojkatow": "P-13 Linia warunkowego zatrzymania złożona z trójkątów.jpg",
    "p-14-linia-warunkowego-zatrzymania-zlozona-z-prostokatow": "P-14 Linia warunkowego zatrzymania złożona z prostokątów.jpg",
    "p-17-linia-przystankowa": "P-17 linia przystankowa.jpg",
}


def square_crop(image: Image.Image) -> Image.Image:
    side = min(image.size)
    left = (image.width - side) // 2
    top = (image.height - side) // 2

    return image.crop((left, top, left + side, top + side))


def save_assets(source_dir: Path, slug: str, source_name: str) -> None:
    source_path = source_dir / source_name

    if not source_path.is_file():
        raise FileNotFoundError(source_path)

    with Image.open(source_path) as source:
        square = square_crop(source.convert("RGB"))
        full = square.resize((WEBP_SIZE, WEBP_SIZE), Image.Resampling.LANCZOS)
        thumb = full.resize((CUTOUT_SIZE, CUTOUT_SIZE), Image.Resampling.LANCZOS)

        full.save(
            WEBP_DIR / f"znak-{slug}.webp",
            format="WEBP",
            quality=92,
            method=6,
        )
        thumb.quantize(colors=256).save(
            CUTOUT_DIR / f"{slug}.png",
            format="PNG",
            optimize=True,
        )


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--source-dir",
        type=Path,
        default=Path.home() / "Desktop" / "linje drogowe",
    )
    parser.add_argument("--slug", choices=sorted(SIGNS))
    args = parser.parse_args()

    WEBP_DIR.mkdir(parents=True, exist_ok=True)
    CUTOUT_DIR.mkdir(parents=True, exist_ok=True)

    signs = (
        {args.slug: SIGNS[args.slug]}
        if args.slug
        else SIGNS
    )

    for slug, source_name in signs.items():
        save_assets(args.source_dir, slug, source_name)
        print(f"generated {slug}")


if __name__ == "__main__":
    main()

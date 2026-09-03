from __future__ import annotations

import math
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
OUTPUT_DIR = ROOT / "public" / "traffic-signs" / "signs" / "informational"
SIZE = 1200
PADDING = 100
WHITE = (255, 255, 255)
BLACK = (18, 18, 18)
BLUE = (36, 42, 135)
YELLOW = (255, 212, 0)
RED = (214, 33, 35)


def load_font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        Path("C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf"),
        Path("C:/Windows/Fonts/segoeuib.ttf" if bold else "C:/Windows/Fonts/segoeui.ttf"),
        Path("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf" if bold else "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"),
    ]

    for candidate in candidates:
        if candidate.exists():
            return ImageFont.truetype(str(candidate), size=size)

    return ImageFont.load_default()


def new_canvas() -> tuple[Image.Image, ImageDraw.ImageDraw]:
    image = Image.new("RGB", (SIZE, SIZE), WHITE)
    return image, ImageDraw.Draw(image)


def centered_box() -> tuple[int, int, int, int]:
    return (PADDING, PADDING, SIZE - PADDING, SIZE - PADDING)


def draw_blue_square(draw: ImageDraw.ImageDraw, radius: int = 26, border: int = 14) -> tuple[int, int, int, int]:
    box = centered_box()
    draw.rounded_rectangle(box, radius=radius, fill=BLUE, outline=BLACK, width=border)
    return box


def draw_white_square(draw: ImageDraw.ImageDraw, radius: int = 18, border: int = 10) -> tuple[int, int, int, int]:
    box = centered_box()
    draw.rounded_rectangle(box, radius=radius, fill=WHITE, outline=BLACK, width=border)
    return box


def draw_direction_arrow(draw: ImageDraw.ImageDraw, x1: int, y1: int, x2: int, y2: int, thickness: int = 120) -> None:
    draw.line((x1, y1, x2, y2), fill=WHITE, width=thickness)
    angle = math.atan2(y2 - y1, x2 - x1)
    head = 170
    spread = math.pi / 7
    p1 = (x2, y2)
    p2 = (x2 - head * math.cos(angle - spread), y2 - head * math.sin(angle - spread))
    p3 = (x2 - head * math.cos(angle + spread), y2 - head * math.sin(angle + spread))
    draw.polygon((p1, p2, p3), fill=WHITE)


def draw_priority_road(draw: ImageDraw.ImageDraw, crossed: bool = False) -> None:
    box = draw_white_square(draw)
    left, top, right, bottom = box
    cx = (left + right) // 2
    cy = (top + bottom) // 2
    outer = 270
    inner = 220

    diamond_outer = [
        (cx, cy - outer),
        (cx + outer, cy),
        (cx, cy + outer),
        (cx - outer, cy),
    ]
    diamond_inner = [
        (cx, cy - inner),
        (cx + inner, cy),
        (cx, cy + inner),
        (cx - inner, cy),
    ]

    draw.polygon(diamond_outer, fill=WHITE, outline=BLACK)
    draw.polygon(diamond_inner, fill=YELLOW, outline=BLACK)

    if crossed:
        margin = 170
        draw.line((left + margin, top + margin, right - margin, bottom - margin), fill=BLACK, width=36)


def draw_one_way(draw: ImageDraw.ImageDraw) -> None:
    box = draw_blue_square(draw, radius=22)
    left, top, right, bottom = box
    draw_direction_arrow(draw, left + 220, (top + bottom) // 2, right - 170, (top + bottom) // 2, thickness=130)


def draw_dead_end(draw: ImageDraw.ImageDraw, entry: bool = False) -> None:
    box = draw_blue_square(draw, radius=22)
    left, top, right, bottom = box
    cx = (left + right) // 2
    stem_top = top + 220
    stem_bottom = bottom - 180
    stem_width = 120

    cross_top = top + 160
    cross_bottom = top + 290
    cross_left = left + 210
    cross_right = right - 210

    draw.rectangle((cx - stem_width // 2, stem_top, cx + stem_width // 2, stem_bottom), fill=WHITE)
    draw.rectangle((cross_left, cross_top, cross_right, cross_bottom), fill=WHITE)

    if entry:
        arm_top = cross_top + 15
        arm_bottom = cross_bottom - 15
        draw.rectangle((cross_left, arm_top, cx - stem_width // 2 - 30, arm_bottom), fill=RED)


def draw_pedestrian(draw: ImageDraw.ImageDraw) -> None:
    box = draw_blue_square(draw, radius=22)
    left, top, right, bottom = box
    tri = [
        ((left + right) // 2, top + 170),
        (right - 210, bottom - 180),
        (left + 210, bottom - 180),
    ]
    draw.polygon(tri, fill=WHITE)
    draw.polygon(
        [
            ((left + right) // 2, top + 230),
            (right - 260, bottom - 220),
            (left + 260, bottom - 220),
        ],
        fill=BLUE,
    )
    cx = (left + right) // 2
    head_y = top + 330
    draw.ellipse((cx - 34, head_y - 34, cx + 34, head_y + 34), fill=WHITE)
    draw.line((cx, head_y + 30, cx, head_y + 180), fill=WHITE, width=28)
    draw.line((cx, head_y + 80, cx - 90, head_y + 135), fill=WHITE, width=24)
    draw.line((cx, head_y + 80, cx + 90, head_y + 125), fill=WHITE, width=24)
    draw.line((cx, head_y + 180, cx - 85, head_y + 275), fill=WHITE, width=24)
    draw.line((cx, head_y + 180, cx + 78, head_y + 275), fill=WHITE, width=24)
    stripe_y = bottom - 190
    stripe_h = 28
    stripe_gap = 24
    start_x = left + 270
    for idx in range(3):
        x = start_x + idx * 120
        draw.rectangle((x, stripe_y, x + 70, stripe_y + stripe_h), fill=WHITE)
        draw.rectangle((x + 70, stripe_y + stripe_h + stripe_gap, x + 150, stripe_y + 2 * stripe_h + stripe_gap), fill=WHITE)


def draw_bicycle(draw: ImageDraw.ImageDraw, shifted: bool = False) -> None:
    box = draw_blue_square(draw, radius=22)
    left, top, right, bottom = box

    if shifted:
        tri = [
            ((left + right) // 2, top + 170),
            (right - 210, bottom - 180),
            (left + 210, bottom - 180),
        ]
        draw.polygon(tri, fill=WHITE)
        draw.polygon(
            [
                ((left + right) // 2, top + 230),
                (right - 260, bottom - 220),
                (left + 260, bottom - 220),
            ],
            fill=BLUE,
        )
        left += 30
        right -= 30
        top += 40
        bottom -= 20

    wheel_y = bottom - 260
    wheel_r = 110
    rear_x = left + 310
    front_x = right - 310
    draw.ellipse((rear_x - wheel_r, wheel_y - wheel_r, rear_x + wheel_r, wheel_y + wheel_r), outline=WHITE, width=24)
    draw.ellipse((front_x - wheel_r, wheel_y - wheel_r, front_x + wheel_r, wheel_y + wheel_r), outline=WHITE, width=24)

    hub_y = wheel_y - 20
    hub_x = (rear_x + front_x) // 2 - 30
    draw.line((rear_x, wheel_y, hub_x, hub_y), fill=WHITE, width=22)
    draw.line((hub_x, hub_y, front_x, wheel_y), fill=WHITE, width=22)
    draw.line((hub_x, hub_y, hub_x - 90, hub_y - 170), fill=WHITE, width=22)
    draw.line((hub_x - 90, hub_y - 170, rear_x + 20, wheel_y - 20), fill=WHITE, width=22)
    draw.line((hub_x - 90, hub_y - 170, hub_x + 80, hub_y - 170), fill=WHITE, width=22)
    draw.line((hub_x + 90, hub_y - 170, front_x - 20, wheel_y - 10), fill=WHITE, width=22)
    draw.line((hub_x + 80, hub_y - 170, hub_x + 110, hub_y - 120), fill=WHITE, width=18)
    draw.line((hub_x - 115, hub_y - 170, hub_x - 55, hub_y - 230), fill=WHITE, width=18)
    draw.line((hub_x + 40, hub_y - 220, hub_x + 130, hub_y - 220), fill=WHITE, width=18)
    draw.line((hub_x - 20, hub_y - 40, hub_x + 85, hub_y - 40), fill=WHITE, width=18)


def draw_parking(draw: ImageDraw.ImageDraw) -> None:
    box = draw_blue_square(draw, radius=22)
    left, top, right, bottom = box
    font = load_font(560, bold=True)
    text = "P"
    bbox = draw.textbbox((0, 0), text, font=font)
    tw = bbox[2] - bbox[0]
    th = bbox[3] - bbox[1]
    draw.text(((left + right - tw) / 2, (top + bottom - th) / 2 - 35), text, font=font, fill=WHITE)


def draw_fuel(draw: ImageDraw.ImageDraw) -> None:
    box = draw_blue_square(draw, radius=22)
    left, top, right, bottom = box
    body = (left + 320, top + 250, right - 370, bottom - 230)
    draw.rounded_rectangle(body, radius=24, outline=WHITE, width=26)
    draw.rectangle((body[0] + 50, body[1] + 60, body[2] - 50, body[1] + 180), outline=WHITE, width=20)
    draw.line((body[2], body[1] + 70, right - 240, top + 340), fill=WHITE, width=22)
    draw.line((right - 240, top + 340, right - 240, bottom - 300), fill=WHITE, width=22)
    draw.line((right - 240, bottom - 300, right - 290, bottom - 300), fill=WHITE, width=22)
    draw.line((body[0] + 130, body[3], body[0] + 130, body[3] + 90), fill=WHITE, width=26)
    draw.line((body[2] - 130, body[3], body[2] - 130, body[3] + 90), fill=WHITE, width=26)
    draw.line((body[0] + 70, body[3] + 90, body[2] - 70, body[3] + 90), fill=WHITE, width=24)


def draw_restaurant(draw: ImageDraw.ImageDraw) -> None:
    box = draw_blue_square(draw, radius=22)
    left, top, right, bottom = box
    cx = (left + right) // 2
    fork_x = cx - 120
    top_y = top + 220
    bottom_y = bottom - 220
    draw.line((fork_x, top_y, fork_x, bottom_y), fill=WHITE, width=30)
    for offset in (-55, -20, 15, 50):
        draw.line((fork_x + offset, top_y, fork_x + offset, top_y + 180), fill=WHITE, width=18)
    knife_x = cx + 120
    draw.polygon(
        [
            (knife_x - 25, top_y),
            (knife_x + 70, top_y + 140),
            (knife_x + 70, bottom_y),
            (knife_x - 35, bottom_y),
            (knife_x - 25, top_y + 240),
        ],
        fill=WHITE,
    )


def draw_info(draw: ImageDraw.ImageDraw) -> None:
    box = draw_blue_square(draw, radius=22)
    left, top, right, bottom = box
    font = load_font(560, bold=True)
    text = "i"
    bbox = draw.textbbox((0, 0), text, font=font)
    tw = bbox[2] - bbox[0]
    th = bbox[3] - bbox[1]
    draw.text(((left + right - tw) / 2, (top + bottom - th) / 2 - 40), text, font=font, fill=WHITE)


SIGN_BUILDERS = {
    "znak-d-1-droga-z-pierwszenstwem.webp": lambda d: draw_priority_road(d, crossed=False),
    "znak-d-2-koniec-drogi-z-pierwszenstwem.webp": lambda d: draw_priority_road(d, crossed=True),
    "znak-d-3-droga-jednokierunkowa.webp": draw_one_way,
    "znak-d-4a-droga-bez-przejazdu.webp": lambda d: draw_dead_end(d, entry=False),
    "znak-d-4b-wjazd-na-droge-bez-przejazdu.webp": lambda d: draw_dead_end(d, entry=True),
    "znak-d-6-przejscie-dla-pieszych.webp": draw_pedestrian,
    "znak-d-6a-przejazd-dla-rowerzystow.webp": lambda d: draw_bicycle(d, shifted=True),
    "znak-d-18-parking.webp": draw_parking,
    "znak-d-23-stacja-paliwowa.webp": draw_fuel,
    "znak-d-28-restauracja.webp": draw_restaurant,
    "znak-d-34-punkt-informacji-turystycznej.webp": draw_info,
}


def main() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    for filename, builder in SIGN_BUILDERS.items():
        image, draw = new_canvas()
        builder(draw)
        image.save(OUTPUT_DIR / filename, format="WEBP", quality=92, method=6)


if __name__ == "__main__":
    main()

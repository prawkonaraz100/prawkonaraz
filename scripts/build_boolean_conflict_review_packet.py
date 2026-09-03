from __future__ import annotations

import csv
import json
import subprocess
from pathlib import Path
from typing import Any

import psycopg2
from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
SUMMARY_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "summary.json"
BOOLEAN_CONFLICTS_PATH = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "overlap-only-review"
    / "remaining-shared-conflicts"
    / "boolean_answer_conflict.json"
)
QUEUE_PATH = ROOT / "output" / "analysis" / "pj360-compare" / "queues" / "tier-b-review.json"
OUTPUT_DIR = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "overlap-only-review"
    / "remaining-shared-conflicts"
    / "boolean-review"
)
FRAME_DIR = OUTPUT_DIR / "frames"
CONTACT_SHEET_PATH = OUTPUT_DIR / "contact-sheet.jpg"
JSON_PATH = OUTPUT_DIR / "packet.json"
CSV_PATH = OUTPUT_DIR / "packet.csv"

MEDIA_ROOT = Path("D:/datasets/mi-prawo-jazdy-2026/app-media")

CARD_WIDTH = 420
CARD_HEIGHT = 320
THUMB_WIDTH = 392
THUMB_HEIGHT = 180
PADDING = 14
COLS = 3


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def write_csv(path: Path, rows: list[dict[str, Any]]) -> None:
    if not rows:
        path.write_text("", encoding="utf-8")
        return

    fieldnames = list(rows[0].keys())
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)


def get_dsn() -> str:
    summary = load_json(SUMMARY_PATH)
    return str(summary["dsn"]).replace("password=***", "password=prawkobit")


def normalize_answer(value: str) -> str:
    value = (value or "").strip().lower()
    if value in {"a", "tak"}:
        return "tak"
    if value in {"b", "nie"}:
        return "nie"
    return value


def wrap_text(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.ImageFont, width: int) -> list[str]:
    words = text.split()
    lines: list[str] = []
    current = ""

    for word in words:
        candidate = word if current == "" else f"{current} {word}"
        if draw.textbbox((0, 0), candidate, font=font)[2] <= width:
            current = candidate
        else:
            if current:
                lines.append(current)
            current = word

    if current:
        lines.append(current)

    return lines


def fetch_local_rows() -> dict[str, dict[str, Any]]:
    queue = load_json(QUEUE_PATH)
    by_id: dict[str, dict[str, Any]] = {}

    for item in queue:
        local = item["local"]
        by_id[str(local["gov_id"])] = {
            "local": local,
            "external": item.get("external"),
            "external_candidates": item.get("external_candidates") or [],
        }

    return by_id


def fetch_media_rows(conflict_ids: list[str]) -> dict[str, dict[str, Any]]:
    dsn = get_dsn()
    conn = psycopg2.connect(dsn)
    cur = conn.cursor()
    cur.execute(
        """
        select q.external_id,
               min(case when qm.kind = 'image' then qm.path end) as image_path,
               min(case when qm.kind = 'video' then qm.path end) as video_path,
               min(case when qm.poster_path is not null then qm.poster_path end) as poster_path,
               string_agg(distinct lc.code, ',' order by lc.code) as categories
        from questions q
        join license_categories lc on lc.id = q.license_category_id
        left join question_media qm on qm.question_id = q.id
        where q.external_id = any(%s)
        group by q.external_id
        """,
        (conflict_ids,),
    )
    rows = {
        str(external_id): {
            "image_path": image_path,
            "video_path": video_path,
            "poster_path": poster_path,
            "categories": categories.split(",") if categories else [],
        }
        for external_id, image_path, video_path, poster_path, categories in cur.fetchall()
    }
    conn.close()
    return rows


def resolve_media_path(relative_path: str | None) -> Path | None:
    if not relative_path:
        return None
    path = MEDIA_ROOT / relative_path.replace("/", "\\")
    return path if path.exists() else None


def ffprobe_duration(path: Path) -> float | None:
    try:
        result = subprocess.run(
            [
                "ffprobe",
                "-v",
                "error",
                "-show_entries",
                "format=duration",
                "-of",
                "default=noprint_wrappers=1:nokey=1",
                str(path),
            ],
            capture_output=True,
            text=True,
            check=True,
        )
    except (subprocess.CalledProcessError, FileNotFoundError):
        return None

    try:
        return float(result.stdout.strip())
    except ValueError:
        return None


def extract_frames(gov_id: str, video_path: Path) -> list[Path]:
    FRAME_DIR.mkdir(parents=True, exist_ok=True)
    duration = ffprobe_duration(video_path)
    if not duration or duration <= 0:
        return []

    targets = [max(duration * ratio, 0.1) for ratio in (0.15, 0.45, 0.75)]
    output_paths: list[Path] = []

    for index, second in enumerate(targets, start=1):
        frame_path = FRAME_DIR / f"{gov_id}-frame-{index}.jpg"
        subprocess.run(
            [
                "ffmpeg",
                "-y",
                "-ss",
                f"{second:.2f}",
                "-i",
                str(video_path),
                "-frames:v",
                "1",
                str(frame_path),
            ],
            capture_output=True,
            check=False,
        )
        if frame_path.exists():
            output_paths.append(frame_path)

    return output_paths


def pick_preview_image(gov_id: str, media: dict[str, Any]) -> tuple[Path | None, list[Path]]:
    image_path = resolve_media_path(media.get("image_path"))
    if image_path:
        return image_path, []

    poster_path = resolve_media_path(media.get("poster_path"))
    video_path = resolve_media_path(media.get("video_path"))
    frame_paths = extract_frames(gov_id, video_path) if video_path else []

    if poster_path:
        return poster_path, frame_paths
    if frame_paths:
        return frame_paths[0], frame_paths

    return None, []


def render_card(draw: ImageDraw.ImageDraw, card: Image.Image, row: dict[str, Any], preview_path: Path | None) -> None:
    title_font = ImageFont.load_default()
    body_font = ImageFont.load_default()
    muted_font = ImageFont.load_default()

    draw.rounded_rectangle((0, 0, CARD_WIDTH - 1, CARD_HEIGHT - 1), radius=16, fill="white", outline="#d1d5db")

    text_y = PADDING
    id_line = f"ID {row['gov_id']} · {row['local_answer'].upper()} · {row['local_media']}"
    draw.text((PADDING, text_y), id_line, fill="#111827", font=title_font)
    text_y += 22

    prompt_lines = wrap_text(draw, row["prompt"], body_font, CARD_WIDTH - (PADDING * 2))
    for line in prompt_lines[:4]:
        draw.text((PADDING, text_y), line, fill="#111827", font=body_font)
        text_y += 16

    draw.text((PADDING, text_y + 4), f"PJ360: {row['external_answer'].upper()}", fill="#b91c1c", font=muted_font)
    draw.text((PADDING + 120, text_y + 4), f"Kat: {','.join(row['categories'])}", fill="#374151", font=muted_font)
    text_y += 28

    thumb_top = text_y
    thumb_box = (PADDING, thumb_top, PADDING + THUMB_WIDTH, thumb_top + THUMB_HEIGHT)
    draw.rounded_rectangle(thumb_box, radius=12, fill="#f3f4f6", outline="#e5e7eb")

    if preview_path and preview_path.exists():
        preview = Image.open(preview_path).convert("RGB")
        preview.thumbnail((THUMB_WIDTH - 8, THUMB_HEIGHT - 8))
        paste_x = PADDING + ((THUMB_WIDTH - preview.width) // 2)
        paste_y = thumb_top + ((THUMB_HEIGHT - preview.height) // 2)
        card.paste(preview, (paste_x, paste_y))
    else:
        draw.text((PADDING + 8, thumb_top + 8), "Brak podglądu", fill="#6b7280", font=muted_font)


def build_contact_sheet(rows: list[dict[str, Any]]) -> None:
    if not rows:
        return

    sheet_rows = (len(rows) + COLS - 1) // COLS
    width = (CARD_WIDTH * COLS) + (PADDING * (COLS + 1))
    height = (CARD_HEIGHT * sheet_rows) + (PADDING * (sheet_rows + 1))
    sheet = Image.new("RGB", (width, height), "#f8fafc")

    for index, row in enumerate(rows):
        col = index % COLS
        line = index // COLS
        x = PADDING + (col * (CARD_WIDTH + PADDING))
        y = PADDING + (line * (CARD_HEIGHT + PADDING))

        card = Image.new("RGB", (CARD_WIDTH, CARD_HEIGHT), "white")
        draw = ImageDraw.Draw(card)
        preview_path = Path(row["preview_image_path"]) if row["preview_image_path"] else None
        render_card(draw, card, row, preview_path)
        sheet.paste(card, (x, y))

    CONTACT_SHEET_PATH.parent.mkdir(parents=True, exist_ok=True)
    sheet.save(CONTACT_SHEET_PATH, quality=90)


def main() -> None:
    conflicts = load_json(BOOLEAN_CONFLICTS_PATH)
    queue_by_id = fetch_local_rows()
    conflict_ids = [str(item["gov_id"]) for item in conflicts]
    media_rows = fetch_media_rows(conflict_ids)

    packet_rows: list[dict[str, Any]] = []

    for conflict in conflicts:
        gov_id = str(conflict["gov_id"])
        queue_item = queue_by_id[gov_id]
        local = queue_item["local"]
        external = queue_item.get("external") or (queue_item.get("external_candidates") or [{}])[0]
        media = media_rows.get(gov_id, {})
        preview_image, frame_paths = pick_preview_image(gov_id, media)

        packet_rows.append(
            {
                "gov_id": gov_id,
                "prompt": conflict["prompt"],
                "question_type": local["question_type"],
                "local_answer": normalize_answer(conflict["local_answer"]),
                "external_answer": normalize_answer(conflict["external_answer"]),
                "local_media": conflict["local_media"],
                "external_media": conflict["external_media"],
                "categories": media.get("categories", local["categories"]),
                "source_url": conflict["source_url"],
                "external_explanation_text": external.get("explanation_text"),
                "preview_image_path": str(preview_image) if preview_image else None,
                "frame_paths": [str(path) for path in frame_paths],
                "image_path": str(resolve_media_path(media.get("image_path"))) if media.get("image_path") else None,
                "video_path": str(resolve_media_path(media.get("video_path"))) if media.get("video_path") else None,
                "poster_path": str(resolve_media_path(media.get("poster_path"))) if media.get("poster_path") else None,
            }
        )

    packet_rows.sort(key=lambda item: item["gov_id"])

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    write_json(JSON_PATH, packet_rows)
    write_csv(
        CSV_PATH,
        [
            {
                **row,
                "categories": ",".join(row["categories"]),
                "frame_paths": " | ".join(row["frame_paths"]),
            }
            for row in packet_rows
        ],
    )
    build_contact_sheet(packet_rows)

    print(
        json.dumps(
            {
                "packet_count": len(packet_rows),
                "contact_sheet": str(CONTACT_SHEET_PATH),
                "packet_json": str(JSON_PATH),
            },
            ensure_ascii=False,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()

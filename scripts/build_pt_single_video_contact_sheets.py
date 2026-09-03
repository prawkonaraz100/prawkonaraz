from __future__ import annotations

import json
import subprocess
import sys
from pathlib import Path

from PIL import Image, ImageDraw


ROOT = Path(__file__).resolve().parents[1]
APP_MEDIA_ROOT = Path("D:/datasets/mi-prawo-jazdy-2026/app-media")
INPUT_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-media-review" / "all.json"
OUTPUT_DIR = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-media-review" / "single-contact-sheets"


def load_rows() -> list[dict[str, object]]:
    return json.loads(INPUT_PATH.read_text(encoding="utf-8"))


def ffprobe_duration(video_path: Path) -> float:
    command = [
        "ffprobe",
        "-v",
        "error",
        "-show_entries",
        "format=duration",
        "-of",
        "default=noprint_wrappers=1:nokey=1",
        str(video_path),
    ]
    result = subprocess.run(command, check=True, capture_output=True, text=True)
    return float(result.stdout.strip())


def build_timestamps(duration: float, frame_count: int = 4) -> list[float]:
    return [duration * (index + 1) / (frame_count + 1) for index in range(frame_count)]


def extract_frame(video_path: Path, timestamp: float, output_path: Path) -> None:
    command = [
        "ffmpeg",
        "-y",
        "-ss",
        f"{timestamp:.3f}",
        "-i",
        str(video_path),
        "-frames:v",
        "1",
        str(output_path),
    ]
    subprocess.run(command, check=True, capture_output=True, text=True)


def render_contact_sheet(video_path: Path, output_path: Path) -> dict[str, object]:
    duration = ffprobe_duration(video_path)
    timestamps = build_timestamps(duration)

    output_path.parent.mkdir(parents=True, exist_ok=True)
    temp_dir = output_path.parent / "_tmp"
    temp_dir.mkdir(parents=True, exist_ok=True)

    frame_paths: list[Path] = []
    for index, timestamp in enumerate(timestamps, start=1):
        frame_path = temp_dir / f"{output_path.stem}__frame-{index}.jpg"
        extract_frame(video_path, timestamp, frame_path)
        frame_paths.append(frame_path)

    frames = [Image.open(frame_path).convert("RGB") for frame_path in frame_paths]
    tile_width = 480
    resized_frames = []
    for frame in frames:
        ratio = tile_width / frame.width
        resized_height = int(frame.height * ratio)
        resized_frames.append(frame.resize((tile_width, resized_height)))

    tile_height = max(frame.height for frame in resized_frames)
    canvas = Image.new("RGB", (tile_width * 2, tile_height * 2), color=(18, 18, 18))
    draw = ImageDraw.Draw(canvas)

    for index, (frame, timestamp) in enumerate(zip(resized_frames, timestamps)):
        column = index % 2
        row = index // 2
        x = column * tile_width
        y = row * tile_height
        canvas.paste(frame, (x, y))
        draw.rectangle((x + 8, y + 8, x + 112, y + 38), fill=(0, 0, 0))
        draw.text((x + 16, y + 14), f"{timestamp:.1f}s", fill=(255, 255, 255))

    canvas.save(output_path, quality=92)

    return {
        "video_path": str(video_path),
        "contact_sheet_path": str(output_path),
        "duration_seconds": round(duration, 3),
        "timestamps": [round(timestamp, 3) for timestamp in timestamps],
    }


def main() -> None:
    targets = {item.strip() for item in sys.argv[1:] if item.strip()}
    if not targets:
        raise SystemExit("Usage: python scripts/build_pt_single_video_contact_sheets.py <external_id> [<external_id> ...]")

    rows = load_rows()
    selected = [row for row in rows if str(row["external_id"]) in targets and row["primary_media_kind"] == "video"]

    reports: list[dict[str, object]] = []

    for row in selected:
        external_id = str(row["external_id"])
        relative_path = str(row["primary_media_path"])
        video_path = APP_MEDIA_ROOT / relative_path
        output_path = OUTPUT_DIR / f"{external_id}.jpg"

        report = dict(row)
        report.update(render_contact_sheet(video_path, output_path))
        reports.append(report)

    reports.sort(key=lambda item: int(str(item["external_id"])))
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    summary_path = OUTPUT_DIR / "last-run.json"
    summary_path.write_text(json.dumps(reports, ensure_ascii=False, indent=2), encoding="utf-8")

    print(
        json.dumps(
            {
                "target_count": len(targets),
                "rendered_count": len(reports),
                "summary_path": str(summary_path),
            },
            ensure_ascii=False,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()

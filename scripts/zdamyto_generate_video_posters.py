from __future__ import annotations

import argparse
import json
import os
import sqlite3
import subprocess
from concurrent.futures import ThreadPoolExecutor, as_completed
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Any


REPO_ROOT = Path(__file__).resolve().parents[1]
DEFAULT_DB_PATH = REPO_ROOT / "database" / "database.sqlite"
DEFAULT_REPORT_PATH = REPO_ROOT / "storage" / "app" / "import-reports" / "zdamyto-video-posters.json"


@dataclass
class VideoRow:
    id: int
    path: str
    poster_path: str | None
    disk: str


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Generate missing poster images for imported zdamyto video questions.",
    )
    parser.add_argument(
        "--db",
        type=Path,
        default=DEFAULT_DB_PATH,
    )
    parser.add_argument(
        "--media-root",
        type=Path,
        default=resolve_media_root(),
    )
    parser.add_argument(
        "--ffmpeg-binary",
        default=os.environ.get("MEDIA_FFMPEG_BINARY", "ffmpeg"),
    )
    parser.add_argument(
        "--workers",
        type=int,
        default=4,
    )
    parser.add_argument(
        "--limit",
        type=int,
        default=None,
    )
    parser.add_argument(
        "--report",
        type=Path,
        default=DEFAULT_REPORT_PATH,
    )
    return parser.parse_args()


def resolve_media_root() -> Path:
    env_path = REPO_ROOT / ".env"
    if env_path.exists():
        for line in env_path.read_text(encoding="utf-8").splitlines():
            if line.startswith("MEDIA_LOCAL_ROOT="):
                raw = line.split("=", 1)[1].strip().strip('"').strip("'")
                if raw:
                    return Path(raw)

    return REPO_ROOT / "storage" / "app" / "public-media"


def load_rows(db_path: Path, limit: int | None) -> list[VideoRow]:
    connection = sqlite3.connect(db_path)
    connection.row_factory = sqlite3.Row

    try:
        sql = """
            select id, path, poster_path, disk
            from question_media
            where kind = 'video'
              and (poster_path is null or trim(poster_path) = '')
              and path like 'media/questions/zdamyto/%'
            order by id asc
        """
        if limit is not None:
            sql += f" limit {int(limit)}"

        return [
            VideoRow(
                id=int(row["id"]),
                path=str(row["path"]),
                poster_path=str(row["poster_path"]) if row["poster_path"] else None,
                disk=str(row["disk"]),
            )
            for row in connection.execute(sql).fetchall()
        ]
    finally:
        connection.close()


def derive_poster_path(video_relative_path: str) -> str:
    video_path = Path(video_relative_path)
    return str(video_path.with_name(f"{video_path.stem}-poster.jpg")).replace("\\", "/")


def build_ffmpeg_command(ffmpeg_binary: str, source: Path, target: Path) -> list[str]:
    return [
        ffmpeg_binary,
        "-y",
        "-ss",
        "00:00:00.8",
        "-i",
        str(source),
        "-frames:v",
        "1",
        "-vf",
        "scale='min(1280,iw)':-2",
        "-q:v",
        "3",
        str(target),
    ]


def generate_poster(ffmpeg_binary: str, source: Path, target: Path) -> None:
    target.parent.mkdir(parents=True, exist_ok=True)

    primary = subprocess.run(
        build_ffmpeg_command(ffmpeg_binary, source, target),
        capture_output=True,
        text=True,
    )

    if primary.returncode == 0 and target.exists() and target.stat().st_size > 0:
        return

    fallback = subprocess.run(
        [
            ffmpeg_binary,
            "-y",
            "-i",
            str(source),
            "-frames:v",
            "1",
            "-vf",
            "scale='min(1280,iw)':-2",
            "-q:v",
            "3",
            str(target),
        ],
        capture_output=True,
        text=True,
    )

    if fallback.returncode != 0 or not target.exists() or target.stat().st_size <= 0:
        stderr = (fallback.stderr or primary.stderr or "").strip()
        raise RuntimeError(stderr or "ffmpeg failed to generate poster")


def process_row(row: VideoRow, media_root: Path, ffmpeg_binary: str) -> dict[str, Any]:
    video_absolute = media_root / row.path
    poster_relative = derive_poster_path(row.path)
    poster_absolute = media_root / poster_relative

    if not video_absolute.exists():
        return {
            "id": row.id,
            "status": "missing_video",
            "video_path": row.path,
        }

    try:
        if not poster_absolute.exists() or poster_absolute.stat().st_size <= 0:
            generate_poster(ffmpeg_binary, video_absolute, poster_absolute)

        return {
            "id": row.id,
            "status": "generated",
            "video_path": row.path,
            "poster_path": poster_relative,
            "poster_bytes": poster_absolute.stat().st_size,
        }
    except Exception as exc:  # noqa: BLE001
        return {
            "id": row.id,
            "status": "failed",
            "video_path": row.path,
            "poster_path": poster_relative,
            "error": str(exc),
        }


def update_database(db_path: Path, successful_rows: list[dict[str, Any]]) -> None:
    if not successful_rows:
        return

    connection = sqlite3.connect(db_path)
    try:
        now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")
        connection.executemany(
            "update question_media set poster_path = ?, updated_at = ? where id = ?",
            [
                (
                    row["poster_path"],
                    now,
                    row["id"],
                )
                for row in successful_rows
            ],
        )
        connection.commit()
    finally:
        connection.close()


def main() -> int:
    args = parse_args()
    rows = load_rows(args.db, args.limit)
    args.report.parent.mkdir(parents=True, exist_ok=True)

    report: dict[str, Any] = {
        "started_at": datetime.now(timezone.utc).isoformat(),
        "db": str(args.db),
        "media_root": str(args.media_root),
        "ffmpeg_binary": args.ffmpeg_binary,
        "workers": args.workers,
        "requested_rows": len(rows),
        "results": [],
    }

    if not rows:
        report["generated"] = 0
        report["failed"] = 0
        report["missing_video"] = 0
        report["finished_at"] = datetime.now(timezone.utc).isoformat()
        args.report.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
        print(json.dumps(report, ensure_ascii=False, indent=2))
        return 0

    results: list[dict[str, Any]] = []
    max_workers = max(1, min(args.workers, 8))

    with ThreadPoolExecutor(max_workers=max_workers) as executor:
        futures = {
            executor.submit(process_row, row, args.media_root, args.ffmpeg_binary): row.id
            for row in rows
        }

        for future in as_completed(futures):
            results.append(future.result())

    successful = [row for row in results if row["status"] == "generated"]
    update_database(args.db, successful)

    report["results"] = sorted(results, key=lambda item: item["id"])
    report["generated"] = sum(1 for row in results if row["status"] == "generated")
    report["failed"] = sum(1 for row in results if row["status"] == "failed")
    report["missing_video"] = sum(1 for row in results if row["status"] == "missing_video")
    report["finished_at"] = datetime.now(timezone.utc).isoformat()
    args.report.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps({
        "requested_rows": report["requested_rows"],
        "generated": report["generated"],
        "failed": report["failed"],
        "missing_video": report["missing_video"],
        "report": str(args.report),
    }, ensure_ascii=False, indent=2))

    return 0 if report["failed"] == 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())

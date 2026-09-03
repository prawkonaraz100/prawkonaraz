from __future__ import annotations

import argparse
import json
import math
import subprocess
from collections import Counter, defaultdict
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import psycopg


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_OUTPUT_DIR = ROOT / "output" / "analysis" / "pj360-media-audit"
DB_DSN = "host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit"
FFMPEG_BIN = "ffmpeg"
FFPROBE_BIN = "ffprobe"


@dataclass
class QuestionMediaRow:
    question_id: int
    category_code: str
    external_id: str
    prompt: str
    question_metadata: dict[str, Any]
    media_id: int | None
    kind: str | None
    disk: str | None
    path: str | None
    poster_path: str | None
    bytes: int | None
    duration_seconds: int | None
    width: int | None
    height: int | None
    variant: str | None
    media_metadata: dict[str, Any]


def resolve_media_root() -> Path:
    env_path = ROOT / ".env"
    if env_path.exists():
        for line in env_path.read_text(encoding="utf-8").splitlines():
            if line.startswith("MEDIA_LOCAL_ROOT="):
                raw = line.split("=", 1)[1].strip().strip('"').strip("'")
                if raw:
                    return Path(raw)

    return ROOT / "storage" / "app" / "public-media"


MEDIA_ROOT = resolve_media_root()


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Audit and enrich media metadata for imported PJ360 questions."
    )
    parser.add_argument(
        "--category",
        default=None,
        help="Optional license category code filter, e.g. B.",
    )
    parser.add_argument(
        "--write-db",
        action="store_true",
        help="Persist inferred metadata back to question_media.",
    )
    parser.add_argument(
        "--generate-posters",
        action="store_true",
        help="Generate poster JPG files for PJ360 videos when missing.",
    )
    parser.add_argument(
        "--output",
        default=str(DEFAULT_OUTPUT_DIR / "pj360-media-audit-report.json"),
        help="Path to output JSON report.",
    )
    return parser.parse_args()


def dump_json(path: Path, payload: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")


def fetch_rows(category_code: str | None) -> list[QuestionMediaRow]:
    sql = """
        select
            q.id as question_id,
            lc.code as category_code,
            q.external_id,
            q.prompt,
            q.metadata as question_metadata,
            qm.id as media_id,
            qm.kind,
            qm.disk,
            qm.path,
            qm.poster_path,
            qm.bytes,
            qm.duration_seconds,
            qm.width,
            qm.height,
            qm.variant,
            qm.metadata as media_metadata
        from questions q
        join license_categories lc on lc.id = q.license_category_id
        left join question_media qm on qm.question_id = q.id
        where q.source = 'pj360'
    """
    params: list[Any] = []

    if category_code:
        sql += " and lc.code = %s"
        params.append(category_code.upper())

    sql += " order by lc.code, q.external_id, qm.sort_order nulls first, qm.id nulls first"

    with psycopg.connect(DB_DSN) as connection, connection.cursor() as cursor:
        cursor.execute(sql, params)
        rows = cursor.fetchall()

    return [
        QuestionMediaRow(
            question_id=row[0],
            category_code=row[1],
            external_id=row[2],
            prompt=row[3],
            question_metadata=row[4] or {},
            media_id=row[5],
            kind=row[6],
            disk=row[7],
            path=row[8],
            poster_path=row[9],
            bytes=row[10],
            duration_seconds=row[11],
            width=row[12],
            height=row[13],
            variant=row[14],
            media_metadata=row[15] or {},
        )
        for row in rows
    ]


def resolve_disk_path(relative_path: str | None) -> Path | None:
    if not relative_path:
        return None

    return MEDIA_ROOT / Path(relative_path)


def probe_media(path: Path) -> dict[str, Any]:
    command = [
        FFPROBE_BIN,
        "-v",
        "error",
        "-show_entries",
        "stream=width,height,duration:format=duration",
        "-of",
        "json",
        str(path),
    ]
    completed = subprocess.run(command, capture_output=True, text=True, check=False)

    if completed.returncode != 0:
        raise RuntimeError(completed.stderr.strip() or completed.stdout.strip() or "ffprobe_failed")

    payload = json.loads(completed.stdout or "{}")
    streams = payload.get("streams") or []
    first_stream = streams[0] if streams else {}
    duration_raw = first_stream.get("duration")

    if duration_raw in (None, "", "N/A"):
        duration_raw = (payload.get("format") or {}).get("duration")

    duration_seconds = None

    try:
        if duration_raw not in (None, "", "N/A"):
            duration_seconds = max(0, int(math.ceil(float(duration_raw))))
    except (TypeError, ValueError):
        duration_seconds = None

    return {
        "width": first_stream.get("width"),
        "height": first_stream.get("height"),
        "duration_seconds": duration_seconds,
    }


def build_video_poster_path(media_relative_path: str) -> str:
    relative = Path(media_relative_path)
    filename = f"{relative.stem}-poster.jpg"

    return (relative.parent / filename).as_posix()


def ensure_video_poster(video_path: Path, poster_path: Path) -> None:
    poster_path.parent.mkdir(parents=True, exist_ok=True)
    command = [
        FFMPEG_BIN,
        "-y",
        "-ss",
        "00:00:00.500",
        "-i",
        str(video_path),
        "-frames:v",
        "1",
        "-q:v",
        "2",
        str(poster_path),
    ]
    completed = subprocess.run(command, capture_output=True, text=True, check=False)

    if completed.returncode != 0:
        raise RuntimeError(completed.stderr.strip() or completed.stdout.strip() or "ffmpeg_failed")


def update_media_row(media_id: int, updates: dict[str, Any]) -> None:
    if not updates:
        return

    assignments = ", ".join(f"{column} = %s" for column in updates.keys())
    values = list(updates.values())
    values.append(media_id)

    with psycopg.connect(DB_DSN) as connection, connection.cursor() as cursor:
        cursor.execute(
            f"update question_media set {assignments}, updated_at = now() where id = %s",
            values,
        )
        connection.commit()


def main() -> None:
    args = parse_args()
    rows = fetch_rows(args.category)
    questions: dict[int, dict[str, Any]] = {}

    for row in rows:
        question = questions.setdefault(
            row.question_id,
            {
                "question_id": row.question_id,
                "category_code": row.category_code,
                "external_id": row.external_id,
                "prompt": row.prompt,
                "question_metadata": row.question_metadata,
                "media_rows": [],
            },
        )
        question["media_rows"].append(row)

    summary = {
        "questions_total": len(questions),
        "questions_with_media": 0,
        "questions_without_media": 0,
        "media_rows_total": 0,
        "image_rows": 0,
        "video_rows": 0,
        "missing_media_files": 0,
        "videos_missing_posters": 0,
        "rows_missing_dimensions": 0,
        "videos_missing_duration": 0,
        "rows_updated": 0,
        "posters_generated": 0,
        "text_only_questions": 0,
        "questions_with_media_expected_but_missing": 0,
    }
    question_counts_by_category: Counter[str] = Counter()
    issue_buckets: dict[str, list[dict[str, Any]]] = defaultdict(list)
    updated_rows: list[dict[str, Any]] = []

    for question in questions.values():
        question_counts_by_category[question["category_code"]] += 1
        media_rows: list[QuestionMediaRow] = [
            row for row in question["media_rows"] if row.media_id is not None
        ]
        expected_main_media = bool((question["question_metadata"] or {}).get("main_media_original"))

        if not media_rows:
            summary["questions_without_media"] += 1
            if expected_main_media:
                summary["questions_with_media_expected_but_missing"] += 1
                issue_buckets["question_expected_media_without_rows"].append(
                    {
                        "question_id": question["question_id"],
                        "category_code": question["category_code"],
                        "external_id": question["external_id"],
                        "main_media_original": question["question_metadata"].get("main_media_original"),
                        "prompt": question["prompt"],
                    }
                )
            else:
                summary["text_only_questions"] += 1
            continue

        summary["questions_with_media"] += 1

        for media in media_rows:
            summary["media_rows_total"] += 1

            if media.kind == "image":
                summary["image_rows"] += 1
            elif media.kind == "video":
                summary["video_rows"] += 1

            media_path = resolve_disk_path(media.path)
            poster_disk_path = resolve_disk_path(media.poster_path)
            file_exists = bool(media_path and media_path.exists())
            poster_exists = bool(poster_disk_path and poster_disk_path.exists())

            updates: dict[str, Any] = {}
            issue_row = {
                "question_id": media.question_id,
                "category_code": media.category_code,
                "external_id": media.external_id,
                "media_id": media.media_id,
                "kind": media.kind,
                "path": media.path,
                "poster_path": media.poster_path,
            }

            if not file_exists:
                summary["missing_media_files"] += 1
                issue_buckets["missing_media_files"].append(issue_row)
                continue

            if media.bytes != media_path.stat().st_size:
                updates["bytes"] = media_path.stat().st_size

            try:
                probed = probe_media(media_path)
            except RuntimeError as error:
                issue_buckets["probe_failed"].append({**issue_row, "error": str(error)})
                continue

            if media.width != probed["width"] or media.height != probed["height"]:
                if probed["width"] and probed["height"]:
                    updates["width"] = int(probed["width"])
                    updates["height"] = int(probed["height"])

            if media.kind == "video":
                if media.duration_seconds != probed["duration_seconds"] and probed["duration_seconds"] is not None:
                    updates["duration_seconds"] = int(probed["duration_seconds"])

                if not media.poster_path or not poster_exists:
                    summary["videos_missing_posters"] += 1

                    if args.generate_posters:
                        poster_relative = build_video_poster_path(media.path or "")
                        poster_absolute = resolve_disk_path(poster_relative)

                        try:
                            ensure_video_poster(media_path, poster_absolute)
                            poster_exists = True
                            updates["poster_path"] = poster_relative
                            summary["posters_generated"] += 1
                        except RuntimeError as error:
                            issue_buckets["poster_generation_failed"].append(
                                {**issue_row, "error": str(error)}
                            )
                    else:
                        issue_buckets["videos_missing_posters"].append(issue_row)

                if (media.duration_seconds or updates.get("duration_seconds")) in (None, 0):
                    summary["videos_missing_duration"] += 1

            if (
                (media.width or updates.get("width")) in (None, 0)
                or (media.height or updates.get("height")) in (None, 0)
            ):
                summary["rows_missing_dimensions"] += 1

            if args.write_db and updates and media.media_id is not None:
                update_media_row(media.media_id, updates)
                summary["rows_updated"] += 1

            if updates:
                updated_rows.append(
                    {
                        **issue_row,
                        "updates": updates,
                    }
                )

    report = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "category_filter": args.category.upper() if args.category else None,
        "write_db": bool(args.write_db),
        "generate_posters": bool(args.generate_posters),
        "summary": summary,
        "question_counts_by_category": dict(sorted(question_counts_by_category.items())),
        "issues": issue_buckets,
        "updated_rows": updated_rows,
    }

    output_path = Path(args.output)
    dump_json(output_path, report)
    print(json.dumps(report["summary"], ensure_ascii=False, indent=2))
    print(f"report_path={output_path}")


if __name__ == "__main__":
    main()

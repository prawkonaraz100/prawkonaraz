from __future__ import annotations

import csv
import json
from collections import Counter
from pathlib import Path
from typing import Any

try:
    import psycopg  # type: ignore
except ImportError:  # pragma: no cover
    psycopg = None  # type: ignore

try:
    import psycopg2  # type: ignore
    from psycopg2.extras import RealDictCursor  # type: ignore
except ImportError:  # pragma: no cover
    psycopg2 = None  # type: ignore
    RealDictCursor = None  # type: ignore


ROOT = Path(__file__).resolve().parents[1]
SUMMARY_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "summary.json"
OUTPUT_DIR = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-media-review"


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def write_csv(path: Path, rows: list[dict[str, Any]]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
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


def fetch_rows() -> list[dict[str, Any]]:
    dsn = get_dsn()
    sql = """
        select
            q.external_id,
            q.prompt,
            q.question_type,
            q.correct_answer,
            q.option_a,
            q.option_b,
            q.option_c,
            coalesce(primary_media.kind, 'none') as primary_media_kind,
            primary_media.disk as primary_media_disk,
            primary_media.path as primary_media_path,
            primary_media.poster_path as primary_media_poster_path
        from questions q
        join license_categories lc on lc.id = q.license_category_id
        left join lateral (
            select
                qm.kind,
                qm.disk,
                qm.path,
                qm.poster_path
            from question_media qm
            where qm.question_id = q.id
            order by qm.sort_order asc, qm.id asc
            limit 1
        ) as primary_media on true
        where lc.code = 'PT'
          and (q.explanation is null or q.explanation = '')
          and exists (
              select 1
              from question_media qm2
              where qm2.question_id = q.id
          )
        order by
            coalesce(primary_media.kind, 'none') asc,
            q.question_type asc,
            q.external_id asc
    """

    if psycopg is not None:
        with psycopg.connect(dsn) as conn:  # type: ignore[attr-defined]
            with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:  # type: ignore[attr-defined]
                cur.execute(sql)
                return [dict(row) for row in cur.fetchall()]

    if psycopg2 is None or RealDictCursor is None:  # pragma: no cover
        raise SystemExit("Neither psycopg nor psycopg2 is available.")

    with psycopg2.connect(dsn) as conn:  # type: ignore[call-arg]
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute(sql)
            return [dict(row) for row in cur.fetchall()]


def bucket(rows: list[dict[str, Any]], media_kind: str, question_type: str) -> list[dict[str, Any]]:
    return [
        row
        for row in rows
        if row["primary_media_kind"] == media_kind and row["question_type"] == question_type
    ]


def build_summary(rows: list[dict[str, Any]]) -> dict[str, Any]:
    media_counts = Counter(row["primary_media_kind"] for row in rows)
    type_counts = Counter(row["question_type"] for row in rows)

    return {
        "pt_media_missing_external_ids": len(rows),
        "media_counts": dict(sorted(media_counts.items())),
        "type_counts": dict(sorted(type_counts.items())),
        "image_boolean_count": len(bucket(rows, "image", "boolean")),
        "image_single_choice_count": len(bucket(rows, "image", "single_choice")),
        "video_boolean_count": len(bucket(rows, "video", "boolean")),
        "video_single_choice_count": len(bucket(rows, "video", "single_choice")),
    }


def export_group(base_name: str, rows: list[dict[str, Any]]) -> None:
    write_json(OUTPUT_DIR / f"{base_name}.json", rows)
    write_csv(OUTPUT_DIR / f"{base_name}.csv", rows)
    write_json(OUTPUT_DIR / f"{base_name}-first-25.json", rows[:25])
    write_csv(OUTPUT_DIR / f"{base_name}-first-25.csv", rows[:25])


def main() -> None:
    rows = fetch_rows()
    summary = build_summary(rows)

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    write_json(OUTPUT_DIR / "summary.json", summary)
    write_json(OUTPUT_DIR / "all.json", rows)
    write_csv(OUTPUT_DIR / "all.csv", rows)

    export_group("image-boolean", bucket(rows, "image", "boolean"))
    export_group("image-single-choice", bucket(rows, "image", "single_choice"))
    export_group("video-boolean", bucket(rows, "video", "boolean"))
    export_group("video-single-choice", bucket(rows, "video", "single_choice"))

    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

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
OUTPUT_DIR = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-only-manual-review"


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
            coalesce(
                (
                    select qm.kind
                    from question_media qm
                    where qm.question_id = q.id
                    order by qm.sort_order asc, qm.id asc
                    limit 1
                ),
                'none'
            ) as primary_media_kind
        from questions q
        join license_categories lc on lc.id = q.license_category_id
        where lc.code = 'PT'
          and (q.explanation is null or q.explanation = '')
        order by q.external_id asc
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


def build_summary(rows: list[dict[str, Any]]) -> dict[str, Any]:
    media_counts = Counter(row["primary_media_kind"] for row in rows)
    type_counts = Counter(row["question_type"] for row in rows)
    repeated_prompts = Counter(row["prompt"] for row in rows)
    repeated_prompt_rows = [
        {"prompt": prompt, "count": count}
        for prompt, count in repeated_prompts.items()
        if count > 1
    ]
    repeated_prompt_rows.sort(key=lambda item: (-item["count"], item["prompt"]))

    return {
        "pt_missing_external_ids": len(rows),
        "media_counts": dict(sorted(media_counts.items())),
        "type_counts": dict(sorted(type_counts.items())),
        "repeated_prompt_count": len(repeated_prompt_rows),
        "top_repeated_prompts": repeated_prompt_rows[:25],
        "text_single_choice_count": sum(
            1
            for row in rows
            if row["question_type"] == "single_choice" and row["primary_media_kind"] == "none"
        ),
        "text_boolean_count": sum(
            1
            for row in rows
            if row["question_type"] == "boolean" and row["primary_media_kind"] == "none"
        ),
    }


def main() -> None:
    rows = fetch_rows()
    summary = build_summary(rows)

    text_single_choice = [
        row
        for row in rows
        if row["question_type"] == "single_choice" and row["primary_media_kind"] == "none"
    ]
    text_boolean = [
        row
        for row in rows
        if row["question_type"] == "boolean" and row["primary_media_kind"] == "none"
    ]
    first_text_single_choice_batch = text_single_choice[:25]

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    write_json(OUTPUT_DIR / "summary.json", summary)
    write_json(OUTPUT_DIR / "all.json", rows)
    write_csv(OUTPUT_DIR / "all.csv", rows)
    write_json(OUTPUT_DIR / "text-single-choice.json", text_single_choice)
    write_csv(OUTPUT_DIR / "text-single-choice.csv", text_single_choice)
    write_json(OUTPUT_DIR / "text-boolean.json", text_boolean)
    write_csv(OUTPUT_DIR / "text-boolean.csv", text_boolean)
    write_json(OUTPUT_DIR / "text-single-choice-first-25.json", first_text_single_choice_batch)
    write_csv(OUTPUT_DIR / "text-single-choice-first-25.csv", first_text_single_choice_batch)

    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

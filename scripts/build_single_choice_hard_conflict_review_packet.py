from __future__ import annotations

import csv
import json
from collections import defaultdict
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
REMAINING_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "shared_review_overlap_only.json"
QUEUE_PATH = ROOT / "output" / "analysis" / "pj360-compare" / "queues" / "tier-b-review.json"
OUTPUT_DIR = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "overlap-only-review"
    / "remaining-shared-conflicts"
    / "single-choice-hard-review"
)


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


def write_markdown(path: Path, lines: list[str]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text("\n".join(lines).rstrip() + "\n", encoding="utf-8")


def get_dsn() -> str:
    summary = load_json(SUMMARY_PATH)
    return str(summary["dsn"]).replace("password=***", "password=prawkobit")


def fetch_media_rows(conflict_ids: list[str]) -> dict[str, dict[str, Any]]:
    dsn = get_dsn()

    if psycopg is not None:
        with psycopg.connect(dsn) as conn:  # type: ignore[attr-defined]
            with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:  # type: ignore[attr-defined]
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
                return {
                    str(row["external_id"]): {
                        "image_path": row["image_path"],
                        "video_path": row["video_path"],
                        "poster_path": row["poster_path"],
                        "categories": row["categories"].split(",") if row["categories"] else [],
                    }
                    for row in cur.fetchall()
                }

    if psycopg2 is None or RealDictCursor is None:  # pragma: no cover
        raise SystemExit("Neither psycopg nor psycopg2 is available.")

    conn = psycopg2.connect(dsn)  # type: ignore[call-arg]
    cur = conn.cursor(cursor_factory=RealDictCursor)
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
        str(row["external_id"]): {
            "image_path": row["image_path"],
            "video_path": row["video_path"],
            "poster_path": row["poster_path"],
            "categories": row["categories"].split(",") if row["categories"] else [],
        }
        for row in cur.fetchall()
    }
    conn.close()
    return rows


def build_packet() -> tuple[list[dict[str, Any]], list[dict[str, Any]], dict[str, Any]]:
    remaining = {str(item["external_id"]) for item in load_json(REMAINING_PATH)}
    queue = load_json(QUEUE_PATH)
    media_rows = fetch_media_rows(sorted(remaining))

    packet: list[dict[str, Any]] = []
    grouped: dict[str, list[dict[str, Any]]] = defaultdict(list)

    for item in queue:
        local = item["local"]
        gov_id = str(local["gov_id"])
        if gov_id not in remaining or local["question_type"] != "single_choice":
            continue

        external = item.get("external") or (item.get("external_candidates") or [None])[0]
        if external is None:
            continue

        if str(local["accepted_answer"]).strip().lower() == str(external.get("accepted_answer") or "").strip().lower():
            continue

        media = media_rows.get(gov_id, {})
        row = {
            "gov_id": gov_id,
            "prompt": local["prompt"],
            "categories": local["categories"],
            "structure_scope": local["structure_scope"],
            "local_answer": local["accepted_answer"],
            "local_media_kind": local["question_media_kind"],
            "local_option_a": local.get("option_a"),
            "local_option_b": local.get("option_b"),
            "local_option_c": local.get("option_c"),
            "local_image_path": media.get("image_path"),
            "local_video_path": media.get("video_path"),
            "local_poster_path": media.get("poster_path"),
            "external_answer": external.get("accepted_answer"),
            "external_media_kind": external.get("question_media_kind"),
            "external_categories": external.get("categories") or [],
            "external_url": external.get("url"),
            "external_explanation": external.get("explanation_text"),
            "reasons": item.get("reasons") or [],
        }
        packet.append(row)
        grouped[row["prompt"]].append(row)

    packet.sort(key=lambda entry: (entry["prompt"], int(entry["gov_id"])))

    families: list[dict[str, Any]] = []
    for prompt, rows in sorted(grouped.items(), key=lambda pair: (-len(pair[1]), pair[0])):
        families.append(
            {
                "prompt": prompt,
                "family_size": len(rows),
                "gov_ids": [row["gov_id"] for row in rows],
                "rows": rows,
            }
        )

    summary = {
        "remaining_hard_conflict_external_ids": len(packet),
        "prompt_family_count": len(families),
        "largest_families": [
            {"prompt": family["prompt"], "family_size": family["family_size"], "gov_ids": family["gov_ids"]}
            for family in families[:10]
        ],
    }

    return packet, families, summary


def build_markdown(families: list[dict[str, Any]], summary: dict[str, Any]) -> list[str]:
    lines = [
        "# Single Choice Hard Conflict Review",
        "",
        f"- Remaining hard conflict external IDs: {summary['remaining_hard_conflict_external_ids']}",
        f"- Prompt families: {summary['prompt_family_count']}",
        "",
    ]

    for family in families:
        lines.append(f"## {family['prompt']}")
        lines.append("")
        lines.append(f"- Family size: {family['family_size']}")
        lines.append(f"- External IDs: {', '.join(family['gov_ids'])}")
        lines.append("")

        for row in family["rows"]:
            lines.append(f"### GOV {row['gov_id']} ({', '.join(row['categories'])})")
            lines.append(f"- Scope: {row['structure_scope']}")
            lines.append(f"- Local answer: {row['local_answer']}")
            lines.append(f"- External answer: {row['external_answer']}")
            lines.append(f"- Local media: {row['local_media_kind']}")
            lines.append(f"- External media: {row['external_media_kind']}")
            lines.append(f"- Option A: {row['local_option_a']}")
            lines.append(f"- Option B: {row['local_option_b']}")
            lines.append(f"- Option C: {row['local_option_c']}")
            lines.append(f"- Local image path: {row['local_image_path']}")
            lines.append(f"- Local video path: {row['local_video_path']}")
            lines.append(f"- Local poster path: {row['local_poster_path']}")
            lines.append(f"- External URL: {row['external_url']}")
            lines.append(f"- External explanation: {row['external_explanation']}")
            lines.append("")

    return lines


def main() -> None:
    packet, families, summary = build_packet()
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    csv_rows: list[dict[str, Any]] = []
    for family in families:
        for row in family["rows"]:
            csv_rows.append(
                {
                    "prompt": family["prompt"],
                    "family_size": family["family_size"],
                    "gov_id": row["gov_id"],
                    "categories": ",".join(row["categories"]),
                    "structure_scope": row["structure_scope"],
                    "local_answer": row["local_answer"],
                    "external_answer": row["external_answer"],
                    "local_media_kind": row["local_media_kind"],
                    "external_media_kind": row["external_media_kind"],
                    "local_option_a": row["local_option_a"],
                    "local_option_b": row["local_option_b"],
                    "local_option_c": row["local_option_c"],
                    "local_image_path": row["local_image_path"],
                    "local_video_path": row["local_video_path"],
                    "local_poster_path": row["local_poster_path"],
                    "external_url": row["external_url"],
                    "external_explanation": row["external_explanation"],
                }
            )

    write_json(OUTPUT_DIR / "summary.json", summary)
    write_json(OUTPUT_DIR / "packet.json", packet)
    write_json(OUTPUT_DIR / "prompt-families.json", families)
    write_csv(OUTPUT_DIR / "packet.csv", csv_rows)
    write_markdown(OUTPUT_DIR / "prompt-families.md", build_markdown(families, summary))

    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

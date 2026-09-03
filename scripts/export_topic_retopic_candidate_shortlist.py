from __future__ import annotations

import argparse
import json
from collections import Counter
from pathlib import Path
from typing import Any

import psycopg


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_DIFF_PATH = ROOT / "output" / "analysis" / "pj360-exact-topic-membership" / "exact-vs-local-topic-membership-diff.json"
DEFAULT_OUTPUT_DIR = ROOT / "output" / "analysis" / "pj360-exact-topic-membership"
DB_DSN = "host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit"


def load_json(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def categories_lookup(payload: dict[str, Any]) -> dict[str, dict[str, Any]]:
    return {
        str(category["category_code"]).upper(): category
        for category in payload.get("categories", [])
    }


def fetch_active_overrides(category_code: str) -> dict[tuple[str, str, str], str]:
    query = """
        SELECT license_category_code, LOWER(source) AS source, external_id, question_topic_key
        FROM question_topic_overrides
        WHERE is_active = TRUE
          AND license_category_code = %s
    """

    with psycopg.connect(DB_DSN) as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(query, (category_code,))
            rows = cur.fetchall()

    return {
        (
            str(row["license_category_code"]).upper(),
            str(row["source"]).strip().lower(),
            str(row["external_id"]).strip(),
        ): str(row["question_topic_key"] or "").strip()
        for row in rows
    }


def build_shortlist(category_payload: dict[str, Any]) -> dict[str, Any]:
    category_code = str(category_payload["category_code"]).upper()
    active_overrides = fetch_active_overrides(category_code)
    candidates: dict[tuple[int, str], dict[str, Any]] = {}
    target_topic_counter = Counter()
    source_topic_counter = Counter()
    skipped_existing_override_same_target = Counter()
    skipped_existing_override_conflict = Counter()

    for topic in category_payload.get("topics", []):
        target_topic_key = str(topic["topic_key"])
        target_topic_label = str(topic["topic_label"])

        for example in topic.get("wrong_topic_examples", []):
            for candidate in example.get("candidate_questions", []):
                source_topic_key = str(candidate.get("topic_key") or "")
                question_id = int(candidate["question_id"])
                if source_topic_key == target_topic_key:
                    continue

                stable_key = (
                    category_code,
                    str(candidate.get("source") or "").strip().lower(),
                    str(candidate.get("external_id") or "").strip(),
                )

                existing_override_topic_key = active_overrides.get(stable_key)
                if existing_override_topic_key is not None:
                    if existing_override_topic_key == target_topic_key:
                        skipped_existing_override_same_target[target_topic_key] += 1
                    else:
                        skipped_existing_override_conflict[f"{existing_override_topic_key} -> {target_topic_key}"] += 1
                    continue

                unique_key = (question_id, target_topic_key)
                if unique_key not in candidates:
                    candidates[unique_key] = {
                        "question_id": question_id,
                        "external_id": candidate.get("external_id"),
                        "source": candidate.get("source"),
                        "current_topic_key": source_topic_key,
                        "target_topic_key": target_topic_key,
                        "target_topic_label": target_topic_label,
                        "main_media_original": candidate.get("main_media_original"),
                        "prompt": example.get("prompt"),
                        "accepted_answer": example.get("accepted_answer"),
                        "question_media_kind": example.get("question_media_kind"),
                    }
                    target_topic_counter[target_topic_key] += 1
                    source_topic_counter[source_topic_key] += 1

    rows = sorted(
        candidates.values(),
        key=lambda row: (
            row["target_topic_key"],
            row["current_topic_key"],
            int(row["question_id"]),
        ),
    )

    return {
        "category_code": category_code,
        "summary": {
            "retopic_candidate_count": len(rows),
            "target_topic_counts": dict(target_topic_counter.most_common()),
            "source_topic_counts": dict(source_topic_counter.most_common()),
            "skipped_existing_override_same_target": dict(skipped_existing_override_same_target.most_common()),
            "skipped_existing_override_conflict": dict(skipped_existing_override_conflict.most_common()),
        },
        "records": rows,
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Export retopic candidate shortlist from exact-vs-local membership diff.")
    parser.add_argument("--categories", default="B", help="Lista kategorii oddzielona przecinkami.")
    parser.add_argument("--diff-json", default=str(DEFAULT_DIFF_PATH), help="Sciezka do pliku exact-vs-local diff.")
    parser.add_argument("--output-dir", default=str(DEFAULT_OUTPUT_DIR), help="Katalog docelowy.")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    requested_categories = [item.strip().upper() for item in args.categories.split(",") if item.strip()]
    payload = load_json(Path(args.diff_json))
    lookup = categories_lookup(payload)

    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    summaries = {}
    for category_code in requested_categories:
        shortlist = build_shortlist(lookup[category_code])
        output_path = output_dir / f"{category_code.lower()}-retopic-candidate-shortlist.json"
        output_path.write_text(json.dumps(shortlist, ensure_ascii=False, indent=2), encoding="utf-8")
        summaries[category_code] = shortlist["summary"]

    print(json.dumps(summaries, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

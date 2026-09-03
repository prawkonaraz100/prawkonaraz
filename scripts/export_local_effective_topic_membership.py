from __future__ import annotations

import argparse
import json
import re
import unicodedata
from collections import defaultdict
from pathlib import Path
from typing import Any

import psycopg


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_OUTPUT_DIR = ROOT / "output" / "analysis" / "pj360-exact-topic-membership"
DB_DSN = "host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit"
COMPARABLE_CATEGORY_CODES = ["A", "AM", "A1", "A2", "B", "B1", "C", "C1", "D", "D1", "T"]


def normalize_text(value: str) -> str:
    value = value or ""
    value = unicodedata.normalize("NFKC", value)
    value = value.replace("\xa0", " ")
    value = re.sub(r"\s+", " ", value)
    return value.strip()


def metadata_dict(raw_metadata: Any) -> dict[str, Any]:
    if isinstance(raw_metadata, dict):
        return raw_metadata

    if isinstance(raw_metadata, str) and raw_metadata.strip() != "":
        try:
            parsed = json.loads(raw_metadata)
            return parsed if isinstance(parsed, dict) else {}
        except json.JSONDecodeError:
            return {}

    return {}


def answer_text_from_row(row: dict[str, Any]) -> str:
    question_type = row["question_type"]
    correct_answer = (row["correct_answer"] or "").lower()

    if question_type == "boolean":
        return "tak" if correct_answer == "a" else "nie"

    if correct_answer == "a":
        return normalize_text(row["option_a"] or "")
    if correct_answer == "b":
        return normalize_text(row["option_b"] or "")
    if correct_answer == "c":
        return normalize_text(row["option_c"] or "")

    return ""


def bucket_label(sort_order: int) -> str:
    return "Pytania specjalistyczne" if sort_order >= 210 else "Pytania podstawowe"


def fetch_topic_lookup() -> dict[int, dict[str, Any]]:
    query = """
        SELECT id, key, name, sort_order
        FROM question_topics
        WHERE is_active = TRUE
        ORDER BY sort_order, name
    """

    with psycopg.connect(DB_DSN) as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(query)
            rows = cur.fetchall()

    return {
        int(row["id"]): {
            "topic_key": str(row["key"]),
            "topic_label": str(row["name"]),
            "sort_order": int(row["sort_order"]),
            "bucket": bucket_label(int(row["sort_order"])),
        }
        for row in rows
    }


def fetch_local_questions(categories: list[str]) -> list[dict[str, Any]]:
    query = """
        SELECT
            lc.code AS category_code,
            q.id AS question_id,
            LOWER(q.source) AS source,
            q.external_id,
            q.prompt,
            q.question_type,
            q.correct_answer,
            q.option_a,
            q.option_b,
            q.option_c,
            q.metadata,
            q.question_topic_id AS classifier_topic_id,
            qto.question_topic_key AS override_topic_key,
            qto_topic.id AS override_topic_id,
            COALESCE(qto_topic.id, q.question_topic_id) AS effective_topic_id,
            EXISTS (
                SELECT 1
                FROM question_media qm
                WHERE qm.question_id = q.id
                  AND qm.kind = 'video'
            ) AS has_video,
            EXISTS (
                SELECT 1
                FROM question_media qm
                WHERE qm.question_id = q.id
                  AND qm.kind = 'image'
            ) AS has_image
        FROM questions q
        JOIN license_categories lc ON lc.id = q.license_category_id
        LEFT JOIN question_topic_overrides qto
            ON qto.is_active = TRUE
           AND qto.license_category_code = lc.code
           AND LOWER(qto.source) = LOWER(q.source)
           AND qto.external_id = q.external_id
        LEFT JOIN question_topics qto_topic
            ON qto_topic.is_active = TRUE
           AND qto_topic.key = qto.question_topic_key
        WHERE lc.code = ANY(%s)
          AND q.is_active = TRUE
          AND q.delivery_issue IS NULL
          AND q.question_topic_id IS NOT NULL
        ORDER BY lc.code, effective_topic_id, q.id
    """

    with psycopg.connect(DB_DSN) as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(query, (categories,))
            return cur.fetchall()


def build_payload(categories: list[str]) -> dict[str, Any]:
    topic_lookup = fetch_topic_lookup()
    rows = fetch_local_questions(categories)

    categories_payload: list[dict[str, Any]] = []
    grouped_by_category: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for row in rows:
        grouped_by_category[str(row["category_code"])].append(row)

    for category_code in categories:
        category_rows = grouped_by_category.get(category_code, [])
        topics_map: dict[int, dict[str, Any]] = {}

        for row in category_rows:
            topic_id = int(row["effective_topic_id"])
            topic_meta = topic_lookup[topic_id]
            metadata = metadata_dict(row.get("metadata"))

            if topic_id not in topics_map:
                topics_map[topic_id] = {
                    "topic_key": topic_meta["topic_key"],
                    "topic_label": topic_meta["topic_label"],
                    "bucket": topic_meta["bucket"],
                    "sort_order": topic_meta["sort_order"],
                    "questions": [],
                }

            media_kind = "none"
            if row["has_video"]:
                media_kind = "video"
            elif row["has_image"]:
                media_kind = "image"

            topics_map[topic_id]["questions"].append(
                {
                    "question_id": int(row["question_id"]),
                    "source": str(row["source"] or ""),
                    "external_id": str(row["external_id"] or ""),
                    "prompt": normalize_text(row["prompt"] or ""),
                    "accepted_answer": answer_text_from_row(row),
                    "question_media_kind": media_kind,
                    "main_media_original": normalize_text(str(metadata.get("main_media_original") or "")),
                }
            )

        topics = sorted(
            topics_map.values(),
            key=lambda topic: (int(topic["sort_order"]), str(topic["topic_label"])),
        )
        for topic in topics:
            topic["questions_count"] = len(topic["questions"])
            topic.pop("sort_order", None)

        categories_payload.append(
            {
                "category_code": category_code,
                "total_questions": len(category_rows),
                "topics_count": len(topics),
                "topics": topics,
            }
        )

    return {
        "generated_for_categories": categories,
        "categories": categories_payload,
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Export local effective topic membership in the same shape as PJ360 exact membership.")
    parser.add_argument("--categories", default=",".join(COMPARABLE_CATEGORY_CODES), help="Lista kategorii oddzielona przecinkami.")
    parser.add_argument("--output-dir", default=str(DEFAULT_OUTPUT_DIR), help="Katalog docelowy.")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    categories = [item.strip().upper() for item in args.categories.split(",") if item.strip()]
    payload = build_payload(categories)

    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    all_path = output_dir / "local-effective-topic-membership-all.json"
    all_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    for category in payload["categories"]:
        category_path = output_dir / f"local-effective-topic-membership-{str(category['category_code']).upper()}.json"
        category_path.write_text(json.dumps(category, ensure_ascii=False, indent=2), encoding="utf-8")

    quick_summary = {
        category["category_code"]: {
            "total_questions": category["total_questions"],
            "topics_count": category["topics_count"],
            "largest_topics": [
                {
                    "topic_key": topic["topic_key"],
                    "questions_count": topic["questions_count"],
                }
                for topic in sorted(category["topics"], key=lambda item: item["questions_count"], reverse=True)[:5]
            ],
        }
        for category in payload["categories"]
    }

    print(json.dumps(quick_summary, ensure_ascii=False, indent=2))
    print(f"Zapisano lokalny membership do: {all_path}")


if __name__ == "__main__":
    main()

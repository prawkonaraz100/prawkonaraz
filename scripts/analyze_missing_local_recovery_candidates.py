from __future__ import annotations

import argparse
import json
import re
import unicodedata
from collections import Counter, defaultdict
from pathlib import Path
from typing import Any

import psycopg


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_INPUT_DIR = ROOT / "output" / "analysis" / "pj360-exact-topic-membership"
DEFAULT_INVENTORY_PATH = DEFAULT_INPUT_DIR / "b-missing-local-inventory.json"
DEFAULT_OUTPUT_PATH = DEFAULT_INPUT_DIR / "b-missing-local-recovery-candidates.json"
DB_DSN = "host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit"
POLISH_ASCII_TRANSLATION = str.maketrans(
    {
        "ą": "a",
        "ć": "c",
        "ę": "e",
        "ł": "l",
        "ń": "n",
        "ó": "o",
        "ś": "s",
        "ź": "z",
        "ż": "z",
        "Ą": "A",
        "Ć": "C",
        "Ę": "E",
        "Ł": "L",
        "Ń": "N",
        "Ó": "O",
        "Ś": "S",
        "Ź": "Z",
        "Ż": "Z",
    }
)


def normalize_text(value: str | None) -> str:
    value = value or ""
    value = unicodedata.normalize("NFKC", value)
    value = value.replace("\xa0", " ")
    value = re.sub(r"\s+", " ", value)
    return value.strip()


def normalize_key(value: str | None) -> str:
    value = normalize_text(value).lower()
    value = value.translate(POLISH_ASCII_TRANSLATION)
    value = unicodedata.normalize("NFKD", value)
    value = "".join(ch for ch in value if not unicodedata.combining(ch))
    value = re.sub(r"[^a-z0-9]+", " ", value)
    return re.sub(r"\s+", " ", value).strip()


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


def signature(prompt: str | None, accepted_answer: str | None, question_media_kind: str | None) -> tuple[str, str, str]:
    return (
        normalize_key(prompt),
        normalize_key(accepted_answer),
        normalize_text(question_media_kind or "none"),
    )


def fetch_all_local_questions() -> dict[tuple[str, str, str], list[dict[str, Any]]]:
    query = """
        SELECT
            q.id AS question_id,
            LOWER(q.source) AS source,
            q.external_id,
            q.prompt,
            q.question_type,
            q.correct_answer,
            q.option_a,
            q.option_b,
            q.option_c,
            q.is_active,
            q.delivery_issue,
            lc.code AS license_category_code,
            COALESCE(qt.key, 'unassigned') AS current_topic_key,
            qm_image.path AS image_path,
            qm_video.path AS video_path,
            EXISTS (
                SELECT 1
                FROM question_media qmi
                WHERE qmi.question_id = q.id
                  AND qmi.kind = 'video'
            ) AS has_video,
            EXISTS (
                SELECT 1
                FROM question_media qmi
                WHERE qmi.question_id = q.id
                  AND qmi.kind = 'image'
            ) AS has_image
        FROM questions q
        JOIN license_categories lc ON lc.id = q.license_category_id
        LEFT JOIN question_topics qt ON qt.id = q.question_topic_id
        LEFT JOIN LATERAL (
            SELECT path
            FROM question_media
            WHERE question_id = q.id AND kind = 'image'
            ORDER BY id ASC
            LIMIT 1
        ) qm_image ON TRUE
        LEFT JOIN LATERAL (
            SELECT path
            FROM question_media
            WHERE question_id = q.id AND kind = 'video'
            ORDER BY id ASC
            LIMIT 1
        ) qm_video ON TRUE
    """

    with psycopg.connect(DB_DSN) as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(query)
            rows = cur.fetchall()

    by_signature: dict[tuple[str, str, str], list[dict[str, Any]]] = defaultdict(list)
    for row in rows:
        media_kind = "none"
        if row["has_video"]:
            media_kind = "video"
        elif row["has_image"]:
            media_kind = "image"

        record = {
            "question_id": int(row["question_id"]),
            "license_category_code": str(row["license_category_code"] or ""),
            "source": str(row["source"] or ""),
            "external_id": str(row["external_id"] or ""),
            "current_topic_key": str(row["current_topic_key"] or "unassigned"),
            "is_active": bool(row["is_active"]),
            "delivery_issue": row["delivery_issue"],
            "question_media_kind": media_kind,
            "accepted_answer": answer_text_from_row(row),
            "image_path": row["image_path"],
            "video_path": row["video_path"],
        }
        by_signature[signature(row["prompt"], record["accepted_answer"], media_kind)].append(record)

    return by_signature


def classify_candidates(category_code: str, candidates: list[dict[str, Any]]) -> tuple[str, dict[str, int]]:
    counters = {
        "same_category_active_ready": 0,
        "same_category_active_blocked": 0,
        "same_category_inactive": 0,
        "other_category_active_ready": 0,
        "other_category_active_blocked": 0,
        "other_category_inactive": 0,
    }

    for candidate in candidates:
        same_category = candidate["license_category_code"] == category_code
        active_ready = candidate["is_active"] and not candidate["delivery_issue"]
        active_blocked = candidate["is_active"] and bool(candidate["delivery_issue"])
        inactive = not candidate["is_active"]

        if same_category and active_ready:
            counters["same_category_active_ready"] += 1
        elif same_category and active_blocked:
            counters["same_category_active_blocked"] += 1
        elif same_category and inactive:
            counters["same_category_inactive"] += 1
        elif (not same_category) and active_ready:
            counters["other_category_active_ready"] += 1
        elif (not same_category) and active_blocked:
            counters["other_category_active_blocked"] += 1
        elif (not same_category) and inactive:
            counters["other_category_inactive"] += 1

    if counters["same_category_active_ready"] > 0:
        return "already_recoverable_same_category", counters
    if counters["same_category_active_blocked"] > 0 or counters["same_category_inactive"] > 0:
        return "recoverable_same_category_non_ready", counters
    if counters["other_category_active_ready"] > 0:
        return "recoverable_from_other_category", counters
    if counters["other_category_active_blocked"] > 0 or counters["other_category_inactive"] > 0:
        return "recoverable_from_other_category_non_ready", counters
    return "truly_missing_local", counters


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Analyze if missing-local questions can be recovered from local DB.")
    parser.add_argument("--inventory-json", default=str(DEFAULT_INVENTORY_PATH), help="Sciezka do pliku missing-local inventory.")
    parser.add_argument("--output", default=str(DEFAULT_OUTPUT_PATH), help="Sciezka do pliku wynikowego JSON.")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    inventory = json.loads(Path(args.inventory_json).read_text(encoding="utf-8"))
    category_code = str(inventory["category_code"]).upper()
    local_by_signature = fetch_all_local_questions()

    topic_summary: list[dict[str, Any]] = []
    status_counter: Counter[str] = Counter()
    records: list[dict[str, Any]] = []

    for topic in inventory.get("topics", []):
        per_topic_counter: Counter[str] = Counter()

        for question in topic.get("questions", []):
            sig = signature(
                question.get("prompt"),
                question.get("accepted_answer"),
                question.get("question_media_kind"),
            )
            candidates = local_by_signature.get(sig, [])
            recovery_status, counters = classify_candidates(category_code, candidates)
            status_counter[recovery_status] += 1
            per_topic_counter[recovery_status] += 1

            records.append(
                {
                    "topic_key": topic["topic_key"],
                    "topic_label": topic["topic_label"],
                    "internal_question_id": question.get("internal_question_id"),
                    "page": question.get("page"),
                    "position_on_page": question.get("position_on_page"),
                    "prompt": question.get("prompt"),
                    "accepted_answer": question.get("accepted_answer"),
                    "question_media_kind": question.get("question_media_kind"),
                    "question_media_url": question.get("question_media_url"),
                    "recovery_status": recovery_status,
                    "candidate_counters": counters,
                    "candidate_questions": candidates[:12],
                }
            )

        topic_summary.append(
            {
                "topic_key": topic["topic_key"],
                "topic_label": topic["topic_label"],
                "missing_local_count": topic["missing_local_count"],
                "recovery_status_counts": dict(per_topic_counter),
            }
        )

    topic_summary.sort(key=lambda row: row["missing_local_count"], reverse=True)
    records.sort(key=lambda row: (row["recovery_status"], row["topic_key"], row["internal_question_id"]))

    payload = {
        "category_code": category_code,
        "summary": {
            "missing_local_total": inventory["summary"]["missing_local_total"],
            "recovery_status_counts": dict(status_counter),
        },
        "topics": topic_summary,
        "records": records,
    }

    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    records_by_status: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for record in records:
        records_by_status[record["recovery_status"]].append(record)

    for status, status_records in records_by_status.items():
        status_path = output_path.parent / f"{category_code.lower()}-missing-local-{status}.json"
        status_path.write_text(
            json.dumps(
                {
                    "category_code": category_code,
                    "recovery_status": status,
                    "count": len(status_records),
                    "records": status_records,
                },
                ensure_ascii=False,
                indent=2,
            ),
            encoding="utf-8",
        )

    print(json.dumps(payload["summary"], ensure_ascii=False, indent=2))
    print(f"Zapisano raport recovery do: {output_path}")


if __name__ == "__main__":
    main()

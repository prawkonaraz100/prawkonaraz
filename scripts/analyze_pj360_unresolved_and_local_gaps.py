from __future__ import annotations

import argparse
import json
import re
import unicodedata
from collections import Counter, defaultdict
from dataclasses import dataclass
from pathlib import Path
from typing import Any

import psycopg


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_MEMBERSHIP_PATH = ROOT / "output" / "analysis" / "pj360-exact-topic-membership" / "exact-topic-membership-all.json"
DEFAULT_PACKAGE_DIR = ROOT / "resources" / "topic-overrides"
DEFAULT_SUMMARY_PATH = ROOT / "output" / "analysis" / "pj360-exact-topic-membership" / "unresolved-local-gaps-summary.json"
DEFAULT_MISSING_PATH = ROOT / "output" / "analysis" / "pj360-exact-topic-membership" / "missing-locally-by-category-topic.json"
DEFAULT_AMBIGUOUS_PATH = ROOT / "output" / "analysis" / "pj360-exact-topic-membership" / "ambiguous-local-match-by-category-topic.json"
COMPARABLE_CATEGORY_CODES = ["A", "AM", "A1", "A2", "B", "B1", "C", "C1", "D", "D1", "T"]
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


@dataclass
class LocalQuestion:
    question_id: int
    source: str
    external_id: str
    prompt: str
    accepted_answer: str
    question_media_kind: str
    current_topic_key: str


def normalize_text(value: str) -> str:
    value = value or ""
    value = unicodedata.normalize("NFKC", value)
    value = value.replace("\xa0", " ")
    value = re.sub(r"\s+", " ", value)
    return value.strip()


def normalize_key(value: str) -> str:
    value = normalize_text(value).lower()
    value = value.translate(POLISH_ASCII_TRANSLATION)
    value = unicodedata.normalize("NFKD", value)
    value = "".join(ch for ch in value if not unicodedata.combining(ch))
    value = re.sub(r"[^a-z0-9]+", " ", value)
    return re.sub(r"\s+", " ", value).strip()


def load_membership_payload(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def membership_signature(question: dict[str, Any]) -> tuple[str, str, str]:
    return (
        normalize_key(question.get("prompt") or ""),
        normalize_key(question.get("accepted_answer") or ""),
        normalize_text(question.get("question_media_kind") or "none"),
    )


def local_signature(question: LocalQuestion) -> tuple[str, str, str]:
    return (
        normalize_key(question.prompt),
        normalize_key(question.accepted_answer),
        normalize_text(question.question_media_kind or "none"),
    )


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


def fetch_local_questions(category_code: str) -> list[LocalQuestion]:
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
            COALESCE(qt.key, 'unassigned') AS current_topic_key,
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
        LEFT JOIN question_topics qt ON qt.id = q.question_topic_id
        WHERE lc.code = %s
          AND q.is_active = TRUE
          AND q.delivery_issue IS NULL
        ORDER BY q.id
    """

    with psycopg.connect(DB_DSN) as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(query, (category_code,))
            rows = cur.fetchall()

    questions: list[LocalQuestion] = []
    for row in rows:
        media_kind = "none"
        if row["has_video"]:
            media_kind = "video"
        elif row["has_image"]:
            media_kind = "image"

        questions.append(
            LocalQuestion(
                question_id=int(row["question_id"]),
                source=str(row["source"] or ""),
                external_id=str(row["external_id"] or ""),
                prompt=normalize_text(row["prompt"] or ""),
                accepted_answer=answer_text_from_row(row),
                question_media_kind=media_kind,
                current_topic_key=str(row["current_topic_key"] or "unassigned"),
            )
        )

    return questions


def load_package(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def package_path(package_dir: Path, category_code: str) -> Path:
    return package_dir / f"pj360-{category_code.lower()}-exact-topic-membership-package.json"


def summarize_category(
    *,
    category_code: str,
    membership_category: dict[str, Any],
    package_payload: dict[str, Any],
) -> dict[str, Any]:
    local_questions = fetch_local_questions(category_code)

    membership_rows: list[dict[str, Any]] = []
    target_counts: dict[str, int] = {}
    for topic in membership_category.get("topics", []):
        topic_key = str(topic["topic_key"])
        questions = topic.get("questions", [])
        target_counts[topic_key] = len(questions)
        for question in questions:
            row = dict(question)
            row["target_topic_key"] = topic_key
            row["target_topic_label"] = topic.get("topic_label")
            membership_rows.append(row)

    membership_by_signature: dict[tuple[str, str, str], list[dict[str, Any]]] = defaultdict(list)
    for row in membership_rows:
        membership_by_signature[membership_signature(row)].append(row)

    local_by_signature: dict[tuple[str, str, str], list[LocalQuestion]] = defaultdict(list)
    for row in local_questions:
        local_by_signature[local_signature(row)].append(row)

    missing_local_by_topic: Counter[str] = Counter()
    missing_local_examples: list[dict[str, Any]] = []
    local_only_by_topic: Counter[str] = Counter()
    local_only_examples: list[dict[str, Any]] = []

    signature_keys = set(membership_by_signature) | set(local_by_signature)
    for signature_key in signature_keys:
        membership_group = membership_by_signature.get(signature_key, [])
        local_group = sorted(local_by_signature.get(signature_key, []), key=lambda item: item.question_id)
        membership_count = len(membership_group)
        local_count = len(local_group)

        if membership_count > local_count:
            missing_rows = membership_group[local_count:]
            for row in missing_rows:
                missing_local_by_topic[str(row["target_topic_key"])] += 1
            if len(missing_local_examples) < 40 and missing_rows:
                first = missing_rows[0]
                missing_local_examples.append(
                    {
                        "topic_key": first["target_topic_key"],
                        "prompt": first["prompt"],
                        "accepted_answer": first.get("accepted_answer"),
                        "question_media_kind": first.get("question_media_kind"),
                        "missing_count_for_signature": membership_count - local_count,
                    }
                )

        if local_count > membership_count:
            extra_rows = local_group[membership_count:]
            for row in extra_rows:
                local_only_by_topic[row.current_topic_key] += 1
            if len(local_only_examples) < 40 and extra_rows:
                first = extra_rows[0]
                local_only_examples.append(
                    {
                        "current_topic_key": first.current_topic_key,
                        "prompt": first.prompt,
                        "accepted_answer": first.accepted_answer,
                        "question_media_kind": first.question_media_kind,
                        "extra_count_for_signature": local_count - membership_count,
                        "sample_question_ids": [row.question_id for row in extra_rows[:5]],
                        "sample_external_ids": [row.external_id for row in extra_rows[:5]],
                    }
                )

    unresolved = package_payload.get("unresolved", [])
    unresolved_by_reason: Counter[str] = Counter()
    unresolved_by_topic: Counter[str] = Counter()
    ambiguous_by_topic: Counter[str] = Counter()
    unresolved_examples: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for row in unresolved:
        reason = str(row.get("reason") or "unknown")
        membership_question = row.get("membership_question") or {}
        topic_key = str(membership_question.get("target_topic_key") or "unknown")
        unresolved_by_reason[reason] += 1
        unresolved_by_topic[topic_key] += 1
        if reason == "ambiguous_local_match":
            ambiguous_by_topic[topic_key] += 1
        if len(unresolved_examples[reason]) < 15:
            unresolved_examples[reason].append(
                {
                    "topic_key": topic_key,
                    "prompt": membership_question.get("prompt"),
                    "accepted_answer": membership_question.get("accepted_answer"),
                    "question_media_kind": membership_question.get("question_media_kind"),
                    "candidate_question_ids": row.get("candidate_question_ids", []),
                }
            )

    actual_counts = Counter(row.current_topic_key for row in local_questions)
    delta_rows = []
    for topic_key, target in target_counts.items():
        actual = int(actual_counts.get(topic_key, 0))
        delta_rows.append(
            {
                "topic_key": topic_key,
                "actual": actual,
                "target": target,
                "delta": actual - target,
                "missing_local_count": int(missing_local_by_topic.get(topic_key, 0)),
                "local_only_count": int(local_only_by_topic.get(topic_key, 0)),
                "unresolved_count": int(unresolved_by_topic.get(topic_key, 0)),
            }
        )
    delta_rows.sort(key=lambda row: abs(row["delta"]), reverse=True)
    topic_index = {row["topic_key"]: row for row in delta_rows}

    return {
        "category_code": category_code,
        "membership_questions_count": len(membership_rows),
        "local_questions_count": len(local_questions),
        "total_delta": len(local_questions) - len(membership_rows),
        "unresolved_count": len(unresolved),
        "unresolved_by_reason": dict(unresolved_by_reason.most_common()),
        "ambiguous_by_topic": dict(ambiguous_by_topic.most_common()),
        "topic_index": topic_index,
        "largest_topic_gaps": delta_rows[:12],
        "missing_local_by_topic": dict(missing_local_by_topic.most_common()),
        "local_only_by_topic": dict(local_only_by_topic.most_common()),
        "unresolved_examples": dict(unresolved_examples),
        "missing_local_examples": missing_local_examples,
        "local_only_examples": local_only_examples,
    }


def build_missing_local_report(summary: dict[str, Any]) -> dict[str, Any]:
    categories_payload: dict[str, Any] = {}

    for code, category in summary["categories"].items():
        topic_rows = []
        for topic_key, missing_count in category["missing_local_by_topic"].items():
            gap_row = category["topic_index"].get(topic_key)
            topic_rows.append(
                {
                    "topic_key": topic_key,
                    "missing_local_count": missing_count,
                    "actual": gap_row["actual"] if gap_row else None,
                    "target": gap_row["target"] if gap_row else None,
                    "delta": gap_row["delta"] if gap_row else None,
                    "unresolved_count": gap_row["unresolved_count"] if gap_row else None,
                }
            )

        missing_examples_by_topic: dict[str, list[dict[str, Any]]] = defaultdict(list)
        for example in category["missing_local_examples"]:
            missing_examples_by_topic[str(example["topic_key"])].append(example)

        topic_rows.sort(key=lambda row: row["missing_local_count"], reverse=True)

        categories_payload[code] = {
            "total_delta": category["total_delta"],
            "topics": topic_rows,
            "examples_by_topic": dict(missing_examples_by_topic),
        }

    return {
        "generated_for_categories": summary["generated_for_categories"],
        "categories": categories_payload,
    }


def build_ambiguous_report(summary: dict[str, Any]) -> dict[str, Any]:
    categories_payload: dict[str, Any] = {}

    for code, category in summary["categories"].items():
        ambiguous_examples = category["unresolved_examples"].get("ambiguous_local_match", [])

        categories_payload[code] = {
            "unresolved_by_reason": category["unresolved_by_reason"],
            "ambiguous_count": category["unresolved_by_reason"].get("ambiguous_local_match", 0),
            "ambiguous_by_topic": category["ambiguous_by_topic"],
            "ambiguous_examples": ambiguous_examples,
        }

    return {
        "generated_for_categories": summary["generated_for_categories"],
        "categories": categories_payload,
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Analyze unresolved and local-only gaps after PJ360 exact sync.")
    parser.add_argument("--membership-json", default=str(DEFAULT_MEMBERSHIP_PATH), help="Sciezka do pliku exact-membership JSON.")
    parser.add_argument("--package-dir", default=str(DEFAULT_PACKAGE_DIR), help="Katalog z finalnymi exact package per kategoria.")
    parser.add_argument("--categories", default=",".join(COMPARABLE_CATEGORY_CODES), help="Lista kategorii oddzielona przecinkami.")
    parser.add_argument("--output", default=str(DEFAULT_SUMMARY_PATH), help="Sciezka do pliku wynikowego JSON.")
    parser.add_argument("--missing-output", default=str(DEFAULT_MISSING_PATH), help="Sciezka do pliku `missing locally` JSON.")
    parser.add_argument("--ambiguous-output", default=str(DEFAULT_AMBIGUOUS_PATH), help="Sciezka do pliku `ambiguous_local_match` JSON.")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    membership_payload = load_membership_payload(Path(args.membership_json))
    requested_categories = [item.strip().upper() for item in args.categories.split(",") if item.strip()]

    categories_lookup = {
        str(category["category_code"]).upper(): category
        for category in membership_payload.get("categories", [])
    }

    summary: dict[str, Any] = {
        "generated_for_categories": requested_categories,
        "categories": {},
    }

    for category_code in requested_categories:
        membership_category = categories_lookup[category_code]
        payload = load_package(package_path(Path(args.package_dir), category_code))
        summary["categories"][category_code] = summarize_category(
            category_code=category_code,
            membership_category=membership_category,
            package_payload=payload,
        )

    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(summary, ensure_ascii=False, indent=2), encoding="utf-8")

    missing_payload = build_missing_local_report(summary)
    missing_output_path = Path(args.missing_output)
    missing_output_path.parent.mkdir(parents=True, exist_ok=True)
    missing_output_path.write_text(json.dumps(missing_payload, ensure_ascii=False, indent=2), encoding="utf-8")

    ambiguous_payload = build_ambiguous_report(summary)
    ambiguous_output_path = Path(args.ambiguous_output)
    ambiguous_output_path.parent.mkdir(parents=True, exist_ok=True)
    ambiguous_output_path.write_text(json.dumps(ambiguous_payload, ensure_ascii=False, indent=2), encoding="utf-8")

    quick_summary = {
        code: {
            "unresolved": data["unresolved_count"],
            "total_delta": data["total_delta"],
            "largest_gap_topic": data["largest_topic_gaps"][0]["topic_key"] if data["largest_topic_gaps"] else None,
        }
        for code, data in summary["categories"].items()
    }

    for category in summary["categories"].values():
        category.pop("topic_index", None)
    print(json.dumps(quick_summary, ensure_ascii=False, indent=2))
    print(f"Zapisano raport unresolved/local-gaps do: {output_path}")
    print(f"Zapisano raport missing locally do: {missing_output_path}")
    print(f"Zapisano raport ambiguous_local_match do: {ambiguous_output_path}")


if __name__ == "__main__":
    main()

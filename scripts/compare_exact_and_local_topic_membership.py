from __future__ import annotations

import argparse
import json
import re
import unicodedata
from collections import Counter, defaultdict
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_EXACT_PATH = ROOT / "output" / "analysis" / "pj360-exact-topic-membership" / "exact-topic-membership-all.json"
DEFAULT_LOCAL_PATH = ROOT / "output" / "analysis" / "pj360-exact-topic-membership" / "local-effective-topic-membership-all.json"
DEFAULT_OUTPUT_DIR = ROOT / "output" / "analysis" / "pj360-exact-topic-membership"
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


def signature(prompt: str, accepted_answer: str, question_media_kind: str) -> tuple[str, str, str]:
    return (
        normalize_key(prompt),
        normalize_key(accepted_answer),
        normalize_text(question_media_kind or "none"),
    )


def load_json(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def topic_lookup_from_categories(payload: dict[str, Any]) -> dict[str, dict[str, Any]]:
    return {
        str(category["category_code"]).upper(): category
        for category in payload.get("categories", [])
    }


def compare_category(
    *,
    category_code: str,
    exact_category: dict[str, Any],
    local_category: dict[str, Any],
) -> dict[str, Any]:
    exact_topics = {str(topic["topic_key"]): topic for topic in exact_category.get("topics", [])}
    local_topics = {str(topic["topic_key"]): topic for topic in local_category.get("topics", [])}
    topic_keys = []
    seen = set()
    for topic in exact_category.get("topics", []):
        topic_key = str(topic["topic_key"])
        if topic_key not in seen:
            topic_keys.append(topic_key)
            seen.add(topic_key)
    for topic in local_category.get("topics", []):
        topic_key = str(topic["topic_key"])
        if topic_key not in seen:
            topic_keys.append(topic_key)
            seen.add(topic_key)

    local_global_index: dict[tuple[str, str, str], list[dict[str, Any]]] = defaultdict(list)
    local_global_topic_counts: dict[tuple[str, str, str], Counter[str]] = defaultdict(Counter)
    exact_global_topic_counts: dict[tuple[str, str, str], Counter[str]] = defaultdict(Counter)

    for topic in local_category.get("topics", []):
        topic_key = str(topic["topic_key"])
        for question in topic.get("questions", []):
            key = signature(
                str(question.get("prompt") or ""),
                str(question.get("accepted_answer") or ""),
                str(question.get("question_media_kind") or "none"),
            )
            question_payload = dict(question)
            question_payload["topic_key"] = topic_key
            question_payload["topic_label"] = topic.get("topic_label")
            local_global_index[key].append(question_payload)
            local_global_topic_counts[key][topic_key] += 1

    for topic in exact_category.get("topics", []):
        topic_key = str(topic["topic_key"])
        for question in topic.get("questions", []):
            key = signature(
                str(question.get("prompt") or ""),
                str(question.get("accepted_answer") or ""),
                str(question.get("question_media_kind") or "none"),
            )
            exact_global_topic_counts[key][topic_key] += 1

    topic_rows = []
    wrong_topic_examples_by_topic: dict[str, list[dict[str, Any]]] = defaultdict(list)
    missing_examples_by_topic: dict[str, list[dict[str, Any]]] = defaultdict(list)
    local_only_examples_by_topic: dict[str, list[dict[str, Any]]] = defaultdict(list)

    total_matched_same_topic = 0
    total_available_other_topic = 0
    total_missing_local = 0
    total_local_only = 0
    exact_total_questions = sum(len(topic.get("questions", [])) for topic in exact_category.get("topics", []))
    local_total_questions = sum(len(topic.get("questions", [])) for topic in local_category.get("topics", []))

    for topic_key in topic_keys:
        exact_topic = exact_topics.get(topic_key, {})
        local_topic = local_topics.get(topic_key, {})
        exact_questions = exact_topic.get("questions", [])
        local_questions = local_topic.get("questions", [])

        exact_signature_counts: Counter[tuple[str, str, str]] = Counter()
        local_signature_counts: Counter[tuple[str, str, str]] = Counter()
        signature_example_question: dict[tuple[str, str, str], dict[str, Any]] = {}
        local_signature_example_question: dict[tuple[str, str, str], dict[str, Any]] = {}

        for question in exact_questions:
            key = signature(
                str(question.get("prompt") or ""),
                str(question.get("accepted_answer") or ""),
                str(question.get("question_media_kind") or "none"),
            )
            exact_signature_counts[key] += 1
            signature_example_question.setdefault(key, question)

        for question in local_questions:
            key = signature(
                str(question.get("prompt") or ""),
                str(question.get("accepted_answer") or ""),
                str(question.get("question_media_kind") or "none"),
            )
            local_signature_counts[key] += 1
            local_signature_example_question.setdefault(key, question)

        matched_same_topic = 0
        available_other_topic = 0
        missing_local = 0
        local_only = 0

        all_signatures = set(exact_signature_counts) | set(local_signature_counts)
        for key in all_signatures:
            pj_count = int(exact_signature_counts.get(key, 0))
            local_same_count = int(local_signature_counts.get(key, 0))
            local_any_count = len(local_global_index.get(key, []))
            local_other_count = max(local_any_count - local_same_count, 0)

            same_topic_match_count = min(pj_count, local_same_count)
            remaining_pj_count = max(pj_count - same_topic_match_count, 0)
            cross_topic_available_count = min(remaining_pj_count, local_other_count)
            missing_count = max(remaining_pj_count - cross_topic_available_count, 0)

            matched_same_topic += same_topic_match_count
            available_other_topic += cross_topic_available_count
            missing_local += missing_count

            if local_same_count > pj_count:
                local_only += local_same_count - pj_count

            if cross_topic_available_count > 0 and len(wrong_topic_examples_by_topic[topic_key]) < 12:
                question = signature_example_question.get(key) or {}
                candidates = sorted(
                    local_global_index.get(key, []),
                    key=lambda row: (row.get("topic_key") != topic_key, row.get("question_id")),
                )
                wrong_topic_examples_by_topic[topic_key].append(
                    {
                        "prompt": question.get("prompt"),
                        "accepted_answer": question.get("accepted_answer"),
                        "question_media_kind": question.get("question_media_kind"),
                        "pj_count_in_topic": pj_count,
                        "local_count_in_topic": local_same_count,
                        "local_count_other_topics": local_other_count,
                        "local_topic_distribution": dict(local_global_topic_counts.get(key, Counter()).most_common()),
                        "candidate_questions": [
                            {
                                "question_id": row.get("question_id"),
                                "external_id": row.get("external_id"),
                                "source": row.get("source"),
                                "topic_key": row.get("topic_key"),
                                "main_media_original": row.get("main_media_original"),
                            }
                            for row in candidates[:8]
                        ],
                    }
                )

            if missing_count > 0 and len(missing_examples_by_topic[topic_key]) < 12:
                question = signature_example_question.get(key) or {}
                missing_examples_by_topic[topic_key].append(
                    {
                        "prompt": question.get("prompt"),
                        "accepted_answer": question.get("accepted_answer"),
                        "question_media_kind": question.get("question_media_kind"),
                        "missing_count": missing_count,
                        "pj_count_in_topic": pj_count,
                        "local_count_any_topic": local_any_count,
                    }
                )

            if local_same_count > pj_count and len(local_only_examples_by_topic[topic_key]) < 12:
                question = local_signature_example_question.get(key) or {}
                local_only_examples_by_topic[topic_key].append(
                    {
                        "prompt": question.get("prompt"),
                        "accepted_answer": question.get("accepted_answer"),
                        "question_media_kind": question.get("question_media_kind"),
                        "extra_local_count": local_same_count - pj_count,
                        "pj_topic_distribution": dict(exact_global_topic_counts.get(key, Counter()).most_common()),
                        "local_topic_distribution": dict(local_global_topic_counts.get(key, Counter()).most_common()),
                    }
                )

        total_matched_same_topic += matched_same_topic
        total_available_other_topic += available_other_topic
        total_missing_local += missing_local
        total_local_only += local_only

        topic_rows.append(
            {
                "topic_key": topic_key,
                "topic_label": str(exact_topic.get("topic_label") or local_topic.get("topic_label") or topic_key),
                "bucket": str(exact_topic.get("bucket") or local_topic.get("bucket") or ""),
                "pj360_count": len(exact_questions),
                "local_count": len(local_questions),
                "delta": len(local_questions) - len(exact_questions),
                "matched_same_topic_count": matched_same_topic,
                "available_other_topic_count": available_other_topic,
                "missing_local_count": missing_local,
                "local_only_in_topic_count": local_only,
                "wrong_topic_examples": wrong_topic_examples_by_topic.get(topic_key, []),
                "missing_examples": missing_examples_by_topic.get(topic_key, []),
                "local_only_examples": local_only_examples_by_topic.get(topic_key, []),
            }
        )

    topic_rows.sort(key=lambda row: (abs(int(row["delta"])), row["missing_local_count"], row["available_other_topic_count"]), reverse=True)

    return {
        "category_code": category_code,
        "pj360_total_questions": exact_total_questions,
        "local_total_questions": local_total_questions,
        "topics_count": len(topic_rows),
        "summary": {
            "total_delta": local_total_questions - exact_total_questions,
            "matched_same_topic_total": total_matched_same_topic,
            "available_other_topic_total": total_available_other_topic,
            "missing_local_total": total_missing_local,
            "local_only_in_topic_total": total_local_only,
        },
        "topics": topic_rows,
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Compare PJ360 exact topic membership with local effective topic membership.")
    parser.add_argument("--categories", default="B", help="Lista kategorii oddzielona przecinkami.")
    parser.add_argument("--exact-json", default=str(DEFAULT_EXACT_PATH), help="Sciezka do exact-membership PJ360 JSON.")
    parser.add_argument("--local-json", default=str(DEFAULT_LOCAL_PATH), help="Sciezka do local effective membership JSON.")
    parser.add_argument("--output-dir", default=str(DEFAULT_OUTPUT_DIR), help="Katalog docelowy.")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    requested_categories = [item.strip().upper() for item in args.categories.split(",") if item.strip()]
    exact_payload = load_json(Path(args.exact_json))
    local_payload = load_json(Path(args.local_json))
    exact_lookup = topic_lookup_from_categories(exact_payload)
    local_lookup = topic_lookup_from_categories(local_payload)

    categories_payload = []
    for category_code in requested_categories:
        categories_payload.append(
            compare_category(
                category_code=category_code,
                exact_category=exact_lookup[category_code],
                local_category=local_lookup[category_code],
            )
        )

    result = {
        "generated_for_categories": requested_categories,
        "categories": categories_payload,
    }

    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    all_path = output_dir / "exact-vs-local-topic-membership-diff.json"
    all_path.write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding="utf-8")

    for category in categories_payload:
        category_path = output_dir / f"{str(category['category_code']).lower()}-exact-vs-local-topic-membership-diff.json"
        category_path.write_text(json.dumps(category, ensure_ascii=False, indent=2), encoding="utf-8")

    quick_summary = {
        category["category_code"]: {
            "total_delta": category["summary"]["total_delta"],
            "available_other_topic_total": category["summary"]["available_other_topic_total"],
            "missing_local_total": category["summary"]["missing_local_total"],
            "largest_topic_gaps": [
                {
                    "topic_key": topic["topic_key"],
                    "delta": topic["delta"],
                    "available_other_topic_count": topic["available_other_topic_count"],
                    "missing_local_count": topic["missing_local_count"],
                }
                for topic in category["topics"][:8]
            ],
        }
        for category in categories_payload
    }

    print(json.dumps(quick_summary, ensure_ascii=False, indent=2))
    print(f"Zapisano diff membership do: {all_path}")


if __name__ == "__main__":
    main()

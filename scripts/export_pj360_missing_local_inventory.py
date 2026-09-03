from __future__ import annotations

import argparse
import json
import re
import unicodedata
from collections import Counter, defaultdict
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_INPUT_DIR = ROOT / "output" / "analysis" / "pj360-exact-topic-membership"
DEFAULT_EXACT_PATH = DEFAULT_INPUT_DIR / "exact-topic-membership-all.json"
DEFAULT_LOCAL_PATH = DEFAULT_INPUT_DIR / "local-effective-topic-membership-all.json"
DEFAULT_OUTPUT_DIR = DEFAULT_INPUT_DIR
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


def question_signature(question: dict[str, Any]) -> tuple[str, str, str]:
    return (
        normalize_key(question.get("prompt")),
        normalize_key(question.get("accepted_answer")),
        normalize_text(question.get("question_media_kind") or "none"),
    )


def load_category_map(path: Path) -> dict[str, dict[str, Any]]:
    payload = json.loads(path.read_text(encoding="utf-8"))

    if "categories" in payload and isinstance(payload["categories"], list):
        return {
            str(category["category_code"]).upper(): category
            for category in payload["categories"]
        }

    if "category_code" in payload:
        return {str(payload["category_code"]).upper(): payload}

    raise ValueError(f"Nieznany format pliku JSON: {path}")


def load_category_with_fallback(
    *,
    category_code: str,
    categories: dict[str, dict[str, Any]],
    fallback_dir: Path,
    filename_pattern: str,
) -> dict[str, Any]:
    if category_code in categories:
        return categories[category_code]

    fallback_path = fallback_dir / filename_pattern.format(category=category_code)
    if not fallback_path.exists():
        raise KeyError(f"Brak pliku pomocniczego dla kategorii {category_code}: {fallback_path}")

    fallback_categories = load_category_map(fallback_path)
    if category_code not in fallback_categories:
        raise KeyError(f"Brak kategorii {category_code} w pliku {fallback_path}")

    categories[category_code] = fallback_categories[category_code]
    return categories[category_code]


def topic_lookup(category_payload: dict[str, Any]) -> dict[str, dict[str, Any]]:
    return {
        str(topic["topic_key"]): topic
        for topic in category_payload.get("topics", [])
    }


def build_category_inventory(
    *,
    category_code: str,
    exact_category: dict[str, Any],
    local_category: dict[str, Any],
) -> dict[str, Any]:
    local_topics = topic_lookup(local_category)
    local_by_signature: dict[tuple[str, str, str], list[dict[str, Any]]] = defaultdict(list)

    for topic in local_category.get("topics", []):
        for question in topic.get("questions", []):
            local_by_signature[question_signature(question)].append(
                {
                    "question_id": question.get("question_id"),
                    "external_id": question.get("external_id"),
                    "source": question.get("source"),
                    "topic_key": topic.get("topic_key"),
                    "topic_label": topic.get("topic_label"),
                    "main_media_original": question.get("main_media_original"),
                }
            )

    local_totals_by_signature = {
        signature: len(rows)
        for signature, rows in local_by_signature.items()
    }
    covered_local_by_signature: Counter[tuple[str, str, str]] = Counter()

    topic_rows: list[dict[str, Any]] = []
    total_missing = 0
    target_total = 0
    local_total = 0

    for topic in exact_category.get("topics", []):
        topic_key = str(topic["topic_key"])
        local_topic = local_topics.get(topic_key, {})
        local_count = len(local_topic.get("questions", []))
        target_count = len(topic.get("questions", []))
        target_total += target_count
        local_total += local_count
        missing_questions: list[dict[str, Any]] = []

        for question in topic.get("questions", []):
            signature = question_signature(question)
            available_local = local_totals_by_signature.get(signature, 0)

            if covered_local_by_signature[signature] < available_local:
                covered_local_by_signature[signature] += 1
                continue

            candidates = local_by_signature.get(signature, [])
            local_topic_distribution = Counter(
                str(candidate.get("topic_key") or "unassigned")
                for candidate in candidates
            )

            missing_questions.append(
                {
                    "internal_question_id": question.get("internal_question_id"),
                    "page": question.get("page"),
                    "position_on_page": question.get("position_on_page"),
                    "prompt": question.get("prompt"),
                    "accepted_answer": question.get("accepted_answer"),
                    "question_media_kind": question.get("question_media_kind"),
                    "question_media_url": question.get("question_media_url"),
                    "topic_slug": question.get("topic_slug"),
                    "local_count_any_topic": available_local,
                    "local_topic_distribution": dict(local_topic_distribution),
                    "local_candidate_questions": candidates[:10],
                }
            )

        total_missing += len(missing_questions)
        topic_rows.append(
            {
                "topic_key": topic_key,
                "topic_label": topic.get("topic_label"),
                "bucket": topic.get("bucket"),
                "actual": local_count,
                "target": target_count,
                "delta": local_count - target_count,
                "missing_local_count": len(missing_questions),
                "questions": missing_questions,
            }
        )

    topic_rows.sort(
        key=lambda row: (row["missing_local_count"], abs(row["delta"])),
        reverse=True,
    )

    return {
        "category_code": category_code,
        "summary": {
            "local_total_questions": local_total,
            "target_total_questions": target_total,
            "total_delta": local_total - target_total,
            "missing_local_total": total_missing,
            "topics_with_missing_local": sum(1 for row in topic_rows if row["missing_local_count"] > 0),
        },
        "topics": [row for row in topic_rows if row["missing_local_count"] > 0],
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Export exact missing-local inventory per category/topic from PJ360 and local membership JSON."
    )
    parser.add_argument(
        "--categories",
        default="B",
        help="Lista kategorii oddzielona przecinkami. Domyslnie: B.",
    )
    parser.add_argument(
        "--exact-json",
        default=str(DEFAULT_EXACT_PATH),
        help="Sciezka do pliku exact-topic-membership JSON.",
    )
    parser.add_argument(
        "--local-json",
        default=str(DEFAULT_LOCAL_PATH),
        help="Sciezka do pliku local-effective-topic-membership JSON.",
    )
    parser.add_argument(
        "--output-dir",
        default=str(DEFAULT_OUTPUT_DIR),
        help="Katalog docelowy dla inventory JSON.",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    requested_categories = [item.strip().upper() for item in args.categories.split(",") if item.strip()]

    exact_path = Path(args.exact_json)
    local_path = Path(args.local_json)
    exact_categories = load_category_map(exact_path)
    local_categories = load_category_map(local_path)

    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    inventories: list[dict[str, Any]] = []
    quick_summary: dict[str, Any] = {}

    for category_code in requested_categories:
        exact_category = load_category_with_fallback(
            category_code=category_code,
            categories=exact_categories,
            fallback_dir=exact_path.parent,
            filename_pattern="exact-topic-membership-{category}.json",
        )
        local_category = load_category_with_fallback(
            category_code=category_code,
            categories=local_categories,
            fallback_dir=local_path.parent,
            filename_pattern="local-effective-topic-membership-{category}.json",
        )

        inventory = build_category_inventory(
            category_code=category_code,
            exact_category=exact_category,
            local_category=local_category,
        )
        inventories.append(inventory)

        category_output = output_dir / f"{category_code.lower()}-missing-local-inventory.json"
        category_output.write_text(json.dumps(inventory, ensure_ascii=False, indent=2), encoding="utf-8")

        quick_summary[category_code] = {
            "missing_local_total": inventory["summary"]["missing_local_total"],
            "topics_with_missing_local": inventory["summary"]["topics_with_missing_local"],
            "largest_topic_gap": inventory["topics"][0]["topic_key"] if inventory["topics"] else None,
        }

    all_output = output_dir / "missing-local-inventory-all.json"
    all_output.write_text(
        json.dumps(
            {
                "generated_for_categories": requested_categories,
                "categories": inventories,
            },
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )

    print(json.dumps(quick_summary, ensure_ascii=False, indent=2))
    print(f"Zapisano inventory do: {all_output}")


if __name__ == "__main__":
    main()

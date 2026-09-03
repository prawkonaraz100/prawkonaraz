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
DEFAULT_OUTPUT_DIR = ROOT / "output" / "analysis" / "pj360-exact-topic-membership"
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
    main_media_original: str


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


def load_json(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


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
            q.metadata,
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

        metadata = metadata_dict(row.get("metadata"))
        questions.append(
            LocalQuestion(
                question_id=int(row["question_id"]),
                source=str(row["source"] or ""),
                external_id=str(row["external_id"] or ""),
                prompt=normalize_text(row["prompt"] or ""),
                accepted_answer=answer_text_from_row(row),
                question_media_kind=media_kind,
                current_topic_key=str(row["current_topic_key"] or "unassigned"),
                main_media_original=normalize_text(str(metadata.get("main_media_original") or "")),
            )
        )

    return questions


def build_topic_index(
    *,
    membership_category: dict[str, Any],
    local_questions: list[LocalQuestion],
    package_payload: dict[str, Any],
) -> dict[str, dict[str, int]]:
    target_counts = {
        str(topic["topic_key"]): len(topic.get("questions", []))
        for topic in membership_category.get("topics", [])
    }
    actual_counts = Counter(question.current_topic_key for question in local_questions)

    unresolved_by_topic: Counter[str] = Counter()
    unresolved_by_topic_reason: dict[str, Counter[str]] = defaultdict(Counter)
    for row in package_payload.get("unresolved", []):
        reason = str(row.get("reason") or "unknown")
        membership_question = row.get("membership_question") or {}
        topic_key = str(membership_question.get("target_topic_key") or "unknown")
        unresolved_by_topic[topic_key] += 1
        unresolved_by_topic_reason[topic_key][reason] += 1

    topic_index: dict[str, dict[str, int]] = {}
    for topic_key, target in target_counts.items():
        topic_index[topic_key] = {
            "actual": int(actual_counts.get(topic_key, 0)),
            "target": int(target),
            "delta": int(actual_counts.get(topic_key, 0) - target),
            "unresolved_count": int(unresolved_by_topic.get(topic_key, 0)),
            "ambiguous_count": int(unresolved_by_topic_reason[topic_key].get("ambiguous_local_match", 0)),
            "no_local_prompt_match_count": int(unresolved_by_topic_reason[topic_key].get("no_local_prompt_match", 0)),
            "answer_mismatch_count": int(unresolved_by_topic_reason[topic_key].get("answer_mismatch", 0)),
            "media_kind_mismatch_count": int(unresolved_by_topic_reason[topic_key].get("media_kind_mismatch", 0)),
        }

    return topic_index


def package_path(package_dir: Path, category_code: str) -> Path:
    return package_dir / f"pj360-{category_code.lower()}-exact-topic-membership-package.json"


def build_ambiguous_review(
    *,
    category_code: str,
    membership_category: dict[str, Any],
    package_payload: dict[str, Any],
    local_questions: list[LocalQuestion],
) -> dict[str, Any]:
    local_by_id = {question.question_id: question for question in local_questions}
    topic_index = build_topic_index(
        membership_category=membership_category,
        local_questions=local_questions,
        package_payload=package_payload,
    )

    rows = []
    ambiguous_by_topic = Counter()
    resolution_bucket_counts = Counter()
    cross_topic_conflict_by_topic = Counter()
    topic_closure_conflicts = []
    for unresolved in package_payload.get("unresolved", []):
        if str(unresolved.get("reason") or "") != "ambiguous_local_match":
            continue

        membership_question = unresolved.get("membership_question") or {}
        topic_key = str(membership_question.get("target_topic_key") or "unknown")
        ambiguous_by_topic[topic_key] += 1

        candidates = []
        current_topic_distribution = Counter()
        candidate_answers = set()
        for candidate_id in unresolved.get("candidate_question_ids", []):
            candidate = local_by_id.get(int(candidate_id))
            if candidate is None:
                continue

            current_topic_distribution[candidate.current_topic_key] += 1
            candidate_answers.add(candidate.accepted_answer)
            candidates.append(
                {
                    "question_id": candidate.question_id,
                    "external_id": candidate.external_id,
                    "source": candidate.source,
                    "current_topic_key": candidate.current_topic_key,
                    "question_media_kind": candidate.question_media_kind,
                    "main_media_original": candidate.main_media_original,
                    "prompt": candidate.prompt,
                    "accepted_answer": candidate.accepted_answer,
                    "already_in_target_topic": candidate.current_topic_key == topic_key,
                }
            )

        current_topics = set(current_topic_distribution)
        if current_topics == {topic_key}:
            resolution_bucket = "topic_aligned_duplicate"
        elif len(current_topics) > 1:
            resolution_bucket = "mixed_topic_conflict"
            cross_topic_conflict_by_topic[topic_key] += 1
        else:
            resolution_bucket = "cross_topic_conflict"
            cross_topic_conflict_by_topic[topic_key] += 1

        answer_variant_present = any(answer != membership_question.get("accepted_answer") for answer in candidate_answers)
        resolution_bucket_counts[resolution_bucket] += 1

        record = {
            "topic_key": topic_key,
            "topic_context": topic_index.get(topic_key),
            "resolution_bucket": resolution_bucket,
            "answer_variant_present": answer_variant_present,
            "membership_question": {
                "internal_question_id": membership_question.get("internal_question_id"),
                "topic_slug": membership_question.get("target_topic_slug") or membership_question.get("topic_slug"),
                "topic_label": membership_question.get("target_topic_label") or membership_question.get("topic_label"),
                "page": membership_question.get("page"),
                "position_on_page": membership_question.get("position_on_page"),
                "prompt": membership_question.get("prompt"),
                "accepted_answer": membership_question.get("accepted_answer"),
                "question_media_kind": membership_question.get("question_media_kind"),
                "question_media_url": membership_question.get("question_media_url"),
            },
            "candidate_count": len(candidates),
            "current_topic_distribution": dict(current_topic_distribution.most_common()),
            "candidates": candidates,
        }
        rows.append(record)
        if resolution_bucket != "topic_aligned_duplicate":
            topic_closure_conflicts.append(record)

    rows.sort(
        key=lambda row: (
            -int(row["topic_context"]["ambiguous_count"] if row["topic_context"] else 0),
            -int(row["candidate_count"]),
            row["topic_key"],
            normalize_key(str(row["membership_question"]["prompt"] or "")),
        )
    )

    return {
        "category_code": category_code,
        "summary": {
            "ambiguous_count": len(rows),
            "ambiguous_by_topic": dict(ambiguous_by_topic.most_common()),
            "resolution_bucket_counts": dict(resolution_bucket_counts.most_common()),
            "cross_topic_conflict_by_topic": dict(cross_topic_conflict_by_topic.most_common()),
            "topic_closure_conflict_count": len(topic_closure_conflicts),
            "review_order_topics": [topic for topic, _count in ambiguous_by_topic.most_common()],
        },
        "records": rows,
        "topic_closure_conflicts": topic_closure_conflicts,
    }


def build_import_gap_shortlist(
    *,
    category_code: str,
    membership_category: dict[str, Any],
    package_payload: dict[str, Any],
    local_questions: list[LocalQuestion],
) -> dict[str, Any]:
    topic_index = build_topic_index(
        membership_category=membership_category,
        local_questions=local_questions,
        package_payload=package_payload,
    )

    import_rows = []
    mismatch_rows = []
    blocking_import_rows = []
    import_by_topic = Counter()
    blocking_import_by_topic = Counter()
    mismatch_by_reason = Counter()
    mismatch_by_topic = Counter()

    for unresolved in package_payload.get("unresolved", []):
        reason = str(unresolved.get("reason") or "unknown")
        membership_question = unresolved.get("membership_question") or {}
        topic_key = str(membership_question.get("target_topic_key") or "unknown")
        payload = {
            "reason": reason,
            "topic_key": topic_key,
            "topic_context": topic_index.get(topic_key),
            "membership_question": {
                "internal_question_id": membership_question.get("internal_question_id"),
                "topic_slug": membership_question.get("target_topic_slug") or membership_question.get("topic_slug"),
                "topic_label": membership_question.get("target_topic_label") or membership_question.get("topic_label"),
                "page": membership_question.get("page"),
                "position_on_page": membership_question.get("position_on_page"),
                "prompt": membership_question.get("prompt"),
                "accepted_answer": membership_question.get("accepted_answer"),
                "question_media_kind": membership_question.get("question_media_kind"),
                "question_media_url": membership_question.get("question_media_url"),
            },
        }

        if reason == "no_local_prompt_match":
            import_by_topic[topic_key] += 1
            import_rows.append(payload)
            if payload["topic_context"] and int(payload["topic_context"]["delta"]) < 0:
                blocking_import_by_topic[topic_key] += 1
                blocking_import_rows.append(payload)
            continue

        if reason in {"answer_mismatch", "media_kind_mismatch"}:
            mismatch_by_reason[reason] += 1
            mismatch_by_topic[topic_key] += 1
            mismatch_rows.append(payload)

    import_rows.sort(
        key=lambda row: (
            -int(row["topic_context"]["no_local_prompt_match_count"] if row["topic_context"] else 0),
            row["topic_key"],
            normalize_key(str(row["membership_question"]["prompt"] or "")),
        )
    )
    mismatch_rows.sort(
        key=lambda row: (
            row["reason"],
            row["topic_key"],
            normalize_key(str(row["membership_question"]["prompt"] or "")),
        )
    )

    return {
        "category_code": category_code,
        "summary": {
            "import_gap_count": len(import_rows),
            "import_gap_by_topic": dict(import_by_topic.most_common()),
            "blocking_import_gap_count": len(blocking_import_rows),
            "blocking_import_gap_by_topic": dict(blocking_import_by_topic.most_common()),
            "non_import_mismatch_count": len(mismatch_rows),
            "non_import_mismatch_by_reason": dict(mismatch_by_reason.most_common()),
            "non_import_mismatch_by_topic": dict(mismatch_by_topic.most_common()),
        },
        "records": import_rows,
        "blocking_records": blocking_import_rows,
        "non_import_mismatches": mismatch_rows,
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Build category-specific PJ360 unresolved review worksets.")
    parser.add_argument("--category", default="B", help="Kod kategorii, np. B.")
    parser.add_argument("--membership-json", default=str(DEFAULT_MEMBERSHIP_PATH), help="Sciezka do exact-membership JSON.")
    parser.add_argument("--package-dir", default=str(DEFAULT_PACKAGE_DIR), help="Katalog z finalnymi exact package per kategoria.")
    parser.add_argument("--output-dir", default=str(DEFAULT_OUTPUT_DIR), help="Katalog docelowy na worksety JSON.")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    category_code = args.category.strip().upper()

    membership_payload = load_json(Path(args.membership_json))
    membership_lookup = {
        str(category["category_code"]).upper(): category
        for category in membership_payload.get("categories", [])
    }
    membership_category = membership_lookup[category_code]
    package_payload = load_json(package_path(Path(args.package_dir), category_code))
    local_questions = fetch_local_questions(category_code)

    ambiguous_payload = build_ambiguous_review(
        category_code=category_code,
        membership_category=membership_category,
        package_payload=package_payload,
        local_questions=local_questions,
    )
    import_payload = build_import_gap_shortlist(
        category_code=category_code,
        membership_category=membership_category,
        package_payload=package_payload,
        local_questions=local_questions,
    )

    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)
    ambiguous_path = output_dir / f"{category_code.lower()}-ambiguous-review.json"
    import_path = output_dir / f"{category_code.lower()}-import-gap-shortlist.json"

    ambiguous_path.write_text(json.dumps(ambiguous_payload, ensure_ascii=False, indent=2), encoding="utf-8")
    import_path.write_text(json.dumps(import_payload, ensure_ascii=False, indent=2), encoding="utf-8")

    quick_summary = {
        "category_code": category_code,
        "ambiguous_count": ambiguous_payload["summary"]["ambiguous_count"],
        "ambiguous_top_topics": ambiguous_payload["summary"]["ambiguous_by_topic"],
        "import_gap_count": import_payload["summary"]["import_gap_count"],
        "import_gap_top_topics": import_payload["summary"]["import_gap_by_topic"],
        "non_import_mismatch_by_reason": import_payload["summary"]["non_import_mismatch_by_reason"],
    }

    print(json.dumps(quick_summary, ensure_ascii=False, indent=2))
    print(f"Zapisano review workset ambiguous do: {ambiguous_path}")
    print(f"Zapisano shortlistę import gap do: {import_path}")


if __name__ == "__main__":
    main()

from __future__ import annotations

import argparse
import json
import re
import unicodedata
from dataclasses import dataclass
from datetime import datetime, UTC
from pathlib import Path
from typing import Any

import psycopg


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_MEMBERSHIP_PATH = ROOT / "output" / "analysis" / "pj360-exact-topic-membership" / "exact-topic-membership.json"
DEFAULT_OUTPUT_PATH = ROOT / "output" / "analysis" / "pj360-exact-topic-membership" / "exact-topic-override-package.json"
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
    gov_id: str
    prompt: str
    accepted_answer: str
    structure_scope: str
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


def normalized_membership_answer(row: dict[str, Any]) -> str:
    return normalize_key(row.get("accepted_answer") or "")


def normalized_membership_media(row: dict[str, Any]) -> str:
    return normalize_text(row.get("question_media_kind") or "none")


def normalized_local_answer(question: LocalQuestion) -> str:
    return normalize_key(question.accepted_answer)


def normalized_local_media(question: LocalQuestion) -> str:
    return normalize_text(question.question_media_kind or "none")


def membership_group_key(row: dict[str, Any]) -> tuple[str, str, str, str]:
    return (
        normalize_key(row["prompt"]),
        normalized_membership_answer(row),
        normalized_membership_media(row),
        str(row["target_topic_key"] or ""),
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


def fetch_local_questions(category_code: str, scope: str) -> list[LocalQuestion]:
    query = """
        SELECT
            q.id AS question_id,
            LOWER(q.source) AS source,
            q.external_id,
            COALESCE(q.metadata->>'government_question_id', q.external_id) AS gov_id,
            q.prompt,
            q.question_type,
            q.correct_answer,
            q.option_a,
            q.option_b,
            q.option_c,
            UPPER(COALESCE(q.metadata->>'structure_scope', 'PODSTAWOWY')) AS structure_scope,
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
          AND (%s <> 'active' OR q.is_active = TRUE)
          AND (%s <> 'active_ready' OR (q.is_active = TRUE AND q.delivery_issue IS NULL))
        ORDER BY q.id
    """

    with psycopg.connect(DB_DSN) as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(query, (category_code, scope, scope))
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
                gov_id=str(row["gov_id"] or ""),
                prompt=normalize_text(row["prompt"] or ""),
                accepted_answer=answer_text_from_row(row),
                structure_scope=str(row["structure_scope"] or "PODSTAWOWY"),
                question_media_kind=media_kind,
                current_topic_key=str(row["current_topic_key"] or "unassigned"),
            )
        )

    return questions


def score_membership_match(external_question: dict[str, Any], local_question: LocalQuestion) -> int:
    score = 0

    if normalize_key(external_question["prompt"]) == normalize_key(local_question.prompt):
        score += 100

    if normalize_key(external_question.get("accepted_answer") or "") == normalize_key(local_question.accepted_answer):
        score += 20

    if normalize_text(external_question.get("question_media_kind") or "none") == local_question.question_media_kind:
        score += 10

    if normalize_text(external_question.get("question_type") or "single_choice") == normalize_text(local_question.structure_scope and external_question.get("question_type") or "single_choice"):
        score += 0

    return score


def load_membership_topic_rows(path: Path, category_code: str) -> list[dict[str, Any]]:
    payload = json.loads(path.read_text(encoding="utf-8"))
    for category in payload.get("categories", []):
        if str(category.get("category_code", "")).upper() != category_code:
            continue

        rows: list[dict[str, Any]] = []
        for topic in category.get("topics", []):
            topic_key = topic.get("topic_key")
            topic_label = topic.get("topic_label")
            topic_slug = topic.get("topic_slug")
            for question in topic.get("questions", []):
                row = dict(question)
                row["target_topic_key"] = topic_key
                row["target_topic_label"] = topic_label
                row["target_topic_slug"] = topic_slug
                rows.append(row)
        return rows

    raise RuntimeError(f"Brak kategorii {category_code} w pliku membership: {path}")


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Build a review-only exact override package from PJ360 topic membership.")
    parser.add_argument("--membership-json", default=str(DEFAULT_MEMBERSHIP_PATH), help="Sciezka do pliku exact-membership JSON.")
    parser.add_argument("--category", required=True, help="Kod kategorii, np. B.")
    parser.add_argument("--scope", default="active_ready", choices=["active_ready", "active", "all"], help="Zakres lokalnych pytan do matchingu.")
    parser.add_argument("--output", default=str(DEFAULT_OUTPUT_PATH), help="Sciezka do pliku review-only package JSON.")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    category_code = args.category.strip().upper()
    membership_path = Path(args.membership_json)
    output_path = Path(args.output)

    membership_rows = load_membership_topic_rows(membership_path, category_code)
    local_questions = fetch_local_questions(category_code, args.scope)

    local_by_prompt: dict[str, list[LocalQuestion]] = {}
    for question in local_questions:
        local_by_prompt.setdefault(normalize_key(question.prompt), []).append(question)

    membership_groups: dict[tuple[str, str, str, str], list[dict[str, Any]]] = {}
    for membership_question in membership_rows:
        membership_groups.setdefault(membership_group_key(membership_question), []).append(membership_question)

    overrides: list[dict[str, Any]] = []
    unresolved: list[dict[str, Any]] = []
    seen_identity: set[tuple[str, str, str]] = set()
    processed_group_keys: set[tuple[str, str, str, str]] = set()

    for membership_question in membership_rows:
        group_key = membership_group_key(membership_question)
        if group_key in processed_group_keys:
            continue

        prompt_key = normalize_key(membership_question["prompt"])
        candidates = local_by_prompt.get(prompt_key, [])

        if not candidates:
            for group_member in membership_groups[group_key]:
                unresolved.append(
                    {
                        "reason": "no_local_prompt_match",
                        "membership_question": group_member,
                    }
                )
            processed_group_keys.add(group_key)
            continue

        exact_candidates = [
            candidate
            for candidate in candidates
            if normalized_local_answer(candidate) == normalized_membership_answer(membership_question)
            and normalized_local_media(candidate) == normalized_membership_media(membership_question)
        ]

        # Safe group resolution: if PJ360 has N identical membership rows for the
        # same target topic and we have exactly N local candidates with the same
        # prompt/answer/media signature, we can assign the whole group without
        # guessing a 1:1 row mapping.
        grouped_membership_rows = membership_groups[group_key]
        if len(grouped_membership_rows) > 1 and len(exact_candidates) == len(grouped_membership_rows):
            for candidate in sorted(exact_candidates, key=lambda item: item.question_id):
                if not candidate.source or not candidate.external_id:
                    unresolved.append(
                        {
                            "reason": "missing_stable_identity",
                            "membership_question": membership_question,
                            "local_question_id": candidate.question_id,
                        }
                    )
                    continue

                stable_key = (category_code, candidate.source, candidate.external_id)
                if stable_key in seen_identity:
                    continue
                seen_identity.add(stable_key)

                if candidate.current_topic_key == membership_question["target_topic_key"]:
                    continue

                overrides.append(
                    {
                        "license_category_code": category_code,
                        "source": candidate.source,
                        "external_id": candidate.external_id,
                        "question_topic_key": membership_question["target_topic_key"],
                        "reason": f"Exact membership PJ360 dla {category_code}: {membership_question['target_topic_label']}",
                        "metadata": {
                            "workflow": "pj360_exact_membership",
                            "matching_strategy": "grouped_exact_signature",
                            "membership_group_size": len(grouped_membership_rows),
                            "topic_slug": membership_question["target_topic_slug"],
                            "topic_label": membership_question["target_topic_label"],
                            "current_topic_key": candidate.current_topic_key,
                            "prompt_excerpt": candidate.prompt[:180],
                            "accepted_answer": candidate.accepted_answer,
                            "question_media_kind": candidate.question_media_kind,
                            "membership_page": membership_question["page"],
                            "membership_position": membership_question["position_on_page"],
                            "membership_internal_question_id": membership_question.get("internal_question_id"),
                        },
                    }
                )

            processed_group_keys.add(group_key)
            continue

        scored = sorted(
            (
                (
                    score_membership_match(membership_question, candidate),
                    candidate.question_id,
                    candidate,
                )
                for candidate in candidates
            ),
            reverse=True,
        )

        best_score, _, best_candidate = scored[0]
        ambiguous = len(scored) > 1 and scored[0][0] == scored[1][0]
        answer_matches = normalize_key(membership_question.get("accepted_answer") or "") == normalize_key(best_candidate.accepted_answer)
        media_matches = normalize_text(membership_question.get("question_media_kind") or "none") == best_candidate.question_media_kind

        if ambiguous:
            for group_member in grouped_membership_rows:
                unresolved.append(
                    {
                        "reason": "ambiguous_local_match",
                        "membership_question": group_member,
                        "candidate_question_ids": [candidate.question_id for _, _, candidate in scored[:5]],
                    }
                )
            processed_group_keys.add(group_key)
            continue

        if not answer_matches:
            for group_member in grouped_membership_rows:
                unresolved.append(
                    {
                        "reason": "answer_mismatch",
                        "membership_question": group_member,
                        "local_question": {
                            "question_id": best_candidate.question_id,
                            "prompt": best_candidate.prompt,
                            "accepted_answer": best_candidate.accepted_answer,
                            "question_media_kind": best_candidate.question_media_kind,
                            "current_topic_key": best_candidate.current_topic_key,
                        },
                    }
                )
            processed_group_keys.add(group_key)
            continue

        if not media_matches:
            for group_member in grouped_membership_rows:
                unresolved.append(
                    {
                        "reason": "media_kind_mismatch",
                        "membership_question": group_member,
                        "local_question": {
                            "question_id": best_candidate.question_id,
                            "prompt": best_candidate.prompt,
                            "accepted_answer": best_candidate.accepted_answer,
                            "question_media_kind": best_candidate.question_media_kind,
                            "current_topic_key": best_candidate.current_topic_key,
                        },
                    }
                )
            processed_group_keys.add(group_key)
            continue

        if not best_candidate.source or not best_candidate.external_id:
            for group_member in grouped_membership_rows:
                unresolved.append(
                    {
                        "reason": "missing_stable_identity",
                        "membership_question": group_member,
                        "local_question_id": best_candidate.question_id,
                    }
                )
            processed_group_keys.add(group_key)
            continue

        stable_key = (category_code, best_candidate.source, best_candidate.external_id)
        if stable_key in seen_identity:
            processed_group_keys.add(group_key)
            continue
        seen_identity.add(stable_key)

        if best_candidate.current_topic_key == membership_question["target_topic_key"]:
            processed_group_keys.add(group_key)
            continue

        overrides.append(
            {
                "license_category_code": category_code,
                "source": best_candidate.source,
                "external_id": best_candidate.external_id,
                "question_topic_key": membership_question["target_topic_key"],
                "reason": f"Exact membership PJ360 dla {category_code}: {membership_question['target_topic_label']}",
                "metadata": {
                    "workflow": "pj360_exact_membership",
                    "topic_slug": membership_question["target_topic_slug"],
                    "topic_label": membership_question["target_topic_label"],
                    "current_topic_key": best_candidate.current_topic_key,
                    "prompt_excerpt": best_candidate.prompt[:180],
                    "accepted_answer": best_candidate.accepted_answer,
                    "question_media_kind": best_candidate.question_media_kind,
                    "membership_page": membership_question["page"],
                    "membership_position": membership_question["position_on_page"],
                    "membership_internal_question_id": membership_question.get("internal_question_id"),
                },
            }
        )
        processed_group_keys.add(group_key)

    payload = {
        "generated_at": datetime.now(UTC).isoformat(),
        "workflow": "pj360_exact_membership",
        "category": category_code,
        "scope": args.scope,
        "membership_path": str(membership_path),
        "membership_questions_count": len(membership_rows),
        "local_questions_count": len(local_questions),
        "exported_overrides_count": len(overrides),
        "unresolved_count": len(unresolved),
        "overrides": overrides,
        "unresolved": unresolved,
    }

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    print(
        json.dumps(
            {
                "category": category_code,
                "membership_questions": len(membership_rows),
                "local_questions": len(local_questions),
                "exported_overrides": len(overrides),
                "unresolved": len(unresolved),
            },
            ensure_ascii=False,
            indent=2,
        )
    )
    print(f"Zapisano exact override package do: {output_path}")


if __name__ == "__main__":
    main()

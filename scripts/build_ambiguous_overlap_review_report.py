from __future__ import annotations

import difflib
import json
import os
import re
from collections import Counter
from pathlib import Path
from typing import Any

import psycopg


ROOT = Path(__file__).resolve().parents[1]
PJ360_DIR = ROOT / "output" / "analysis" / "pj360-compare"
REMAINING_DIR = ROOT / "output" / "analysis" / "remaining-explanations"
OUTPUT_DIR = REMAINING_DIR / "ambiguous-overlap-review"
DEFAULT_DSN = (
    os.getenv("PJ360_REMAINING_DB_DSN")
    or os.getenv("PJ360_COMPARE_DB_DSN")
    or "host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit"
)


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def normalize_text(value: str | None) -> str:
    if not value:
        return ""

    normalized = value.lower()
    normalized = re.sub(r"\s+", " ", normalized)
    normalized = re.sub(r"[\"“”'`]", "", normalized)
    normalized = re.sub(r"[^0-9a-ząćęłńóśźż ]+", " ", normalized)
    normalized = re.sub(r"\s+", " ", normalized)
    return normalized.strip()


def token_set(value: str | None) -> set[str]:
    return {token for token in normalize_text(value).split(" ") if token}


def jaccard(left: set[str], right: set[str]) -> float:
    if not left and not right:
        return 1.0
    if not left or not right:
        return 0.0
    return len(left & right) / len(left | right)


def fetch_local_rows(dsn: str, gov_ids: list[str]) -> list[dict[str, Any]]:
    sql = """
        with base as (
            select
                q.id,
                q.external_id,
                q.prompt,
                q.question_type,
                q.correct_answer,
                q.option_a,
                q.option_b,
                q.option_c,
                lc.code as category_code,
                (
                    select qm.kind
                    from question_media qm
                    where qm.question_id = q.id
                    order by qm.sort_order asc, qm.id asc
                    limit 1
                ) as primary_media_kind
            from questions q
            join license_categories lc on lc.id = q.license_category_id
            where q.external_id = any(%s)
        )
        select *
        from base
        order by external_id, category_code, id
    """

    with psycopg.connect(dsn) as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(sql, (gov_ids,))
            return list(cur.fetchall())


def accepted_answer_text(local: dict[str, Any]) -> str | None:
    question_type = local.get("question_type")
    correct_answer = (local.get("correct_answer") or "").lower()

    if question_type == "boolean":
        return "tak" if correct_answer == "a" else "nie" if correct_answer == "b" else None

    option_map = {
        "a": local.get("option_a"),
        "b": local.get("option_b"),
        "c": local.get("option_c"),
    }
    return normalize_text(option_map.get(correct_answer))


def group_local_rows(rows: list[dict[str, Any]]) -> dict[str, dict[str, Any]]:
    grouped: dict[str, dict[str, Any]] = {}

    for row in rows:
        gov_id = str(row["external_id"])
        group = grouped.setdefault(
            gov_id,
            {
                "gov_id": gov_id,
                "prompt": row["prompt"],
                "question_type": row["question_type"],
                "correct_answer": row["correct_answer"],
                "accepted_answer_text": accepted_answer_text(row),
                "categories": [],
                "question_media_kind": row["primary_media_kind"] or "none",
                "option_a": row["option_a"],
                "option_b": row["option_b"],
                "option_c": row["option_c"],
            },
        )

        category_code = row["category_code"]
        if category_code not in group["categories"]:
            group["categories"].append(category_code)

    return grouped


def candidate_score(local: dict[str, Any], external: dict[str, Any]) -> dict[str, Any]:
    local_prompt = normalize_text(local["prompt"])
    external_prompt = normalize_text(external["prompt"])
    local_tokens = token_set(local["prompt"])
    external_tokens = token_set(external["prompt"])

    sequence_ratio = difflib.SequenceMatcher(None, local_prompt, external_prompt).ratio()
    token_ratio = jaccard(local_tokens, external_tokens)
    answer_matches = normalize_text(local["accepted_answer_text"]) == normalize_text(
        external.get("accepted_answer")
    )
    media_kind_matches = (local.get("question_media_kind") or "none") == (
        external.get("question_media_kind") or "none"
    )
    category_overlap = sorted(set(local["categories"]) & set(external.get("categories") or []))
    category_overlap_count = len(category_overlap)

    score = sequence_ratio * 100 + token_ratio * 50
    if answer_matches:
        score += 20
    if media_kind_matches:
        score += 10
    score += min(category_overlap_count, 5)

    return {
        "site_question_id": external["site_question_id"],
        "url": external["url"],
        "prompt": external["prompt"],
        "accepted_answer": external.get("accepted_answer"),
        "categories": external.get("categories") or [],
        "structure_scope": external.get("structure_scope"),
        "question_media_kind": external.get("question_media_kind") or "none",
        "explanation_text": external.get("explanation_text"),
        "sequence_ratio": round(sequence_ratio, 4),
        "token_ratio": round(token_ratio, 4),
        "answer_matches": answer_matches,
        "media_kind_matches": media_kind_matches,
        "category_overlap": category_overlap,
        "category_overlap_count": category_overlap_count,
        "score": round(score, 2),
    }


def build_report() -> dict[str, Any]:
    review_queue = load_json(REMAINING_DIR / "shared_review_ambiguous_overlap.json")
    external_questions = load_json(PJ360_DIR / "external_questions.json")
    post_triage = {
        str(item["gov_id"]): item
        for item in load_json(PJ360_DIR / "post-triage" / "review_ambiguous_overlap.json")
    }

    gov_ids = [str(item["external_id"]) for item in review_queue]
    local_rows = fetch_local_rows(DEFAULT_DSN, gov_ids)
    local_groups = group_local_rows(local_rows)

    results: list[dict[str, Any]] = []
    top_candidate_counter: Counter[str] = Counter()

    for queue_item in review_queue:
        gov_id = str(queue_item["external_id"])
        local = local_groups[gov_id]
        candidates = []

        for external in external_questions:
            if external.get("question_type") and external["question_type"] != local["question_type"]:
                continue

            candidate = candidate_score(local, external)
            if candidate["token_ratio"] < 0.28 and candidate["sequence_ratio"] < 0.7:
                continue

            candidates.append(candidate)

        candidates.sort(
            key=lambda item: (
                -item["score"],
                not item["answer_matches"],
                not item["media_kind_matches"],
                -item["category_overlap_count"],
                item["site_question_id"],
            )
        )
        top_candidates = candidates[:5]
        if top_candidates:
            top_candidate_counter[top_candidates[0]["site_question_id"]] += 1

        reference = post_triage.get(gov_id, {})

        results.append(
            {
                "gov_id": gov_id,
                "prompt": local["prompt"],
                "question_type": local["question_type"],
                "correct_answer": local["correct_answer"],
                "accepted_answer_text": local["accepted_answer_text"],
                "categories": local["categories"],
                "question_media_kind": local["question_media_kind"],
                "existing_reference_site_question_id": reference.get("external_site_question_id"),
                "existing_reference_url": reference.get("external_url"),
                "top_candidates": top_candidates,
            }
        )

    results.sort(key=lambda item: int(item["gov_id"]))

    summary = {
        "dsn": DEFAULT_DSN.replace("password=prawkobit", "password=***"),
        "queue_count": len(review_queue),
        "with_candidates": sum(1 for item in results if item["top_candidates"]),
        "without_candidates": sum(1 for item in results if not item["top_candidates"]),
        "top_candidate_site_question_ids": top_candidate_counter.most_common(15),
    }

    return {
        "summary": summary,
        "results": results,
    }


def build_markdown(results: list[dict[str, Any]], summary: dict[str, Any]) -> str:
    lines = [
        "# Ambiguous Overlap Review",
        "",
        f"- Rekordy w kolejce: `{summary['queue_count']}`",
        f"- Z kandydatami: `{summary['with_candidates']}`",
        f"- Bez kandydatow: `{summary['without_candidates']}`",
        "",
    ]

    for item in results:
        lines.append(f"## {item['gov_id']}")
        lines.append("")
        lines.append(f"- Prompt: {item['prompt']}")
        lines.append(
            f"- Lokalnie: `{item['question_type']}` / answer `{item['correct_answer']}` / media `{item['question_media_kind']}` / kategorie `{', '.join(item['categories'])}`"
        )
        if item["existing_reference_site_question_id"]:
            lines.append(
                f"- Aktualna referencja post-triage: `{item['existing_reference_site_question_id']}`"
            )
        if not item["top_candidates"]:
            lines.append("- Brak kandydatow po filtrowaniu.")
            lines.append("")
            continue

        lines.append("- Top kandydaci:")
        for candidate in item["top_candidates"]:
            lines.append(
                "  - "
                f"`{candidate['site_question_id']}` score `{candidate['score']}` "
                f"answer_match `{candidate['answer_matches']}` media_match `{candidate['media_kind_matches']}` "
                f"cat_overlap `{candidate['category_overlap_count']}` :: {candidate['prompt']}"
            )
        lines.append("")

    return "\n".join(lines) + "\n"


def main() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    report = build_report()
    write_json(OUTPUT_DIR / "ambiguous-overlap-review.json", report)
    (OUTPUT_DIR / "ambiguous-overlap-review.md").write_text(
        build_markdown(report["results"], report["summary"]),
        encoding="utf-8",
    )
    print(json.dumps(report["summary"], ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

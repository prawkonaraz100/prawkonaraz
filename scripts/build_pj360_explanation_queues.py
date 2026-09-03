from __future__ import annotations

import argparse
import csv
import json
import os
import re
import unicodedata
from collections import Counter, defaultdict
from pathlib import Path
from typing import Any

import psycopg


DEFAULT_COMPARE_DIR = Path("output/analysis/pj360-compare")
DEFAULT_OUTPUT_DIR = DEFAULT_COMPARE_DIR / "queues"
DEFAULT_DB_DSN = os.environ.get(
    "PJ360_COMPARE_DB_DSN",
    "host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit",
)


def normalize_text(value: str) -> str:
    value = value or ""
    value = unicodedata.normalize("NFKC", value)
    value = value.replace("\xa0", " ")
    value = re.sub(r"\s+", " ", value)
    return value.strip()


def normalize_key(value: str) -> str:
    value = normalize_text(value).lower()
    value = value.replace("’", "'").replace("`", "'").replace("„", '"').replace("”", '"')
    value = value.replace("–", "-").replace("—", "-")
    return value


def loose_key(value: str) -> str:
    value = normalize_key(value)
    value = re.sub(r"[^\wąćęłńóśźż]+", "", value, flags=re.IGNORECASE)
    return value


def score_match(external: dict[str, Any], local: dict[str, Any], exact_prompt: bool) -> int:
    score = 0
    if exact_prompt:
        score += 100
    if normalize_key(external["accepted_answer"]) == normalize_key(local["accepted_answer"]):
        score += 20
    if (external.get("structure_scope") or "") == local["structure_scope"]:
        score += 10
    if external["question_media_kind"] == local["question_media_kind"]:
        score += 5
    score += len(set(external["categories"]) & set(local["categories"])) * 2
    return score


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def load_compare_artifacts(compare_dir: Path) -> tuple[list[dict[str, Any]], list[dict[str, Any]], dict[str, Any]]:
    external_questions = load_json(compare_dir / "external_questions.json")
    local_questions = load_json(compare_dir / "local_questions.json")
    report = load_json(compare_dir / "comparison_report.json")

    return external_questions, local_questions, report


def rebuild_matches(
    external_questions: list[dict[str, Any]],
    local_questions: list[dict[str, Any]],
) -> tuple[list[dict[str, Any]], list[dict[str, Any]]]:
    local_by_exact_prompt: dict[str, list[dict[str, Any]]] = defaultdict(list)
    local_by_loose_prompt: dict[str, list[dict[str, Any]]] = defaultdict(list)

    for question in local_questions:
        local_by_exact_prompt[normalize_key(question["prompt"])].append(question)
        local_by_loose_prompt[loose_key(question["prompt"])].append(question)

    matched_pairs: list[dict[str, Any]] = []
    local_matched_ids: set[str] = set()

    for external in external_questions:
        exact_candidates = local_by_exact_prompt.get(normalize_key(external["prompt"]), [])
        candidates = exact_candidates
        exact_prompt = True

        if not candidates:
            candidates = local_by_loose_prompt.get(loose_key(external["prompt"]), [])
            exact_prompt = False

        if not candidates:
            continue

        scored = sorted(
            (
                (
                    score_match(external, local, exact_prompt),
                    str(local["gov_id"]),
                    local,
                )
                for local in candidates
            ),
            reverse=True,
        )

        best_score, _, best_local = scored[0]
        ambiguous = len(scored) > 1 and scored[0][0] == scored[1][0]
        local_matched_ids.add(str(best_local["gov_id"]))

        matched_pairs.append(
            {
                "external": external,
                "local": best_local,
                "match": {
                    "exact_prompt": exact_prompt,
                    "score": best_score,
                    "ambiguous": ambiguous,
                    "answer_matches": normalize_key(external["accepted_answer"]) == normalize_key(best_local["accepted_answer"]),
                    "categories_match": sorted(external["categories"]) == sorted(best_local["categories"]),
                    "structure_scope_match": (external.get("structure_scope") or "") == best_local["structure_scope"],
                    "media_kind_match": external["question_media_kind"] == best_local["question_media_kind"],
                },
            }
        )

    local_only = [question for question in local_questions if str(question["gov_id"]) not in local_matched_ids]

    return matched_pairs, local_only


def fetch_pt_questions(db_dsn: str) -> list[dict[str, Any]]:
    query = """
        WITH base AS (
            SELECT
                COALESCE(q.metadata->>'government_question_id', q.external_id) AS gov_id,
                q.prompt,
                q.question_type,
                q.correct_answer,
                q.option_a,
                q.option_b,
                q.option_c,
                UPPER(COALESCE(q.metadata->>'structure_scope', 'PODSTAWOWY')) AS structure_scope,
                lc.code AS category_code,
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
            WHERE lc.code = 'PT'
        )
        SELECT
            gov_id,
            MIN(prompt) AS prompt,
            MIN(question_type) AS question_type,
            MIN(correct_answer) AS correct_answer,
            MIN(option_a) AS option_a,
            MIN(option_b) AS option_b,
            MIN(option_c) AS option_c,
            MIN(structure_scope) AS structure_scope,
            ARRAY_AGG(DISTINCT category_code ORDER BY category_code) AS categories,
            BOOL_OR(has_video) AS has_video,
            BOOL_OR(has_image) AS has_image
        FROM base
        GROUP BY gov_id
        ORDER BY gov_id::int NULLS LAST, gov_id;
    """

    with psycopg.connect(db_dsn) as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(query)
            rows = cur.fetchall()

    pt_questions: list[dict[str, Any]] = []

    for row in rows:
        media_kind = "none"
        if row["has_video"]:
            media_kind = "video"
        elif row["has_image"]:
            media_kind = "image"

        pt_questions.append(
            {
                "gov_id": str(row["gov_id"]),
                "prompt": normalize_text(row["prompt"]),
                "accepted_answer": normalize_text(row["option_a"] if row["correct_answer"] == "a" else row["option_b"] if row["correct_answer"] == "b" else row["option_c"] or ""),
                "categories": list(row["categories"] or []),
                "structure_scope": row["structure_scope"],
                "question_type": row["question_type"],
                "question_media_kind": media_kind,
                "option_a": normalize_text(row["option_a"] or "") or None,
                "option_b": normalize_text(row["option_b"] or "") or None,
                "option_c": normalize_text(row["option_c"] or "") or None,
            }
        )

    return pt_questions


def classify_queues(
    external_questions: list[dict[str, Any]],
    local_questions: list[dict[str, Any]],
    matched_pairs: list[dict[str, Any]],
    local_only: list[dict[str, Any]],
    pt_questions: list[dict[str, Any]],
) -> dict[str, Any]:
    external_prompt_counts = Counter(normalize_key(item["prompt"]) for item in external_questions)
    local_prompt_counts = Counter(normalize_key(item["prompt"]) for item in local_questions)
    external_by_exact_prompt: dict[str, list[dict[str, Any]]] = defaultdict(list)
    external_by_loose_prompt: dict[str, list[dict[str, Any]]] = defaultdict(list)

    for item in external_questions:
        external_by_exact_prompt[normalize_key(item["prompt"])].append(item)
        external_by_loose_prompt[loose_key(item["prompt"])].append(item)

    tier_a_safe_auto: list[dict[str, Any]] = []
    tier_b_review: list[dict[str, Any]] = []
    tier_c_manual: list[dict[str, Any]] = []

    review_reason_counts: Counter[str] = Counter()

    for item in matched_pairs:
        local = item["local"]
        external = item["external"]
        local_prompt_key = normalize_key(local["prompt"])
        external_prompt_key = normalize_key(external["prompt"])
        reasons: list[str] = []

        if not item["match"]["exact_prompt"]:
            reasons.append("loose_prompt_only")
        if item["match"]["ambiguous"]:
            reasons.append("ambiguous_best_match")
        if not item["match"]["answer_matches"]:
            reasons.append("answer_mismatch")
        if not item["match"]["categories_match"]:
            reasons.append("category_mismatch")
        if not item["match"]["structure_scope_match"]:
            reasons.append("structure_scope_mismatch")
        if not item["match"]["media_kind_match"]:
            reasons.append("media_kind_mismatch")
        if local_prompt_counts[local_prompt_key] > 1:
            reasons.append("local_prompt_duplicate")
        if external_prompt_counts[external_prompt_key] > 1:
            reasons.append("external_prompt_duplicate")

        queue_item = {
            "local": local,
            "external": {
                "site_question_id": external["site_question_id"],
                "url": external["url"],
                "prompt": external["prompt"],
                "accepted_answer": external["accepted_answer"],
                "categories": external["categories"],
                "structure_scope": external["structure_scope"],
                "question_media_kind": external["question_media_kind"],
                "explanation_text": external["explanation_text"],
                "explanation_sections": external["explanation_sections"],
                "explanation_video_url": external["explanation_video_url"],
            },
            "match": item["match"],
            "reasons": reasons,
        }

        if reasons:
            tier_b_review.append(queue_item)
            review_reason_counts.update(reasons)
        else:
            tier_a_safe_auto.append(queue_item)

    for local in local_only:
        exact_candidates = external_by_exact_prompt.get(normalize_key(local["prompt"]), [])
        loose_candidates = external_by_loose_prompt.get(loose_key(local["prompt"]), [])

        if exact_candidates or loose_candidates:
            exact = bool(exact_candidates)
            candidates = exact_candidates if exact else loose_candidates
            reasons = ["prompt_overlap_not_selected"]

            if not exact:
                reasons.append("loose_prompt_only")
            if local_prompt_counts[normalize_key(local["prompt"])] > 1:
                reasons.append("local_prompt_duplicate")

            review_reason_counts.update(reasons)
            tier_b_review.append(
                {
                    "local": local,
                    "external_candidates": [
                        {
                            "site_question_id": candidate["site_question_id"],
                            "url": candidate["url"],
                            "prompt": candidate["prompt"],
                            "accepted_answer": candidate["accepted_answer"],
                            "categories": candidate["categories"],
                            "structure_scope": candidate["structure_scope"],
                            "question_media_kind": candidate["question_media_kind"],
                            "explanation_text": candidate["explanation_text"],
                            "explanation_sections": candidate["explanation_sections"],
                            "explanation_video_url": candidate["explanation_video_url"],
                        }
                        for candidate in candidates
                    ],
                    "reasons": reasons,
                }
            )
        else:
            tier_c_manual.append(
                {
                    "local": local,
                    "reasons": ["no_pj360_prompt_match"],
                }
            )

    exact_prompt_overlap_local_count = sum(
        1 for item in local_questions if normalize_key(item["prompt"]) in external_by_exact_prompt
    )
    loose_prompt_overlap_local_count = sum(
        1 for item in local_questions if loose_key(item["prompt"]) in external_by_loose_prompt
    )

    return {
        "summary": {
            "external_questions_count": len(external_questions),
            "local_shared_questions_count": len(local_questions),
            "local_pt_questions_count": len(pt_questions),
            "exact_prompt_overlap_local_count": exact_prompt_overlap_local_count,
            "loose_prompt_overlap_local_count": loose_prompt_overlap_local_count,
            "tier_a_safe_auto_count": len(tier_a_safe_auto),
            "tier_b_review_count": len(tier_b_review),
            "tier_c_manual_count": len(tier_c_manual),
            "tier_pt_manual_count": len(pt_questions),
            "review_reason_counts": dict(sorted(review_reason_counts.items())),
            "local_prompt_duplicate_count": sum(1 for count in local_prompt_counts.values() if count > 1),
            "local_rows_in_duplicate_prompts_count": sum(count for count in local_prompt_counts.values() if count > 1),
        },
        "tier_a_safe_auto": tier_a_safe_auto,
        "tier_b_review": tier_b_review,
        "tier_c_manual": tier_c_manual,
        "tier_pt_manual": pt_questions,
    }


def write_json(path: Path, data: Any) -> None:
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


def flatten_safe_rows(items: list[dict[str, Any]]) -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    for item in items:
        rows.append(
            {
                "gov_id": item["local"]["gov_id"],
                "prompt": item["local"]["prompt"],
                "categories": ",".join(item["local"]["categories"]),
                "question_media_kind": item["local"]["question_media_kind"],
                "structure_scope": item["local"]["structure_scope"],
                "site_question_id": item["external"]["site_question_id"] or "",
                "external_url": item["external"]["url"],
                "external_media_kind": item["external"]["question_media_kind"],
                "has_explanation_video": "yes" if item["external"]["explanation_video_url"] else "no",
            }
        )
    return rows


def flatten_review_rows(items: list[dict[str, Any]]) -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    for item in items:
        external = item.get("external")
        external_candidates = item.get("external_candidates", [])
        rows.append(
            {
                "gov_id": item["local"]["gov_id"],
                "prompt": item["local"]["prompt"],
                "categories": ",".join(item["local"]["categories"]),
                "question_media_kind": item["local"]["question_media_kind"],
                "structure_scope": item["local"]["structure_scope"],
                "reasons": ",".join(item["reasons"]),
                "site_question_id": (external or {}).get("site_question_id", ""),
                "external_url": (external or {}).get("url", ""),
                "external_candidate_count": len(external_candidates),
            }
        )
    return rows


def flatten_manual_rows(items: list[dict[str, Any]]) -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    for item in items:
        local = item["local"] if "local" in item else item
        rows.append(
            {
                "gov_id": local["gov_id"],
                "prompt": local["prompt"],
                "categories": ",".join(local["categories"]),
                "question_media_kind": local["question_media_kind"],
                "structure_scope": local["structure_scope"],
            }
        )
    return rows


def build_summary_markdown(summary: dict[str, Any]) -> str:
    lines = [
        "# Kolejki adaptacji wyjasnien PJ360",
        "",
        "## Podsumowanie",
        "",
        f"- pytania PJ360: `{summary['external_questions_count']}`",
        f"- nasze pytania w 11 wspolnych kategoriach: `{summary['local_shared_questions_count']}`",
        f"- nasze pytania `PT`: `{summary['local_pt_questions_count']}`",
        f"- pytania z exact prompt overlap: `{summary['exact_prompt_overlap_local_count']}`",
        f"- pytania z loose prompt overlap: `{summary['loose_prompt_overlap_local_count']}`",
        f"- `Tier A / safe auto`: `{summary['tier_a_safe_auto_count']}`",
        f"- `Tier B / review`: `{summary['tier_b_review_count']}`",
        f"- `Tier C / manual`: `{summary['tier_c_manual_count']}`",
        f"- `Tier PT / manual`: `{summary['tier_pt_manual_count']}`",
        f"- duplikowane prompty lokalne: `{summary['local_prompt_duplicate_count']}`",
        f"- wiersze lokalne pod duplikowanymi promptami: `{summary['local_rows_in_duplicate_prompts_count']}`",
        "",
        "## Powody trafienia do review",
        "",
    ]

    for reason, count in summary["review_reason_counts"].items():
        lines.append(f"- `{reason}`: `{count}`")

    lines.append("")
    return "\n".join(lines)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Buduje kolejki adaptacji wyjaśnień PJ360.")
    parser.add_argument(
        "--compare-dir",
        type=Path,
        default=DEFAULT_COMPARE_DIR,
        help="Katalog z external_questions.json, local_questions.json i comparison_report.json.",
    )
    parser.add_argument(
        "--output-dir",
        type=Path,
        default=DEFAULT_OUTPUT_DIR,
        help="Katalog wyjściowy dla kolejek.",
    )
    parser.add_argument(
        "--db-dsn",
        default=DEFAULT_DB_DSN,
        help="DSN do PostgreSQL do pobrania pytań PT.",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    external_questions, local_questions, _report = load_compare_artifacts(args.compare_dir)
    matched_pairs, local_only = rebuild_matches(external_questions, local_questions)
    pt_questions = fetch_pt_questions(args.db_dsn)
    queues = classify_queues(external_questions, local_questions, matched_pairs, local_only, pt_questions)

    args.output_dir.mkdir(parents=True, exist_ok=True)

    write_json(args.output_dir / "summary.json", queues["summary"])
    write_json(args.output_dir / "tier-a-safe-auto.json", queues["tier_a_safe_auto"])
    write_json(args.output_dir / "tier-b-review.json", queues["tier_b_review"])
    write_json(args.output_dir / "tier-c-manual.json", queues["tier_c_manual"])
    write_json(args.output_dir / "tier-pt-manual.json", queues["tier_pt_manual"])

    write_csv(args.output_dir / "tier-a-safe-auto.csv", flatten_safe_rows(queues["tier_a_safe_auto"]))
    write_csv(args.output_dir / "tier-b-review.csv", flatten_review_rows(queues["tier_b_review"]))
    write_csv(args.output_dir / "tier-c-manual.csv", flatten_manual_rows(queues["tier_c_manual"]))
    write_csv(args.output_dir / "tier-pt-manual.csv", flatten_manual_rows(queues["tier_pt_manual"]))

    (args.output_dir / "README.md").write_text(build_summary_markdown(queues["summary"]), encoding="utf-8")

    print(json.dumps(queues["summary"], ensure_ascii=False, indent=2))
    print(f"Zapisano kolejki do: {args.output_dir}")


if __name__ == "__main__":
    main()

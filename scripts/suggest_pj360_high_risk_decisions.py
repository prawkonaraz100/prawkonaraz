from __future__ import annotations

import argparse
import csv
import json
import os
import re
import unicodedata
from collections import Counter, defaultdict
from difflib import SequenceMatcher
from pathlib import Path
from typing import Any

import psycopg


DEFAULT_BASE_DIR = Path("output/analysis/pj360-compare")
DEFAULT_OUTPUT_DIR = DEFAULT_BASE_DIR / "high-risk-review"
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


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def write_csv(path: Path, rows: list[dict[str, Any]]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    if not rows:
        path.write_text("", encoding="utf-8")
        return

    fieldnames: list[str] = []
    seen: set[str] = set()
    for row in rows:
        for key in row.keys():
            if key not in seen:
                seen.add(key)
                fieldnames.append(key)

    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)


def fetch_local_media_metadata(db_dsn: str, gov_ids: list[str]) -> dict[str, dict[str, Any]]:
    query = """
        WITH question_base AS (
            SELECT
                q.id AS question_id,
                COALESCE(q.metadata->>'government_question_id', q.external_id) AS gov_id,
                q.prompt,
                q.correct_answer,
                q.option_a,
                q.option_b,
                q.option_c,
                lc.code AS category_code
            FROM questions q
            JOIN license_categories lc ON lc.id = q.license_category_id
            WHERE COALESCE(q.metadata->>'government_question_id', q.external_id) = ANY(%s)
        )
        SELECT
            qb.gov_id,
            ARRAY_AGG(DISTINCT qb.question_id ORDER BY qb.question_id) AS question_ids,
            ARRAY_AGG(DISTINCT qb.category_code ORDER BY qb.category_code) AS categories,
            ARRAY_REMOVE(
                ARRAY_AGG(
                    DISTINCT CASE
                        WHEN qm.path IS NULL THEN NULL
                        ELSE CONCAT(qm.kind, ':', qm.path)
                    END
                ),
                NULL
            ) AS media_entries,
            ARRAY_REMOVE(
                ARRAY_AGG(
                    DISTINCT CASE
                        WHEN qm.poster_path IS NULL THEN NULL
                        ELSE qm.poster_path
                    END
                ),
                NULL
            ) AS poster_entries
        FROM question_base qb
        LEFT JOIN question_media qm ON qm.question_id = qb.question_id
        GROUP BY qb.gov_id
        ORDER BY qb.gov_id::int NULLS LAST, qb.gov_id;
    """

    with psycopg.connect(db_dsn) as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(query, (gov_ids,))
            rows = cur.fetchall()

    return {str(row["gov_id"]): dict(row) for row in rows}


def build_prompt_groups(local_questions: list[dict[str, Any]]) -> dict[str, list[dict[str, Any]]]:
    by_prompt: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for item in local_questions:
        by_prompt[normalize_key(item["prompt"])].append(item)
    return by_prompt


def answer_similarity(left: str, right: str) -> float:
    return SequenceMatcher(None, normalize_key(left), normalize_key(right)).ratio()


def suggest_decision(item: dict[str, Any], prompt_group: list[dict[str, Any]]) -> tuple[str, str]:
    reasons = set(item["reasons"])
    local = item["local"]
    external = item["external"]

    if "answer_mismatch" in reasons:
        similarity = answer_similarity(local["accepted_answer"], external["accepted_answer"])
        if not item["match"]["ambiguous"] and similarity >= 0.75:
            return "likely_editorial_answer_mismatch", "Różnica wygląda na literówkę, odmianę albo interpunkcję, a nie realny konflikt odpowiedzi."
        return "manual_answer_conflict", "Odpowiedź różni się znacząco i trzeba ręcznie rozstrzygnąć, czy to kolizja promptu lub medium."

    if "media_kind_mismatch" in reasons and len(reasons) == 1:
        return "review_media_presence_only", "Treść i odpowiedź są zgodne, ale PJ360 nie pokazuje tego samego rodzaju medium co nasza baza."

    if "structure_scope_mismatch" in reasons and len(reasons) == 1:
        return "usable_text_scope_conflict", "Wyjaśnienie może być merytorycznie użyteczne, ale scope trzeba traktować jako osobny konflikt klasyfikacyjny."

    if "category_mismatch" in reasons and "ambiguous_best_match" not in reasons:
        return "review_external_category_policy", "Wyjaśnienie wygląda użytecznie, ale PJ360 ma inną politykę kategorii niż nasz import gov.pl."

    if "ambiguous_best_match" in reasons:
        same_prompt_answer_set = {normalize_key(candidate["accepted_answer"]) for candidate in prompt_group}
        same_prompt_media_set = {candidate["question_media_kind"] for candidate in prompt_group}

        if len(same_prompt_answer_set) == 1 and len(same_prompt_media_set) == 1:
            return "duplicate_prompt_same_answer_media", "Duplikat promptu wygląda na mniej groźny, bo kandydaci mają tę samą odpowiedź i ten sam rodzaj medium."

        return "needs_media_disambiguation", "To wygląda na prawdziwą kolizję promptu i trzeba rozstrzygnąć po obrazie, wideo albo ścieżce medium."

    return "manual_review", "Przypadek nie mieści się w prostym wzorcu i wymaga ręcznego sprawdzenia."


def flatten_row(item: dict[str, Any]) -> dict[str, Any]:
    external = item["external"]
    local_media = item["local_media"]
    return {
        "gov_id": item["local"]["gov_id"],
        "prompt": item["local"]["prompt"],
        "categories": ",".join(item["local"]["categories"]),
        "question_media_kind": item["local"]["question_media_kind"],
        "structure_scope": item["local"]["structure_scope"],
        "accepted_answer": item["local"]["accepted_answer"],
        "reasons": ",".join(item["reasons"]),
        "recommended_decision": item["recommended_decision"],
        "decision_note": item["decision_note"],
        "external_site_question_id": external.get("site_question_id", ""),
        "external_url": external.get("url", ""),
        "external_media_kind": external.get("question_media_kind", ""),
        "external_media_url": external.get("question_media_url", ""),
        "local_question_ids": ",".join(str(value) for value in local_media.get("question_ids", []) or []),
        "local_media_entries": " | ".join(local_media.get("media_entries", []) or []),
        "local_poster_entries": " | ".join(local_media.get("poster_entries", []) or []),
        "same_prompt_gov_ids": ",".join(item["same_prompt_gov_ids"]),
    }


def build_summary(items: list[dict[str, Any]]) -> dict[str, Any]:
    decision_counts = Counter(item["recommended_decision"] for item in items)
    reason_counts = Counter(reason for item in items for reason in item["reasons"])
    return {
        "high_risk_total": len(items),
        "recommended_decision_counts": dict(sorted(decision_counts.items())),
        "reason_counts": dict(sorted(reason_counts.items())),
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Buduje sugestie decyzji dla Tier B high-risk.")
    parser.add_argument(
        "--base-dir",
        type=Path,
        default=DEFAULT_BASE_DIR,
        help="Katalog bazowy analizy pj360-compare.",
    )
    parser.add_argument(
        "--output-dir",
        type=Path,
        default=DEFAULT_OUTPUT_DIR,
        help="Katalog wyjsciowy dla enriched high-risk review.",
    )
    parser.add_argument(
        "--db-dsn",
        default=DEFAULT_DB_DSN,
        help="DSN do PostgreSQL.",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    high_risk = load_json(args.base_dir / "review-packets" / "tier-b-high-risk.json")
    local_questions = load_json(args.base_dir / "local_questions.json")

    prompt_groups = build_prompt_groups(local_questions)
    relevant_gov_ids = sorted(
        {
            str(item["local"]["gov_id"])
            for item in high_risk
        }
        | {
            str(candidate["gov_id"])
            for item in high_risk
            for candidate in prompt_groups[normalize_key(item["local"]["prompt"])]
        }
    )

    local_media_metadata = fetch_local_media_metadata(args.db_dsn, relevant_gov_ids)

    enriched: list[dict[str, Any]] = []
    for item in high_risk:
        prompt_group = prompt_groups[normalize_key(item["local"]["prompt"])]
        recommended_decision, decision_note = suggest_decision(item, prompt_group)

        enriched.append(
            {
                **item,
                "recommended_decision": recommended_decision,
                "decision_note": decision_note,
                "local_media": local_media_metadata.get(str(item["local"]["gov_id"]), {}),
                "same_prompt_gov_ids": [str(candidate["gov_id"]) for candidate in prompt_group],
            }
        )

    summary = build_summary(enriched)

    args.output_dir.mkdir(parents=True, exist_ok=True)
    write_json(args.output_dir / "high-risk-enriched.json", enriched)
    write_json(args.output_dir / "high-risk-summary.json", summary)
    write_csv(args.output_dir / "high-risk-enriched.csv", [flatten_row(item) for item in enriched])

    print(json.dumps(summary, ensure_ascii=False, indent=2))
    print(f"Zapisano high-risk review do: {args.output_dir}")


if __name__ == "__main__":
    main()

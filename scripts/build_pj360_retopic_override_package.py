from __future__ import annotations

import argparse
import json
from collections import Counter, defaultdict
from datetime import UTC, datetime
from pathlib import Path
from typing import Any

import psycopg


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_INPUT_DIR = ROOT / "output" / "analysis" / "pj360-exact-topic-membership"
DEFAULT_OUTPUT_DIR = ROOT / "resources" / "topic-overrides"
DB_DSN = "host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit"


def load_json(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Build a review-only override package from PJ360 retopic candidate shortlist."
    )
    parser.add_argument("--category", required=True, help="Kod kategorii, np. B.")
    parser.add_argument(
        "--input-json",
        default="",
        help="Sciezka do pliku <category>-retopic-candidate-shortlist.json. Domyslnie katalog exact-topic-membership.",
    )
    parser.add_argument(
        "--output-json",
        default="",
        help="Sciezka do review-only package JSON. Domyslnie resources/topic-overrides/pj360-<category>-retopic-package.json",
    )
    return parser.parse_args()


def shortlist_path_for(category_code: str, custom: str) -> Path:
    if custom.strip():
        return Path(custom)

    return DEFAULT_INPUT_DIR / f"{category_code.lower()}-retopic-candidate-shortlist.json"


def output_path_for(category_code: str, custom: str) -> Path:
    if custom.strip():
        return Path(custom)

    return DEFAULT_OUTPUT_DIR / f"pj360-{category_code.lower()}-retopic-package.json"


def fetch_active_overrides(category_code: str) -> dict[tuple[str, str, str], dict[str, Any]]:
    query = """
        SELECT license_category_code, LOWER(source) AS source, external_id, question_topic_key, reason, metadata
        FROM question_topic_overrides
        WHERE is_active = TRUE
          AND license_category_code = %s
    """

    with psycopg.connect(DB_DSN) as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(query, (category_code,))
            rows = cur.fetchall()

    return {
        (
            str(row["license_category_code"]).strip().upper(),
            str(row["source"]).strip().lower(),
            str(row["external_id"]).strip(),
        ): {
            "question_topic_key": str(row["question_topic_key"] or "").strip(),
            "reason": str(row.get("reason") or "").strip(),
            "metadata": row.get("metadata") or {},
        }
        for row in rows
    }


def build_package(
    payload: dict[str, Any],
    category_code: str,
    source_path: Path,
    active_overrides: dict[tuple[str, str, str], dict[str, Any]],
) -> dict[str, Any]:
    rows = payload.get("records", [])
    grouped: dict[tuple[str, str, str], list[dict[str, Any]]] = defaultdict(list)

    for row in rows:
        source = str(row.get("source") or "").strip().lower()
        external_id = str(row.get("external_id") or "").strip()
        target_topic_key = str(row.get("target_topic_key") or "").strip()

        if source == "" or external_id == "" or target_topic_key == "":
            continue

        grouped[(category_code, source, external_id)].append(row)

    overrides: list[dict[str, Any]] = []
    skipped_conflicts: list[dict[str, Any]] = []
    skipped_same_topic: list[dict[str, Any]] = []
    skipped_existing_override_same_target: list[dict[str, Any]] = []
    skipped_existing_override_conflicts: list[dict[str, Any]] = []
    target_counter = Counter()
    source_counter = Counter()

    for stable_key, members in sorted(grouped.items()):
        target_keys = sorted({str(member.get("target_topic_key") or "").strip() for member in members if member.get("target_topic_key")})
        current_topic_keys = sorted({str(member.get("current_topic_key") or "").strip() for member in members if member.get("current_topic_key")})

        if len(target_keys) != 1:
            skipped_conflicts.append(
                {
                    "stable_identity": {
                        "license_category_code": stable_key[0],
                        "source": stable_key[1],
                        "external_id": stable_key[2],
                    },
                    "target_topic_keys": target_keys,
                    "current_topic_keys": current_topic_keys,
                    "question_ids": sorted({int(member["question_id"]) for member in members if member.get("question_id") is not None}),
                    "records": members,
                }
            )
            continue

        target_topic_key = target_keys[0]

        if len(current_topic_keys) == 1 and current_topic_keys[0] == target_topic_key:
            skipped_same_topic.append(
                {
                    "stable_identity": {
                        "license_category_code": stable_key[0],
                        "source": stable_key[1],
                        "external_id": stable_key[2],
                    },
                    "target_topic_key": target_topic_key,
                    "records": members,
                }
            )
            continue

        existing_override = active_overrides.get(stable_key)
        if existing_override is not None:
            existing_target = str(existing_override.get("question_topic_key") or "").strip()

            if existing_target == target_topic_key:
                skipped_existing_override_same_target.append(
                    {
                        "stable_identity": {
                            "license_category_code": stable_key[0],
                            "source": stable_key[1],
                            "external_id": stable_key[2],
                        },
                        "question_topic_key": target_topic_key,
                        "records": members,
                    }
                )
                continue

            skipped_existing_override_conflicts.append(
                {
                    "stable_identity": {
                        "license_category_code": stable_key[0],
                        "source": stable_key[1],
                        "external_id": stable_key[2],
                    },
                    "existing_override_topic_key": existing_target,
                    "candidate_topic_key": target_topic_key,
                    "records": members,
                }
            )
            continue

        prompt_excerpt = ""
        accepted_answer = ""
        question_media_kind = ""
        main_media_originals: list[str] = []
        question_ids: list[int] = []

        for member in members:
            if prompt_excerpt == "":
                prompt_excerpt = str(member.get("prompt") or "")[:180]
            if accepted_answer == "":
                accepted_answer = str(member.get("accepted_answer") or "")
            if question_media_kind == "":
                question_media_kind = str(member.get("question_media_kind") or "")
            media_original = str(member.get("main_media_original") or "").strip()
            if media_original:
                main_media_originals.append(media_original)
            if member.get("question_id") is not None:
                question_ids.append(int(member["question_id"]))

        current_topic_key = current_topic_keys[0] if len(current_topic_keys) == 1 else "mixed_current_topics"
        target_topic_label = next(
            (str(member.get("target_topic_label") or "") for member in members if member.get("target_topic_label")),
            "",
        )

        overrides.append(
            {
                "license_category_code": stable_key[0],
                "source": stable_key[1],
                "external_id": stable_key[2],
                "question_topic_key": target_topic_key,
                "reason": f"PJ360 exact retopic dla {stable_key[0]}: {target_topic_label or target_topic_key}",
                "metadata": {
                    "workflow": "pj360_exact_retopic",
                    "source_shortlist_path": str(source_path),
                    "current_topic_key": current_topic_key,
                    "target_topic_key": target_topic_key,
                    "target_topic_label": target_topic_label,
                    "question_ids": sorted(set(question_ids)),
                    "record_count": len(members),
                    "prompt_excerpt": prompt_excerpt,
                    "accepted_answer": accepted_answer,
                    "question_media_kind": question_media_kind,
                    "main_media_originals": sorted(set(main_media_originals)),
                },
            }
        )
        target_counter[target_topic_key] += 1
        source_counter[current_topic_key] += 1

    return {
        "generated_at": datetime.now(UTC).isoformat(),
        "workflow": "pj360_exact_retopic",
        "category": category_code,
        "source_shortlist_path": str(source_path),
        "exported_overrides_count": len(overrides),
        "skipped_conflict_count": len(skipped_conflicts),
        "skipped_same_topic_count": len(skipped_same_topic),
        "skipped_existing_override_same_target_count": len(skipped_existing_override_same_target),
        "skipped_existing_override_conflict_count": len(skipped_existing_override_conflicts),
        "summary": {
            "target_topic_counts": dict(target_counter.most_common()),
            "source_topic_counts": dict(source_counter.most_common()),
        },
        "overrides": overrides,
        "skipped_conflicts": skipped_conflicts,
        "skipped_same_topic": skipped_same_topic,
        "skipped_existing_override_same_target": skipped_existing_override_same_target,
        "skipped_existing_override_conflicts": skipped_existing_override_conflicts,
    }


def main() -> None:
    args = parse_args()
    category_code = args.category.strip().upper()
    input_path = shortlist_path_for(category_code, args.input_json)
    output_path = output_path_for(category_code, args.output_json)

    payload = load_json(input_path)
    active_overrides = fetch_active_overrides(category_code)
    package = build_package(payload, category_code, input_path, active_overrides)

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(package, ensure_ascii=False, indent=2), encoding="utf-8")

    print(
        json.dumps(
            {
                "category": category_code,
                "exported_overrides": package["exported_overrides_count"],
                "skipped_conflicts": package["skipped_conflict_count"],
                "skipped_same_topic": package["skipped_same_topic_count"],
                "skipped_existing_override_same_target": package["skipped_existing_override_same_target_count"],
                "skipped_existing_override_conflicts": package["skipped_existing_override_conflict_count"],
                "output_path": str(output_path),
            },
            ensure_ascii=False,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()

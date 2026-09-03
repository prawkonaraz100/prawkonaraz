from __future__ import annotations

import argparse
import json
from collections import Counter
from datetime import UTC, datetime
from pathlib import Path
from typing import Any

import psycopg


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_CONFLICTS_DIR = ROOT / "output" / "analysis" / "pj360-exact-topic-membership"
DEFAULT_PACKAGE_DIR = ROOT / "resources" / "topic-overrides"
DB_DSN = "host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit"


def load_json(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Build a delta-aware PJ360 conflict-resolution package from true retopic conflicts."
    )
    parser.add_argument("--category", required=True, help="Kod kategorii, np. B.")
    parser.add_argument(
        "--conflicts-json",
        default="",
        help="Sciezka do pliku <category>-retopic-true-conflicts.json.",
    )
    parser.add_argument(
        "--diff-json",
        default="",
        help="Sciezka do pliku <category>-exact-vs-local-topic-membership-diff.json.",
    )
    parser.add_argument(
        "--output-json",
        default="",
        help="Sciezka do review-only package JSON.",
    )
    return parser.parse_args()


def default_conflicts_path(category_code: str, custom: str) -> Path:
    if custom.strip():
        return Path(custom)

    return DEFAULT_CONFLICTS_DIR / f"{category_code.lower()}-retopic-true-conflicts.json"


def default_diff_path(category_code: str, custom: str) -> Path:
    if custom.strip():
        return Path(custom)

    return DEFAULT_CONFLICTS_DIR / f"{category_code.lower()}-exact-vs-local-topic-membership-diff.json"


def default_output_path(category_code: str, custom: str) -> Path:
    if custom.strip():
        return Path(custom)

    return DEFAULT_PACKAGE_DIR / f"pj360-{category_code.lower()}-delta-aware-conflict-package.json"


def diff_summary_by_topic(diff_payload: dict[str, Any]) -> dict[str, dict[str, int]]:
    summary: dict[str, dict[str, int]] = {}

    for row in diff_payload.get("topics", []):
        topic_key = str(row.get("topic_key") or "").strip()
        if topic_key == "":
            continue
        summary[topic_key] = {
            "target": int(row.get("pj360_count") or 0),
            "local": int(row.get("local_count") or 0),
            "remaining": int(row.get("pj360_count") or 0) - int(row.get("local_count") or 0),
        }

    return summary


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
    category_code: str,
    conflicts_payload: dict[str, Any],
    diff_payload: dict[str, Any],
    conflicts_path: Path,
    diff_path: Path,
    active_overrides: dict[tuple[str, str, str], dict[str, Any]],
) -> dict[str, Any]:
    conflict_rows = conflicts_payload.get("records", [])
    topic_state = diff_summary_by_topic(diff_payload)
    remaining = {topic_key: payload["remaining"] for topic_key, payload in topic_state.items()}

    sortable_rows: list[tuple[int, int, str, dict[str, Any]]] = []
    for row in conflict_rows:
        target_keys = [str(topic).strip() for topic in row.get("target_topic_keys", []) if str(topic).strip()]
        if len(target_keys) < 2:
            continue
        deficits = sorted((remaining.get(topic_key, 0) for topic_key in target_keys), reverse=True)
        top_deficit = deficits[0]
        second_deficit = deficits[1] if len(deficits) > 1 else 0
        margin = top_deficit - second_deficit
        stable_identity = row.get("stable_identity") or {}
        external_id = str(stable_identity.get("external_id") or "")
        sortable_rows.append((margin, top_deficit, external_id, row))

    sortable_rows.sort(key=lambda item: (item[0], item[1], item[2]), reverse=True)

    overrides: list[dict[str, Any]] = []
    manual_conflicts: list[dict[str, Any]] = []
    skipped_existing_override_same_target: list[dict[str, Any]] = []
    skipped_existing_override_conflicts: list[dict[str, Any]] = []
    recommended_counter = Counter()
    current_counter = Counter()

    for _, _, _, row in sortable_rows:
        stable_identity = row.get("stable_identity") or {}
        target_keys = [str(topic).strip() for topic in row.get("target_topic_keys", []) if str(topic).strip()]
        current_topic_keys = [str(topic).strip() for topic in row.get("current_topic_keys", []) if str(topic).strip()]
        current_topic_key = current_topic_keys[0] if len(current_topic_keys) == 1 else "mixed_current_topics"
        stable_key = (
            str(stable_identity.get("license_category_code") or category_code).strip().upper(),
            str(stable_identity.get("source") or "").strip().lower(),
            str(stable_identity.get("external_id") or "").strip(),
        )

        candidate_rows = sorted(
            (
                {
                    "topic_key": topic_key,
                    "remaining_before": remaining.get(topic_key, 0),
                }
                for topic_key in target_keys
            ),
            key=lambda item: (item["remaining_before"], item["topic_key"]),
            reverse=True,
        )

        top_remaining = candidate_rows[0]["remaining_before"]
        top_candidates = [item for item in candidate_rows if item["remaining_before"] == top_remaining]

        if top_remaining <= 0 or len(top_candidates) != 1:
            manual_conflicts.append(
                {
                    "stable_identity": stable_identity,
                    "current_topic_keys": current_topic_keys,
                    "candidate_topics": candidate_rows,
                    "records": row.get("records", []),
                }
            )
            continue

        chosen = top_candidates[0]
        target_topic_key = str(chosen["topic_key"])
        existing_override = active_overrides.get(stable_key)

        if existing_override is not None:
            existing_target = str(existing_override.get("question_topic_key") or "").strip()

            if existing_target == target_topic_key:
                skipped_existing_override_same_target.append(
                    {
                        "stable_identity": stable_identity,
                        "question_topic_key": target_topic_key,
                        "candidate_topics": candidate_rows,
                        "records": row.get("records", []),
                    }
                )
                continue

            skipped_existing_override_conflicts.append(
                {
                    "stable_identity": stable_identity,
                    "existing_override_topic_key": existing_target,
                    "candidate_topic_key": target_topic_key,
                    "candidate_topics": candidate_rows,
                    "records": row.get("records", []),
                }
            )
            continue

        target_topic_label = next(
            (
                str(member.get("target_topic_label") or "")
                for member in row.get("records", [])
                if str(member.get("target_topic_key") or "").strip() == target_topic_key
            ),
            "",
        )

        prompt_excerpt = ""
        accepted_answer = ""
        question_media_kind = ""
        main_media_originals: list[str] = []
        question_ids: list[int] = []

        for member in row.get("records", []):
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

        overrides.append(
            {
                "license_category_code": str(stable_identity.get("license_category_code") or category_code),
                "source": str(stable_identity.get("source") or "").strip().lower(),
                "external_id": str(stable_identity.get("external_id") or "").strip(),
                "question_topic_key": target_topic_key,
                "reason": f"PJ360 delta-aware conflict resolution dla {category_code}: {target_topic_label or target_topic_key}",
                "metadata": {
                    "workflow": "pj360_delta_aware_conflict_resolution",
                    "source_conflicts_path": str(conflicts_path),
                    "source_diff_path": str(diff_path),
                    "current_topic_key": current_topic_key,
                    "candidate_topics_before": candidate_rows,
                    "resolved_by": "highest_remaining_deficit_unique",
                    "question_ids": sorted(set(question_ids)),
                    "record_count": len(row.get("records", [])),
                    "prompt_excerpt": prompt_excerpt,
                    "accepted_answer": accepted_answer,
                    "question_media_kind": question_media_kind,
                    "main_media_originals": sorted(set(main_media_originals)),
                },
            }
        )
        recommended_counter[target_topic_key] += 1
        current_counter[current_topic_key] += 1

        remaining[target_topic_key] = remaining.get(target_topic_key, 0) - 1
        remaining[current_topic_key] = remaining.get(current_topic_key, 0) + 1

    touched_topic_keys = set(recommended_counter.keys()) | set(current_counter.keys())
    touched_topics_after = {
        topic_key: {
            "remaining_after": remaining.get(topic_key, 0),
            "remaining_before": topic_state.get(topic_key, {}).get("remaining", 0),
            "target": topic_state.get(topic_key, {}).get("target", 0),
            "local_before": topic_state.get(topic_key, {}).get("local", 0),
            "local_after": topic_state.get(topic_key, {}).get("target", 0) - remaining.get(topic_key, 0),
        }
        for topic_key in sorted(touched_topic_keys)
    }

    return {
        "generated_at": datetime.now(UTC).isoformat(),
        "workflow": "pj360_delta_aware_conflict_resolution",
        "category": category_code,
        "source_conflicts_path": str(conflicts_path),
        "source_diff_path": str(diff_path),
        "exported_overrides_count": len(overrides),
        "manual_conflict_count": len(manual_conflicts),
        "skipped_existing_override_same_target_count": len(skipped_existing_override_same_target),
        "skipped_existing_override_conflict_count": len(skipped_existing_override_conflicts),
        "summary": {
            "recommended_target_topic_counts": dict(recommended_counter.most_common()),
            "recommended_source_topic_counts": dict(current_counter.most_common()),
            "touched_topics_after": touched_topics_after,
        },
        "overrides": overrides,
        "manual_conflicts": manual_conflicts,
        "skipped_existing_override_same_target": skipped_existing_override_same_target,
        "skipped_existing_override_conflicts": skipped_existing_override_conflicts,
    }


def main() -> None:
    args = parse_args()
    category_code = args.category.strip().upper()
    conflicts_path = default_conflicts_path(category_code, args.conflicts_json)
    diff_path = default_diff_path(category_code, args.diff_json)
    output_path = default_output_path(category_code, args.output_json)

    conflicts_payload = load_json(conflicts_path)
    diff_payload = load_json(diff_path)
    active_overrides = fetch_active_overrides(category_code)
    package = build_package(
        category_code,
        conflicts_payload,
        diff_payload,
        conflicts_path,
        diff_path,
        active_overrides,
    )

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(package, ensure_ascii=False, indent=2), encoding="utf-8")

    print(
        json.dumps(
            {
                "category": category_code,
                "exported_overrides": package["exported_overrides_count"],
                "manual_conflicts": package["manual_conflict_count"],
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

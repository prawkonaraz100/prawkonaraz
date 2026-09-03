from __future__ import annotations

import csv
import json
import os
from collections import Counter, defaultdict
from pathlib import Path
from typing import Any

try:
    import psycopg  # type: ignore
except ModuleNotFoundError:  # pragma: no cover - local fallback
    psycopg = None
    import psycopg2  # type: ignore
    from psycopg2.extras import RealDictCursor  # type: ignore


ROOT = Path(__file__).resolve().parents[1]
PJ360_DIR = ROOT / "output" / "analysis" / "pj360-compare"
OUTPUT_DIR = ROOT / "output" / "analysis" / "remaining-explanations"

DEFAULT_DSN = (
    os.getenv("PJ360_REMAINING_DB_DSN")
    or os.getenv("PJ360_COMPARE_DB_DSN")
    or "host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit"
)


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def load_gov_ids_from_queue(path: Path, key: str = "gov_id") -> set[str]:
    rows = load_json(path)
    gov_ids: set[str] = set()

    for row in rows:
        if key in row and row[key]:
            gov_ids.add(str(row[key]))
            continue

        local = row.get("local")
        if isinstance(local, dict) and local.get(key):
            gov_ids.add(str(local[key]))

    return gov_ids


def load_publish_ready_gov_ids(path: Path) -> set[str]:
    rows = load_json(path)
    return {
        str(row["gov_id"])
        for row in rows
        if row.get("publish_ready") and row.get("gov_id") is not None
    }


def build_bucket_sets() -> dict[str, set[str]]:
    bucket_sets: dict[str, set[str]] = {}
    publish_ready = load_gov_ids_from_queue(
        PJ360_DIR / "publish-candidates" / "publish-candidates.json", key="gov_id"
    )
    tier_a_all = load_gov_ids_from_queue(PJ360_DIR / "queues" / "tier-a-safe-auto.json")
    bucket_sets["shared_tier_a_flagged"] = tier_a_all - publish_ready

    bucket_sets["shared_tier_c_manual"] = load_gov_ids_from_queue(
        PJ360_DIR / "queues" / "tier-c-manual.json"
    )

    post_triage_map = {
        "shared_manual_no_reference_after_redirect": "manual_no_reference_after_redirect.json",
        "shared_manual_answer_conflict": "manual_only_answer_conflict.json",
        "shared_review_scope_conflict": "review_scope_conflict.json",
        "shared_review_media_presence_only": "review_media_presence_only.json",
        "shared_review_external_category_policy": "review_external_category_policy.json",
        "shared_review_loose_prompt_only": "review_loose_prompt_only.json",
        "shared_review_ambiguous_overlap": "review_ambiguous_overlap.json",
        "shared_review_overlap_only": "review_overlap_only.json",
        "shared_resolved_reference_already_published": "resolved_external_reference.json",
        "shared_reference_usable_already_published": "reference_usable_after_answer_review.json",
    }

    for bucket, filename in post_triage_map.items():
        bucket_sets[bucket] = load_gov_ids_from_queue(PJ360_DIR / "post-triage" / filename)

    return bucket_sets


def fetch_question_rows(dsn: str) -> list[dict[str, Any]]:
    sql = """
        select
            q.id,
            q.external_id,
            q.prompt,
            q.question_type,
            q.correct_answer,
            lc.code as category_code,
            coalesce(nullif(q.explanation, ''), null) as explanation,
            coalesce(
                (
                    select qm.kind
                    from question_media qm
                    where qm.question_id = q.id
                    order by qm.sort_order asc, qm.id asc
                    limit 1
                ),
                'none'
            ) as primary_media_kind
        from questions q
        join license_categories lc on lc.id = q.license_category_id
        order by q.external_id asc, lc.code asc, q.id asc
    """

    if psycopg is not None:
        with psycopg.connect(dsn) as conn:
            with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
                cur.execute(sql)
                return list(cur.fetchall())

    with psycopg2.connect(dsn) as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute(sql)
            return [dict(row) for row in cur.fetchall()]


def group_rows_by_external_id(rows: list[dict[str, Any]]) -> dict[str, dict[str, Any]]:
    grouped: dict[str, dict[str, Any]] = {}

    for row in rows:
        external_id = str(row["external_id"])
        group = grouped.setdefault(
            external_id,
            {
                "external_id": external_id,
                "prompt": row["prompt"],
                "question_type": row["question_type"],
                "correct_answer": row["correct_answer"],
                "all_categories": set(),
                "missing_categories": set(),
                "present_categories": set(),
                "all_media_kinds": set(),
                "missing_media_kinds": set(),
                "missing_row_count": 0,
                "present_row_count": 0,
                "rows": [],
            },
        )

        category = row["category_code"]
        media_kind = row["primary_media_kind"] or "none"
        has_explanation = bool(row["explanation"])

        group["all_categories"].add(category)
        group["all_media_kinds"].add(media_kind)

        if has_explanation:
            group["present_categories"].add(category)
            group["present_row_count"] += 1
        else:
            group["missing_categories"].add(category)
            group["missing_media_kinds"].add(media_kind)
            group["missing_row_count"] += 1

        group["rows"].append(row)

    return grouped


def classify_group(group: dict[str, Any], bucket_sets: dict[str, set[str]]) -> tuple[str, str]:
    external_id = group["external_id"]
    missing_categories = set(group["missing_categories"])
    present_categories = set(group["present_categories"])

    has_shared_missing = any(code != "PT" for code in missing_categories)
    has_pt_missing = "PT" in missing_categories
    has_any_present = bool(present_categories)

    if has_shared_missing:
        ordered_buckets = [
            "shared_tier_c_manual",
            "shared_tier_a_flagged",
            "shared_manual_no_reference_after_redirect",
            "shared_manual_answer_conflict",
            "shared_review_scope_conflict",
            "shared_review_media_presence_only",
            "shared_review_external_category_policy",
            "shared_review_loose_prompt_only",
            "shared_review_ambiguous_overlap",
            "shared_review_overlap_only",
            "shared_resolved_reference_already_published",
            "shared_reference_usable_already_published",
        ]

        for bucket in ordered_buckets:
            if external_id in bucket_sets.get(bucket, set()):
                recommended_action = {
                    "shared_tier_c_manual": "Napisac od zera. Brak bezpiecznego odpowiednika w PJ360.",
                    "shared_tier_a_flagged": "Ręcznie dopracowac draft Tier A i zatwierdzic.",
                    "shared_manual_no_reference_after_redirect": "Napisac od zera albo znalezc inne referencje; PJ360 nie daje juz bezpiecznego targetu.",
                    "shared_manual_answer_conflict": "Zweryfikowac odpowiedz i medium z gov.pl, potem napisac od zera.",
                    "shared_review_scope_conflict": "Review merytoryczny scope; tekst PJ360 moze byc czesciowo uzywalny.",
                    "shared_review_media_presence_only": "Review medium i tresci, potem adaptacja reczna.",
                    "shared_review_external_category_policy": "Review polityki kategorii i dopiero potem wyjasnienie.",
                    "shared_review_loose_prompt_only": "Review promptu, bez automatu; mozliwa adaptacja po potwierdzeniu matcha.",
                    "shared_review_ambiguous_overlap": "Najpierw rozstrzygnac overlap promptu, potem adaptowac tekst.",
                    "shared_review_overlap_only": "Rozstrzygnac duplikat promptu i dopiero potem pisac/adaptowac.",
                    "shared_resolved_reference_already_published": "Anomalia: sprawdzic, czemu shared nadal jest puste mimo rozstrzygnietej referencji.",
                    "shared_reference_usable_already_published": "Anomalia: sprawdzic, czemu shared nadal jest puste mimo curated draftu.",
                }[bucket]
                return bucket, recommended_action

        return (
            "shared_unknown",
            "Sprawdzic recznie. Rekord nie wpada do znanej kolejki review ani publikacji.",
        )

    if has_pt_missing and has_any_present:
        return (
            "pt_overlap_missing_only_pt",
            "Skopiowac lub dostosowac juz istniejace wyjasnienie z innych kategorii do PT po szybkiej kontroli.",
        )

    if has_pt_missing:
        return (
            "pt_only_manual",
            "Napisac od zera dla PT; brak publikacji z poprzedniego etapu.",
        )

    return ("unknown", "Sprawdzic recznie.")


def write_csv(path: Path, rows: list[dict[str, Any]]) -> None:
    if not rows:
        path.write_text("", encoding="utf-8")
        return

    fieldnames = list(rows[0].keys())
    with path.open("w", encoding="utf-8", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)


def clear_bucket_artifacts(output_dir: Path) -> None:
    protected = {"summary.json", "backlog.json", "backlog.csv", "README.md"}

    for pattern in ("*.json", "*.csv"):
        for path in output_dir.glob(pattern):
            if path.name in protected:
                continue
            path.unlink(missing_ok=True)


def main() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    clear_bucket_artifacts(OUTPUT_DIR)

    bucket_sets = build_bucket_sets()
    rows = fetch_question_rows(DEFAULT_DSN)
    grouped = group_rows_by_external_id(rows)

    backlog_entries: list[dict[str, Any]] = []
    row_bucket_counts: Counter[str] = Counter()
    external_bucket_counts: Counter[str] = Counter()
    category_row_counts: Counter[str] = Counter()
    media_row_counts: Counter[str] = Counter()
    type_row_counts: Counter[str] = Counter()

    for external_id, group in grouped.items():
        if not group["missing_categories"]:
            continue

        bucket, recommended_action = classify_group(group, bucket_sets)

        missing_categories = sorted(group["missing_categories"])
        present_categories = sorted(group["present_categories"])
        missing_media_kinds = sorted(group["missing_media_kinds"])

        entry = {
            "external_id": external_id,
            "prompt": group["prompt"],
            "question_type": group["question_type"],
            "correct_answer": group["correct_answer"],
            "all_categories": sorted(group["all_categories"]),
            "missing_categories": missing_categories,
            "present_categories": present_categories,
            "missing_row_count": group["missing_row_count"],
            "present_row_count": group["present_row_count"],
            "missing_media_kinds": missing_media_kinds,
            "bucket": bucket,
            "recommended_action": recommended_action,
        }
        backlog_entries.append(entry)

        external_bucket_counts[bucket] += 1
        row_bucket_counts[bucket] += group["missing_row_count"]

        for row in group["rows"]:
            if row["explanation"]:
                continue

            category_row_counts[row["category_code"]] += 1
            media_row_counts[row["primary_media_kind"] or "none"] += 1
            type_row_counts[row["question_type"] or "unknown"] += 1

    backlog_entries.sort(
        key=lambda item: (
            item["bucket"],
            -item["missing_row_count"],
            item["external_id"],
        )
    )

    by_bucket: dict[str, list[dict[str, Any]]] = defaultdict(list)
    for entry in backlog_entries:
        by_bucket[entry["bucket"]].append(entry)

    summary = {
        "dsn": DEFAULT_DSN.replace("password=prawkobit", "password=***"),
        "missing_rows_total": sum(row_bucket_counts.values()),
        "missing_external_ids_total": len(backlog_entries),
        "missing_external_ids_with_shared_rows": sum(
            1 for entry in backlog_entries if any(code != "PT" for code in entry["missing_categories"])
        ),
        "missing_external_ids_pt_only_or_pt_overlap": sum(
            1 for entry in backlog_entries if "PT" in entry["missing_categories"]
        ),
        "bucket_external_id_counts": dict(sorted(external_bucket_counts.items())),
        "bucket_row_counts": dict(sorted(row_bucket_counts.items())),
        "category_row_counts": dict(sorted(category_row_counts.items())),
        "type_row_counts": dict(sorted(type_row_counts.items())),
        "media_row_counts": dict(sorted(media_row_counts.items())),
        "quick_win_external_ids": [
            entry["external_id"]
            for entry in backlog_entries
            if entry["bucket"] == "pt_overlap_missing_only_pt"
        ],
    }

    (OUTPUT_DIR / "summary.json").write_text(
        json.dumps(summary, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )
    (OUTPUT_DIR / "backlog.json").write_text(
        json.dumps(backlog_entries, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )
    write_csv(OUTPUT_DIR / "backlog.csv", backlog_entries)

    for bucket, entries in by_bucket.items():
        safe_name = bucket.replace("/", "-")
        (OUTPUT_DIR / f"{safe_name}.json").write_text(
            json.dumps(entries, indent=2, ensure_ascii=False),
            encoding="utf-8",
        )
        write_csv(OUTPUT_DIR / f"{safe_name}.csv", entries)

    lines = [
        "# Backlog brakujacych wyjasnien",
        "",
        f"- Brakujace wiersze pytan: `{summary['missing_rows_total']}`",
        f"- Brakujace unikalne external_id: `{summary['missing_external_ids_total']}`",
        f"- External_id z brakami w kategoriach wspolnych: `{summary['missing_external_ids_with_shared_rows']}`",
        f"- External_id z brakami obejmujacymi PT: `{summary['missing_external_ids_pt_only_or_pt_overlap']}`",
        "",
        "## Buckety",
        "",
    ]

    for bucket, count in sorted(external_bucket_counts.items()):
        lines.append(
            f"- `{bucket}`: `{count}` external_id / `{row_bucket_counts[bucket]}` wierszy"
        )

    lines.extend(["", "## Szybkie wygrane", ""])
    quick_wins = summary["quick_win_external_ids"]
    if quick_wins:
        lines.append(
            f"- `pt_overlap_missing_only_pt`: `{len(quick_wins)}` external_id, gdzie PT jest puste, ale inne kategorie maja juz wyjasnienie."
        )
    else:
        lines.append("- Brak szybkich wygranych tego typu w aktualnym snapshotcie.")

    (OUTPUT_DIR / "README.md").write_text("\n".join(lines) + "\n", encoding="utf-8")

    print(json.dumps(summary, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()

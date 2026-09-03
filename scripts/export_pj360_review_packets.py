from __future__ import annotations

import argparse
import csv
import json
from collections import Counter, defaultdict
from pathlib import Path
from typing import Any


DEFAULT_BASE_DIR = Path("output/analysis/pj360-compare")
DEFAULT_OUTPUT_DIR = DEFAULT_BASE_DIR / "review-packets"
HIGH_RISK_REASONS = {
    "answer_mismatch",
    "media_kind_mismatch",
    "structure_scope_mismatch",
    "category_mismatch",
    "ambiguous_best_match",
}


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


def flatten_tier_b_item(item: dict[str, Any], packet_type: str = "tier_b_review") -> dict[str, Any]:
    external = item.get("external", {})
    return {
        "packet_type": packet_type,
        "gov_id": item["local"]["gov_id"],
        "prompt": item["local"]["prompt"],
        "categories": ",".join(item["local"]["categories"]),
        "question_type": item["local"]["question_type"],
        "question_media_kind": item["local"]["question_media_kind"],
        "structure_scope": item["local"]["structure_scope"],
        "accepted_answer": item["local"]["accepted_answer"],
        "reasons": ",".join(item["reasons"]),
        "external_site_question_id": external.get("site_question_id", ""),
        "external_url": external.get("url", ""),
        "external_media_kind": external.get("question_media_kind", ""),
        "review_decision": "",
        "review_notes": "",
    }


def flatten_tier_a_flagged_item(packet: dict[str, Any], validation_item: dict[str, Any]) -> dict[str, Any]:
    return {
        "packet_type": "tier_a_flagged_draft",
        "gov_id": packet["gov_id"],
        "prompt": packet["prompt"],
        "categories": ",".join(packet["categories"]),
        "question_type": packet["question_type"],
        "question_media_kind": packet["question_media_kind"],
        "structure_scope": packet["structure_scope"],
        "accepted_answer": packet["accepted_answer"],
        "reasons": ",".join(validation_item["validation_flags"]),
        "external_site_question_id": packet["external_site_question_id"] or "",
        "external_url": packet["external_url"],
        "external_media_kind": packet["external_question_media_kind"],
        "draft_text": packet["draft_text"],
        "review_decision": "",
        "review_notes": "",
    }


def flatten_manual_item(item: dict[str, Any], packet_type: str) -> dict[str, Any]:
    local = item["local"] if "local" in item else item
    reasons = item.get("reasons", [])
    return {
        "packet_type": packet_type,
        "gov_id": local["gov_id"],
        "prompt": local["prompt"],
        "categories": ",".join(local["categories"]),
        "question_type": local["question_type"],
        "question_media_kind": local["question_media_kind"],
        "structure_scope": local["structure_scope"],
        "accepted_answer": local["accepted_answer"],
        "reasons": ",".join(reasons),
        "external_site_question_id": "",
        "external_url": "",
        "external_media_kind": "",
        "review_decision": "",
        "review_notes": "",
    }


def build_readme(summary: dict[str, Any]) -> str:
    lines = [
        "# Paczki review PJ360",
        "",
        "## Podsumowanie",
        "",
        f"- `tier_b_total`: `{summary['tier_b_total']}`",
        f"- `tier_b_high_risk`: `{summary['tier_b_high_risk']}`",
        f"- `tier_b_duplicate_or_overlap`: `{summary['tier_b_duplicate_or_overlap']}`",
        f"- `tier_a_flagged_drafts`: `{summary['tier_a_flagged_drafts']}`",
        f"- `tier_c_manual`: `{summary['tier_c_manual']}`",
        f"- `tier_pt_manual`: `{summary['tier_pt_manual']}`",
        "",
        "## Tier B / reasons",
        "",
    ]

    for reason, count in summary["tier_b_reason_counts"].items():
        lines.append(f"- `{reason}`: `{count}`")

    lines.extend([
        "",
        "## Tier A / validation flags",
        "",
    ])

    for flag, count in summary["tier_a_flag_counts"].items():
        lines.append(f"- `{flag}`: `{count}`")

    lines.extend([
        "",
        "## Pakiety kategorii",
        "",
        "Kazdy plik w `by-category/` zawiera lacznie:",
        "",
        "- pytania z `Tier B`,",
        "- oflagowane drafty z `Tier A`,",
        "- pozycje `Tier C`,",
        "- oraz `PT`, jesli dotyczy danej kategorii.",
        "",
    ])

    return "\n".join(lines)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Eksportuje paczki review dla adaptacji wyjasnien PJ360.")
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
        help="Katalog wyjsciowy dla paczek review.",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    queues_dir = args.base_dir / "queues"
    drafts_dir = args.base_dir / "drafts"
    validation_dir = drafts_dir / "validation"

    tier_b = load_json(queues_dir / "tier-b-review.json")
    tier_c = load_json(queues_dir / "tier-c-manual.json")
    tier_pt = load_json(queues_dir / "tier-pt-manual.json")
    draft_packets = load_json(drafts_dir / "tier-a-draft-packets.json")
    draft_validation = load_json(validation_dir / "tier-a-draft-validation.json")

    draft_packet_by_id = {item["gov_id"]: item for item in draft_packets}
    flagged_drafts = [item for item in draft_validation if item["validation_flags"]]

    tier_b_reason_counts = Counter(reason for item in tier_b for reason in item["reasons"])
    tier_a_flag_counts = Counter(flag for item in flagged_drafts for flag in item["validation_flags"])

    tier_b_high_risk = [
        item for item in tier_b if any(reason in HIGH_RISK_REASONS for reason in item["reasons"])
    ]
    tier_b_duplicate_or_overlap = [
        item
        for item in tier_b
        if any(reason not in HIGH_RISK_REASONS for reason in item["reasons"])
        and not any(reason in HIGH_RISK_REASONS for reason in item["reasons"])
    ]

    args.output_dir.mkdir(parents=True, exist_ok=True)

    summary = {
        "tier_b_total": len(tier_b),
        "tier_b_high_risk": len(tier_b_high_risk),
        "tier_b_duplicate_or_overlap": len(tier_b_duplicate_or_overlap),
        "tier_a_flagged_drafts": len(flagged_drafts),
        "tier_c_manual": len(tier_c),
        "tier_pt_manual": len(tier_pt),
        "tier_b_reason_counts": dict(sorted(tier_b_reason_counts.items())),
        "tier_a_flag_counts": dict(sorted(tier_a_flag_counts.items())),
    }

    write_json(args.output_dir / "summary.json", summary)
    (args.output_dir / "README.md").write_text(build_readme(summary), encoding="utf-8")

    write_json(args.output_dir / "tier-b-high-risk.json", tier_b_high_risk)
    write_json(args.output_dir / "tier-b-duplicate-or-overlap.json", tier_b_duplicate_or_overlap)
    write_json(args.output_dir / "tier-a-flagged-drafts.json", flagged_drafts)
    write_json(args.output_dir / "tier-c-manual.json", tier_c)
    write_json(args.output_dir / "tier-pt-manual.json", tier_pt)

    write_csv(
        args.output_dir / "tier-b-high-risk.csv",
        [flatten_tier_b_item(item, "tier_b_high_risk") for item in tier_b_high_risk],
    )
    write_csv(
        args.output_dir / "tier-b-duplicate-or-overlap.csv",
        [flatten_tier_b_item(item, "tier_b_duplicate_or_overlap") for item in tier_b_duplicate_or_overlap],
    )
    write_csv(
        args.output_dir / "tier-a-flagged-drafts.csv",
        [flatten_tier_a_flagged_item(draft_packet_by_id[item["gov_id"]], item) for item in flagged_drafts],
    )
    write_csv(
        args.output_dir / "tier-c-manual.csv",
        [flatten_manual_item(item, "tier_c_manual") for item in tier_c],
    )
    write_csv(
        args.output_dir / "tier-pt-manual.csv",
        [flatten_manual_item(item, "tier_pt_manual") for item in tier_pt],
    )

    by_reason_dir = args.output_dir / "by-reason"
    for reason in sorted(tier_b_reason_counts):
        items = [item for item in tier_b if reason in item["reasons"]]
        write_json(by_reason_dir / f"{reason}.json", items)
        write_csv(by_reason_dir / f"{reason}.csv", [flatten_tier_b_item(item) for item in items])

    by_flag_dir = args.output_dir / "by-flag"
    for flag in sorted(tier_a_flag_counts):
        items = [item for item in flagged_drafts if flag in item["validation_flags"]]
        write_json(by_flag_dir / f"{flag}.json", items)
        write_csv(
            by_flag_dir / f"{flag}.csv",
            [flatten_tier_a_flagged_item(draft_packet_by_id[item["gov_id"]], item) for item in items],
        )

    category_rows: dict[str, list[dict[str, Any]]] = defaultdict(list)

    for item in tier_b:
        flattened = flatten_tier_b_item(item)
        for category in item["local"]["categories"]:
            category_rows[category].append(flattened)

    for item in flagged_drafts:
        packet = draft_packet_by_id[item["gov_id"]]
        flattened = flatten_tier_a_flagged_item(packet, item)
        for category in packet["categories"]:
            category_rows[category].append(flattened)

    for item in tier_c:
        flattened = flatten_manual_item(item, "tier_c_manual")
        for category in item["local"]["categories"]:
            category_rows[category].append(flattened)

    for item in tier_pt:
        flattened = flatten_manual_item(item, "tier_pt_manual")
        for category in item["categories"]:
            category_rows[category].append(flattened)

    by_category_dir = args.output_dir / "by-category"
    category_summary: dict[str, Any] = {}
    for category, rows in sorted(category_rows.items()):
        write_csv(by_category_dir / f"{category}.csv", rows)
        write_json(by_category_dir / f"{category}.json", rows)
        type_counts = Counter(row["packet_type"] for row in rows)
        category_summary[category] = {
            "row_count": len(rows),
            "packet_type_counts": dict(sorted(type_counts.items())),
        }

    write_json(args.output_dir / "by-category-summary.json", category_summary)

    print(json.dumps(summary, ensure_ascii=False, indent=2))
    print(f"Zapisano paczki review do: {args.output_dir}")


if __name__ == "__main__":
    main()

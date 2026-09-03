from __future__ import annotations

import argparse
import csv
import json
import sys
from pathlib import Path
from typing import Any


SCRIPT_DIR = Path(__file__).resolve().parent
if str(SCRIPT_DIR) not in sys.path:
    sys.path.insert(0, str(SCRIPT_DIR))

from generate_pj360_explanation_drafts import build_draft_packet  # noqa: E402


DEFAULT_BASE_DIR = Path("output/analysis/pj360-compare")
DEFAULT_OUTPUT_DIR = DEFAULT_BASE_DIR / "drafts"
DEFAULT_POST_TRIAGE_DIR = DEFAULT_BASE_DIR / "post-triage"


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
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


def build_summary(rows: list[dict[str, Any]]) -> dict[str, Any]:
    quality_flag_counts: dict[str, int] = {}
    decision_counts: dict[str, int] = {}

    for row in rows:
        decision = row["tier_b_decision"]
        decision_counts[decision] = decision_counts.get(decision, 0) + 1
        for flag in row["quality_flags"]:
            quality_flag_counts[flag] = quality_flag_counts.get(flag, 0) + 1

    return {
        "draft_count": len(rows),
        "publish_ready_count": sum(1 for row in rows if row["publish_ready"]),
        "needs_review_count": sum(1 for row in rows if not row["publish_ready"]),
        "decision_counts": dict(sorted(decision_counts.items())),
        "quality_flag_counts": dict(sorted(quality_flag_counts.items())),
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Generuje curated drafty dla bezpiecznej czesci Tier B.")
    parser.add_argument(
        "--base-dir",
        type=Path,
        default=DEFAULT_BASE_DIR,
        help="Katalog bazowy pj360-compare.",
    )
    parser.add_argument(
        "--post-triage-dir",
        type=Path,
        default=DEFAULT_POST_TRIAGE_DIR,
        help="Katalog post-triage dla Tier B.",
    )
    parser.add_argument(
        "--output-dir",
        type=Path,
        default=DEFAULT_OUTPUT_DIR,
        help="Katalog wyjsciowy dla draftow.",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()

    external_questions = load_json(args.base_dir / "external_questions.json")
    tier_b_queue = load_json(args.base_dir / "queues" / "tier-b-review.json")
    resolved_reference = load_json(args.post_triage_dir / "resolved_external_reference.json")
    reference_after_answer_review = load_json(args.post_triage_dir / "reference_usable_after_answer_review.json")

    external_by_site_id = {
        str(item["site_question_id"]): item
        for item in external_questions
        if item.get("site_question_id") is not None
    }
    tier_b_by_local_id = {
        item["local"]["gov_id"]: item
        for item in tier_b_queue
    }

    curated_sources = resolved_reference + reference_after_answer_review
    drafts: list[dict[str, Any]] = []

    for source in curated_sources:
        gov_id = source["gov_id"]
        local_queue_item = tier_b_by_local_id.get(gov_id)
        if local_queue_item is None:
            continue

        site_question_id = source.get("external_site_question_id")
        external_full = external_by_site_id.get(str(site_question_id)) if site_question_id else None
        if external_full is None:
            external_full = local_queue_item.get("external")
        if external_full is None:
            continue

        packet = build_draft_packet(
            {
                "local": local_queue_item["local"],
                "external": external_full,
            }
        )
        packet["tier_b_decision"] = source["decision"]
        packet["tier_b_note"] = source["note"]
        packet["tier_b_reasons"] = local_queue_item["reasons"]
        packet["redirected_to_local_gov_id"] = source.get("redirected_to_local_gov_id")
        packet["resolved_from_local_gov_id"] = source.get("resolved_from_local_gov_id")
        packet["resolution_method"] = source.get("resolution_method")
        drafts.append(packet)

    summary = build_summary(drafts)

    args.output_dir.mkdir(parents=True, exist_ok=True)
    write_json(args.output_dir / "tier-b-curated-draft-packets.json", drafts)
    write_json(args.output_dir / "tier-b-curated-draft-summary.json", summary)
    write_csv(args.output_dir / "tier-b-curated-draft-packets.csv", drafts)

    print(json.dumps(summary, ensure_ascii=False, indent=2))
    print(f"Zapisano curated drafty Tier B do: {args.output_dir}")


if __name__ == "__main__":
    main()

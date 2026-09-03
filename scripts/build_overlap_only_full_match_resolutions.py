from __future__ import annotations

import json
import sys
from pathlib import Path
from typing import Any


SCRIPT_DIR = Path(__file__).resolve().parent
if str(SCRIPT_DIR) not in sys.path:
    sys.path.insert(0, str(SCRIPT_DIR))

from generate_pj360_explanation_drafts import build_draft_packet  # noqa: E402


ROOT = Path(__file__).resolve().parents[1]
POST_TRIAGE_PATH = ROOT / "output" / "analysis" / "pj360-compare" / "post-triage" / "review_overlap_only.json"
QUEUE_PATH = ROOT / "output" / "analysis" / "pj360-compare" / "queues" / "tier-b-review.json"
OUTPUT_PATH = ROOT / "storage" / "app" / "manual" / "question-explanation-overlap-only-full-match.json"
SUMMARY_PATH = (
    ROOT
    / "output"
    / "analysis"
    / "remaining-explanations"
    / "overlap-only-review"
    / "full-match-summary.json"
)


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def normalize_text(value: str) -> str:
    return str(value or "").strip().lower()


def main() -> None:
    post_triage = load_json(POST_TRIAGE_PATH)
    queue = load_json(QUEUE_PATH)
    queue_by_gov = {str(item["local"]["gov_id"]): item for item in queue}

    resolutions: list[dict[str, Any]] = []
    skipped: list[dict[str, Any]] = []

    for row in post_triage:
        gov_id = str(row["gov_id"])
        queue_item = queue_by_gov.get(gov_id)

        if queue_item is None:
            skipped.append({"gov_id": gov_id, "reason": "missing_queue_item"})
            continue

        local = queue_item["local"]
        candidates: list[dict[str, Any]] = []
        if queue_item.get("external"):
            candidates.append(queue_item["external"])
        candidates.extend(queue_item.get("external_candidates") or [])

        if len(candidates) != 1:
            skipped.append(
                {
                    "gov_id": gov_id,
                    "reason": "candidate_count_not_equal_1",
                    "candidate_count": len(candidates),
                }
            )
            continue

        external = candidates[0]
        if normalize_text(local["accepted_answer"]) != normalize_text(external.get("accepted_answer")):
            skipped.append({"gov_id": gov_id, "reason": "answer_mismatch"})
            continue

        if normalize_text(local["question_media_kind"]) != normalize_text(external.get("question_media_kind")):
            skipped.append({"gov_id": gov_id, "reason": "media_kind_mismatch"})
            continue

        packet = build_draft_packet({"local": local, "external": external})
        if not packet["publish_ready"]:
            skipped.append(
                {
                    "gov_id": gov_id,
                    "reason": "draft_not_publish_ready",
                    "quality_flags": packet["quality_flags"],
                }
            )
            continue

        resolutions.append(
            {
                "external_id": gov_id,
                "resolved_explanation": packet["draft_text"],
                "source_site_question_id": external.get("site_question_id"),
                "source_url": external.get("url"),
            }
        )

    summary = {
        "post_triage_path": str(POST_TRIAGE_PATH),
        "queue_path": str(QUEUE_PATH),
        "resolution_count": len(resolutions),
        "skipped_count": len(skipped),
        "skipped_reason_counts": {},
    }

    for item in skipped:
        reason = item["reason"]
        summary["skipped_reason_counts"][reason] = summary["skipped_reason_counts"].get(reason, 0) + 1

    summary["skipped_reason_counts"] = dict(sorted(summary["skipped_reason_counts"].items()))

    write_json(OUTPUT_PATH, resolutions)
    write_json(SUMMARY_PATH, {"summary": summary, "skipped": skipped})
    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

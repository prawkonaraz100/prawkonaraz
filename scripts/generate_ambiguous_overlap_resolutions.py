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
PJ360_DIR = ROOT / "output" / "analysis" / "pj360-compare"
REMAINING_DIR = ROOT / "output" / "analysis" / "remaining-explanations"
REPORT_PATH = REMAINING_DIR / "ambiguous-overlap-review" / "ambiguous-overlap-review.json"
OUTPUT_PATH = REMAINING_DIR / "ambiguous-overlap-review" / "generated-resolutions.json"
SUMMARY_PATH = REMAINING_DIR / "ambiguous-overlap-review" / "generated-resolutions-summary.json"


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def main() -> None:
    report = load_json(REPORT_PATH)
    external_questions = load_json(PJ360_DIR / "external_questions.json")
    tier_b_review = load_json(PJ360_DIR / "queues" / "tier-b-review.json")
    external_by_site_id = {
        str(item["site_question_id"]): item
        for item in external_questions
        if item.get("site_question_id") is not None
    }
    tier_b_by_gov_id = {
        str(item["local"]["gov_id"]): item["local"]
        for item in tier_b_review
        if item.get("local", {}).get("gov_id") is not None
    }

    resolutions: list[dict[str, Any]] = []
    skipped: list[dict[str, Any]] = []

    for item in report["results"]:
        top_candidates = item.get("top_candidates") or []
        if not top_candidates:
            skipped.append({"gov_id": item["gov_id"], "reason": "no_top_candidate"})
            continue

        top = top_candidates[0]
        is_strict = (
            top.get("answer_matches") is True
            and top.get("media_kind_matches") is True
            and float(top.get("sequence_ratio") or 0) == 1.0
            and float(top.get("token_ratio") or 0) == 1.0
        )
        if not is_strict:
            skipped.append({"gov_id": item["gov_id"], "reason": "candidate_not_strict"})
            continue

        external = external_by_site_id.get(str(top["site_question_id"]))
        if external is None:
            skipped.append({"gov_id": item["gov_id"], "reason": "missing_external_payload"})
            continue

        local = tier_b_by_gov_id.get(item["gov_id"])
        if local is None:
            skipped.append({"gov_id": item["gov_id"], "reason": "missing_local_payload"})
            continue

        packet = build_draft_packet({"local": local, "external": external})
        resolutions.append(
            {
                "external_id": item["gov_id"],
                "resolved_explanation": packet["draft_text"],
                "source_site_question_id": top["site_question_id"],
                "source_url": top["url"],
                "score": top["score"],
            }
        )

    summary = {
        "report_path": str(REPORT_PATH),
        "resolution_count": len(resolutions),
        "skipped_count": len(skipped),
        "skipped": skipped,
    }

    write_json(OUTPUT_PATH, resolutions)
    write_json(SUMMARY_PATH, summary)
    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

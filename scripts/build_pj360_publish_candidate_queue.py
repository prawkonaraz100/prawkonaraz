from __future__ import annotations

import csv
import json
from pathlib import Path
from typing import Any


BASE_DIR = Path("output/analysis/pj360-compare")
OUTPUT_DIR = BASE_DIR / "publish-candidates"


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


def simplify_packet(packet: dict[str, Any], source_queue: str) -> dict[str, Any]:
    return {
        "gov_id": packet["gov_id"],
        "source_queue": source_queue,
        "prompt": packet["prompt"],
        "categories": packet["categories"],
        "question_type": packet["question_type"],
        "question_media_kind": packet["question_media_kind"],
        "structure_scope": packet["structure_scope"],
        "accepted_answer": packet["accepted_answer"],
        "accepted_answer_label": packet.get("accepted_answer_label"),
        "external_site_question_id": packet.get("external_site_question_id"),
        "external_url": packet.get("external_url"),
        "draft_text": packet["draft_text"],
        "source_summary": packet.get("source_summary", ""),
        "quality_flags": packet.get("quality_flags", []),
        "publish_ready": packet.get("publish_ready", False),
        "tier_b_decision": packet.get("tier_b_decision"),
        "tier_b_note": packet.get("tier_b_note"),
        "resolution_method": packet.get("resolution_method"),
    }


def main() -> None:
    tier_a = load_json(BASE_DIR / "drafts" / "tier-a-draft-packets.json")
    tier_b_curated = load_json(BASE_DIR / "drafts" / "tier-b-curated-draft-packets.json")

    publish_candidates: list[dict[str, Any]] = []

    for packet in tier_a:
        if packet.get("publish_ready"):
            publish_candidates.append(simplify_packet(packet, "tier_a"))

    for packet in tier_b_curated:
        if packet.get("publish_ready"):
            publish_candidates.append(simplify_packet(packet, "tier_b_curated"))

    publish_candidates.sort(key=lambda item: (item["source_queue"], int(item["gov_id"])))

    summary = {
        "publish_candidate_total": len(publish_candidates),
        "tier_a_publish_ready": sum(1 for item in publish_candidates if item["source_queue"] == "tier_a"),
        "tier_b_curated_publish_ready": sum(1 for item in publish_candidates if item["source_queue"] == "tier_b_curated"),
    }

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    write_json(OUTPUT_DIR / "publish-candidates.json", publish_candidates)
    write_json(OUTPUT_DIR / "publish-candidates-summary.json", summary)
    write_csv(OUTPUT_DIR / "publish-candidates.csv", publish_candidates)

    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

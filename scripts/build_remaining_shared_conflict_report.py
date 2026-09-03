from __future__ import annotations

import csv
import json
import re
from collections import Counter, defaultdict
from difflib import SequenceMatcher
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
POST_TRIAGE_PATH = ROOT / "output" / "analysis" / "pj360-compare" / "post-triage" / "review_overlap_only.json"
QUEUE_PATH = ROOT / "output" / "analysis" / "pj360-compare" / "queues" / "tier-b-review.json"
REMAINING_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "shared_review_overlap_only.json"
OUTPUT_DIR = ROOT / "output" / "analysis" / "remaining-explanations" / "overlap-only-review" / "remaining-shared-conflicts"


def load_json(path: Path) -> Any:
    if not path.exists():
        return []
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


def clear_output_dir(path: Path) -> None:
    protected = {"summary.json"}
    path.mkdir(parents=True, exist_ok=True)

    for pattern in ("*.json", "*.csv"):
        for child in path.glob(pattern):
            if child.name in protected:
                continue
            child.unlink(missing_ok=True)


def normalize_text(value: str) -> str:
    return str(value or "").strip().lower()


def classify(row: dict[str, Any]) -> str:
    local_answer = row["local_answer"]
    external_answer = row["external_answer"]
    local_media = row["local_media"]
    external_media = row["external_media"]
    question_type = row["question_type"]
    seq_ratio = row["answer_sequence_ratio"]

    if local_answer == external_answer and local_media != external_media:
        return "media_mismatch_only"

    if local_answer != external_answer and local_media != external_media:
        return "answer_and_media_conflict"

    if question_type == "boolean":
        return "boolean_answer_conflict"

    if re.search(r"\d", row["local_answer_raw"]) or re.search(r"\d", row["external_answer_raw"]):
        return "single_choice_numeric_conflict"

    if seq_ratio >= 0.75:
        return "single_choice_close_text_variant"

    return "single_choice_hard_answer_conflict"


def main() -> None:
    post_triage = load_json(POST_TRIAGE_PATH)
    queue = load_json(QUEUE_PATH)
    remaining = load_json(REMAINING_PATH)
    remaining_ids = {str(item["external_id"]) for item in remaining}
    queue_by_gov = {str(item["local"]["gov_id"]): item for item in queue}

    rows: list[dict[str, Any]] = []
    bucketed: dict[str, list[dict[str, Any]]] = defaultdict(list)
    counts: Counter[str] = Counter()

    for item in post_triage:
        gov_id = str(item["gov_id"])
        if gov_id not in remaining_ids:
            continue

        queue_item = queue_by_gov[gov_id]
        local = queue_item["local"]
        external = queue_item.get("external") or (queue_item.get("external_candidates") or [None])[0]
        if external is None:
            continue

        row = {
            "gov_id": gov_id,
            "prompt": local["prompt"],
            "question_type": local["question_type"],
            "local_answer_raw": str(local["accepted_answer"]).strip(),
            "external_answer_raw": str(external.get("accepted_answer") or "").strip(),
            "local_answer": normalize_text(local["accepted_answer"]),
            "external_answer": normalize_text(external.get("accepted_answer")),
            "local_media": normalize_text(local["question_media_kind"]),
            "external_media": normalize_text(external.get("question_media_kind")),
            "source_url": external.get("url"),
            "answer_sequence_ratio": round(
                SequenceMatcher(
                    None,
                    str(local["accepted_answer"]).strip().lower(),
                    str(external.get("accepted_answer") or "").strip().lower(),
                ).ratio(),
                3,
            ),
        }
        row["bucket"] = classify(row)

        rows.append(row)
        bucketed[row["bucket"]].append(row)
        counts[row["bucket"]] += 1

    rows.sort(key=lambda item: (item["bucket"], item["gov_id"]))

    summary = {
        "remaining_shared_external_ids": len(rows),
        "bucket_counts": dict(sorted(counts.items())),
    }

    clear_output_dir(OUTPUT_DIR)
    write_json(OUTPUT_DIR / "summary.json", summary)
    write_json(OUTPUT_DIR / "all.json", rows)
    write_csv(OUTPUT_DIR / "all.csv", rows)

    for bucket, bucket_rows in bucketed.items():
        write_json(OUTPUT_DIR / f"{bucket}.json", bucket_rows)
        write_csv(OUTPUT_DIR / f"{bucket}.csv", bucket_rows)

    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

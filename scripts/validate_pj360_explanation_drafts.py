from __future__ import annotations

import argparse
import csv
import json
from pathlib import Path
from typing import Any


DEFAULT_DRAFTS_PATH = Path("output/analysis/pj360-compare/drafts/tier-a-draft-packets.json")
DEFAULT_OUTPUT_DIR = Path("output/analysis/pj360-compare/drafts/validation")


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
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


def validate_draft(packet: dict[str, Any]) -> list[str]:
    flags = list(packet.get("quality_flags", []))
    draft_text = packet.get("draft_text", "") or ""

    if packet["question_type"] == "boolean":
        if not draft_text.startswith("Tak") and not draft_text.startswith("Nie"):
            flags.append("missing_boolean_verdict")
    else:
        if not draft_text.startswith("Poprawna jest odpowiedz"):
            flags.append("missing_single_choice_verdict")

    if packet["question_media_kind"] == "video" and "nagran" not in draft_text.lower():
        flags.append("weak_video_reference")
    if packet["question_media_kind"] == "image" and "obra" not in draft_text.lower() and "oznaczeni" not in draft_text.lower():
        flags.append("weak_image_reference")
    if len(draft_text) < 120:
        flags.append("draft_too_short")

    return sorted(set(flags))


def build_summary(results: list[dict[str, Any]]) -> dict[str, Any]:
    flag_counts: dict[str, int] = {}
    for item in results:
        for flag in item["validation_flags"]:
            flag_counts[flag] = flag_counts.get(flag, 0) + 1

    return {
        "validated_count": len(results),
        "clean_count": sum(1 for item in results if not item["validation_flags"]),
        "flagged_count": sum(1 for item in results if item["validation_flags"]),
        "validation_flag_counts": dict(sorted(flag_counts.items())),
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Waliduje drafty wyjasnien dla Tier A.")
    parser.add_argument(
        "--drafts-path",
        type=Path,
        default=DEFAULT_DRAFTS_PATH,
        help="Plik JSON z draftami.",
    )
    parser.add_argument(
        "--output-dir",
        type=Path,
        default=DEFAULT_OUTPUT_DIR,
        help="Katalog wyjsciowy dla wynikow walidacji.",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    drafts = load_json(args.drafts_path)

    validation_results: list[dict[str, Any]] = []
    for draft in drafts:
        validation_results.append(
            {
                "gov_id": draft["gov_id"],
                "prompt": draft["prompt"],
                "question_media_kind": draft["question_media_kind"],
                "question_type": draft["question_type"],
                "publish_ready": draft["publish_ready"],
                "validation_flags": validate_draft(draft),
                "draft_text": draft["draft_text"],
            }
        )

    summary = build_summary(validation_results)

    args.output_dir.mkdir(parents=True, exist_ok=True)
    write_json(args.output_dir / "tier-a-draft-validation.json", validation_results)
    write_json(args.output_dir / "tier-a-draft-validation-summary.json", summary)
    write_csv(args.output_dir / "tier-a-draft-validation.csv", validation_results)

    print(json.dumps(summary, ensure_ascii=False, indent=2))
    print(f"Zapisano walidacje do: {args.output_dir}")


if __name__ == "__main__":
    main()

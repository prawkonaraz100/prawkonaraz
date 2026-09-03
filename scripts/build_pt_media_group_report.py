from __future__ import annotations

import json
from collections import defaultdict
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
INPUT_PATH = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-media-review" / "all.json"
OUTPUT_DIR = ROOT / "output" / "analysis" / "remaining-explanations" / "pt-media-review" / "grouped"


def load_rows() -> list[dict[str, Any]]:
    return json.loads(INPUT_PATH.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def build_group_report(rows: list[dict[str, Any]]) -> list[dict[str, Any]]:
    grouped: dict[tuple[str, str], list[dict[str, Any]]] = defaultdict(list)

    for row in rows:
        media_path = str(row["primary_media_path"])
        media_fingerprint = Path(media_path).name
        key = (str(row["primary_media_kind"]), media_fingerprint)
        grouped[key].append(row)

    report: list[dict[str, Any]] = []
    for (media_kind, media_fingerprint), items in grouped.items():
        items.sort(key=lambda row: int(str(row["external_id"])))
        report.append(
            {
                "media_kind": media_kind,
                "media_fingerprint": media_fingerprint,
                "count": len(items),
                "question_types": sorted({str(item["question_type"]) for item in items}),
                "external_ids": [str(item["external_id"]) for item in items],
                "sample_paths": [str(item["primary_media_path"]) for item in items[:5]],
                "rows": items,
            }
        )

    report.sort(key=lambda item: (-int(item["count"]), item["media_kind"], item["media_fingerprint"]))
    return report


def build_summary(groups: list[dict[str, Any]]) -> dict[str, Any]:
    duplicate_groups = [group for group in groups if int(group["count"]) > 1]

    return {
        "group_count": len(groups),
        "duplicate_group_count": len(duplicate_groups),
        "largest_groups": [
            {
                "media_kind": group["media_kind"],
                "media_fingerprint": group["media_fingerprint"],
                "count": group["count"],
                "external_ids": group["external_ids"],
                "sample_paths": group["sample_paths"],
            }
            for group in duplicate_groups[:25]
        ],
    }


def main() -> None:
    rows = load_rows()
    groups = build_group_report(rows)
    summary = build_summary(groups)

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    write_json(OUTPUT_DIR / "summary.json", summary)
    write_json(OUTPUT_DIR / "all-groups.json", groups)
    write_json(OUTPUT_DIR / "duplicate-groups.json", [group for group in groups if int(group["count"]) > 1])

    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

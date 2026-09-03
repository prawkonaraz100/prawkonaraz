from __future__ import annotations

import argparse
import json
from collections import Counter
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_PACKAGE_DIR = ROOT / "resources" / "topic-overrides"
DEFAULT_OUTPUT_DIR = ROOT / "output" / "analysis" / "pj360-exact-topic-membership"


def load_json(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Export retopic conflict worksets from a PJ360 retopic override package."
    )
    parser.add_argument("--category", required=True, help="Kod kategorii, np. B.")
    parser.add_argument(
        "--package-json",
        default="",
        help="Sciezka do pliku pj360-<category>-retopic-package.json. Domyslnie resources/topic-overrides.",
    )
    parser.add_argument("--output-dir", default=str(DEFAULT_OUTPUT_DIR), help="Katalog docelowy.")
    return parser.parse_args()


def default_package_path(category_code: str, custom: str) -> Path:
    if custom.strip():
        return Path(custom)

    return DEFAULT_PACKAGE_DIR / f"pj360-{category_code.lower()}-retopic-package.json"


def summarize_true_conflicts(rows: list[dict[str, Any]]) -> dict[str, Any]:
    target_combo_counter = Counter()
    current_topic_counter = Counter()

    for row in rows:
        target_combo_counter[" | ".join(row.get("target_topic_keys", []))] += 1
        for topic in row.get("current_topic_keys", []):
            current_topic_counter[str(topic)] += 1

    return {
        "count": len(rows),
        "target_topic_combinations": dict(target_combo_counter.most_common()),
        "current_topic_counts": dict(current_topic_counter.most_common()),
    }


def summarize_override_conflicts(rows: list[dict[str, Any]]) -> dict[str, Any]:
    existing_counter = Counter()
    candidate_counter = Counter()

    for row in rows:
        existing_counter[str(row.get("existing_override_topic_key") or "")] += 1
        candidate_counter[str(row.get("candidate_topic_key") or "")] += 1

    return {
        "count": len(rows),
        "existing_override_topic_counts": dict(existing_counter.most_common()),
        "candidate_topic_counts": dict(candidate_counter.most_common()),
    }


def main() -> None:
    args = parse_args()
    category_code = args.category.strip().upper()
    package_path = default_package_path(category_code, args.package_json)
    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    payload = load_json(package_path)
    true_conflicts = payload.get("skipped_conflicts", [])
    override_conflicts = payload.get("skipped_existing_override_conflicts", [])

    true_conflicts_payload = {
        "category_code": category_code,
        "workflow": "pj360_exact_retopic_true_conflicts",
        "source_package_path": str(package_path),
        "summary": summarize_true_conflicts(true_conflicts),
        "records": true_conflicts,
    }
    override_conflicts_payload = {
        "category_code": category_code,
        "workflow": "pj360_exact_retopic_override_guard_conflicts",
        "source_package_path": str(package_path),
        "summary": summarize_override_conflicts(override_conflicts),
        "records": override_conflicts,
    }

    true_conflicts_path = output_dir / f"{category_code.lower()}-retopic-true-conflicts.json"
    override_conflicts_path = output_dir / f"{category_code.lower()}-retopic-override-guard-conflicts.json"
    true_conflicts_path.write_text(json.dumps(true_conflicts_payload, ensure_ascii=False, indent=2), encoding="utf-8")
    override_conflicts_path.write_text(json.dumps(override_conflicts_payload, ensure_ascii=False, indent=2), encoding="utf-8")

    print(
        json.dumps(
            {
                "category": category_code,
                "true_conflicts": true_conflicts_payload["summary"]["count"],
                "override_guard_conflicts": override_conflicts_payload["summary"]["count"],
                "true_conflicts_path": str(true_conflicts_path),
                "override_guard_conflicts_path": str(override_conflicts_path),
            },
            ensure_ascii=False,
            indent=2,
        )
    )


if __name__ == "__main__":
    main()

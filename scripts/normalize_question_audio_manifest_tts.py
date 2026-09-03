#!/usr/bin/env python3
"""Build a TTS-friendly copy of a Laravel question-audio manifest.

The Laravel manifest remains the source of truth for asset_key and source_text_hash.
This script changes only item["source_text"] for the local TTS generator and records
the original visible text in item["tts_source_text_original"].
"""

from __future__ import annotations

import argparse
import csv
import json
import re
from dataclasses import dataclass
from pathlib import Path
from typing import Any


@dataclass(frozen=True)
class ReplacementRule:
    pattern: re.Pattern[str]
    replacement: str


def rule(pattern: str, replacement: str) -> ReplacementRule:
    return ReplacementRule(re.compile(pattern, re.IGNORECASE | re.UNICODE), replacement)


RULES: list[ReplacementRule] = [
    rule(r"\bod godziny 6\.00 do godziny 10\.00\b", "Od godziny szóstej do godziny dziesiątej"),
    rule(
        r"\bJak jest maksymalna dopuszczalna wysokość pojazdu z ładunkiem\?",
        "Jaka jest maksymalna dopuszczalna wysokość pojazdu z ładunkiem?",
    ),
    rule(r"\bkategorii I na przejazd\b", "kategorii pierwszej na przejazd"),
    rule(r"\btzw\.\s*\"korytarz życia\"", "tak zwany korytarz życia"),
    rule(r"\bm\.\s*in\.", "między innymi"),
    rule(r"\bużywac\b", "używać"),
    rule(r"\bskręcic\b", "skręcić"),
    rule(r"\bnadjeżdzającym\b", "nadjeżdżającym"),
    rule(r"\bnadjeżdzającemu\b", "nadjeżdżającemu"),
    rule(r"\bnr\s*1\b", "numer jeden"),
    rule(r"\bnr\s*2\b", "numer dwa"),
    rule(r"\bnr\s*3\b", "numer trzy"),
    rule(r"\bdo\s+140\s*km\s*/\s*h\b", "do stu czterdziestu kilometrów na godzinę"),
    rule(r"\bdo\s+120\s*km\s*/\s*h\b", "do stu dwudziestu kilometrów na godzinę"),
    rule(r"\bdo\s+105\s*km\s*/\s*h\b", "do stu pięciu kilometrów na godzinę"),
    rule(r"\bdo\s+100\s*km\s*/\s*h\b", "do stu kilometrów na godzinę"),
    rule(r"\bdo\s+90\s*km\s*/\s*h\b", "do dziewięćdziesięciu kilometrów na godzinę"),
    rule(r"\bdo\s+80\s*km\s*/\s*h\b", "do osiemdziesięciu kilometrów na godzinę"),
    rule(r"\bdo\s+70\s*km\s*/\s*h\b", "do siedemdziesięciu kilometrów na godzinę"),
    rule(r"\bdo\s+60\s*km\s*/\s*h\b", "do sześćdziesięciu kilometrów na godzinę"),
    rule(r"\bdo\s+50\s*km\s*/\s*h\b", "do pięćdziesięciu kilometrów na godzinę"),
    rule(r"\bdo\s+45\s*km\s*/\s*h\b", "do czterdziestu pięciu kilometrów na godzinę"),
    rule(r"\bdo\s+40\s*km\s*/\s*h\b", "do czterdziestu kilometrów na godzinę"),
    rule(r"\bdo\s+30\s*km\s*/\s*h\b", "do trzydziestu kilometrów na godzinę"),
    rule(r"\bdo\s+25\s*km\s*/\s*h\b", "do dwudziestu pięciu kilometrów na godzinę"),
    rule(r"\bdo\s+20\s*km\s*/\s*h\b", "do dwudziestu kilometrów na godzinę"),
    rule(r"\b140\s*km\s*/\s*h\b", "sto czterdzieści kilometrów na godzinę"),
    rule(r"\b120\s*km\s*/\s*h\b", "sto dwadzieścia kilometrów na godzinę"),
    rule(r"\b105\s*km\s*/\s*h\b", "sto pięć kilometrów na godzinę"),
    rule(r"\b100\s*km\s*/\s*h\b", "sto kilometrów na godzinę"),
    rule(r"\b90\s*km\s*/\s*h\b", "dziewięćdziesiąt kilometrów na godzinę"),
    rule(r"\b80\s*km\s*/\s*h\b", "osiemdziesiąt kilometrów na godzinę"),
    rule(r"\b70\s*km\s*/\s*h\b", "siedemdziesiąt kilometrów na godzinę"),
    rule(r"\b60\s*km\s*/\s*h\b", "sześćdziesiąt kilometrów na godzinę"),
    rule(r"\b50\s*km\s*/\s*h\b", "pięćdziesiąt kilometrów na godzinę"),
    rule(r"\b45\s*km\s*/\s*h\b", "czterdzieści pięć kilometrów na godzinę"),
    rule(r"\b40\s*km\s*/\s*h\b", "czterdzieści kilometrów na godzinę"),
    rule(r"\b30\s*km\s*/\s*h\b", "trzydzieści kilometrów na godzinę"),
    rule(r"\b25\s*km\s*/\s*h\b", "dwadzieścia pięć kilometrów na godzinę"),
    rule(r"\b20\s*km\s*/\s*h\b", "dwadzieścia kilometrów na godzinę"),
    rule(r"\b1,2\s*km\b", "jednego kilometra i dwustu metrów"),
    rule(r"\b50\s*[-–]\s*70\s*metrów\b", "od pięćdziesięciu do siedemdziesięciu metrów"),
    rule(r"\b30\s*[-–]\s*50\s*m\b", "od trzydziestu do pięćdziesięciu metrów"),
    rule(r"\bpowyżej\s+50\s*m\b", "powyżej pięćdziesięciu metrów"),
    rule(r"\bdo\s+50\s*m\b", "do pięćdziesięciu metrów"),
    rule(r"\bdo\s+60\s*m\b", "do sześćdziesięciu metrów"),
    rule(r"\b18,75\s*m\b", "osiemnaście metrów i siedemdziesiąt pięć centymetrów"),
    rule(r"\b3,8\s*metra\b", "trzech metrów i osiemdziesięciu centymetrów"),
    rule(r"\b2,8\s*metra\b", "dwóch metrów i osiemdziesięciu centymetrów"),
    rule(r"\b1,5\s*m\b", "półtora metra"),
    rule(r"\b0,5\s*m\b", "pół metra"),
    rule(r"\b0,5\s*metra\b", "pół metra"),
    rule(r"\b500\s*m\b", "pięćset metrów"),
    rule(r"\b200\s*m\b", "dwieście metrów"),
    rule(r"\b150\s*m\b", "stu pięćdziesięciu metrów"),
    rule(r"\b100\s*m\b", "sto metrów"),
    rule(r"\b70\s*m\b", "siedemdziesiąt metrów"),
    rule(r"\b60\s*m\b", "sześćdziesiąt metrów"),
    rule(r"\b50\s*m\b", "pięćdziesiąt metrów"),
    rule(r"\b40\s*m\b", "czterdzieści metrów"),
    rule(r"\b28\s*metrów\b", "dwudziestu ośmiu metrów"),
    rule(r"\b11\s*m\b", "jedenastu metrów"),
    rule(r"\b11\s*metrów\b", "jedenastu metrów"),
    rule(r"\b10\s*metrów\b", "dziesięciu metrów"),
    rule(r"\b5\s*m\b", "pięć metrów"),
    rule(r"\b4\s*m\b", "cztery metry"),
    rule(r"\b2\s*m\b", "dwa metry"),
    rule(r"\b1\s*m\b", "jeden metr"),
    rule(r"\b1\s*minuty\b", "jednej minuty"),
    rule(r"\b1\s*sekundę\b", "jedną sekundę"),
    rule(r"\b150\s*cm\b", "sto pięćdziesiąt centymetrów"),
    rule(r"\b135\s*cm\b", "sto trzydzieści pięć centymetrów"),
    rule(r"\b23\s*cm\b", "dwadzieścia trzy centymetry"),
    rule(r"\b1,6\s*mm\b", "jeden i sześć dziesiątych milimetra"),
    rule(r"\b11,5\s*tony\b", "jedenaście i pół tony"),
    rule(r"\b10\s*ton\b", "dziesięć ton"),
    rule(r"\b2,5\s*t\b", "dwóch i pół tony"),
    rule(r"\bbez względu na dmc\b", "bez względu na dopuszczalną masę całkowitą"),
    rule(r"\bdmc\b", "dopuszczalnej masy całkowitej"),
]


def normalize_text(text: str) -> tuple[str, list[str]]:
    replacements: list[str] = []
    normalized = text

    for replacement_rule in RULES:
        matches = list(replacement_rule.pattern.finditer(normalized))

        if not matches:
            continue

        for match in matches:
            replacements.append(f"{match.group(0)} => {replacement_rule.replacement}")

        normalized = replacement_rule.pattern.sub(replacement_rule.replacement, normalized)

    normalized = re.sub(r"\s+", " ", normalized).strip()

    return normalized, replacements


def manifest_items(payload: Any) -> list[dict[str, Any]]:
    if isinstance(payload, dict):
        items = payload.get("items", [])
    else:
        items = payload

    if not isinstance(items, list):
        raise ValueError("Manifest items must be a list.")

    return [item for item in items if isinstance(item, dict)]


def main() -> int:
    parser = argparse.ArgumentParser(description="Normalize question-audio manifest source_text for TTS.")
    parser.add_argument("--input", required=True, type=Path)
    parser.add_argument("--output", required=True, type=Path)
    parser.add_argument("--csv", required=True, type=Path)
    parser.add_argument("--report", required=True, type=Path)
    args = parser.parse_args()

    payload = json.loads(args.input.read_text(encoding="utf-8"))
    items = manifest_items(payload)
    rows: list[dict[str, Any]] = []

    for item in items:
        original = str(item.get("source_text") or "")
        normalized, replacements = normalize_text(original)

        if normalized != original:
            item["tts_source_text_original"] = original
            item["source_text"] = normalized

        rows.append(
            {
                "external_id": item.get("external_id"),
                "audio_type": item.get("audio_type"),
                "category_codes": ",".join(str(value) for value in item.get("category_codes") or []),
                "changed": "yes" if normalized != original else "no",
                "replacements": " | ".join(replacements),
                "source_text_original": original,
                "source_text_tts": normalized,
            }
        )

    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )

    args.csv.parent.mkdir(parents=True, exist_ok=True)
    with args.csv.open("w", newline="", encoding="utf-8-sig") as handle:
        writer = csv.DictWriter(
            handle,
            fieldnames=[
                "external_id",
                "audio_type",
                "category_codes",
                "changed",
                "replacements",
                "source_text_original",
                "source_text_tts",
            ],
        )
        writer.writeheader()
        writer.writerows(rows)

    changed = [row for row in rows if row["changed"] == "yes"]
    args.report.parent.mkdir(parents=True, exist_ok=True)
    args.report.write_text(
        "\n".join(
            [
                "# Specialist Correct Answer Audio TTS Normalization",
                "",
                f"- Input: `{args.input}`",
                f"- Output: `{args.output}`",
                f"- Items total: {len(rows)}",
                f"- Items changed for TTS: {len(changed)}",
                "",
                "Visible answer text, source hashes, and asset keys are unchanged in Laravel. "
                "Only generator-facing `source_text` is normalized in this manifest copy.",
                "",
            ]
        )
        + "\n",
        encoding="utf-8",
    )

    print(json.dumps({"items": len(rows), "changed": len(changed), "output": str(args.output)}, ensure_ascii=False))

    return 0


if __name__ == "__main__":
    raise SystemExit(main())

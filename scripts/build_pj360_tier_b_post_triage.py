from __future__ import annotations

import json
from collections import Counter, defaultdict
from pathlib import Path
from typing import Any


BASE_DIR = Path("output/analysis/pj360-compare")
OUTPUT_DIR = BASE_DIR / "post-triage"

EDITORIAL_ANSWER_IDS = {
    "3573",
    "6921",
    "7185",
    "7706",
    "7728",
    "8726",
}

MANUAL_ANSWER_IDS = {
    "10898",
    "10899",
    "11151",
}


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def write_csv(path: Path, rows: list[dict[str, Any]]) -> None:
    if not rows:
        path.write_text("", encoding="utf-8")
        return

    headers = list(rows[0].keys())
    lines = [";".join(headers)]
    for row in rows:
        values = []
        for header in headers:
            value = row.get(header, "")
            if isinstance(value, list):
                value = ", ".join(str(item) for item in value)
            elif value is None:
                value = ""
            else:
                value = str(value)
            values.append(value.replace("\n", " ").replace(";", ","))
        lines.append(";".join(values))

    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text("\n".join(lines), encoding="utf-8")


def main() -> None:
    tier_b = load_json(BASE_DIR / "queues" / "tier-b-review.json")
    high_risk = load_json(BASE_DIR / "high-risk-review" / "high-risk-enriched.json")
    media = load_json(BASE_DIR / "media-disambiguation" / "media-disambiguation-resolved.json")
    external_questions = load_json(BASE_DIR / "external_questions.json")

    high_risk_by_id = {item["local"]["gov_id"]: item for item in high_risk}
    external_by_site_id = {
        str(item["site_question_id"]): item
        for item in external_questions
        if item.get("site_question_id") is not None
    }

    external_prompt_count: dict[str, int] = defaultdict(int)
    for item in external_questions:
        external_prompt_count[item["prompt"]] += 1

    resolved_winners: dict[str, dict[str, Any]] = {}
    redirected_losers: dict[str, dict[str, Any]] = {}
    for item in media:
        local_id = item["local"]["gov_id"]
        resolved_id = item["resolved_gov_id"]
        site_question_id = str(item["external"]["site_question_id"])

        winner_payload = {
            "resolved_status": item["resolved_status"],
            "source_local_gov_id": local_id,
            "resolved_local_gov_id": resolved_id,
            "site_question_id": site_question_id,
            "external_url": item["external"]["url"],
            "prompt": item["local"]["prompt"],
        }
        resolved_winners[resolved_id] = winner_payload

        if local_id != resolved_id:
            redirected_losers[local_id] = winner_payload

    review_external_category_ids = {
        item["local"]["gov_id"]
        for item in high_risk
        if item["recommended_decision"] == "review_external_category_policy"
    }
    review_scope_ids = {
        item["local"]["gov_id"]
        for item in high_risk
        if item["recommended_decision"] == "usable_text_scope_conflict"
    }
    review_media_presence_ids = {
        item["local"]["gov_id"]
        for item in high_risk
        if item["recommended_decision"] == "review_media_presence_only"
    }

    packets: dict[str, list[dict[str, Any]]] = defaultdict(list)
    summary_counter: Counter[str] = Counter()

    for item in tier_b:
        local_id = item["local"]["gov_id"]
        prompt = item["local"]["prompt"]
        external = item.get("external")
        external_site_question_id = str(external["site_question_id"]) if external and external.get("site_question_id") else None
        reasons = item["reasons"]

        decision = "review_overlap_only"
        note = "Prompt overlap lub duplikat wymaga lekkiego review przed adaptacją."

        if local_id in resolved_winners:
            winner = resolved_winners[local_id]
            decision = "resolved_external_reference"
            note = "Mamy już rozstrzygnięty i przypisany właściwy rekord PJ360 dla tego lokalnego pytania."
            external_site_question_id = winner["site_question_id"]
            external = external_by_site_id.get(winner["site_question_id"], external)
        elif local_id in redirected_losers:
            redirect = redirected_losers[local_id]
            prompt_external_count = external_prompt_count.get(prompt, 0)
            if prompt_external_count == 1:
                decision = "manual_no_reference_after_redirect"
                note = (
                    "Jedyna znaleziona strona PJ360 dla tego promptu należy do innego lokalnego gov_id, "
                    "więc to pytanie zostaje bez bezpiecznej referencji z PJ360."
                )
            else:
                decision = "review_additional_external_candidates"
                note = (
                    "Bieżące dopasowanie zostało przekierowane do innego lokalnego gov_id; "
                    "trzeba sprawdzić, czy istnieje druga strona PJ360 dla tego promptu."
                )
        elif local_id in MANUAL_ANSWER_IDS:
            decision = "manual_only_answer_conflict"
            note = "Realny konflikt odpowiedzi. Nie wolno adaptować z PJ360 bez ręcznej decyzji."
        elif local_id in EDITORIAL_ANSWER_IDS:
            decision = "reference_usable_after_answer_review"
            note = "Rozjazd odpowiedzi jest redakcyjny; PJ360 nadaje się jako materiał referencyjny."
        elif local_id in review_scope_ids:
            decision = "review_scope_conflict"
            note = "Trzeba potwierdzić zgodność PODSTAWOWY/SPECJALISTYCZNY przed adaptacją."
        elif local_id in review_media_presence_ids:
            decision = "review_media_presence_only"
            note = "Treść wygląda używalnie, ale trzeba ręcznie potwierdzić kwestię obecności medium."
        elif local_id in review_external_category_ids:
            decision = "review_external_category_policy"
            note = "Trzeba sprawdzić politykę kategorii po stronie PJ360 przed użyciem referencji."
        elif "loose_prompt_only" in reasons:
            decision = "review_loose_prompt_only"
            note = "Dopasowanie jest tylko po luźniejszej normalizacji promptu i wymaga ręcznego potwierdzenia."
        elif "ambiguous_best_match" in reasons:
            decision = "review_ambiguous_overlap"
            note = "Kilka kandydatów wygląda podobnie; potrzeba krótkiego review wyboru referencji."
        elif "prompt_overlap_not_selected" in reasons or "local_prompt_duplicate" in reasons:
            decision = "review_overlap_only"
            note = "To overlap promptu bez twardego konfliktu odpowiedzi, kategorii ani medium."

        packet_row = {
            "gov_id": local_id,
            "decision": decision,
            "prompt": prompt,
            "categories": item["local"]["categories"],
            "structure_scope": item["local"]["structure_scope"],
            "question_type": item["local"]["question_type"],
            "question_media_kind": item["local"]["question_media_kind"],
            "reasons": reasons,
            "external_site_question_id": external_site_question_id,
            "external_url": external["url"] if external else "",
            "external_accepted_answer": external["accepted_answer"] if external else "",
            "note": note,
        }

        if local_id in redirected_losers:
            packet_row["redirected_to_local_gov_id"] = redirected_losers[local_id]["resolved_local_gov_id"]
        if local_id in resolved_winners:
            packet_row["resolved_from_local_gov_id"] = resolved_winners[local_id]["source_local_gov_id"]
            packet_row["resolution_method"] = resolved_winners[local_id]["resolved_status"]

        packets[decision].append(packet_row)
        summary_counter[decision] += 1

    summary = {
        "tier_b_total": len(tier_b),
        "decision_counts": dict(sorted(summary_counter.items())),
        "resolved_reference_total": summary_counter["resolved_external_reference"],
        "manual_total": (
            summary_counter["manual_no_reference_after_redirect"]
            + summary_counter["manual_only_answer_conflict"]
        ),
    }

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    write_json(OUTPUT_DIR / "tier-b-post-triage-summary.json", summary)
    for decision, rows in sorted(packets.items()):
        write_json(OUTPUT_DIR / f"{decision}.json", rows)
        write_csv(OUTPUT_DIR / f"{decision}.csv", rows)

    md_lines = [
        "# Post-triage Tier B summary",
        "",
        "Data: 2026-04-09",
        "",
        f"- `Tier B` lacznie: `{summary['tier_b_total']}`",
        f"- rekordy z juz rozstrzygnieta referencja PJ360: `{summary['resolved_reference_total']}`",
        f"- rekordy manualne po triage: `{summary['manual_total']}`",
        "",
        "## Breakdown",
        "",
    ]
    for key, value in sorted(summary["decision_counts"].items()):
        md_lines.append(f"- `{key}`: `{value}`")

    (OUTPUT_DIR / "tier-b-post-triage-summary.md").write_text("\n".join(md_lines), encoding="utf-8")

    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

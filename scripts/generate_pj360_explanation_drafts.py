from __future__ import annotations

import argparse
import csv
import json
import re
import unicodedata
from pathlib import Path
from typing import Any


DEFAULT_QUEUE_PATH = Path("output/analysis/pj360-compare/queues/tier-a-safe-auto.json")
DEFAULT_OUTPUT_DIR = Path("output/analysis/pj360-compare/drafts")


def normalize_text(value: str) -> str:
    value = value or ""
    value = unicodedata.normalize("NFKC", value)
    value = value.replace("\xa0", " ")
    value = re.sub(r"\s+", " ", value)
    return value.strip()


def normalize_key(value: str) -> str:
    value = normalize_text(value).lower()
    value = value.replace("’", "'").replace("`", "'").replace("„", '"').replace("”", '"')
    value = value.replace("–", "-").replace("—", "-")
    return value


def sentence_split(value: str) -> list[str]:
    value = normalize_text(value)
    if not value:
        return []

    parts = re.split(r"(?<=[.!?])\s+", value)
    return [part.strip() for part in parts if part.strip()]


def strip_source_prefix(text: str) -> str:
    text = normalize_text(text)
    text = re.sub(r'^"?(A|B|C|TAK|NIE)"?\s*[-–]\s*', "", text, flags=re.IGNORECASE)
    text = re.sub(r"^(tak|nie)\s*[-–]\s*", "", text, flags=re.IGNORECASE)
    text = re.sub(r"^(bowiem|poniewaz|ponieważ)\s*,?\s*", "", text, flags=re.IGNORECASE)
    return text.strip(" .")


def remove_leading_answer_echo(text: str, accepted_answer: str) -> str:
    accepted = normalize_key(accepted_answer)
    cleaned = normalize_text(text)

    if not cleaned:
        return ""

    cleaned_key = normalize_key(cleaned)
    if cleaned_key.startswith(accepted):
        cleaned = cleaned[len(accepted_answer):].lstrip(" ,.-:")

    cleaned = re.sub(r"^(jest|to|oznacza|wynika z tego, ze|wynika z tego, że)\s+", "", cleaned, flags=re.IGNORECASE)

    return cleaned.strip(" .")


def compress_source_reason(source_text: str, accepted_answer: str) -> str:
    source_text = strip_source_prefix(source_text)
    source_text = remove_leading_answer_echo(source_text, accepted_answer)

    sentences = sentence_split(source_text)
    if not sentences:
        return ""

    first_sentence = sentences[0]
    first_sentence = remove_leading_answer_echo(first_sentence, accepted_answer)

    replacements = {
        "bezwzględnie nie można": "nie wolno",
        "bezwzglednie nie mozna": "nie wolno",
        "jest obowiązany": "ma obowiazek",
        "jest zobowiązany": "ma obowiazek",
        "kierujący pojazdem": "kierujacy",
        "kierujący": "kierujacy",
        "powinien wiedzieć": "powinien pamietac",
        "może się spodziewać": "mozna sie spodziewac",
        "należy": "trzeba",
    }

    for source, target in replacements.items():
        first_sentence = re.sub(re.escape(source), target, first_sentence, flags=re.IGNORECASE)

    first_sentence = re.sub(r",?\s*co jest kluczowe[^.]*$", "", first_sentence, flags=re.IGNORECASE)
    first_sentence = re.sub(r",?\s*co umożliwia[^.]*$", "", first_sentence, flags=re.IGNORECASE)
    first_sentence = re.sub(r",?\s*pod warunkiem że[^.]*$", "", first_sentence, flags=re.IGNORECASE)
    first_sentence = re.sub(r",?\s*pod warunkiem ze[^.]*$", "", first_sentence, flags=re.IGNORECASE)
    first_sentence = re.sub(r",?\s*to jednak[^.]*$", "", first_sentence, flags=re.IGNORECASE)
    first_sentence = re.sub(r",?\s*jednak[^.]*$", "", first_sentence, flags=re.IGNORECASE)
    first_sentence = re.sub(r",?\s*gdyż\s*", ", bo ", first_sentence, flags=re.IGNORECASE)
    first_sentence = re.sub(r",?\s*ponieważ\s*", ", bo ", first_sentence, flags=re.IGNORECASE)
    first_sentence = re.sub(r",?\s*poniewaz\s*", ", bo ", first_sentence, flags=re.IGNORECASE)
    first_sentence = re.sub(r"\s+", " ", first_sentence).strip(" .")

    sentence_like_segments = [
        segment.strip(" .")
        for segment in re.split(r"(?<=[a-ząćęłńóśźż])\s+(?=[A-ZĄĆĘŁŃÓŚŹŻ])", first_sentence)
        if segment.strip(" .")
    ]
    if sentence_like_segments and len(sentence_like_segments[0]) >= 50:
        first_sentence = sentence_like_segments[0]

    clause_split = re.split(r"\s+(jednakże|jednakze|ale|lecz|natomiast)\s+", first_sentence, maxsplit=1, flags=re.IGNORECASE)
    if clause_split:
        candidate = clause_split[0].strip(" .")
        if len(candidate) >= 60:
            first_sentence = candidate

    if len(first_sentence) > 170:
        cutoff = first_sentence.rfind(" ", 0, 150)
        if cutoff >= 80:
            first_sentence = first_sentence[:cutoff].rstrip(" ,.;:") + "..."
        else:
            first_sentence = first_sentence[:167].rstrip(" ,.;:") + "..."

    trailing_words = first_sentence.split()
    if trailing_words and len(trailing_words[-1]) <= 4 and len(first_sentence) >= 80:
        first_sentence = " ".join(trailing_words[:-1]).rstrip(" ,.;:")

    return first_sentence.strip(" .")


def accepted_answer_label(local: dict[str, Any]) -> str | None:
    accepted = normalize_key(local["accepted_answer"])
    for label in ("a", "b", "c"):
        option = local.get(f"option_{label}")
        if option and normalize_key(option) == accepted:
            return label.upper()

    return None


def build_support_sentence(local: dict[str, Any], external: dict[str, Any]) -> str:
    media_kind = local["question_media_kind"]
    titles = {normalize_key(section.get("title") or "") for section in external.get("explanation_sections", [])}

    if "znaki drogowe" in titles:
        if media_kind == "video":
            return "Na nagraniu trzeba zwrocic uwage na oznakowanie i to, jak wplywa ono na dalszy przebieg sytuacji."
        return "Na obrazie kluczowe jest poprawne odczytanie oznakowania i jego znaczenia."
    if media_kind == "video":
        return "Na nagraniu najwazniejszy jest przebieg sytuacji, a nie pojedynczy detal."
    if media_kind == "image":
        return "Na obrazie kluczowe sa elementy sytuacji i ich znaczenie dla ruchu."
    if "kodeks drogowy" in titles:
        return "Rozstrzyga tu konkretna zasada ruchu drogowego, a nie intuicja kierujacego."

    return "W praktyce trzeba ocenic sytuacje zgodnie z zasadami ruchu drogowego, a nie tylko po pozorach."


def draft_first_sentence(local: dict[str, Any], reason: str) -> str:
    question_type = local["question_type"]
    accepted = normalize_text(local["accepted_answer"])
    accepted_key = normalize_key(accepted)

    if question_type == "boolean":
        verdict = "Tak" if accepted_key == "tak" else "Nie"
        if reason:
            return f"{verdict}, bo {reason}."
        return f"{verdict}."

    label = accepted_answer_label(local)
    if label and reason:
        return f"Poprawna jest odpowiedz {label}, bo {reason}."
    if label:
        return f"Poprawna jest odpowiedz {label}."
    if reason:
        return f"Poprawna jest wlasnie ta odpowiedz, bo {reason}."

    return "Poprawna jest wlasnie ta odpowiedz."


def reason_looks_dangling(reason: str) -> bool:
    normalized = normalize_text(reason)
    if not normalized:
        return False

    words = re.findall(r"[\wąćęłńóśźż-]+", normalized.lower(), flags=re.IGNORECASE)
    dangling_words = {
        "jest",
        "sa",
        "są",
        "ma",
        "maja",
        "mają",
        "byl",
        "była",
        "było",
        "byly",
        "były",
        "innymi",
        "tylko",
        "oraz",
        "albo",
        "których",
        "ktorych",
        "którym",
        "ktorym",
        "której",
        "ktorej",
        "mozliwosc",
        "możliwość",
        "znak",
        "znaki",
        "oznakowanie",
    }

    if normalized.endswith(",") or normalized.endswith("-") or normalized.endswith("..."):
        return True
    if re.search(r",\s*(bo|poniewaz|ponieważ)\s*$", normalized, flags=re.IGNORECASE):
        return True
    if words and words[-1] in dangling_words:
        return True
    if words and len(words[-1]) <= 3 and len(normalized) >= 80:
        return True

    return False


def draft_fallback_reason(local: dict[str, Any]) -> str:
    if local["question_type"] == "boolean":
        accepted_key = normalize_key(local["accepted_answer"])
        if accepted_key == "tak":
            return "w tej sytuacji warunki rzeczywiscie pozwalaja na takie zachowanie"
        return "w tej sytuacji przepisy albo okolicznosci na to nie pozwalaja"

    return "tylko ta odpowiedz odpowiada zasadom obowiazujacym w tej sytuacji"


def build_draft_text(local: dict[str, Any], external: dict[str, Any]) -> str:
    reason = compress_source_reason(external.get("explanation_text") or "", local["accepted_answer"])
    if reason_looks_dangling(reason):
        reason = draft_fallback_reason(local)
    first_sentence = draft_first_sentence(local, reason)
    support_sentence = build_support_sentence(local, external)

    parts = [first_sentence]

    if support_sentence:
        parts.append(support_sentence)

    if local["question_type"] == "boolean":
        parts.append("Zapamietaj: w takich pytaniach liczy sie to, czy warunki sytuacji rzeczywiscie pozwalaja na takie zachowanie.")
    else:
        parts.append("Pozostale odpowiedzi odpadaja, bo nie pasuja do zasad obowiazujacych w tej sytuacji.")

    return " ".join(part.strip() for part in parts if part.strip())


def token_set(text: str) -> set[str]:
    normalized = normalize_key(text)
    return {token for token in re.findall(r"[\wąćęłńóśźż]+", normalized, flags=re.IGNORECASE) if len(token) > 2}


def token_overlap_ratio(left: str, right: str) -> float:
    left_tokens = token_set(left)
    right_tokens = token_set(right)
    if not left_tokens or not right_tokens:
        return 0.0

    intersection = len(left_tokens & right_tokens)
    union = len(left_tokens | right_tokens)

    return intersection / union if union else 0.0


def build_quality_flags(
    local: dict[str, Any],
    external: dict[str, Any],
    draft_text: str,
    source_summary: str = "",
) -> list[str]:
    flags: list[str] = []
    source_text = external.get("explanation_text") or ""
    normalized_source_reference = normalize_text(strip_source_prefix(source_text))
    overlap = token_overlap_ratio(draft_text, source_text)

    if not source_text:
        flags.append("missing_source_explanation")
    if len(sentence_split(draft_text)) < 2:
        flags.append("too_few_sentences")
    if len(sentence_split(draft_text)) > 4:
        flags.append("too_many_sentences")
    if len(draft_text) > 420:
        flags.append("draft_too_long")
    if overlap >= 0.55:
        flags.append("high_source_overlap")
    if local["question_type"] != "boolean" and accepted_answer_label(local) is None:
        flags.append("missing_answer_label")
    if (
        local["question_media_kind"] == "none"
        and not external.get("explanation_sections")
        and len(normalized_source_reference) < 100
    ):
        flags.append("low_context_reference")

    return flags


def build_draft_packet(item: dict[str, Any]) -> dict[str, Any]:
    local = item["local"]
    external = item["external"]
    draft_text = build_draft_text(local, external)
    source_summary = compress_source_reason(external.get("explanation_text") or "", local["accepted_answer"])
    quality_flags = build_quality_flags(local, external, draft_text, source_summary)

    return {
        "gov_id": local["gov_id"],
        "prompt": local["prompt"],
        "categories": local["categories"],
        "question_type": local["question_type"],
        "question_media_kind": local["question_media_kind"],
        "structure_scope": local["structure_scope"],
        "accepted_answer": local["accepted_answer"],
        "accepted_answer_label": accepted_answer_label(local),
        "external_site_question_id": external.get("site_question_id"),
        "external_url": external["url"],
        "external_question_media_kind": external["question_media_kind"],
        "external_has_explanation_video": bool(external.get("explanation_video_url")),
        "source_summary": source_summary,
        "draft_text": draft_text,
        "quality_flags": quality_flags,
        "publish_ready": len(quality_flags) == 0,
    }


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


def build_summary(drafts: list[dict[str, Any]]) -> dict[str, Any]:
    flag_counts: dict[str, int] = {}
    for draft in drafts:
        for flag in draft["quality_flags"]:
            flag_counts[flag] = flag_counts.get(flag, 0) + 1

    return {
        "draft_count": len(drafts),
        "publish_ready_count": sum(1 for draft in drafts if draft["publish_ready"]),
        "needs_review_count": sum(1 for draft in drafts if not draft["publish_ready"]),
        "quality_flag_counts": dict(sorted(flag_counts.items())),
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Generuje drafty wyjasnien dla Tier A.")
    parser.add_argument(
        "--queue-path",
        type=Path,
        default=DEFAULT_QUEUE_PATH,
        help="Plik JSON z kolejka Tier A.",
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
    tier_a_items = load_json(args.queue_path)
    drafts = [build_draft_packet(item) for item in tier_a_items]
    summary = build_summary(drafts)

    args.output_dir.mkdir(parents=True, exist_ok=True)
    write_json(args.output_dir / "tier-a-draft-packets.json", drafts)
    write_json(args.output_dir / "tier-a-draft-summary.json", summary)
    write_csv(args.output_dir / "tier-a-draft-packets.csv", drafts)

    print(json.dumps(summary, ensure_ascii=False, indent=2))
    print(f"Zapisano drafty do: {args.output_dir}")


if __name__ == "__main__":
    main()

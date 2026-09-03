from __future__ import annotations

import argparse
import json
import mimetypes
import shutil
import subprocess
from pathlib import Path
from typing import Any

from bs4 import BeautifulSoup

from zdamyto_scrape import ZdamytoScraper


CATEGORY_META = {
    "A": {"name": "Kategoria A", "sort_order": 10},
    "B": {"name": "Kategoria B", "sort_order": 20},
    "C": {"name": "Kategoria C", "sort_order": 30},
    "D": {"name": "Kategoria D", "sort_order": 40},
    "T": {"name": "Kategoria T", "sort_order": 50},
}

SUPPLEMENTARY_SECTION_PREFIXES = (
    "dodatkowe-pytania-uzupelniajace",
    "nowe-grudzien-2025",
)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Prepare a catalog:import-json payload from the staged zdamyto scrape.",
    )
    parser.add_argument(
        "--source-root",
        type=Path,
        default=Path(r"C:\Users\xxx\Desktop\serwistestyprawojazdy\storage\app\zdamyto-scrape-full"),
    )
    parser.add_argument(
        "--output-root",
        type=Path,
        default=Path(r"C:\Users\xxx\Desktop\serwistestyprawojazdy\storage\app\zdamyto-import"),
    )
    parser.add_argument(
        "--media-root",
        type=Path,
        default=Path(r"D:\datasets\mi-prawo-jazdy-2026\app-media"),
    )
    return parser.parse_args()


def load_jsonl(path: Path) -> list[dict[str, Any]]:
    rows: list[dict[str, Any]] = []
    with path.open("r", encoding="utf-8") as handle:
        for line in handle:
            line = line.strip()
            if line:
                rows.append(json.loads(line))
    return rows


def normalize_slug_list(occurrences: list[dict[str, Any]], category_code: str) -> list[str]:
    seen: set[str] = set()
    slugs: list[str] = []
    for occurrence in occurrences:
        if occurrence.get("category_code") != category_code:
            continue
        slug = str(occurrence.get("section_slug") or "").strip()
        if not slug or slug in seen:
            continue
        seen.add(slug)
        slugs.append(slug)
    return slugs


def choose_primary_section_slug(section_slugs: list[str]) -> str | None:
    if not section_slugs:
        return None

    preferred = [
        slug
        for slug in section_slugs
        if not any(slug.startswith(prefix) for prefix in SUPPLEMENTARY_SECTION_PREFIXES)
    ]

    return (preferred or section_slugs)[0]


def infer_structure_scope(primary_slug: str | None) -> str:
    if not primary_slug:
        return "PODSTAWOWY"

    basic_prefixes = (
        "znaki-ostrzegawcze",
        "znaki-zakazu-nakazu",
        "znaki-informacyjne-kierunku-i-miejscowosci-uzupelniajace",
        "znaki-drogowe-poziome",
        "sygnaly-swietlne-sygnaly-dawane-przez-kierujacego-ruchem",
        "wlaczanie-sie-do-ruchu-skrzyzowania-rownorzedne",
        "skrzyzowania-ze-znakami-okreslajacymi-pierwszenstwo-przejazdu",
        "skrzyzowania-z-sygnalizacje-swietlna",
        "skrzyzowania-lub-przejscia-dla-pieszych-z-kierujacych-ruchem-miejsca-przystankow-komunikacji-publicznej",
        "pozycja-pojazdu-na-drodze-wjazd-i-zjazd-ze-skrzyzowania-zatrzymanie-i-postoj",
        "zmiana-pasa-ruchu-zmiana-kierunku-jazdy",
        "wyprzedzanie",
        "omijanie-wymijanie-cofanie",
        "uzywanie-swiatel-zewnetrznych-i-sygnalow-pojazdu",
        "znaczenie-zachowania-szczegolnej-ostrosnosci-w-stosunku-do-innych-uzytkownikow-drogi-wysiadanie-z-pojazdu-zabezpieczenie-pojazdu",
        "zachowanie-wobec-pieszego-wobec-osoby-o-ograniczonej-mozliwosci-poruszania-sie",
        "zachowanie-wobec-rowerzysty-i-dzieci",
        "zachowanie-na-przejazdach-kolejowych-i-tramwajowych",
        "ogolne-zasady-okreslajace-zachowanie-kierowcy-w-momencie-awarii-lub-wypadku-udzielanie-pierwszej-pomocy-przedmedycznej",
        "spostrzeganie-ocena-sytuacji-i-podejmowanie-decyzji",
    )

    if any(primary_slug.startswith(prefix) for prefix in basic_prefixes):
        return "PODSTAWOWY"

    return "SPECJALISTYCZNY"


def repaired_answers(question_id: str, raw_dir: Path) -> list[dict[str, Any]]:
    html_path = raw_dir / f"{question_id}.html"
    if not html_path.exists():
        return []

    html = html_path.read_text(encoding="utf-8")
    soup = BeautifulSoup(html, "html.parser")
    parser = ZdamytoScraper(email="", password="", out_dir=raw_dir.parent.parent)
    return parser.extract_answers(soup)


def answer_payload(answers: list[dict[str, Any]]) -> tuple[str, str, str | None, str]:
    if len(answers) < 2:
        raise ValueError("Question has fewer than two answers after repair.")

    labels = [str(answer.get("label") or "").strip() for answer in answers]
    keys = [str(answer.get("key") or "").upper() for answer in answers]
    correct = next((answer for answer in answers if answer.get("is_correct")), None)
    if correct is None:
        raise ValueError("Question has no marked correct answer.")

    if len(answers) == 2 and {label.lower() for label in labels} == {"tak", "nie"}:
        answers_by_label = {label.lower(): label for label in labels}
        correct_key = "a" if str(correct.get("label")).lower() == "tak" else "b"
        return (
            answers_by_label.get("tak", "Tak"),
            answers_by_label.get("nie", "Nie"),
            None,
            correct_key,
        )

    ordered: dict[str, str] = {}
    for answer in answers:
        key = str(answer.get("key") or "").upper()
        if key in {"A", "B", "C"}:
            ordered[key] = str(answer.get("label") or "").strip()

    if {"A", "B"} - ordered.keys():
        raise ValueError("Question is missing A/B options after repair.")

    correct_key = str(correct.get("key") or "").lower()
    if correct_key not in {"a", "b", "c"}:
        raise ValueError("Question correct answer key is invalid after repair.")

    return (
        ordered["A"],
        ordered["B"],
        ordered.get("C"),
        correct_key,
    )


def mime_type_for_path(path: Path) -> str | None:
    guessed, _ = mimetypes.guess_type(path.name)
    return guessed


def poster_target_for_relative_media(target_relative: str) -> str | None:
    target_path = Path(target_relative)
    if target_path.suffix.lower() != ".mp4":
        return None

    return str(target_path.with_name(f"{target_path.stem}-poster.jpg")).replace("\\", "/")


def generate_video_poster(source_video: Path, target_poster: Path) -> None:
    target_poster.parent.mkdir(parents=True, exist_ok=True)

    primary = subprocess.run(
        [
            "ffmpeg",
            "-y",
            "-ss",
            "00:00:00.8",
            "-i",
            str(source_video),
            "-frames:v",
            "1",
            "-vf",
            "scale='min(1280,iw)':-2",
            "-q:v",
            "3",
            str(target_poster),
        ],
        capture_output=True,
        text=True,
    )

    if primary.returncode == 0 and target_poster.exists() and target_poster.stat().st_size > 0:
        return

    fallback = subprocess.run(
        [
            "ffmpeg",
            "-y",
            "-i",
            str(source_video),
            "-frames:v",
            "1",
            "-vf",
            "scale='min(1280,iw)':-2",
            "-q:v",
            "3",
            str(target_poster),
        ],
        capture_output=True,
        text=True,
    )

    if fallback.returncode != 0 or not target_poster.exists() or target_poster.stat().st_size <= 0:
        stderr = (fallback.stderr or primary.stderr or "").strip()
        raise RuntimeError(stderr or f"Could not generate poster for {source_video}.")


def relative_media_target(question_id: str, source_media_path: str) -> str:
    source_name = Path(source_media_path).name
    kind = "video" if source_name.lower().endswith(".mp4") else "image"
    return str(Path("media") / "questions" / "zdamyto" / question_id / kind / source_name).replace("\\", "/")


def copy_media_files(rows: list[dict[str, Any]], media_root: Path, source_root: Path) -> dict[str, dict[str, Any]]:
    copied: dict[str, dict[str, Any]] = {}

    for row in rows:
        for media in row.get("media", []):
            local_path = source_root / str(media.get("local_path") or "")
            if not local_path.exists():
                continue

            target_relative = relative_media_target(str(row["question_id"]), str(media["local_path"]))
            if target_relative in copied:
                continue

            target_path = media_root / target_relative
            target_path.parent.mkdir(parents=True, exist_ok=True)

            if not target_path.exists() or target_path.stat().st_size != local_path.stat().st_size:
                shutil.copy2(local_path, target_path)

            poster_relative = poster_target_for_relative_media(target_relative)
            if poster_relative:
                poster_target = media_root / poster_relative
                if not poster_target.exists() or poster_target.stat().st_size <= 0:
                    generate_video_poster(target_path, poster_target)
            else:
                poster_relative = None

            copied[target_relative] = {
                "bytes": target_path.stat().st_size,
                "mime_type": mime_type_for_path(target_path),
                "path": target_relative,
                "kind": media.get("kind"),
                "poster_path": poster_relative,
            }

    return copied


def build_media_payload(
    row: dict[str, Any],
    copied_media: dict[str, dict[str, Any]],
) -> list[dict[str, Any]]:
    payload: list[dict[str, Any]] = []

    for index, media in enumerate(row.get("media", [])):
        target_relative = relative_media_target(str(row["question_id"]), str(media["local_path"]))
        copied = copied_media.get(target_relative)
        if copied is None:
            continue

        payload.append(
            {
                "kind": "video" if copied["kind"] == "video" else "image",
                "disk": "media_local",
                "path": copied["path"],
                "poster_path": copied.get("poster_path"),
                "mime_type": copied["mime_type"],
                "bytes": copied["bytes"],
                "duration_seconds": None,
                "width": None,
                "height": None,
                "variant": "full",
                "sort_order": index,
                "metadata": {
                    "asset_group": f"zdamyto:{row['question_id']}:{copied['kind']}",
                    "source_site": "zdamyto.com",
                    "remote_url": media.get("remote_url"),
                },
            }
        )

    return payload


def main() -> int:
    args = parse_args()
    source_root = args.source_root
    output_root = args.output_root
    output_root.mkdir(parents=True, exist_ok=True)

    raw_question_dir = source_root / "raw" / "question-pages"
    source_questions_path = source_root / "data" / "questions.jsonl"
    rows = load_jsonl(source_questions_path)
    copied_media = copy_media_files(rows, args.media_root, source_root)

    categories_payload: dict[str, dict[str, Any]] = {}
    skipped_questions: list[dict[str, Any]] = []
    import_rows_total = 0

    for row in rows:
        question_id = str(row["question_id"])
        answers = repaired_answers(question_id, raw_question_dir) or row.get("answers", [])

        try:
            option_a, option_b, option_c, correct_answer = answer_payload(answers)
        except ValueError as exception:
            skipped_questions.append(
                {
                    "question_id": question_id,
                    "reason": str(exception),
                    "prompt": row.get("prompt"),
                }
            )
            continue

        occurrences = row.get("occurrences", [])
        unique_category_codes = sorted({str(occ.get("category_code") or "").upper() for occ in occurrences if occ.get("category_code")})

        if not unique_category_codes:
            skipped_questions.append(
                {
                    "question_id": question_id,
                    "reason": "Question has no category occurrences.",
                    "prompt": row.get("prompt"),
                }
            )
            continue

        media_payload = build_media_payload(row, copied_media)
        main_media_original = Path(str(row["media"][0]["local_path"])).name if row.get("media") else ""

        for category_code in unique_category_codes:
            category_meta = CATEGORY_META.get(category_code, {"name": f"Kategoria {category_code}", "sort_order": 999})
            category_bucket = categories_payload.setdefault(
                category_code,
                {
                    "code": category_code,
                    "name": category_meta["name"],
                    "slug": category_code.lower(),
                    "sort_order": category_meta["sort_order"],
                    "description": f"Pytania z serwisu zdamyto dla kategorii {category_code}.",
                    "questions": [],
                },
            )

            section_slugs = normalize_slug_list(occurrences, category_code)
            primary_section_slug = choose_primary_section_slug(section_slugs)
            structure_scope = infer_structure_scope(primary_section_slug)

            category_bucket["questions"].append(
                {
                    "external_id": f"zdamyto:{question_id}",
                    "prompt": row.get("prompt"),
                    "explanation": row.get("explanation"),
                    "option_a": option_a,
                    "option_b": option_b,
                    "option_c": option_c,
                    "correct_answer": correct_answer,
                    "difficulty": 1,
                    "points": 1,
                    "question_type": "boolean" if option_c is None and {option_a.lower(), option_b.lower()} == {"tak", "nie"} else "single_choice",
                    "is_active": True,
                    "source": "zdamyto",
                    "published_at": None,
                    "metadata": {
                        "source_site": "zdamyto.com",
                        "source_question_id": question_id,
                        "source_question_url": row.get("question_url"),
                        "source_explanation": row.get("explanation"),
                        "legal_basis": row.get("legal_basis"),
                        "main_media_original": main_media_original,
                        "structure_scope": structure_scope,
                        "zdamyto_category_tags": row.get("category_tags", []),
                        "zdamyto_primary_section_slug": primary_section_slug,
                        "zdamyto_section_slugs": section_slugs,
                        "zdamyto_occurrences": [
                            occurrence
                            for occurrence in occurrences
                            if str(occurrence.get("category_code") or "").upper() == category_code
                        ],
                    },
                    "media": media_payload,
                }
            )
            import_rows_total += 1

    payload = {
        "batch_id": "zdamyto-full-import",
        "categories": [categories_payload[code] for code in sorted(categories_payload.keys())],
    }

    payload_path = output_root / "zdamyto-catalog.json"
    summary_path = output_root / "summary.json"
    skipped_path = output_root / "skipped_questions.json"

    payload_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    skipped_path.write_text(json.dumps(skipped_questions, ensure_ascii=False, indent=2), encoding="utf-8")
    summary = {
        "source_questions_total": len(rows),
        "import_rows_total": import_rows_total,
        "categories_total": len(categories_payload),
        "copied_media_total": len(copied_media),
        "skipped_questions_total": len(skipped_questions),
        "payload_path": str(payload_path),
        "skipped_path": str(skipped_path),
    }
    summary_path.write_text(json.dumps(summary, ensure_ascii=False, indent=2), encoding="utf-8")

    print(json.dumps(summary, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

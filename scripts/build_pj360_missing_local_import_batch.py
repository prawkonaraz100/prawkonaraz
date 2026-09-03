from __future__ import annotations

import argparse
import json
import os
import shutil
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Any
from urllib.parse import urlparse
from urllib.request import Request, urlopen

import psycopg


ROOT = Path(__file__).resolve().parents[1]
ANALYSIS_DIR = ROOT / "output" / "analysis" / "pj360-exact-topic-membership"
EXTERNAL_QUESTIONS_PATH = ROOT / "output" / "analysis" / "pj360-compare" / "external_questions.json"
IMPORT_ROOT = ROOT / "storage" / "app" / "import-batches"
OVERRIDE_ROOT = ROOT / "resources" / "topic-overrides"
DB_DSN = "host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit"
USER_AGENT = "Mozilla/5.0 (compatible; Codex PJ360 Import Builder/1.0)"


@dataclass
class CategoryContext:
    code: str
    name: str
    slug: str
    description: str | None
    is_active: bool
    sort_order: int


def resolve_media_root() -> Path:
    env_path = ROOT / ".env"
    if env_path.exists():
        for line in env_path.read_text(encoding="utf-8").splitlines():
            if line.startswith("MEDIA_LOCAL_ROOT="):
                raw = line.split("=", 1)[1].strip().strip('"').strip("'")
                if raw:
                    return Path(raw)

    return ROOT / "storage" / "app" / "public-media"


MEDIA_ROOT = resolve_media_root()


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Build a PJ360 missing-local import batch for one category.")
    parser.add_argument("--category", default="B", help="License category code, e.g. B")
    parser.add_argument(
        "--skip-download",
        action="store_true",
        help="Do not download PJ360 media assets; reuse only already cached files.",
    )
    return parser.parse_args()


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def load_optional_records(path: Path) -> dict[str, Any]:
    if not path.exists():
        return {"records": []}

    payload = load_json(path)
    if not isinstance(payload, dict):
        return {"records": []}

    if not isinstance(payload.get("records"), list):
        payload["records"] = []

    return payload


def dump_json(path: Path, payload: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")


def normalize_yes_no(value: str) -> str:
    return value.strip().lower().replace(".", "")


def looks_like_letter_answer(value: str) -> bool:
    return value.strip().lower() in {"a", "b", "c"}


def safe_slug(value: str) -> str:
    return "".join(char.lower() if char.isalnum() else "-" for char in value).strip("-")


def guess_mime_type(url: str | None, media_kind: str) -> str | None:
    if not url:
        return None

    suffix = Path(urlparse(url).path).suffix.lower()

    if media_kind == "image":
        return {
            ".jpg": "image/jpeg",
            ".jpeg": "image/jpeg",
            ".png": "image/png",
            ".webp": "image/webp",
            ".avif": "image/avif",
        }.get(suffix, "image/jpeg")

    if media_kind == "video":
        return {
            ".mp4": "video/mp4",
        }.get(suffix, "video/mp4")

    return None


def build_answer_payload(external_question: dict[str, Any]) -> dict[str, Any]:
    accepted_raw = str(external_question.get("accepted_answer") or "").strip()
    suggested = [str(item).strip() for item in (external_question.get("suggested_answers") or []) if str(item).strip()]
    accepted_normalized = normalize_yes_no(accepted_raw)
    suggested_normalized = [normalize_yes_no(item) for item in suggested]

    if accepted_normalized in {"tak", "nie"} and {"tak", "nie"}.issubset({accepted_normalized, *suggested_normalized}):
        return {
            "question_type": "boolean",
            "option_a": "Tak",
            "option_b": "Nie",
            "option_c": None,
            "correct_answer": "a" if accepted_normalized == "tak" else "b",
        }

    if looks_like_letter_answer(accepted_raw) and all(looks_like_letter_answer(item) for item in suggested):
        labels = []
        for candidate in ["a", "b", "c"]:
            if candidate == accepted_raw.strip().lower() or candidate in {item.strip().lower() for item in suggested}:
                labels.append(candidate.upper())

        labels = labels[:3]

        while len(labels) < 2:
            labels.append(chr(ord("A") + len(labels)))

        return {
            "question_type": "single_choice",
            "option_a": labels[0],
            "option_b": labels[1],
            "option_c": labels[2] if len(labels) > 2 else None,
            "correct_answer": accepted_raw.strip().lower(),
        }

    choices = [accepted_raw, *suggested]

    while len(choices) < 2:
        choices.append("")

    return {
        "question_type": "single_choice",
        "option_a": choices[0],
        "option_b": choices[1],
        "option_c": choices[2] if len(choices) > 2 else None,
        "correct_answer": "a",
    }


def ensure_media_asset(url: str, category_code: str, external_id: str, media_kind: str, skip_download: bool) -> dict[str, Any]:
    parsed = urlparse(url)
    suffix = Path(parsed.path).suffix.lower() or (".mp4" if media_kind == "video" else ".jpg")
    relative_path = Path("imports") / "pj360" / category_code.lower() / external_id / f"full{suffix}"
    absolute_path = MEDIA_ROOT / relative_path

    if not absolute_path.exists():
        if skip_download:
            raise RuntimeError(f"Brak lokalnego media assetu i wlaczono --skip-download: {absolute_path}")

        absolute_path.parent.mkdir(parents=True, exist_ok=True)
        request = Request(url, headers={"User-Agent": USER_AGENT})

        with urlopen(request, timeout=120) as response, absolute_path.open("wb") as destination:
            shutil.copyfileobj(response, destination)

    return {
        "kind": media_kind,
        "disk": "media_local",
        "path": relative_path.as_posix(),
        "poster_path": None,
        "mime_type": guess_mime_type(url, media_kind),
        "bytes": absolute_path.stat().st_size,
        "variant": "full",
        "metadata": {
            "source_url": url,
            "import_batch": "pj360_missing_local",
        },
    }


def category_context(category_code: str) -> CategoryContext:
    with psycopg.connect(DB_DSN) as connection, connection.cursor() as cursor:
        cursor.execute(
            """
            select code, name, slug, description, is_active, sort_order
            from license_categories
            where code = %s
            """,
            (category_code,),
        )
        row = cursor.fetchone()

    if row is None:
        raise RuntimeError(f"Nie znaleziono kategorii {category_code} w bazie.")

    return CategoryContext(
        code=row[0],
        name=row[1],
        slug=row[2],
        description=row[3],
        is_active=bool(row[4]),
        sort_order=int(row[5]),
    )


def load_db_question(question_id: int) -> dict[str, Any]:
    with psycopg.connect(DB_DSN) as connection, connection.cursor() as cursor:
        cursor.execute(
            """
            select
                q.id,
                lc.code,
                q.external_id,
                q.prompt,
                q.explanation,
                q.option_a,
                q.option_b,
                q.option_c,
                q.correct_answer,
                q.difficulty,
                q.points,
                q.question_type,
                q.is_active,
                q.requires_primary_media,
                q.delivery_issue,
                q.source,
                q.published_at,
                q.metadata
            from questions q
            join license_categories lc on lc.id = q.license_category_id
            where q.id = %s
            """,
            (question_id,),
        )
        row = cursor.fetchone()

        if row is None:
            raise RuntimeError(f"Nie znaleziono pytania {question_id} w bazie.")

        cursor.execute(
            """
            select
                kind,
                disk,
                path,
                poster_path,
                mime_type,
                bytes,
                duration_seconds,
                width,
                height,
                variant,
                sort_order,
                metadata
            from question_media
            where question_id = %s
            order by sort_order, id
            """,
            (question_id,),
        )
        media_rows = cursor.fetchall()

    question = {
        "id": row[0],
        "license_category_code": row[1],
        "external_id": row[2],
        "prompt": row[3],
        "explanation": row[4],
        "option_a": row[5],
        "option_b": row[6],
        "option_c": row[7],
        "correct_answer": row[8],
        "difficulty": row[9],
        "points": row[10],
        "question_type": row[11],
        "is_active": bool(row[12]),
        "requires_primary_media": bool(row[13]),
        "delivery_issue": row[14],
        "source": row[15],
        "published_at": row[16].isoformat() if row[16] else None,
        "metadata": row[17] or {},
        "media": [
            {
                "kind": media_row[0],
                "disk": media_row[1],
                "path": media_row[2],
                "poster_path": media_row[3],
                "mime_type": media_row[4],
                "bytes": media_row[5],
                "duration_seconds": media_row[6],
                "width": media_row[7],
                "height": media_row[8],
                "variant": media_row[9],
                "sort_order": media_row[10],
                "metadata": media_row[11] or {},
            }
            for media_row in media_rows
        ],
    }

    return question


def build_external_question_payload(
    record: dict[str, Any],
    external_question: dict[str, Any],
    category: CategoryContext,
    skip_download: bool,
) -> tuple[dict[str, Any], dict[str, Any]]:
    site_question_id = str(record["internal_question_id"])
    import_external_id = f"pj360:{site_question_id}"
    media_kind = str(record.get("question_media_kind") or "none")
    media_url = record.get("question_media_url") or external_question.get("question_media_url")
    answer_payload = build_answer_payload(external_question)
    structure_scope = str(external_question.get("structure_scope") or "PODSTAWOWY")

    metadata = {
        "source_site": "prawo-jazdy-360.pl",
        "structure_scope": structure_scope,
        "pj360_site_question_id": site_question_id,
        "pj360_url": external_question.get("url"),
        "pj360_topic_key": record["topic_key"],
        "pj360_topic_label": record["topic_label"],
        "pj360_page": record.get("page"),
        "pj360_position_on_page": record.get("position_on_page"),
        "pj360_missing_local_import": True,
        "pj360_categories": external_question.get("categories") or [category.code],
    }

    media_payload: list[dict[str, Any]] = []

    if media_kind != "none" and media_url:
        media_entry = ensure_media_asset(
            url=str(media_url),
            category_code=category.code,
            external_id=site_question_id,
            media_kind=media_kind,
            skip_download=skip_download,
        )
        metadata["main_media_original"] = Path(urlparse(str(media_url)).path).name or f"{site_question_id}{Path(media_entry['path']).suffix}"
        media_payload.append(media_entry)
    else:
        metadata["main_media_original"] = ""

    question_payload = {
        "external_id": import_external_id,
        "prompt": external_question["prompt"],
        "explanation": external_question.get("explanation_text"),
        "option_a": answer_payload["option_a"],
        "option_b": answer_payload["option_b"],
        "option_c": answer_payload["option_c"],
        "correct_answer": answer_payload["correct_answer"],
        "difficulty": 1,
        "points": 1,
        "question_type": answer_payload["question_type"],
        "is_active": True,
        "source": "pj360",
        "published_at": datetime.now(timezone.utc).isoformat(),
        "metadata": metadata,
        "media": media_payload,
    }

    override_row = {
        "license_category_code": category.code,
        "source": "pj360",
        "external_id": import_external_id,
        "question_topic_key": record["topic_key"],
        "reason": "pj360_missing_local_import",
        "metadata": {
            "topic_label": record["topic_label"],
            "pj360_site_question_id": site_question_id,
            "import_origin": "truly_missing_local",
        },
    }

    return question_payload, override_row


def build_clone_question_payload(
    record: dict[str, Any],
    candidate_question: dict[str, Any],
    category: CategoryContext,
) -> tuple[dict[str, Any], dict[str, Any]]:
    metadata = dict(candidate_question.get("metadata") or {})
    metadata.update(
        {
            "structure_scope": "SPECJALISTYCZNY" if record["topic_key"] in {
                "speed_limits",
                "safety_equipment_and_restraints",
                "distances_and_braking",
                "risk_conditions_weather_and_time",
                "driver_field_of_view",
                "driving_technique",
                "vehicle_load_and_passenger_safety",
                "owner_obligations_insurance_documents",
                "mechanical_aspects_of_road_safety",
                "rescue_actions",
                "driving_with_trailer",
            } else "PODSTAWOWY",
            "pj360_missing_local_import": True,
            "pj360_recovered_from_other_category": candidate_question["license_category_code"],
            "pj360_topic_key": record["topic_key"],
            "pj360_topic_label": record["topic_label"],
            "pj360_site_question_id": str(record["internal_question_id"]),
            "pj360_page": record.get("page"),
            "pj360_position_on_page": record.get("position_on_page"),
        }
    )

    question_payload = {
        "external_id": candidate_question["external_id"],
        "prompt": candidate_question["prompt"],
        "explanation": candidate_question["explanation"],
        "option_a": candidate_question["option_a"],
        "option_b": candidate_question["option_b"],
        "option_c": candidate_question["option_c"],
        "correct_answer": candidate_question["correct_answer"],
        "difficulty": candidate_question["difficulty"] or 1,
        "points": candidate_question["points"] or 1,
        "question_type": candidate_question["question_type"],
        "is_active": True,
        "source": candidate_question["source"],
        "published_at": candidate_question["published_at"] or datetime.now(timezone.utc).isoformat(),
        "metadata": metadata,
        "media": candidate_question["media"],
    }

    override_row = {
        "license_category_code": category.code,
        "source": candidate_question["source"],
        "external_id": candidate_question["external_id"],
        "question_topic_key": record["topic_key"],
        "reason": "pj360_missing_local_recovered_from_other_category",
        "metadata": {
            "topic_label": record["topic_label"],
            "pj360_site_question_id": str(record["internal_question_id"]),
            "recovered_from_category": candidate_question["license_category_code"],
            "import_origin": "recoverable_from_other_category",
        },
    }

    return question_payload, override_row


def main() -> int:
    args = parse_args()
    category_code = str(args.category).upper().strip()
    category = category_context(category_code)

    truly_missing_path = ANALYSIS_DIR / f"{category_code.lower()}-missing-local-truly_missing_local.json"
    recoverable_path = ANALYSIS_DIR / f"{category_code.lower()}-missing-local-recoverable_from_other_category.json"

    truly_missing = load_optional_records(truly_missing_path)
    recoverable = load_optional_records(recoverable_path)
    external_questions = load_json(EXTERNAL_QUESTIONS_PATH)
    external_index = {
        str(entry["site_question_id"]): entry
        for entry in external_questions
        if str(entry.get("site_question_id") or "").strip()
    }

    questions_payload: list[dict[str, Any]] = []
    overrides_payload: list[dict[str, Any]] = []
    stats = {
        "truly_missing_local": 0,
        "recoverable_from_other_category": 0,
        "downloaded_media_assets": 0,
    }

    for record in truly_missing["records"]:
        site_question_id = str(record["internal_question_id"])
        external_question = external_index.get(site_question_id)

        if external_question is None:
            raise RuntimeError(f"Brak pytania {site_question_id} w external_questions.json")

        question_payload, override_payload = build_external_question_payload(
            record=record,
            external_question=external_question,
            category=category,
            skip_download=bool(args.skip_download),
        )

        if question_payload["media"]:
            stats["downloaded_media_assets"] += len(question_payload["media"])

        questions_payload.append(question_payload)
        overrides_payload.append(override_payload)
        stats["truly_missing_local"] += 1

    for record in recoverable["records"]:
        candidate_questions = record.get("candidate_questions") or []

        if not candidate_questions:
            raise RuntimeError(f"Brak candidate_questions dla recoverable record {record['internal_question_id']}")

        candidate_question = load_db_question(int(candidate_questions[0]["question_id"]))
        question_payload, override_payload = build_clone_question_payload(
            record=record,
            candidate_question=candidate_question,
            category=category,
        )

        questions_payload.append(question_payload)
        overrides_payload.append(override_payload)
        stats["recoverable_from_other_category"] += 1

    payload = {
        "batch_id": f"pj360-{category_code.lower()}-missing-local-import",
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "categories": [
            {
                "code": category.code,
                "name": category.name,
                "slug": category.slug,
                "description": category.description,
                "is_active": category.is_active,
                "sort_order": category.sort_order,
                "questions": questions_payload,
            }
        ],
    }

    override_package = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "category": category.code,
        "reason": "pj360_missing_local_import",
        "overrides": overrides_payload,
    }

    report = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "category": category.code,
        "questions_total": len(questions_payload),
        "truly_missing_local": stats["truly_missing_local"],
        "recoverable_from_other_category": stats["recoverable_from_other_category"],
        "media_assets_referenced": stats["downloaded_media_assets"],
    }

    payload_path = IMPORT_ROOT / f"pj360-{category_code.lower()}-missing-local-import.json"
    override_path = OVERRIDE_ROOT / f"pj360-{category_code.lower()}-missing-local-import-overrides.json"
    report_path = ANALYSIS_DIR / f"{category_code.lower()}-missing-local-import-batch-report.json"

    dump_json(payload_path, payload)
    dump_json(override_path, override_package)
    dump_json(report_path, report)

    print(json.dumps({
        "payload_path": str(payload_path),
        "override_path": str(override_path),
        "report_path": str(report_path),
        **report,
    }, ensure_ascii=False, indent=2))

    return 0


if __name__ == "__main__":
    raise SystemExit(main())

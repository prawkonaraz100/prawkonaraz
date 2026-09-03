#!/usr/bin/env python3
"""Collect the authorised qualification C course into import-ready manifests.

Credentials are read only from TPJ_USERNAME and TPJ_PASSWORD.  The session id,
credentials and student fields are never written to disk or printed.
"""

from __future__ import annotations

import argparse
import concurrent.futures
import datetime as dt
import hashlib
import json
import mimetypes
import os
import re
import sys
import time
import unicodedata
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Any


API_BASE = "https://api.testynaprawojazdy.eu/eprawko-rest"
MEDIA_BASE = "https://bezpiecznykierowca.eu/internetmedia/640x360/"
COURSE_NAME = "Kwalifikacja wstępna przyspieszona — kat. C"
SOURCE_CATEGORY = "Y"
MODULE_ORDER = [100, 101, 102, 103, 126, 104, 105, 106, 107, 108, 109, 110, 111, 112]
EXPECTED_COUNTS = {
    100: 117,
    101: 170,
    102: 80,
    103: 102,
    126: 18,
    104: 164,
    105: 148,
    106: 87,
    107: 63,
    108: 83,
    109: 91,
    110: 74,
    111: 61,
    112: 65,
}
PLACEHOLDERS = {"KW_BRAK.JPG", "KW_BRAK.JPEG", "KW_BRAK.PNG", ""}
USER_AGENT = "PrawkoNaRaz authorised course migration/1.0"


class CollectorError(RuntimeError):
    pass


def utc_now() -> str:
    return dt.datetime.now(dt.timezone.utc).isoformat().replace("+00:00", "Z")


def json_bytes(value: Any) -> bytes:
    return (json.dumps(value, ensure_ascii=False, indent=2) + "\n").encode("utf-8")


def atomic_write(path: Path, value: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    temporary = path.with_suffix(path.suffix + ".tmp")
    temporary.write_bytes(json_bytes(value))
    temporary.replace(path)


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def request_bytes(
    url: str,
    *,
    method: str = "GET",
    headers: dict[str, str] | None = None,
    body: bytes | None = None,
    timeout: int = 45,
    attempts: int = 3,
) -> tuple[bytes, dict[str, str], int]:
    merged_headers = {"User-Agent": USER_AGENT, "Accept": "application/json, */*"}
    if headers:
        merged_headers.update(headers)

    last_error: Exception | None = None
    for attempt in range(1, attempts + 1):
        try:
            request = urllib.request.Request(url, data=body, headers=merged_headers, method=method)
            with urllib.request.urlopen(request, timeout=timeout) as response:
                response_headers = {key.lower(): value for key, value in response.headers.items()}
                return response.read(), response_headers, response.status
        except (urllib.error.URLError, TimeoutError, OSError) as error:
            last_error = error
            if attempt < attempts:
                time.sleep(attempt)

    raise CollectorError(f"Request failed after {attempts} attempts: {url}: {last_error}")


def request_json(
    url: str,
    *,
    method: str = "GET",
    headers: dict[str, str] | None = None,
    body: bytes | None = None,
) -> Any:
    payload, _, _ = request_bytes(url, method=method, headers=headers, body=body)
    try:
        return json.loads(payload.decode("utf-8"))
    except (UnicodeDecodeError, json.JSONDecodeError) as error:
        raise CollectorError(f"Invalid JSON response from {url}: {error}") from error


def login(username: str, password: str) -> tuple[str, str]:
    body = urllib.parse.urlencode({"userName": username, "password": password}).encode("utf-8")
    data = request_json(
        f"{API_BASE}/login",
        method="POST",
        headers={"Content-Type": "application/x-www-form-urlencoded"},
        body=body,
    )
    session_id = str(data.get("uuid") or "").strip()
    student_id = str(data.get("id") or "").strip()
    if not session_id or not student_id:
        raise CollectorError("Login succeeded without the required session or student identifier.")
    return session_id, student_id


def slugify(value: str) -> str:
    ascii_value = unicodedata.normalize("NFKD", value).encode("ascii", "ignore").decode("ascii")
    return re.sub(r"[^a-z0-9]+", "-", ascii_value.lower()).strip("-")


def module_slug(module_id: int, code: str, title: str) -> str:
    if module_id == 100:
        return "1-1-charakterystyka-ukladu-przeniesienia-napedu"
    return f"{slugify(code)}-{slugify(title)}"[:190].rstrip("-")


def selected_modules(argument: str | None) -> list[int]:
    if not argument:
        return MODULE_ORDER.copy()
    requested = {int(part.strip()) for part in argument.split(",") if part.strip()}
    unknown = requested.difference(MODULE_ORDER)
    if unknown:
        raise CollectorError(f"Unknown module ids: {sorted(unknown)}")
    return [module_id for module_id in MODULE_ORDER if module_id in requested]


def extract_module_payload(api_data: dict[str, Any], module_id: int, expected: int) -> dict[str, Any]:
    execution = api_data.get("execution")
    if not isinstance(execution, list):
        raise CollectorError(f"Module {module_id}: API response has no execution list.")
    if len(execution) != expected:
        raise CollectorError(f"Module {module_id}: expected {expected} questions, got {len(execution)}.")

    questions: list[dict[str, Any]] = []
    for index, entry in enumerate(execution, start=1):
        question = entry.get("question") if isinstance(entry, dict) else None
        if not isinstance(question, dict):
            raise CollectorError(f"Module {module_id}: question {index} is missing.")
        clean = dict(question)
        clean["ordinalNumber"] = int(entry.get("ordinalNumber") or index)
        questions.append(clean)

    first_module = questions[0].get("module") or {}
    code = str(first_module.get("moduleId") or module_id)
    title = str(first_module.get("name") or f"Moduł {code}").strip()
    external_ids = [str(question.get("externalId") or "").strip() for question in questions]
    if any(not value for value in external_ids):
        raise CollectorError(f"Module {module_id}: at least one external id is missing.")
    if len(external_ids) != len(set(external_ids)):
        raise CollectorError(f"Module {module_id}: duplicate external ids detected.")

    return {
        "schemaVersion": 1,
        "capturedAt": utc_now(),
        "source": {
            "system": "testynaprawojazdy.eu",
            "course": COURSE_NAME,
            "sourceCategory": SOURCE_CATEGORY,
        },
        "module": {
            "sourceId": module_id,
            "code": code,
            "title": title,
            "expectedQuestions": expected,
        },
        "questions": questions,
    }


def validate_cached_source(value: dict[str, Any], module_id: int, expected: int) -> None:
    module = value.get("module") or {}
    questions = value.get("questions") or []
    if int(module.get("sourceId") or 0) != module_id or len(questions) != expected:
        raise CollectorError(f"Cached source module {module_id} does not match the expected inventory.")


def media_candidates(filename: str, media_type: str) -> list[str]:
    safe_name = Path(filename).name
    if media_type == "VIDEO":
        stem = Path(safe_name).stem if Path(safe_name).suffix else safe_name
        candidates = []
        if Path(safe_name).suffix.lower() in {".mp4", ".webm", ".ogg", ".ogv"}:
            candidates.append(safe_name)
        candidates.extend([f"{stem}.mp4", f"{stem}.webm", f"{stem}.ogg"])
        return list(dict.fromkeys(candidates))
    return [safe_name]


def extension_for(content_type: str, remote_name: str, kind: str) -> str:
    mime = content_type.split(";", 1)[0].strip().lower()
    preferred = {
        "image/jpeg": ".jpg",
        "image/png": ".png",
        "image/webp": ".webp",
        "image/gif": ".gif",
        "video/mp4": ".mp4",
        "video/webm": ".webm",
        "video/ogg": ".ogg",
        "application/ogg": ".ogg",
    }.get(mime)
    if preferred:
        return preferred
    suffix = Path(remote_name).suffix.lower()
    if suffix:
        return suffix
    guessed = mimetypes.guess_extension(mime)
    return guessed or (".mp4" if kind == "VIDEO" else ".bin")


def download_one(filename: str, media_type: str, media_root: Path, force: bool) -> dict[str, Any]:
    candidates = media_candidates(filename, media_type)
    last_failure = "not_found"
    for remote_name in candidates:
        remote_url = MEDIA_BASE + urllib.parse.quote(remote_name)
        try:
            payload, headers, status = request_bytes(
                remote_url,
                headers={"Accept": "video/*, image/*, application/octet-stream"},
                attempts=2,
            )
        except CollectorError as error:
            last_failure = str(error)
            continue

        content_type = headers.get("content-type", "application/octet-stream").split(";", 1)[0].lower()
        expected_prefix = "video/" if media_type == "VIDEO" else "image/"
        if not content_type.startswith(expected_prefix):
            last_failure = f"unexpected MIME {content_type}"
            continue
        if not payload:
            last_failure = "empty payload"
            continue

        digest = hashlib.sha256(payload).hexdigest()
        extension = extension_for(content_type, remote_name, media_type)
        destination = media_root / f"{digest}{extension}"
        existed = destination.exists()
        if force or not existed:
            temporary = destination.with_suffix(destination.suffix + ".part")
            temporary.write_bytes(payload)
            temporary.replace(destination)

        return {
            "status": "downloaded" if force or not existed else "cached",
            "remoteName": remote_name,
            "remoteUrl": remote_url,
            "localPath": f"media/{destination.name}",
            "bytes": len(payload),
            "mimeType": content_type,
            "sha256": digest,
            "httpStatus": status,
            "failureReason": None,
        }

    return {
        "status": "failed",
        "remoteName": None,
        "remoteUrl": MEDIA_BASE + urllib.parse.quote(candidates[0]),
        "localPath": None,
        "bytes": None,
        "mimeType": None,
        "sha256": None,
        "httpStatus": None,
        "failureReason": last_failure[:500],
    }


def download_media(
    sources: dict[int, dict[str, Any]],
    media_root: Path,
    *,
    workers: int,
    force: bool,
) -> dict[tuple[str, str], dict[str, Any]]:
    media_root.mkdir(parents=True, exist_ok=True)
    tasks: dict[tuple[str, str], None] = {}
    for source in sources.values():
        for question in source["questions"]:
            filename = str(question.get("media") or "").strip()
            media_type = str(question.get("madiaType") or "IMAGE").upper()
            if filename.upper() in PLACEHOLDERS:
                continue
            tasks[(filename, media_type)] = None

    results: dict[tuple[str, str], dict[str, Any]] = {}
    with concurrent.futures.ThreadPoolExecutor(max_workers=max(1, workers)) as executor:
        futures = {
            executor.submit(download_one, filename, media_type, media_root, force): (filename, media_type)
            for filename, media_type in tasks
        }
        for completed, future in enumerate(concurrent.futures.as_completed(futures), start=1):
            key = futures[future]
            results[key] = future.result()
            if completed % 50 == 0 or completed == len(futures):
                print(f"media {completed}/{len(futures)}", flush=True)

    return results


def normalized_question(
    source_question: dict[str, Any],
    source: dict[str, Any],
    media_result: dict[str, Any] | None,
) -> dict[str, Any]:
    module = source["module"]
    correct = str(source_question.get("correct") or "").strip().lower()
    if correct not in {"a", "b", "c"}:
        raise CollectorError(
            f"Module {module['sourceId']}, question {source_question.get('externalId')}: invalid correct answer."
        )

    required = ["questionText", "answerA", "answerB", "answerC"]
    if any(not str(source_question.get(field) or "").strip() for field in required):
        raise CollectorError(
            f"Module {module['sourceId']}, question {source_question.get('externalId')}: required text is missing."
        )

    points = int(source_question.get("weight") or 1)
    if points < 1 or points > 3:
        raise CollectorError(
            f"Module {module['sourceId']}, question {source_question.get('externalId')}: invalid points {points}."
        )

    filename = str(source_question.get("media") or "").strip()
    media_type = str(source_question.get("madiaType") or "IMAGE").upper()
    row: dict[str, Any] = {
        "category_id": "C",
        "external_id": f"tpj:Y:{source_question['externalId']}",
        "question_text": str(source_question["questionText"]).strip(),
        "answer_a": str(source_question["answerA"]).strip(),
        "answer_b": str(source_question["answerB"]).strip(),
        "answer_c": str(source_question["answerC"]).strip(),
        "correct_answer": correct,
        "explanation": str(source_question.get("explenation") or "").strip() or None,
        "points": points,
        "difficulty": 1,
        "question_type": "single_choice",
        "is_active": False,
        "source": "testynaprawojazdy.eu",
        "published_at": source_question.get("lastUpdateDate"),
        "image_path": None,
        "metadata": {
            "source_site": "testynaprawojazdy.eu",
            "source_course": "qualification_c_accelerated",
            "source_category": "Y",
            "source_module_id": module["sourceId"],
            "source_module_code": module["code"],
            "source_module_title": module["title"],
            "source_question_id": source_question.get("id"),
            "source_external_id": source_question.get("externalId"),
            "source_ordinal_number": source_question.get("ordinalNumber"),
            "source_question_type": source_question.get("type"),
            "source_question_version": source_question.get("version"),
            "source_media_original": filename or None,
            "source_media_type": media_type,
            "source_media_exists": source_question.get("mediaExists"),
            "legal_source": source_question.get("legalSource"),
            "structure_scope": "SPECJALISTYCZNY",
            "import_preview": True,
        },
    }

    if media_result and media_result.get("localPath"):
        if media_type == "VIDEO":
            row["video_path"] = media_result["localPath"]
            row["poster_path"] = None
        else:
            row["image_path"] = media_result["localPath"]
    return row


def build_artifacts(
    output: Path,
    sources: dict[int, dict[str, Any]],
    media_results: dict[tuple[str, str], dict[str, Any]],
) -> dict[str, Any]:
    total_questions = 0
    total_placeholders = 0
    total_media_references = 0
    failed_media: list[dict[str, Any]] = []
    manifests: list[str] = []
    all_external_ids: list[str] = []

    for sort_index, module_id in enumerate(MODULE_ORDER, start=1):
        if module_id not in sources:
            continue
        source = sources[module_id]
        module = source["module"]
        module_dir = output / "modules" / f"module-{module_id}"
        questions: list[dict[str, Any]] = []
        media_inventory: list[dict[str, Any]] = []

        for question in source["questions"]:
            filename = str(question.get("media") or "").strip()
            media_type = str(question.get("madiaType") or "IMAGE").upper()
            placeholder = filename.upper() in PLACEHOLDERS
            media_result = None if placeholder else media_results.get((filename, media_type))
            if placeholder:
                total_placeholders += 1
            else:
                total_media_references += 1
                if not media_result or media_result.get("status") == "failed":
                    failed_media.append(
                        {
                            "moduleId": module_id,
                            "externalId": question.get("externalId"),
                            "filename": filename,
                            "type": media_type,
                            "failureReason": (media_result or {}).get("failureReason") or "not_downloaded",
                        }
                    )

            questions.append(normalized_question(question, source, media_result))
            media_inventory.append(
                {
                    "externalId": question.get("externalId"),
                    "ordinalNumber": question.get("ordinalNumber"),
                    "type": media_type,
                    "filename": filename or None,
                    "isPlaceholder": placeholder,
                    "remoteUrl": None if placeholder else (media_result or {}).get("remoteUrl"),
                    "downloadStatus": "placeholder" if placeholder else (media_result or {}).get("status", "not_downloaded"),
                    "localPath": None if placeholder else (media_result or {}).get("localPath"),
                    "bytes": None if placeholder else (media_result or {}).get("bytes"),
                    "mimeType": None if placeholder else (media_result or {}).get("mimeType"),
                    "sha256": None if placeholder else (media_result or {}).get("sha256"),
                    "httpStatus": None if placeholder else (media_result or {}).get("httpStatus"),
                    "failureReason": None if placeholder else (media_result or {}).get("failureReason"),
                }
            )

        atomic_write(module_dir / "questions.json", questions)
        atomic_write(module_dir / "media-inventory.json", media_inventory)
        total_questions += len(questions)
        all_external_ids.extend(question["external_id"] for question in questions)

        manifest_name = f"manifest-module-{module_id}.json"
        manifest = {
            "batch_id": f"qualification-c-y-module-{module_id}-full-20260730",
            "category_id": "C",
            "category_name": "Kategoria C",
            "category_description": "Pytania kategorii C. Kolekcja kwalifikacji wstępnej pozostaje niepubliczna do końca audytu.",
            "questions_file": f"modules/module-{module_id}/questions.json",
            "media_root": ".",
            "source": "testynaprawojazdy.eu",
            "collection": {
                "code": "qualification-c-accelerated",
                "slug": "kwalifikacja-wstepna-przyspieszona-c",
                "name": COURSE_NAME,
                "description": "Program pytań dla kwalifikacji wstępnej przyspieszonej kierowców kategorii C.",
                "kind": "professional_qualification",
                "source": "testynaprawojazdy.eu",
                "is_active": True,
                "is_public": False,
                "sort_order": 100,
                "metadata": {
                    "source_course": "qualification_c_accelerated",
                    "source_category": "Y",
                    "import_status": "production_working_version",
                    "lifecycle_status": "working_version",
                    "planned_refactor": True,
                    "planned_refactor_scope": "code_and_learning_module",
                },
            },
            "module": {
                "source_id": str(module_id),
                "code": module["code"],
                "slug": module_slug(module_id, module["code"], module["title"]),
                "name": module["title"],
                "expected_questions": module["expectedQuestions"],
                "sort_order": sort_index * 10,
                "is_active": True,
                "sync_mode": "replace",
                "metadata": {
                    "source_status": "ACTIVE",
                    "import_status": "production_working_version",
                    "lifecycle_status": "working_version",
                    "planned_refactor": True,
                },
            },
        }
        atomic_write(output / manifest_name, manifest)
        manifests.append(manifest_name)

    report = {
        "schemaVersion": 1,
        "generatedAt": utc_now(),
        "course": COURSE_NAME,
        "sourceCategory": SOURCE_CATEGORY,
        "modules": len(sources),
        "questionsTotal": total_questions,
        "uniqueExternalIds": len(set(all_external_ids)),
        "duplicateExternalMemberships": total_questions - len(set(all_external_ids)),
        "mediaReferences": total_media_references,
        "placeholders": total_placeholders,
        "uniqueMediaFiles": len({r.get("sha256") for r in media_results.values() if r.get("sha256")}),
        "failedMediaCount": len(failed_media),
        "failedMedia": failed_media,
        "manifests": manifests,
    }
    atomic_write(output / "collection-report.json", report)
    return report


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", default="storage/app/qualification-c-full")
    parser.add_argument("--modules", help="Comma-separated source module ids")
    parser.add_argument("--skip-media", action="store_true")
    parser.add_argument("--force-data", action="store_true")
    parser.add_argument("--force-media", action="store_true")
    parser.add_argument("--workers", type=int, default=8)
    args = parser.parse_args()

    username = os.environ.get("TPJ_USERNAME", "").strip()
    password = os.environ.get("TPJ_PASSWORD", "")
    if not username or not password:
        raise CollectorError("Set TPJ_USERNAME and TPJ_PASSWORD for this process.")

    output = Path(args.output).resolve()
    output.mkdir(parents=True, exist_ok=True)
    modules = selected_modules(args.modules)
    session_id, student_id = login(username, password)
    auth_headers = {"jsessionid": session_id, "Content-Type": "application/json"}

    inventory = request_json(f"{API_BASE}/questions/getModules/Y", headers=auth_headers)
    actual_counts = {int(row["id"]): int(row["count"]) for row in inventory}
    if actual_counts != EXPECTED_COUNTS:
        raise CollectorError(f"Source module inventory changed: {actual_counts}")
    if sum(actual_counts.values()) != 1323:
        raise CollectorError(f"Source question total changed: {sum(actual_counts.values())}")
    atomic_write(
        output / "source-inventory.json",
        {
            "capturedAt": utc_now(),
            "sourceCategory": "Y",
            "modules": [
                {"sourceId": module_id, "count": actual_counts[module_id]}
                for module_id in MODULE_ORDER
            ],
            "questionsTotal": sum(actual_counts.values()),
        },
    )

    sources: dict[int, dict[str, Any]] = {}
    for index, module_id in enumerate(modules, start=1):
        module_dir = output / "modules" / f"module-{module_id}"
        source_path = module_dir / "source-module.json"
        if source_path.exists() and not args.force_data:
            source = load_json(source_path)
            validate_cached_source(source, module_id, EXPECTED_COUNTS[module_id])
            state = "cached"
        else:
            encoded_student = urllib.parse.quote(student_id, safe="")
            data = request_json(
                f"{API_BASE}/learning/create/studentId/{encoded_student}/category/Y/lang/PL/moduleId/{module_id}",
                method="PUT",
                headers=auth_headers,
                body=b"",
            )
            source = extract_module_payload(data, module_id, EXPECTED_COUNTS[module_id])
            atomic_write(source_path, source)
            state = "downloaded"
        sources[module_id] = source
        checksum = hashlib.sha256(json_bytes(source)).hexdigest()
        print(f"module {module_id}: {len(source['questions'])} questions, {state}, sha256={checksum[:12]}", flush=True)

    if args.skip_media:
        media_results: dict[tuple[str, str], dict[str, Any]] = {}
    else:
        media_results = download_media(
            sources,
            output / "media",
            workers=args.workers,
            force=args.force_media,
        )

    report = build_artifacts(output, sources, media_results)
    print(json.dumps({key: value for key, value in report.items() if key != "failedMedia"}, ensure_ascii=False, indent=2))
    return 0 if report["failedMediaCount"] == 0 or args.skip_media else 1


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except CollectorError as error:
        print(f"ERROR: {error}", file=sys.stderr)
        raise SystemExit(1)

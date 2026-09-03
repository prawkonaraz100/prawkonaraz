from __future__ import annotations

import argparse
import io
import json
import os
import subprocess
import tempfile
from pathlib import Path
from typing import Any
from urllib.parse import urljoin

import psycopg
import requests
from bs4 import BeautifulSoup
from PIL import Image


BASE_URL = "https://www.prawo-jazdy-360.pl"
LOGIN_URL = f"{BASE_URL}/logowanie"
DEFAULT_BASE_DIR = Path("output/analysis/pj360-compare")
DEFAULT_OUTPUT_DIR = DEFAULT_BASE_DIR / "media-disambiguation"
DEFAULT_APP_MEDIA_ROOT = Path(r"D:\datasets\mi-prawo-jazdy-2026\app-media")
DEFAULT_DB_DSN = os.environ.get(
    "PJ360_COMPARE_DB_DSN",
    "host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit",
)


def normalize_text(value: str) -> str:
    return " ".join((value or "").split()).strip()


def normalize_answer_text(value: str | None) -> str | None:
    if not value:
        return None

    normalized = normalize_text(value).lower().rstrip(".")
    return normalized or None


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def write_json(path: Path, data: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def build_session(email: str, password: str) -> requests.Session:
    session = requests.Session()
    login_page = session.get(LOGIN_URL, timeout=30)
    login_page.raise_for_status()
    soup = BeautifulSoup(login_page.text, "html.parser")
    token = soup.find("input", {"name": "__RequestVerificationToken"})
    if token is None or not token.get("value"):
        raise RuntimeError("Nie udało się pobrać tokena logowania PJ360.")

    response = session.post(
        LOGIN_URL,
        data={
            "Email": email,
            "Password": password,
            "RememberMe": "true",
            "__RequestVerificationToken": token["value"],
        },
        headers={"Referer": LOGIN_URL},
        timeout=30,
    )
    response.raise_for_status()

    if ".AspNetCore._360Auth" not in session.cookies:
        raise RuntimeError("Logowanie do PJ360 nie powiodło się.")

    return session


def parse_external_question_media(session: requests.Session, url: str) -> dict[str, str | None]:
    response = session.get(url, timeout=30)
    response.raise_for_status()
    soup = BeautifulSoup(response.text, "html.parser")

    video = soup.select_one(".question video")
    if video is not None:
        source = video.select_one("source[type='video/mp4']") or video.select_one("source")
        video_url = source.get("src") if source else None
        poster_url = video.get("poster")
        return {
            "media_kind": "video",
            "media_url": urljoin(BASE_URL, video_url) if video_url else None,
            "poster_url": urljoin(BASE_URL, poster_url) if poster_url else None,
        }

    image = soup.select_one(".question img.media-img, .question picture img, .question img")
    if image is not None:
        image_url = image.get("src") or image.get("data-src")
        return {
            "media_kind": "image",
            "media_url": urljoin(BASE_URL, image_url) if image_url else None,
            "poster_url": None,
        }

    return {
        "media_kind": "none",
        "media_url": None,
        "poster_url": None,
    }


def average_hash(image: Image.Image, hash_size: int = 8) -> str:
    image = image.convert("L").resize((hash_size, hash_size))
    pixels = list(image.getdata())
    avg = sum(pixels) / len(pixels)
    bits = "".join("1" if pixel >= avg else "0" for pixel in pixels)
    return f"{int(bits, 2):0{hash_size * hash_size // 4}x}"


def hash_distance(left: str, right: str) -> int:
    left_bits = bin(int(left, 16))[2:].zfill(len(left) * 4)
    right_bits = bin(int(right, 16))[2:].zfill(len(right) * 4)
    return sum(1 for a, b in zip(left_bits, right_bits) if a != b)


def image_hash_from_bytes(data: bytes) -> str:
    with Image.open(io.BytesIO(data)) as image:
        return average_hash(image)


def image_hash_from_path(path: Path) -> str:
    with Image.open(path) as image:
        return average_hash(image)


def image_hash_from_video_url(url: str) -> str | None:
    with tempfile.TemporaryDirectory(prefix="pj360-video-hash-") as temp_dir:
        frame_path = Path(temp_dir) / "frame.jpg"
        command = [
            "ffmpeg",
            "-y",
            "-i",
            url,
            "-frames:v",
            "1",
            str(frame_path),
        ]
        process = subprocess.run(
            command,
            capture_output=True,
            text=True,
            timeout=120,
            check=False,
        )
        if process.returncode != 0 or not frame_path.exists():
            return None

        return image_hash_from_path(frame_path)


def fetch_local_candidate_media(
    db_dsn: str,
    gov_ids: list[str],
    app_media_root: Path,
) -> dict[str, dict[str, Any]]:
    query = """
        WITH question_base AS (
            SELECT
                q.id AS question_id,
                COALESCE(q.metadata->>'government_question_id', q.external_id) AS gov_id,
                lc.code AS category_code
            FROM questions q
            JOIN license_categories lc ON lc.id = q.license_category_id
            WHERE COALESCE(q.metadata->>'government_question_id', q.external_id) = ANY(%s)
        )
        SELECT
            qb.gov_id,
            ARRAY_AGG(DISTINCT qb.question_id ORDER BY qb.question_id) AS question_ids,
            ARRAY_AGG(DISTINCT qb.category_code ORDER BY qb.category_code) AS categories,
            MIN(q.correct_answer) AS correct_answer,
            MIN(q.question_type) AS question_type,
            MIN(q.option_a) AS option_a,
            MIN(q.option_b) AS option_b,
            MIN(q.option_c) AS option_c,
            ARRAY_REMOVE(
                ARRAY_AGG(
                    DISTINCT CASE
                        WHEN qm.path IS NULL THEN NULL
                        ELSE CONCAT(qm.kind, ':', qm.path)
                    END
                ),
                NULL
            ) AS media_entries
        FROM question_base qb
        JOIN questions q ON q.id = qb.question_id
        LEFT JOIN question_media qm ON qm.question_id = qb.question_id
        GROUP BY qb.gov_id
        ORDER BY qb.gov_id::int NULLS LAST, qb.gov_id;
    """

    with psycopg.connect(db_dsn) as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(query, (gov_ids,))
            rows = cur.fetchall()

    result: dict[str, dict[str, Any]] = {}

    for row in rows:
        gid = str(row["gov_id"])
        media_entries = row["media_entries"] or []
        representative_path = None
        representative_kind = None

        for entry in media_entries:
            if entry.startswith("image:") and "/full." in entry:
                representative_kind = "image"
                representative_path = app_media_root / entry.split(":", 1)[1]
                break

        if representative_path is None:
            for entry in media_entries:
                if entry.startswith("video:") and "/full." in entry:
                    video_path = app_media_root / entry.split(":", 1)[1]
                    poster_candidates = list(video_path.parent.parent.glob("image/poster.*.jpg"))
                    representative_kind = "video"
                    representative_path = poster_candidates[0] if poster_candidates else None
                    break

        result[gid] = {
            "question_ids": row["question_ids"] or [],
            "categories": row["categories"] or [],
            "correct_answer": row["correct_answer"],
            "question_type": row["question_type"],
            "option_a": row["option_a"],
            "option_b": row["option_b"],
            "option_c": row["option_c"],
            "media_entries": media_entries,
            "representative_kind": representative_kind,
            "representative_path": str(representative_path) if representative_path else None,
            "representative_exists": representative_path.exists() if representative_path else False,
        }

    return result


def build_local_answer_signature(local_media: dict[str, Any]) -> dict[str, Any]:
    question_type = local_media.get("question_type")
    correct_answer = (local_media.get("correct_answer") or "").lower()

    if question_type == "boolean":
        accepted = "tak" if correct_answer == "a" else "nie" if correct_answer == "b" else None
        suggested = ["nie"] if accepted == "tak" else ["tak"] if accepted == "nie" else []
        return {
            "accepted": accepted,
            "suggested": suggested,
        }

    option_map = {
        "a": normalize_answer_text(local_media.get("option_a")),
        "b": normalize_answer_text(local_media.get("option_b")),
        "c": normalize_answer_text(local_media.get("option_c")),
    }
    accepted = option_map.get(correct_answer)
    suggested = sorted(
        value
        for key, value in option_map.items()
        if value and key != correct_answer
    )
    return {
        "accepted": accepted,
        "suggested": suggested,
    }


def build_external_answer_signature(external: dict[str, Any]) -> dict[str, Any]:
    accepted = normalize_answer_text(external.get("accepted_answer"))
    suggested = sorted(
        normalized
        for value in external.get("suggested_answers", [])
        if (normalized := normalize_answer_text(value))
    )
    return {
        "accepted": accepted,
        "suggested": suggested,
    }


def resolve_item(
    item: dict[str, Any],
    session: requests.Session,
    local_media_lookup: dict[str, dict[str, Any]],
    external_lookup: dict[str, dict[str, Any]],
) -> dict[str, Any]:
    external_key = str(item["external"]["site_question_id"])
    external_record = external_lookup.get(external_key, item["external"])
    external_media = parse_external_question_media(session, item["external"]["url"])
    same_prompt_gov_ids = item["same_prompt_gov_ids"]
    external_answer_signature = build_external_answer_signature(external_record)

    source_hash = None
    source_hash_basis = None

    if external_media["poster_url"]:
        response = session.get(external_media["poster_url"], timeout=30)
        response.raise_for_status()
        source_hash = image_hash_from_bytes(response.content)
        source_hash_basis = "external_poster"
    elif external_media["media_url"] and external_media["media_kind"] == "image":
        response = session.get(external_media["media_url"], timeout=30)
        response.raise_for_status()
        source_hash = image_hash_from_bytes(response.content)
        source_hash_basis = "external_image"
    elif external_media["media_url"] and external_media["media_kind"] == "video":
        source_hash = image_hash_from_video_url(external_media["media_url"])
        source_hash_basis = "external_video_first_frame" if source_hash else None

    candidate_matches: list[dict[str, Any]] = []
    for gid in same_prompt_gov_ids:
        local_media = local_media_lookup.get(str(gid), {})
        representative_path = local_media.get("representative_path")
        representative_exists = local_media.get("representative_exists", False)
        local_hash = None
        distance = None

        if representative_path and representative_exists and source_hash:
            local_hash = image_hash_from_path(Path(representative_path))
            distance = hash_distance(source_hash, local_hash)

        candidate_matches.append(
            {
                "gov_id": str(gid),
                "categories": local_media.get("categories", []),
                "correct_answer": local_media.get("correct_answer"),
                "question_type": local_media.get("question_type"),
                "option_a": local_media.get("option_a"),
                "option_b": local_media.get("option_b"),
                "option_c": local_media.get("option_c"),
                "representative_kind": local_media.get("representative_kind"),
                "representative_path": representative_path,
                "representative_exists": representative_exists,
                "hash_distance": distance,
                "answer_signature": build_local_answer_signature(local_media),
            }
        )

    for candidate in candidate_matches:
        local_answer_signature = candidate["answer_signature"]
        candidate["accepted_answer_match"] = (
            external_answer_signature["accepted"] is not None
            and external_answer_signature["accepted"] == local_answer_signature["accepted"]
        )
        candidate["full_answer_signature_match"] = (
            candidate["accepted_answer_match"]
            and external_answer_signature["suggested"] == local_answer_signature["suggested"]
        )

    candidate_matches.sort(
        key=lambda item: (
            not item.get("full_answer_signature_match", False),
            not item.get("accepted_answer_match", False),
            item["hash_distance"] is None,
            item["hash_distance"] if item["hash_distance"] is not None else 9999,
            item["gov_id"],
        )
    )

    resolved_status = "still_manual"
    resolved_gov_id = None

    exact_answer_matches = [candidate for candidate in candidate_matches if candidate["full_answer_signature_match"]]
    accepted_answer_matches = [candidate for candidate in candidate_matches if candidate["accepted_answer_match"]]
    preferred_candidates = exact_answer_matches or accepted_answer_matches or candidate_matches

    comparable = [item for item in preferred_candidates if item["hash_distance"] is not None]
    if len(comparable) >= 1:
        best = comparable[0]
        second = comparable[1] if len(comparable) > 1 else None

        if best["hash_distance"] == 0 and (second is None or second["hash_distance"] >= 6):
            resolved_status = "resolved_by_exact_media_hash"
            resolved_gov_id = best["gov_id"]
        elif best["hash_distance"] <= 4 and (second is None or second["hash_distance"] - best["hash_distance"] >= 6):
            resolved_status = "likely_resolved_by_media_hash"
            resolved_gov_id = best["gov_id"]
        else:
            resolved_status = "still_manual"
    elif len(exact_answer_matches) == 1:
        resolved_status = "resolved_by_exact_answer_signature"
        resolved_gov_id = exact_answer_matches[0]["gov_id"]
    elif len(accepted_answer_matches) == 1:
        resolved_status = "resolved_by_accepted_answer_signature"
        resolved_gov_id = accepted_answer_matches[0]["gov_id"]
    elif source_hash is None:
        resolved_status = "external_media_missing"
    else:
        resolved_status = "local_media_missing"

    return {
        **item,
        "external_media": external_media,
        "external_record": external_record,
        "external_answer_signature": external_answer_signature,
        "source_hash_basis": source_hash_basis,
        "candidate_matches": candidate_matches,
        "resolved_status": resolved_status,
        "resolved_gov_id": resolved_gov_id,
    }


def build_summary(items: list[dict[str, Any]]) -> dict[str, Any]:
    counts: dict[str, int] = {}
    for item in items:
        status = item["resolved_status"]
        counts[status] = counts.get(status, 0) + 1

    return {
        "needs_media_disambiguation_total": len(items),
        "resolved_status_counts": dict(sorted(counts.items())),
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Rozstrzyga media disambiguation dla PJ360.")
    parser.add_argument(
        "--base-dir",
        type=Path,
        default=DEFAULT_BASE_DIR,
        help="Katalog bazowy analizy pj360-compare.",
    )
    parser.add_argument(
        "--output-dir",
        type=Path,
        default=DEFAULT_OUTPUT_DIR,
        help="Katalog wyjsciowy dla wynikow media disambiguation.",
    )
    parser.add_argument(
        "--db-dsn",
        default=DEFAULT_DB_DSN,
        help="DSN do PostgreSQL.",
    )
    parser.add_argument(
        "--app-media-root",
        type=Path,
        default=DEFAULT_APP_MEDIA_ROOT,
        help="Root lokalnego katalogu mediow aplikacji.",
    )
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    email = os.environ.get("PJ360_EMAIL")
    password = os.environ.get("PJ360_PASSWORD")
    if not email or not password:
        raise SystemExit("Ustaw zmienne PJ360_EMAIL i PJ360_PASSWORD.")

    high_risk = load_json(args.base_dir / "high-risk-review" / "high-risk-enriched.json")
    external_questions = load_json(args.base_dir / "external_questions.json")
    external_lookup = {
        str(item["site_question_id"]): item
        for item in external_questions
        if item.get("site_question_id") is not None
    }
    target_items = [item for item in high_risk if item["recommended_decision"] == "needs_media_disambiguation"]
    gov_ids = sorted({str(gid) for item in target_items for gid in item["same_prompt_gov_ids"]})

    local_media_lookup = fetch_local_candidate_media(args.db_dsn, gov_ids, args.app_media_root)
    session = build_session(email, password)
    resolved = [resolve_item(item, session, local_media_lookup, external_lookup) for item in target_items]
    summary = build_summary(resolved)

    args.output_dir.mkdir(parents=True, exist_ok=True)
    write_json(args.output_dir / "media-disambiguation-summary.json", summary)
    write_json(args.output_dir / "media-disambiguation-resolved.json", resolved)

    print(json.dumps(summary, ensure_ascii=False, indent=2))
    print(f"Zapisano media disambiguation do: {args.output_dir}")


if __name__ == "__main__":
    main()

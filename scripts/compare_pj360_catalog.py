from __future__ import annotations

import json
import os
import re
import unicodedata
from concurrent.futures import ThreadPoolExecutor, as_completed
from dataclasses import dataclass, asdict
from html import unescape
from pathlib import Path
from typing import Any
from urllib.parse import urljoin

import psycopg
import requests
from bs4 import BeautifulSoup


BASE_URL = "https://www.prawo-jazdy-360.pl"
LOGIN_URL = f"{BASE_URL}/logowanie"
LIST_URL = f"{BASE_URL}/testy-na-prawo-jazdy/pytania-egzaminacyjne"
COMPARABLE_CATEGORY_CODES = ["A", "AM", "A1", "A2", "B", "B1", "C", "C1", "D", "D1", "T"]
OUTPUT_DIR = Path("output/analysis/pj360-compare")


@dataclass
class ExternalQuestion:
    site_question_id: str | None
    url: str
    prompt: str
    accepted_answer: str
    suggested_answers: list[str]
    categories: list[str]
    structure_scope: str | None
    answer_count: int | None
    question_media_kind: str
    question_media_url: str | None
    explanation_text: str | None
    explanation_sections: list[dict[str, Any]]
    explanation_video_url: str | None


@dataclass
class LocalQuestion:
    gov_id: str
    prompt: str
    accepted_answer: str
    categories: list[str]
    structure_scope: str
    question_type: str
    question_media_kind: str
    option_a: str | None
    option_b: str | None
    option_c: str | None


def normalize_text(value: str) -> str:
    value = unescape(value or "")
    value = unicodedata.normalize("NFKC", value)
    value = value.replace("\xa0", " ")
    value = re.sub(r"\s+", " ", value)
    return value.strip()


def normalize_key(value: str) -> str:
    value = normalize_text(value).lower()
    value = value.replace("’", "'").replace("`", "'").replace("„", '"').replace("”", '"')
    value = value.replace("–", "-").replace("—", "-")
    return value


def loose_key(value: str) -> str:
    value = normalize_key(value)
    value = re.sub(r"[^\wąćęłńóśźż]+", "", value, flags=re.IGNORECASE)
    return value


def answer_text_from_local_row(row: dict[str, Any]) -> str:
    question_type = row["question_type"]
    correct_answer = (row["correct_answer"] or "").lower()

    if question_type == "boolean":
        return "tak" if correct_answer == "a" else "nie"

    if correct_answer == "a":
        return normalize_text(row["option_a"] or "")
    if correct_answer == "b":
        return normalize_text(row["option_b"] or "")
    if correct_answer == "c":
        return normalize_text(row["option_c"] or "")

    return ""


def build_session(email: str, password: str) -> requests.Session:
    session = requests.Session()
    login_page = session.get(LOGIN_URL, timeout=30)
    login_page.raise_for_status()
    soup = BeautifulSoup(login_page.text, "html.parser")
    token = soup.find("input", {"name": "__RequestVerificationToken"})
    if token is None or not token.get("value"):
        raise RuntimeError("Nie udało się pobrać tokena logowania z prawo-jazdy-360.pl.")

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
        allow_redirects=True,
    )
    response.raise_for_status()

    if ".AspNetCore._360Auth" not in session.cookies:
        raise RuntimeError("Logowanie do prawo-jazdy-360.pl nie powiodło się.")

    return session


def discover_last_page(session: requests.Session) -> int:
    response = session.get(LIST_URL, timeout=30)
    response.raise_for_status()
    page_numbers = {
        int(match.group(1))
        for match in re.finditer(r"[?&]strona=(\d+)", response.text)
    }
    return max(page_numbers) if page_numbers else 1


def fetch_list_page(session: requests.Session, page_no: int) -> list[str]:
    url = LIST_URL if page_no == 1 else f"{LIST_URL}?strona={page_no}"
    response = session.get(url, timeout=30)
    response.raise_for_status()
    soup = BeautifulSoup(response.text, "html.parser")
    urls = set()

    for anchor in soup.find_all("a", href=True):
        href = anchor["href"]
        if href.startswith("/testy-na-prawo-jazdy/pytania-egzaminacyjne/") and "?strona=" not in href:
            urls.add(urljoin(BASE_URL, href))

    return sorted(urls)


def categories_from_soup(soup: BeautifulSoup) -> list[str]:
    label = soup.find("span", class_="label", string=lambda text: text and "Kategorie:" in text)
    if not label or not label.parent:
        return []

    raw = normalize_text(label.parent.get_text(" ", strip=True))
    raw = raw.replace("Kategorie:", "", 1).strip()
    return [item.strip() for item in raw.split(",") if item.strip()]


def structure_scope_from_soup(soup: BeautifulSoup) -> str | None:
    label = soup.find("span", class_="label", string=lambda text: text and "Rodzaj pytania:" in text)
    if not label or not label.parent:
        return None

    raw = normalize_text(label.parent.get_text(" ", strip=True))
    raw = raw.replace("Rodzaj pytania:", "", 1).strip().lower()
    if raw.startswith("podstaw"):
        return "PODSTAWOWY"
    if raw.startswith("specjalist"):
        return "SPECJALISTYCZNY"

    return raw.upper() if raw else None


def parse_jsonld_question(soup: BeautifulSoup) -> tuple[dict[str, Any] | None, dict[str, Any] | None]:
    script = soup.find("script", {"id": "jsonld-script"})
    if not script or not script.string:
        return None, None

    data = json.loads(script.string)
    graph = data.get("@graph", [])
    question_data = None
    video_data = None

    for item in graph:
        if item.get("@type") == "QAPage":
            question_data = item.get("mainEntity")
        elif item.get("@type") == "VideoObject":
            video_data = item

    return question_data, video_data


def collect_section_text(container: BeautifulSoup) -> str:
    parts: list[str] = []

    for line_break in container.select("br"):
        line_break.replace_with("\n")

    for item in container.stripped_strings:
        normalized = normalize_text(item)
        if normalized:
            parts.append(normalized)

    text = " ".join(parts)
    text = re.sub(r"\s*\n\s*", "\n", text)
    text = re.sub(r"[ \t]+", " ", text)

    return text.strip()


def explanation_details_from_soup(soup: BeautifulSoup) -> tuple[str | None, list[dict[str, Any]]]:
    explanation = soup.select_one(".explanation")
    if explanation is None:
        return None, []

    expert_text = None
    expert_block = explanation.select_one(".text")
    if expert_block is not None:
        expert_text = collect_section_text(expert_block) or None

    sections: list[dict[str, Any]] = []
    for block in explanation.select(".fx-c.g-16"):
        title_node = block.select_one(".subtitle.l")
        if title_node is None:
            continue

        title = normalize_text(title_node.get_text(" ", strip=True))
        content_nodes = [node for node in block.find_all(class_="content", recursive=False)]
        caption_node = block.select_one(".caption")
        image_node = block.select_one("img")

        section_text_parts: list[str] = []
        if caption_node is not None:
            section_text_parts.append(collect_section_text(caption_node))
        for content_node in content_nodes:
            section_text_parts.append(collect_section_text(content_node))

        section_text = "\n\n".join(part for part in section_text_parts if part).strip()
        sections.append(
            {
                "title": title,
                "text": section_text or None,
                "image_url": urljoin(BASE_URL, image_node.get("src")) if image_node and image_node.get("src") else None,
            }
        )

    return expert_text, sections


def parse_external_question(session: requests.Session, url: str) -> ExternalQuestion:
    response = session.get(url, timeout=30)
    response.raise_for_status()
    soup = BeautifulSoup(response.text, "html.parser")
    question_data, video_data = parse_jsonld_question(soup)

    if question_data is None:
        raise RuntimeError(f"Brak JSON-LD z pytaniem dla {url}")

    question_container = soup.select_one(".question")
    media_video = question_container.select_one("video") if question_container else None
    media_image = question_container.select_one("img.media-img, img") if question_container else None
    media_kind = "none"
    media_url = None

    if media_video is not None:
        media_kind = "video"
        media_url = media_video.get("src") or media_video.get("data-src") or media_video.get("poster")
    elif media_image is not None:
        media_kind = "image"
        media_url = media_image.get("src") or media_image.get("data-src")

    site_question_id = None
    quid = soup.find("input", {"id": "quid"})
    if quid and quid.get("value"):
        site_question_id = normalize_text(quid["value"])
    else:
        match = re.search(r"id pytania:\s*(\d+)", response.text, re.IGNORECASE)
        if match:
            site_question_id = match.group(1)

    accepted = question_data.get("acceptedAnswer", {})
    suggested = question_data.get("suggestedAnswer", [])
    suggested_texts = []
    if isinstance(suggested, list):
        suggested_texts = [normalize_text(item.get("text", "")) for item in suggested if normalize_text(item.get("text", ""))]

    image_field = question_data.get("image")
    if media_url is None and isinstance(image_field, dict):
        media_url = image_field.get("url")
        if media_url:
            media_kind = "image"

    explanation_text, explanation_sections = explanation_details_from_soup(soup)

    return ExternalQuestion(
        site_question_id=site_question_id,
        url=url,
        prompt=normalize_text(question_data.get("text") or question_data.get("name") or ""),
        accepted_answer=normalize_text(accepted.get("text", "")),
        suggested_answers=suggested_texts,
        categories=categories_from_soup(soup),
        structure_scope=structure_scope_from_soup(soup),
        answer_count=question_data.get("answerCount"),
        question_media_kind=media_kind,
        question_media_url=media_url,
        explanation_text=explanation_text,
        explanation_sections=explanation_sections,
        explanation_video_url=video_data.get("contentUrl") if isinstance(video_data, dict) else None,
    )


def fetch_external_catalog(email: str, password: str) -> list[ExternalQuestion]:
    session = build_session(email, password)
    last_page = discover_last_page(session)

    question_urls: set[str] = set()
    with ThreadPoolExecutor(max_workers=12) as executor:
        futures = {executor.submit(fetch_list_page, session, page_no): page_no for page_no in range(1, last_page + 1)}
        for future in as_completed(futures):
            question_urls.update(future.result())

    external_questions: list[ExternalQuestion] = []
    with ThreadPoolExecutor(max_workers=10) as executor:
        futures = {executor.submit(parse_external_question, session, url): url for url in sorted(question_urls)}
        for future in as_completed(futures):
            external_questions.append(future.result())

    external_questions.sort(key=lambda item: item.url)
    return external_questions


def fetch_local_catalog() -> list[LocalQuestion]:
    query = """
        WITH base AS (
            SELECT
                COALESCE(q.metadata->>'government_question_id', q.external_id) AS gov_id,
                q.prompt,
                q.question_type,
                q.correct_answer,
                q.option_a,
                q.option_b,
                q.option_c,
                UPPER(COALESCE(q.metadata->>'structure_scope', 'PODSTAWOWY')) AS structure_scope,
                lc.code AS category_code,
                EXISTS (
                    SELECT 1
                    FROM question_media qm
                    WHERE qm.question_id = q.id
                    AND qm.kind = 'video'
                ) AS has_video,
                EXISTS (
                    SELECT 1
                    FROM question_media qm
                    WHERE qm.question_id = q.id
                    AND qm.kind = 'image'
                ) AS has_image
            FROM questions q
            JOIN license_categories lc ON lc.id = q.license_category_id
            WHERE lc.code = ANY(%s)
        )
        SELECT
            gov_id,
            MIN(prompt) AS prompt,
            MIN(question_type) AS question_type,
            MIN(correct_answer) AS correct_answer,
            MIN(option_a) AS option_a,
            MIN(option_b) AS option_b,
            MIN(option_c) AS option_c,
            MIN(structure_scope) AS structure_scope,
            ARRAY_AGG(DISTINCT category_code ORDER BY category_code) AS categories,
            BOOL_OR(has_video) AS has_video,
            BOOL_OR(has_image) AS has_image
        FROM base
        GROUP BY gov_id
        ORDER BY gov_id::int NULLS LAST, gov_id;
    """

    with psycopg.connect("host=127.0.0.1 port=5432 dbname=prawkobit user=prawkobit password=prawkobit") as conn:
        with conn.cursor(row_factory=psycopg.rows.dict_row) as cur:
            cur.execute(query, (COMPARABLE_CATEGORY_CODES,))
            rows = cur.fetchall()

    local_questions = []
    for row in rows:
        accepted_answer = answer_text_from_local_row(row)
        media_kind = "none"
        if row["has_video"]:
            media_kind = "video"
        elif row["has_image"]:
            media_kind = "image"

        local_questions.append(
            LocalQuestion(
                gov_id=str(row["gov_id"]),
                prompt=normalize_text(row["prompt"]),
                accepted_answer=accepted_answer,
                categories=list(row["categories"] or []),
                structure_scope=row["structure_scope"],
                question_type=row["question_type"],
                question_media_kind=media_kind,
                option_a=normalize_text(row["option_a"] or "") or None,
                option_b=normalize_text(row["option_b"] or "") or None,
                option_c=normalize_text(row["option_c"] or "") or None,
            )
        )

    return local_questions


def score_match(external: ExternalQuestion, local: LocalQuestion, exact_prompt: bool) -> int:
    score = 0
    if exact_prompt:
        score += 100
    if normalize_key(external.accepted_answer) == normalize_key(local.accepted_answer):
        score += 20
    if (external.structure_scope or "") == local.structure_scope:
        score += 10
    if external.question_media_kind == local.question_media_kind:
        score += 5
    score += len(set(external.categories) & set(local.categories)) * 2
    return score


def compare_catalogs(external_questions: list[ExternalQuestion], local_questions: list[LocalQuestion]) -> dict[str, Any]:
    local_by_exact_prompt: dict[str, list[LocalQuestion]] = {}
    local_by_loose_prompt: dict[str, list[LocalQuestion]] = {}

    for question in local_questions:
        local_by_exact_prompt.setdefault(normalize_key(question.prompt), []).append(question)
        local_by_loose_prompt.setdefault(loose_key(question.prompt), []).append(question)

    matched_pairs = []
    local_matched_ids: set[str] = set()
    external_only = []

    for external in external_questions:
        exact_candidates = local_by_exact_prompt.get(normalize_key(external.prompt), [])
        candidates = exact_candidates
        exact_prompt = True

        if not candidates:
            candidates = local_by_loose_prompt.get(loose_key(external.prompt), [])
            exact_prompt = False

        if not candidates:
            external_only.append(asdict(external))
            continue

        scored = sorted(
            (
                (
                    score_match(external, local, exact_prompt),
                    local.gov_id,
                    local,
                )
                for local in candidates
            ),
            reverse=True,
        )

        best_score, _, best_local = scored[0]
        ambiguous = len(scored) > 1 and scored[0][0] == scored[1][0]

        local_matched_ids.add(best_local.gov_id)
        matched_pairs.append(
            {
                "external": asdict(external),
                "local": asdict(best_local),
                "match": {
                    "exact_prompt": exact_prompt,
                    "score": best_score,
                    "ambiguous": ambiguous,
                    "answer_matches": normalize_key(external.accepted_answer) == normalize_key(best_local.accepted_answer),
                    "categories_match": sorted(external.categories) == sorted(best_local.categories),
                    "structure_scope_match": (external.structure_scope or "") == best_local.structure_scope,
                    "media_kind_match": external.question_media_kind == best_local.question_media_kind,
                },
            }
        )

    local_only = [asdict(question) for question in local_questions if question.gov_id not in local_matched_ids]

    answer_mismatches = [item for item in matched_pairs if not item["match"]["answer_matches"]]
    category_mismatches = [item for item in matched_pairs if not item["match"]["categories_match"]]
    scope_mismatches = [item for item in matched_pairs if not item["match"]["structure_scope_match"]]
    media_mismatches = [item for item in matched_pairs if not item["match"]["media_kind_match"]]
    ambiguous_matches = [item for item in matched_pairs if item["match"]["ambiguous"]]

    return {
        "summary": {
            "external_questions_count": len(external_questions),
            "local_questions_count": len(local_questions),
            "matched_count": len(matched_pairs),
            "exact_prompt_matches": sum(1 for item in matched_pairs if item["match"]["exact_prompt"]),
            "loose_prompt_matches": sum(1 for item in matched_pairs if not item["match"]["exact_prompt"]),
            "external_only_count": len(external_only),
            "local_only_count": len(local_only),
            "answer_mismatches_count": len(answer_mismatches),
            "category_mismatches_count": len(category_mismatches),
            "structure_scope_mismatches_count": len(scope_mismatches),
            "media_kind_mismatches_count": len(media_mismatches),
            "ambiguous_matches_count": len(ambiguous_matches),
        },
        "answer_mismatches": answer_mismatches,
        "category_mismatches": category_mismatches,
        "structure_scope_mismatches": scope_mismatches,
        "media_kind_mismatches": media_mismatches,
        "ambiguous_matches": ambiguous_matches,
        "external_only": external_only,
        "local_only": local_only,
    }


def write_outputs(external_questions: list[ExternalQuestion], local_questions: list[LocalQuestion], report: dict[str, Any]) -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    (OUTPUT_DIR / "external_questions.json").write_text(
        json.dumps([asdict(item) for item in external_questions], ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    (OUTPUT_DIR / "local_questions.json").write_text(
        json.dumps([asdict(item) for item in local_questions], ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    (OUTPUT_DIR / "comparison_report.json").write_text(
        json.dumps(report, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )


def main() -> None:
    email = os.environ.get("PJ360_EMAIL")
    password = os.environ.get("PJ360_PASSWORD")
    if not email or not password:
        raise SystemExit("Ustaw zmienne środowiskowe PJ360_EMAIL i PJ360_PASSWORD.")

    external_questions = fetch_external_catalog(email, password)
    local_questions = fetch_local_catalog()
    report = compare_catalogs(external_questions, local_questions)
    write_outputs(external_questions, local_questions, report)

    print(json.dumps(report["summary"], ensure_ascii=False, indent=2))
    print(f"Zapisano raport do: {OUTPUT_DIR}")


if __name__ == "__main__":
    main()

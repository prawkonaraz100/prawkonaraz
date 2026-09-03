from __future__ import annotations

import argparse
import json
import os
import re
import unicodedata
from dataclasses import dataclass, asdict
from datetime import datetime, UTC
from pathlib import Path
from typing import Any

import requests
from bs4 import BeautifulSoup, Tag


ROOT = Path(__file__).resolve().parents[1]
BASE_URL = "https://www.prawo-jazdy-360.pl"
LOGIN_URL = f"{BASE_URL}/logowanie"
DEFAULT_TOPIC_PATH = "/kurs/znaki-ostrzegawcze"
TOPIC_GROUPS_PATH = ROOT / "output" / "analysis" / "pj360-category-consistency" / "pj360-topic-groups.json"
TOPIC_DEFINITIONS_PATH = ROOT / "output" / "analysis" / "pj360-category-consistency" / "topic-definitions.json"
DEFAULT_OUTPUT_PATH = ROOT / "output" / "analysis" / "pj360-exact-topic-membership" / "exact-topic-membership.json"
COMPARABLE_CATEGORY_CODES = ["A", "AM", "A1", "A2", "B", "B1", "C", "C1", "D", "D1", "T"]
POLISH_ASCII_TRANSLATION = str.maketrans(
    {
        "ą": "a",
        "ć": "c",
        "ę": "e",
        "ł": "l",
        "ń": "n",
        "ó": "o",
        "ś": "s",
        "ź": "z",
        "ż": "z",
        "Ą": "A",
        "Ć": "C",
        "Ę": "E",
        "Ł": "L",
        "Ń": "N",
        "Ó": "O",
        "Ś": "S",
        "Ź": "Z",
        "Ż": "Z",
    }
)


@dataclass
class TopicQuestion:
    category_code: str
    topic_slug: str
    topic_label: str
    topic_key: str | None
    bucket: str | None
    page: int
    position_on_page: int
    internal_question_id: str | None
    prompt: str
    accepted_answer: str | None
    answer_count: int
    question_type: str
    question_media_kind: str
    question_media_url: str | None


def normalize_text(value: str) -> str:
    value = value or ""
    value = unicodedata.normalize("NFKC", value)
    value = value.replace("\xa0", " ")
    value = re.sub(r"\s+", " ", value)
    return value.strip()


def normalize_key(value: str) -> str:
    value = normalize_text(value).lower()
    value = value.translate(POLISH_ASCII_TRANSLATION)
    value = unicodedata.normalize("NFKD", value)
    value = "".join(ch for ch in value if not unicodedata.combining(ch))
    value = value.replace("’", "'").replace("`", "'").replace("„", '"').replace("”", '"')
    value = value.replace("–", "-").replace("—", "-")
    value = re.sub(r"[^a-z0-9]+", " ", value)
    return re.sub(r"\s+", " ", value).strip()


def slugify_topic_name(value: str) -> str:
    value = normalize_text(value)
    value = value.replace("/", "")
    value = value.translate(POLISH_ASCII_TRANSLATION).lower()
    value = unicodedata.normalize("NFKD", value)
    value = "".join(ch for ch in value if not unicodedata.combining(ch))
    value = value.replace("’", "").replace("`", "").replace("„", "").replace("”", "")
    value = value.replace("–", "-").replace("—", "-")
    value = re.sub(r"[^a-z0-9]+", "-", value)
    return re.sub(r"-{2,}", "-", value).strip("-")


def build_session(email: str, password: str) -> requests.Session:
    session = requests.Session()
    login_page = session.get(LOGIN_URL, timeout=30)
    login_page.raise_for_status()
    soup = BeautifulSoup(login_page.text, "html.parser")
    token = soup.find("input", {"name": "__RequestVerificationToken"})
    if token is None or not token.get("value"):
        raise RuntimeError("Nie udało się pobrać tokena logowania z PJ360.")

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
        raise RuntimeError("Logowanie do PJ360 nie powiodło się.")

    return session


def load_json_payload(path: Path) -> Any:
    raw = path.read_text(encoding="utf-8")
    starts = [index for index in [raw.find("["), raw.find("{")] if index >= 0]
    if not starts:
        raise RuntimeError(f"Plik nie zawiera poprawnego JSON: {path}")

    start_index = min(starts)
    return json.loads(raw[start_index:])


def load_topic_definitions() -> dict[str, dict[str, dict[str, Any]]]:
    definitions = load_json_payload(TOPIC_DEFINITIONS_PATH)
    by_name: dict[str, dict[str, Any]] = {}
    by_slug: dict[str, dict[str, Any]] = {}

    for item in definitions:
        for name_field in [item.get("name"), item.get("display_name")]:
            if not name_field:
                continue
            by_name[normalize_key(name_field)] = item
            by_slug[slugify_topic_name(name_field)] = item

    return {
        "by_name": by_name,
        "by_slug": by_slug,
    }


def load_expected_counts() -> dict[tuple[str, str], int]:
    groups = load_json_payload(TOPIC_GROUPS_PATH)
    lookup: dict[tuple[str, str], int] = {}
    for category_entry in groups:
        category_code = str(category_entry["requestedCategory"]).upper()
        for bucket in category_entry.get("buckets", []):
            for item in bucket.get("items", []):
                raw_count = str(item["count"]).split("/")[-1]
                lookup[(category_code, normalize_key(item["label"]))] = int(raw_count)
    return lookup


def fetch_topic_page(session: requests.Session, slug: str, page: int = 1) -> BeautifulSoup:
    url = f"{BASE_URL}/kurs/{slug}"
    if page > 1:
        url = f"{url}?strona={page}"

    response = session.get(url, timeout=30)
    response.raise_for_status()
    return BeautifulSoup(response.text, "html.parser")


def current_request_token(soup: BeautifulSoup) -> str:
    token = soup.find("input", {"id": "RequestVerificationToken"})
    if token is None or not token.get("value"):
        raise RuntimeError("Brak tokena RequestVerificationToken na stronie kursu PJ360.")
    return str(token["value"])


def change_category(session: requests.Session, category_code: str, request_token: str, referer_url: str) -> None:
    response = session.post(
        f"{BASE_URL}/user/change-category",
        headers={
            "X-Requested-With": "FetchRequest",
            "RequestVerificationToken": request_token,
            "Content-Type": "application/json; charset=UTF-8",
            "Referer": referer_url,
        },
        json=category_code,
        timeout=30,
    )
    response.raise_for_status()


def resolve_topic_definition(
    *,
    label: str,
    slug: str,
    topic_definitions: dict[str, dict[str, dict[str, Any]]],
) -> dict[str, Any] | None:
    by_name = topic_definitions["by_name"]
    by_slug = topic_definitions["by_slug"]

    label_key = normalize_key(label)
    if label_key in by_name:
        return by_name[label_key]

    if slug in by_slug:
        return by_slug[slug]

    for candidate_key, item in by_name.items():
        if candidate_key.startswith(label_key) or label_key.startswith(candidate_key):
            return item

    return None


def parse_topic_options(soup: BeautifulSoup, topic_definitions: dict[str, dict[str, dict[str, Any]]]) -> list[dict[str, Any]]:
    select = soup.select_one("#select-group")
    if select is None:
        raise RuntimeError("Nie znaleziono selecta tematów #select-group na stronie PJ360.")

    topics: list[dict[str, Any]] = []
    for option in select.select("option"):
        slug = normalize_text(option.get("data-url", ""))
        label = normalize_text(option.get_text(" ", strip=True))
        if not slug or not label:
            continue

        definition = resolve_topic_definition(label=label, slug=slug, topic_definitions=topic_definitions)
        topics.append(
            {
                "slug": slug,
                "label": label,
                "group_id": normalize_text(option.get("value", "")) or None,
                "topic_key": definition["key"] if definition else None,
                "bucket": definition["bucket"] if definition else None,
            }
        )

    return topics


def parse_max_page(soup: BeautifulSoup) -> int:
    pages = []
    for element in soup.select("#questions-pagination [data-page]"):
        raw = element.get("data-page")
        if raw and str(raw).isdigit():
            pages.append(int(raw))
    return max(pages) if pages else 1


def parse_question_media(question_block: Tag) -> tuple[str, str | None]:
    video = question_block.select_one("video")
    if video is not None:
        source = video.select_one("source")
        if source and source.get("src"):
            return "video", normalize_text(source["src"])
        if video.get("poster"):
            return "video", normalize_text(video["poster"])
        return "video", None

    image = question_block.select_one(".img img, img")
    if image is not None and image.get("src"):
        return "image", normalize_text(image["src"])

    return "none", None


def parse_accepted_answer(question_block: Tag) -> tuple[str | None, int, str]:
    labels = question_block.select("#question-answers label")
    normalized_labels = [normalize_text(label.get_text(" ", strip=True)) for label in labels if normalize_text(label.get_text(" ", strip=True))]
    correct_label = question_block.select_one("#question-answers label.correct-answer")
    accepted_answer = normalize_text(correct_label.get_text(" ", strip=True)) if correct_label else None

    question_type = "single_choice"
    if len(normalized_labels) == 2 and {normalize_key(item) for item in normalized_labels} == {"tak", "nie"}:
        question_type = "boolean"

    return accepted_answer, len(normalized_labels), question_type


def parse_topic_questions(
    soup: BeautifulSoup,
    *,
    category_code: str,
    topic_slug: str,
    topic_label: str,
    topic_key: str | None,
    bucket: str | None,
    page: int,
) -> list[TopicQuestion]:
    questions: list[TopicQuestion] = []

    # PJ360 wraps the question accordions in extra containers, so we cannot
    # rely on them being direct children of #partial-questions.
    for position, accordion in enumerate(soup.select("#partial-questions .accordion"), start=1):
        header = accordion.select_one(".accordion-header")
        title = header.select_one("h2") if header else None
        prompt = normalize_text(title.get_text(" ", strip=True)) if title else ""
        if not prompt:
            continue

        question_block = accordion.select_one(".question")
        if question_block is None:
            continue

        quid = accordion.select_one("input#quid")
        internal_question_id = normalize_text(quid.get("value", "")) if quid else normalize_text(header.get("data-id", "")) if header else None

        accepted_answer, answer_count, question_type = parse_accepted_answer(question_block)
        media_kind, media_url = parse_question_media(question_block)

        questions.append(
            TopicQuestion(
                category_code=category_code,
                topic_slug=topic_slug,
                topic_label=topic_label,
                topic_key=topic_key,
                bucket=bucket,
                page=page,
                position_on_page=position,
                internal_question_id=internal_question_id or None,
                prompt=prompt,
                accepted_answer=accepted_answer,
                answer_count=answer_count,
                question_type=question_type,
                question_media_kind=media_kind,
                question_media_url=media_url,
            )
        )

    return questions


def deduplicate_questions(questions: list[TopicQuestion]) -> list[TopicQuestion]:
    unique: dict[tuple[str, str, str, str], TopicQuestion] = {}
    for question in questions:
        key = (
            question.internal_question_id or "",
            normalize_key(question.prompt),
            normalize_key(question.accepted_answer or ""),
            question.question_media_kind,
        )
        unique.setdefault(key, question)
    return list(unique.values())


def extract_category(
    session: requests.Session,
    category_code: str,
    topic_definitions: dict[str, dict[str, Any]],
    expected_counts: dict[tuple[str, str], int],
    requested_topic_slugs: set[str] | None = None,
) -> dict[str, Any]:
    bootstrap_soup = fetch_topic_page(session, "znaki-ostrzegawcze")
    request_token = current_request_token(bootstrap_soup)
    change_category(session, category_code, request_token, f"{BASE_URL}{DEFAULT_TOPIC_PATH}")
    category_soup = fetch_topic_page(session, "znaki-ostrzegawcze")
    topics = parse_topic_options(category_soup, topic_definitions)

    if requested_topic_slugs:
        topics = [topic for topic in topics if topic["slug"] in requested_topic_slugs]

    topic_payloads: list[dict[str, Any]] = []
    for topic in topics:
        first_page = fetch_topic_page(session, topic["slug"], 1)
        max_page = parse_max_page(first_page)
        extracted_questions = parse_topic_questions(
            first_page,
            category_code=category_code,
            topic_slug=topic["slug"],
            topic_label=topic["label"],
            topic_key=topic["topic_key"],
            bucket=topic["bucket"],
            page=1,
        )

        for page in range(2, max_page + 1):
            page_soup = fetch_topic_page(session, topic["slug"], page)
            extracted_questions.extend(
                parse_topic_questions(
                    page_soup,
                    category_code=category_code,
                    topic_slug=topic["slug"],
                    topic_label=topic["label"],
                    topic_key=topic["topic_key"],
                    bucket=topic["bucket"],
                    page=page,
                )
            )

        deduped = deduplicate_questions(extracted_questions)
        expected_total = expected_counts.get((category_code, normalize_key(topic["label"])))
        topic_payloads.append(
            {
                "category_code": category_code,
                "topic_slug": topic["slug"],
                "topic_label": topic["label"],
                "topic_key": topic["topic_key"],
                "bucket": topic["bucket"],
                "expected_total": expected_total,
                "pages": max_page,
                "extracted_questions_count": len(deduped),
                "status": "ok" if expected_total in (None, len(deduped)) else "count_mismatch",
                "questions": [asdict(question) for question in deduped],
            }
        )

    return {
        "category_code": category_code,
        "topics_count": len(topic_payloads),
        "topics": topic_payloads,
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Extract exact PJ360 topic membership from paginated /kurs/<topic> pages.")
    parser.add_argument("--categories", default="B", help="Lista kategorii oddzielona przecinkami, np. B albo A,AM,B,C.")
    parser.add_argument("--topics", default="", help="Opcjonalna lista topic slugów oddzielona przecinkami, np. znaki-ostrzegawcze,wyprzedzanie.")
    parser.add_argument("--output", default=str(DEFAULT_OUTPUT_PATH), help="Sciezka do pliku wyjsciowego JSON.")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    email = os.environ.get("PJ360_EMAIL")
    password = os.environ.get("PJ360_PASSWORD")
    if not email or not password:
        raise SystemExit("Ustaw zmienne PJ360_EMAIL i PJ360_PASSWORD.")

    requested_categories = [item.strip().upper() for item in args.categories.split(",") if item.strip()]
    invalid_categories = [item for item in requested_categories if item not in COMPARABLE_CATEGORY_CODES]
    if invalid_categories:
        raise SystemExit(f"Nieobslugiwane kategorie: {', '.join(invalid_categories)}")

    requested_topics = {item.strip() for item in args.topics.split(",") if item.strip()} or None
    topic_definitions = load_topic_definitions()
    expected_counts = load_expected_counts()
    session = build_session(email, password)

    payload = {
        "generated_at": datetime.now(UTC).isoformat(),
        "categories": [],
    }

    for category_code in requested_categories:
        payload["categories"].append(
            extract_category(
                session,
                category_code,
                topic_definitions=topic_definitions,
                expected_counts=expected_counts,
                requested_topic_slugs=requested_topics,
            )
        )

    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    summary = {
        category["category_code"]: {
            "topics": category["topics_count"],
            "questions": sum(topic["extracted_questions_count"] for topic in category["topics"]),
        }
        for category in payload["categories"]
    }
    print(json.dumps(summary, ensure_ascii=False, indent=2))
    print(f"Zapisano exact-membership do: {output_path}")


if __name__ == "__main__":
    main()

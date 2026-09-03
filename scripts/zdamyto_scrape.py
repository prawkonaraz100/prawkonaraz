#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import re
import time
from dataclasses import dataclass
from pathlib import Path
from typing import Iterable
from urllib.parse import urljoin, urlparse

import requests
from bs4 import BeautifulSoup, Tag


BASE_URL = "https://www.zdamyto.com/"
HOME_URL = urljoin(BASE_URL, "/")
LOGIN_URL = urljoin(BASE_URL, "/u/sign-in-up")
LOGIN_POST_URL = urljoin(BASE_URL, "/u/sign-in-up?a=in")
DEFAULT_CATEGORIES = ["A", "B", "C", "D", "T"]
USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    "AppleWebKit/537.36 (KHTML, like Gecko) "
    "Chrome/136.0.0.0 Safari/537.36 Codex/ZdamytoScraper"
)


@dataclass(frozen=True)
class SectionRef:
    category_code: str
    category_slug: str
    section_slug: str
    list_url: str


class ZdamytoScraper:
    def __init__(
        self,
        email: str,
        password: str,
        out_dir: Path,
        category_codes: list[str] | None = None,
        pause_seconds: float = 0.0,
        timeout_seconds: int = 30,
    ) -> None:
        self.email = email
        self.password = password
        self.out_dir = out_dir
        self.category_codes = [code.upper() for code in (category_codes or DEFAULT_CATEGORIES)]
        self.pause_seconds = pause_seconds
        self.timeout_seconds = timeout_seconds

        self.session = requests.Session()
        self.session.headers.update({"User-Agent": USER_AGENT})

        self.raw_dir = self.out_dir / "raw"
        self.question_html_dir = self.raw_dir / "question-pages"
        self.list_html_dir = self.raw_dir / "list-pages"
        self.media_dir = self.out_dir / "media"
        self.data_dir = self.out_dir / "data"

        for path in [
            self.out_dir,
            self.raw_dir,
            self.question_html_dir,
            self.list_html_dir,
            self.media_dir,
            self.data_dir,
        ]:
            path.mkdir(parents=True, exist_ok=True)

    def run(self, discover_only: bool = False, question_limit: int | None = None) -> dict:
        self.login()
        categories = self.discover_categories()
        sections = self.discover_sections(categories)

        result: dict[str, object] = {
            "base_url": BASE_URL,
            "category_codes": self.category_codes,
            "categories": categories,
            "sections": [section.__dict__ for section in sections],
        }

        self.write_json(self.data_dir / "categories.json", categories)
        self.write_json(self.data_dir / "sections.json", [section.__dict__ for section in sections])

        if discover_only:
            summary = {
                **result,
                "questions": [],
                "summary": {
                    "categories_count": len(categories),
                    "sections_count": len(sections),
                    "questions_count": 0,
                },
            }
            self.write_json(self.out_dir / "summary.json", summary)
            return summary

        question_map = self.crawl_questions(sections, question_limit=question_limit)
        questions = list(question_map.values())

        catalog_path = self.data_dir / "questions.jsonl"
        with catalog_path.open("w", encoding="utf-8") as handle:
            for question in questions:
                handle.write(json.dumps(question, ensure_ascii=False) + "\n")

        summary = {
            **result,
            "summary": {
                "categories_count": len(categories),
                "sections_count": len(sections),
                "questions_count": len(questions),
                "downloaded_media_count": sum(len(question["media"]) for question in questions),
            },
        }
        self.write_json(self.out_dir / "summary.json", summary)
        return summary

    def login(self) -> None:
        response = self.get(LOGIN_URL)
        ctn_match = re.search(r'name="ctn" value="([^"]+)"', response.text)
        if not ctn_match:
            raise RuntimeError("Nie udalo sie znalezc tokena logowania ctn.")

        payload = {
            "ctn": ctn_match.group(1),
            "p[username]": self.email,
            "p[password]": self.password,
            "a[submit]": "Zaloguj się",
        }

        login_response = self.session.post(
            LOGIN_POST_URL,
            data=payload,
            timeout=self.timeout_seconds,
            allow_redirects=True,
        )
        login_response.raise_for_status()

        if "/u/profile" not in login_response.url:
            raise RuntimeError(f"Logowanie wyglada na nieudane. Final URL: {login_response.url}")

    def discover_categories(self) -> list[dict]:
        response = self.get(HOME_URL)
        soup = BeautifulSoup(response.text, "html.parser")
        category_map: dict[str, dict] = {}

        pattern = re.compile(r"/testy-na-prawo-jazdy/kategoria-([a-z0-9]+)/egzamin")
        for anchor in soup.find_all("a", href=True):
            href = anchor["href"]
            match = pattern.search(href)
            if not match:
                continue

            category_slug = match.group(1)
            category_code = category_slug.upper()
            if category_code not in self.category_codes:
                continue

            category_map[category_code] = {
                "code": category_code,
                "slug": category_slug,
                "exam_url": urljoin(BASE_URL, f"/testy-na-prawo-jazdy/kategoria-{category_slug}/egzamin"),
                "study_url": urljoin(BASE_URL, f"/testy-na-prawo-jazdy/kategoria-{category_slug}/nauka"),
                "review_url": urljoin(BASE_URL, f"/testy-na-prawo-jazdy/kategoria-{category_slug}/zaliczenie"),
            }

        missing = [code for code in self.category_codes if code not in category_map]
        for category_code in missing:
            category_slug = category_code.lower()
            category_map[category_code] = {
                "code": category_code,
                "slug": category_slug,
                "exam_url": urljoin(BASE_URL, f"/testy-na-prawo-jazdy/kategoria-{category_slug}/egzamin"),
                "study_url": urljoin(BASE_URL, f"/testy-na-prawo-jazdy/kategoria-{category_slug}/nauka"),
                "review_url": urljoin(BASE_URL, f"/testy-na-prawo-jazdy/kategoria-{category_slug}/zaliczenie"),
            }

        return [category_map[code] for code in self.category_codes]

    def discover_sections(self, categories: list[dict]) -> list[SectionRef]:
        section_map: dict[tuple[str, str], SectionRef] = {}
        pattern = re.compile(r"/testy-na-prawo-jazdy/kategoria-([a-z0-9]+)/([^/]+)/pytania(?:/\d+)?$")

        for category in categories:
            response = self.get(category["study_url"])
            soup = BeautifulSoup(response.text, "html.parser")

            raw_path = self.list_html_dir / f"{category['code']}-nauka.html"
            raw_path.write_text(response.text, encoding="utf-8")

            for anchor in soup.find_all("a", href=True):
                href = urljoin(BASE_URL, anchor["href"])
                match = pattern.search(urlparse(href).path)
                if not match:
                    continue

                category_slug = match.group(1)
                section_slug = match.group(2)
                category_code = category_slug.upper()

                if category_code != category["code"]:
                    continue

                key = (category_code, section_slug)
                section_map[key] = SectionRef(
                    category_code=category_code,
                    category_slug=category_slug,
                    section_slug=section_slug,
                    list_url=urljoin(
                        BASE_URL,
                        f"/testy-na-prawo-jazdy/kategoria-{category_slug}/{section_slug}/pytania",
                    ),
                )

        return list(section_map.values())

    def crawl_questions(self, sections: Iterable[SectionRef], question_limit: int | None = None) -> dict[str, dict]:
        question_map: dict[str, dict] = {}

        for section in sections:
            for list_page_url in self.iter_list_pages(section.list_url):
                response = self.get(list_page_url)
                list_page_name = self.slugify_path(urlparse(list_page_url).path) + ".html"
                (self.list_html_dir / list_page_name).write_text(response.text, encoding="utf-8")

                soup = BeautifulSoup(response.text, "html.parser")
                section_label = self.parse_section_label(soup) or section.section_slug.replace("-", " ")

                for question_id in self.extract_question_ids(response.text):
                    question = question_map.get(question_id)
                    if question is None:
                        question = self.fetch_question(question_id)
                        question_map[question_id] = question

                    occurrence = {
                        "category_code": section.category_code,
                        "category_slug": section.category_slug,
                        "section_slug": section.section_slug,
                        "section_label": section_label,
                        "list_page_url": list_page_url,
                    }
                    if occurrence not in question["occurrences"]:
                        question["occurrences"].append(occurrence)

                    if question_limit is not None and len(question_map) >= question_limit:
                        return question_map

        return question_map

    def iter_list_pages(self, list_url: str) -> list[str]:
        visited: set[str] = set()
        queue = [list_url]
        ordered: list[str] = []

        while queue:
            current = queue.pop(0)
            if current in visited:
                continue
            visited.add(current)
            ordered.append(current)

            response = self.get(current)
            soup = BeautifulSoup(response.text, "html.parser")
            for anchor in soup.find_all("a", href=True):
                href = urljoin(BASE_URL, anchor["href"])
                if not href.startswith(list_url):
                    continue
                if href not in visited and href not in queue:
                    queue.append(href)

        return ordered

    def fetch_question(self, question_id: str) -> dict:
        question_url = urljoin(BASE_URL, f"/testy-na-prawo-jazdy/pytanie/{question_id}")
        response = self.get(question_url)
        html_path = self.question_html_dir / f"{question_id}.html"
        html_path.write_text(response.text, encoding="utf-8")

        soup = BeautifulSoup(response.text, "html.parser")
        prompt = soup.find("h1").get_text(" ", strip=True) if soup.find("h1") else None
        answers = self.extract_answers(soup)
        explanation = self.extract_section_text(soup, "Uzasadnienie:")
        legal_basis = self.extract_section_text(soup, "Podstawa prawna:")
        category_tags = self.extract_list_after_heading(soup, "Pytanie należy do kategorii:")
        topical_tags = self.extract_list_after_heading(soup, "Działy tematyczne:")
        media = self.extract_media(question_id, soup)

        return {
            "site": "zdamyto.com",
            "question_id": question_id,
            "question_url": question_url,
            "prompt": prompt,
            "answers": answers,
            "correct_answer": next((answer["label"] for answer in answers if answer["is_correct"]), None),
            "explanation": explanation,
            "legal_basis": legal_basis,
            "category_tags": category_tags,
            "topical_tags": topical_tags,
            "media": media,
            "occurrences": [],
        }

    def extract_answers(self, soup: BeautifulSoup) -> list[dict]:
        answers: list[dict] = []
        seen_keys: set[str] = set()

        answer_nodes = soup.select(
            "button[class*='list-question-answer-'], li[class*='list-question-answer-']",
        )

        for node in answer_nodes:
            classes = node.get("class", [])
            answer_key = None
            for item in classes:
                if item.startswith("list-question-answer-"):
                    answer_key = item.removeprefix("list-question-answer-")
                    break

            if answer_key is None or answer_key in seen_keys:
                continue

            label = None
            key_text = node.select_one(".list-question-item-name")
            content_text = node.select_one(".list-question-item-content")

            if key_text and content_text:
                label = content_text.get_text(" ", strip=True)
            else:
                text = node.get_text(" ", strip=True)
                if text in {"Tak", "Nie", "A", "B", "C"}:
                    label = text
                elif answer_key in {"T", "N"} and text:
                    label = "Tak" if answer_key == "T" else "Nie"
                else:
                    label = text

            label = self.clean_answer_label(label)
            if not label:
                continue

            is_correct = "btn-green" in classes or bool(node.select_one(".icon-check, .btn-green"))

            answers.append(
                {
                    "key": answer_key,
                    "label": label,
                    "is_correct": is_correct,
                    "classes": classes,
                }
            )
            seen_keys.add(answer_key)

        return answers

    def clean_answer_label(self, value: str | None) -> str | None:
        if value is None:
            return None

        text = value.replace("\xa0", " ")
        text = re.sub(r"\s+", " ", text).strip()
        text = re.sub(r"[✗✔✓✘]+", "", text).strip()
        text = re.sub(r"^(A|B|C)\s+", "", text)
        text = re.sub(r"\s+$", "", text)

        return text or None

    def extract_section_text(self, soup: BeautifulSoup, heading_text: str) -> str | None:
        heading = self.find_heading(soup, heading_text)
        if heading is None:
            return None

        parts: list[str] = []
        normalized_target = self.normalize_space(heading_text)
        for sibling in heading.next_siblings:
            if isinstance(sibling, Tag):
                if sibling.name in {"h1", "h2", "h3", "h4"}:
                    if self.normalize_space(sibling.get_text(" ", strip=True)) == normalized_target:
                        continue
                    break
                sibling_text = sibling.get_text(" ", strip=True)
                if sibling_text:
                    parts.append(sibling_text)
            elif isinstance(sibling, str):
                text = sibling.strip()
                if text:
                    parts.append(text)

        return " ".join(parts).strip() or None

    def extract_list_after_heading(self, soup: BeautifulSoup, heading_text: str) -> list[str]:
        heading = self.find_heading(soup, heading_text)
        if heading is None:
            return []

        values: list[str] = []
        normalized_target = self.normalize_space(heading_text)
        for sibling in heading.next_siblings:
            if isinstance(sibling, Tag):
                if sibling.name in {"h1", "h2", "h3", "h4"}:
                    if self.normalize_space(sibling.get_text(" ", strip=True)) == normalized_target:
                        continue
                    break
                for anchor in sibling.find_all("a", href=True):
                    text = anchor.get_text(" ", strip=True)
                    if text and text not in values:
                        values.append(text)
            if values:
                break

        return values

    def extract_media(self, question_id: str, soup: BeautifulSoup) -> list[dict]:
        media_urls: list[tuple[str, str]] = []

        for source in soup.find_all("source", src=True):
            src = urljoin(BASE_URL, source["src"])
            media_urls.append(("video", src))

        for video in soup.find_all("video", poster=True):
            media_urls.append(("image", urljoin(BASE_URL, video["poster"])))

        for image in soup.find_all("img", src=True):
            src = urljoin(BASE_URL, image["src"])
            if "app.zdamyto.com" not in src:
                continue
            if "/files/" not in src:
                continue
            media_urls.append(("image", src))

        unique: list[dict] = []
        seen_urls: set[str] = set()
        for kind, remote_url in media_urls:
            if remote_url in seen_urls:
                continue
            seen_urls.add(remote_url)
            local_path = self.download_media(question_id, remote_url)
            unique.append(
                {
                    "kind": kind,
                    "remote_url": remote_url,
                    "local_path": str(local_path.relative_to(self.out_dir)),
                }
            )

        return unique

    def download_media(self, question_id: str, remote_url: str) -> Path:
        parsed = urlparse(remote_url)
        filename = Path(parsed.path).name or f"{question_id}.bin"
        question_media_dir = self.media_dir / question_id
        question_media_dir.mkdir(parents=True, exist_ok=True)
        output_path = question_media_dir / filename

        if output_path.exists():
            return output_path

        with self.session.get(remote_url, timeout=self.timeout_seconds, stream=True) as response:
            response.raise_for_status()
            with output_path.open("wb") as handle:
                for chunk in response.iter_content(chunk_size=1024 * 256):
                    if chunk:
                        handle.write(chunk)

        return output_path

    def parse_section_label(self, soup: BeautifulSoup) -> str | None:
        if soup.title and "dział:" in soup.title.get_text():
            title = soup.title.get_text(" ", strip=True)
            return title.split("dział:", 1)[1].strip()
        return None

    def find_heading(self, soup: BeautifulSoup, heading_text: str) -> Tag | None:
        normalized_target = self.normalize_space(heading_text)
        for heading in soup.find_all(["h1", "h2", "h3", "h4"]):
            if self.normalize_space(heading.get_text(" ", strip=True)) == normalized_target:
                return heading
        return None

    def extract_question_ids(self, html: str) -> list[str]:
        question_ids: list[str] = []
        for question_id in re.findall(r"/testy-na-prawo-jazdy/pytanie/(\d+)", html):
            if question_id not in question_ids:
                question_ids.append(question_id)
        return question_ids

    def get(self, url: str) -> requests.Response:
        response = self.session.get(url, timeout=self.timeout_seconds)
        response.raise_for_status()
        if self.pause_seconds:
            time.sleep(self.pause_seconds)
        return response

    def write_json(self, path: Path, payload: object) -> None:
        path.write_text(
            json.dumps(payload, ensure_ascii=False, indent=2),
            encoding="utf-8",
        )

    def normalize_space(self, value: str) -> str:
        return " ".join(value.split())

    def slugify_path(self, value: str) -> str:
        slug = value.strip("/").replace("/", "__")
        return slug or "root"


def build_arg_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Scrape question catalog data from zdamyto.com")
    parser.add_argument("--email", required=True, help="Login email for zdamyto.com")
    parser.add_argument("--password", required=True, help="Login password for zdamyto.com")
    parser.add_argument(
        "--out",
        default="storage/app/zdamyto-scrape",
        help="Output directory for raw pages, media and parsed data",
    )
    parser.add_argument(
        "--categories",
        default=",".join(DEFAULT_CATEGORIES),
        help="Comma-separated category codes to scan, e.g. A,B,C,D,T",
    )
    parser.add_argument("--discover-only", action="store_true", help="Only discover categories and sections")
    parser.add_argument("--question-limit", type=int, default=None, help="Limit unique fetched question pages")
    parser.add_argument("--pause", type=float, default=0.0, help="Pause between GET requests in seconds")
    return parser


def main() -> int:
    parser = build_arg_parser()
    args = parser.parse_args()

    scraper = ZdamytoScraper(
        email=args.email,
        password=args.password,
        out_dir=Path(args.out),
        category_codes=[item.strip().upper() for item in args.categories.split(",") if item.strip()],
        pause_seconds=args.pause,
    )
    summary = scraper.run(discover_only=args.discover_only, question_limit=args.question_limit)
    print(json.dumps(summary["summary"] if "summary" in summary else summary, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

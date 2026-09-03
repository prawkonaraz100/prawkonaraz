import fs from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';
import { chromium } from 'playwright';

const cwd = process.cwd();
const outputDir = path.join(cwd, 'output', 'playwright', 'session-stress');
const reportPath = path.join(outputDir, 'session-stress-report.json');
const markdownPath = path.join(outputDir, 'session-stress-report.md');
const baseUrl = process.env.SESSION_STRESS_BASE_URL ?? 'http://127.0.0.1:8000';
const email = process.env.SESSION_STRESS_EMAIL ?? 'smoke-e2e@example.test';
const password = process.env.SESSION_STRESS_PASSWORD ?? 'ChangeMe123!';
const perStatusSmokeStepLimit = Number(process.env.SESSION_STRESS_SMOKE_LIMIT ?? 12);
const questionTimeoutMs = Number(process.env.SESSION_STRESS_QUESTION_TIMEOUT_MS ?? 7000);
const runFullUnanswered = process.env.SESSION_STRESS_FULL_UNANSWERED !== 'false';
const categoryFilter = (process.env.SESSION_STRESS_CATEGORY_CODES ?? '')
    .split(',')
    .map((value) => value.trim())
    .filter(Boolean);

const statusOrder = ['unanswered', 'all', 'correct', 'incorrect', 'memorized', 'random'];
const answerOptionTestIds = ['answer-option-a', 'answer-option-b', 'answer-option-c'];

const report = {
    started_at: new Date().toISOString(),
    base_url: baseUrl,
    email,
    methodology: {
        unanswered: runFullUnanswered
            ? 'full traversal'
            : `smoke (${perStatusSmokeStepLimit} steps)`,
        all: `smoke (${perStatusSmokeStepLimit} steps)`,
        correct: `smoke (${perStatusSmokeStepLimit} steps)`,
        incorrect: `smoke (${perStatusSmokeStepLimit} steps)`,
        memorized: `smoke (${perStatusSmokeStepLimit} steps)`,
        random: `smoke (${perStatusSmokeStepLimit} steps)`,
    },
    summary: {
        categories: 0,
        session_runs: 0,
        completed_runs: 0,
        failed_runs: 0,
        skipped_runs: 0,
        total_questions_traversed: 0,
        video_questions_seen: 0,
        image_questions_seen: 0,
    },
    categories: [],
};

let browser;
let page;

await main().catch(async (error) => {
    report.finished_at = new Date().toISOString();
    report.status = 'failed';
    report.error = serializeError(error);
    await persistReport();
    console.error(error instanceof Error ? error.stack ?? error.message : String(error));
    process.exitCode = 1;
}).finally(async () => {
    await browser?.close().catch(() => {});
});

async function main() {
    await fs.mkdir(outputDir, { recursive: true });

    browser = await chromium.launch({
        headless: process.env.SESSION_STRESS_HEADLESS !== 'false',
    });

    page = await browser.newPage({
        viewport: { width: 1440, height: 1000 },
    });

    await login();
    await ensureSessionPreferences();

    const categories = await readCategoryOptions();
    const filteredCategories = categoryFilter.length === 0
        ? categories
        : categories.filter((category) =>
            categoryFilter.includes(category.code)
            || categoryFilter.includes(category.label)
            || categoryFilter.includes(category.value),
        );

    report.summary.categories = filteredCategories.length;
    console.log(
        `[session-stress] categories=${filteredCategories.map((category) => category.code).join(', ') || 'none'}`,
    );

    for (const category of filteredCategories) {
        const categoryReport = await runCategory(category);
        report.categories.push(categoryReport);
        await persistReport();
    }

    report.finished_at = new Date().toISOString();
    report.status = 'ok';
    await persistReport();
}

async function login() {
    await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });

    if (new URL(page.url()).pathname === '/moje-postepy') {
        return;
    }

    await page.locator('[data-testid="login-email"]').fill(email);
    await page.locator('[data-testid="login-password"]').fill(password);
    await Promise.all([
        page.waitForURL('**/moje-postepy', { timeout: 30000 }),
        page.locator('[data-testid="login-submit"]').click(),
    ]);
}

async function ensureSessionPreferences() {
    await page.goto(`${baseUrl}/nauka`, { waitUntil: 'domcontentloaded' });
    await page.evaluate(() => {
        window.localStorage.setItem(
            'study-session-preferences',
            JSON.stringify({
                autoAdvance: true,
                autoPlayVideos: false,
            }),
        );
    });
}

async function readCategoryOptions() {
    await page.goto(`${baseUrl}/nauka`, { waitUntil: 'domcontentloaded' });
    await page.locator('label:has-text("Kategoria") select').waitFor({ timeout: 30000 });

    return page.locator('label:has-text("Kategoria") select option').evaluateAll((elements) =>
        elements
            .map((element) => ({
                value: element.getAttribute('value') ?? '',
                label: (element.textContent ?? '').trim(),
                code: (element.textContent ?? '').trim(),
            }))
            .filter((option) => option.value !== ''),
    );
}

async function runCategory(category) {
    await setActiveCategory(category.value);
    await page.goto(`${baseUrl}/nauka`, { waitUntil: 'domcontentloaded' });

    const groups = await readGroupOptions();
    const statuses = await readStatusOptions();

    console.log(
        `[session-stress] category=${category.code} groups=${groups.length} statuses=${statuses.length}`,
    );

    const categoryReport = {
        category: category,
        groups: [],
    };

    for (const group of groups) {
        const groupReport = {
            group,
            runs: [],
        };

        for (const status of statuses.filter((option) => statusOrder.includes(option.value))) {
            const mode = status.value === 'unanswered' && runFullUnanswered
                ? 'full'
                : 'smoke';

            const runReport = await runSession(category, group, status, mode);
            groupReport.runs.push(runReport);

            report.summary.session_runs += 1;

            if (runReport.result === 'completed') {
                report.summary.completed_runs += 1;
            } else if (runReport.result === 'failed') {
                report.summary.failed_runs += 1;
            } else {
                report.summary.skipped_runs += 1;
            }

            report.summary.total_questions_traversed += runReport.questions_traversed;
            report.summary.video_questions_seen += runReport.video_questions_seen;
            report.summary.image_questions_seen += runReport.image_questions_seen;
            await persistReport();
        }

        categoryReport.groups.push(groupReport);
    }

    return categoryReport;
}

async function setActiveCategory(categoryValue) {
    await page.goto(`${baseUrl}/nauka`, { waitUntil: 'domcontentloaded' });

    const categorySelect = page.locator('label:has-text("Kategoria") select');
    await categorySelect.waitFor({ timeout: 30000 });
    const currentValue = await categorySelect.inputValue();

    if (currentValue === categoryValue) {
        return;
    }

    await Promise.all([
        page.waitForURL('**/nauka', { timeout: 30000 }),
        categorySelect.selectOption(categoryValue),
    ]);
}

async function readGroupOptions() {
    await page.locator('#question_topic_id').waitFor({ timeout: 30000 });

    return page.locator('#question_topic_id option').evaluateAll((elements) =>
        elements.map((element) => ({
            value: element.getAttribute('value') ?? '',
            label: (element.textContent ?? '').replace(/\s+/g, ' ').trim(),
        })),
    );
}

async function readStatusOptions() {
    await page.locator('#question_status').waitFor({ timeout: 30000 });

    return page.locator('#question_status option').evaluateAll((elements) =>
        elements.map((element) => ({
            value: element.getAttribute('value') ?? '',
            label: (element.textContent ?? '').replace(/\s+/g, ' ').trim(),
        })),
    );
}

async function runSession(category, group, status, mode) {
    const runReport = {
        category_code: category.value,
        category_label: category.label,
        group_value: group.value,
        group_label: group.label,
        status_value: status.value,
        status_label: status.label,
        mode,
        result: 'running',
        questions_reported: 0,
        questions_traversed: 0,
        video_questions_seen: 0,
        image_questions_seen: 0,
        average_transition_ms: null,
        max_transition_ms: null,
        request_statuses: {},
        failure: null,
    };

    await page.goto(`${baseUrl}/nauka`, { waitUntil: 'domcontentloaded' });
    await page.locator('#question_topic_id').selectOption(group.value);
    await page.locator('#question_status').selectOption(status.value);

    const responses = [];
    const responseHandler = async (response) => {
        const url = response.url();

        if (!url.includes('/nauka/teraz/odpowiedzi') && !url.includes('/nauka/teraz/pytania')) {
            return;
        }

        const key = `${response.request().method()} ${new URL(url).pathname}`;
        responses.push({
            key,
            status: response.status(),
        });
    };

    page.on('response', responseHandler);

    try {
        console.log(
            `[session-stress] run start category=${category.code} group="${group.label}" status=${status.value} mode=${mode}`,
        );
        await page.getByRole('button', { name: 'Rozpocznij naukę' }).click();
        await waitForSessionStart();

        if (!page.url().includes('/nauka/teraz')) {
            runReport.result = 'skipped';
            runReport.failure = await readSessionStartFailure();
            return finalizeRunReport(runReport, responses);
        }

        const totalQuestions = await readCurrentSessionTotalQuestions();
        runReport.questions_reported = totalQuestions;

        const transitionDurations = [];
        const maxSteps = mode === 'full'
            ? Math.max(totalQuestions, 1)
            : Math.min(totalQuestions, perStatusSmokeStepLimit);

        for (let index = 0; index < maxSteps; index += 1) {
            if (await isSessionCompleted()) {
                break;
            }

            const prompt = await readCurrentPrompt();
            const signature = await readCurrentQuestionSignature();

            if (!prompt) {
                runReport.result = 'failed';
                runReport.failure = {
                    reason: 'missing_prompt',
                    step: index + 1,
                };
                break;
            }

            const hasVideo = (await page.locator('[data-testid="session-question-video-poster"]').count()) > 0
                || (await page.locator('[data-testid="session-question-video"]').count()) > 0;
            const hasImage = (await page.locator('[data-testid="session-question-image"]').count()) > 0;

            if (hasVideo) {
                runReport.video_questions_seen += 1;
            }

            if (hasImage) {
                runReport.image_questions_seen += 1;
            }

            const answerButton = await firstAvailableAnswerButton();

            if (!answerButton) {
                runReport.result = 'failed';
                runReport.failure = {
                    reason: 'missing_answer_button',
                    step: index + 1,
                    prompt,
                };
                break;
            }

            const startedAt = Date.now();
            await answerButton.click();

            try {
                await waitForQuestionAdvance(signature);
            } catch {
                runReport.result = 'failed';
                runReport.failure = {
                    reason: 'question_did_not_advance',
                    step: index + 1,
                    prompt,
                    signature,
                    current_prompt: await readCurrentPrompt(),
                    current_signature: await readCurrentQuestionSignature(),
                    visible_sync_errors: await readSyncErrors(),
                };
                break;
            }

            transitionDurations.push(Date.now() - startedAt);
            runReport.questions_traversed += 1;
        }

        if (runReport.result === 'running') {
            runReport.result = 'completed';
        }

        console.log(
            `[session-stress] run end category=${category.code} group="${group.label}" status=${status.value} result=${runReport.result} traversed=${runReport.questions_traversed}`,
        );

        runReport.average_transition_ms = transitionDurations.length
            ? Math.round(
                transitionDurations.reduce((sum, value) => sum + value, 0)
                / transitionDurations.length,
            )
            : null;
        runReport.max_transition_ms = transitionDurations.length
            ? Math.max(...transitionDurations)
            : null;

        if (runReport.result === 'failed') {
            const failurePath = path.join(
                outputDir,
                sanitizeFilename(`${category.value}-${group.value}-${status.value}-failure.png`),
            );
            await page.screenshot({ path: failurePath, fullPage: true }).catch(() => {});
            runReport.failure = {
                ...runReport.failure,
                screenshot: failurePath,
            };
        }

        return finalizeRunReport(runReport, responses);
    } finally {
        page.off('response', responseHandler);
    }
}

async function waitForSessionStart() {
    const startedAt = Date.now();

    while (Date.now() - startedAt < 15000) {
        const pathname = new URL(page.url()).pathname;

        if (pathname === '/nauka/teraz' || pathname === '/nauka') {
            if (pathname === '/nauka/teraz') {
                return;
            }
        }

        await page.waitForTimeout(100);
    }
}

async function readSessionStartFailure() {
    const errors = await page.locator('form p.text-sm.font-medium').allTextContents().catch(() => []);

    return {
        reason: 'session_not_started',
        errors,
        url: page.url(),
    };
}

async function readCurrentSessionTotalQuestions() {
    const label = await readCurrentQuestionCounterLabel();
    const match = label.match(/Pytanie\s+\d+\s*\/\s*(\d+)/i);

    return match ? Number(match[1]) : 0;
}

async function readCurrentQuestionCounterLabel() {
    return ((await page.locator('text=/Pytanie\\s+\\d+\\s*\\/\\s*\\d+/').first().textContent().catch(() => '')) || '').trim();
}

async function isSessionCompleted() {
    return page.locator('[data-testid="session-complete"]').count().then((count) => count > 0);
}

async function readCurrentPrompt() {
    return ((await page.locator('[data-testid="session-question-prompt"]').textContent().catch(() => '')) || '')
        .replace(/\s+/g, ' ')
        .trim();
}

async function readCurrentQuestionSignature() {
    const [counter, prompt] = await Promise.all([
        readCurrentQuestionCounterLabel(),
        readCurrentPrompt(),
    ]);

    return `${counter} | ${prompt}`;
}

async function firstAvailableAnswerButton() {
    for (const testId of answerOptionTestIds) {
        const locator = page.locator(`[data-testid="${testId}"]`);

        if (await locator.count()) {
            const isDisabled = await locator.isDisabled().catch(() => true);

            if (!isDisabled) {
                return locator;
            }
        }
    }

    return null;
}

async function waitForQuestionAdvance(previousSignature) {
    await page.waitForFunction(
        ({ signature }) => {
            const completed = document.querySelector('[data-testid="session-complete"]');

            if (completed) {
                return true;
            }

            const counter = Array.from(document.querySelectorAll('*'))
                .map((element) => (element.textContent || '').trim())
                .find((text) => /Pytanie\s+\d+\s*\/\s*\d+/i.test(text));
            const currentPrompt = document.querySelector('[data-testid="session-question-prompt"]');
            const normalizedPrompt = currentPrompt
                ? (currentPrompt.textContent || '').replace(/\\s+/g, ' ').trim()
                : '';
            const currentSignature = `${counter || ''} | ${normalizedPrompt}`;

            return !!currentPrompt && currentSignature !== signature;
        },
        { signature: previousSignature },
        { timeout: questionTimeoutMs },
    );
}

async function readSyncErrors() {
    const texts = await page.locator('text=/Laduje kolejne pytanie|Nie udalo sie zapisac|Kolejne pytanie nie jest jeszcze gotowe|Trwa zapisywanie odpowiedzi|Nie udalo sie doladowac kolejnych pytan/i')
        .allTextContents()
        .catch(() => []);

    return texts.map((text) => text.replace(/\s+/g, ' ').trim());
}

function finalizeRunReport(runReport, responses) {
    runReport.request_statuses = responses.reduce((carry, item) => {
        const bucket = carry[item.key] ?? {};
        bucket[item.status] = (bucket[item.status] ?? 0) + 1;
        carry[item.key] = bucket;

        return carry;
    }, {});

    return runReport;
}

async function persistReport() {
    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(reportPath, `${JSON.stringify(report, null, 2)}\n`, 'utf8');
    await fs.writeFile(markdownPath, buildMarkdownReport(report), 'utf8');
}

function buildMarkdownReport(currentReport) {
    const lines = [
        '# Session Stress Report',
        '',
        `- Started: ${currentReport.started_at}`,
        `- Finished: ${currentReport.finished_at ?? '-'}`,
        `- Base URL: ${currentReport.base_url}`,
        `- Status: ${currentReport.status ?? 'running'}`,
        '',
        '## Summary',
        '',
        `- Categories: ${currentReport.summary.categories}`,
        `- Session runs: ${currentReport.summary.session_runs}`,
        `- Completed runs: ${currentReport.summary.completed_runs}`,
        `- Failed runs: ${currentReport.summary.failed_runs}`,
        `- Skipped runs: ${currentReport.summary.skipped_runs}`,
        `- Traversed questions: ${currentReport.summary.total_questions_traversed}`,
        `- Video questions seen: ${currentReport.summary.video_questions_seen}`,
        `- Image questions seen: ${currentReport.summary.image_questions_seen}`,
        '',
        '## Methodology',
        '',
        ...Object.entries(currentReport.methodology).map(([key, value]) => `- ${key}: ${value}`),
        '',
        '## Failures',
        '',
    ];

    const failures = [];

    for (const category of currentReport.categories) {
        for (const group of category.groups) {
            for (const run of group.runs) {
                if (run.result === 'failed') {
                    failures.push(
                        `- ${run.category_code} | ${run.group_label} | ${run.status_value}: ${run.failure?.reason ?? 'unknown'}`,
                    );
                }
            }
        }
    }

    if (failures.length === 0) {
        lines.push('- No failures recorded.');
    } else {
        lines.push(...failures);
    }

    return `${lines.join('\n')}\n`;
}

function sanitizeFilename(value) {
    return value.replace(/[^a-z0-9._-]+/gi, '-').toLowerCase();
}

function serializeError(error) {
    if (error instanceof Error) {
        return {
            name: error.name,
            message: error.message,
            stack: error.stack,
        };
    }

    return {
        message: String(error),
    };
}

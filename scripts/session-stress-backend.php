<?php

declare(strict_types=1);

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\StudySession;
use App\Models\User;
use App\Support\QuestionProgressManager;
use App\Support\StudySessionManager;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$outputDir = __DIR__.'/../output/session-stress-backend';
$jsonPath = $outputDir.'/report.json';
$markdownPath = $outputDir.'/report.md';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$categoryFilter = array_values(array_filter(array_map(
    static fn (string $value): string => trim($value),
    explode(',', (string) ($_ENV['SESSION_STRESS_CATEGORY_CODES'] ?? getenv('SESSION_STRESS_CATEGORY_CODES') ?: ''))
)));
$statusFilter = array_values(array_filter(array_map(
    static fn (string $value): string => trim($value),
    explode(',', (string) ($_ENV['SESSION_STRESS_STATUS_FILTER'] ?? getenv('SESSION_STRESS_STATUS_FILTER') ?: ''))
)));
$statuses = $statusFilter !== []
    ? $statusFilter
    : ['unanswered', 'incorrect', 'correct', 'memorized', 'random', 'all'];

$email = (string) ($_ENV['SESSION_STRESS_EMAIL'] ?? getenv('SESSION_STRESS_EMAIL') ?: 'smoke-e2e@example.test');
$password = (string) ($_ENV['SESSION_STRESS_PASSWORD'] ?? getenv('SESSION_STRESS_PASSWORD') ?: 'ChangeMe123!');

/** @var StudySessionManager $studySessionManager */
$studySessionManager = $app->make(StudySessionManager::class);
/** @var QuestionProgressManager $questionProgressManager */
$questionProgressManager = $app->make(QuestionProgressManager::class);

$user = User::query()->firstOrCreate(
    ['email' => $email],
    [
        'name' => 'Session Stress',
        'password' => Hash::make($password),
        'email_verified_at' => now(),
    ],
);

if (! $user->exists) {
    throw new RuntimeException('Failed to create or load stress-test user.');
}

$report = [
    'started_at' => now()->toIso8601String(),
    'user' => [
        'id' => $user->getKey(),
        'email' => $user->email,
    ],
    'statuses' => $statuses,
    'summary' => [
        'categories' => 0,
        'groups' => 0,
        'runs' => 0,
        'completed' => 0,
        'failed' => 0,
        'skipped' => 0,
        'questions_traversed' => 0,
    ],
    'failures' => [],
    'categories' => [],
];

resetUserStudyState($user);

$categories = LicenseCategory::query()
    ->orderBy('code')
    ->get()
    ->filter(static fn (LicenseCategory $category): bool => $categoryFilter === []
        || in_array($category->code, $categoryFilter, true))
    ->values();

$report['summary']['categories'] = $categories->count();

foreach ($categories as $category) {
    $categoryRow = [
        'code' => $category->code,
        'name' => $category->name,
        'groups' => [],
    ];

    $topicCounts = Question::query()
        ->selectRaw('question_topic_id, count(*) as questions_count')
        ->where('license_category_id', $category->getKey())
        ->where('is_active', true)
        ->readyForDelivery()
        ->whereNotNull('question_topic_id')
        ->groupBy('question_topic_id')
        ->orderBy('question_topic_id')
        ->get();

    $topicIds = $topicCounts->pluck('question_topic_id')->map(static fn ($id): int => (int) $id)->all();
    $topics = QuestionTopic::query()
        ->whereIn('id', $topicIds)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get()
        ->keyBy('id');

    foreach ($topicCounts as $topicCount) {
        $topicId = (int) $topicCount->question_topic_id;
        /** @var QuestionTopic|null $topic */
        $topic = $topics->get($topicId);

        if (! $topic) {
            continue;
        }

        $report['summary']['groups']++;
        $groupRow = [
            'topic_id' => $topicId,
            'topic_key' => $topic->key,
            'topic_name' => $topic->name,
            'questions_count' => (int) $topicCount->questions_count,
            'runs' => [],
        ];

        echo sprintf(
            "[backend-stress] category=%s topic=%s questions=%d\n",
            $category->code,
            $topic->name,
            (int) $topicCount->questions_count,
        );

        foreach ($statuses as $status) {
            $run = runStatusStress(
                $studySessionManager,
                $questionProgressManager,
                $user,
                $category,
                $topic,
                $status,
            );

            $groupRow['runs'][] = $run;
            $report['summary']['runs']++;
            $report['summary']['questions_traversed'] += $run['questions_traversed'];

            if ($run['result'] === 'completed') {
                $report['summary']['completed']++;
            } elseif ($run['result'] === 'failed') {
                $report['summary']['failed']++;
                $report['failures'][] = [
                    'category_code' => $category->code,
                    'topic_name' => $topic->name,
                    'status' => $status,
                    'failure' => $run['failure'],
                ];
            } else {
                $report['summary']['skipped']++;
            }

            file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
            file_put_contents($markdownPath, buildMarkdownReport($report));
        }

        $categoryRow['groups'][] = $groupRow;
    }

    $report['categories'][] = $categoryRow;
}

$report['finished_at'] = now()->toIso8601String();
$report['status'] = 'ok';

file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
file_put_contents($markdownPath, buildMarkdownReport($report));

echo "[backend-stress] done\n";

function runStatusStress(
    StudySessionManager $studySessionManager,
    QuestionProgressManager $questionProgressManager,
    User $user,
    LicenseCategory $category,
    QuestionTopic $topic,
    string $status,
): array {
    $run = [
        'status' => $status,
        'result' => 'running',
        'questions_reported' => 0,
        'questions_traversed' => 0,
        'duration_ms' => null,
        'failure' => null,
    ];

    $startedAt = microtime(true);
    $questions = topicQuestions($category, $topic);
    $run['questions_reported'] = $questions->count();

    resetTopicStudyState($user, $questions->pluck('id')->all());
    seedStatusBaseline($questionProgressManager, $user, $questions, $status);

    try {
        $studySession = $studySessionManager->start(
            $user,
            $category,
            StudySessionManager::MODE_LEARN,
            max($questions->count(), 1),
            [
                'question_topic_id' => $topic->getKey(),
                'question_status' => $status,
            ],
        );
    } catch (ValidationException $exception) {
        $errors = $exception->errors();
        $run['result'] = $status === 'unanswered'
            ? 'failed'
            : 'skipped';
        $run['failure'] = [
            'reason' => 'session_not_started',
            'errors' => $errors,
        ];
        $run['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

        echo sprintf(
            "[backend-stress] %s | %s | %s -> %s\n",
            $category->code,
            $topic->name,
            $status,
            $run['result'],
        );

        return $run;
    }

    $questionIds = $studySessionManager->questionIds($studySession);
    $sessionQuestions = Question::query()
        ->whereIn('id', $questionIds)
        ->get(['id', 'correct_answer', 'option_a', 'option_b', 'option_c'])
        ->keyBy('id');

    foreach ($questionIds as $expectedIndex => $questionId) {
        $studySession = StudySession::query()->findOrFail($studySession->getKey());
        $payload = is_array($studySession->payload) ? $studySession->payload : [];
        $currentIndex = (int) ($payload['current_index'] ?? 0);
        $currentQuestionId = $questionIds[$currentIndex] ?? null;

        if ($studySession->status === 'completed') {
            break;
        }

        if ($currentQuestionId !== $questionId) {
            $run['result'] = 'failed';
            $run['failure'] = [
                'reason' => 'unexpected_current_question',
                'expected_question_id' => $questionId,
                'actual_question_id' => $currentQuestionId,
                'expected_index' => $expectedIndex,
                'actual_index' => $currentIndex,
            ];
            break;
        }

        /** @var Question|null $question */
        $question = $sessionQuestions->get($questionId);

        if (! $question) {
            $run['result'] = 'failed';
            $run['failure'] = [
                'reason' => 'missing_question_model',
                'question_id' => $questionId,
            ];
            break;
        }

        try {
            $studySessionManager->recordAnswer(
                $studySession,
                $question,
                chooseAnswer($question, $status),
                1000,
            );
        } catch (Throwable $throwable) {
            $run['result'] = 'failed';
            $run['failure'] = [
                'reason' => 'record_answer_failed',
                'question_id' => $questionId,
                'message' => $throwable->getMessage(),
            ];
            break;
        }

        $run['questions_traversed']++;

        $studySession = StudySession::query()->findOrFail($studySession->getKey());
        $nextPayload = is_array($studySession->payload) ? $studySession->payload : [];
        $nextIndex = (int) ($nextPayload['current_index'] ?? 0);

        if ($studySession->status !== 'completed' && $nextIndex <= $currentIndex) {
            $run['result'] = 'failed';
            $run['failure'] = [
                'reason' => 'session_did_not_advance',
                'question_id' => $questionId,
                'current_index' => $currentIndex,
                'next_index' => $nextIndex,
            ];
            break;
        }
    }

    if ($run['result'] === 'running') {
        $run['result'] = 'completed';
    }

    $run['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);

    echo sprintf(
        "[backend-stress] %s | %s | %s -> %s (%d/%d)\n",
        $category->code,
        $topic->name,
        $status,
        $run['result'],
        $run['questions_traversed'],
        $run['questions_reported'],
    );

    return $run;
}

function topicQuestions(LicenseCategory $category, QuestionTopic $topic)
{
    return Question::query()
        ->where('license_category_id', $category->getKey())
        ->where('question_topic_id', $topic->getKey())
        ->where('is_active', true)
        ->readyForDelivery()
        ->orderBy('id')
        ->get(['id', 'correct_answer', 'option_a', 'option_b', 'option_c']);
}

function chooseAnswer(Question $question, string $status): string
{
    $correct = strtolower((string) $question->correct_answer);
    $available = array_values(array_filter([
        $question->option_a !== null ? 'a' : null,
        $question->option_b !== null ? 'b' : null,
        $question->option_c !== null ? 'c' : null,
    ]));

    if ($status === 'incorrect') {
        foreach ($available as $option) {
            if ($option !== $correct) {
                return $option;
            }
        }
    }

    return in_array($correct, $available, true)
        ? $correct
        : ($available[0] ?? 'a');
}

function seedStatusBaseline(
    QuestionProgressManager $questionProgressManager,
    User $user,
    $questions,
    string $status,
): void {
    if ($status === 'unanswered' || $status === 'all' || $status === 'random') {
        return;
    }

    foreach ($questions as $question) {
        if ($status === 'incorrect') {
            $questionProgressManager->recordAnswer($user, $question, false, 25000);

            continue;
        }

        $questionProgressManager->recordAnswer($user, $question, true, 1500);

        if ($status === 'memorized') {
            $questionProgressManager->recordAnswer($user, $question, true, 1500);
        }
    }
}

function resetUserStudyState(User $user): void
{
    DB::transaction(function () use ($user): void {
        DB::table('study_session_answers')
            ->whereIn('study_session_id', function ($query) use ($user): void {
                $query->select('id')
                    ->from('study_sessions')
                    ->where('user_id', $user->getKey());
            })
            ->delete();

        DB::table('study_sessions')
            ->where('user_id', $user->getKey())
            ->delete();

        DB::table('user_question_progress')
            ->where('user_id', $user->getKey())
            ->delete();
    });
}

function resetTopicStudyState(User $user, array $questionIds): void
{
    DB::transaction(function () use ($questionIds, $user): void {
        DB::table('study_session_answers')
            ->whereIn('study_session_id', function ($query) use ($questionIds, $user): void {
                $query->select('study_sessions.id')
                    ->from('study_sessions')
                    ->join('study_session_answers', 'study_session_answers.study_session_id', '=', 'study_sessions.id')
                    ->where('study_sessions.user_id', $user->getKey())
                    ->whereIn('study_session_answers.question_id', $questionIds);
            })
            ->delete();

        DB::table('study_sessions')
            ->where('user_id', $user->getKey())
            ->delete();

        DB::table('user_question_progress')
            ->where('user_id', $user->getKey())
            ->whereIn('question_id', $questionIds)
            ->delete();
    });
}

function buildMarkdownReport(array $report): string
{
    $lines = [
        '# Backend Session Stress Report',
        '',
        '- Started: '.($report['started_at'] ?? '-'),
        '- Finished: '.($report['finished_at'] ?? '-'),
        '- Status: '.($report['status'] ?? 'running'),
        '',
        '## Summary',
        '',
        '- Categories: '.$report['summary']['categories'],
        '- Groups: '.$report['summary']['groups'],
        '- Runs: '.$report['summary']['runs'],
        '- Completed: '.$report['summary']['completed'],
        '- Failed: '.$report['summary']['failed'],
        '- Skipped: '.$report['summary']['skipped'],
        '- Questions traversed: '.$report['summary']['questions_traversed'],
        '',
        '## Failures',
        '',
    ];

    if ($report['failures'] === []) {
        $lines[] = '- No failures recorded.';
    } else {
        foreach ($report['failures'] as $failure) {
            $lines[] = sprintf(
                '- %s | %s | %s: %s',
                $failure['category_code'],
                $failure['topic_name'],
                $failure['status'],
                $failure['failure']['reason'] ?? 'unknown',
            );
        }
    }

    return implode(PHP_EOL, $lines).PHP_EOL;
}

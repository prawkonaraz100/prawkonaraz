<?php

use App\Models\QuestionCollection;
use App\Models\QuestionModule;
use App\Support\LearningProgressMessageService;
use App\Support\QuestionCollectionProgressService;
use Tests\TestCase;

uses(TestCase::class);

test('learning messages use exact counts and achievements without mistake or review prompts', function () {
    $service = new LearningProgressMessageService;
    $base = [
        'total_questions' => 200, 'answered_questions' => 200, 'unanswered_questions' => 0,
        'incorrect_questions' => 0, 'total_topics' => 3, 'completed_topics' => 3, 'percent' => 100,
    ];
    $cases = [
        [['total_questions' => 0, 'total_topics' => 0], 0, 'empty', 'none'],
        [['answered_questions' => 0, 'unanswered_questions' => 200, 'completed_topics' => 0], 10, 'not_started', 'classic'],
        [['answered_questions' => 194, 'unanswered_questions' => 6, 'completed_topics' => 0, 'percent' => 97], 10, 'in_progress', 'classic'],
        [['answered_questions' => 199, 'unanswered_questions' => 1, 'percent' => 100], 0, 'in_progress', 'classic'],
        [['incorrect_questions' => 4, 'completed_topics' => 0], 10, 'topics_pending', 'classic'],
        [['incorrect_questions' => 4], 10, 'ready', 'exam'],
        [['completed_topics' => 2], 10, 'topics_pending', 'classic'],
        [[], 4, 'ready', 'exam'],
        [[], 0, 'ready', 'exam'],
        // A manually retained list is not a current question-error bucket.
        [['incorrect_list_count' => 9], 0, 'ready', 'exam'],
    ];
    foreach ($cases as [$changes, $pending, $state, $action]) {
        $message = $service->forCategory([...$base, ...$changes], $pending);
        expect($message['state'])->toBe($state)->and($message['action'])->toBe($action)
            ->and($message['title'])->not->toBeEmpty()->and($message['message'])->not->toBeEmpty();
        expect($message['icon'])->toBe(match ($state) {
            'not_started' => 'book', 'in_progress' => 'arrow', 'topics_pending' => 'clipboard',
            'ready' => 'trophy', default => 'info',
        });
    }
    expect($service->forCategory($base, 4))->toBe($service->forCategory($base, 0));
    expect($service->forCategory([...$base, 'incorrect_questions' => 2], 0))->toBe($service->forCategory($base, 0));
    $congratulations = $service->forCategory([...$base, 'answered_questions' => 194, 'unanswered_questions' => 6, 'completed_topics' => 1], 0, ['name' => 'Znaki ostrzegawcze']);
    expect($congratulations['state'])->toBe('topic_completed')
        ->and($congratulations['title'])->toBe('Gratulacje! Dział zaliczony')
        ->and($congratulations['message'])->toContain('Znaki ostrzegawcze')->toContain('1 / 3')
        ->and($congratulations['icon'])->toBe('trophy')->and($congratulations['action'])->toBe('classic');
    expect($service->forCategory($base, 0, ['name' => 'Ostatni dział'])['action'])->toBe('exam');

    foreach ([
        [0, 0, 0, 'empty'], [200, 0, 0, 'not_started'], [200, 199, 0, 'in_progress'],
        [200, 200, 2, 'covered'], [200, 200, 0, 'covered'],
    ] as [$total, $answered, $incorrect, $state]) {
        $message = $service->forProfessionalCourse(['total_questions' => $total, 'answered_count' => $answered, 'percent' => 100], $incorrect);
        expect($message['state'])->toBe($state)->and($message['action'])->not->toBe('exam');
        expect($message['icon'])->toBe(match ($state) {
            'not_started' => 'book', 'in_progress' => 'arrow', 'covered' => 'check', default => 'info',
        });
    }
    expect($service->forProfessionalCourse(['total_questions' => 200, 'answered_count' => 200], 2))
        ->toBe($service->forProfessionalCourse(['total_questions' => 200, 'answered_count' => 200], 0));

    $course = new QuestionCollection;
    $course->setRelation('modules', collect([new QuestionModule(['id' => 1])]));
    $module = $course->modules->first();
    $module->forceFill(['id' => 1]);
    $progress = (new QuestionCollectionProgressService)->collectionProgressFor($course, collect([
        1 => ['answered_count' => 199, 'total_questions' => 200, 'percent' => 100],
    ]));
    expect($progress['percent'])->toBe(99);
});

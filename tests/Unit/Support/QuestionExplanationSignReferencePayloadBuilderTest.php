<?php

use App\Models\Question;
use App\Models\QuestionExplanationSignOverride;
use App\Models\SharedQuestionExplanationSignOverride;
use App\Models\TrafficSign;
use App\Support\QuestionExplanationSignOverrideManager;
use App\Support\QuestionExplanationSignReferencePayloadBuilder;
use App\Support\StudySessionApiPayloadBuilder;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

function publishedExplanationSign(string $code, string $name): TrafficSign
{
    return TrafficSign::factory()
        ->published()
        ->create([
            'code' => $code,
            'name' => $name,
            'image_path' => "https://media.example.test/traffic-signs/{$code}.webp",
            'image_alt' => "Znak {$code} {$name}",
        ]);
}

test('it resolves every unique published sign in explanation order', function () {
    config(['study.explanation_sign_references_enabled' => true]);

    publishedExplanationSign('B-20', 'Stop');
    publishedExplanationSign('P-12', 'Linia bezwzglednego zatrzymania');
    publishedExplanationSign('A-7', 'Ustap pierwszenstwa');
    publishedExplanationSign('C-1', 'Nakaz jazdy prosto');

    $question = Question::factory()->create([
        'explanation' => 'B-20 nakazuje zatrzymanie. P-12 wskazuje miejsce. B-20 nie zwalnia z obowiazku. A-7 zmienia pierwszenstwo, a C-1 kierunek jazdy.',
    ]);

    $references = app(QuestionExplanationSignReferencePayloadBuilder::class)->forQuestion($question);

    expect($references)->toHaveCount(4)
        ->and(collect($references)->pluck('code')->all())->toBe(['B-20', 'P-12', 'A-7', 'C-1'])
        ->and($references[0]['name'])->toBe('Stop')
        ->and($references[0]['image_url'])->toBe('https://media.example.test/traffic-signs/B-20.webp')
        ->and($references[0]['source'])->toBe('automatic');
});

test('it ignores unavailable sign graphics and can be disabled without changing explanations', function () {
    $question = Question::factory()->create([
        'explanation' => 'S-3 pokazuje kierunek ruchu, a B-20 nakazuje zatrzymanie.',
    ]);

    TrafficSign::factory()
        ->published()
        ->create([
            'code' => 'S-3',
            'name' => 'Sygnalizator kierunkowy',
            'image_path' => 'traffic-signs/missing-s-3.webp',
        ]);
    publishedExplanationSign('B-20', 'Stop');

    config(['study.explanation_sign_references_enabled' => true]);

    expect(collect(app(QuestionExplanationSignReferencePayloadBuilder::class)->forQuestion($question))
        ->pluck('code')
        ->all()
    )->toBe(['B-20']);

    config(['study.explanation_sign_references_enabled' => false]);

    expect(app(QuestionExplanationSignReferencePayloadBuilder::class)->forQuestion($question))
        ->toBe([])
        ->and($question->fresh()->explanation)->toContain('S-3');
});

test('it keeps the source code as the inline match for public sign variants', function () {
    config(['study.explanation_sign_references_enabled' => true]);

    publishedExplanationSign('S-1a', 'Sygnalizator kierunkowy');

    $question = Question::factory()->create([
        'explanation' => 'S-1a wskazuje sygnal dla kierunku ruchu.',
    ]);

    $reference = app(QuestionExplanationSignReferencePayloadBuilder::class)->forQuestion($question)[0];

    expect($reference['code'])->toBe('S-1')
        ->and($reference['match_text'])->toBe('S-1a');
});

test('the API payload uses the same sign references and respects visual explanation visibility', function () {
    config(['study.explanation_sign_references_enabled' => true]);

    publishedExplanationSign('B-20', 'Stop');

    $question = Question::factory()->create([
        'explanation' => 'B-20 nakazuje zatrzymanie.',
    ]);

    $payloadBuilder = app(StudySessionApiPayloadBuilder::class);

    expect($payloadBuilder->question($question, includeVisualExplanations: true)['explanation_sign_references'])
        ->toHaveCount(1)
        ->and($payloadBuilder->question($question, includeVisualExplanations: false)['explanation_sign_references'])
        ->toBe([]);
});

test('shared corrections hide and replace automatic signs for every copy of a question', function () {
    config(['study.explanation_sign_references_enabled' => true]);

    $replacementSign = publishedExplanationSign('A-7', 'Ustap pierwszenstwa');
    publishedExplanationSign('B-20', 'Stop');
    publishedExplanationSign('P-12', 'Linia bezwzglednego zatrzymania');

    $firstQuestion = Question::factory()->create([
        'external_id' => 'pj360:501',
        'source' => 'gov.pl-mi',
        'explanation' => 'B-20 nakazuje zatrzymanie, a P-12 wskazuje miejsce.',
    ]);
    $secondQuestion = Question::factory()->create([
        'external_id' => 'pj360:501',
        'source' => 'gov.pl-mi',
        'explanation' => 'B-20 nakazuje zatrzymanie, a P-12 wskazuje miejsce.',
    ]);

    SharedQuestionExplanationSignOverride::query()->create([
        'external_id' => 'pj360:501',
        'source_scope' => SharedQuestionExplanationSignOverride::sourceScopeFor('gov.pl-mi'),
        'action' => SharedQuestionExplanationSignOverride::ACTION_REPLACE,
        'detected_code' => 'B-20',
        'traffic_sign_id' => $replacementSign->getKey(),
        'position' => 1,
        'is_active' => true,
    ]);
    SharedQuestionExplanationSignOverride::query()->create([
        'external_id' => 'pj360:501',
        'source_scope' => SharedQuestionExplanationSignOverride::sourceScopeFor('gov.pl-mi'),
        'action' => SharedQuestionExplanationSignOverride::ACTION_HIDE,
        'detected_code' => 'P-12',
        'position' => 2,
        'is_active' => true,
    ]);

    $references = app(QuestionExplanationSignReferencePayloadBuilder::class)->forQuestion($secondQuestion);

    expect($references)->toHaveCount(1)
        ->and($references[0]['code'])->toBe('A-7')
        ->and($references[0]['match_text'])->toBe('B-20')
        ->and($references[0]['placement'])->toBe('replace')
        ->and($references[0]['source'])->toBe('shared_override')
        ->and(app(QuestionExplanationSignReferencePayloadBuilder::class)->forQuestion($firstQuestion)[0]['code'])->toBe('A-7');
});

test('a local correction takes precedence over a shared correction and can add one inline sign', function () {
    config(['study.explanation_sign_references_enabled' => true]);

    $replacementSign = publishedExplanationSign('A-7', 'Ustap pierwszenstwa');
    $manualSign = publishedExplanationSign('D-1', 'Droga z pierwszenstwem');
    publishedExplanationSign('B-20', 'Stop');

    $question = Question::factory()->create([
        'external_id' => 'pj360:777',
        'source' => 'gov.pl-mi',
        'explanation' => 'B-20 nakazuje zatrzymanie. Po zatrzymaniu upewnij sie, ze mozna ruszyc.',
    ]);

    SharedQuestionExplanationSignOverride::query()->create([
        'external_id' => 'pj360:777',
        'source_scope' => SharedQuestionExplanationSignOverride::sourceScopeFor('gov.pl-mi'),
        'action' => SharedQuestionExplanationSignOverride::ACTION_REPLACE,
        'detected_code' => 'B-20',
        'traffic_sign_id' => $replacementSign->getKey(),
        'position' => 1,
        'is_active' => true,
    ]);
    QuestionExplanationSignOverride::query()->create([
        'question_id' => $question->getKey(),
        'action' => QuestionExplanationSignOverride::ACTION_HIDE,
        'detected_code' => 'B-20',
        'position' => 1,
        'is_active' => true,
    ]);
    QuestionExplanationSignOverride::query()->create([
        'question_id' => $question->getKey(),
        'action' => QuestionExplanationSignOverride::ACTION_ADD,
        'anchor_text' => 'Po zatrzymaniu',
        'traffic_sign_id' => $manualSign->getKey(),
        'position' => 2,
        'is_active' => true,
    ]);

    $references = app(QuestionExplanationSignReferencePayloadBuilder::class)->forQuestion($question);

    expect($references)->toHaveCount(1)
        ->and($references[0]['code'])->toBe('D-1')
        ->and($references[0]['match_text'])->toBe('Po zatrzymaniu')
        ->and($references[0]['placement'])->toBe('after')
        ->and($references[0]['source'])->toBe('local_override');
});

test('manual additions require one unambiguous explanation fragment', function () {
    $sign = publishedExplanationSign('B-20', 'Stop');
    $question = Question::factory()->create([
        'explanation' => 'Zatrzymaj sie przed przejsciem. Zatrzymaj sie takze przed przejazdem.',
    ]);

    expect(fn () => app(QuestionExplanationSignOverrideManager::class)->syncLocal($question, [[
        'action' => 'add',
        'anchor_text' => 'Zatrzymaj sie',
        'traffic_sign_id' => $sign->getKey(),
        'is_active' => true,
    ]]))->toThrow(ValidationException::class);
});

test('manual replacements require a code that is present in the explanation text', function () {
    $sign = publishedExplanationSign('A-7', 'Ustap pierwszenstwa');
    $question = Question::factory()->create([
        'explanation' => 'B-20 nakazuje zatrzymanie.',
    ]);

    expect(fn () => app(QuestionExplanationSignOverrideManager::class)->syncLocal($question, [[
        'action' => 'replace',
        'detected_code' => 'P-12',
        'traffic_sign_id' => $sign->getKey(),
        'is_active' => true,
    ]]))->toThrow(ValidationException::class);
});

test('manual additions accept text formatted as bold when the anchor is one text node', function () {
    config(['study.explanation_sign_references_enabled' => true]);

    $sign = publishedExplanationSign('A-7', 'Ustap pierwszenstwa');
    $question = Question::factory()->create([
        'explanation' => '**Po zatrzymaniu** upewnij sie, ze mozna ruszyc.',
    ]);

    app(QuestionExplanationSignOverrideManager::class)->syncLocal($question, [[
        'action' => 'add',
        'anchor_text' => 'Po zatrzymaniu',
        'traffic_sign_id' => $sign->getKey(),
        'is_active' => true,
    ]]);

    expect(app(QuestionExplanationSignReferencePayloadBuilder::class)->forQuestion($question)[0]['match_text'])
        ->toBe('Po zatrzymaniu');
});

test('shared corrections require every question copy to contain the code or anchor', function () {
    $sign = publishedExplanationSign('A-7', 'Ustap pierwszenstwa');
    $firstQuestion = Question::factory()->create([
        'external_id' => 'pj360:shared-validation',
        'source' => 'gov.pl-mi',
        'explanation' => 'B-20 nakazuje zatrzymanie.',
    ]);
    Question::factory()->create([
        'external_id' => 'pj360:shared-validation',
        'source' => 'gov.pl-mi',
        'explanation' => 'Ustap pierwszenstwa przed przejazdem.',
    ]);

    expect(fn () => app(QuestionExplanationSignOverrideManager::class)->syncShared($firstQuestion, [[
        'action' => 'replace',
        'detected_code' => 'B-20',
        'traffic_sign_id' => $sign->getKey(),
        'is_active' => true,
    ]]))->toThrow(ValidationException::class)
        ->and(SharedQuestionExplanationSignOverride::query()->count())->toBe(0);
});

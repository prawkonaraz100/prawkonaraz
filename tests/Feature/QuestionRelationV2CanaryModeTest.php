<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionPublicExplanation;
use App\Models\QuestionRelation;
use App\Models\QuestionRelationRankingRun;
use App\Models\QuestionRelationRecommendation;
use App\Models\QuestionRelationRollout;
use App\Models\QuestionSeoTopic;
use App\Models\QuestionSeoTopicMembership;
use App\Support\PublicQuestionRelationsService;
use App\Support\QuestionRelationCanaryRolloutManager;
use App\Support\QuestionRelationV2CanaryMonitor;
use App\Support\QuestionRelationV2ShadowResolver;
use Illuminate\Support\Str;

/** @return array{topic: QuestionSeoTopic, run: QuestionRelationRankingRun, sourceQuestion: Question, sourceCategory: LicenseCategory, targetQuestions: array<int, Question>} */
function createQuestionRelationV2CanaryFixture(bool $withShadowRollout = true): array
{
    $sourceCategory = LicenseCategory::factory()->categoryB()->create();
    $targetCategory = LicenseCategory::factory()->categoryA()->create();
    $topic = QuestionSeoTopic::query()->create([
        'key' => 'secondary:zawracanie-canary-mode',
        'slug' => 'zawracanie-canary-mode',
        'label' => 'Zawracanie — canary',
        'kind' => QuestionSeoTopic::KIND_TOPIC,
        'status' => QuestionSeoTopic::STATUS_PUBLISHED,
        'content_quality_status' => QuestionSeoTopic::QUALITY_APPROVED,
    ]);
    $sourceQuestion = Question::factory()
        ->for($sourceCategory, 'licenseCategory')
        ->create([
            'external_id' => '94000',
            'prompt' => 'Czy możesz wykonać zawracanie w trybie canary?',
        ]);
    $sourceExplanation = QuestionPublicExplanation::factory()
        ->published()
        ->for($sourceQuestion)
        ->create(['external_id' => '94000']);
    QuestionSeoTopicMembership::query()->create([
        'question_public_explanation_id' => $sourceExplanation->getKey(),
        'question_seo_topic_id' => $topic->getKey(),
        'source' => QuestionRelation::SOURCE_GRAPH,
        'is_primary' => true,
        'role' => QuestionSeoTopicMembership::ROLE_PRIMARY,
        'status' => QuestionSeoTopicMembership::STATUS_VERIFIED,
        'confidence' => 1,
    ]);
    $run = QuestionRelationRankingRun::query()->create([
        'question_seo_topic_id' => $topic->getKey(),
        'input_version' => 'canary-mode-fixture-v1',
        'generator_version' => 'canary-mode-fixture-v1',
        'config_hash' => hash('sha256', 'canary-mode-fixture-v1'),
        'status' => QuestionRelationRankingRun::STATUS_VALIDATED,
        'generated_at' => now()->subMinute(),
        'validated_at' => now(),
    ]);
    $targetQuestions = [];

    foreach (range(1, 15) as $position) {
        $externalId = (string) (94000 + $position);
        $targetQuestion = Question::factory()
            ->for($targetCategory, 'licenseCategory')
            ->create([
                'external_id' => $externalId,
                'prompt' => "Wyłącznie V2 canary target {$position}",
            ]);
        $targetExplanation = QuestionPublicExplanation::factory()
            ->published()
            ->for($targetQuestion)
            ->create(['external_id' => $externalId]);
        QuestionRelationRecommendation::query()->create([
            'question_relation_ranking_run_id' => $run->getKey(),
            'source_explanation_id' => $sourceExplanation->getKey(),
            'target_explanation_id' => $targetExplanation->getKey(),
            'question_relation_id' => null,
            'scope' => $position <= 4 ? 'direct' : 'same_subtopic',
            'group_key' => $position <= 4 ? 'closest' : 'context',
            'rank' => $position,
            'score' => $position <= 4 ? 0.8 - ($position / 100) : 0,
            'score_components' => [
                'layer' => $position <= 4 ? 'direct_score' : 'hub_fallback',
                'artifact_position' => $position,
            ],
            'status' => QuestionRelationRecommendation::STATUS_SELECTED,
        ]);
        $targetQuestions[] = $targetQuestion;
    }

    if ($withShadowRollout) {
        QuestionRelationRollout::query()->create([
            'question_seo_topic_id' => $topic->getKey(),
            'mode' => QuestionRelationRollout::MODE_SHADOW,
            'active_ranking_run_id' => $run->getKey(),
            'exposure_percentage' => 0,
            'cohort_seed' => 'canary-mode-fixture-shadow',
        ]);
    }

    return compact('topic', 'run', 'sourceQuestion', 'sourceCategory', 'targetQuestions');
}

test('public V2 canary requires both switches and a deterministic cohort assignment', function () {
    config()->set('app.url', 'https://prawkonaraz.pl');
    $fixture = createQuestionRelationV2CanaryFixture(false);
    QuestionRelationRollout::query()->create([
        'question_seo_topic_id' => $fixture['topic']->getKey(),
        'mode' => QuestionRelationRollout::MODE_CANARY,
        'active_ranking_run_id' => $fixture['run']->getKey(),
        'exposure_percentage' => 100,
        'cohort_seed' => 'canary-mode-fixture-v1',
    ]);
    $service = app(PublicQuestionRelationsService::class);

    config()->set('question_relations.v2_enabled', true);
    config()->set('question_relations.v2_canary_enabled', false);
    $baseline = $service->forQuestion(
        $fixture['sourceQuestion'],
        collect([$fixture['sourceCategory']]),
    );

    expect(data_get($baseline, 'canary'))->toBeNull()
        ->and($baseline['groups']->flatMap(fn (array $group) => $group['items'])
            ->contains(fn (array $item): bool => ($item['relation_source'] ?? null) === 'v2_canary'))
        ->toBeFalse();

    config()->set('question_relations.v2_canary_enabled', true);
    $canary = $service->forQuestion(
        $fixture['sourceQuestion'],
        collect([$fixture['sourceCategory']]),
    );

    expect(data_get($canary, 'canary.mode'))->toBe(QuestionRelationRollout::MODE_CANARY)
        ->and(data_get($canary, 'canary.run_id'))->toBe($fixture['run']->getKey())
        ->and(data_get($canary, 'canary.resolved_recommendation_count'))->toBe(15)
        ->and(data_get($canary, 'canary.cohort.included'))->toBeTrue()
        ->and($canary['groups']->flatMap(fn (array $group) => $group['items'])
            ->contains(fn (array $item): bool => ($item['relation_source'] ?? null) === 'v2_canary'))
        ->toBeTrue();

    $assignment = QuestionRelationV2ShadowResolver::canaryCohortForExternalId(
        (string) $fixture['sourceQuestion']->external_id,
        (int) $fixture['topic']->getKey(),
        'canary-mode-fixture-v1',
        100,
    );

    expect($assignment)->toBe(data_get($canary, 'canary.cohort'));

    $response = $this->get(route('public.questions.show', [
        'externalId' => $fixture['sourceQuestion']->external_id,
        'slug' => Str::slug($fixture['sourceQuestion']->prompt),
    ]));

    $response
        ->assertOk()
        ->assertSeeText($fixture['targetQuestions'][0]->prompt)
        ->assertDontSeeText('Podgląd V2');
});

test('canary rollout manager previews safely, requires explicit public intent, and replaces only zero-exposure shadow', function () {
    $fixture = createQuestionRelationV2CanaryFixture();
    $manager = app(QuestionRelationCanaryRolloutManager::class);
    $seed = 'canary-mode-fixture-v1';

    config()->set('question_relations.v2_enabled', false);
    config()->set('question_relations.v2_canary_enabled', false);

    $preview = $manager->configure(
        $fixture['topic']->key,
        $fixture['run']->getKey(),
        100,
        $seed,
    );

    expect(data_get($preview, 'quality_gates.passed'))->toBeTrue()
        ->and(data_get($preview, 'write_gates.passed'))->toBeFalse()
        ->and(data_get($preview, 'cohort.included_sources'))->toBe(1)
        ->and(QuestionRelationRollout::query()->sole()->mode)->toBe(QuestionRelationRollout::MODE_SHADOW);

    $this->artisan('seo:configure-question-relation-canary', [
        '--topic-key' => $fixture['topic']->key,
        '--run' => $fixture['run']->getKey(),
        '--exposure' => 100,
        '--cohort-seed' => $seed,
    ])->assertSuccessful();

    config()->set('question_relations.v2_enabled', true);
    config()->set('question_relations.v2_canary_enabled', true);

    $unconfirmed = $manager->configure(
        $fixture['topic']->key,
        $fixture['run']->getKey(),
        100,
        $seed,
        true,
        false,
    );

    expect(data_get($unconfirmed, 'mode'))->toBe('write_blocked')
        ->and(data_get($unconfirmed, 'write_gates.blockers.public_canary_confirmation_missing'))->toBe(1)
        ->and(QuestionRelationRollout::query()->sole()->mode)->toBe(QuestionRelationRollout::MODE_SHADOW);

    $written = $manager->configure(
        $fixture['topic']->key,
        $fixture['run']->getKey(),
        100,
        $seed,
        true,
        true,
    );
    $rollout = QuestionRelationRollout::query()->sole();

    expect(data_get($written, 'mode'))->toBe('write')
        ->and(data_get($written, 'applied'))->toBeTrue()
        ->and($rollout->mode)->toBe(QuestionRelationRollout::MODE_CANARY)
        ->and($rollout->active_ranking_run_id)->toBe($fixture['run']->getKey())
        ->and($rollout->exposure_percentage)->toBe(100)
        ->and($rollout->cohort_seed)->toBe($seed)
        ->and(data_get($rollout->metadata, 'public_output'))->toBeTrue();

    $repeat = $manager->configure(
        $fixture['topic']->key,
        $fixture['run']->getKey(),
        100,
        $seed,
        true,
        true,
    );

    expect(data_get($repeat, 'mode'))->toBe('write_idempotent')
        ->and(data_get($repeat, 'applied'))->toBeFalse()
        ->and(data_get($repeat, 'applied_changes.rollouts_created_or_changed'))->toBe(0);
});

test('canary rollout plan blocks sources that no longer have a verified primary membership', function () {
    $fixture = createQuestionRelationV2CanaryFixture();
    QuestionSeoTopicMembership::query()->delete();

    $preview = app(QuestionRelationCanaryRolloutManager::class)->configure(
        $fixture['topic']->key,
        $fixture['run']->getKey(),
        100,
        'canary-membership-gate-fixture-v1',
    );

    expect(data_get($preview, 'quality_gates.passed'))->toBeFalse()
        ->and(data_get($preview, 'quality_gates.blockers.sources_without_verified_primary_membership'))->toBe(1)
        ->and(QuestionRelationRollout::query()->sole()->mode)->toBe(QuestionRelationRollout::MODE_SHADOW);
});

test('canary monitor keeps a true V1 baseline and reports the stable public cohort', function () {
    $fixture = createQuestionRelationV2CanaryFixture(false);
    QuestionRelationRollout::query()->create([
        'question_seo_topic_id' => $fixture['topic']->getKey(),
        'mode' => QuestionRelationRollout::MODE_CANARY,
        'active_ranking_run_id' => $fixture['run']->getKey(),
        'exposure_percentage' => 100,
        'cohort_seed' => 'canary-monitor-fixture-v1',
    ]);
    config()->set('question_relations.v2_enabled', true);
    config()->set('question_relations.v2_canary_enabled', true);

    $v1Baseline = app(PublicQuestionRelationsService::class)->forQuestion(
        $fixture['sourceQuestion'],
        collect([$fixture['sourceCategory']]),
        collect(),
        false,
    );

    $report = app(QuestionRelationV2CanaryMonitor::class)->inspect($fixture['topic']->key);

    expect(data_get($v1Baseline, 'canary'))->toBeNull()
        ->and($report['status'])->toBe('ok')
        ->and(data_get($report, 'scope.public_output'))->toBeTrue()
        ->and(data_get($report, 'summary.active_canary_rollouts'))->toBe(1)
        ->and(data_get($report, 'summary.errors'))->toBe(0)
        ->and(data_get($report, 'topics.0.topic.key'))->toBe($fixture['topic']->key)
        ->and(data_get($report, 'topics.0.cohort.included_sources'))->toBe(1)
        ->and(data_get($report, 'topics.0.audit.recommendations.selected'))->toBe(15)
        ->and(data_get($report, 'topics.0.audit.comparison.v2_links_total'))->toBe(15);
});

test('canary monitor fails loudly for a malformed public rollout', function () {
    $fixture = createQuestionRelationV2CanaryFixture(false);
    QuestionRelationRollout::query()->create([
        'question_seo_topic_id' => $fixture['topic']->getKey(),
        'mode' => QuestionRelationRollout::MODE_CANARY,
        'active_ranking_run_id' => $fixture['run']->getKey(),
        'exposure_percentage' => 0,
        'cohort_seed' => 'invalid-canary-monitor-fixture-v1',
    ]);

    $report = app(QuestionRelationV2CanaryMonitor::class)->inspect($fixture['topic']->key);

    expect($report['status'])->toBe('failed')
        ->and(data_get($report, 'summary.active_canary_rollouts'))->toBe(1)
        ->and(data_get($report, 'issues.errors.0.type'))->toBe('canary_exposure_out_of_range');
});

<?php

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignConfusionPair;
use App\Models\TrafficSignLearningAnswer;
use App\Models\TrafficSignLearningSession;
use App\Models\User;
use App\Models\UserTrafficSignProgress;
use App\Support\TrafficSignConfusionPairService;
use App\Support\TrafficSignLearningCorpusService;
use App\Support\TrafficSignLearningPlannerService;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

test('traffic sign learning route requires authentication', function () {
    $this->get(route('session.traffic-signs'))
        ->assertRedirect(route('login'));
});

test('traffic sign learning route requires full product access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('session.traffic-signs'))
        ->assertRedirect(route('access.activate'));
});

test('traffic sign learning route renders private learning screen for paid users', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $author = ContentAuthor::factory()->published()->create();
    $category = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki ostrzegawcze',
        'slug' => 'znaki-ostrzegawcze',
        'sort_order' => 1,
    ]);

    TrafficSign::factory()
        ->count(3)
        ->published()
        ->for($author, 'author')
        ->for($category, 'category')
        ->sequence(
            ['code' => 'A-1', 'slug' => 'a-1-niebezpieczny-zakret-w-prawo', 'sort_order' => 1],
            ['code' => 'A-2', 'slug' => 'a-2-niebezpieczny-zakret-w-lewo', 'sort_order' => 2],
            ['code' => 'A-3', 'slug' => 'a-3-niebezpieczne-zakrety', 'sort_order' => 3],
        )
        ->create();

    $this->actingAs($user)
        ->get(route('session.traffic-signs'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TrafficSignLearning/Index')
            ->where('overview.trainable_count', 3)
            ->where('overview.mastered_count', 0)
            ->where('categories.0.slug', 'znaki-ostrzegawcze')
            ->where('categories.0.total_signs', 3)
            ->has('session_preview.signs', 3)
        );
});

test('traffic sign learning route is available to the app route helper', function () {
    expect(config('ziggy.groups.app'))
        ->toContain('session.traffic-signs')
        ->toContain('traffic-sign-learning.store')
        ->toContain('traffic-sign-learning.current')
        ->toContain('traffic-sign-learning.answers.store')
        ->toContain('traffic-sign-learning.answers.sync')
        ->toContain('traffic-sign-learning.results.show');
});

test('public traffic sign catalog does not expose a learning route', function () {
    $this->get('/znaki-drogowe/nauka')
        ->assertNotFound();
});

test('traffic sign learning corpus uses only published trainable mvp signs', function () {
    $author = ContentAuthor::factory()->published()->create();
    $unpublishedAuthor = ContentAuthor::factory()->create();
    $warningCategory = TrafficSignCategory::factory()->published()->create([
        'slug' => 'znaki-ostrzegawcze',
        'sort_order' => 1,
    ]);
    $outsideMvpCategory = TrafficSignCategory::factory()->published()->create([
        'slug' => 'tabliczki-do-znakow',
        'sort_order' => 20,
    ]);
    $unpublishedCategory = TrafficSignCategory::factory()->create([
        'slug' => 'znaki-zakazu',
        'sort_order' => 2,
    ]);

    $included = TrafficSign::factory()->published()->for($author, 'author')->for($warningCategory, 'category')->create([
        'code' => 'A-1',
        'slug' => 'a-1-trainable',
        'image_path' => 'traffic-signs/a-1.svg',
        'intro_definition' => 'Ostrzega przed niebezpiecznym zakrętem.',
    ]);

    TrafficSign::factory()->published()->for($author, 'author')->for($outsideMvpCategory, 'category')->create([
        'code' => 'T-91',
        'slug' => 'outside-mvp',
    ]);
    TrafficSign::factory()->published()->for($author, 'author')->for($warningCategory, 'category')->create([
        'code' => 'A-91',
        'slug' => 'missing-image',
        'image_path' => null,
    ]);
    TrafficSign::factory()->published()->for($author, 'author')->for($warningCategory, 'category')->create([
        'code' => 'A-92',
        'slug' => 'missing-education',
        'intro_definition' => null,
        'meaning' => null,
        'driver_behavior' => null,
    ]);
    TrafficSign::factory()->published()->for($unpublishedAuthor, 'author')->for($warningCategory, 'category')->create([
        'code' => 'A-93',
        'slug' => 'unpublished-author',
    ]);
    TrafficSign::factory()->published()->for($author, 'author')->for($unpublishedCategory, 'category')->create([
        'code' => 'B-91',
        'slug' => 'unpublished-category',
    ]);

    $signs = app(TrafficSignLearningCorpusService::class)->trainableSigns();

    expect($signs)->toHaveCount(1)
        ->and($signs->first()?->is($included))->toBeTrue();
});

test('traffic sign learning planner prioritizes review and learning signs before new signs', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $author = ContentAuthor::factory()->published()->create();
    $category = TrafficSignCategory::factory()->published()->create([
        'slug' => 'znaki-ostrzegawcze',
        'sort_order' => 1,
    ]);

    $signs = collect(range(1, 14))
        ->map(fn (int $index): TrafficSign => TrafficSign::factory()
            ->published()
            ->for($author, 'author')
            ->for($category, 'category')
            ->create([
                'code' => 'A-'.$index,
                'slug' => 'a-'.$index.'-planner',
                'sort_order' => $index,
            ]));

    UserTrafficSignProgress::query()->create([
        'user_id' => $user->getKey(),
        'traffic_sign_id' => $signs[0]->getKey(),
        'state' => UserTrafficSignProgress::STATE_NEEDS_REVIEW,
        'next_review_at' => now()->subMinute(),
    ]);
    UserTrafficSignProgress::query()->create([
        'user_id' => $user->getKey(),
        'traffic_sign_id' => $signs[1]->getKey(),
        'state' => UserTrafficSignProgress::STATE_LEARNING,
        'next_review_at' => now()->subMinute(),
    ]);
    UserTrafficSignProgress::query()->create([
        'user_id' => $user->getKey(),
        'traffic_sign_id' => $signs[2]->getKey(),
        'state' => UserTrafficSignProgress::STATE_MASTERED,
    ]);

    $planned = app(TrafficSignLearningPlannerService::class)->plan($user);

    expect($planned)->toHaveCount(12)
        ->and($planned->first()?->is($signs[0]))->toBeTrue()
        ->and($planned->get(1)?->is($signs[1]))->toBeTrue()
        ->and($planned->pluck('id')->unique())->toHaveCount(12);
});

test('paid user can start a traffic sign learning session and see the first question', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    createTrafficSignLearningSigns(4);

    $this->actingAs($user)
        ->post(route('traffic-sign-learning.store'))
        ->assertRedirect(route('traffic-sign-learning.current'));

    $session = TrafficSignLearningSession::query()->sole();

    expect($session->user_id)->toBe($user->getKey())
        ->and($session->status)->toBe(TrafficSignLearningSession::STATUS_IN_PROGRESS)
        ->and($session->answers)->toHaveCount(4)
        ->and($session->answers->first()?->options)->toHaveCount(4);

    $this->actingAs($user)
        ->get(route('traffic-sign-learning.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TrafficSignLearning/Show')
            ->where('question.position', 1)
            ->where('question.total', 4)
            ->where('question.answered', false)
            ->where('question.explanation', $session->answers->first()?->trafficSign->intro_definition)
            ->has('question.options', 4)
            ->has('questions', 4)
            ->where('questions.0.position', 1)
            ->where('questions.1.position', 2)
        );
});

test('traffic sign answer stores feedback and updates isolated sign progress', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    createTrafficSignLearningSigns(4);

    $this->actingAs($user)->post(route('traffic-sign-learning.store'));

    /** @var TrafficSignLearningAnswer $answer */
    $answer = TrafficSignLearningAnswer::query()->orderBy('position')->firstOrFail();

    $this->actingAs($user)
        ->post(route('traffic-sign-learning.answers.store'), [
            'answer_id' => $answer->getKey(),
            'selected_traffic_sign_id' => $answer->traffic_sign_id,
            'response_time_ms' => 1400,
        ])
        ->assertRedirect(route('traffic-sign-learning.current', ['feedback' => $answer->getKey()]));

    $progress = UserTrafficSignProgress::query()->where('user_id', $user->getKey())->sole();

    expect($answer->fresh()->is_correct)->toBeTrue()
        ->and($progress->state)->toBe(UserTrafficSignProgress::STATE_LEARNING)
        ->and($progress->correct_count)->toBe(1)
        ->and($progress->incorrect_count)->toBe(0)
        ->and($progress->correct_streak)->toBe(1);

    $this->actingAs($user)
        ->get(route('traffic-sign-learning.current', ['feedback' => $answer->getKey()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TrafficSignLearning/Show')
            ->where('question.answered', true)
            ->where('question.feedback.is_correct', true)
        );
});

test('traffic sign answer can return immediate json feedback without inertia redirect', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    createTrafficSignLearningSigns(4);

    $this->actingAs($user)->post(route('traffic-sign-learning.store'));

    /** @var TrafficSignLearningAnswer $answer */
    $answer = TrafficSignLearningAnswer::query()->orderBy('position')->firstOrFail();

    $this->actingAs($user)
        ->postJson(route('traffic-sign-learning.answers.store'), [
            'answer_id' => $answer->getKey(),
            'selected_traffic_sign_id' => $answer->traffic_sign_id,
            'response_time_ms' => 900,
        ])
        ->assertOk()
        ->assertJsonPath('completed', false)
        ->assertJsonPath('feedback.answered', true)
        ->assertJsonPath('feedback.has_more', true)
        ->assertJsonPath('feedback.feedback.is_correct', true)
        ->assertJsonPath('feedback.feedback.correct_traffic_sign_id', $answer->traffic_sign_id)
        ->assertJsonPath('feedback.next_url', route('traffic-sign-learning.current', absolute: false));
});

test('traffic sign current question can be fetched as json for preloading next step', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    createTrafficSignLearningSigns(4);

    $this->actingAs($user)->post(route('traffic-sign-learning.store'));

    /** @var TrafficSignLearningAnswer $answer */
    $answer = TrafficSignLearningAnswer::query()->orderBy('position')->firstOrFail();

    $this->actingAs($user)
        ->postJson(route('traffic-sign-learning.answers.store'), [
            'answer_id' => $answer->getKey(),
            'selected_traffic_sign_id' => $answer->traffic_sign_id,
        ])
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('traffic-sign-learning.current'))
        ->assertOk()
        ->assertJsonPath('session.status', TrafficSignLearningSession::STATUS_IN_PROGRESS)
        ->assertJsonPath('question.position', 2)
        ->assertJsonPath('question.answered', false)
        ->assertJsonCount(4, 'questions')
        ->assertJsonCount(4, 'question.options');
});

test('traffic sign answers can be synced in one background batch', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    createTrafficSignLearningSigns(4);

    $this->actingAs($user)->post(route('traffic-sign-learning.store'));

    $answers = TrafficSignLearningAnswer::query()
        ->orderBy('position')
        ->take(2)
        ->get();

    $this->actingAs($user)
        ->postJson(route('traffic-sign-learning.answers.sync'), [
            'answers' => $answers
                ->map(fn (TrafficSignLearningAnswer $answer): array => [
                    'answer_id' => $answer->getKey(),
                    'selected_traffic_sign_id' => $answer->traffic_sign_id,
                    'response_time_ms' => 700,
                ])
                ->values()
                ->all(),
        ])
        ->assertOk()
        ->assertJsonPath('completed', false)
        ->assertJsonCount(2, 'synced_answer_ids')
        ->assertJsonPath('session.status', TrafficSignLearningSession::STATUS_IN_PROGRESS);

    expect(TrafficSignLearningAnswer::query()->whereNotNull('answered_at')->count())->toBe(2)
        ->and(UserTrafficSignProgress::query()->where('user_id', $user->getKey())->count())->toBe(2);

    $this->actingAs($user)
        ->getJson(route('traffic-sign-learning.current'))
        ->assertOk()
        ->assertJsonPath('question.position', 3);
});

test('traffic sign answer sync completes a session and keeps retried batches idempotent', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    createTrafficSignLearningSigns(4);

    $this->actingAs($user)->post(route('traffic-sign-learning.store'));

    $answers = TrafficSignLearningAnswer::query()
        ->orderBy('position')
        ->get();

    $payload = $answers
        ->map(function (TrafficSignLearningAnswer $answer, int $index): array {
            $selectedTrafficSignId = $answer->traffic_sign_id;

            if ($index === 0) {
                $selectedTrafficSignId = collect($answer->options)
                    ->pluck('traffic_sign_id')
                    ->first(fn (int $trafficSignId): bool => $trafficSignId !== $answer->traffic_sign_id);
            }

            return [
                'answer_id' => $answer->getKey(),
                'selected_traffic_sign_id' => $selectedTrafficSignId,
                'response_time_ms' => 800,
            ];
        })
        ->values()
        ->all();

    $this->actingAs($user)
        ->postJson(route('traffic-sign-learning.answers.sync'), [
            'answers' => $payload,
        ])
        ->assertOk()
        ->assertJsonPath('completed', true)
        ->assertJsonPath('session.status', TrafficSignLearningSession::STATUS_COMPLETED)
        ->assertJsonPath('redirect_url', route('traffic-sign-learning.results.show', TrafficSignLearningSession::query()->sole(), absolute: false));

    $session = TrafficSignLearningSession::query()->sole();

    expect($session->fresh()->correct_answers_count)->toBe(3)
        ->and(TrafficSignLearningAnswer::query()->whereNotNull('answered_at')->count())->toBe(4)
        ->and(UserTrafficSignProgress::query()->where('user_id', $user->getKey())->sum('attempts_count'))->toBe(4);

    $this->actingAs($user)
        ->postJson(route('traffic-sign-learning.answers.sync'), [
            'answers' => $payload,
        ])
        ->assertOk()
        ->assertJsonPath('completed', true)
        ->assertJsonCount(4, 'synced_answer_ids');

    expect(UserTrafficSignProgress::query()->where('user_id', $user->getKey())->sum('attempts_count'))->toBe(4);
});

test('wrong traffic sign answer marks sign as needs review and stores confusion target', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    createTrafficSignLearningSigns(4);

    $this->actingAs($user)->post(route('traffic-sign-learning.store'));

    /** @var TrafficSignLearningAnswer $answer */
    $answer = TrafficSignLearningAnswer::query()->orderBy('position')->firstOrFail();
    $wrongOptionId = collect($answer->options)
        ->pluck('traffic_sign_id')
        ->first(fn (int $trafficSignId): bool => $trafficSignId !== $answer->traffic_sign_id);

    $this->actingAs($user)
        ->post(route('traffic-sign-learning.answers.store'), [
            'answer_id' => $answer->getKey(),
            'selected_traffic_sign_id' => $wrongOptionId,
        ])
        ->assertRedirect(route('traffic-sign-learning.current', ['feedback' => $answer->getKey()]));

    $progress = UserTrafficSignProgress::query()->where('user_id', $user->getKey())->sole();

    expect($answer->fresh()->is_correct)->toBeFalse()
        ->and($progress->state)->toBe(UserTrafficSignProgress::STATE_NEEDS_REVIEW)
        ->and($progress->last_confused_with_traffic_sign_id)->toBe($wrongOptionId)
        ->and($progress->correct_streak)->toBe(0);
});

test('traffic sign confusion service derives pairs from supporting pages', function () {
    [$yieldSign, $stopSign] = createTrafficSignConfusionFixture();

    $confusingSigns = app(TrafficSignConfusionPairService::class)->confusingSignsFor($yieldSign);

    expect($confusingSigns->pluck('id')->all())
        ->toContain($stopSign->getKey());
});

test('paid user can start a similar traffic signs session with confusing options', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    [$yieldSign, $stopSign] = createTrafficSignConfusionFixture();

    $this->actingAs($user)
        ->post(route('traffic-sign-learning.store'), [
            'mode' => TrafficSignLearningSession::MODE_SIMILAR_SIGNS,
        ])
        ->assertRedirect(route('traffic-sign-learning.current'));

    $session = TrafficSignLearningSession::query()->sole();
    /** @var TrafficSignLearningAnswer $firstAnswer */
    $firstAnswer = $session->answers()->orderBy('position')->firstOrFail();

    expect($session->mode)->toBe(TrafficSignLearningSession::MODE_SIMILAR_SIGNS)
        ->and($firstAnswer->traffic_sign_id)->toBe($yieldSign->getKey())
        ->and(collect($firstAnswer->options)->pluck('traffic_sign_id')->all())->toContain($stopSign->getKey());
});

test('paid user can start a description to sign session with visual options', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    createTrafficSignLearningSigns(4);

    $this->actingAs($user)
        ->post(route('traffic-sign-learning.store'), [
            'mode' => TrafficSignLearningSession::MODE_DESCRIPTION_TO_SIGN,
        ])
        ->assertRedirect(route('traffic-sign-learning.current'));

    $session = TrafficSignLearningSession::query()->sole();
    /** @var TrafficSignLearningAnswer $firstAnswer */
    $firstAnswer = $session->answers()->orderBy('position')->firstOrFail();

    expect($session->mode)->toBe(TrafficSignLearningSession::MODE_DESCRIPTION_TO_SIGN)
        ->and($firstAnswer->answer_mode)->toBe(TrafficSignLearningAnswer::MODE_MEANING_TO_SIGN)
        ->and($firstAnswer->options)->toHaveCount(4);

    $this->actingAs($user)
        ->get(route('traffic-sign-learning.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TrafficSignLearning/Show')
            ->where('question.mode', TrafficSignLearningSession::MODE_DESCRIPTION_TO_SIGN)
            ->where('question.answer_mode', TrafficSignLearningAnswer::MODE_MEANING_TO_SIGN)
            ->where('question.prompt', $firstAnswer->trafficSign->meaning)
            ->has('question.options', 4)
            ->where('question.options.0.image_url', fn (mixed $url): bool => is_string($url) && $url !== '')
        );
});

test('traffic sign confusion sync command materializes supporting page pairs', function () {
    [$yieldSign, $stopSign] = createTrafficSignConfusionFixture();

    $this->artisan('traffic-signs:sync-confusion-pairs')
        ->assertSuccessful();

    expect(TrafficSignConfusionPair::query()
        ->where('traffic_sign_id', $yieldSign->getKey())
        ->where('confusing_traffic_sign_id', $stopSign->getKey())
        ->where('source', TrafficSignConfusionPair::SOURCE_SUPPORTING_PAGE)
        ->exists())->toBeTrue();
});

test('traffic sign learning session completes and renders result', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    createTrafficSignLearningSigns(4);

    $this->actingAs($user)->post(route('traffic-sign-learning.store'));

    TrafficSignLearningAnswer::query()
        ->orderBy('position')
        ->get()
        ->each(function (TrafficSignLearningAnswer $answer) use ($user): void {
            $this->actingAs($user)
                ->post(route('traffic-sign-learning.answers.store'), [
                    'answer_id' => $answer->getKey(),
                    'selected_traffic_sign_id' => $answer->traffic_sign_id,
                ]);
        });

    $session = TrafficSignLearningSession::query()->sole();

    expect($session->fresh()->status)->toBe(TrafficSignLearningSession::STATUS_COMPLETED)
        ->and($session->fresh()->correct_answers_count)->toBe(4);

    $this->actingAs($user)
        ->get(route('traffic-sign-learning.results.show', $session))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TrafficSignLearning/Result')
            ->where('result.total_signs_count', 4)
            ->where('result.correct_answers_count', 4)
            ->where('result.confusions', [])
            ->has('result.answers', 4)
        );
});

test('traffic sign learning can start a session for a selected category only', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    createTrafficSignLearningSigns(3, 'znaki-ostrzegawcze', 'Znaki ostrzegawcze', 'A');
    createTrafficSignLearningSigns(5, 'znaki-informacyjne', 'Znaki informacyjne', 'D');

    $this->actingAs($user)
        ->post(route('traffic-sign-learning.store'), [
            'category_slug' => 'znaki-ostrzegawcze',
        ])
        ->assertRedirect(route('traffic-sign-learning.current'));

    $session = TrafficSignLearningSession::query()->sole();
    $session->load('answers.trafficSign.category');

    expect($session->answers)->toHaveCount(3)
        ->and($session->payload['category_slugs'])->toBe(['znaki-ostrzegawcze'])
        ->and($session->answers->every(
            fn (TrafficSignLearningAnswer $answer): bool => $answer->trafficSign->category->slug === 'znaki-ostrzegawcze',
        ))->toBeTrue()
        ->and($session->answers->first()?->options)->toHaveCount(4);
});

test('traffic sign learning category summaries expose progress states per category', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $warningSigns = createTrafficSignLearningSigns(3, 'znaki-ostrzegawcze', 'Znaki ostrzegawcze', 'A');
    createTrafficSignLearningSigns(2, 'znaki-informacyjne', 'Znaki informacyjne', 'D');

    UserTrafficSignProgress::query()->create([
        'user_id' => $user->getKey(),
        'traffic_sign_id' => $warningSigns[0]->getKey(),
        'state' => UserTrafficSignProgress::STATE_MASTERED,
        'correct_streak' => 3,
    ]);
    UserTrafficSignProgress::query()->create([
        'user_id' => $user->getKey(),
        'traffic_sign_id' => $warningSigns[1]->getKey(),
        'state' => UserTrafficSignProgress::STATE_LEARNING,
        'correct_streak' => 1,
    ]);
    UserTrafficSignProgress::query()->create([
        'user_id' => $user->getKey(),
        'traffic_sign_id' => $warningSigns[2]->getKey(),
        'state' => UserTrafficSignProgress::STATE_NEEDS_REVIEW,
        'correct_streak' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('session.traffic-signs'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TrafficSignLearning/Index')
            ->where('categories.0.slug', 'znaki-ostrzegawcze')
            ->where('categories.0.total_signs', 3)
            ->where('categories.0.mastered_signs', 1)
            ->where('categories.0.learning_signs', 1)
            ->where('categories.0.needs_review_signs', 1)
            ->where('categories.0.new_signs', 0)
            ->where('categories.1.slug', 'znaki-informacyjne')
            ->where('categories.1.new_signs', 2)
        );
});

function createTrafficSignLearningSigns(
    int $count,
    string $categorySlug = 'znaki-ostrzegawcze',
    string $categoryName = 'Znaki ostrzegawcze',
    string $codePrefix = 'A',
): Collection {
    $author = ContentAuthor::factory()->published()->create();
    $category = TrafficSignCategory::factory()->published()->create([
        'name' => $categoryName,
        'slug' => $categorySlug,
        'sort_order' => 1,
    ]);

    return collect(range(1, $count))
        ->map(fn (int $index): TrafficSign => TrafficSign::factory()
            ->published()
            ->for($author, 'author')
            ->for($category, 'category')
            ->create([
                'code' => $codePrefix.'-'.$index,
                'slug' => strtolower($codePrefix).'-'.$index.'-learning-flow',
                'name' => 'Znak testowy '.$index,
                'sort_order' => $index,
                'intro_definition' => 'Opis znaku testowego '.$index.'.',
            ]));
}

function createTrafficSignConfusionFixture(): array
{
    $author = ContentAuthor::factory()->published()->create();
    $warningCategory = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki ostrzegawcze',
        'slug' => 'znaki-ostrzegawcze',
        'sort_order' => 1,
    ]);
    $prohibitionCategory = TrafficSignCategory::factory()->published()->create([
        'name' => 'Znaki zakazu',
        'slug' => 'znaki-zakazu',
        'sort_order' => 2,
    ]);

    $yieldSign = TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($warningCategory, 'category')
        ->create([
            'code' => 'A-7',
            'slug' => 'a-7-ustap-pierwszenstwa',
            'name' => 'Ustąp pierwszeństwa',
            'sort_order' => 7,
            'intro_definition' => 'Ostrzega o skrzyżowaniu z drogą z pierwszeństwem.',
        ]);

    $stopSign = TrafficSign::factory()
        ->published()
        ->for($author, 'author')
        ->for($prohibitionCategory, 'category')
        ->create([
            'code' => 'B-20',
            'slug' => 'b-20-stop',
            'name' => 'Stop',
            'sort_order' => 20,
            'intro_definition' => 'Nakazuje zatrzymanie przed wjazdem na skrzyżowanie.',
        ]);

    createTrafficSignLearningSigns(2, 'znaki-nakazu', 'Znaki nakazu', 'C');

    return [$yieldSign, $stopSign];
}

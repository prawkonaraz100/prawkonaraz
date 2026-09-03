<?php

use App\Models\LicenseCategory;
use App\Models\ProductAccessGrant;
use App\Models\ProductPlan;
use App\Models\PurchaseOrder;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryHero;
use App\Models\QuestionTopicCategoryLabel;
use App\Models\RankedPlayerRating;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserQuestionProgress;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Inertia\Testing\AssertableInertia as Assert;

test('session page shows topic groups for the users selected category', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $warningTopic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $rightOfWayTopic = QuestionTopic::query()->create([
        'key' => 'signals_and_right_of_way',
        'name' => 'Sygnaly, pierwszenstwo i skrzyzowania',
        'sort_order' => 50,
    ]);

    QuestionTopicCategoryLabel::factory()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $warningTopic->getKey(),
        'display_name' => 'Ostrzegawcze dla kategorii B',
    ]);

    QuestionTopicCategoryHero::factory()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $warningTopic->getKey(),
        'hero_image_path' => 'study/topic-heroes/b/warning-signs.webp',
        'hero_image_alt' => 'Znaki ostrzegawcze dla kategorii B',
        'hero_image_position' => 'right center',
    ]);

    Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $warningTopic->getKey(),
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $rightOfWayTopic->getKey(),
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('category.code', 'B')
            ->where('filters.question_scope', 'all')
            ->where('filters.question_status', 'all')
            ->where('filters.question_topic_id', $warningTopic->getKey())
            ->where('filters.question_count', 2)
            ->has('group_options', 2)
            ->where('group_options.0.label', 'Pytania podstawowe')
            ->where('group_options.0.options.0.label', 'Ostrzegawcze dla kategorii B')
            ->where('group_options.0.options.0.questions_count', 2)
            ->where('group_options.0.options.0.hero_image_alt', 'Znaki ostrzegawcze dla kategorii B')
            ->where('group_options.0.options.0.hero_image_position', 'right center')
            ->where('group_options.0.options.0.hero_image_source', 'category')
            ->where('group_options.1.label', 'Grupy pytan')
            ->where('group_options.1.options.0.label', 'Sygnaly, pierwszenstwo i skrzyzowania')
            ->where('group_options.1.options.0.questions_count', 1)
            ->where('status_options.0.value', 'all')
        );
});

test('session page defaults to the latest completed learning topic and shell', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $firstTopic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);
    $latestTopic = QuestionTopic::query()->create([
        'key' => 'road_markings',
        'name' => 'Znaki poziome',
        'sort_order' => 20,
    ]);

    Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->create(['question_topic_id' => $firstTopic->getKey()]);
    Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->create(['question_topic_id' => $latestTopic->getKey()]);

    StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => 'learn',
            'status' => 'completed',
            'started_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(25),
            'payload' => [
                'ui_shell' => 'exam_like',
                'filters' => [
                    'question_topic_id' => $firstTopic->getKey(),
                    'question_scope' => 'all',
                    'question_status' => 'all',
                    'randomize_order' => false,
                    'question_count' => 2,
                ],
            ],
        ]);

    StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => 'learn',
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(5),
            'payload' => [
                'ui_shell' => 'zen',
                'filters' => [
                    'question_topic_id' => $latestTopic->getKey(),
                    'question_scope' => 'all',
                    'question_status' => 'all',
                    'randomize_order' => false,
                    'question_count' => 3,
                ],
            ],
        ]);

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('filters.question_topic_id', $latestTopic->getKey())
            ->where('filters.ui_shell', 'zen')
            ->where('filters.question_count', 3)
        );
});

test('session page exposes a lightweight learning dashboard without an active session', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('learning_dashboard.schema_version', 1)
            ->where('learning_dashboard.active_session', null)
            ->where('learning_dashboard.course_progress.category_id', $category->getKey())
            ->where('learning_dashboard.course_progress.total_questions', 2)
            ->where('learning_dashboard.course_progress.answered_questions', 0)
            ->where('learning_dashboard.course_progress.unanswered_questions', 2)
            ->where('learning_dashboard.course_progress.percent', 0)
            ->where('learning_dashboard.review.due_count', fn ($count) => is_int($count) && $count >= 0)
            ->where('learning_dashboard.weekly_activity', null)
            ->where('learning_dashboard.study_time', null)
            ->where('learning_dashboard.recent_learning_activity', null)
            ->where('learning_dashboard.access_ui.can_show_pricing_link', true)
            ->where('learning_dashboard.access_ui.purchase_mode', 'web')
        );
});

test('session page exposes active session summary for mobile resume', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $studySession = StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->inProgress()
        ->create([
            'mode' => 'learn',
            'started_at' => now()->subMinutes(8),
            'total_questions_count' => 3,
            'payload' => [
                'question_ids' => $questions->pluck('id')->all(),
                'current_index' => 1,
                'answered_count' => 1,
                'ui_shell' => 'zen',
                'filters' => [
                    'question_topic_id' => $topic->getKey(),
                    'question_scope' => 'all',
                    'question_status' => 'unanswered',
                    'randomize_order' => false,
                    'question_count' => 3,
                    'question_count_strategy' => 'fixed',
                ],
            ],
        ]);

    StudySessionAnswer::factory()
        ->for($studySession, 'studySession')
        ->for($questions->first())
        ->create([
            'selected_answer' => 'a',
            'is_correct' => true,
        ]);

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('learning_dashboard.active_session.id', $studySession->getKey())
            ->where('learning_dashboard.active_session.mode', 'learn')
            ->where('learning_dashboard.active_session.ui_shell', 'zen')
            ->where('learning_dashboard.active_session.title', 'Zen mode')
            ->where('learning_dashboard.active_session.subtitle', 'Kategoria B')
            ->where('learning_dashboard.active_session.category.id', $category->getKey())
            ->where('learning_dashboard.active_session.category.short_name', 'B')
            ->where('learning_dashboard.active_session.progress.answered', 1)
            ->where('learning_dashboard.active_session.progress.remaining', 2)
            ->where('learning_dashboard.active_session.progress.total', 3)
            ->where('learning_dashboard.active_session.progress.percent', 33)
            ->where('learning_dashboard.active_session.progress.current_question_number', 2)
            ->where('learning_dashboard.active_session.filters.question_topic_id', $topic->getKey())
            ->where('learning_dashboard.active_session.filters.question_status', 'unanswered')
            ->where('learning_dashboard.active_session.resume_url', '/nauka/teraz')
            ->where('learning_dashboard.active_session.can_replace', true)
        );
});

test('session page explicit topic query overrides the latest completed learning topic', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $requestedTopic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);
    $latestTopic = QuestionTopic::query()->create([
        'key' => 'road_markings',
        'name' => 'Znaki poziome',
        'sort_order' => 20,
    ]);

    Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->create(['question_topic_id' => $requestedTopic->getKey()]);
    Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->create(['question_topic_id' => $latestTopic->getKey()]);

    StudySession::factory()
        ->for($user)
        ->for($category, 'licenseCategory')
        ->create([
            'mode' => 'learn',
            'status' => 'completed',
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(5),
            'payload' => [
                'ui_shell' => 'zen',
                'filters' => [
                    'question_topic_id' => $latestTopic->getKey(),
                    'question_scope' => 'all',
                    'question_status' => 'all',
                    'randomize_order' => false,
                    'question_count' => 3,
                ],
            ],
        ]);

    $this->actingAs($user)
        ->get(route('session.index', ['question_topic_id' => $requestedTopic->getKey()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('filters.question_topic_id', $requestedTopic->getKey())
            ->where('filters.ui_shell', 'zen')
            ->where('filters.question_count', 2)
        );
});

test('session page exposes invite friend cta for eligible owners', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $plan = ProductPlan::query()->where('code', 'start-90')->firstOrFail();
    $order = PurchaseOrder::factory()
        ->paid()
        ->create([
            'user_id' => $user->getKey(),
            'product_plan_id' => $plan->getKey(),
            'amount_gross_cents' => $plan->price_gross_cents,
            'currency' => $plan->currency,
            'access_days' => $plan->access_days,
        ]);

    ProductAccessGrant::query()->create([
        'user_id' => $user->getKey(),
        'source' => ProductAccessGrant::SOURCE_PURCHASE,
        'status' => ProductAccessGrant::STATUS_ACTIVE,
        'starts_at' => now()->subMinute(),
        'expires_at' => now()->addDays(80),
        'purchase_order_id' => $order->getKey(),
        'notes' => 'Dostęp testowy.',
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('friend_invitation_cta.visible', true)
            ->where('friend_invitation_cta.can_issue', true)
            ->where('friend_invitation_cta.pending_count', 0)
            ->where('friend_invitation_cta.profile_url', route('profile.edit', absolute: false).'#zapros-znajomego')
        );
});

test('ranking mode page renders with the users preferred category and transport summary', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 10,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $leaderUserOne = User::factory()->withPurchasedAccess()->create([
        'name' => 'Ranking One',
    ]);
    $leaderUserTwo = User::factory()->withPurchasedAccess()->create([
        'name' => 'Ranking Two',
    ]);

    RankedPlayerRating::query()->create([
        'user_id' => $user->getKey(),
        'rating' => 1490,
        'peak_rating' => 1490,
        'matches_played' => 3,
        'wins' => 2,
        'losses' => 1,
        'draws' => 0,
        'current_streak' => 1,
        'best_streak' => 2,
    ]);

    RankedPlayerRating::query()->create([
        'user_id' => $leaderUserOne->getKey(),
        'rating' => 1685,
        'peak_rating' => 1700,
        'matches_played' => 18,
        'wins' => 13,
        'losses' => 4,
        'draws' => 1,
        'current_streak' => 4,
        'best_streak' => 6,
    ]);

    RankedPlayerRating::query()->create([
        'user_id' => $leaderUserTwo->getKey(),
        'rating' => 1612,
        'peak_rating' => 1644,
        'matches_played' => 10,
        'wins' => 6,
        'losses' => 3,
        'draws' => 1,
        'current_streak' => 2,
        'best_streak' => 3,
    ]);

    $this->actingAs($user)
        ->get(route('session.ranking'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Ranking')
            ->where('screen', 'lobby')
            ->where('selectedCategory.code', 'B')
            ->where('specSummary.api_version', '1.0')
            ->where('specSummary.matchmaking_timeout_seconds', 90)
            ->where('specSummary.match_duration_seconds', 90)
            ->where('specSummary.max_concurrent_players', 100)
            ->where('specSummary.queue_channel_pattern', 'queue:{user_id}')
            ->where('specSummary.match_channel_pattern', 'match:{match_id}')
            ->where('realtimeTransport.preferred', 'sse')
            ->where('realtimeTransport.websocket.enabled', false)
            ->where('realtimeTransport.sse.enabled', true)
            ->has('leaderboard', 2)
            ->where('leaderboard.0.username', 'Ranking One')
            ->where('leaderboard.0.rating', 1685)
            ->where('leaderboard.0.position', 1)
            ->where('leaderboard.1.username', 'Ranking Two')
            ->where('leaderboard.1.position', 2)
        );
});

test('ranking mode waiting, match and result routes render dedicated screens', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 10,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs_ranked_screens',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $this->actingAs($user)
        ->get(route('session.ranking.waiting'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Ranking')
            ->where('screen', 'waiting')
            ->where('selectedCategory.code', 'B')
        );

    $this->actingAs($user)
        ->get(route('session.ranking.match'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Ranking')
            ->where('screen', 'match')
            ->where('selectedCategory.code', 'B')
        );

    $this->actingAs($user)
        ->get(route('session.ranking.result'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Ranking')
            ->where('screen', 'result')
            ->where('selectedCategory.code', 'B')
        );
});

test('ranking mode lobby can lock automatic return from the match view', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 10,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs_ranked_lobby_lock',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $this->actingAs($user)
        ->get(route('session.ranking', ['stay' => 'lobby']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Ranking')
            ->where('screen', 'lobby')
            ->where('navigationLock', 'lobby')
            ->where('selectedCategory.code', 'B')
        );
});

test('ranking mode page prefers websocket when ranked websocket transport is configured', function () {
    config()->set('ranked.websocket_enabled', true);
    config()->set('ranked.websocket_url', 'ws://localhost:8080/ranked');
    config()->set('ranked.websocket_ticket_store', 'array');

    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 10,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $this->actingAs($user)
        ->get(route('session.ranking'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Ranking')
            ->where('realtimeTransport.preferred', 'websocket')
            ->where('realtimeTransport.websocket.enabled', true)
            ->where('realtimeTransport.websocket.url', fn (?string $url) => is_string($url)
                && str_starts_with($url, 'ws://localhost:8080/ranked?ticket=')
                && strlen((string) parse_url($url, PHP_URL_QUERY)) > 7)
            ->where('realtimeTransport.websocket.fallback', 'sse')
        );
});

test('ranking mode page keeps category B even when another category is requested', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $categoryB = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 10,
    ]);
    $categoryC = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
        'sort_order' => 20,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $categoryB->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    Question::factory()
        ->for($categoryC, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $this->actingAs($user)
        ->get(route('session.ranking', ['category' => $categoryC->getKey()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Ranking')
            ->where('selectedCategory.code', 'B')
            ->where('selectedCategory.short_name', 'B')
        );
});

test('ranking mode page ignores stale category query values in the url', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $categoryB = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 10,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $categoryB->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs_ranked_stale_category',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $this->actingAs($user)
        ->get('/nauka/ranking?category=999999')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Ranking')
            ->where('selectedCategory.code', 'B')
            ->where('selectedCategory.short_name', 'B')
        );
});

test('session page study context hides non-official categories even when they have ready questions', function () {
    $user = User::factory()->testAccount()->create();
    $officialCategories = collect([
        ['code' => 'A', 'name' => 'Kategoria A', 'sort_order' => 10],
        ['code' => 'B', 'name' => 'Kategoria B', 'sort_order' => 20],
        ['code' => 'C', 'name' => 'Kategoria C', 'sort_order' => 30],
        ['code' => 'D', 'name' => 'Kategoria D', 'sort_order' => 40],
        ['code' => 'T', 'name' => 'Kategoria T', 'sort_order' => 50],
    ])->map(fn (array $attributes) => LicenseCategory::factory()->create($attributes));

    $preferredCategory = $officialCategories->firstWhere('code', 'B');

    $bogusCategory = LicenseCategory::factory()->create([
        'code' => 'X',
        'name' => 'Kategoria X',
        'sort_order' => 5,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $preferredCategory->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $officialCategories->each(fn (LicenseCategory $category) => Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]));

    Question::factory()
        ->for($bogusCategory, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('studyContext.categories', 5)
            ->where('studyContext.categories', fn ($categories) => collect($categories)->pluck('code')->values()->all() === ['A', 'B', 'C', 'D', 'T'])
            ->where('category.code', 'B')
        );
});

test('session page keeps all official categories visible for system accounts even when the preferred category is B', function () {
    $user = User::factory()->testAccount()->create();
    $officialCategory = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 10,
    ]);
    $otherOfficialCategory = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
        'sort_order' => 20,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $officialCategory->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->for($officialCategory, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    Question::factory()
        ->for($otherOfficialCategory, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('studyContext.categories', 2)
            ->where('studyContext.categories', fn ($categories) => collect($categories)->pluck('code')->values()->all() === ['B', 'C'])
            ->where('studyContext.categories.0.short_name', 'B')
            ->where('category.code', 'B')
            ->where('category.short_name', 'B')
        );
});

test('session page filter includes additional official categories and hides PT from the public picker', function () {
    $user = User::factory()->testAccount()->create();
    $categories = collect([
        ['code' => 'PT', 'name' => 'Kategoria PT', 'sort_order' => 10],
        ['code' => 'B', 'name' => 'Kategoria B', 'sort_order' => 20],
        ['code' => 'AM', 'name' => 'Kategoria AM', 'sort_order' => 30],
    ])->map(fn (array $attributes) => LicenseCategory::factory()->create($attributes));

    $preferredCategory = $categories->firstWhere('code', 'B');

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $preferredCategory->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $categories->each(fn (LicenseCategory $category) => Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]));

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('studyContext.categories', 2)
            ->where('studyContext.categories', fn ($items) => collect($items)->pluck('code')->values()->all() === ['AM', 'B'])
            ->where('studyContext.categories.0.short_name', 'AM')
            ->where('studyContext.categories.1.short_name', 'B')
            ->where('category.code', 'B')
            ->where('category.short_name', 'B')
        );
});

test('session page orders official categories by fixed public sequence instead of database sort order', function () {
    $user = User::factory()->testAccount()->create();
    $categories = collect([
        ['code' => 'B', 'name' => 'Kategoria B', 'sort_order' => 10],
        ['code' => 'PT', 'name' => 'Kategoria PT', 'sort_order' => 20],
        ['code' => 'AM', 'name' => 'Kategoria AM', 'sort_order' => 30],
        ['code' => 'A', 'name' => 'Kategoria A', 'sort_order' => 40],
        ['code' => 'D1', 'name' => 'Kategoria D1', 'sort_order' => 50],
    ])->map(fn (array $attributes) => LicenseCategory::factory()->create($attributes));

    $preferredCategory = $categories->firstWhere('code', 'B');

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $preferredCategory->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $categories->each(fn (LicenseCategory $category) => Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]));

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('studyContext.categories', 4)
            ->where('studyContext.categories', fn ($items) => collect($items)->pluck('code')->values()->all() === ['AM', 'A', 'B', 'D1'])
            ->where('studyContext.categories.0.short_name', 'AM')
            ->where('studyContext.categories.1.short_name', 'A')
            ->where('studyContext.categories.2.short_name', 'B')
            ->where('studyContext.categories.3.short_name', 'D1')
        );
});

test('session page does not share memory trainer due count in study context', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 10,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $dueQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $futureQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($dueQuestion, 'question')
        ->dueToday()
        ->create();

    UserQuestionProgress::factory()
        ->for($user)
        ->for($futureQuestion, 'question')
        ->scheduledInFuture()
        ->create();

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('studyContext.reviewDueCount')
            ->where('category.code', 'B')
        );
});

test('session page study context respects locked target category without global memory count', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $targetCategory = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    $otherCategory = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
        'sort_order' => 2,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $targetCategory->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'review_due_context_filter',
        'name' => 'Review due context filter',
        'sort_order' => 10,
    ]);

    $targetQuestion = Question::factory()
        ->for($targetCategory, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);
    $otherQuestion = Question::factory()
        ->for($otherCategory, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($targetQuestion, 'question')
        ->dueToday()
        ->create();
    UserQuestionProgress::factory()
        ->for($user)
        ->for($otherQuestion, 'question')
        ->dueToday()
        ->create();

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('studyContext.reviewDueCount')
            ->where('studyContext.categories.0.id', $targetCategory->getKey())
            ->missing('studyContext.categories.1')
            ->where('category.code', 'B')
        );
});

test('session page shares visual explanations mode from the user profile', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
        'sort_order' => 10,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
            'visual_explanations_mode' => 'before_answer',
            'visual_explanations_enabled' => true,
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('studyContext.visualExplanationsMode', 'before_answer')
            ->where('studyContext.visualExplanationsEnabled', true)
            ->where('category.code', 'C')
        );
});

test('session start builds a full exam from the selected category and ignores topic filters', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $selectedTopic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $otherTopic = QuestionTopic::query()->create([
        'key' => 'signals_and_right_of_way',
        'name' => 'Sygnaly, pierwszenstwo i skrzyzowania',
        'sort_order' => 50,
    ]);

    $basicSelectedTopicQuestions = Question::factory()
        ->count(12)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $selectedTopic->getKey(),
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    $basicOtherTopicQuestions = Question::factory()
        ->count(8)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $otherTopic->getKey(),
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    $specialistSelectedTopicQuestions = Question::factory()
        ->count(5)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $selectedTopic->getKey(),
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $specialistOtherTopicQuestions = Question::factory()
        ->count(7)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $otherTopic->getKey(),
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    UserQuestionProgress::factory()
        ->count(3)
        ->for($user)
        ->state(new Sequence(
            fn ($sequence) => ['question_id' => $basicSelectedTopicQuestions[$sequence->index]->getKey()],
        ))
        ->create([
            'total_attempts' => 1,
            'correct_count' => 1,
            'incorrect_count' => 0,
            'correct_streak' => 1,
            'last_quality' => 3,
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'exam',
            'ui_shell' => 'exam',
            'question_count' => 10,
            'question_topic_id' => $selectedTopic->getKey(),
            'question_status' => 'unanswered',
            'randomize_order' => true,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();
    $allExamQuestionIds = $basicSelectedTopicQuestions
        ->concat($basicOtherTopicQuestions)
        ->concat($specialistSelectedTopicQuestions)
        ->concat($specialistOtherTopicQuestions)
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    expect($studySession->mode)->toBe('exam');
    expect($studySession->total_questions_count)->toBe(32);
    expect(collect($studySession->questionIds())->sort()->values()->all())
        ->toEqual($allExamQuestionIds);
    expect(data_get($studySession->payload, 'filters.question_topic_id'))->toBeNull();
    expect(data_get($studySession->payload, 'filters.question_scope'))->toBe('all');
    expect(data_get($studySession->payload, 'filters.question_status'))->toBe('all');
    expect(data_get($studySession->payload, 'filters.randomize_order'))->toBeFalse();
    expect(data_get($studySession->payload, 'filters.question_count'))->toBe(32);
    expect(data_get($studySession->payload, 'ui_shell'))->toBe('exam');
});

test('learn mode persists exam like shell without switching to exam mechanics', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'ui_shell' => 'exam_like',
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'all',
            'randomize_order' => false,
            'question_count' => 3,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    expect($studySession->mode)->toBe('learn');
    expect(data_get($studySession->payload, 'ui_shell'))->toBe('exam_like');

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('session.mode', 'learn')
            ->where('session.ui_shell', 'exam_like')
            ->where('currentQuestion.id', $studySession->questionIds()[0])
        );
});

test('current learn question exposes shared explanation conflict metadata for inline admin editing', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);
    $otherCategory = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $sharedExternalId = '13447';

    $firstQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'source' => 'gov',
            'external_id' => $sharedExternalId,
            'question_topic_id' => $topic->getKey(),
            'prompt' => 'Pierwsza wersja wspolnego pytania?',
            'explanation' => 'Pierwsze wyjasnienie.',
        ]);

    Question::factory()
        ->for($otherCategory, 'licenseCategory')
        ->create([
            'source' => 'gov',
            'external_id' => $sharedExternalId,
            'question_topic_id' => $topic->getKey(),
            'prompt' => 'Druga wersja wspolnego pytania?',
            'explanation' => 'Drugie wyjasnienie.',
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'ui_shell' => 'zen',
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'all',
            'randomize_order' => false,
            'question_count' => 1,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    expect($studySession->questionIds())->toContain($firstQuestion->getKey());

    $this->actingAs($user)
        ->get(route('study-sessions.current'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('StudySessions/Show')
            ->where('currentQuestion.id', $firstQuestion->getKey())
            ->where('currentQuestion.external_id', $sharedExternalId)
            ->where('currentQuestion.source', 'gov')
            ->where('currentQuestion.shared_explanation_has_conflict', true)
        );
});

test('session start rejects exam mode when the selected category does not have a full official set', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'official_exam_scope',
        'name' => 'Zakres egzaminacyjny',
        'sort_order' => 10,
    ]);

    Question::factory()
        ->count(19)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    Question::factory()
        ->count(12)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $this->actingAs($user)
        ->from(route('session.index'))
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'exam',
            'question_count' => 32,
        ])
        ->assertRedirect(route('session.index'))
        ->assertSessionHasErrors('license_category_id');

    expect(StudySession::query()->count())->toBe(0);
});

test('learn mode uses all matching topic questions instead of an exam-style session cap', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    $selectedTopic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $matchingQuestions = Question::factory()
        ->count(5)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $selectedTopic->getKey(),
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 3,
            'question_topic_id' => $selectedTopic->getKey(),
            'question_status' => 'all',
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    expect($studySession->mode)->toBe('learn');
    expect($studySession->total_questions_count)->toBe(5);
    expect(collect($studySession->questionIds())->sort()->values()->all())
        ->toEqual($matchingQuestions->pluck('id')->sort()->values()->all());
});

test('session page defaults to the first specialist topic when specialist scope is requested', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $basicTopic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $specialistTopic = QuestionTopic::query()->create([
        'key' => 'speed_limits',
        'name' => 'Predkosci i ograniczenia',
        'sort_order' => 20,
    ]);

    Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $basicTopic->getKey(),
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $specialistTopic->getKey(),
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $this->actingAs($user)
        ->get(route('session.index', ['question_scope' => 'specialist']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('filters.question_scope', 'specialist')
            ->where('filters.question_topic_id', $specialistTopic->getKey())
            ->where('filters.question_count', 2)
        );
});

test('learn mode can start a specialist-only session from a mixed topic pool', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'speed_limits',
        'name' => 'Predkosci i ograniczenia',
        'sort_order' => 10,
    ]);

    $basicQuestions = Question::factory()
        ->count(2)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    $specialistQuestions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_scope' => 'specialist',
            'question_count' => 3,
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'all',
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    expect($studySession->mode)->toBe('learn');
    expect($studySession->total_questions_count)->toBe(3);
    expect(collect($studySession->questionIds())->sort()->values()->all())
        ->toEqual($specialistQuestions->pluck('id')->sort()->values()->all());
    expect(data_get($studySession->payload, 'filters.question_scope'))->toBe('specialist');
    expect($basicQuestions)->toHaveCount(2);
});

test('study session start defaults to all questions from the selected topic when status is omitted', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $selectedTopic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $questions = Question::factory()
        ->count(4)
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $selectedTopic->getKey(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($questions->first(), 'question')
        ->create([
            'total_attempts' => 2,
            'correct_count' => 1,
            'incorrect_count' => 1,
            'correct_streak' => 0,
            'last_quality' => 2,
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 4,
            'question_topic_id' => $selectedTopic->getKey(),
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest()->firstOrFail();

    expect($studySession->mode)->toBe('learn');
    expect($studySession->total_questions_count)->toBe(4);
    expect(collect($studySession->questionIds())->sort()->values()->all())
        ->toEqual($questions->pluck('id')->sort()->values()->all());
});

test('session page and study filters treat correct and memorized as distinct buckets', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $topic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);

    $unansweredQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $incorrectQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $correctQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    $memorizedQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'question_topic_id' => $topic->getKey(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($incorrectQuestion, 'question')
        ->create([
            'total_attempts' => 2,
            'correct_count' => 1,
            'incorrect_count' => 1,
            'repetitions' => 1,
            'correct_streak' => 1,
            'last_quality' => 3,
            'next_review_at' => today(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($correctQuestion, 'question')
        ->create([
            'total_attempts' => 1,
            'correct_count' => 1,
            'incorrect_count' => 0,
            'repetitions' => 1,
            'correct_streak' => 1,
            'last_quality' => 4,
            'next_review_at' => today()->addDay(),
        ]);

    UserQuestionProgress::factory()
        ->for($user)
        ->for($memorizedQuestion, 'question')
        ->create([
            'total_attempts' => 4,
            'correct_count' => 3,
            'incorrect_count' => 1,
            'repetitions' => 3,
            'correct_streak' => 3,
            'last_quality' => 5,
            'next_review_at' => today()->addDays(4),
        ]);

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('group_options.0.options.0.counts.unanswered', 1)
            ->where('group_options.0.options.0.counts.incorrect', 1)
            ->where('group_options.0.options.0.counts.correct', 1)
            ->where('group_options.0.options.0.counts.memorized', 1)
            ->where('status_options.3.label', 'Dobrze rozwiązane')
            ->where('status_options.4.label', 'Utrwalone')
        );

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 10,
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'correct',
        ])
        ->assertRedirect();

    $correctSession = StudySession::query()->latest('id')->firstOrFail();

    expect($correctSession->questionIds()->values()->all())->toEqual([$correctQuestion->getKey()]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 10,
            'question_topic_id' => $topic->getKey(),
            'question_status' => 'memorized',
        ])
        ->assertRedirect();

    $memorizedSession = StudySession::query()->latest('id')->firstOrFail();

    expect($memorizedSession->questionIds()->values()->all())->toEqual([$memorizedQuestion->getKey()]);
});

test('classic learning can start incorrect questions across all topics', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create(['code' => 'B']);
    $otherCategory = LicenseCategory::factory()->create(['code' => 'C']);

    UserProfile::factory()
        ->for($user, 'user')
        ->create([
            'target_category_id' => $category->getKey(),
        ]);

    $firstTopic = QuestionTopic::query()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
        'sort_order' => 10,
    ]);
    $secondTopic = QuestionTopic::query()->create([
        'key' => 'traffic_lights',
        'name' => 'Sygnaly swietlne',
        'sort_order' => 20,
    ]);

    $firstIncorrectQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['question_topic_id' => $firstTopic->getKey()]);
    $secondIncorrectQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['question_topic_id' => $secondTopic->getKey()]);
    $correctQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['question_topic_id' => $secondTopic->getKey()]);
    $unansweredQuestion = Question::factory()
        ->for($category, 'licenseCategory')
        ->create(['question_topic_id' => $firstTopic->getKey()]);
    $otherCategoryIncorrectQuestion = Question::factory()
        ->for($otherCategory, 'licenseCategory')
        ->create(['question_topic_id' => $firstTopic->getKey()]);

    foreach ([$firstIncorrectQuestion, $secondIncorrectQuestion, $otherCategoryIncorrectQuestion] as $question) {
        UserQuestionProgress::factory()
            ->for($user)
            ->for($question, 'question')
            ->create([
                'total_attempts' => 2,
                'correct_count' => 1,
                'incorrect_count' => 1,
                'repetitions' => 0,
                'correct_streak' => 0,
                'last_quality' => 2,
                'next_review_at' => today(),
            ]);
    }

    UserQuestionProgress::factory()
        ->for($user)
        ->for($correctQuestion, 'question')
        ->create([
            'total_attempts' => 1,
            'correct_count' => 1,
            'incorrect_count' => 0,
            'repetitions' => 1,
            'correct_streak' => 1,
            'last_quality' => 4,
            'next_review_at' => today()->addDay(),
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'ui_shell' => 'exam_like',
            'question_count' => 10,
            'question_topic_id' => null,
            'question_scope' => 'all',
            'question_status' => 'incorrect',
            'randomize_order' => false,
        ])
        ->assertRedirect();

    $studySession = StudySession::query()->latest('id')->firstOrFail();
    $questionIds = $studySession->questionIds()->values()->all();
    sort($questionIds);
    $expectedQuestionIds = [
        $firstIncorrectQuestion->getKey(),
        $secondIncorrectQuestion->getKey(),
    ];
    sort($expectedQuestionIds);

    expect($questionIds)->toBe($expectedQuestionIds)
        ->and(data_get($studySession->payload, 'filters.question_topic_id'))->toBeNull()
        ->and(data_get($studySession->payload, 'filters.question_status'))->toBe('incorrect')
        ->and($studySession->questionIds()->contains($correctQuestion->getKey()))->toBeFalse()
        ->and($studySession->questionIds()->contains($unansweredQuestion->getKey()))->toBeFalse()
        ->and($studySession->questionIds()->contains($otherCategoryIncorrectQuestion->getKey()))->toBeFalse();
});

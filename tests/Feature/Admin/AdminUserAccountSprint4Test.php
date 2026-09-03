<?php

use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\RankedPlayerRating;
use App\Models\RankedQueueEntry;
use App\Models\ReviewMemoryProgress;
use App\Models\ReviewTrainerDailyAnswer;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserQuestionProgress;
use App\Support\AdminUserAccountService;

test('admin can see category and moderator pool controls in user resource', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(UserResource::getUrl('edit', ['record' => $user], panel: 'admin'))
        ->assertOk()
        ->assertSee('Stała kategoria nauki', false)
        ->assertSee('Kategoria kursanta', false)
        ->assertSee('Pula moderatora', false);
});

test('moderator cannot access admin panel to change own pool', function () {
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->get('/admin')
        ->assertForbidden();
});

test('moderator pool usage is calculated from owned accounts', function () {
    $moderator = User::factory()->moderator()->create([
        'moderator_quota' => 3,
    ]);

    User::factory()
        ->count(2)
        ->create([
            'created_by_moderator_id' => $moderator->getKey(),
            'moderator_owner_id' => $moderator->getKey(),
        ]);

    expect($moderator->refresh()->moderatorAccountsUsed())->toBe(2)
        ->and($moderator->moderatorQuotaLimit())->toBe(3)
        ->and($moderator->moderatorQuotaRemaining())->toBe(1);
});

test('admin settings changes are auditable', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $service = app(AdminUserAccountService::class);
    $before = $service->adminSettingsSnapshot($user);

    $user->forceFill([
        'role' => User::ROLE_MODERATOR,
        'moderator_quota' => 50,
    ])->save();

    $service->recordAdminSettingsChanges($user->refresh(), $before, $admin);

    $auditLog = AuditLog::query()
        ->where('action', 'user.admin_settings_changed')
        ->where('entity_type', 'user')
        ->where('entity_id', (string) $user->getKey())
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->actor_user_id)->toBe($admin->getKey())
        ->and(data_get($auditLog->metadata, 'changes.role.old'))->toBe(User::ROLE_STUDENT)
        ->and(data_get($auditLog->metadata, 'changes.role.new'))->toBe(User::ROLE_MODERATOR)
        ->and(data_get($auditLog->metadata, 'changes.moderator_quota.new'))->toBe(50);
});

test('admin can remove moderator role and audit the change', function () {
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create([
        'moderator_quota' => 12,
    ]);
    $service = app(AdminUserAccountService::class);
    $before = $service->adminSettingsSnapshot($moderator);

    $moderator->forceFill([
        'role' => User::ROLE_STUDENT,
        'moderator_quota' => User::DEFAULT_MODERATOR_QUOTA,
    ])->save();

    $service->recordAdminSettingsChanges($moderator->refresh(), $before, $admin);

    $auditLog = AuditLog::query()
        ->where('action', 'user.admin_settings_changed')
        ->where('entity_type', 'user')
        ->where('entity_id', (string) $moderator->getKey())
        ->first();

    expect($moderator->isModerator())->toBeFalse()
        ->and($moderator->isStudent())->toBeTrue()
        ->and($auditLog)->not->toBeNull()
        ->and(data_get($auditLog->metadata, 'changes.role.old'))->toBe(User::ROLE_MODERATOR)
        ->and(data_get($auditLog->metadata, 'changes.role.new'))->toBe(User::ROLE_STUDENT);
});

test('admin category change resets dependent learning data and writes audit log', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $oldCategory = LicenseCategory::factory()->categoryB()->create();
    $newCategory = LicenseCategory::factory()->categoryC()->create();
    $question = Question::factory()
        ->for($oldCategory, 'licenseCategory')
        ->create();

    UserProfile::factory()
        ->for($user, 'user')
        ->for($oldCategory, 'targetCategory')
        ->create([
            'study_streak' => 8,
            'last_study_date' => today(),
            'onboarding_step' => 'completed',
        ]);

    $studySession = StudySession::factory()
        ->for($user, 'user')
        ->for($oldCategory, 'licenseCategory')
        ->create();

    $studySessionAnswer = StudySessionAnswer::factory()
        ->for($studySession, 'studySession')
        ->for($question, 'question')
        ->create();

    ReviewTrainerDailyAnswer::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $oldCategory->getKey(),
        'question_id' => $question->getKey(),
        'study_session_id' => $studySession->getKey(),
        'study_session_answer_id' => $studySessionAnswer->getKey(),
        'review_day' => today(),
        'daily_plan_policy_version' => 'review-daily-plan-v1',
        'answer_kind' => 'choice',
        'is_correct' => true,
        'answered_at' => now(),
    ]);

    UserQuestionProgress::factory()
        ->for($user, 'user')
        ->for($question, 'question')
        ->create([
            'total_attempts' => 4,
            'correct_count' => 2,
            'incorrect_count' => 2,
        ]);

    ReviewMemoryProgress::factory()
        ->for($user, 'user')
        ->for($question, 'question')
        ->for($oldCategory, 'licenseCategory')
        ->create([
            'verified_attempts_count' => 1,
            'verified_correct_count' => 1,
        ]);

    RankedQueueEntry::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $oldCategory->getKey(),
        'status' => 'queued',
        'joined_at' => now(),
    ]);

    RankedPlayerRating::query()->create([
        'user_id' => $user->getKey(),
        'rating' => 1600,
        'peak_rating' => 1620,
        'matches_played' => 4,
        'wins' => 2,
        'losses' => 2,
    ]);

    $resetCounts = app(AdminUserAccountService::class)
        ->changeTargetCategory($user, $newCategory->getKey(), $admin);

    expect($resetCounts)->toMatchArray([
        'study_sessions' => 1,
        'study_session_answers' => 1,
        'question_progress' => 1,
        'review_memory_progress' => 1,
        'review_trainer_daily_answers' => 1,
        'ranked_queue_entries' => 1,
        'ranked_player_ratings' => 1,
    ]);

    $profile = $user->refresh()->profile;

    expect($profile?->target_category_id)->toBe($newCategory->getKey())
        ->and($profile?->study_streak)->toBe(0)
        ->and($profile?->last_study_date)->toBeNull()
        ->and($profile?->onboarding_step)->toBe('admin_category_changed')
        ->and(StudySession::query()->where('user_id', $user->getKey())->count())->toBe(0)
        ->and(StudySessionAnswer::query()->count())->toBe(0)
        ->and(UserQuestionProgress::query()->where('user_id', $user->getKey())->count())->toBe(0)
        ->and(ReviewMemoryProgress::query()->where('user_id', $user->getKey())->count())->toBe(0)
        ->and(ReviewTrainerDailyAnswer::query()->where('user_id', $user->getKey())->count())->toBe(0)
        ->and(RankedQueueEntry::query()->where('user_id', $user->getKey())->count())->toBe(0)
        ->and(RankedPlayerRating::query()->where('user_id', $user->getKey())->count())->toBe(0);

    $auditLog = AuditLog::query()
        ->where('action', 'user.target_category_changed')
        ->where('entity_id', (string) $user->getKey())
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->actor_user_id)->toBe($admin->getKey())
        ->and(data_get($auditLog->metadata, 'old_target_category_code'))->toBe('B')
        ->and(data_get($auditLog->metadata, 'new_target_category_code'))->toBe('C')
        ->and(data_get($auditLog->metadata, 'reset_counts.study_sessions'))->toBe(1);
});

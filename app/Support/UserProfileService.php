<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserProfile;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;

class UserProfileService
{
    public const VISUAL_EXPLANATIONS_MODE_BEFORE_ANSWER = 'before_answer';

    public const VISUAL_EXPLANATIONS_MODE_AFTER_INCORRECT = 'after_incorrect';

    public const VISUAL_EXPLANATIONS_MODE_OFF = 'off';

    public static function normalizePreferredLearningTrack(?string $track): string
    {
        return in_array($track, UserProfile::learningTracks(), true)
            ? $track
            : UserProfile::LEARNING_TRACK_UNDECIDED;
    }

    public function profileFor(User $user): UserProfile
    {
        return UserProfile::query()->firstOrCreate(
            ['user_id' => $user->getKey()],
            $this->defaults($user),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): UserProfile
    {
        $profile = $this->profileFor($user);

        if (array_key_exists('display_name', $attributes)) {
            $profile->display_name = $attributes['display_name'];
        }

        if (array_key_exists('target_category_id', $attributes)) {
            $profile->target_category_id = $attributes['target_category_id'];
        }

        if (array_key_exists('preferred_learning_track', $attributes)) {
            $profile->preferred_learning_track = self::normalizePreferredLearningTrack(
                is_string($attributes['preferred_learning_track'] ?? null)
                    ? $attributes['preferred_learning_track']
                    : null,
            );
        }

        if (array_key_exists('exam_date', $attributes)) {
            $profile->exam_date = $attributes['exam_date'];
        }

        if (array_key_exists('onboarding_step', $attributes)) {
            $profile->onboarding_step = $attributes['onboarding_step'];
        }

        if (array_key_exists('visual_explanations_enabled', $attributes)) {
            $profile->visual_explanations_enabled = (bool) $attributes['visual_explanations_enabled'];
        }

        if (array_key_exists('visual_explanations_mode', $attributes)) {
            $profile->visual_explanations_mode = $attributes['visual_explanations_mode'];
            $profile->visual_explanations_enabled = $attributes['visual_explanations_mode'] !== self::VISUAL_EXPLANATIONS_MODE_OFF;
        } elseif (array_key_exists('visual_explanations_enabled', $attributes)) {
            $profile->visual_explanations_mode = $attributes['visual_explanations_enabled']
                ? self::VISUAL_EXPLANATIONS_MODE_AFTER_INCORRECT
                : self::VISUAL_EXPLANATIONS_MODE_OFF;
        }

        if (array_key_exists('auto_remove_incorrect_questions_on_correct', $attributes)) {
            $profile->auto_remove_incorrect_questions_on_correct = (bool) $attributes['auto_remove_incorrect_questions_on_correct'];
        }

        $profile->save();

        return $profile->fresh('targetCategory') ?? $profile->load('targetCategory');
    }

    public function markStudyActivity(User $user, ?CarbonInterface $occurredAt = null): UserProfile
    {
        $profile = $this->profileFor($user);
        $occurredAt ??= now();
        $activityDate = $occurredAt->toDateString();

        if ($profile->last_study_date?->toDateString() === $activityDate) {
            return $profile;
        }

        $nextStreak = 1;

        if ($profile->last_study_date?->toDateString() === $occurredAt->copy()->subDay()->toDateString()) {
            $nextStreak = max($profile->study_streak, 0) + 1;
        }

        $profile->forceFill([
            'study_streak' => $nextStreak,
            'last_study_date' => $activityDate,
        ]);

        try {
            $profile->save();
        } catch (QueryException $exception) {
            if (! str_contains(strtolower($exception->getMessage()), 'database is locked')) {
                throw $exception;
            }
        }

        return $profile;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(User $user): array
    {
        $latestStudySession = $user->studySessions()
            ->latest('created_at')
            ->first();

        return [
            'display_name' => null,
            'target_category_id' => null,
            'preferred_learning_track' => UserProfile::LEARNING_TRACK_UNDECIDED,
            'exam_date' => null,
            'study_streak' => $this->initialStudyStreak($user),
            'last_study_date' => $latestStudySession?->created_at?->toDateString(),
            'tier' => 'free',
            'onboarding_step' => null,
            'visual_explanations_enabled' => true,
            'visual_explanations_mode' => self::VISUAL_EXPLANATIONS_MODE_AFTER_INCORRECT,
            'auto_remove_incorrect_questions_on_correct' => false,
        ];
    }

    protected function initialStudyStreak(User $user): int
    {
        $activityDates = $user->studySessions()
            ->latest('created_at')
            ->get()
            ->map(fn ($studySession) => $studySession->created_at?->toDateString())
            ->filter()
            ->unique()
            ->values();

        if ($activityDates->isEmpty()) {
            return 0;
        }

        $expectedDate = today();
        $streak = 0;

        foreach ($activityDates as $activityDate) {
            if ($activityDate !== $expectedDate->toDateString()) {
                break;
            }

            $streak++;
            $expectedDate = $expectedDate->copy()->subDay();
        }

        return $streak;
    }
}

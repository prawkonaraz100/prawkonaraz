<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionSignLanguageAsset;
use App\Models\User;
use App\Models\UserProfile;

class PjmFreeAccessResolver
{
    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_FREE_PJM = 'free_pjm';

    public function forUser(User $user): PjmFreeAccessDecision
    {
        if ($user->isBanned()) {
            return PjmFreeAccessDecision::deny('banned');
        }

        if ($user->isUnclaimedTemporaryAccount()) {
            return PjmFreeAccessDecision::deny('temporary_account_unclaimed');
        }

        if ($user->mustChangePassword()) {
            return PjmFreeAccessDecision::deny('password_change_required');
        }

        if ($user->hasSystemProductAccess()) {
            return PjmFreeAccessDecision::allow(self::SOURCE_SYSTEM);
        }

        if (! config('study.pjm_module_enabled', false)) {
            return PjmFreeAccessDecision::deny('pjm_module_suspended');
        }

        if (! $user->hasVerifiedEmail()) {
            return PjmFreeAccessDecision::deny('email_unverified');
        }

        $profile = $user->profile()->first();

        if ($profile?->preferred_learning_track !== UserProfile::LEARNING_TRACK_PJM) {
            return PjmFreeAccessDecision::deny('pjm_track_not_selected');
        }

        $categoryId = $profile->target_category_id;
        $hasPjmQuestions = $categoryId !== null
            && Question::query()
                ->where('license_category_id', $categoryId)
                ->where('is_active', true)
                ->whereNull('delivery_issue')
                ->whereNotNull('external_id')
                ->where('external_id', '!=', '')
                ->whereExists(function ($query): void {
                    $query
                        ->selectRaw('1')
                        ->from('question_sign_language_assets as pjm_assets')
                        ->whereColumn('pjm_assets.external_id', 'questions.external_id')
                        ->where('pjm_assets.asset_role', QuestionSignLanguageAsset::ROLE_QUESTION)
                        ->where('pjm_assets.is_active', true)
                        ->whereIn('pjm_assets.processing_status', [
                            QuestionSignLanguageAsset::STATUS_READY,
                            QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED,
                        ]);
                })
                ->exists();

        if (! $hasPjmQuestions) {
            return PjmFreeAccessDecision::deny('missing_pjm_assets');
        }

        return PjmFreeAccessDecision::allow(self::SOURCE_FREE_PJM);
    }
}

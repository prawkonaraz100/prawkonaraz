<?php

namespace App\Support;

use App\Models\QuestionCollection;
use App\Models\QuestionModule;
use App\Models\StudySession;
use App\Models\User;

class QuestionCollectionAccessService
{
    public function __construct(
        protected ProductAccessResolver $productAccessResolver,
    ) {}

    public function forUser(User $user, QuestionCollection $collection): QuestionCollectionAccessDecision
    {
        if ($user->isAdministrator()) {
            return QuestionCollectionAccessDecision::allow();
        }

        if (! $collection->is_active) {
            return QuestionCollectionAccessDecision::deny('collection_inactive');
        }

        if (! $collection->is_available_to_learners) {
            return QuestionCollectionAccessDecision::deny('collection_unavailable');
        }

        if (! $this->productAccessResolver->forUser($user)->allowed) {
            return QuestionCollectionAccessDecision::deny('missing_product_access');
        }

        return QuestionCollectionAccessDecision::allow();
    }

    public function forModule(
        User $user,
        QuestionCollection $collection,
        QuestionModule $module,
    ): QuestionCollectionAccessDecision {
        if ($module->question_collection_id !== $collection->getKey()) {
            return QuestionCollectionAccessDecision::deny('module_not_in_collection');
        }

        $collectionDecision = $this->forUser($user, $collection);

        if (! $collectionDecision->allowed) {
            return $collectionDecision;
        }

        if (! $user->isAdministrator() && ! $module->is_active) {
            return QuestionCollectionAccessDecision::deny('module_inactive');
        }

        return QuestionCollectionAccessDecision::allow();
    }

    public function forStudySession(User $user, ?StudySession $studySession): ?QuestionCollectionAccessDecision
    {
        if (! $studySession || ! $this->isCourseSession($studySession)) {
            return null;
        }

        $collection = $this->collectionForSession($studySession);

        if (! $collection instanceof QuestionCollection) {
            return QuestionCollectionAccessDecision::deny('collection_missing');
        }

        if ($this->isCollectionReviewSession($studySession)) {
            return $this->forUser($user, $collection);
        }

        $module = $this->moduleForSession($studySession);

        if (! $module instanceof QuestionModule) {
            return QuestionCollectionAccessDecision::deny('module_missing');
        }

        return $this->forModule($user, $collection, $module);
    }

    public function isCourseSession(StudySession $studySession): bool
    {
        if ($studySession->question_collection_id !== null || $studySession->question_module_id !== null) {
            return true;
        }

        return in_array(data_get($studySession->payload, 'context.type'), [
            'question_module',
            'question_collection_review',
        ], true);
    }

    public function isCollectionReviewSession(StudySession $studySession): bool
    {
        return $studySession->question_collection_id !== null
            && $studySession->question_module_id === null
            && data_get($studySession->payload, 'context.type') === 'question_collection_review';
    }

    protected function collectionForSession(StudySession $studySession): ?QuestionCollection
    {
        if ($studySession->question_collection_id !== null) {
            return $studySession->questionCollection;
        }

        $collectionCode = data_get($studySession->payload, 'context.collection_code');

        if (! is_string($collectionCode) || $collectionCode === '') {
            return null;
        }

        return QuestionCollection::query()
            ->where('code', $collectionCode)
            ->first();
    }

    protected function moduleForSession(StudySession $studySession): ?QuestionModule
    {
        if ($studySession->question_module_id !== null) {
            return $studySession->questionModule;
        }

        $moduleId = data_get($studySession->payload, 'context.question_module_id');

        if (! is_numeric($moduleId) || (int) $moduleId <= 0) {
            return null;
        }

        return QuestionModule::query()->find((int) $moduleId);
    }
}

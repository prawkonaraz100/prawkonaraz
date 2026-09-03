<?php

namespace App\Support;

use App\Models\QuestionCollection;
use App\Models\QuestionModule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionCollectionProgressService
{
    /**
     * @param  Collection<int, array{answered_count:int, total_questions:int, percent:int}>  $progressByModule
     * @return array{answered_count:int, total_questions:int, percent:int}
     */
    public function collectionProgressFor(
        QuestionCollection $collection,
        Collection $progressByModule,
    ): array {
        $moduleIds = $collection->modules
            ->pluck('id')
            ->map(fn (mixed $moduleId): int => (int) $moduleId);
        $answeredCount = $moduleIds
            ->sum(fn (int $moduleId): int => (int) ($progressByModule->get($moduleId)['answered_count'] ?? 0));
        $totalQuestions = $moduleIds
            ->sum(fn (int $moduleId): int => (int) ($progressByModule->get($moduleId)['total_questions'] ?? 0));

        return [
            'answered_count' => $answeredCount,
            'total_questions' => $totalQuestions,
            'percent' => $totalQuestions > 0
                ? (int) round(($answeredCount / $totalQuestions) * 100)
                : 0,
        ];
    }

    /**
     * @param  Collection<int, QuestionCollection>  $collections
     * @return Collection<int, array{answered_count:int, total_questions:int, percent:int}>
     */
    public function moduleProgressFor(User $user, Collection $collections): Collection
    {
        $modules = $collections
            ->flatMap(fn (QuestionCollection $collection): Collection => $collection->modules)
            ->values();

        if ($modules->isEmpty()) {
            return collect();
        }

        $moduleIds = $modules
            ->pluck('id')
            ->map(fn (mixed $moduleId): int => (int) $moduleId)
            ->all();
        $collectionIds = $collections
            ->pluck('id')
            ->map(fn (mixed $collectionId): int => (int) $collectionId)
            ->all();

        $answeredByModule = DB::table('study_session_answers as answers')
            ->join('study_sessions as sessions', 'sessions.id', '=', 'answers.study_session_id')
            ->join('question_module_question as assignments', 'assignments.question_id', '=', 'answers.question_id')
            ->join('question_modules as modules', function ($join): void {
                $join
                    ->on('modules.id', '=', 'assignments.question_module_id')
                    ->on('modules.question_collection_id', '=', 'sessions.question_collection_id');
            })
            ->where('sessions.user_id', $user->getKey())
            ->whereIn('sessions.question_collection_id', $collectionIds)
            ->whereIn('assignments.question_module_id', $moduleIds)
            ->where(function ($query): void {
                $query
                    ->whereNull('sessions.question_module_id')
                    ->orWhereColumn('sessions.question_module_id', 'assignments.question_module_id');
            })
            ->groupBy('assignments.question_module_id')
            ->selectRaw('assignments.question_module_id as module_id, COUNT(DISTINCT answers.question_id) as answered_count')
            ->pluck('answered_count', 'module_id')
            ->mapWithKeys(fn (mixed $count, mixed $moduleId): array => [(int) $moduleId => (int) $count]);

        return $modules
            ->mapWithKeys(function (QuestionModule $module) use ($answeredByModule): array {
                $totalQuestions = max((int) $module->questions_count, 0);
                $answeredCount = min((int) ($answeredByModule->get($module->getKey()) ?? 0), $totalQuestions);

                return [
                    $module->getKey() => [
                        'answered_count' => $answeredCount,
                        'total_questions' => $totalQuestions,
                        'percent' => $totalQuestions > 0
                            ? (int) round(($answeredCount / $totalQuestions) * 100)
                            : 0,
                    ],
                ];
            });
    }
}

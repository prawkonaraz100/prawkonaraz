<?php

namespace App\Http\Controllers;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Support\QuestionMediaPayloadBuilder;
use App\Support\StudyContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionCatalogController extends Controller
{
    public function __invoke(
        Request $request,
        QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        StudyContextService $studyContextService,
    ): Response {
        $request->validate([
            'category' => ['nullable', 'integer', 'exists:license_categories,id'],
            'sort' => ['nullable', 'string', 'in:latest,difficulty_asc,difficulty_desc,points_asc,points_desc'],
        ]);

        $categoryId = $studyContextService->requestedOrPreferredCategoryId($request);
        $sort = (string) ($request->string('sort')->value() ?: 'latest');

        $questionsQuery = Question::query()
            ->with('licenseCategory', 'questionTopic', 'media')
            ->where('is_active', true)
            ->readyForDelivery()
            ->when($categoryId, fn ($query) => $query->where('license_category_id', $categoryId));

        $this->applySort($questionsQuery, $sort);

        $questions = $questionsQuery
            ->paginate(9)
            ->withQueryString()
            ->through(function (Question $question) use ($questionMediaPayloadBuilder): array {
                $media = $questionMediaPayloadBuilder->forCatalog($question->media);

                return [
                    'id' => $question->getKey(),
                    'external_id' => $question->external_id,
                    'prompt' => $question->prompt,
                    'question_type' => $question->question_type,
                    'difficulty' => $question->difficulty,
                    'points' => $question->points,
                    'license_category' => [
                        'id' => $question->licenseCategory?->getKey(),
                        'name' => $question->licenseCategory?->name,
                        'code' => $question->licenseCategory?->code,
                    ],
                    'topic' => $question->questionTopic ? [
                        'id' => $question->questionTopic->getKey(),
                        'key' => $question->questionTopic->key,
                        'name' => $question->questionTopic->name,
                    ] : null,
                    'media_count' => count($media),
                    'media' => $media,
                ];
            });

        $categories = $studyContextService->visibleCategoriesQuery()
            ->withCount([
                'questions' => fn ($query) => $query->where('is_active', true)->readyForDelivery(),
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LicenseCategory $category) => [
                'id' => $category->getKey(),
                'code' => $category->code,
                'name' => $category->name,
                'questions_count' => $category->questions_count,
            ])
            ->values();

        return Inertia::render('Questions/Index', [
            'categories' => $categories,
            'filters' => [
                'category' => $categoryId,
                'sort' => $sort,
            ],
            'questions' => $questions,
        ]);
    }

    protected function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'difficulty_asc' => $query
                ->orderBy('difficulty')
                ->orderByDesc('published_at')
                ->orderBy('id'),
            'difficulty_desc' => $query
                ->orderByDesc('difficulty')
                ->orderByDesc('published_at')
                ->orderBy('id'),
            'points_asc' => $query
                ->orderBy('points')
                ->orderBy('difficulty')
                ->orderBy('id'),
            'points_desc' => $query
                ->orderByDesc('points')
                ->orderByDesc('difficulty')
                ->orderBy('id'),
            default => $query
                ->orderByDesc('published_at')
                ->orderBy('id'),
        };
    }
}

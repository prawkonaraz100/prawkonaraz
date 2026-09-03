<?php

namespace App\Support;

use App\Models\LegalArticleTopicCandidate;
use App\Models\LegalContentPage;
use App\Models\Question;
use App\Models\QuestionLegalReference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LegalContentAgentWorkspaceService
{
    /**
     * @return array<string, mixed>
     */
    public function queue(): array
    {
        $candidates = LegalArticleTopicCandidate::query()
            ->where('level', LegalArticleTopicCandidate::LEVEL_FOCUSED)
            ->orderBy('sort_order')
            ->get();
        $candidateIds = $candidates->modelKeys();
        $links = DB::table('legal_article_topic_candidate_question as links')
            ->join('questions', 'questions.id', '=', 'links.question_id')
            ->leftJoin('license_categories', 'license_categories.id', '=', 'questions.license_category_id')
            ->whereIn('links.legal_article_topic_candidate_id', $candidateIds)
            ->get([
                'links.legal_article_topic_candidate_id',
                'links.canonical_external_id',
                'questions.id as question_id',
                'license_categories.code as category_code',
            ])
            ->groupBy('legal_article_topic_candidate_id');
        $verifiedReferences = DB::table('legal_article_topic_candidate_question as links')
            ->join('question_legal_references as legal_references', 'legal_references.question_id', '=', 'links.question_id')
            ->whereIn('links.legal_article_topic_candidate_id', $candidateIds)
            ->where('legal_references.status', QuestionLegalReference::STATUS_VERIFIED)
            ->whereNotNull('legal_references.verified_at')
            ->get([
                'links.legal_article_topic_candidate_id',
                'links.canonical_external_id',
            ])
            ->groupBy('legal_article_topic_candidate_id');
        $articleSlugs = $candidates
            ->pluck('existing_article_slug')
            ->filter()
            ->unique()
            ->values();
        $pagesBySlug = LegalContentPage::query()
            ->whereIn('slug', $articleSlugs)
            ->get(['id', 'slug', 'title', 'status', 'published_at', 'last_reviewed_at'])
            ->keyBy('slug');

        $topics = $candidates
            ->map(function (LegalArticleTopicCandidate $candidate) use (
                $links,
                $verifiedReferences,
                $pagesBySlug,
            ): array {
                $topicLinks = $links->get($candidate->getKey(), collect());
                $verified = $verifiedReferences->get($candidate->getKey(), collect());
                $page = $candidate->existing_article_slug
                    ? $pagesBySlug->get($candidate->existing_article_slug)
                    : null;
                $canonicalCount = $topicLinks
                    ->pluck('canonical_external_id')
                    ->filter()
                    ->unique()
                    ->count();

                return [
                    'slug' => $candidate->slug,
                    'title' => $candidate->title,
                    'workflow_status' => $this->workflowStatus($candidate, $page, $canonicalCount),
                    'candidate_status' => $candidate->status,
                    'canonical_questions' => $canonicalCount,
                    'question_rows' => $topicLinks->pluck('question_id')->unique()->count(),
                    'verified_canonical_questions' => $verified
                        ->pluck('canonical_external_id')
                        ->filter()
                        ->unique()
                        ->count(),
                    'categories' => $topicLinks
                        ->pluck('category_code')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all(),
                    'existing_article_slug' => $candidate->existing_article_slug,
                    'article_title' => $page?->title,
                    'article_status' => $page?->status,
                    'article_last_reviewed_at' => $page?->last_reviewed_at?->toDateString(),
                    'rule_version' => $candidate->rule_version,
                ];
            })
            ->sort(function (array $left, array $right): int {
                $statusOrder = [
                    'ready' => 0,
                    'manual_review' => 1,
                    'in_progress' => 2,
                    'published' => 3,
                    'rejected' => 4,
                ];

                return [
                    $statusOrder[$left['workflow_status']] ?? 9,
                    -$left['canonical_questions'],
                    $left['title'],
                ] <=> [
                    $statusOrder[$right['workflow_status']] ?? 9,
                    -$right['canonical_questions'],
                    $right['title'],
                ];
            })
            ->values();

        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'focused_topics' => $topics->count(),
                'ready_topics' => $topics->where('workflow_status', 'ready')->count(),
                'used_candidate_topics' => $topics->where('workflow_status', 'published')->count(),
                'published_articles' => $topics
                    ->where('workflow_status', 'published')
                    ->pluck('existing_article_slug')
                    ->filter()
                    ->unique()
                    ->count(),
                'manual_review_topics' => $topics->where('workflow_status', 'manual_review')->count(),
            ],
            'next_topics' => $topics
                ->where('workflow_status', 'ready')
                ->take(20)
                ->values()
                ->all(),
            'topics' => $topics->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dossier(string $slug): array
    {
        $candidate = LegalArticleTopicCandidate::query()
            ->where('slug', $slug)
            ->firstOrFail();
        $questions = $candidate->questions()
            ->with([
                'licenseCategory:id,code,name',
                'questionTopic:id,key,name',
                'media:id,question_id,kind,path,poster_path,variant,sort_order',
                'legalReferences.legalUnit:id,slug,label,title,status',
                'legalReferences.contentPage:id,slug,title,status',
                'legalReferences.topic:id,slug,title,status',
            ])
            ->orderBy('questions.id')
            ->get([
                'questions.id',
                'questions.license_category_id',
                'questions.question_topic_id',
                'questions.external_id',
                'questions.prompt',
                'questions.option_a',
                'questions.option_b',
                'questions.option_c',
                'questions.correct_answer',
                'questions.question_type',
                'questions.explanation',
                'questions.is_active',
                'questions.delivery_issue',
                'questions.source',
            ]);
        $canonicalQuestions = $questions
            ->groupBy(fn (Question $question): string => (string) $question->pivot->canonical_external_id)
            ->map(fn (Collection $rows, string $canonicalExternalId): array => $this->canonicalQuestion(
                $canonicalExternalId,
                $rows,
            ))
            ->sortBy(fn (array $question): array => [
                is_numeric($question['external_id']) ? 0 : 1,
                is_numeric($question['external_id']) ? (int) $question['external_id'] : $question['external_id'],
            ])
            ->values();
        $page = $candidate->existing_article_slug
            ? LegalContentPage::query()
                ->with(['legalUnits:id,slug,label,title,status,last_checked_at'])
                ->where('slug', $candidate->existing_article_slug)
                ->first()
            : null;

        return [
            'generated_at' => now()->toIso8601String(),
            'candidate' => [
                'slug' => $candidate->slug,
                'title' => $candidate->title,
                'description' => $candidate->description,
                'level' => $candidate->level,
                'status' => $candidate->status,
                'rule_version' => $candidate->rule_version,
                'existing_article_slug' => $candidate->existing_article_slug,
            ],
            'workflow' => [
                'status' => $this->workflowStatus(
                    $candidate,
                    $page,
                    $canonicalQuestions->count(),
                ),
                'next_action' => $page
                    ? 'Przejrzyj relacje pytan i zdecyduj, czy artykul wymaga rozbudowy lub ponownego review.'
                    : 'Zweryfikuj klaster, oficjalna podstawe prawna i utworz artykul zgodnie z runbookiem.',
                'public_relations_are_verified_only' => true,
            ],
            'article' => $page ? [
                'slug' => $page->slug,
                'title' => $page->title,
                'status' => $page->status,
                'published_at' => $page->published_at?->toDateString(),
                'last_reviewed_at' => $page->last_reviewed_at?->toDateString(),
                'legal_units' => $page->legalUnits
                    ->map(fn ($unit): array => [
                        'slug' => $unit->slug,
                        'label' => $unit->label,
                        'title' => $unit->title,
                        'status' => $unit->status,
                        'last_checked_at' => $unit->last_checked_at?->toDateString(),
                    ])
                    ->values()
                    ->all(),
            ] : null,
            'summary' => [
                'canonical_questions' => $canonicalQuestions->count(),
                'question_rows' => $questions->count(),
                'categories' => $questions
                    ->pluck('licenseCategory.code')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
                'canonical_questions_with_verified_reference' => $canonicalQuestions
                    ->filter(fn (array $question): bool => collect($question['legal_references'])
                        ->contains('status', QuestionLegalReference::STATUS_VERIFIED))
                    ->count(),
                'prompt_conflicts' => $canonicalQuestions
                    ->where('checks.prompt_variants', '>', 1)
                    ->pluck('external_id')
                    ->values()
                    ->all(),
                'answer_conflicts' => $canonicalQuestions
                    ->where('checks.answer_variants', '>', 1)
                    ->pluck('external_id')
                    ->values()
                    ->all(),
                'correct_answer_conflicts' => $canonicalQuestions
                    ->where('checks.correct_answer_conflict', true)
                    ->pluck('external_id')
                    ->values()
                    ->all(),
            ],
            'questions' => $canonicalQuestions->all(),
        ];
    }

    protected function workflowStatus(
        LegalArticleTopicCandidate $candidate,
        ?LegalContentPage $page,
        int $canonicalQuestions,
    ): string {
        if ($candidate->status === 'rejected') {
            return 'rejected';
        }

        if ($page?->isPubliclyVisible()) {
            return 'published';
        }

        if ($candidate->status !== LegalArticleTopicCandidate::STATUS_CANDIDATE) {
            return $candidate->status;
        }

        return $canonicalQuestions >= 3 ? 'ready' : 'manual_review';
    }

    /**
     * @param  Collection<int, Question>  $rows
     * @return array<string, mixed>
     */
    protected function canonicalQuestion(string $canonicalExternalId, Collection $rows): array
    {
        $representative = $rows
            ->sortBy(fn (Question $question): array => [
                $question->external_id === $canonicalExternalId ? 0 : 1,
                $question->getKey(),
            ])
            ->first();
        $promptVariants = $rows->pluck('prompt')->filter()->unique()->values();
        $answerVariants = $rows
            ->map(fn (Question $question): array => [
                'option_a' => $question->option_a,
                'option_b' => $question->option_b,
                'option_c' => $question->option_c,
                'correct_answer' => $question->correct_answer,
                'correct_answer_text' => $this->correctAnswerText($question),
            ])
            ->unique(fn (array $answers): string => json_encode($answers))
            ->values();
        $correctAnswers = $answerVariants
            ->pluck('correct_answer')
            ->filter()
            ->unique()
            ->values();
        $legalReferences = $rows
            ->flatMap(fn (Question $question): Collection => $question->legalReferences
                ->map(fn ($reference): array => [
                    'question_id' => (int) $question->getKey(),
                    'question_external_id' => $question->external_id,
                    'status' => $reference->status,
                    'assignment_source' => $reference->assignment_source,
                    'confidence' => $reference->confidence,
                    'verified_at' => $reference->verified_at?->toDateString(),
                    'legal_unit_slug' => $reference->legalUnit?->slug,
                    'legal_unit_label' => $reference->legalUnit?->label,
                    'legal_unit_title' => $reference->legalUnit?->title,
                    'legal_topic_slug' => $reference->topic?->slug,
                    'article_slug' => $reference->contentPage?->slug,
                    'public_note' => $reference->public_note,
                ]))
            ->unique(fn (array $reference): string => implode('|', [
                $reference['legal_unit_slug'],
                $reference['article_slug'],
                $reference['public_note'],
            ]))
            ->values();

        return [
            'external_id' => $canonicalExternalId,
            'prompt' => $representative?->prompt,
            'prompt_variants' => $promptVariants->all(),
            'categories' => $rows
                ->pluck('licenseCategory.code')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'question_topics' => $rows
                ->map(fn (Question $question): array => [
                    'key' => $question->questionTopic?->key,
                    'name' => $question->questionTopic?->name,
                ])
                ->filter(fn (array $topic): bool => filled($topic['key']))
                ->unique('key')
                ->values()
                ->all(),
            'answers' => $answerVariants->all(),
            'explanations' => $rows
                ->pluck('explanation')
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'media' => $rows
                ->flatMap(fn (Question $question): Collection => $question->media
                    ->map(fn ($media): array => [
                        'kind' => $media->kind,
                        'variant' => $media->variant,
                        'path' => $media->path,
                        'poster_path' => $media->poster_path,
                    ]))
                ->unique(fn (array $media): string => implode('|', [
                    $media['kind'],
                    $media['variant'],
                    $media['path'],
                ]))
                ->values()
                ->all(),
            'candidate_matches' => $rows
                ->map(fn (Question $question): array => [
                    'match_type' => $question->pivot->match_type,
                    'matched_by' => $question->pivot->matched_by,
                    'confidence' => (int) $question->pivot->confidence,
                    'assignment_source' => $question->pivot->assignment_source,
                ])
                ->unique(fn (array $match): string => json_encode($match))
                ->values()
                ->all(),
            'legal_references' => $legalReferences->all(),
            'source_rows' => $rows
                ->map(fn (Question $question): array => [
                    'id' => (int) $question->getKey(),
                    'external_id' => $question->external_id,
                    'category' => $question->licenseCategory?->code,
                    'source' => $question->source,
                    'is_active' => (bool) $question->is_active,
                    'delivery_issue' => $question->delivery_issue,
                ])
                ->values()
                ->all(),
            'checks' => [
                'prompt_variants' => $promptVariants->count(),
                'answer_variants' => $answerVariants->count(),
                'correct_answer_conflict' => $correctAnswers->count() > 1,
                'has_media' => $rows->contains(fn (Question $question): bool => $question->media->isNotEmpty()),
                'has_verified_legal_reference' => $legalReferences
                    ->contains('status', QuestionLegalReference::STATUS_VERIFIED),
            ],
        ];
    }

    protected function correctAnswerText(Question $question): ?string
    {
        return match ($question->correct_answer) {
            'a' => $question->option_a,
            'b' => $question->option_b,
            'c' => $question->option_c,
            default => null,
        };
    }
}

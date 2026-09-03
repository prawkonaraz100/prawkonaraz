<?php

namespace App\Models;

use Database\Factories\QuestionPublicExplanationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionPublicExplanation extends Model
{
    /** @use HasFactory<QuestionPublicExplanationFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'question_id',
        'external_id',
        'title',
        'body',
        'dont_confuse_with',
        'exam_trap',
        'common_mistakes',
        'related_questions',
        'status',
        'author_id',
        'reviewer_id',
        'published_at',
        'last_reviewed_at',
        'source_note',
        'internal_note',
    ];

    protected function casts(): array
    {
        return [
            'common_mistakes' => 'array',
            'related_questions' => 'array',
            'published_at' => 'datetime',
            'last_reviewed_at' => 'date',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(ContentAuthor::class, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(ContentAuthor::class, 'reviewer_id');
    }

    public function leftRelations(): HasMany
    {
        return $this->hasMany(QuestionRelation::class, 'left_explanation_id');
    }

    public function rightRelations(): HasMany
    {
        return $this->hasMany(QuestionRelation::class, 'right_explanation_id');
    }

    public function seoTopics(): BelongsToMany
    {
        return $this->belongsToMany(
            QuestionSeoTopic::class,
            'question_seo_topic_memberships',
        )->withPivot([
            'source',
            'is_primary',
            'role',
            'status',
            'confidence',
            'reason',
            'metadata',
            'reviewed_by_user_id',
            'reviewed_at',
        ])->withTimestamps();
    }

    public function seoTopicMemberships(): HasMany
    {
        return $this->hasMany(QuestionSeoTopicMembership::class);
    }

    public function relationRecommendations(): HasMany
    {
        return $this->hasMany(QuestionRelationRecommendation::class, 'source_explanation_id');
    }

    public function displayAuthor(): ?ContentAuthor
    {
        return $this->author ?? $this->reviewer;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('status'), self::STATUS_PUBLISHED)
            ->whereNotNull($this->qualifyColumn('published_at'));
    }
}

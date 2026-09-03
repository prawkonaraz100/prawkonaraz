<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    public const DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA = 'missing_primary_media';

    protected $fillable = [
        'license_category_id',
        'question_topic_id',
        'external_id',
        'prompt',
        'explanation',
        'option_a',
        'option_b',
        'option_c',
        'correct_answer',
        'difficulty',
        'points',
        'question_type',
        'is_active',
        'requires_primary_media',
        'delivery_issue',
        'source',
        'published_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => 'integer',
            'points' => 'integer',
            'is_active' => 'boolean',
            'requires_primary_media' => 'boolean',
            'published_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function licenseCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class);
    }

    public function questionTopic(): BelongsTo
    {
        return $this->belongsTo(QuestionTopic::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(QuestionMedia::class)->orderBy('sort_order');
    }

    public function incorrectQuestionEntries(): HasMany
    {
        return $this->hasMany(UserIncorrectQuestion::class);
    }

    public function signLanguageAssets(): HasMany
    {
        return $this->hasMany(QuestionSignLanguageAsset::class, 'external_id', 'external_id')
            ->orderBy('asset_role')
            ->orderBy('id');
    }

    public function audioAssets(): HasMany
    {
        return $this->hasMany(QuestionAudioAsset::class, 'external_id', 'external_id')
            ->orderBy('audio_type')
            ->orderBy('id');
    }

    public function activeSignLanguageAssets(): HasMany
    {
        return $this->signLanguageAssets()
            ->where('is_active', true)
            ->whereIn('processing_status', [
                QuestionSignLanguageAsset::STATUS_READY,
                QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED,
            ]);
    }

    public function signLanguageQuestionAsset(): HasOne
    {
        return $this->hasOne(QuestionSignLanguageAsset::class, 'external_id', 'external_id')
            ->where('asset_role', QuestionSignLanguageAsset::ROLE_QUESTION)
            ->where('is_active', true)
            ->whereIn('processing_status', [
                QuestionSignLanguageAsset::STATUS_READY,
                QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED,
            ]);
    }

    public function explanationAssets(): HasMany
    {
        return $this->hasMany(QuestionExplanationAsset::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function explanationAnnotations(): HasMany
    {
        return $this->hasMany(QuestionExplanationAnnotation::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function referenceExplanationAsset(): HasOne
    {
        return $this->hasOne(QuestionExplanationAsset::class)
            ->where('kind', QuestionExplanationAsset::KIND_REFERENCE_SIGN);
    }

    public function studySessionAnswers(): HasMany
    {
        return $this->hasMany(StudySessionAnswer::class);
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserQuestionProgress::class);
    }

    public function legalReferences(): HasMany
    {
        return $this->hasMany(QuestionLegalReference::class);
    }

    public function legalArticleTopicCandidates(): BelongsToMany
    {
        return $this->belongsToMany(
            LegalArticleTopicCandidate::class,
            'legal_article_topic_candidate_question',
        )->withPivot([
            'canonical_external_id',
            'match_type',
            'matched_by',
            'confidence',
            'assignment_source',
        ])->withTimestamps();
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(QuestionModule::class, 'question_module_question')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function scopeDueForReview(Builder $query, User $user): Builder
    {
        return $query
            ->join('user_question_progress as progress', function ($join) use ($user): void {
                $join->on('progress.question_id', '=', 'questions.id')
                    ->where('progress.user_id', '=', $user->getKey());
            })
            ->whereDate('progress.next_review_at', '<=', today())
            ->select('questions.*');
    }

    public function scopeReadyForDelivery(Builder $query): Builder
    {
        return $query->whereNull('delivery_issue');
    }

    public function scopeMissingPrimaryMedia(Builder $query): Builder
    {
        return $query->where('delivery_issue', self::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA);
    }

    public function expectsPrimaryMedia(): bool
    {
        return (bool) $this->requires_primary_media;
    }

    public function hasDeliveryIssue(): bool
    {
        return filled($this->delivery_issue);
    }

    public function deliveryStatus(): string
    {
        return match (true) {
            $this->delivery_issue === self::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA => 'Brak glownego medium',
            $this->requires_primary_media => 'Gotowe z medium',
            default => 'Gotowe tekstowe',
        };
    }

    public function deliveryIssueLabel(): ?string
    {
        return match ($this->delivery_issue) {
            self::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA => 'Brak glownego medium',
            default => null,
        };
    }
}

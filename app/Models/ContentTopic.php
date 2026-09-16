<?php

namespace App\Models;

use App\Support\NewsroomRouteContract;
use Database\Factories\ContentTopicFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

class ContentTopic extends Model
{
    /** @use HasFactory<ContentTopicFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const PUBLICATION_CORPUS_MINIMUM = 3;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'status',
        'featured_article_id',
        'seo_title',
        'seo_description',
        'published_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (ContentTopic $topic): void {
            if (preg_match('/\\A'.NewsroomRouteContract::SLUG_PATTERN.'\\z/', (string) $topic->slug) !== 1) {
                throw ValidationException::withMessages([
                    'slug' => 'Slug tematu może zawierać tylko małe litery, cyfry i myślniki.',
                ]);
            }

            if (!in_array((string) $topic->status, [
                self::STATUS_DRAFT,
                self::STATUS_PUBLISHED,
                self::STATUS_ARCHIVED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Nieobsługiwany status tematu.',
                ]);
            }

            if (!$topic->exists) {
                return;
            }

            $wasPublished = $topic->getRawOriginal('published_at') !== null;

            if ($wasPublished && $topic->isDirty('slug')) {
                throw ValidationException::withMessages([
                    'slug' => 'Slug tematu jest niezmienny po pierwszej publikacji.',
                ]);
            }

            if ($wasPublished && $topic->isDirty('published_at') && $topic->published_at === null) {
                throw ValidationException::withMessages([
                    'published_at' => 'Data pierwszej publikacji tematu nie może zostać wyczyszczona.',
                ]);
            }
        });

        static::deleting(function (ContentTopic $topic): void {
            if (!$topic->canBeDeleted()) {
                throw ValidationException::withMessages([
                    'topic' => 'Nie można usunąć topicu po pierwszej publikacji. Użyj archiwizacji, aby zachować historyczny URL.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
        ];
    }

    public function featuredArticle(): BelongsTo
    {
        return $this->belongsTo(ContentArticle::class, 'featured_article_id');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            ContentArticle::class,
            'content_article_topic',
            'topic_id',
            'article_id',
        )->withPivot('created_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function eligibleCorpusCount(): int
    {
        return $this->articles()
            ->activelyDistributed()
            ->indexable()
            ->count();
    }

    public function meetsPublicationCorpusBaseline(): bool
    {
        return $this->eligibleCorpusCount() >= self::PUBLICATION_CORPUS_MINIMUM;
    }

    public function isCorpusBelowBaseline(): bool
    {
        return $this->isPubliclyVisible()
            && !$this->meetsPublicationCorpusBaseline();
    }

    public function hasEligibleFeaturedArticle(): bool
    {
        if ($this->featured_article_id === null) {
            return true;
        }

        return $this->articles()
            ->whereKey($this->featured_article_id)
            ->activelyDistributed()
            ->indexable()
            ->exists();
    }

    public function meetsPublicationRequirements(): bool
    {
        return trim((string) $this->description) !== ''
            && $this->meetsPublicationCorpusBaseline()
            && $this->hasEligibleFeaturedArticle();
    }

    public function isEditoriallyPromotable(): bool
    {
        return $this->isPubliclyVisible()
            && $this->meetsPublicationRequirements();
    }

    public function canBeDeleted(): bool
    {
        return $this->published_at === null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

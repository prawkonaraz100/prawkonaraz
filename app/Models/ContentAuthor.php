<?php

namespace App\Models;

use Database\Factories\ContentAuthorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ContentAuthor extends Model
{
    /** @use HasFactory<ContentAuthorFactory> */
    use HasFactory;

    public const DEFAULT_LEGAL_REFERENCE_VERIFIER_SLUG = 'jakub-wisniewski';

    protected $fillable = [
        'name',
        'slug',
        'job_title',
        'bio',
        'photo_path',
        'linkedin_url',
        'external_profile_url',
        'is_published',
        'published_at',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $author): void {
            if (! $author->isDirty(['is_published', 'published_at'])) {
                return;
            }

            $wasPubliclyVisible = (bool) $author->getOriginal('is_published')
                && $author->getOriginal('published_at') !== null
                && $author->asDateTime($author->getOriginal('published_at'))->lte(now());

            if (! $wasPubliclyVisible || $author->isPubliclyVisible()) {
                return;
            }

            if (! $author->authoredContentArticles()->indexable()->exists()) {
                return;
            }

            throw ValidationException::withMessages([
                'is_published' => 'Nie można odpublikować autora, dopóki ma publiczne, indeksowalne artykuły Newsroomu. Najpierw przypisz je do innego publicznego autora albo wycofaj/noindexuj publikacje.',
            ]);
        });
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function authoredContentArticles(): HasMany
    {
        return $this->hasMany(ContentArticle::class, 'author_id');
    }

    public function reviewedContentArticles(): HasMany
    {
        return $this->hasMany(ContentArticle::class, 'reviewer_id');
    }

    public function trafficSigns(): HasMany
    {
        return $this->hasMany(TrafficSign::class);
    }

    public function authoredLegalContentPages(): HasMany
    {
        return $this->hasMany(LegalContentPage::class, 'author_id');
    }

    public function reviewedLegalContentPages(): HasMany
    {
        return $this->hasMany(LegalContentPage::class, 'reviewer_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public static function defaultLegalReferenceVerifier(): ?self
    {
        return self::query()
            ->published()
            ->where('slug', self::DEFAULT_LEGAL_REFERENCE_VERIFIER_SLUG)
            ->first(['id', 'name', 'slug', 'job_title', 'is_published', 'published_at']);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

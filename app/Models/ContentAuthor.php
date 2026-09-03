<?php

namespace App\Models;

use Database\Factories\ContentAuthorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
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

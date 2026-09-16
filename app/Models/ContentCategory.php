<?php

namespace App\Models;

use App\Support\NewsroomRouteContract;
use Database\Factories\ContentCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ContentCategory extends Model
{
    /** @use HasFactory<ContentCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'position',
        'is_active',
        'seo_title',
        'seo_description',
    ];

    protected static function booted(): void
    {
        static::saving(function (ContentCategory $category): void {
            if (preg_match('/\\A'.NewsroomRouteContract::SLUG_PATTERN.'\\z/', (string) $category->slug) !== 1) {
                throw ValidationException::withMessages([
                    'slug' => 'Slug kategorii może zawierać tylko małe litery, cyfry i myślniki.',
                ]);
            }

            if (! $category->exists) {
                return;
            }

            if ($category->isDirty('slug')) {
                throw ValidationException::withMessages([
                    'slug' => 'Slug kategorii jest niezmienny po utworzeniu.',
                ]);
            }

            if (
                $category->isDirty('is_active')
                && ! $category->is_active
                && (
                    $category->articles()->publiclyVisible()->exists()
                    || $category->articles()->activelyDistributed()->exists()
                )
            ) {
                throw ValidationException::withMessages([
                    'is_active' => 'Nie można wyłączyć kategorii, dopóki ma publiczne lub aktywnie dystrybuowane artykuły.',
                ]);
            }
        });

        static::deleting(function (ContentCategory $category): void {
            if ($category->articles()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'Nie można usunąć kategorii, która jest używana przez artykuły.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(ContentArticle::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isPublicationEligible(): bool
    {
        return $this->is_active;
    }

    public function hasPubliclyVisibleArticles(): bool
    {
        if (array_key_exists('publicly_visible_articles_count', $this->getAttributes())) {
            return (int) $this->getAttribute('publicly_visible_articles_count') > 0;
        }

        return $this->articles()->publiclyVisible()->exists();
    }

    public function hasActivelyDistributedArticles(): bool
    {
        if (array_key_exists('actively_distributed_articles_count', $this->getAttributes())) {
            return (int) $this->getAttribute('actively_distributed_articles_count') > 0;
        }

        return $this->articles()->activelyDistributed()->exists();
    }

    public function canBeDeactivated(): bool
    {
        return ! $this->hasPubliclyVisibleArticles()
            && ! $this->hasActivelyDistributedArticles();
    }

    public function canBeDeleted(): bool
    {
        if (array_key_exists('articles_count', $this->getAttributes())) {
            return (int) $this->getAttribute('articles_count') === 0;
        }

        return ! $this->articles()->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

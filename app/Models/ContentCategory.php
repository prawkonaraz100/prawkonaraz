<?php

namespace App\Models;

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
                && ! $category->canBeDeactivated()
            ) {
                throw ValidationException::withMessages([
                    'is_active' => 'Nie można wyłączyć kategorii, dopóki ma publiczne lub aktywnie dystrybuowane artykuły.',
                ]);
            }
        });

        static::deleting(function (ContentCategory $category): void {
            if (! $category->canBeDeleted()) {
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
        return $this->articles()->publiclyVisible()->exists();
    }

    public function hasActivelyDistributedArticles(): bool
    {
        return $this->articles()->activelyDistributed()->exists();
    }

    public function canBeDeactivated(): bool
    {
        return ! $this->hasPubliclyVisibleArticles()
            && ! $this->hasActivelyDistributedArticles();
    }

    public function canBeDeleted(): bool
    {
        return ! $this->articles()->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

<?php

namespace App\Models;

use Database\Factories\ContentCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

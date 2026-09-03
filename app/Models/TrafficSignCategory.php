<?php

namespace App\Models;

use Database\Factories\TrafficSignCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrafficSignCategory extends Model
{
    /** @use HasFactory<TrafficSignCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'intro_title',
        'intro_body',
        'sort_order',
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

    public function queryMapEntries(): HasMany
    {
        return $this->hasMany(TrafficSignQueryMapEntry::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
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

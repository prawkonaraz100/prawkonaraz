<?php

namespace App\Models;

use App\Enums\ContentArticleSourceType;
use Database\Factories\ContentArticleSourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentArticleSource extends Model
{
    /** @use HasFactory<ContentArticleSourceFactory> */
    use HasFactory;

    protected $touches = ['article'];

    protected $fillable = [
        'article_id',
        'source_type',
        'publisher',
        'title',
        'url',
        'published_at',
        'accessed_at',
        'is_primary',
        'is_official',
        'is_publicly_cited',
        'note',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => ContentArticleSourceType::class,
            'published_at' => 'immutable_datetime',
            'accessed_at' => 'immutable_datetime',
            'is_primary' => 'boolean',
            'is_official' => 'boolean',
            'is_publicly_cited' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(ContentArticle::class, 'article_id');
    }

    public function scopePubliclyCited(Builder $query): Builder
    {
        return $query->where('is_publicly_cited', true);
    }
}

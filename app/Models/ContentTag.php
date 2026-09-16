<?php

namespace App\Models;

use Database\Factories\ContentTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContentTag extends Model
{
    /** @use HasFactory<ContentTagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            ContentArticle::class,
            'content_article_tag',
            'tag_id',
            'article_id',
        )->withPivot('created_at');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentArticleRedirect extends Model
{
    protected $fillable = [
        'article_id',
        'from_path',
        'to_path',
        'http_status',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(ContentArticle::class, 'article_id');
    }
}

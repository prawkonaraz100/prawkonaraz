<?php

namespace App\Models;

use Database\Factories\QuestionMediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionMedia extends Model
{
    /** @use HasFactory<QuestionMediaFactory> */
    use HasFactory;

    protected $table = 'question_media';

    protected $fillable = [
        'question_id',
        'kind',
        'disk',
        'path',
        'poster_path',
        'mime_type',
        'bytes',
        'duration_seconds',
        'width',
        'height',
        'variant',
        'sort_order',
        'metadata',
        'seo_video_description',
    ];

    protected function casts(): array
    {
        return [
            'bytes' => 'integer',
            'duration_seconds' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'variant' => 'string',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}

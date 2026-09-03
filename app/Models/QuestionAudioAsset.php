<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionAudioAsset extends Model
{
    use HasFactory;

    public const CONTENT_SCOPE_QUESTION = 'question';

    public const TYPE_QUESTION = 'question';
    public const TYPE_CORRECT_ANSWER = 'correct_answer';
    public const TYPE_EXPLANATION = 'explanation';
    public const TYPE_EXAM_TRAP = 'exam_trap';

    public const STATUS_PENDING = 'pending';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_FAILED = 'failed';
    public const STATUS_OUTDATED = 'outdated';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'question_id',
        'external_id',
        'category_code',
        'asset_key',
        'content_scope',
        'audio_type',
        'locale',
        'source_text_hash',
        'source_text',
        'storage_disk',
        'storage_path',
        'duration_seconds',
        'encoding_format',
        'bytes',
        'checksum_sha256',
        'voice_provider',
        'voice_id',
        'voice_name',
        'model_id',
        'generation_version',
        'status',
        'error_message',
        'generated_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'question_id' => 'integer',
            'duration_seconds' => 'float',
            'bytes' => 'integer',
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function scopeGenerated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_GENERATED);
    }

    public function scopeQuestionAudio(Builder $query): Builder
    {
        return $query
            ->where('content_scope', self::CONTENT_SCOPE_QUESTION)
            ->where('audio_type', self::TYPE_QUESTION);
    }

    public function scopeCorrectAnswerAudio(Builder $query): Builder
    {
        return $query
            ->where('content_scope', self::CONTENT_SCOPE_QUESTION)
            ->where('audio_type', self::TYPE_CORRECT_ANSWER);
    }

    public function isGenerated(): bool
    {
        return $this->status === self::STATUS_GENERATED;
    }
}

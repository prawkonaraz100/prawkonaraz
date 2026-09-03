<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedQuestionExplanationSignOverride extends Model
{
    public const NULL_SOURCE_SCOPE = '__NULL_SOURCE__';

    public const ACTION_HIDE = QuestionExplanationSignOverride::ACTION_HIDE;

    public const ACTION_REPLACE = QuestionExplanationSignOverride::ACTION_REPLACE;

    public const ACTION_ADD = QuestionExplanationSignOverride::ACTION_ADD;

    protected $fillable = [
        'external_id',
        'source_scope',
        'action',
        'detected_code',
        'anchor_text',
        'traffic_sign_id',
        'position',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function sourceScopeFor(?string $source): string
    {
        return filled($source) ? (string) $source : self::NULL_SOURCE_SCOPE;
    }

    public function trafficSign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function resolutionKey(): string
    {
        if ($this->action === self::ACTION_ADD) {
            return 'add:'.mb_strtolower((string) $this->anchor_text);
        }

        return 'detected:'.mb_strtoupper((string) $this->detected_code);
    }
}

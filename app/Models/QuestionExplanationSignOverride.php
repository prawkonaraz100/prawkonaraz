<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionExplanationSignOverride extends Model
{
    public const ACTION_HIDE = 'hide';

    public const ACTION_REPLACE = 'replace';

    public const ACTION_ADD = 'add';

    protected $fillable = [
        'question_id',
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

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
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

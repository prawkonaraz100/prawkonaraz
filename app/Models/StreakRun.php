<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StreakRun extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_FAILED = 'failed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'user_id',
        'license_category_id',
        'current_question_id',
        'failed_question_id',
        'status',
        'score',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function licenseCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class);
    }

    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(StreakRunAnswer::class);
    }
}

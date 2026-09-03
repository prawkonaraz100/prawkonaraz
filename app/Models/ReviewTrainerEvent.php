<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewTrainerEvent extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'license_category_id',
        'study_session_id',
        'event_name',
        'planner_version',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'license_category_id' => 'integer',
            'study_session_id' => 'integer',
            'payload' => 'array',
            'occurred_at' => 'datetime',
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

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }
}

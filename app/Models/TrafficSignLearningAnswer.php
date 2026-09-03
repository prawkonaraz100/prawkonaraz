<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficSignLearningAnswer extends Model
{
    public const MODE_SIGN_TO_MEANING = 'sign_to_meaning';

    public const MODE_MEANING_TO_SIGN = 'meaning_to_sign';

    protected $fillable = [
        'traffic_sign_learning_session_id',
        'user_id',
        'traffic_sign_id',
        'selected_traffic_sign_id',
        'position',
        'answer_mode',
        'options',
        'is_correct',
        'response_time_ms',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'options' => 'array',
            'is_correct' => 'boolean',
            'response_time_ms' => 'integer',
            'answered_at' => 'datetime',
        ];
    }

    public function learningSession(): BelongsTo
    {
        return $this->belongsTo(TrafficSignLearningSession::class, 'traffic_sign_learning_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trafficSign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class);
    }

    public function selectedTrafficSign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class, 'selected_traffic_sign_id');
    }
}

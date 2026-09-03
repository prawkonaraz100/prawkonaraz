<?php

namespace App\Models;

use App\Support\StudySessionAnswerKind;
use Database\Factories\StudySessionAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySessionAnswer extends Model
{
    /** @use HasFactory<StudySessionAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'study_session_id',
        'question_id',
        'selected_answer',
        'answer_kind',
        'is_correct',
        'response_time_ms',
        'answered_at',
    ];

    protected $attributes = [
        'answer_kind' => StudySessionAnswerKind::CHOICE,
    ];

    protected function casts(): array
    {
        return [
            'answer_kind' => 'string',
            'is_correct' => 'boolean',
            'response_time_ms' => 'integer',
            'answered_at' => 'datetime',
        ];
    }

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}

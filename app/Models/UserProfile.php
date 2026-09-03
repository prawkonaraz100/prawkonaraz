<?php

namespace App\Models;

use Database\Factories\UserProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    /** @use HasFactory<UserProfileFactory> */
    use HasFactory;

    public const LEARNING_TRACK_PJM = 'pjm';

    public const LEARNING_TRACK_CLASSIC = 'classic';

    public const LEARNING_TRACK_UNDECIDED = 'undecided';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'display_name',
        'target_category_id',
        'preferred_learning_track',
        'exam_date',
        'study_streak',
        'last_study_date',
        'tier',
        'onboarding_step',
        'visual_explanations_enabled',
        'visual_explanations_mode',
        'auto_remove_incorrect_questions_on_correct',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'study_streak' => 'integer',
            'last_study_date' => 'date',
            'visual_explanations_enabled' => 'boolean',
            'auto_remove_incorrect_questions_on_correct' => 'boolean',
        ];
    }

    /**
     * @return list<string>
     */
    public static function learningTracks(): array
    {
        return [
            self::LEARNING_TRACK_PJM,
            self::LEARNING_TRACK_CLASSIC,
            self::LEARNING_TRACK_UNDECIDED,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class, 'target_category_id');
    }
}

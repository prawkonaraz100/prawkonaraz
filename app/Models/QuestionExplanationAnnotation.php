<?php

namespace App\Models;

use Database\Factories\QuestionExplanationAnnotationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionExplanationAnnotation extends Model
{
    /** @use HasFactory<QuestionExplanationAnnotationFactory> */
    use HasFactory;

    public const ANNOTATION_TYPE_ARROW = 'arrow';

    public const ANNOTATION_TYPE_CIRCLE = 'circle';

    public const ANNOTATION_TYPE_LABEL = 'label';

    public const ANNOTATION_TYPE_TEXT = 'text';

    public const ARROW_DEFAULT_LENGTH_PERCENT = 18.0;

    public const ARROW_DEFAULT_ANGLE_DEGREES = 45;

    public const ARROW_DEFAULT_STROKE_PERCENT = 1.6;

    public const ARROW_DEFAULT_HEAD_PERCENT = 3.5;

    public const TARGET_KIND_QUESTION_IMAGE = 'question_image';

    public const TARGET_KIND_VIDEO_FRAME = 'video_frame';

    protected $fillable = [
        'question_id',
        'target_kind',
        'frame_time_seconds',
        'annotation_type',
        'x_percent',
        'y_percent',
        'width_percent',
        'height_percent',
        'arrow_length_percent',
        'arrow_angle_degrees',
        'arrow_stroke_percent',
        'arrow_head_percent',
        'label',
        'tone',
        'position',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'frame_time_seconds' => 'integer',
            'x_percent' => 'float',
            'y_percent' => 'float',
            'width_percent' => 'float',
            'height_percent' => 'float',
            'arrow_length_percent' => 'float',
            'arrow_angle_degrees' => 'integer',
            'arrow_stroke_percent' => 'float',
            'arrow_head_percent' => 'float',
            'position' => 'integer',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}

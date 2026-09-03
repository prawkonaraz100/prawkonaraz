<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionExplanationDraft extends Model
{
    use HasFactory;

    public const STATUS_STAGED = 'staged';

    public const STATUS_STAGING_CONFLICT = 'staging_conflict';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_APPLIED_WITH_SKIPS = 'applied_with_skips';

    public const STATUS_SKIPPED_EXISTING = 'skipped_existing';

    protected $fillable = [
        'source',
        'external_id',
        'prompt',
        'draft_text',
        'source_summary',
        'categories',
        'question_type',
        'question_media_kind',
        'structure_scope',
        'accepted_answer',
        'accepted_answer_label',
        'source_queue',
        'source_question_id',
        'source_url',
        'tier_b_decision',
        'tier_b_note',
        'resolution_method',
        'quality_flags',
        'staging_flags',
        'local_category_codes',
        'missing_category_codes',
        'local_question_ids',
        'local_question_count',
        'local_existing_explanation_count',
        'applied_question_count',
        'skipped_existing_question_count',
        'status',
        'staging_issue',
        'last_applied_at',
        'source_payload',
        'last_apply_report',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'quality_flags' => 'array',
            'staging_flags' => 'array',
            'local_category_codes' => 'array',
            'missing_category_codes' => 'array',
            'local_question_ids' => 'array',
            'last_applied_at' => 'datetime',
            'source_payload' => 'array',
            'last_apply_report' => 'array',
        ];
    }
}

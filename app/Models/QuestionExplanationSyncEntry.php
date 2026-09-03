<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionExplanationSyncEntry extends Model
{
    public const STATUS_PLANNED = 'planned';

    public const STATUS_APPLIED = 'applied';

    public const STATUS_SKIPPED_STALE = 'skipped_stale';

    public const STATUS_ROLLED_BACK = 'rolled_back';

    public const STATUS_ROLLBACK_CONFLICT = 'rollback_conflict';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'content_import_run_id',
        'question_id',
        'question_public_explanation_id',
        'external_id',
        'license_category_code',
        'source_public_explanation_hash',
        'question_integrity_hash',
        'previous_explanation_hash',
        'new_explanation_hash',
        'previous_explanation',
        'new_explanation',
        'status',
        'reason',
        'applied_at',
        'rolled_back_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    public function contentImportRun(): BelongsTo
    {
        return $this->belongsTo(ContentImportRun::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function questionPublicExplanation(): BelongsTo
    {
        return $this->belongsTo(QuestionPublicExplanation::class);
    }
}

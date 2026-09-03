<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentImportRun extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'kind',
        'identifier',
        'status',
        'dry_run',
        'source_path',
        'output_path',
        'report_path',
        'rows_total',
        'questions_total',
        'media_total',
        'asset_plan_total',
        'uploaded_assets_total',
        'errors_count',
        'warnings_count',
        'summary',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dry_run' => 'boolean',
            'rows_total' => 'integer',
            'questions_total' => 'integer',
            'media_total' => 'integer',
            'asset_plan_total' => 'integer',
            'uploaded_assets_total' => 'integer',
            'errors_count' => 'integer',
            'warnings_count' => 'integer',
            'summary' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function questionExplanationSyncEntries(): HasMany
    {
        return $this->hasMany(QuestionExplanationSyncEntry::class);
    }
}

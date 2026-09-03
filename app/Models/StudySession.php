<?php

namespace App\Models;

use Database\Factories\StudySessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class StudySession extends Model
{
    /** @use HasFactory<StudySessionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_category_id',
        'question_collection_id',
        'question_module_id',
        'mode',
        'status',
        'started_at',
        'completed_at',
        'correct_answers_count',
        'total_questions_count',
        'score_percent',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'correct_answers_count' => 'integer',
            'total_questions_count' => 'integer',
            'score_percent' => 'decimal:2',
            'payload' => 'array',
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

    public function questionCollection(): BelongsTo
    {
        return $this->belongsTo(QuestionCollection::class);
    }

    public function questionModule(): BelongsTo
    {
        return $this->belongsTo(QuestionModule::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(StudySessionAnswer::class);
    }

    /**
     * Limit a query to the regular learning flow for a licence category.
     *
     * Legacy qualification sessions did not have relational course columns,
     * so retain the payload condition while those records exist.
     *
     * @param  Builder<StudySession>  $query
     * @return Builder<StudySession>
     */
    public function scopeRegularCategory(Builder $query): Builder
    {
        return $query
            ->whereNull('question_collection_id')
            ->whereNull('question_module_id')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('payload->context->type')
                    ->orWhere('payload->context->type', '!=', 'question_module');
            });
    }

    /**
     * @return Collection<int, int>
     */
    public function questionIds(): Collection
    {
        return collect($this->payload['question_ids'] ?? [])
            ->map(fn (mixed $questionId) => (int) $questionId)
            ->filter()
            ->values();
    }
}

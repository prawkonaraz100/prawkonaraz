<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuestionModule extends Model
{
    protected $fillable = [
        'question_collection_id',
        'source_id',
        'code',
        'slug',
        'name',
        'description',
        'expected_questions',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'expected_questions' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(QuestionCollection::class, 'question_collection_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'question_module_question')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }
}

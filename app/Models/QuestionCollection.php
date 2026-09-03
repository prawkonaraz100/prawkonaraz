<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionCollection extends Model
{
    protected $fillable = [
        'license_category_id',
        'code',
        'slug',
        'name',
        'description',
        'kind',
        'source',
        'is_active',
        'is_public',
        'is_available_to_learners',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'is_available_to_learners' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function licenseCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(QuestionModule::class)->orderBy('sort_order')->orderBy('id');
    }

    public function incorrectQuestionEntries(): HasMany
    {
        return $this->hasMany(QuestionCollectionIncorrectQuestion::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\QuestionTopicCategoryHeroFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionTopicCategoryHero extends Model
{
    /** @use HasFactory<QuestionTopicCategoryHeroFactory> */
    use HasFactory;

    protected $fillable = [
        'license_category_id',
        'question_topic_id',
        'hero_image_path',
        'hero_image_alt',
        'hero_image_position',
        'admin_note',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function licenseCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class);
    }

    public function questionTopic(): BelongsTo
    {
        return $this->belongsTo(QuestionTopic::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

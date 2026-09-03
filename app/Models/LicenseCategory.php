<?php

namespace App\Models;

use Database\Factories\LicenseCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseCategory extends Model
{
    /** @use HasFactory<LicenseCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'slug',
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function questionCollections(): HasMany
    {
        return $this->hasMany(QuestionCollection::class);
    }

    public function questionTopicCategoryLabels(): HasMany
    {
        return $this->hasMany(QuestionTopicCategoryLabel::class);
    }

    public function questionTopicCategoryHeroes(): HasMany
    {
        return $this->hasMany(QuestionTopicCategoryHero::class);
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function userProfiles(): HasMany
    {
        return $this->hasMany(UserProfile::class, 'target_category_id');
    }

    public function rankedMatches(): HasMany
    {
        return $this->hasMany(RankedMatch::class);
    }

    public function rankedQueueEntries(): HasMany
    {
        return $this->hasMany(RankedQueueEntry::class);
    }
}

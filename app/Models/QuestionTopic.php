<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'hero_image_path',
        'hero_image_alt',
        'hero_image_position',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function categoryLabels(): HasMany
    {
        return $this->hasMany(QuestionTopicCategoryLabel::class);
    }

    public function categoryHeroes(): HasMany
    {
        return $this->hasMany(QuestionTopicCategoryHero::class);
    }
}

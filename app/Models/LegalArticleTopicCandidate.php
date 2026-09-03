<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LegalArticleTopicCandidate extends Model
{
    public const LEVEL_PILLAR = 'pillar';

    public const LEVEL_FOCUSED = 'focused';

    public const STATUS_CANDIDATE = 'candidate';

    protected $fillable = [
        'slug',
        'title',
        'description',
        'level',
        'status',
        'existing_article_slug',
        'sort_order',
        'rule_version',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(
            Question::class,
            'legal_article_topic_candidate_question',
        )->withPivot([
            'canonical_external_id',
            'match_type',
            'matched_by',
            'confidence',
            'assignment_source',
        ])->withTimestamps();
    }
}

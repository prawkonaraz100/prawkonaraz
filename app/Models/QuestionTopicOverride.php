<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionTopicOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_category_code',
        'source',
        'external_id',
        'question_topic_key',
        'reason',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }
}

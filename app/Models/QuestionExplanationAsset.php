<?php

namespace App\Models;

use Database\Factories\QuestionExplanationAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionExplanationAsset extends Model
{
    /** @use HasFactory<QuestionExplanationAssetFactory> */
    use HasFactory;

    public const KIND_REFERENCE_SIGN = 'reference_sign';

    protected $fillable = [
        'question_id',
        'traffic_sign_id',
        'kind',
        'disk',
        'file_path',
        'title',
        'body',
        'caption',
        'alt_text',
        'position',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function trafficSign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

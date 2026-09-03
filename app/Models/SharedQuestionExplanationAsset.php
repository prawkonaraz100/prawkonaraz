<?php

namespace App\Models;

use Database\Factories\SharedQuestionExplanationAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedQuestionExplanationAsset extends Model
{
    /** @use HasFactory<SharedQuestionExplanationAssetFactory> */
    use HasFactory;

    public const KIND_REFERENCE_SIGN = 'reference_sign';

    public const NULL_SOURCE_SCOPE = '__NULL_SOURCE__';

    protected $fillable = [
        'external_id',
        'source_scope',
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

    public static function sourceScopeFor(?string $source): string
    {
        return filled($source) ? (string) $source : self::NULL_SOURCE_SCOPE;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function trafficSign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

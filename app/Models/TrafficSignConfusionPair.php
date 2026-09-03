<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficSignConfusionPair extends Model
{
    public const SOURCE_SUPPORTING_PAGE = 'supporting_page';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'traffic_sign_id',
        'confusing_traffic_sign_id',
        'source',
        'source_slug',
        'strength',
    ];

    protected function casts(): array
    {
        return [
            'strength' => 'integer',
        ];
    }

    public function trafficSign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class);
    }

    public function confusingTrafficSign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class, 'confusing_traffic_sign_id');
    }

    /**
     * @return array<string, string>
     */
    public static function sourceOptions(): array
    {
        return [
            static::SOURCE_SUPPORTING_PAGE => 'Strona porównawcza',
            static::SOURCE_MANUAL => 'Ręcznie',
        ];
    }

    public function sourceLabel(): string
    {
        return static::sourceOptions()[$this->source] ?? 'Inne';
    }
}

<?php

namespace App\Models;

use Database\Factories\TrafficSignQueryMapEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficSignQueryMapEntry extends Model
{
    /** @use HasFactory<TrafficSignQueryMapEntryFactory> */
    use HasFactory;

    public const TARGET_HUB = 'hub';

    public const TARGET_CATEGORY = 'category';

    public const TARGET_SIGN = 'sign';

    public const TARGET_SUPPORTING = 'supporting_article';

    public const INTENT_INFORMATIONAL = 'informational';

    public const INTENT_BEHAVIORAL = 'behavioral';

    public const INTENT_COMPARISON = 'comparison';

    public const INTENT_LEGAL = 'legal';

    public const INTENT_EXAM = 'exam';

    public const PRIORITY_P1 = 'p1';

    public const PRIORITY_P2 = 'p2';

    public const PRIORITY_P3 = 'p3';

    public const STATUS_WATCHLIST = 'watchlist';

    public const STATUS_BACKLOG = 'backlog';

    public const STATUS_BRIEF_READY = 'brief_ready';

    public const STATUS_DRAFTING = 'drafting';

    public const STATUS_READY = 'ready';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_DEFERRED = 'deferred';

    protected $fillable = [
        'traffic_sign_id',
        'traffic_sign_category_id',
        'primary_query',
        'mapped_title',
        'target_type',
        'search_intent',
        'priority',
        'rollout_status',
        'batch_label',
        'target_path',
        'watch_reason',
        'source_plan',
        'correction_notes',
        'competitor_notes',
        'first_mover_note',
        'notes',
    ];

    public function trafficSign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrafficSignCategory::class, 'traffic_sign_category_id');
    }

    /**
     * @return array<string, string>
     */
    public static function targetTypeOptions(): array
    {
        return [
            static::TARGET_HUB => 'Hub',
            static::TARGET_CATEGORY => 'Kategoria',
            static::TARGET_SIGN => 'Strona znaku',
            static::TARGET_SUPPORTING => 'Strona wspierająca',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function searchIntentOptions(): array
    {
        return [
            static::INTENT_INFORMATIONAL => 'Informacyjna',
            static::INTENT_BEHAVIORAL => 'Zachowanie kierowcy',
            static::INTENT_COMPARISON => 'Porównawcza',
            static::INTENT_LEGAL => 'Prawna',
            static::INTENT_EXAM => 'Egzaminacyjna',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function priorityOptions(): array
    {
        return [
            static::PRIORITY_P1 => 'P1',
            static::PRIORITY_P2 => 'P2',
            static::PRIORITY_P3 => 'P3',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function rolloutStatusOptions(): array
    {
        return [
            static::STATUS_WATCHLIST => 'Watchlista',
            static::STATUS_BACKLOG => 'Backlog',
            static::STATUS_BRIEF_READY => 'Brief gotowy',
            static::STATUS_DRAFTING => 'W pisaniu',
            static::STATUS_READY => 'Gotowe do produkcji',
            static::STATUS_PUBLISHED => 'Opublikowane',
            static::STATUS_DEFERRED => 'Odłożone',
        ];
    }

    public function targetTypeLabel(): string
    {
        return static::targetTypeOptions()[$this->target_type] ?? 'Nieznany';
    }

    public function targetTypeColor(): string
    {
        return match ($this->target_type) {
            static::TARGET_SIGN => 'success',
            static::TARGET_CATEGORY, static::TARGET_HUB => 'info',
            default => 'warning',
        };
    }

    public function searchIntentLabel(): string
    {
        return static::searchIntentOptions()[$this->search_intent] ?? 'Nieznana';
    }

    public function searchIntentColor(): string
    {
        return match ($this->search_intent) {
            static::INTENT_EXAM, static::INTENT_LEGAL => 'warning',
            static::INTENT_COMPARISON => 'info',
            static::INTENT_BEHAVIORAL => 'success',
            default => 'gray',
        };
    }

    public function priorityLabel(): string
    {
        return static::priorityOptions()[$this->priority] ?? strtoupper((string) $this->priority);
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            static::PRIORITY_P1 => 'danger',
            static::PRIORITY_P2 => 'warning',
            default => 'gray',
        };
    }

    public function rolloutStatusLabel(): string
    {
        return static::rolloutStatusOptions()[$this->rollout_status] ?? 'Nieznany';
    }

    public function rolloutStatusColor(): string
    {
        return match ($this->rollout_status) {
            static::STATUS_PUBLISHED => 'success',
            static::STATUS_READY => 'info',
            static::STATUS_DRAFTING, static::STATUS_BRIEF_READY => 'warning',
            static::STATUS_DEFERRED => 'danger',
            default => 'gray',
        };
    }

    public function rolloutScopeSummary(): string
    {
        $parts = array_filter([
            $this->trafficSign?->code,
            $this->category?->name,
            $this->batch_label,
        ]);

        return $parts === [] ? 'Bez podpiętego scope' : implode(' · ', $parts);
    }
}

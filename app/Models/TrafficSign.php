<?php

namespace App\Models;

use Database\Factories\TrafficSignFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrafficSign extends Model
{
    /** @use HasFactory<TrafficSignFactory> */
    use HasFactory;

    private const PUBLIC_CODE_OVERRIDES = [
        'S-1a' => 'S-1',
        'S-1b' => 'S-1',
        'S-1c' => 'S-1',
        'S-1d' => 'S-1',
    ];

    public const WORKFLOW_DRAFT = 'draft';

    public const WORKFLOW_IN_REVIEW = 'in_review';

    public const WORKFLOW_PUBLISHED = 'published';

    public const WORKFLOW_NEEDS_REVIEW = 'needs_review';

    protected $fillable = [
        'content_author_id',
        'traffic_sign_category_id',
        'reviewer_user_id',
        'code',
        'slug',
        'name',
        'intro_definition',
        'meaning',
        'placement',
        'driver_behavior',
        'legal_summary',
        'legal_reference_label',
        'legal_reference_url',
        'fine_summary',
        'common_mistakes',
        'editorial_notes',
        'review_notes',
        'source_notes',
        'faq_items',
        'meta_title',
        'meta_description',
        'image_path',
        'image_alt',
        'image_width',
        'image_height',
        'og_image_path',
        'og_image_alt',
        'og_image_width',
        'og_image_height',
        'sort_order',
        'workflow_status',
        'reviewed_at',
        'source_checked_at',
        'freshness_review_due_at',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'faq_items' => 'array',
            'is_published' => 'boolean',
            'image_width' => 'integer',
            'image_height' => 'integer',
            'og_image_width' => 'integer',
            'og_image_height' => 'integer',
            'reviewed_at' => 'datetime',
            'source_checked_at' => 'datetime',
            'freshness_review_due_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(ContentAuthor::class, 'content_author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrafficSignCategory::class, 'traffic_sign_category_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function queryMapEntries(): HasMany
    {
        return $this->hasMany(TrafficSignQueryMapEntry::class);
    }

    public function legalReferences(): HasMany
    {
        return $this->hasMany(TrafficSignLegalReference::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function publicCode(): string
    {
        $code = trim((string) $this->code);

        return self::PUBLIC_CODE_OVERRIDES[$code] ?? $code;
    }

    public function publicTitle(): string
    {
        $title = trim(implode(' ', array_filter([
            $this->publicCode(),
            trim((string) $this->name),
        ], fn (string $value): bool => $value !== '')));

        return $title !== '' ? $title : 'Znak drogowy';
    }

    public function publicImageAlt(): string
    {
        $alt = trim((string) $this->image_alt);

        return $alt !== '' ? $alt : $this->publicTitle();
    }

    public function publicOgImageAlt(): string
    {
        $alt = trim((string) $this->og_image_alt);

        return $alt !== '' ? $alt : $this->publicImageAlt();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    public static function workflowOptions(): array
    {
        return [
            static::WORKFLOW_DRAFT => 'Szkic',
            static::WORKFLOW_IN_REVIEW => 'W review',
            static::WORKFLOW_PUBLISHED => 'Opublikowany',
            static::WORKFLOW_NEEDS_REVIEW => 'Wymaga przegladu',
        ];
    }

    public function workflowLabel(): string
    {
        return static::workflowOptions()[$this->workflow_status] ?? 'Nieznany';
    }

    public function workflowColor(): string
    {
        return match ($this->workflow_status) {
            static::WORKFLOW_PUBLISHED => 'success',
            static::WORKFLOW_NEEDS_REVIEW => 'danger',
            static::WORKFLOW_IN_REVIEW => 'warning',
            default => 'gray',
        };
    }

    public function sourceVerificationLabel(): string
    {
        return $this->source_checked_at === null ? 'Do sprawdzenia' : 'Potwierdzone';
    }

    public function sourceVerificationColor(): string
    {
        return $this->source_checked_at === null ? 'warning' : 'success';
    }

    public function freshnessStateLabel(): string
    {
        if ($this->freshness_review_due_at === null) {
            return 'Brak terminu';
        }

        if ($this->freshness_review_due_at->isPast() && ! $this->freshness_review_due_at->isToday()) {
            return 'Po terminie';
        }

        if ($this->freshness_review_due_at->isToday()) {
            return 'Review dzisiaj';
        }

        if ($this->freshness_review_due_at->lte(now()->addDays(14))) {
            return 'Wkrótce review';
        }

        return 'Aktualne';
    }

    public function freshnessStateColor(): string
    {
        return match ($this->freshnessStateLabel()) {
            'Aktualne' => 'success',
            'Wkrótce review', 'Review dzisiaj', 'Brak terminu' => 'warning',
            default => 'danger',
        };
    }

    public function needsFreshnessReview(): bool
    {
        return $this->freshness_review_due_at !== null
            && $this->freshness_review_due_at->lte(now());
    }

    /**
     * @return list<array{label: string, complete: bool}>
     */
    public function publicationChecklistItems(): array
    {
        $faqCount = collect($this->faq_items ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->count();

        return [
            [
                'label' => 'Definicja otwierająca',
                'complete' => filled($this->intro_definition),
            ],
            [
                'label' => 'Sekcja znaczenia znaku',
                'complete' => filled($this->meaning),
            ],
            [
                'label' => 'Sekcja zachowania kierowcy',
                'complete' => filled($this->driver_behavior),
            ],
            [
                'label' => 'Sekcja najczęstszych błędów',
                'complete' => filled($this->common_mistakes),
            ],
            [
                'label' => 'Kontekst prawny ze źródłem',
                'complete' => filled($this->legal_summary)
                    && filled($this->legal_reference_label)
                    && filled($this->legal_reference_url),
            ],
            [
                'label' => 'Minimum 2 pytania FAQ',
                'complete' => $faqCount >= 2,
            ],
            [
                'label' => 'Meta title i description',
                'complete' => filled($this->meta_title) && filled($this->meta_description),
            ],
            [
                'label' => 'Assety i metadata obrazu',
                'complete' => filled($this->image_path)
                    && filled($this->image_alt)
                    && filled($this->image_width)
                    && filled($this->image_height)
                    && filled($this->og_image_path)
                    && filled($this->og_image_alt)
                    && filled($this->og_image_width)
                    && filled($this->og_image_height),
            ],
            [
                'label' => 'Źródła potwierdzone',
                'complete' => $this->source_checked_at !== null,
            ],
            [
                'label' => 'Termin kolejnego review',
                'complete' => $this->freshness_review_due_at !== null,
            ],
        ];
    }

    public function publicationChecklistCompletedCount(): int
    {
        return collect($this->publicationChecklistItems())
            ->where('complete', true)
            ->count();
    }

    public function publicationChecklistTotalCount(): int
    {
        return count($this->publicationChecklistItems());
    }

    public function publicationChecklistCompletionLabel(): string
    {
        return $this->publicationChecklistCompletedCount().'/'.$this->publicationChecklistTotalCount().' gotowe';
    }

    /**
     * @return list<string>
     */
    public function publicationChecklistMissingLabels(): array
    {
        return collect($this->publicationChecklistItems())
            ->filter(fn (array $item): bool => ! $item['complete'])
            ->map(fn (array $item): string => $item['label'])
            ->values()
            ->all();
    }

    public function hasCompletePublicationChecklist(): bool
    {
        return $this->publicationChecklistMissingLabels() === [];
    }
}

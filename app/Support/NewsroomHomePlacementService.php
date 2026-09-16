<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentCategory;
use App\Models\ContentHomePlacement;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class NewsroomHomePlacementService
{
    private const LOCK_PREFIX = 'newsroom:home-placement:';

    public function __construct(
        private readonly PostgresTransactionAdvisoryLock $advisoryLock,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ContentHomePlacement
    {
        return DB::transaction(function () use ($attributes): ContentHomePlacement {
            $normalized = $this->normalizeAttributes($attributes);
            $this->acquireTupleLocks([$this->tupleFor($normalized)]);
            $this->assertNoOverlap($normalized);

            return ContentHomePlacement::query()
                ->create($normalized)
                ->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(
        ContentHomePlacement $placement,
        array $attributes,
    ): ContentHomePlacement {
        return DB::transaction(function () use ($placement, $attributes): ContentHomePlacement {
            $locked = ContentHomePlacement::query()
                ->whereKey($placement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $current = [
                'surface_key' => $locked->surface_key,
                'slot_key' => $locked->slot_key,
                'context_key' => $locked->context_key,
                'position' => $locked->position,
                'article_id' => $locked->article_id,
                'starts_at' => $locked->starts_at,
                'ends_at' => $locked->ends_at,
                'created_by_user_id' => $locked->created_by_user_id,
                'updated_by_user_id' => $locked->updated_by_user_id,
            ];

            $normalized = $this->normalizeAttributes([
                ...$current,
                ...$attributes,
            ]);

            $this->acquireTupleLocks([
                $this->tupleFor($current),
                $this->tupleFor($normalized),
            ]);

            $this->assertNoOverlap($normalized, (int) $locked->getKey());

            $locked->fill($normalized);
            $locked->save();

            return $locked->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{
     *     surface_key: string,
     *     slot_key: string,
     *     context_key: ?string,
     *     position: int,
     *     article_id: int,
     *     starts_at: ?Carbon,
     *     ends_at: ?Carbon,
     *     created_by_user_id: mixed,
     *     updated_by_user_id: mixed
     * }
     */
    private function normalizeAttributes(array $attributes): array
    {
        $surfaceKey = trim((string) ($attributes['surface_key'] ?? ContentHomePlacement::SURFACE_NEWSROOM_HOME));

        if ($surfaceKey !== ContentHomePlacement::SURFACE_NEWSROOM_HOME) {
            throw new InvalidArgumentException("Unsupported newsroom home placement surface [{$surfaceKey}].");
        }

        $slotKey = trim((string) ($attributes['slot_key'] ?? ''));

        if (! in_array($slotKey, ContentHomePlacement::allowedSlotKeys(), true)) {
            throw new InvalidArgumentException("Unsupported newsroom home placement slot [{$slotKey}].");
        }

        $contextKey = array_key_exists('context_key', $attributes)
            ? trim((string) ($attributes['context_key'] ?? ''))
            : '';

        $contextKey = $contextKey !== '' ? $contextKey : null;

        if ($slotKey === ContentHomePlacement::SLOT_CATEGORY_LEAD) {
            if ($contextKey === null) {
                throw new InvalidArgumentException('Category lead placement requires a category slug context.');
            }

            if (! ContentCategory::query()->where('slug', $contextKey)->exists()) {
                throw new InvalidArgumentException("Unknown category lead context [{$contextKey}].");
            }
        } elseif ($contextKey !== null) {
            throw new InvalidArgumentException("Placement slot [{$slotKey}] does not accept context_key in v1.");
        }

        $position = filter_var(
            $attributes['position'] ?? 0,
            FILTER_VALIDATE_INT,
        );

        if ($position === false || $position < 0 || $position > 32767) {
            throw new InvalidArgumentException('Placement position must be an integer between 0 and 32767.');
        }

        $articleId = filter_var(
            $attributes['article_id'] ?? null,
            FILTER_VALIDATE_INT,
        );

        if ($articleId === false || $articleId < 1) {
            throw new InvalidArgumentException('Placement article_id must be a positive integer.');
        }

        $article = ContentArticle::query()->find($articleId);

        if ($article === null) {
            throw new InvalidArgumentException("Placement article [{$articleId}] does not exist.");
        }

        if (
            $slotKey === ContentHomePlacement::SLOT_GUIDES_LEAD
            && $article->type !== ContentArticleType::Guide
        ) {
            throw new DomainException('Guides lead placement must target a guide article.');
        }

        if ($slotKey === ContentHomePlacement::SLOT_CATEGORY_LEAD) {
            $categorySlug = $article->category()->value('slug');

            if ($categorySlug !== $contextKey) {
                throw new DomainException('Category lead placement must target an article from its context category.');
            }
        }

        $startsAt = $this->normalizeDate($attributes['starts_at'] ?? null);
        $endsAt = $this->normalizeDate($attributes['ends_at'] ?? null);

        if ($startsAt !== null && $endsAt !== null && $endsAt->lte($startsAt)) {
            throw new InvalidArgumentException('Placement ends_at must be later than starts_at.');
        }

        return [
            'surface_key' => $surfaceKey,
            'slot_key' => $slotKey,
            'context_key' => $contextKey,
            'position' => $position,
            'article_id' => (int) $articleId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'created_by_user_id' => $attributes['created_by_user_id'] ?? null,
            'updated_by_user_id' => $attributes['updated_by_user_id'] ?? null,
        ];
    }

    private function normalizeDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::parse($value->format(DATE_ATOM));
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('Placement time must be a date-time value.');
        }

        return Carbon::parse($value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertNoOverlap(array $attributes, ?int $ignorePlacementId = null): void
    {
        $query = ContentHomePlacement::query()
            ->where('surface_key', $attributes['surface_key'])
            ->where('slot_key', $attributes['slot_key'])
            ->where('position', $attributes['position'])
            ->where('context_key', $attributes['context_key'])
            ->lockForUpdate();

        if ($ignorePlacementId !== null) {
            $query->whereKeyNot($ignorePlacementId);
        }

        foreach ($query->get() as $existing) {
            if ($this->intervalsOverlap(
                $attributes['starts_at'],
                $attributes['ends_at'],
                $existing->starts_at,
                $existing->ends_at,
            )) {
                throw new DomainException('Newsroom home placement overlaps an existing placement for the same slot tuple.');
            }
        }
    }

    private function intervalsOverlap(
        ?DateTimeInterface $leftStart,
        ?DateTimeInterface $leftEnd,
        ?DateTimeInterface $rightStart,
        ?DateTimeInterface $rightEnd,
    ): bool {
        if ($leftEnd !== null && $rightStart !== null && $leftEnd <= $rightStart) {
            return false;
        }

        if ($rightEnd !== null && $leftStart !== null && $rightEnd <= $leftStart) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{surface_key: string, slot_key: string, context_key: ?string, position: int}
     */
    private function tupleFor(array $attributes): array
    {
        return [
            'surface_key' => (string) $attributes['surface_key'],
            'slot_key' => (string) $attributes['slot_key'],
            'context_key' => isset($attributes['context_key'])
                ? (string) $attributes['context_key']
                : null,
            'position' => (int) $attributes['position'],
        ];
    }

    /**
     * @param  iterable<array{surface_key: string, slot_key: string, context_key: ?string, position: int}>  $tuples
     */
    private function acquireTupleLocks(iterable $tuples): void
    {
        $keys = [];

        foreach ($tuples as $tuple) {
            $keys[] = self::lockKey(
                $tuple['surface_key'],
                $tuple['slot_key'],
                $tuple['context_key'],
                $tuple['position'],
            );
        }

        $this->advisoryLock->acquire($keys);
    }

    public static function lockKey(
        string $surfaceKey,
        string $slotKey,
        ?string $contextKey,
        int $position,
    ): string {
        return self::LOCK_PREFIX.implode(':', [
            $surfaceKey,
            $slotKey,
            $contextKey ?? '-',
            (string) $position,
        ]);
    }
}

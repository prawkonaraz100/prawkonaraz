<?php

namespace App\Support;

use InvalidArgumentException;

final class NewsroomBodyEditorAdapter
{
    /**
     * Convert Filament Builder state into the canonical body_blocks document.
     *
     * @return list<array{type: string, data: array<string, mixed>, key?: string}>
     */
    public static function toCanonical(
        mixed $blocks,
        int $schemaVersion = NewsroomBodyContract::CURRENT_SCHEMA_VERSION,
    ): array {
        if ($blocks === null) {
            $blocks = [];
        }

        if (! is_array($blocks)) {
            throw new InvalidArgumentException('Article body blocks must be an array.');
        }

        $prepared = [];

        foreach ($blocks as $sourceKey => $block) {
            if (! is_array($block)) {
                $prepared[$sourceKey] = $block;

                continue;
            }

            $type = $block['type'] ?? null;
            $data = $block['data'] ?? null;

            if (is_string($type) && is_array($data)) {
                $block['data'] = self::prepareBlockDataForContract($type, $data);
            }

            $prepared[$sourceKey] = $block;
        }

        return NewsroomBodyContract::normalize($prepared, $schemaVersion);
    }

    /**
     * Convert canonical stored body_blocks into Builder-friendly form state.
     *
     * Canonical block keys stay as explicit item fields. Filament Builder UUID
     * array keys are ephemeral UI state and must never become domain identity.
     *
     * @return array<int|string, array{type: string, data: array<string, mixed>}>
     */
    public static function toBuilder(
        mixed $blocks,
        int $schemaVersion = NewsroomBodyContract::CURRENT_SCHEMA_VERSION,
    ): array {
        if ($blocks === null) {
            return [];
        }

        if (! is_array($blocks)) {
            throw new InvalidArgumentException('Stored article body blocks must be an array.');
        }

        $canonical = NewsroomBodyContract::normalize($blocks, $schemaVersion);
        $builder = [];

        foreach ($canonical as $block) {
            if (($block['type'] ?? null) === NewsroomBodyContract::BLOCK_TABLE) {
                $rows = $block['data']['rows'] ?? [];

                $block['data']['rows'] = array_map(
                    static fn (array $row): array => ['cells' => $row],
                    $rows,
                );
            }

            // Keep the canonical key inside the item itself. Filament Builder
            // owns separate ephemeral UUID keys for its Livewire state and
            // intentionally strips those UUID array keys during dehydration.
            $builder[] = $block;
        }

        return $builder;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeArticleData(
        array $data,
        int $schemaVersion = NewsroomBodyContract::CURRENT_SCHEMA_VERSION,
    ): array {
        $data['body_blocks'] = self::toCanonical(
            $data['body_blocks'] ?? [],
            $schemaVersion,
        );
        $data['body_schema_version'] = $schemaVersion;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrateArticleData(array $data): array
    {
        $schemaVersion = self::schemaVersion($data['body_schema_version'] ?? null);
        $data['body_blocks'] = self::toBuilder($data['body_blocks'] ?? [], $schemaVersion);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function prepareBlockDataForContract(string $type, array $data): array
    {
        return match ($type) {
            NewsroomBodyContract::BLOCK_IMAGE => self::prepareImage($data),
            NewsroomBodyContract::BLOCK_RELATED_ARTICLE => self::coerceIntegerField($data, 'article_id'),
            NewsroomBodyContract::BLOCK_LEGAL_REFERENCE => self::coerceIntegerField($data, 'legal_unit_id'),
            NewsroomBodyContract::BLOCK_QUESTION_GROUP => self::coerceIntegerListField($data, 'question_ids'),
            NewsroomBodyContract::BLOCK_TRAFFIC_SIGN_GROUP => self::coerceIntegerListField($data, 'traffic_sign_ids'),
            NewsroomBodyContract::BLOCK_TABLE => self::prepareTable($data),
            default => $data,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function prepareImage(array $data): array
    {
        foreach (['width', 'height'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = self::integerFromForm($data[$field]);
            }
        }

        foreach (['focal_x', 'focal_y'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = self::floatFromForm($data[$field]);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function prepareTable(array $data): array
    {
        $rows = $data['rows'] ?? null;

        if (! is_array($rows)) {
            return $data;
        }

        $data['rows'] = array_values(array_map(
            static function (mixed $row): mixed {
                if (is_array($row) && array_key_exists('cells', $row)) {
                    return $row['cells'];
                }

                return $row;
            },
            $rows,
        ));

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function coerceIntegerField(array $data, string $field): array
    {
        if (array_key_exists($field, $data)) {
            $data[$field] = self::integerFromForm($data[$field]);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function coerceIntegerListField(array $data, string $field): array
    {
        if (! array_key_exists($field, $data) || ! is_array($data[$field])) {
            return $data;
        }

        $data[$field] = array_values(array_map(
            static fn (mixed $value): mixed => self::integerFromForm($value),
            $data[$field],
        ));

        return $data;
    }

    private static function integerFromForm(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/\A[0-9]+\z/', $value) === 1) {
            return (int) $value;
        }

        return $value;
    }

    private static function floatFromForm(mixed $value): mixed
    {
        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }

    private static function schemaVersion(mixed $value): int
    {
        if ($value === null || $value === '') {
            return NewsroomBodyContract::CURRENT_SCHEMA_VERSION;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/\A[0-9]+\z/', $value) === 1) {
            return (int) $value;
        }

        throw new InvalidArgumentException('Article body schema version must be an integer.');
    }
}

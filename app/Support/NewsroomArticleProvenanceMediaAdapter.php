<?php

namespace App\Support;

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

final class NewsroomArticleProvenanceMediaAdapter
{
    /**
     * Validate and normalize provenance, regulatory and article-media fields.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeArticleData(array $data): array
    {
        if (array_key_exists('origin_type', $data)) {
            $origin = $data['origin_type'] instanceof ContentArticleOriginType
                ? $data['origin_type']
                : ContentArticleOriginType::tryFrom((string) $data['origin_type']);

            if ($origin === null) {
                throw new InvalidArgumentException('Unsupported content article origin_type.');
            }

            $data['origin_type'] = $origin->value;
        }

        if (array_key_exists('regulatory_status', $data)) {
            $status = $data['regulatory_status'] instanceof ContentArticleRegulatoryStatus
                ? $data['regulatory_status']
                : ContentArticleRegulatoryStatus::tryFrom((string) $data['regulatory_status']);

            if ($status === null) {
                throw new InvalidArgumentException('Unsupported content article regulatory_status.');
            }

            $data['regulatory_status'] = $status->value;
        }

        foreach ([
            'change_summary',
            'applies_to',
            'exam_impact',
            'hero_image_alt',
            'hero_image_caption',
            'og_image_alt',
            'image_credit',
            'image_license_note',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = self::nullableTrimmedString($data[$field]);
            }
        }

        $data = self::normalizeImage(
            $data,
            'hero_image_path',
            'hero_image_width',
            'hero_image_height',
        );

        $data = self::normalizeImage(
            $data,
            'og_image_path',
            'og_image_width',
            'og_image_height',
        );

        self::normalizeFocalPoint($data);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeImage(
        array $data,
        string $pathField,
        string $widthField,
        string $heightField,
    ): array {
        if (! array_key_exists($pathField, $data)) {
            return $data;
        }

        $path = self::nullableTrimmedString($data[$pathField]);

        if ($path === null) {
            $data[$pathField] = null;
            $data[$widthField] = null;
            $data[$heightField] = null;

            return $data;
        }

        try {
            $storage = app(NewsroomMediaStorage::class);
            $verified = $storage->inspectStoredImage($path);
            $storage->publicUrl($path);
        } catch (ValidationException|RuntimeException $exception) {
            throw new InvalidArgumentException(
                "{$pathField} must reference a verified managed newsroom asset with a stable public URL: {$exception->getMessage()}",
                previous: $exception,
            );
        }

        $data[$pathField] = $verified['path'];
        $data[$widthField] = $verified['width'];
        $data[$heightField] = $verified['height'];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function normalizeFocalPoint(array &$data): void
    {
        $hasX = array_key_exists('hero_focal_x', $data);
        $hasY = array_key_exists('hero_focal_y', $data);

        if (! $hasX && ! $hasY) {
            return;
        }

        $x = self::nullableFloat($data['hero_focal_x'] ?? null, 'hero_focal_x');
        $y = self::nullableFloat($data['hero_focal_y'] ?? null, 'hero_focal_y');

        if (($x === null) xor ($y === null)) {
            throw new InvalidArgumentException('Hero focal point requires both X and Y coordinates.');
        }

        foreach (['hero_focal_x' => $x, 'hero_focal_y' => $y] as $field => $value) {
            if ($value !== null && ($value < 0.0 || $value > 1.0)) {
                throw new InvalidArgumentException("{$field} must be between 0 and 1.");
            }

            $data[$field] = $value;
        }

        if (blank($data['hero_image_path'] ?? null)) {
            $data['hero_focal_x'] = null;
            $data['hero_focal_y'] = null;
        }
    }

    private static function nullableTrimmedString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('Newsroom article text metadata must be a string.');
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function nullableFloat(mixed $value, string $field): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("{$field} must be numeric.");
        }

        return (float) $value;
    }
}

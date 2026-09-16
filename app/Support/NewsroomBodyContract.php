<?php

namespace App\Support;

use InvalidArgumentException;

final class NewsroomBodyContract
{
    public const CURRENT_SCHEMA_VERSION = 1;

    public const EDITOR_COMPONENT = 'Filament\\Forms\\Components\\Builder';

    public const RICH_TEXT_COMPONENT = 'Filament\\Forms\\Components\\RichEditor';

    public const RICH_TEXT_FORMAT = 'tiptap-json';

    public const BLOCK_RICH_TEXT = 'rich_text';
    public const BLOCK_IMAGE = 'image';
    public const BLOCK_QUOTE = 'quote';
    public const BLOCK_TABLE = 'table';
    public const BLOCK_CONTEXT = 'context';
    public const BLOCK_RELATED_ARTICLE = 'related_article';
    public const BLOCK_LEGAL_REFERENCE = 'legal_reference';
    public const BLOCK_QUESTION_GROUP = 'question_group';
    public const BLOCK_TRAFFIC_SIGN_GROUP = 'traffic_sign_group';
    public const BLOCK_PRODUCT_CTA = 'product_cta';
    public const BLOCK_EMBED = 'embed';

    /**
     * @return list<string>
     */
    public static function enabledBlockTypes(): array
    {
        return [
            self::BLOCK_RICH_TEXT,
            self::BLOCK_IMAGE,
            self::BLOCK_QUOTE,
            self::BLOCK_TABLE,
            self::BLOCK_CONTEXT,
            self::BLOCK_RELATED_ARTICLE,
            self::BLOCK_LEGAL_REFERENCE,
            self::BLOCK_QUESTION_GROUP,
            self::BLOCK_TRAFFIC_SIGN_GROUP,
            self::BLOCK_PRODUCT_CTA,
        ];
    }

    /**
     * @return list<string>
     */
    public static function disabledBlockTypes(): array
    {
        return [self::BLOCK_EMBED];
    }

    /**
     * Executable payload contract for N0/N1/N2 integration.
     *
     * @return array<string, array{required: list<string>, optional: list<string>}>
     */
    public static function blockSchemas(): array
    {
        return [
            self::BLOCK_RICH_TEXT => [
                'required' => ['content'],
                'optional' => [],
            ],
            self::BLOCK_IMAGE => [
                'required' => ['path', 'alt'],
                'optional' => ['caption', 'credit', 'width', 'height', 'focal_x', 'focal_y'],
            ],
            self::BLOCK_QUOTE => [
                'required' => ['text', 'attribution'],
                'optional' => ['source_url'],
            ],
            self::BLOCK_TABLE => [
                'required' => ['headers', 'rows'],
                'optional' => ['caption'],
            ],
            self::BLOCK_CONTEXT => [
                'required' => ['variant', 'text'],
                'optional' => ['title'],
            ],
            self::BLOCK_RELATED_ARTICLE => [
                'required' => ['article_id'],
                'optional' => [],
            ],
            self::BLOCK_LEGAL_REFERENCE => [
                'required' => ['legal_unit_id'],
                'optional' => [],
            ],
            self::BLOCK_QUESTION_GROUP => [
                'required' => ['question_ids'],
                'optional' => [],
            ],
            self::BLOCK_TRAFFIC_SIGN_GROUP => [
                'required' => ['traffic_sign_ids'],
                'optional' => [],
            ],
            self::BLOCK_PRODUCT_CTA => [
                'required' => ['kind'],
                'optional' => [],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function contextVariants(): array
    {
        return [
            'dlaczego_to_wazne',
            'co_sie_zmienia',
            'uwaga',
            'metodologia',
        ];
    }

    /**
     * @return list<string>
     */
    public static function productCtaKinds(): array
    {
        return [
            'test',
            'related_questions',
            'learning',
        ];
    }

    /**
     * Normalize and validate the canonical body_blocks document.
     *
     * The output contains no arbitrary HTML/CSS/JS. Rich text stays structured
     * TipTap JSON; presentation HTML is generated later by the public renderer.
     *
     * Accepts both Filament Builder state (UUID associative keys) and the
     * canonical stored list (explicit optional key field).
     *
     * @param  array<int|string, mixed>  $blocks
     * @return list<array{type: string, data: array<string, mixed>, key?: string}>
     */
    public static function normalize(array $blocks, int $schemaVersion = self::CURRENT_SCHEMA_VERSION): array
    {
        if ($schemaVersion !== self::CURRENT_SCHEMA_VERSION) {
            throw new InvalidArgumentException("Unsupported newsroom body schema version: {$schemaVersion}.");
        }

        $normalized = [];

        foreach ($blocks as $sourceKey => $block) {
            $index = count($normalized);

            if (! is_array($block)) {
                throw new InvalidArgumentException("Body block {$index} must be an object.");
            }

            self::assertKeys($block, ['type', 'data'], ['key'], "body block {$index}");

            $type = $block['type'] ?? null;

            if (! is_string($type) || $type === '') {
                throw new InvalidArgumentException("Body block {$index} must have a non-empty type.");
            }

            if (in_array($type, self::disabledBlockTypes(), true)) {
                throw new InvalidArgumentException("Body block type [{$type}] is disabled in newsroom v1.");
            }

            if (! in_array($type, self::enabledBlockTypes(), true)) {
                throw new InvalidArgumentException("Unsupported newsroom body block type [{$type}].");
            }

            $data = $block['data'] ?? null;

            if (! is_array($data)) {
                throw new InvalidArgumentException("Body block {$index} data must be an object.");
            }

            $item = [
                'type' => $type,
                'data' => self::normalizeBlockData($type, $data, $index),
            ];

            $key = $block['key'] ?? (is_string($sourceKey) ? $sourceKey : null);

            if ($key !== null) {
                if (! is_string($key) || preg_match('/\A[A-Za-z0-9_-]{1,100}\z/', $key) !== 1) {
                    throw new InvalidArgumentException("Body block {$index} key has an invalid format.");
                }

                $item['key'] = $key;
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeBlockData(string $type, array $data, int $index): array
    {
        $schema = self::blockSchemas()[$type];
        self::assertKeys($data, $schema['required'], $schema['optional'], "body block {$index} [{$type}] data");

        return match ($type) {
            self::BLOCK_RICH_TEXT => [
                'content' => self::normalizeRichTextDocument(self::arrayValue($data, 'content', $type)),
            ],
            self::BLOCK_IMAGE => self::normalizeImage($data),
            self::BLOCK_QUOTE => self::normalizeQuote($data),
            self::BLOCK_TABLE => self::normalizeTable($data),
            self::BLOCK_CONTEXT => self::normalizeContext($data),
            self::BLOCK_RELATED_ARTICLE => [
                'article_id' => self::positiveInteger($data['article_id'] ?? null, 'article_id'),
            ],
            self::BLOCK_LEGAL_REFERENCE => [
                'legal_unit_id' => self::positiveInteger($data['legal_unit_id'] ?? null, 'legal_unit_id'),
            ],
            self::BLOCK_QUESTION_GROUP => [
                'question_ids' => self::positiveIntegerList($data['question_ids'] ?? null, 'question_ids'),
            ],
            self::BLOCK_TRAFFIC_SIGN_GROUP => [
                'traffic_sign_ids' => self::positiveIntegerList($data['traffic_sign_ids'] ?? null, 'traffic_sign_ids'),
            ],
            self::BLOCK_PRODUCT_CTA => self::normalizeProductCta($data),
            default => throw new InvalidArgumentException("Unsupported newsroom body block type [{$type}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeImage(array $data): array
    {
        $path = self::requiredString($data['path'] ?? null, 'path');

        if (
            str_contains($path, "\0")
            || str_contains($path, '\\')
            || str_contains($path, '..')
            || str_starts_with($path, '/')
            || preg_match('/\A[a-z][a-z0-9+.-]*:/i', $path) === 1
        ) {
            throw new InvalidArgumentException('Image path must be a storage-relative immutable asset path.');
        }

        $width = self::nullablePositiveInteger($data['width'] ?? null, 'width');
        $height = self::nullablePositiveInteger($data['height'] ?? null, 'height');
        $focalX = self::nullableUnitFloat($data['focal_x'] ?? null, 'focal_x');
        $focalY = self::nullableUnitFloat($data['focal_y'] ?? null, 'focal_y');

        if (($focalX === null) !== ($focalY === null)) {
            throw new InvalidArgumentException('Image focal_x and focal_y must be provided together.');
        }

        return [
            'path' => $path,
            'alt' => self::requiredString($data['alt'] ?? null, 'alt'),
            'caption' => self::nullableString($data['caption'] ?? null, 'caption'),
            'credit' => self::nullableString($data['credit'] ?? null, 'credit'),
            'width' => $width,
            'height' => $height,
            'focal_x' => $focalX,
            'focal_y' => $focalY,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeQuote(array $data): array
    {
        $sourceUrl = self::nullableString($data['source_url'] ?? null, 'source_url');

        return [
            'text' => self::requiredString($data['text'] ?? null, 'text'),
            'attribution' => self::requiredString($data['attribution'] ?? null, 'attribution'),
            'source_url' => $sourceUrl === null ? null : self::safeHref($sourceUrl),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeTable(array $data): array
    {
        $headers = self::stringList($data['headers'] ?? null, 'headers', requireNonEmpty: true);
        $rows = $data['rows'] ?? null;

        if (! is_array($rows) || $rows === []) {
            throw new InvalidArgumentException('Table rows must be a non-empty array.');
        }

        $normalizedRows = [];

        foreach (array_values($rows) as $rowIndex => $row) {
            $normalizedRow = self::stringList($row, "rows.{$rowIndex}", requireNonEmpty: false);

            if (count($normalizedRow) !== count($headers)) {
                throw new InvalidArgumentException("Table row {$rowIndex} must have the same number of cells as headers.");
            }

            $normalizedRows[] = $normalizedRow;
        }

        return [
            'caption' => self::nullableString($data['caption'] ?? null, 'caption'),
            'headers' => $headers,
            'rows' => $normalizedRows,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeContext(array $data): array
    {
        $variant = self::requiredString($data['variant'] ?? null, 'variant');

        if (! in_array($variant, self::contextVariants(), true)) {
            throw new InvalidArgumentException("Unsupported context variant [{$variant}].");
        }

        return [
            'variant' => $variant,
            'title' => self::nullableString($data['title'] ?? null, 'title'),
            'text' => self::requiredString($data['text'] ?? null, 'text'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeProductCta(array $data): array
    {
        $kind = self::requiredString($data['kind'] ?? null, 'kind');

        if (! in_array($kind, self::productCtaKinds(), true)) {
            throw new InvalidArgumentException("Unsupported product CTA kind [{$kind}].");
        }

        return ['kind' => $kind];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public static function normalizeRichTextDocument(array $document): array
    {
        $normalized = self::normalizeRichTextNode($document, 'rich_text.content');

        if (($normalized['type'] ?? null) !== 'doc') {
            throw new InvalidArgumentException('Rich text root node must be [doc].');
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private static function normalizeRichTextNode(array $node, string $path): array
    {
        $type = $node['type'] ?? null;

        if (! is_string($type) || ! in_array($type, [
            'doc',
            'paragraph',
            'heading',
            'bulletList',
            'orderedList',
            'listItem',
            'text',
            'hardBreak',
        ], true)) {
            throw new InvalidArgumentException("Unsupported rich text node at {$path}.");
        }

        self::assertKeys($node, ['type'], ['content', 'text', 'marks', 'attrs'], "rich text node {$path}");

        $normalized = ['type' => $type];

        if ($type === 'text') {
            if (! array_key_exists('text', $node) || ! is_string($node['text'])) {
                throw new InvalidArgumentException("Rich text node {$path} must contain text.");
            }

            if (array_key_exists('content', $node)) {
                throw new InvalidArgumentException("Text node {$path} cannot contain child nodes.");
            }

            $normalized['text'] = $node['text'];

            if (array_key_exists('marks', $node)) {
                if (! is_array($node['marks'])) {
                    throw new InvalidArgumentException("Rich text marks at {$path} must be an array.");
                }

                $normalizedMarks = [];

                foreach (array_values($node['marks']) as $markIndex => $mark) {
                    $normalizedMarks[] = self::normalizeRichTextMark($mark, "{$path}.marks.{$markIndex}");
                }

                $normalized['marks'] = $normalizedMarks;
            }

            return $normalized;
        }

        if (array_key_exists('text', $node) || array_key_exists('marks', $node)) {
            throw new InvalidArgumentException("Rich text node {$path} cannot contain text/marks fields.");
        }

        if ($type === 'heading') {
            $attrs = $node['attrs'] ?? null;

            if (! is_array($attrs) || ! isset($attrs['level']) || ! is_int($attrs['level']) || ! in_array($attrs['level'], [2, 3], true)) {
                throw new InvalidArgumentException("Heading {$path} must have level 2 or 3.");
            }

            $normalized['attrs'] = ['level' => $attrs['level']];
        } elseif ($type === 'orderedList') {
            $attrs = $node['attrs'] ?? [];
            $start = $attrs['start'] ?? 1;

            if (! is_int($start) || $start < 1) {
                throw new InvalidArgumentException("Ordered list {$path} start must be a positive integer.");
            }

            $normalized['attrs'] = ['start' => $start];
        }

        $children = $node['content'] ?? [];

        if (! is_array($children)) {
            throw new InvalidArgumentException("Rich text node {$path} content must be an array.");
        }

        $allowedChildren = match ($type) {
            'doc' => ['paragraph', 'heading', 'bulletList', 'orderedList'],
            'paragraph', 'heading' => ['text', 'hardBreak'],
            'bulletList', 'orderedList' => ['listItem'],
            'listItem' => ['paragraph', 'bulletList', 'orderedList'],
            'hardBreak' => [],
            default => [],
        };

        if ($type === 'hardBreak' && $children !== []) {
            throw new InvalidArgumentException("Hard break {$path} cannot contain child nodes.");
        }

        if ($children !== []) {
            $normalizedChildren = [];

            foreach (array_values($children) as $childIndex => $child) {
                if (! is_array($child)) {
                    throw new InvalidArgumentException("Rich text child {$path}.content.{$childIndex} must be an object.");
                }

                $childType = $child['type'] ?? null;

                if (! is_string($childType) || ! in_array($childType, $allowedChildren, true)) {
                    throw new InvalidArgumentException("Rich text child type at {$path}.content.{$childIndex} is not allowed.");
                }

                $normalizedChildren[] = self::normalizeRichTextNode($child, "{$path}.content.{$childIndex}");
            }

            $normalized['content'] = $normalizedChildren;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeRichTextMark(mixed $mark, string $path): array
    {
        if (! is_array($mark)) {
            throw new InvalidArgumentException("Rich text mark {$path} must be an object.");
        }

        self::assertKeys($mark, ['type'], ['attrs'], "rich text mark {$path}");

        $type = $mark['type'] ?? null;

        if (! is_string($type) || ! in_array($type, ['bold', 'italic', 'link'], true)) {
            throw new InvalidArgumentException("Unsupported rich text mark at {$path}.");
        }

        if ($type !== 'link') {
            return ['type' => $type];
        }

        $attrs = $mark['attrs'] ?? null;

        if (! is_array($attrs) || ! isset($attrs['href']) || ! is_string($attrs['href'])) {
            throw new InvalidArgumentException("Rich text link {$path} must contain href.");
        }

        return [
            'type' => 'link',
            'attrs' => [
                'href' => self::safeHref($attrs['href']),
            ],
        ];
    }

    private static function safeHref(string $href): string
    {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '//')) {
            throw new InvalidArgumentException('Link href is not allowed.');
        }

        if (str_starts_with($href, '/') || str_starts_with($href, '#')) {
            return $href;
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);

        if (! is_string($scheme) || ! in_array(strtolower($scheme), ['http', 'https', 'mailto'], true)) {
            throw new InvalidArgumentException('Link href scheme is not allowed.');
        }

        return $href;
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private static function arrayValue(array $value, string $key, string $context): array
    {
        $item = $value[$key] ?? null;

        if (! is_array($item)) {
            throw new InvalidArgumentException("{$context}.{$key} must be an object.");
        }

        return $item;
    }

    private static function requiredString(mixed $value, string $field): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("{$field} must be a non-empty string.");
        }

        return trim($value);
    }

    private static function nullableString(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException("{$field} must be a string or null.");
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function positiveInteger(mixed $value, string $field): int
    {
        if (! is_int($value) || $value < 1) {
            throw new InvalidArgumentException("{$field} must be a positive integer.");
        }

        return $value;
    }

    private static function nullablePositiveInteger(mixed $value, string $field): ?int
    {
        if ($value === null) {
            return null;
        }

        return self::positiveInteger($value, $field);
    }

    private static function nullableUnitFloat(mixed $value, string $field): ?float
    {
        if ($value === null) {
            return null;
        }

        if (! is_int($value) && ! is_float($value)) {
            throw new InvalidArgumentException("{$field} must be numeric or null.");
        }

        $value = (float) $value;

        if ($value < 0 || $value > 1) {
            throw new InvalidArgumentException("{$field} must be between 0 and 1.");
        }

        return $value;
    }

    /**
     * @return list<int>
     */
    private static function positiveIntegerList(mixed $value, string $field): array
    {
        if (! is_array($value) || $value === []) {
            throw new InvalidArgumentException("{$field} must be a non-empty array.");
        }

        $normalized = array_values(array_map(
            fn (mixed $item): int => self::positiveInteger($item, $field),
            $value,
        ));

        if (count(array_unique($normalized)) !== count($normalized)) {
            throw new InvalidArgumentException("{$field} cannot contain duplicate IDs.");
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value, string $field, bool $requireNonEmpty): array
    {
        if (! is_array($value) || ($requireNonEmpty && $value === [])) {
            throw new InvalidArgumentException("{$field} must be an array.");
        }

        return array_values(array_map(
            function (mixed $item) use ($field): string {
                if (! is_string($item)) {
                    throw new InvalidArgumentException("{$field} values must be strings.");
                }

                return trim($item);
            },
            $value,
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $required
     * @param  list<string>  $optional
     */
    private static function assertKeys(array $payload, array $required, array $optional, string $context): void
    {
        $keys = array_keys($payload);
        $missing = array_diff($required, $keys);
        $unknown = array_diff($keys, [...$required, ...$optional]);

        if ($missing !== []) {
            throw new InvalidArgumentException("{$context} is missing fields: ".implode(', ', $missing).'.');
        }

        if ($unknown !== []) {
            throw new InvalidArgumentException("{$context} contains unsupported fields: ".implode(', ', $unknown).'.');
        }
    }
}

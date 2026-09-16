<?php

namespace App\Support;

use DateTimeInterface;
use DomainException;
use InvalidArgumentException;

final class NewsroomRouteContract
{
    public const FAMILY_NEWSROOM = 'newsroom';

    public const FAMILY_GUIDES = 'guides';

    public const SLUG_PATTERN = '[a-z0-9-]+';

    public const NEWSROOM_ARTICLE_SLUG_PATTERN = '(?!(?:kategoria|temat)(?:/|$))[a-z0-9-]+';

    /**
     * @var list<string>
     */
    public const NEWSROOM_RESERVED_ARTICLE_SLUGS = [
        'kategoria',
        'temat',
    ];

    /**
     * @var list<string>
     */
    public const NEWSROOM_TYPES = [
        'news',
        'explainer',
        'analysis',
        'report',
    ];

    /**
     * @var list<string>
     */
    public const GUIDE_TYPES = [
        'guide',
    ];

    public static function familyForType(string $type): string
    {
        return match ($type) {
            'guide' => self::FAMILY_GUIDES,
            'news', 'explainer', 'analysis', 'report' => self::FAMILY_NEWSROOM,
            default => throw new InvalidArgumentException("Unsupported content article type [{$type}]."),
        };
    }

    public static function canonicalPath(string $type, string $slug): string
    {
        self::assertValidArticleSlug($type, $slug);

        return match (self::familyForType($type)) {
            self::FAMILY_NEWSROOM => '/aktualnosci/'.$slug,
            self::FAMILY_GUIDES => '/poradniki/'.$slug,
        };
    }

    public static function assertTypeTransitionAllowed(
        string $currentType,
        string $nextType,
        ?DateTimeInterface $firstPublishedAt,
    ): void {
        if ($firstPublishedAt === null) {
            return;
        }

        if (self::familyForType($currentType) === self::familyForType($nextType)) {
            return;
        }

        throw new DomainException('Published article type cannot change its public route family.');
    }

    public static function isReservedNewsroomArticleSlug(string $slug): bool
    {
        return in_array($slug, self::NEWSROOM_RESERVED_ARTICLE_SLUGS, true);
    }

    private static function assertValidArticleSlug(string $type, string $slug): void
    {
        if (preg_match('/\\A'.self::SLUG_PATTERN.'\\z/', $slug) !== 1) {
            throw new InvalidArgumentException("Invalid content article slug [{$slug}].");
        }

        if (
            self::familyForType($type) === self::FAMILY_NEWSROOM
            && self::isReservedNewsroomArticleSlug($slug)
        ) {
            throw new InvalidArgumentException("Reserved newsroom article slug [{$slug}].");
        }
    }
}

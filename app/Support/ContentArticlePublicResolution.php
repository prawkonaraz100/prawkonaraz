<?php

namespace App\Support;

use App\Models\ContentArticle;
use Symfony\Component\HttpFoundation\Response;

final readonly class ContentArticlePublicResolution
{
    public const STATUS_VISIBLE = 'visible';

    public const STATUS_GONE = 'gone';

    public const STATUS_NOT_FOUND = 'not_found';

    private function __construct(
        public string $status,
        public ?ContentArticle $article = null,
    ) {}

    public static function visible(ContentArticle $article): self
    {
        return new self(self::STATUS_VISIBLE, $article);
    }

    public static function gone(ContentArticle $article): self
    {
        return new self(self::STATUS_GONE, $article);
    }

    public static function notFound(): self
    {
        return new self(self::STATUS_NOT_FOUND);
    }

    public function httpStatus(): int
    {
        return match ($this->status) {
            self::STATUS_VISIBLE => Response::HTTP_OK,
            self::STATUS_GONE => Response::HTTP_GONE,
            self::STATUS_NOT_FOUND => Response::HTTP_NOT_FOUND,
        };
    }

    public function isVisible(): bool
    {
        return $this->status === self::STATUS_VISIBLE;
    }

    public function isGone(): bool
    {
        return $this->status === self::STATUS_GONE;
    }

    public function isNotFound(): bool
    {
        return $this->status === self::STATUS_NOT_FOUND;
    }
}

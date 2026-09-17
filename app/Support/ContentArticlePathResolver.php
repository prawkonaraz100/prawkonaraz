<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentArticleRedirect;
use InvalidArgumentException;

final class ContentArticlePathResolver
{
    public function findPublicCanonical(string $family, string $slug): ?ContentArticle
    {
        if (! in_array($family, [
            NewsroomRouteContract::FAMILY_NEWSROOM,
            NewsroomRouteContract::FAMILY_GUIDES,
        ], true)) {
            throw new InvalidArgumentException("Unsupported newsroom route family [{$family}].");
        }

        $article = ContentArticle::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->first();

        if ($article === null) {
            return null;
        }

        $type = $article->type instanceof ContentArticleType
            ? $article->type->value
            : (string) $article->type;

        if (NewsroomRouteContract::familyForType($type) !== $family) {
            return null;
        }

        try {
            NewsroomRouteContract::canonicalPath($type, $slug);
        } catch (InvalidArgumentException) {
            return null;
        }

        return $article;
    }

    public function findRedirect(string $fromPath): ?ContentArticleRedirect
    {
        $fromPath = '/'.ltrim(trim($fromPath), '/');

        return ContentArticleRedirect::query()
            ->where('from_path', $fromPath)
            ->first();
    }

    public function findCanonicalRedirectTarget(string $fromPath): ?string
    {
        $fromPath = '/'.ltrim(trim($fromPath), '/');
        $redirect = $this->findRedirect($fromPath);

        if ($redirect === null || $redirect->http_status !== 301) {
            return null;
        }

        $article = $redirect->article;

        if (! $article instanceof ContentArticle) {
            return null;
        }

        $type = $article->type instanceof ContentArticleType
            ? $article->type->value
            : (string) $article->type;

        try {
            $canonicalPath = NewsroomRouteContract::canonicalPath($type, (string) $article->slug);
        } catch (InvalidArgumentException) {
            return null;
        }

        if ($canonicalPath === $fromPath || $redirect->to_path !== $canonicalPath) {
            return null;
        }

        return $canonicalPath;
    }
}

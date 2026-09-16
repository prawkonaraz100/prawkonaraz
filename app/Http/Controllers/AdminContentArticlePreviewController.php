<?php

namespace App\Http\Controllers;

use App\Models\ContentArticle;
use App\Support\MediaUrlResolver;
use App\Support\NewsroomBodyContract;
use App\Support\NewsroomRichTextHtmlRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminContentArticlePreviewController extends Controller
{
    public function __invoke(
        Request $request,
        ContentArticle $contentArticle,
        NewsroomRichTextHtmlRenderer $richTextRenderer,
        MediaUrlResolver $mediaUrlResolver,
    ): Response {
        abort_unless($request->user()?->isAdministrator(), 403);

        $contentArticle->loadMissing([
            'category:id,name,slug',
            'author:id,name,slug',
            'reviewer:id,name,slug',
        ]);

        $disk = (string) config('media.newsroom_disk', config('media.public_disk', 'public'));
        $bodyBlocks = NewsroomBodyContract::normalize(
            is_array($contentArticle->body_blocks) ? $contentArticle->body_blocks : [],
            (int) ($contentArticle->body_schema_version ?? NewsroomBodyContract::CURRENT_SCHEMA_VERSION),
        );

        $bodyBlocks = array_map(
            function (array $block) use ($richTextRenderer, $mediaUrlResolver, $disk): array {
                if ($block['type'] === NewsroomBodyContract::BLOCK_RICH_TEXT) {
                    $block['preview_html'] = $richTextRenderer->render($block['data']['content']);

                    return $block;
                }

                if ($block['type'] === NewsroomBodyContract::BLOCK_IMAGE) {
                    $block['preview_url'] = $mediaUrlResolver->resolve(
                        $block['data']['path'] ?? null,
                        $disk,
                    );
                }

                return $block;
            },
            $bodyBlocks,
        );

        $publicSources = $contentArticle->sources()
            ->where('is_publicly_cited', true)
            ->orderBy('sort_order')
            ->get([
                'id',
                'source_type',
                'publisher',
                'title',
                'url',
                'published_at',
                'is_primary',
                'is_official',
            ]);

        $previewUrl = route('admin.newsroom.articles.preview', $contentArticle);

        $response = response()->view('newsroom.article-preview', [
            'article' => $contentArticle,
            'bodyBlocks' => $bodyBlocks,
            'publicSources' => $publicSources,
            'heroImageUrl' => $mediaUrlResolver->resolve($contentArticle->hero_image_path, $disk),
            'analyticsEnabled' => false,
            'breadcrumbs' => [],
            'structuredData' => [],
            'meta' => [
                'title' => '[PODGLĄD] '.$contentArticle->title,
                'description' => $contentArticle->lead,
                'canonical' => $previewUrl,
                'robots' => 'noindex,nofollow',
                'og_type' => 'article',
                'author_name' => $contentArticle->author?->name,
            ],
        ]);

        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}

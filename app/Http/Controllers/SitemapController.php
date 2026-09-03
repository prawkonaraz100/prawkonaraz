<?php

namespace App\Http\Controllers;

use App\Support\SeoSitemapBuilder;
use App\Support\SeoSitemapXmlRenderer;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        protected SeoSitemapBuilder $builder,
        protected SeoSitemapXmlRenderer $renderer,
    ) {}

    public function index(): Response
    {
        return $this->xmlResponse($this->renderer->sitemapIndex($this->builder->sitemapIndexItems()));
    }

    public function staticPages(): Response
    {
        return $this->urlsetResponse($this->builder->staticUrls());
    }

    public function signs(): Response
    {
        return $this->urlsetResponse($this->builder->trafficSignUrls(), true);
    }

    public function categories(): Response
    {
        return $this->urlsetResponse($this->builder->trafficSignCategoryUrls());
    }

    public function supportingPages(): Response
    {
        return $this->urlsetResponse($this->builder->supportingPageUrls());
    }

    public function authors(): Response
    {
        return $this->urlsetResponse($this->builder->authorUrls());
    }

    public function legalContent(): Response
    {
        return $this->urlsetResponse($this->builder->legalContentUrls());
    }

    protected function urlsetResponse(array $urls, bool $includeImages = false): Response
    {
        return $this->xmlResponse($this->renderer->urlset($urls, $includeImages));
    }

    protected function xmlResponse(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}

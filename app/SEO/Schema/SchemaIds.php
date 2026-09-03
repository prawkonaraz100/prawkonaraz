<?php

namespace App\SEO\Schema;

use App\Models\LegalContentPage;
use App\Models\LegalTopic;
use App\Models\LegalUnit;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Support\PublicUrlResolver;

class SchemaIds
{
    public function __construct(
        protected PublicUrlResolver $publicUrlResolver,
    ) {}

    public function root(): string
    {
        return $this->publicUrlResolver->currentRoot();
    }

    public function organization(): string
    {
        return $this->root().'/#organization';
    }

    public function website(): string
    {
        return $this->root().'/#website';
    }

    public function trafficSignTermSet(): string
    {
        return route('traffic-signs.index').'#defined-term-set';
    }

    public function trafficSignHubCategoryList(): string
    {
        return route('traffic-signs.index').'#categories';
    }

    public function trafficSignHubFeaturedList(): string
    {
        return route('traffic-signs.index').'#featured-signs';
    }

    public function trafficSignCategorySignList(TrafficSignCategory $category): string
    {
        return route('traffic-signs.categories.show', $category->slug).'#signs';
    }

    public function trafficSignCategoryEntity(TrafficSignCategory $category): string
    {
        return $this->root().'/entity/traffic-sign-category/'.$category->slug;
    }

    public function trafficSignEntity(TrafficSign $sign): string
    {
        return $this->root().'/entity/traffic-sign/'.$sign->getKey();
    }

    public function trafficSignImage(string $canonicalUrl): string
    {
        return $this->fragment($canonicalUrl, 'image');
    }

    public function trafficSignOgImage(string $canonicalUrl): string
    {
        return $this->fragment($canonicalUrl, 'og-image');
    }

    public function legalContentItemList(): string
    {
        return route('public.regulations').'#articles';
    }

    public function legalContentPageEntity(LegalContentPage $page): string
    {
        return $this->root().'/entity/legal-content/'.$page->getKey();
    }

    public function legalContentLegalUnitList(LegalContentPage $page): string
    {
        return route('public.regulations.show', $page->slug).'#legal-units';
    }

    public function legalContentQuestionList(LegalContentPage $page): string
    {
        return route('public.regulations.show', $page->slug).'#related-questions';
    }

    public function legalTopicEntity(LegalTopic $topic): string
    {
        return $this->root().'/entity/legal-topic/'.$topic->slug;
    }

    public function legalUnit(LegalUnit $legalUnit): string
    {
        $slug = trim((string) $legalUnit->slug);

        if ($slug === '') {
            $slug = 'legal-unit-'.$legalUnit->getKey();
        }

        return $this->root().'/entity/law/'.$slug;
    }

    public function publicQuestionDataset(): string
    {
        return route('public.questions.hub').'#dataset';
    }

    public function publicQuestionHubCategoryList(): string
    {
        return route('public.questions.hub').'#categories';
    }

    public function publicQuestionHubWebPage(): string
    {
        return route('public.questions.hub').'#webpage';
    }

    public function publicQuestionCategoryQuestionList(LicenseCategory $category): string
    {
        return route('public.questions.category', $category->slug).'#questions';
    }

    public function publicQuestionCategoryEntity(LicenseCategory $category): string
    {
        return $this->root().'/entity/category/'.$category->slug;
    }

    public function publicQuestionCategoryEntityFromSlug(string $slug): string
    {
        return $this->root().'/entity/category/'.trim($slug, '/');
    }

    public function publicQuestionEntity(Question $question): string
    {
        return $this->root().'/entity/question/'.$question->getKey();
    }

    public function publicQuestionTopicEntity(QuestionTopic $topic): string
    {
        return $this->root().'/entity/topic/'.$topic->key;
    }

    public function publicQuestionLearningResource(string $canonicalUrl): string
    {
        return $this->fragment($canonicalUrl, 'learning-resource');
    }

    public function publicQuestionImage(string $canonicalUrl): string
    {
        return $this->fragment($canonicalUrl, 'image');
    }

    public function publicQuestionAudio(string $canonicalUrl): string
    {
        return $this->fragment($canonicalUrl, 'audio-question');
    }

    public function publicQuestionLegalUnit(LegalUnit $legalUnit): string
    {
        return $this->legalUnit($legalUnit);
    }

    public function fragment(string $url, string $fragment): string
    {
        $baseUrl = explode('#', $url, 2)[0];

        return $baseUrl.'#'.ltrim($fragment, '#');
    }
}

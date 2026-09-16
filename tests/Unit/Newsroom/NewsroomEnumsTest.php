<?php

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use App\Enums\ContentArticleSourceType;
use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;

test('newsroom v1 enum values match the domain contract', function () {
    expect(array_column(ContentArticleType::cases(), 'value'))->toBe([
        'news',
        'guide',
        'explainer',
        'analysis',
        'report',
    ])->and(array_column(ContentArticleWorkflowStatus::cases(), 'value'))->toBe([
        'draft',
        'in_review',
        'scheduled',
        'published',
        'needs_review',
        'archived',
        'withdrawn',
    ])->and(array_column(ContentArticleSourceType::cases(), 'value'))->toBe([
        'official',
        'legislation',
        'institution',
        'primary_data',
        'interview',
        'report',
        'media',
        'other',
    ])->and(array_column(ContentArticleOriginType::cases(), 'value'))->toBe([
        'original',
        'compiled',
        'official_source',
        'data_analysis',
        'licensed_agency',
    ])->and(array_column(ContentArticleRegulatoryStatus::cases(), 'value'))->toBe([
        'not_applicable',
        'proposal',
        'consultation',
        'official_announcement',
        'adopted_future',
        'in_force',
    ]);
});

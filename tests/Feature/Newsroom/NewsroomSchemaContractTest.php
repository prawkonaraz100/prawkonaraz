<?php

use Illuminate\Support\Facades\Schema;

test('newsroom v1 tables and critical columns exist on the default migration path', function () {
    $tables = [
        'content_categories',
        'content_tags',
        'content_articles',
        'content_topics',
        'content_article_tag',
        'content_article_topic',
        'content_article_sources',
        'content_article_question',
        'content_article_legal_unit',
        'content_article_traffic_sign',
        'content_article_redirects',
        'content_home_placements',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing table [{$table}].");
    }

    expect(Schema::hasColumns('content_articles', [
        'type',
        'category_id',
        'author_id',
        'reviewer_id',
        'origin_type',
        'title',
        'slug',
        'lead',
        'body_blocks',
        'body_schema_version',
        'regulatory_status',
        'workflow_status',
        'first_published_at',
        'public_state_changed_at',
        'hero_image_caption',
        'hero_focal_x',
        'hero_focal_y',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('content_home_placements', [
            'surface_key',
            'slot_key',
            'context_key',
            'position',
            'article_id',
            'starts_at',
            'ends_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumn('content_articles', 'canonical_url'))->toBeFalse()
        ->and(Schema::hasColumn('content_articles', 'featured_position'))->toBeFalse();
});

test('membership pivots match the documented minimal shape', function () {
    expect(Schema::hasColumns('content_article_tag', ['article_id', 'tag_id', 'created_at']))->toBeTrue()
        ->and(Schema::hasColumn('content_article_tag', 'id'))->toBeFalse()
        ->and(Schema::hasColumn('content_article_tag', 'updated_at'))->toBeFalse()
        ->and(Schema::hasColumns('content_article_topic', ['article_id', 'topic_id', 'created_at']))->toBeTrue()
        ->and(Schema::hasColumn('content_article_topic', 'id'))->toBeFalse()
        ->and(Schema::hasColumn('content_article_topic', 'updated_at'))->toBeFalse();
});

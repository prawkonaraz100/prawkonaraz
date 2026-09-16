<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\PostgresTestCase;

uses(PostgresTestCase::class);

test('newsroom migrations create the documented PostgreSQL schema and indexes', function () {
    expect(DB::connection()->getDriverName())->toBe('pgsql');

    foreach ([
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
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing PostgreSQL table [{$table}].");
    }

    $indexes = collect(DB::select(<<<'SQL'
        select indexname
        from pg_indexes
        where schemaname = current_schema()
          and tablename in (
            'content_articles',
            'content_topics',
            'content_article_tag',
            'content_article_topic',
            'content_article_sources',
            'content_article_question',
            'content_article_legal_unit',
            'content_article_traffic_sign',
            'content_home_placements'
          )
    SQL))->pluck('indexname')->all();

    foreach ([
        'content_articles_slug_unique',
        'content_articles_workflow_first_published_idx',
        'content_articles_category_workflow_published_idx',
        'content_articles_type_workflow_published_idx',
        'content_articles_featured_workflow_priority_idx',
        'content_articles_breaking_expires_idx',
        'content_articles_freshness_due_idx',
        'content_articles_scheduled_workflow_idx',
        'content_topics_status_published_idx',
        'content_article_tag_reverse_idx',
        'content_article_topic_reverse_idx',
        'content_article_sources_article_sort_idx',
        'content_article_question_reverse_idx',
        'content_article_legal_unit_reverse_idx',
        'content_article_traffic_sign_reverse_idx',
        'content_home_placements_lookup_idx',
    ] as $index) {
        expect($indexes)->toContain($index);
    }
});

test('critical PostgreSQL foreign keys use the documented delete directions', function () {
    $rows = DB::select(<<<'SQL'
        select
            tc.table_name,
            kcu.column_name,
            ccu.table_name as foreign_table_name,
            rc.delete_rule
        from information_schema.table_constraints tc
        join information_schema.key_column_usage kcu
          on tc.constraint_name = kcu.constraint_name
         and tc.constraint_schema = kcu.constraint_schema
        join information_schema.referential_constraints rc
          on tc.constraint_name = rc.constraint_name
         and tc.constraint_schema = rc.constraint_schema
        join information_schema.constraint_column_usage ccu
          on rc.unique_constraint_name = ccu.constraint_name
         and rc.unique_constraint_schema = ccu.constraint_schema
        where tc.constraint_type = 'FOREIGN KEY'
          and tc.table_schema = current_schema()
    SQL);

    $rules = collect($rows)->keyBy(
        fn (object $row): string => $row->table_name.'.'.$row->column_name,
    );

    expect($rules['content_articles.category_id']->foreign_table_name)->toBe('content_categories')
        ->and($rules['content_articles.category_id']->delete_rule)->toBe('RESTRICT')
        ->and($rules['content_articles.author_id']->foreign_table_name)->toBe('content_authors')
        ->and($rules['content_articles.author_id']->delete_rule)->toBe('RESTRICT')
        ->and($rules['content_topics.featured_article_id']->delete_rule)->toBe('SET NULL')
        ->and($rules['content_article_question.article_id']->delete_rule)->toBe('CASCADE')
        ->and($rules['content_article_question.question_id']->foreign_table_name)->toBe('questions')
        ->and($rules['content_article_question.question_id']->delete_rule)->toBe('CASCADE')
        ->and($rules['content_article_legal_unit.legal_unit_id']->foreign_table_name)->toBe('legal_units')
        ->and($rules['content_article_legal_unit.legal_unit_id']->delete_rule)->toBe('CASCADE')
        ->and($rules['content_article_traffic_sign.traffic_sign_id']->foreign_table_name)->toBe('traffic_signs')
        ->and($rules['content_article_traffic_sign.traffic_sign_id']->delete_rule)->toBe('CASCADE')
        ->and($rules['content_home_placements.created_by_user_id']->delete_rule)->toBe('SET NULL')
        ->and($rules['content_home_placements.updated_by_user_id']->delete_rule)->toBe('SET NULL');
});

test('rolling back the twelve newsroom migrations leaves existing product tables intact', function () {
    $this->artisan('migrate:rollback', ['--step' => 12, '--force' => true])->assertExitCode(0);

    foreach ([
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
    ] as $table) {
        expect(Schema::hasTable($table))->toBeFalse("Rollback left newsroom table [{$table}].");
    }

    expect(Schema::hasTable('content_authors'))->toBeTrue()
        ->and(Schema::hasTable('questions'))->toBeTrue()
        ->and(Schema::hasTable('legal_units'))->toBeTrue()
        ->and(Schema::hasTable('traffic_signs'))->toBeTrue()
        ->and(Schema::hasTable('users'))->toBeTrue();
});

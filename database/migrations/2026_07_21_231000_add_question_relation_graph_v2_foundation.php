<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_seo_topics', function (Blueprint $table): void {
            $table->string('kind', 24)->nullable();
            $table->string('status', 24)->default('draft');
            $table->string('content_quality_status', 24)->default('missing');
            $table->json('metadata')->nullable();

            $table->index(['kind', 'status'], 'qst_kind_status_idx');
            $table->index(
                ['status', 'content_quality_status', 'is_indexable'],
                'qst_publication_gate_idx',
            );
        });

        Schema::table('question_seo_topic_memberships', function (Blueprint $table): void {
            $table->dropUnique('question_seo_membership_question_unique');
        });

        Schema::table('question_seo_topic_memberships', function (Blueprint $table): void {
            $table->boolean('is_primary')->default(false);
            $table->string('role', 24)->default('supporting');
            $table->string('status', 24)->default('candidate');
            $table->decimal('confidence', 7, 6)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->unique(
                ['question_public_explanation_id', 'question_seo_topic_id'],
                'qstm_explanation_topic_unique',
            );
            $table->index(
                ['question_public_explanation_id', 'status', 'is_primary'],
                'qstm_explanation_primary_idx',
            );
            $table->index(
                ['question_seo_topic_id', 'status', 'role'],
                'qstm_topic_status_role_idx',
            );
        });

        Schema::table('question_seo_topic_relations', function (Blueprint $table): void {
            $table->string('status', 24)->default('candidate');
            $table->string('source', 24)->default('graph');
            $table->text('reason')->nullable();
            $table->decimal('score', 7, 6)->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('metadata')->nullable();

            $table->index(['source_topic_id', 'status', 'display_order'], 'qstr_source_status_order_idx');
            $table->index(['target_topic_id', 'status'], 'qstr_target_status_idx');
        });

        Schema::create('question_relation_evidences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_relation_id')->constrained('question_relations')->cascadeOnDelete();
            $table->string('evidence_type', 32);
            $table->text('summary');
            $table->string('source', 24);
            $table->decimal('confidence', 7, 6)->nullable();
            $table->string('status', 24)->default('candidate');
            $table->json('metadata')->nullable();
            $table->string('version', 64)->default('v1');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['question_relation_id', 'status'], 'qre_relation_status_idx');
            $table->index(['evidence_type', 'status', 'source'], 'qre_type_status_source_idx');
        });

        Schema::create('question_relation_ranking_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_seo_topic_id')->constrained('question_seo_topics')->cascadeOnDelete();
            $table->string('input_version', 120);
            $table->string('generator_version', 120);
            $table->string('config_hash', 64);
            $table->string('status', 24)->default('generated');
            $table->json('metrics')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['question_seo_topic_id', 'status', 'created_at'], 'qrrr_topic_status_created_idx');
            $table->index(['generator_version', 'config_hash'], 'qrrr_generator_config_idx');
        });

        Schema::create('question_relation_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('question_relation_ranking_run_id');
            $table->foreign(
                'question_relation_ranking_run_id',
                'qrr_ranking_run_fk',
            )->references('id')->on('question_relation_ranking_runs')->cascadeOnDelete();
            $table->foreignId('source_explanation_id')->constrained('question_public_explanations')->cascadeOnDelete();
            $table->foreignId('target_explanation_id')->constrained('question_public_explanations')->cascadeOnDelete();
            $table->foreignId('question_relation_id')->nullable()->constrained('question_relations')->nullOnDelete();
            $table->string('scope', 32);
            $table->string('group_key', 32);
            $table->unsignedSmallInteger('rank')->nullable();
            $table->decimal('score', 12, 6);
            $table->json('score_components');
            $table->string('status', 24)->default('selected');
            $table->string('suppression_reason', 160)->nullable();
            $table->timestamps();

            $table->unique(
                ['question_relation_ranking_run_id', 'source_explanation_id', 'target_explanation_id'],
                'qrr_run_source_target_unique',
            );
            $table->index(
                ['question_relation_ranking_run_id', 'source_explanation_id', 'status', 'rank'],
                'qrr_run_source_status_rank_idx',
            );
            $table->index(['target_explanation_id', 'status'], 'qrr_target_status_idx');
        });

        Schema::create('question_relation_rollouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_seo_topic_id')->unique()->constrained('question_seo_topics')->cascadeOnDelete();
            $table->string('mode', 16)->default('v1');
            $table->foreignId('active_ranking_run_id')
                ->nullable()
                ->constrained('question_relation_ranking_runs')
                ->nullOnDelete();
            $table->unsignedSmallInteger('exposure_percentage')->default(0);
            $table->string('cohort_seed', 120)->default('v1');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['mode', 'question_seo_topic_id'], 'qrr_mode_topic_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                CREATE UNIQUE INDEX qstm_one_verified_primary
                ON question_seo_topic_memberships (question_public_explanation_id)
                WHERE is_primary = true AND status = 'verified'
                SQL);
            DB::statement(<<<'SQL'
                CREATE UNIQUE INDEX qrr_selected_rank_unique
                ON question_relation_recommendations (
                    question_relation_ranking_run_id,
                    source_explanation_id,
                    rank
                )
                WHERE status = 'selected'
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('question_relation_rollouts');
        Schema::dropIfExists('question_relation_recommendations');
        Schema::dropIfExists('question_relation_ranking_runs');
        Schema::dropIfExists('question_relation_evidences');

        Schema::table('question_seo_topic_relations', function (Blueprint $table): void {
            $table->dropIndex('qstr_source_status_order_idx');
            $table->dropIndex('qstr_target_status_idx');
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropColumn([
                'status',
                'source',
                'reason',
                'score',
                'reviewed_by_user_id',
                'reviewed_at',
                'metadata',
            ]);
        });

        Schema::table('question_seo_topic_memberships', function (Blueprint $table): void {
            $table->dropUnique('qstm_explanation_topic_unique');
            $table->dropIndex('qstm_explanation_primary_idx');
            $table->dropIndex('qstm_topic_status_role_idx');
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropColumn([
                'is_primary',
                'role',
                'status',
                'confidence',
                'reason',
                'metadata',
                'reviewed_by_user_id',
                'reviewed_at',
            ]);
        });

        Schema::table('question_seo_topic_memberships', function (Blueprint $table): void {
            $table->unique('question_public_explanation_id', 'question_seo_membership_question_unique');
        });

        Schema::table('question_seo_topics', function (Blueprint $table): void {
            $table->dropIndex('qst_kind_status_idx');
            $table->dropIndex('qst_publication_gate_idx');
            $table->dropColumn(['kind', 'status', 'content_quality_status', 'metadata']);
        });
    }
};

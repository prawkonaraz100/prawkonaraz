<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_question_explanation_assets', function (Blueprint $table) {
            $table->id();
            $table->string('external_id');
            $table->string('source_scope');
            $table->string('kind', 50);
            $table->string('disk', 50)->default('public');
            $table->string('file_path')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['external_id', 'source_scope', 'kind'], 'shared_question_expl_assets_unique');
            $table->index(['external_id', 'source_scope']);
            $table->index(['external_id', 'source_scope', 'kind', 'is_active'], 'shared_question_expl_assets_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_question_explanation_assets');
    }
};

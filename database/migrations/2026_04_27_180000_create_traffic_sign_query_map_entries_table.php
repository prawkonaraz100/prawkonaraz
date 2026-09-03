<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_sign_query_map_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traffic_sign_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('traffic_sign_category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('primary_query');
            $table->string('mapped_title');
            $table->string('target_type', 40);
            $table->string('search_intent', 40);
            $table->string('priority', 10)->default('p3');
            $table->string('rollout_status', 40)->default('backlog');
            $table->string('batch_label')->nullable();
            $table->string('target_path', 2048)->nullable();
            $table->text('watch_reason')->nullable();
            $table->text('source_plan')->nullable();
            $table->text('correction_notes')->nullable();
            $table->text('competitor_notes')->nullable();
            $table->text('first_mover_note')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rollout_status', 'priority']);
            $table->index(['target_type', 'search_intent']);
            $table->index(['batch_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_sign_query_map_entries');
    }
};

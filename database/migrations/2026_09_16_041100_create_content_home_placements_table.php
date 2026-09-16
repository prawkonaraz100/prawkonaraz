<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_home_placements', function (Blueprint $table): void {
            $table->id();
            $table->string('surface_key', 64)->default('newsroom_home');
            $table->string('slot_key', 64);
            $table->string('context_key', 120)->nullable();
            $table->smallInteger('position')->default(0);
            $table->foreignId('article_id')
                ->constrained('content_articles')
                ->cascadeOnDelete();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['surface_key', 'slot_key', 'context_key', 'position', 'starts_at', 'ends_at'],
                'content_home_placements_lookup_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_home_placements');
    }
};

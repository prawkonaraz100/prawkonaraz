<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_public_explanations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id', 120)->unique();
            $table->string('title')->nullable();
            $table->longText('body');
            $table->string('status', 32)->default('draft')->index();
            $table->foreignId('author_id')->nullable()->constrained('content_authors')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('content_authors')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->date('last_reviewed_at')->nullable();
            $table->text('source_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamps();

            $table->unique('question_id');
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_public_explanations');
    }
};

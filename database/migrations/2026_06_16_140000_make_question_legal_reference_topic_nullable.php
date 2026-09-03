<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_legal_references', function (Blueprint $table): void {
            $table->dropForeign(['legal_topic_id']);
            $table->foreignId('legal_topic_id')
                ->nullable()
                ->change();
            $table->foreign('legal_topic_id')
                ->references('id')
                ->on('legal_topics')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('question_legal_references', function (Blueprint $table): void {
            $table->dropForeign(['legal_topic_id']);
            $table->foreignId('legal_topic_id')
                ->nullable(false)
                ->change();
            $table->foreign('legal_topic_id')
                ->references('id')
                ->on('legal_topics')
                ->restrictOnDelete();
        });
    }
};

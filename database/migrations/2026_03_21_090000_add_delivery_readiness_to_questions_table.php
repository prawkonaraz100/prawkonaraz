<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('requires_primary_media')
                ->default(false)
                ->after('is_active');
            $table->string('delivery_issue', 64)
                ->nullable()
                ->after('requires_primary_media');

            $table->index(['is_active', 'delivery_issue'], 'questions_active_delivery_issue_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_active_delivery_issue_index');
            $table->dropColumn([
                'requires_primary_media',
                'delivery_issue',
            ]);
        });
    }
};

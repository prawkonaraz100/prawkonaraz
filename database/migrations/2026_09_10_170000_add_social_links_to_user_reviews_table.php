<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_reviews', function (Blueprint $table): void {
            $table->json('social_links')->nullable()->after('photo_privacy_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_reviews', function (Blueprint $table): void {
            $table->dropColumn('social_links');
        });
    }
};

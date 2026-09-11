<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_reviews', function (Blueprint $table): void {
            $table->string('photo_path')->nullable()->after('content');
            $table->timestamp('photo_uploaded_at')->nullable()->after('photo_path');
            $table->timestamp('photo_privacy_confirmed_at')->nullable()->after('photo_uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_reviews', function (Blueprint $table): void {
            $table->dropColumn([
                'photo_path',
                'photo_uploaded_at',
                'photo_privacy_confirmed_at',
            ]);
        });
    }
};

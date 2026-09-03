<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path', 2048)->nullable()->after('remember_token');
            $table->timestamp('avatar_uploaded_at')->nullable()->after('avatar_path');
            $table->timestamp('avatar_social_fallback_disabled_at')->nullable()->after('avatar_uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'avatar_path',
                'avatar_uploaded_at',
                'avatar_social_fallback_disabled_at',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('password_login_enabled')
                ->default(true)
                ->after('password');
        });

        Schema::create('user_social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_user_id');
            $table->string('email')->nullable()->index();
            $table->string('display_name')->nullable();
            $table->string('avatar_url', 1000)->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_social_accounts');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('password_login_enabled');
        });
    }
};

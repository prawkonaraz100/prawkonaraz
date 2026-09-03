<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 32)
                ->default(User::ROLE_STUDENT)
                ->after('is_admin')
                ->index();
            $table->boolean('is_test_account')
                ->default(false)
                ->after('role')
                ->index();
            $table->boolean('requires_password_change')
                ->default(false)
                ->after('is_test_account');
            $table->boolean('is_temporary_account')
                ->default(false)
                ->after('requires_password_change')
                ->index();
            $table->timestamp('temporary_account_expires_at')
                ->nullable()
                ->after('is_temporary_account');
            $table->timestamp('claimed_at')
                ->nullable()
                ->after('temporary_account_expires_at');
            $table->unsignedInteger('moderator_quota')
                ->default(User::DEFAULT_MODERATOR_QUOTA)
                ->after('claimed_at');
            $table->foreignId('created_by_moderator_id')
                ->nullable()
                ->after('moderator_quota')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('moderator_owner_id')
                ->nullable()
                ->after('created_by_moderator_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('users')
            ->where('is_admin', true)
            ->update(['role' => User::ROLE_ADMIN]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['created_by_moderator_id']);
            $table->dropForeign(['moderator_owner_id']);
            $table->dropColumn([
                'role',
                'is_test_account',
                'requires_password_change',
                'is_temporary_account',
                'temporary_account_expires_at',
                'claimed_at',
                'moderator_quota',
                'created_by_moderator_id',
                'moderator_owner_id',
            ]);
        });
    }
};

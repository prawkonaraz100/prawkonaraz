<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friend_invitations', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 32)->unique();
            $table->foreignId('owner_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('owner_product_access_grant_id')
                ->nullable()
                ->constrained('product_access_grants')
                ->nullOnDelete();
            $table->foreignId('owner_purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders')
                ->nullOnDelete();
            $table->foreignId('product_plan_id')
                ->nullable()
                ->constrained('product_plans')
                ->nullOnDelete();
            $table->string('status', 32)->index();
            $table->string('token_hash', 64)->unique();
            $table->string('code_hash', 64)->unique();
            $table->string('display_code_last4', 4)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('accepted_at')->nullable()->index();
            $table->foreignId('accepted_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('guest_product_access_grant_id')
                ->nullable()
                ->constrained('product_access_grants')
                ->nullOnDelete();
            $table->timestamp('guest_access_starts_at')->nullable();
            $table->timestamp('guest_access_expires_at')->nullable()->index();
            $table->timestamp('refund_processed_at')->nullable();
            $table->timestamp('refund_buffer_expires_at')->nullable();
            $table->timestamp('converted_at')->nullable()->index();
            $table->foreignId('converted_purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders')
                ->nullOnDelete();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->string('revoked_reason', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['owner_user_id', 'status']);
            $table->index(['accepted_by_user_id', 'status']);
            $table->index(['owner_product_access_grant_id', 'status']);
        });

        DB::statement("CREATE UNIQUE INDEX friend_invitations_one_accepted_owner ON friend_invitations (owner_user_id) WHERE status = 'accepted'");
        DB::statement("CREATE UNIQUE INDEX friend_invitations_one_accepted_guest ON friend_invitations (accepted_by_user_id) WHERE status = 'accepted' AND accepted_by_user_id IS NOT NULL");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS friend_invitations_one_accepted_guest');
        DB::statement('DROP INDEX IF EXISTS friend_invitations_one_accepted_owner');

        Schema::dropIfExists('friend_invitations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_gross_cents');
            $table->string('currency', 3)->default('PLN');
            $table->unsignedInteger('access_days');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 32)->unique();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('product_plan_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('provider', 40)->index();
            $table->string('provider_reference')->nullable()->index();
            $table->string('status', 32)->index();
            $table->unsignedInteger('amount_gross_cents');
            $table->string('currency', 3)->default('PLN');
            $table->unsignedInteger('access_days');
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['product_plan_id', 'status']);
        });

        Schema::table('product_access_grants', function (Blueprint $table): void {
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->after('granted_by_user_id')
                ->constrained('purchase_orders')
                ->nullOnDelete();
        });

        DB::table('product_plans')->insert([
            'code' => 'start-30',
            'name' => 'Plan Start',
            'description' => '30 dni dostępu do nauki, statystyk, powtórek i trybu rankingowego.',
            'price_gross_cents' => 4900,
            'currency' => 'PLN',
            'access_days' => 30,
            'sort_order' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('product_access_grants', function (Blueprint $table): void {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
        });

        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('product_plans');
    }
};

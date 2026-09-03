<?php

use App\Models\ProductAccessGrant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_access_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('source', 40)
                ->index();
            $table->string('status', 32)
                ->default(ProductAccessGrant::STATUS_ACTIVE)
                ->index();
            $table->timestamp('starts_at')
                ->nullable();
            $table->timestamp('expires_at')
                ->nullable()
                ->index();
            $table->timestamp('revoked_at')
                ->nullable()
                ->index();
            $table->foreignId('granted_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('notes')
                ->nullable();
            $table->timestamps();

            $table->index(['user_id', 'source', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_access_grants');
    }
};

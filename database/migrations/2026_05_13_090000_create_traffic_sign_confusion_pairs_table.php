<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_sign_confusion_pairs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('traffic_sign_id')->constrained('traffic_signs')->cascadeOnDelete();
            $table->foreignId('confusing_traffic_sign_id')->constrained('traffic_signs')->cascadeOnDelete();
            $table->string('source', 48)->default('supporting_page');
            $table->string('source_slug')->nullable();
            $table->unsignedTinyInteger('strength')->default(80);
            $table->timestamps();

            $table->unique(
                ['traffic_sign_id', 'confusing_traffic_sign_id', 'source', 'source_slug'],
                'traffic_sign_confusion_pairs_unique',
            );
            $table->index(['traffic_sign_id', 'strength'], 'traffic_sign_confusion_pairs_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_sign_confusion_pairs');
    }
};

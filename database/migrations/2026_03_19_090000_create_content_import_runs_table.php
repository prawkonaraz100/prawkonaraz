<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_import_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 100);
            $table->string('identifier')->nullable()->index();
            $table->string('status', 50)->default('ok')->index();
            $table->boolean('dry_run')->default(false);
            $table->text('source_path')->nullable();
            $table->text('output_path')->nullable();
            $table->text('report_path')->nullable();
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('questions_total')->default(0);
            $table->unsignedInteger('media_total')->default(0);
            $table->unsignedInteger('asset_plan_total')->default(0);
            $table->unsignedInteger('uploaded_assets_total')->default(0);
            $table->unsignedInteger('errors_count')->default(0);
            $table->unsignedInteger('warnings_count')->default(0);
            $table->json('summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_import_runs');
    }
};

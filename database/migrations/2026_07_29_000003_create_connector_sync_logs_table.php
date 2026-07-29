<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Run the migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connector_sync_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sync_batch_id', 64)->unique();
            $table->string('connector', 64)->index();
            $table->string('connector_type', 64)->nullable();
            $table->string('mode', 16);
            $table->boolean('dry_run')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('completed_at');
            $table->unsignedInteger('records_received')->default(0);
            $table->unsignedInteger('records_imported')->default(0);
            $table->unsignedInteger('records_mapped')->default(0);
            $table->unsignedInteger('records_skipped')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connector_sync_logs');
    }
};

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
        Schema::create('raw_connector_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source_system', 64)->index();
            $table->string('natural_key', 191)->index();
            $table->string('import_batch_id', 64)->index();
            $table->json('raw_payload');
            $table->string('processing_status', 32)->default('received');
            $table->timestamps();

            $table->unique(['source_system', 'natural_key'], 'raw_records_source_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_connector_records');
    }
};

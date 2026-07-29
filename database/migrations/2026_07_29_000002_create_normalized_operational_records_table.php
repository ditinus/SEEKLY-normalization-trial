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
        Schema::create('normalized_operational_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('raw_connector_record_id')
                ->unique()
                ->constrained('raw_connector_records')
                ->restrictOnDelete();
            $table->string('source_system', 64)->index();
            $table->string('source_booking_id', 191)->nullable()->index();
            $table->string('portfolio_id', 191)->nullable()->index();
            $table->string('customer_id', 191)->nullable();
            $table->string('customer_name_masked', 191)->nullable();
            $table->string('team_id', 191)->nullable();
            $table->string('service_name', 191)->nullable();
            $table->string('service_category', 191)->nullable();
            $table->string('service_frequency', 64)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('launch27_status', 64)->nullable();
            $table->string('seekly_lifecycle_status', 64)->nullable()->index();
            $table->string('proof_eligibility', 32)->nullable();
            $table->string('sla_eligibility', 32)->nullable();
            $table->string('risk_eligibility', 32)->nullable();
            $table->boolean('has_checklist')->default(false);
            $table->boolean('has_time_tracking')->default(false);
            $table->boolean('has_notes')->default(false);
            $table->boolean('has_booking_images')->default(false);
            $table->boolean('is_historical_record')->default(false);
            $table->boolean('is_future_record')->default(false);
            $table->string('mapper_version', 32)->nullable();
            $table->json('normalized_record'); // full mapper output, superset of the columns above
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normalized_operational_records');
    }
};

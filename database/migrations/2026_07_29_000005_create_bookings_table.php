<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            // One raw import normalizes into exactly one booking, so the FK
            // is unique — this is what makes the hasOne/belongsTo pairing
            // with RawImport enforceable at the database level, not just
            // in application code.
            $table->foreignUuid('raw_import_id')
                ->unique()
                ->constrained('raw_imports')
                ->restrictOnDelete();

            $table->string('connector', 32)->index();
            $table->string('external_booking_id', 150)->nullable()->index();
            $table->string('customer_reference', 150)->nullable();
            $table->string('customer_name', 191)->nullable();
            $table->string('service_name', 191)->nullable();
            $table->string('service_category', 191)->nullable();
            $table->string('service_frequency', 64)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('source_status', 64)->nullable();
            $table->string('status', 64)->index();
            $table->boolean('has_checklist')->default(false);
            $table->boolean('has_time_tracking')->default(false);
            $table->boolean('has_notes')->default(false);
            $table->boolean('is_future')->default(false);
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('normalized_payload');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

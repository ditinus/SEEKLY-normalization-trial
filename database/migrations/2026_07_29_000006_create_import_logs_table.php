<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('connector', 32)->index();
            $table->string('mode', 16);
            $table->string('batch_id', 64)->unique();

            $table->timestamp('started_at');
            $table->timestamp('finished_at');

            $table->unsignedInteger('received_count')->default(0);
            $table->unsignedInteger('mapped_count')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->string('status', 16)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};

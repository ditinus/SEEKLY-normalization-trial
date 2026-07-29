<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_imports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('connector', 32)->index();
            $table->string('external_reference', 150)->nullable();

            $table->json('payload');
            $table->string('checksum', 64)->index();
            $table->string('import_batch_id', 64)->index();

            $table->timestamps();

            $table->unique(['connector', 'external_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_imports');
    }
};

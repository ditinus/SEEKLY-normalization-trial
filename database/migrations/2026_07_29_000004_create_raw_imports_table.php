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

            // Kept short (rather than the default 191) because connector +
            // external_reference back a composite unique index; MySQL's max
            // index key length under utf8mb4 (4 bytes/char) would otherwise
            // be exceeded. Connector natural keys comfortably fit in 150 chars.
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

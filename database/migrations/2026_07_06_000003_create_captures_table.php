<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            // Fecha "Current Time" extraída del propio archivo (no la de subida).
            $table->dateTime('captured_at')->nullable();
            $table->dateTime('uploaded_at');
            $table->string('original_filename');
            $table->string('file_path');
            $table->char('file_hash', 64)->unique(); // SHA-256, detección de duplicados
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('exos_version', 50)->nullable();
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->unsignedInteger('boot_count')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'error'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('parser_warnings')->nullable(); // parser tolerante a fallos
            $table->json('raw_summary')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'captured_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captures');
    }
};

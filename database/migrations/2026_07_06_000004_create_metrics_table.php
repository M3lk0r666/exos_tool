<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capture_id')->constrained()->cascadeOnDelete();
            $table->string('category', 50);  // ports, env, cpu, memory, stack...
            $table->string('entity', 100);   // "3:27", "Slot-1", "System"...
            $table->string('metric', 100);   // crc_errors, temperature, free_pct...
            $table->decimal('value', 20, 4)->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->index(['capture_id', 'category', 'metric']);
            $table->index(['category', 'metric', 'entity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};

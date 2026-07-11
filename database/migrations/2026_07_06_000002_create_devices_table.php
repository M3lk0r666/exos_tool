<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('system_mac', 17)->unique();
            $table->string('sysname');
            $table->string('alias')->nullable();
            $table->string('model')->nullable();
            $table->boolean('is_stack')->default(false);
            $table->string('site')->nullable();
            $table->enum('criticality', ['low', 'medium', 'high'])->default('medium');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'sysname']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};

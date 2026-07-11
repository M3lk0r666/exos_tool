<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capture_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->longText('executive_summary')->nullable();
            $table->longText('conclusions')->nullable();
            $table->longText('recommendations')->nullable();
            $table->enum('status', ['draft', 'issued'])->default('draft');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('issued_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->unique(['capture_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

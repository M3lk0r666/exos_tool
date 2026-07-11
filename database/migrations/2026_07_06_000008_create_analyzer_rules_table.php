<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyzer_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();   // PORT-CRC, SYS-REBOOT...
            $table->string('analyzer', 100);        // clase Analyzer que la aplica
            $table->string('description');
            $table->decimal('threshold_warning', 20, 4)->nullable();
            $table->decimal('threshold_critical', 20, 4)->nullable();
            // Severidades a asignar cuando se cruza cada umbral (escala de 5 niveles)
            $table->string('level_warning', 20)->default('medium');
            $table->string('level_critical', 20)->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('params')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyzer_rules');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rule_code', 30); // PORT-CRC, SYS-REBOOT... (MANUAL para hallazgos manuales)
            $table->enum('level', ['critical', 'high', 'medium', 'low', 'informational']);
            $table->string('area', 50);      // stability, ports, firmware, environment...
            $table->string('entity', 100)->nullable(); // puerto, slot, etc.
            $table->string('title');
            $table->text('description');
            $table->text('impact')->nullable();
            $table->text('recommendation')->nullable();
            $table->longText('evidence')->nullable();      // fragmento textual del archivo
            $table->string('file_location')->nullable();   // línea/sección
            $table->enum('status', ['open', 'acknowledged', 'in_progress', 'resolved', 'false_positive'])
                ->default('open');
            $table->text('status_notes')->nullable();
            $table->boolean('is_manual')->default(false);
            // Ciclo de vida: vínculo con la primera captura donde apareció el hallazgo
            $table->foreignId('first_seen_capture_id')->nullable()
                ->constrained('captures')->nullOnDelete();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['capture_id', 'level']);
            // Matching de hallazgos recurrentes: misma área + entidad + código de regla
            $table->index(['device_id', 'rule_code', 'entity']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};

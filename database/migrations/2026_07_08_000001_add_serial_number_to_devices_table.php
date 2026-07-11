<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Serial de la unidad principal (Switch o Slot-1/master).
            // El detalle por slot vive en captures.raw_summary.serial_numbers.
            $table->string('serial_number', 50)->nullable()->after('system_mac');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('serial_number');
        });
    }
};

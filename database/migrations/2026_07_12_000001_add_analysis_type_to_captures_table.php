<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('captures', function (Blueprint $table) {
            // tech_support = show tech-support all · log = show log
            $table->string('analysis_type', 20)->default('tech_support')->after('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('captures', function (Blueprint $table) {
            $table->dropColumn('analysis_type');
        });
    }
};

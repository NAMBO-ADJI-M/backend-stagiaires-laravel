<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->string('code_validation', 6)->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->dropColumn('code_validation');
        });
    }
};

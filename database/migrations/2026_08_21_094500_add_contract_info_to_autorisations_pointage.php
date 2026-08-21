<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->string('poste')->nullable()->after('code_validation');
            $table->date('date_debut')->nullable()->after('poste');
            $table->date('date_fin')->nullable()->after('date_debut');
            $table->text('conditions_stage')->nullable()->after('date_fin'); // Rémunération, horaires, etc.
        });
    }

    public function down(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->dropColumn(['poste', 'date_debut', 'date_fin', 'conditions_stage']);
        });
    }
};

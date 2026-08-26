<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->timestamp('tuteur_valide_le')->nullable()->after('statut');
            $table->timestamp('stagiaire_valide_le')->nullable()->after('tuteur_valide_le');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->dropColumn(['tuteur_valide_le', 'stagiaire_valide_le']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches_stagiaire_invite', function (Blueprint $table) {
            $table->string('tuteur_nom')->nullable()->after('tuteur_designe');
            $table->string('tuteur_prenom')->nullable()->after('tuteur_nom');
            $table->string('tuteur_fonction')->nullable()->after('tuteur_prenom');
            $table->string('tuteur_email')->nullable()->after('tuteur_fonction');
            $table->string('tuteur_telephone')->nullable()->after('tuteur_email');
        });

        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->string('tuteur_nom')->nullable()->after('tuteur_designe');
            $table->string('tuteur_prenom')->nullable()->after('tuteur_nom');
            $table->string('tuteur_fonction')->nullable()->after('tuteur_prenom');
            $table->string('tuteur_email')->nullable()->after('tuteur_fonction');
            $table->string('tuteur_telephone')->nullable()->after('tuteur_email');
        });
    }

    public function down(): void
    {
        Schema::table('fiches_stagiaire_invite', function (Blueprint $table) {
            $table->dropColumn(['tuteur_nom', 'tuteur_prenom', 'tuteur_fonction', 'tuteur_email', 'tuteur_telephone']);
        });

        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->dropColumn(['tuteur_nom', 'tuteur_prenom', 'tuteur_fonction', 'tuteur_email', 'tuteur_telephone']);
        });
    }
};

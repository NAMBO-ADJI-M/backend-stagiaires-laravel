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
        Schema::table('conventions', function (Blueprint $table) {
            // Informations Tuteur Professionnel
            $table->string('tuteur_nom')->nullable()->after('entreprise_telephone');
            $table->string('tuteur_prenom')->nullable()->after('tuteur_nom');
            $table->string('tuteur_fonction')->nullable()->after('tuteur_prenom');
            $table->string('tuteur_email')->nullable()->after('tuteur_fonction');
            $table->string('tuteur_telephone')->nullable()->after('tuteur_email');

            // Informations stagiaire manquantes
            $table->string('stagiaire_telephone')->nullable()->after('stagiaire_email');
            $table->string('stagiaire_adresse')->nullable()->after('stagiaire_telephone');
            $table->date('stagiaire_date_naissance')->nullable()->after('stagiaire_adresse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->dropColumn([
                'tuteur_nom', 'tuteur_prenom', 'tuteur_fonction', 'tuteur_email', 'tuteur_telephone',
                'stagiaire_telephone', 'stagiaire_adresse', 'stagiaire_date_naissance'
            ]);
        });
    }
};

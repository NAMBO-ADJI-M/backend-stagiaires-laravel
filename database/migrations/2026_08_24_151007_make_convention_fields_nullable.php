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
            $table->string('raison_sociale')->nullable()->change();
            $table->string('adresse')->nullable()->change();
            $table->string('situation_geographique')->nullable()->change();
            $table->string('secteur_activite')->nullable()->change();
            $table->string('representant_legal_nom')->nullable()->change();
            $table->string('representant_legal_fonction')->nullable()->change();
            $table->string('representant_legal_contact')->nullable()->change();
            $table->date('date_debut')->nullable()->change();
            $table->date('date_fin')->nullable()->change();
            $table->string('duree_hebdomadaire')->nullable()->change();
            $table->string('jours_presence')->nullable()->change();
            $table->string('lieu_execution')->nullable()->change();
            $table->text('modalites_suivi')->nullable()->change();
            $table->string('entreprise_email')->nullable()->change();
            $table->string('entreprise_telephone')->nullable()->change();
            $table->string('stagiaire_nom')->nullable()->change();
            $table->string('stagiaire_prenom')->nullable()->change();
            $table->string('stagiaire_numero')->nullable()->change();
            $table->string('stagiaire_email')->nullable()->change();
            $table->string('stagiaire_etablissement')->nullable()->change();
            $table->string('stagiaire_annee_academique')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // On ne revient pas en arrière sur la nullabilité pour éviter des erreurs SQL si des données nulles ont été insérées.
    }
};

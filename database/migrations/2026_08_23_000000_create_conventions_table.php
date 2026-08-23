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
        Schema::create('conventions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('carnet_id')->unique();

            // Informations entreprise
            $table->string('raison_sociale');
            $table->string('adresse');
            $table->string('situation_geographique');
            $table->string('secteur_activite');
            $table->string('representant_legal_nom');
            $table->string('representant_legal_fonction');
            $table->string('representant_legal_contact');

            // Détails du stage
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('duree_hebdomadaire');
            $table->string('jours_presence');
            $table->string('lieu_execution');
            $table->text('modalites_suivi');

            // Gratification
            $table->boolean('gratification_prevue')->default(false);
            $table->decimal('gratification_montant', 10, 2)->nullable();
            $table->string('gratification_periodicite')->nullable();
            $table->text('conges_absences')->nullable();

            // Contacts entreprise
            $table->string('entreprise_email');
            $table->string('entreprise_telephone');

            // Informations stagiaire
            $table->string('stagiaire_nom');
            $table->string('stagiaire_prenom');
            $table->string('stagiaire_numero');
            $table->string('stagiaire_email');
            $table->string('stagiaire_etablissement');
            $table->string('stagiaire_annee_academique');

            // Signature et validation
            $table->enum('statut', ['brouillon', 'en_attente', 'signee'])->default('brouillon');
            $table->timestamp('tuteur_valide_le')->nullable();
            $table->timestamp('stagiaire_valide_le')->nullable();

            $table->timestamps();

            $table->foreign('carnet_id')->references('id')->on('carnets_de_stage')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conventions');
    }
};

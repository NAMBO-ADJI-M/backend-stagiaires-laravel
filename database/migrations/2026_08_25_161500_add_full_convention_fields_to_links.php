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
        $tables = ['fiches_stagiaire_invite', 'autorisations_pointage'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Informations Entreprise (spécifiques à la convention)
                $table->string('raison_sociale_custom')->nullable()->after('entreprise_id');
                $table->string('adresse_custom')->nullable()->after('raison_sociale_custom');
                $table->string('situation_geographique')->nullable()->after('adresse_custom');
                $table->string('secteur_activite_custom')->nullable()->after('situation_geographique');

                // Représentant Légal
                $table->string('representant_legal_nom')->nullable();
                $table->string('representant_legal_fonction')->nullable();
                $table->string('representant_legal_contact')->nullable();

                // Gratification
                $table->boolean('gratification_prevue')->default(false);
                $table->decimal('gratification_montant', 10, 2)->nullable();
                $table->string('gratification_periodicite')->nullable();
                $table->text('conges_absences')->nullable();

                // Contacts documents
                $table->string('entreprise_email_document')->nullable();
                $table->string('entreprise_telephone_document')->nullable();

                // Complément Stagiaire
                $table->string('stagiaire_annee_academique')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['fiches_stagiaire_invite', 'autorisations_pointage'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn([
                    'raison_sociale_custom', 'adresse_custom', 'situation_geographique', 'secteur_activite_custom',
                    'representant_legal_nom', 'representant_legal_fonction', 'representant_legal_contact',
                    'gratification_prevue', 'gratification_montant', 'gratification_periodicite', 'conges_absences',
                    'entreprise_email_document', 'entreprise_telephone_document', 'stagiaire_annee_academique'
                ]);
            });
        }
    }
};

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
        // 1. Mise à jour de la table conventions
        Schema::table('conventions', function (Blueprint $table) {
            // Ajouts
            $table->string('objet_stage')->nullable()->after('representant_legal_contact');
            $table->string('objet_stage_autre')->nullable()->after('objet_stage');
            $table->string('cursus_rattachement')->nullable()->after('objet_stage_autre');
            $table->decimal('lieu_execution_lat', 10, 7)->nullable()->after('lieu_execution');
            $table->decimal('lieu_execution_lng', 10, 7)->nullable()->after('lieu_execution_lat');
            $table->string('teletravail_modalites')->nullable()->after('jours_presence');
            $table->integer('nombre_mois_stage')->nullable()->after('teletravail_modalites');

            // Suppressions
            $table->dropColumn(['situation_geographique', 'stagiaire_date_naissance']);
        });

        // 2. Mise à jour des tables de liaison (autorisations_pointage & fiches_stagiaire_invite)
        $tables = ['fiches_stagiaire_invite', 'autorisations_pointage'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Ajouts
                $table->integer('nombre_mois_stage')->nullable();
                $table->string('objet_stage_autre')->nullable()->after('objet_stage');

                // Suppressions
                if (Schema::hasColumn($table->getTable(), 'situation_geographique')) {
                    $table->dropColumn('situation_geographique');
                }
                if (Schema::hasColumn($table->getTable(), 'modalites_suivi_detail')) {
                    $table->dropColumn('modalites_suivi_detail');
                }
            });
        }

        // Cas spécifique pour autorisations_pointage qui avait la date de naissance
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            if (Schema::hasColumn('autorisations_pointage', 'stagiaire_date_naissance')) {
                $table->dropColumn('stagiaire_date_naissance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->string('situation_geographique')->nullable();
            $table->date('stagiaire_date_naissance')->nullable();
            $table->dropColumn([
                'objet_stage', 'objet_stage_autre', 'cursus_rattachement',
                'lieu_execution_lat', 'lieu_execution_lng', 'teletravail_modalites', 'nombre_mois_stage'
            ]);
        });

        $tables = ['fiches_stagiaire_invite', 'autorisations_pointage'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('situation_geographique')->nullable();
                $table->text('modalites_suivi_detail')->nullable();
                $table->dropColumn(['nombre_mois_stage', 'objet_stage_autre']);
            });
        }

        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->date('stagiaire_date_naissance')->nullable();
        });
    }
};

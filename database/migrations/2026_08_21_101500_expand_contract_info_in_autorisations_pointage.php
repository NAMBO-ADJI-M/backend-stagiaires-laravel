<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            // Cadre administratif
            $table->string('etablissement_nom')->nullable()->after('conditions_stage');
            $table->string('tuteur_designe')->nullable()->after('etablissement_nom');
            $table->string('objet_stage')->nullable()->after('tuteur_designe');
            $table->string('cursus_rattachement')->nullable()->after('objet_stage');

            // Conditions matérielles
            $table->string('lieu_execution')->nullable()->after('cursus_rattachement');
            $table->string('duree_hebdomadaire')->nullable()->after('lieu_execution');
            $table->string('jours_presence')->nullable()->after('duree_hebdomadaire');
            $table->string('teletravail_modalites')->nullable()->after('jours_presence');

            // Encadrement
            $table->string('referent_pedagogique_nom')->nullable()->after('teletravail_modalites');
            $table->string('referent_pedagogique_contact')->nullable()->after('referent_pedagogique_nom');
            $table->text('modalites_suivi_detail')->nullable()->after('referent_pedagogique_contact');
        });
    }

    public function down(): void
    {
        Schema::table('autorisations_pointage', function (Blueprint $table) {
            $table->dropColumn([
                'etablissement_nom', 'tuteur_designé', 'objet_stage', 'cursus_rattachement',
                'lieu_execution', 'duree_hebdomadaire', 'jours_presence', 'teletravail_modalites',
                'referent_pedagogique_nom', 'referent_pedagogique_contact', 'modalites_suivi_detail'
            ]);
        });
    }
};

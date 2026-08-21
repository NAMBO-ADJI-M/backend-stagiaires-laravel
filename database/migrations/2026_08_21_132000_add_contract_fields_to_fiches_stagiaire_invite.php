<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches_stagiaire_invite', function (Blueprint $table) {
            // Cadre administratif
            $table->string('poste')->nullable()->after('email');
            $table->date('date_debut')->nullable()->after('poste');
            $table->date('date_fin')->nullable()->after('date_debut');
            $table->string('etablissement_nom')->nullable()->after('date_fin');
            $table->string('tuteur_designe')->nullable()->after('etablissement_nom');
            $table->string('objet_stage')->nullable()->after('tuteur_designe');
            $table->string('cursus_rattachement')->nullable()->after('objet_stage');

            // Conditions matérielles
            $table->string('lieu_execution')->nullable()->after('cursus_rattachement');
            $table->decimal('lieu_execution_lat', 10, 7)->nullable()->after('lieu_execution');
            $table->decimal('lieu_execution_lng', 10, 7)->nullable()->after('lieu_execution_lat');
            $table->string('duree_hebdomadaire')->nullable()->after('lieu_execution_lng');
            $table->string('jours_presence')->nullable()->after('duree_hebdomadaire');
            $table->string('teletravail_modalites')->nullable()->after('jours_presence');

            // Encadrement
            $table->string('referent_pedagogique_nom')->nullable()->after('teletravail_modalites');
            $table->string('referent_pedagogique_contact')->nullable()->after('referent_pedagogique_nom');
            $table->text('modalites_suivi_detail')->nullable()->after('referent_pedagogique_contact');
            $table->text('conditions_stage')->nullable()->after('modalites_suivi_detail');
        });
    }

    public function down(): void
    {
        Schema::table('fiches_stagiaire_invite', function (Blueprint $table) {
            $table->dropColumn([
                'poste', 'date_debut', 'date_fin', 'etablissement_nom', 'tuteur_designe',
                'objet_stage', 'cursus_rattachement', 'lieu_execution', 'lieu_execution_lat',
                'lieu_execution_lng', 'duree_hebdomadaire', 'jours_presence', 'teletravail_modalites',
                'referent_pedagogique_nom', 'referent_pedagogique_contact', 'modalites_suivi_detail', 'conditions_stage'
            ]);
        });
    }
};

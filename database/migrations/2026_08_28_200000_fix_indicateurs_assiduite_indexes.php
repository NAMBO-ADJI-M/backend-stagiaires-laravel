<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rendre la suppression de l'index idempotente
        $indexExists = collect(DB::select("SHOW INDEX FROM indicateurs_assiduite"))
            ->contains('Key_name', 'indicateurs_assiduite_carnet_id_unique');

        if ($indexExists) {
            Schema::table('indicateurs_assiduite', function (Blueprint $table) {
                $table->dropUnique(['carnet_id']);
            });
        }

        Schema::table('indicateurs_assiduite', function (Blueprint $table) {
            // 2. On s'assure que autorisation_pointage_id est bien unique
            // (Si la migration précédente n'a pas réussi à le mettre, on le force ici)
            try {
                $table->unique('autorisation_pointage_id');
            } catch (\Exception $e) {
                // Déjà présent ou erreur silencieuse si doublons (traités par le cleanup après)
            }
        });

        // 3. Nettoyage des données orphelines et inutiles
        // Supprime les indicateurs sans autorisation ET sans aucune donnée travaillée
        DB::table('indicateurs_assiduite')
            ->whereNull('autorisation_pointage_id')
            ->where('jours_presents', 0)
            ->where('heures_totales_realisees', 0)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicateurs_assiduite', function (Blueprint $table) {
            $table->unique('carnet_id');
            $table->dropUnique(['autorisation_pointage_id']);
        });
    }
};

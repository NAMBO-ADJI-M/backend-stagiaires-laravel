<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rétrograde les statuts CONVENTION_SIGNEE en ACTIVE si la convention réelle n'est pas encore signée.
     */
    public function up(): void
    {
        $autorisations = DB::table('autorisations_pointage')
            ->where('statut', 'CONVENTION_SIGNEE')
            ->get();

        foreach ($autorisations as $auto) {
            // Trouver le carnet de stage EN_COURS correspondant à ce stagiaire et cette entreprise
            $carnet = DB::table('carnets_de_stage')
                ->where('stagiaire_id', $auto->stagiaire_id)
                ->where('entreprise_id', $auto->entreprise_id)
                ->where('statut', 'EN_COURS')
                ->first();

            if ($carnet) {
                // Vérifier si une convention signée existe pour ce carnet
                $convention = DB::table('conventions')
                    ->where('carnet_id', $carnet->id)
                    ->where('statut', 'signee')
                    ->first();

                // Si la convention n'est pas signée (brouillon, en_attente ou inexistante),
                // on rétrograde l'autorisation en ACTIVE (suivi opérationnel mais convention non close)
                if (!$convention) {
                    DB::table('autorisations_pointage')
                        ->where('id', $auto->id)
                        ->update(['statut' => 'ACTIVE']);
                }
            } else {
                // S'il n'y a même pas de carnet en cours, le statut CONVENTION_SIGNEE est erroné
                DB::table('autorisations_pointage')
                    ->where('id', $auto->id)
                    ->update(['statut' => 'ACTIVE']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas de retour en arrière possible (on ne peut pas deviner lesquels étaient "fake signées")
    }
};

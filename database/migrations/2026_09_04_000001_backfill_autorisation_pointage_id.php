<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Peupler les autorisation_pointage_id manquants à partir de carnet_id
        // On récupère les entrées sans autorisation_pointage_id qui ont un carnet_id
        $entrees = DB::table('entrees_carnet')
            ->whereNull('autorisation_pointage_id')
            ->whereNotNull('carnet_id')
            ->get();

        foreach ($entrees as $entree) {
            // On cherche l'autorisation liée à ce carnet
            $autorisation = DB::table('autorisations_pointage')
                ->where('carnet_id', $entree->carnet_id)
                ->first();

            if ($autorisation) {
                DB::table('entrees_carnet')
                    ->where('id', $entree->id)
                    ->update(['autorisation_pointage_id' => $autorisation->id]);
            }
        }
    }

    public function down(): void
    {
        // Pas de retour en arrière possible sur les données backfillées
    }
};

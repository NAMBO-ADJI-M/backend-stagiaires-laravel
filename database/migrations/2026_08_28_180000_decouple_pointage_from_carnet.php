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
        Schema::table('entrees_carnet', function (Blueprint $table) {
            // 1. Ajout de la nouvelle clé de liaison (Découplage)
            $table->foreignUuid('autorisation_pointage_id')
                ->nullable()
                ->after('carnet_id')
                ->constrained('autorisations_pointage')
                ->nullOnDelete();

            // 2. Le carnet devient optionnel sur une entrée (Journal)
            $table->uuid('carnet_id')->nullable()->change();

            // 4. Index pour les lectures fréquentes par autorisation
            $table->index('autorisation_pointage_id');
        });

        // 3. Backfill : Sécurisation des données existantes
        // On récupère toutes les entrées liées à un carnet
        $entrees = DB::table('entrees_carnet')->whereNotNull('carnet_id')->get();

        foreach ($entrees as $entree) {
            // On cherche l'autorisation signée qui était rattachée à ce carnet
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entrees_carnet', function (Blueprint $table) {
            $table->dropIndex(['autorisation_pointage_id']);
            $table->dropForeign(['autorisation_pointage_id']);
            $table->dropColumn('autorisation_pointage_id');

            // Note: Remettre NOT NULL peut échouer si des données orphelines ont été créées
            $table->uuid('carnet_id')->nullable(false)->change();
        });
    }
};

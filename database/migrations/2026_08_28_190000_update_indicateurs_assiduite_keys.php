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
        Schema::table('indicateurs_assiduite', function (Blueprint $table) {
            // 1. Ajout de la nouvelle clé de liaison (Autorisation)
            $table->foreignUuid('autorisation_pointage_id')
                ->nullable()
                ->after('id')
                ->unique()
                ->constrained('autorisations_pointage')
                ->nullOnDelete();

            // 2. Le carnet devient optionnel (retrait de la contrainte NOT NULL)
            $table->uuid('carnet_id')->nullable()->change();
        });

        // 3. Backfill : Synchronisation avec les données existantes
        $indicateurs = DB::table('indicateurs_assiduite')->get();

        foreach ($indicateurs as $indicateur) {
            if ($indicateur->carnet_id) {
                // On cherche l'autorisation liée au carnet
                $autorisation = DB::table('autorisations_pointage')
                    ->where('carnet_id', $indicateur->carnet_id)
                    ->first();

                if ($autorisation) {
                    DB::table('indicateurs_assiduite')
                        ->where('id', $indicateur->id)
                        ->update(['autorisation_pointage_id' => $autorisation->id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('indicateurs_assiduite', function (Blueprint $table) {
            $table->dropForeign(['autorisation_pointage_id']);
            $table->dropColumn('autorisation_pointage_id');

            // Remise en NOT NULL du carnet_id
            $table->uuid('carnet_id')->nullable(false)->change();
        });
    }
};

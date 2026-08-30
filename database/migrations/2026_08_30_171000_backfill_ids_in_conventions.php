<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $conventions = DB::table('conventions')->get();
        $successCount = 0;
        $anomalies = [];

        foreach ($conventions as $conv) {
            $stagiaireId = null;
            $entrepriseId = null;

            // 1. Essayer via Autorisation
            if ($conv->autorisation_pointage_id) {
                $auto = DB::table('autorisations_pointage')->where('id', $conv->autorisation_pointage_id)->first();
                if ($auto) {
                    $stagiaireId = $auto->stagiaire_id;
                    $entrepriseId = $auto->entreprise_id;
                }
            }

            // 2. Fallback via Carnet (si IDs toujours nuls)
            if ((!$stagiaireId || !$entrepriseId) && $conv->carnet_id) {
                $carnet = DB::table('carnets_de_stage')->where('id', $conv->carnet_id)->first();
                if ($carnet) {
                    $stagiaireId = $stagiaireId ?: $carnet->stagiaire_id;
                    $entrepriseId = $entrepriseId ?: $carnet->entreprise_id;
                }
            }

            // Mise à jour si trouvé
            if ($stagiaireId && $entrepriseId) {
                DB::table('conventions')->where('id', $conv->id)->update([
                    'stagiaire_id' => $stagiaireId,
                    'entreprise_id' => $entrepriseId
                ]);
                $successCount++;
            } else {
                $anomalies[] = [
                    'id' => $conv->id,
                    'statut' => $conv->statut,
                    'created_at' => $conv->created_at,
                    'autorisation_id' => $conv->autorisation_pointage_id,
                    'carnet_id' => $conv->carnet_id
                ];
            }
        }

        Log::info("Backfill Conventions : $successCount IDs renseignés.");

        if (!empty($anomalies)) {
            Log::warning("Backfill Conventions : " . count($anomalies) . " anomalies détectées.", ['anomalies' => $anomalies]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('conventions')->update([
            'stagiaire_id' => null,
            'entreprise_id' => null
        ]);
    }
};

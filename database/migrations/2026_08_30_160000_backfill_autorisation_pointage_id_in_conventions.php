<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Convention;
use App\Models\AutorisationPointage;
use App\Models\CarnetDeStage;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $conventions = Convention::whereNull('autorisation_pointage_id')->with('carnet')->get();
        $count = 0;

        foreach ($conventions as $conv) {
            $carnet = $conv->carnet;

            if ($carnet && $carnet->stagiaire_id && $carnet->entreprise_id) {
                // Trouver ou créer l'autorisation pour ce binôme
                $auto = AutorisationPointage::updateOrCreate(
                    [
                        'stagiaire_id' => $carnet->stagiaire_id,
                        'entreprise_id' => $carnet->entreprise_id
                    ],
                    [
                        'statut' => ($conv->statut === 'signee') ? 'CONVENTION_SIGNEE' : 'ACTIVE',
                        'carnet_id' => $carnet->id,
                        'poste' => $conv->poste ?? $carnet->poste ?? 'Stagiaire',
                        'date_debut' => $conv->date_debut,
                        'date_fin' => $conv->date_fin,
                        'etablissement_nom' => $conv->stagiaire_etablissement,
                        'tuteur_designe' => $conv->tuteur_nom ?? 'À définir',
                        'jours_presence' => $conv->jours_presence,
                        'tuteur_valide_le' => $conv->tuteur_valide_le,
                        'stagiaire_valide_le' => $conv->stagiaire_valide_le,
                    ]
                );

                $conv->update(['autorisation_pointage_id' => $auto->id]);
                $count++;
            }
        }

        Log::info("Backfill autorisations : $count conventions mises à jour.");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas d'action nécessaire pour le down car on ne veut pas supprimer les données créées
    }
};

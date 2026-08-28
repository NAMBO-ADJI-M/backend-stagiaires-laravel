<?php

namespace App\Observers;

use App\Models\EntreeCarnet;
use App\Models\ProgressionCompetence;
use App\Models\IndicateurAssiduite;
use Carbon\Carbon;

class EntreeCarnetObserver
{
    // Se déclenche après chaque UPDATE d'une entrée (ex. quand on renseigne date_fin au départ)
    public function updated(EntreeCarnet $entree): void
    {
        $this->traiterSiPresenceCompletee($entree);
    }

    // Se déclenche aussi après une création (si jamais date_fin est fournie dès la création)
    public function created(EntreeCarnet $entree): void
    {
        $this->traiterSiPresenceCompletee($entree);
    }

    private function traiterSiPresenceCompletee(EntreeCarnet $entree): void
    {
        if ($entree->type !== 'PRESENCE' || is_null($entree->date_fin)) {
            return; // rien à faire tant que ce n'est pas une présence complète
        }

        $dureeHeures = Carbon::parse($entree->date_debut)->floatDiffInHours(Carbon::parse($entree->date_fin));

        // 1. Toujours mettre à jour l'assiduité sur l'autorisation
        $this->mettreAJourAssiduite($entree, $dureeHeures);

        // 2. Si un carnet est lié, répartir les heures sur les compétences
        if ($entree->carnet_id) {
            $this->repartirHeuresSurCompetences($entree->carnet_id, $dureeHeures);
        }
    }

    // Répartit les heures au prorata sur toutes les compétences actives (non MAITRISEE) du carnet
    private function repartirHeuresSurCompetences(string $carnetId, float $dureeHeures): void
    {
        $progressions = ProgressionCompetence::with('competence')
            ->where('carnet_id', $carnetId)
            ->where('niveau_auto', '!=', 'MAITRISEE')
            ->get();

        if ($progressions->isEmpty()) {
            return;
        }

        $heuresParCompetence = $dureeHeures / $progressions->count();

        foreach ($progressions as $progression) {
            $nouvellesHeures = $progression->heures_cumulees + $heuresParCompetence;

            $niveau = 'NON_ABORDEE';
            if ($nouvellesHeures >= $progression->competence->seuil_maitrise) {
                $niveau = 'MAITRISEE';
            } elseif ($nouvellesHeures >= $progression->competence->seuil_decouverte) {
                $niveau = 'EN_COURS';
            } elseif ($nouvellesHeures > 0) {
                $niveau = 'DECOUVERTE';
            }

            $progression->update([
                'heures_cumulees' => $nouvellesHeures,
                'niveau_auto' => $niveau,
            ]);
        }
    }

    // Met à jour (ou crée) l'indicateur d'assiduité lié à l'autorisation
    private function mettreAJourAssiduite(EntreeCarnet $entree, float $dureeHeures): void
    {
        $autorisationId = $entree->autorisation_pointage_id;

        $indicateur = IndicateurAssiduite::firstOrCreate(
            ['autorisation_pointage_id' => $autorisationId],
            [
                'carnet_id' => $entree->carnet_id,
                'jours_presents' => 0,
                'jours_attendus' => 0,
                'heures_totales_realisees' => 0
            ]
        );

        // On recompte les jours distincts pour cette autorisation
        $joursDistincts = EntreeCarnet::where('autorisation_pointage_id', $autorisationId)
            ->where('type', 'PRESENCE')
            ->whereNotNull('date_fin')
            ->selectRaw('DATE(date_debut) as jour')
            ->distinct()
            ->count();

        $indicateur->update([
            'heures_totales_realisees' => $indicateur->heures_totales_realisees + $dureeHeures,
            'jours_presents' => $joursDistincts,
        ]);
    }
}

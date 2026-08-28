<?php

namespace App\Observers;

use App\Models\CarnetDeStage;
use App\Models\Competence;
use App\Models\ProgressionCompetence;
use App\Models\IndicateurAssiduite;
use Carbon\Carbon;

class CarnetDeStageObserver
{
    // À la création du carnet : initialise les statistiques et la progression
    public function created(CarnetDeStage $carnet): void
    {
        // 1. Initialiser le référentiel de compétences
        $competences = Competence::where('metier_id', $carnet->metier_id)
            ->whereNull('entreprise_id')
            ->get();

        foreach ($competences as $competence) {
            ProgressionCompetence::firstOrCreate([
                'carnet_id' => $carnet->id,
                'competence_id' => $competence->id,
            ], [
                'heures_cumulees' => 0,
                'niveau_auto' => 'NON_ABORDEE',
            ]);
        }

        // 2. Calculer le nombre de jours ouvrés attendus
        $dateDebut = Carbon::parse($carnet->date_debut);
        $dateFin = Carbon::parse($carnet->date_fin);

        $joursAttendus = $dateDebut->diffInDaysFiltered(function (Carbon $date) {
            return !$date->isWeekend();
        }, $dateFin->addDay());

        // 3. Tenter de lier l'indicateur d'assiduité existant de l'autorisation
        $autorisation = \App\Models\AutorisationPointage::where('stagiaire_id', $carnet->stagiaire_id)
            ->where('entreprise_id', $carnet->entreprise_id)
            ->first();

        if ($autorisation) {
            $indicateur = IndicateurAssiduite::firstOrCreate(
                ['autorisation_pointage_id' => $autorisation->id],
                [
                    'carnet_id' => $carnet->id,
                    'jours_presents' => 0,
                    'jours_attendus' => $joursAttendus,
                    'heures_totales_realisees' => 0
                ]
            );

            // Mise à jour systématique du carnet_id et des jours attendus contractuels
            $indicateur->update([
                'carnet_id' => $carnet->id,
                'jours_attendus' => $joursAttendus
            ]);
        }
        // NOTE : Si pas d'autorisation (cas rare d'un carnet créé avant toute liaison),
        // on ne crée pas d'IndicateurAssiduite ici. Il sera créé au premier pointage.
    }

    // Au rattachement à une entreprise ou changement de dates
    public function updated(CarnetDeStage $carnet): void
    {
        // 1. Gérer le rattachement (ajout des compétences spécifiques)
        if ($carnet->wasChanged('entreprise_id') && !is_null($carnet->entreprise_id)) {
            $competencesEntreprise = Competence::where('metier_id', $carnet->metier_id)
                ->where('entreprise_id', $carnet->entreprise_id)
                ->get();

            foreach ($competencesEntreprise as $competence) {
                ProgressionCompetence::firstOrCreate([
                    'carnet_id' => $carnet->id,
                    'competence_id' => $competence->id,
                ], [
                    'heures_cumulees' => 0,
                    'niveau_auto' => 'NON_ABORDEE',
                ]);
            }
        }

        // 2. Recalculer les jours attendus si les dates changent
        if ($carnet->wasChanged(['date_debut', 'date_fin'])) {
            $dateDebut = Carbon::parse($carnet->date_debut);
            $dateFin = Carbon::parse($carnet->date_fin);

            $joursAttendus = $dateDebut->diffInDaysFiltered(function (Carbon $date) {
                return !$date->isWeekend();
            }, $dateFin->addDay());

            IndicateurAssiduite::where('carnet_id', $carnet->id)
                ->update(['jours_attendus' => $joursAttendus]);
        }
    }
}

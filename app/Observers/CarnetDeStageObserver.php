<?php

namespace App\Observers;

use App\Models\CarnetDeStage;
use App\Models\Competence;
use App\Models\ProgressionCompetence;

class CarnetDeStageObserver
{
    // À la création du carnet : initialise une ligne de progression
    // pour chaque compétence STANDARD du métier choisi (entreprise_id NULL)
    public function created(CarnetDeStage $carnet): void
    {
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
    }

    // Au rattachement à une entreprise : ajoute aussi SES compétences spécifiques
    public function updated(CarnetDeStage $carnet): void
    {
        if (!$carnet->wasChanged('entreprise_id') || is_null($carnet->entreprise_id)) {
            return;
        }

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
}

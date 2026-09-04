<?php

namespace App\Console\Commands;

use App\Models\EntreeCarnet;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ConcluerPausesDeparts extends Command
{
    protected $signature = 'pointage:conclure-pauses';
    protected $description = 'Conclut automatiquement les présences en attente : pause confirmée ou départ définitif';

    public function handle(): void
    {
        $entreesEnAttente = EntreeCarnet::where('type', 'PRESENCE')
            ->whereIn('statut_cloture', ['EN_ATTENTE', 'SORTIE_SILENCIEUSE'])
            ->whereNotNull('date_fin')
            ->with('carnet.entreprise')
            ->get();

        $nbPause = 0;
        $nbDepart = 0;

        foreach ($entreesEnAttente as $entree) {
            $entreprise = $entree->carnet?->entreprise;

            // Si pas de carnet direct, on cherche via l'autorisation
            if (!$entreprise && $entree->autorisation_pointage_id) {
                $auto = \App\Models\AutorisationPointage::with('entreprise')->find($entree->autorisation_pointage_id);
                $entreprise = $auto?->entreprise;
            }

            $now = Carbon::now();
            $dateFin = Carbon::parse($entree->date_fin);
            $minutesDepuisSortie = $dateFin->diffInMinutes($now);

            // Y a-t-il eu un retour dans le rayon après cette sortie ?
            $retourConstate = EntreeCarnet::where('autorisation_pointage_id', $entree->autorisation_pointage_id)
                ->where('type', 'PRESENCE')
                ->where('date_debut', '>', $entree->date_fin)
                ->exists();

            if ($retourConstate) {
                $entree->update(['statut_cloture' => 'PAUSE_CONFIRMEE']);
                $nbPause++;
                continue;
            }

            $heureActuelle = $now->format('H:i:s');
            $heureFinJournee = $entreprise?->heure_fin_journee ?? '17:30:00';

            // Clôture en départ si :
            // 1. Plus de 60 minutes sans retour (marge pour pause déjeuner)
            // 2. Ou l'heure de fin de journée de l'entreprise est atteinte/dépassée
            if ($minutesDepuisSortie >= 60 || $heureActuelle >= $heureFinJournee) {
                $entree->update(['statut_cloture' => 'DEPART_CONFIRME']);
                $nbDepart++;
            }
        }

        $this->info("Traitement terminé : {$nbPause} pause(s) confirmée(s), {$nbDepart} départ(s) confirmé(s).");
    }
}

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
            ->where('statut_cloture', 'EN_ATTENTE')
            ->whereNotNull('date_fin')
            ->with('carnet.entreprise')
            ->get();

        $nbPause = 0;
        $nbDepart = 0;

        foreach ($entreesEnAttente as $entree) {
            $entreprise = $entree->carnet?->entreprise;
            $now = Carbon::now();
            $dateFin = Carbon::parse($entree->date_fin);
            $minutesDepuisSortie = $dateFin->diffInMinutes($now);

            // Y a-t-il eu un retour dans le rayon après cette sortie ?
            $retourConstate = EntreeCarnet::where('carnet_id', $entree->carnet_id)
                ->where('type', 'PRESENCE')
                ->where('date_debut', '>', $entree->date_fin)
                ->exists();

            if ($retourConstate) {
                $entree->update(['statut_cloture' => 'PAUSE_CONFIRMEE']);
                $nbPause++;
                continue;
            }

            $heureActuelle = $now->format('H:i:s');
            $heureFinJournee = $entreprise?->heure_fin_journee;

            // Clôture en départ si :
            // 1. Plus de 45 minutes sans retour
            // 2. Ou l'heure de fin de journée de l'entreprise est atteinte/dépassée
            if ($minutesDepuisSortie >= 45 || ($heureFinJournee && $heureActuelle >= $heureFinJournee)) {
                $entree->update(['statut_cloture' => 'DEPART_CONFIRME']);
                $nbDepart++;
            }
        }

        $this->info("Traitement terminé : {$nbPause} pause(s) confirmée(s), {$nbDepart} départ(s) confirmé(s).");
    }
}

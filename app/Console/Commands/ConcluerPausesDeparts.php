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

            if (!$entreprise || !$entreprise->heure_fin_journee) {
                continue; // pas d'horaires configurés, on ne peut pas conclure
            }

            $heureActuelle = Carbon::now()->format('H:i:s');
            $heureFinJournee = $entreprise->heure_fin_journee;

            // Y a-t-il eu un retour dans le rayon après cette sortie ?
            $retourConstate = EntreeCarnet::where('carnet_id', $entree->carnet_id)
                ->where('type', 'PRESENCE')
                ->where('date_debut', '>', $entree->date_fin)
                ->exists();

            $sortieDansPlagePause = $entreprise->pause_heure_debut
                && $entreprise->pause_heure_fin
                && Carbon::parse($entree->date_fin)->format('H:i:s') >= $entreprise->pause_heure_debut
                && Carbon::parse($entree->date_fin)->format('H:i:s') <= $entreprise->pause_heure_fin;

            if ($heureActuelle >= $heureFinJournee && !$retourConstate) {
                $entree->update(['statut_cloture' => 'DEPART_CONFIRME']);
                $nbDepart++;
            } elseif ($sortieDansPlagePause && $retourConstate) {
                $entree->update(['statut_cloture' => 'PAUSE_CONFIRMEE']);
                $nbPause++;
            }
            // sinon : on ne conclut rien, on attend le prochain passage
        }

        $this->info("Traitement terminé : {$nbPause} pause(s) confirmée(s), {$nbDepart} départ(s) confirmé(s).");
    }
}

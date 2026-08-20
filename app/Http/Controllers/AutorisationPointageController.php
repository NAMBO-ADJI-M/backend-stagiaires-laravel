<?php

namespace App\Http\Controllers;

use App\Models\AutorisationPointage;
use App\Models\Stagiaire;
use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DemandeSuiviNotification;

class AutorisationPointageController extends Controller
{
    /**
     * Le stagiaire active/désactive lui-même le suivi.
     * Passage immédiat à ACTIVE ou INACTIVE.
     */
    public function stagiaireToggle(Request $request)
    {
        $request->validate([
            'entreprise_id' => 'required|uuid|exists:entreprises,id',
            'autorise' => 'required|boolean'
        ]);

        $stagiaire = $request->user()->stagiaire;
        $statut = $request->autorise ? 'ACTIVE' : 'INACTIVE';

        $autorisation = AutorisationPointage::updateOrCreate(
            ['stagiaire_id' => $stagiaire->id, 'entreprise_id' => $request->entreprise_id],
            ['statut' => $statut]
        );

        return response()->json([
            'message' => $request->autorise ? 'Suivi activé.' : 'Suivi désactivé.',
            'statut' => $autorisation->statut
        ]);
    }

    /**
     * L'entreprise demande l'accès au suivi du stagiaire.
     * Statut passe à EN_ATTENTE. Envoie une notification au stagiaire.
     */
    public function entrepriseDemande(Request $request)
    {
        $request->validate([
            'stagiaire_id' => 'required|uuid|exists:stagiaires,id'
        ]);

        $entreprise = $request->user()->entreprise;

        $autorisation = AutorisationPointage::updateOrCreate(
            ['stagiaire_id' => $request->stagiaire_id, 'entreprise_id' => $entreprise->id],
            ['statut' => 'EN_ATTENTE']
        );

        // Envoyer la notification au stagiaire
        $stagiaire = Stagiaire::find($request->stagiaire_id);
        // On suppose qu'une classe DemandeSuiviNotification existe ou sera créée
        // Pour l'instant on utilise notify() de base si le modèle User le supporte
        $stagiaire->user->notify(new \App\Notifications\GenericNotification([
            'type' => 'DEMANDE_SUIVI',
            'title' => 'Demande de suivi',
            'message' => "L'entreprise {$entreprise->raison_sociale} souhaite accéder à votre suivi de pointage.",
            'entreprise_id' => $entreprise->id,
            'autorisation_id' => $autorisation->id
        ]));

        return response()->json([
            'message' => 'Demande de suivi envoyée.',
            'statut' => $autorisation->statut
        ]);
    }

    /**
     * Le stagiaire accepte ou refuse via la notification.
     */
    public function stagiaireRepond(Request $request)
    {
        $request->validate([
            'autorisation_id' => 'required|uuid|exists:autorisations_pointage,id',
            'accepter' => 'required|boolean'
        ]);

        $autorisation = AutorisationPointage::where('id', $request->autorisation_id)
            ->where('stagiaire_id', $request->user()->stagiaire->id)
            ->firstOrFail();

        $autorisation->update([
            'statut' => $request->accepter ? 'ACTIVE' : 'REFUSEE'
        ]);

        return response()->json([
            'message' => $request->accepter ? 'Demande acceptée.' : 'Demande refusée.',
            'statut' => $autorisation->statut
        ]);
    }
}

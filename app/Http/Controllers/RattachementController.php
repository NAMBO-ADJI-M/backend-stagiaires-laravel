<?php

namespace App\Http\Controllers;

use App\Models\FicheStagiaireInvite;
use App\Models\CarnetDeStage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RattachementController extends Controller
{
    // Le stagiaire saisit un code reçu de son tuteur pour rattacher UN de ses carnets
    public function rattacher(Request $request)
    {
        $data = $request->validate([
            'code_invitation' => 'required|string',
            'carnet_id' => 'required|string|exists:carnets_de_stage,id',
        ]);

        $code = strtoupper(trim($data['code_invitation']));

        $fiche = FicheStagiaireInvite::where('code_invitation', $code)
            ->where('utilise', false)
            ->where('date_expiration', '>', now())
            ->first();

        if (!$fiche) {
            throw ValidationException::withMessages([
                'code_invitation' => 'Code invalide, déjà utilisé ou expiré.',
            ]);
        }

        // Règle de sécurité verrouillée : l'email du stagiaire connecté DOIT correspondre
        // à celui de la fiche — blocage strict sinon (pas de tolérance)
        if ($fiche->email !== $request->user()->email) {
            throw ValidationException::withMessages([
                'code_invitation' => "Ce code n'est pas associé à votre adresse e-mail.",
            ]);
        }

        $carnet = CarnetDeStage::where('id', $data['carnet_id'])
            ->where('stagiaire_id', $request->user()->stagiaire->id)
            ->firstOrFail();

        if ($carnet->entreprise_id !== null) {
            throw ValidationException::withMessages([
                'carnet_id' => 'Ce carnet est déjà rattaché à une entreprise.',
            ]);
        }

        // Création/Mise à jour de l'autorisation de pointage à partir de la fiche d'invitation
        $autorisation = \App\Models\AutorisationPointage::updateOrCreate(
            ['stagiaire_id' => $request->user()->stagiaire->id, 'entreprise_id' => $fiche->entreprise_id],
            [
                'statut' => 'ACTIVE',
                'poste' => $fiche->poste,
                'date_debut' => $fiche->date_debut,
                'date_fin' => $fiche->date_fin,
                'etablissement_nom' => $fiche->etablissement_nom,
                'tuteur_designe' => $fiche->tuteur_designe,
                'objet_stage' => $fiche->objet_stage,
                'cursus_rattachement' => $fiche->cursus_rattachement,
                'lieu_execution' => $fiche->lieu_execution,
                'lieu_execution_lat' => $fiche->lieu_execution_lat,
                'lieu_execution_lng' => $fiche->lieu_execution_lng,
                'duree_hebdomadaire' => $fiche->duree_hebdomadaire,
                'jours_presence' => $fiche->jours_presence,
                'teletravail_modalites' => $fiche->teletravail_modalites,
                'referent_pedagogique_nom' => $fiche->referent_pedagogique_nom,
                'referent_pedagogique_contact' => $fiche->referent_pedagogique_contact,
                'modalites_suivi_detail' => $fiche->modalites_suivi_detail,
                'conditions_stage' => $fiche->conditions_stage,
            ]
        );

        // Appel du service de rattachement pour mettre à jour le carnet et créer la convention
        app(\App\Services\RattachementService::class)->rattacherEtSigner($carnet, $fiche->entreprise, $autorisation);

        $fiche->update([
            'utilise' => true,
            'carnet_id' => $carnet->id,
        ]);

        return response()->json([
            'message' => 'Carnet rattaché et convention générée avec succès.',
            'carnet' => $carnet->fresh(),
        ]);
    }
}

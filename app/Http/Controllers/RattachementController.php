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

        $fiche = FicheStagiaireInvite::where('code_invitation', $data['code_invitation'])
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

        $carnet->update([
            'entreprise_id' => $fiche->entreprise_id,
            'code_rattachement_utilise' => $fiche->code_invitation,
            'date_rattachement' => now(),
            'autorisation_suivi' => true,
            'statut' => 'EN_COURS',
        ]);

        $fiche->update([
            'utilise' => true,
            'carnet_id' => $carnet->id,
        ]);

        return response()->json([
            'message' => 'Carnet rattaché avec succès.',
            'carnet' => $carnet->fresh(),
        ]);
    }
}
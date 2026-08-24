<?php

namespace App\Http\Controllers;

use App\Models\EntreeCarnet;
use App\Models\CarnetDeStage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PointageController extends Controller
{
    // Marque une arrivée : ouvre une nouvelle entrée PRESENCE (date_fin encore vide)
    public function arrivee(Request $request)
    {
        $data = $request->validate([
            'carnet_id' => 'required|string|exists:carnets_de_stage,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'position_lat' => 'nullable|numeric', // Support de l'ancien nom
            'position_lng' => 'nullable|numeric',
        ]);

        $carnet = CarnetDeStage::where('id', $data['carnet_id'])
            ->where('stagiaire_id', $request->user()->stagiaire->id)
            ->firstOrFail();

        // Vérification signature convention
        if (!$carnet->convention || $carnet->convention->statut !== 'signee') {
            return response()->json(['message' => 'Pointage impossible. La convention doit être signée par les deux parties.'], 403);
        }

        // Empêche une double arrivée sans départ entre les deux
        $dejaOuverte = EntreeCarnet::where('carnet_id', $carnet->id)
            ->where('type', 'PRESENCE')
            ->whereNull('date_fin')
            ->exists();

        if ($dejaOuverte) {
            throw ValidationException::withMessages([
                'carnet_id' => 'Une présence est déjà en cours pour ce carnet.',
            ]);
        }

        $entree = EntreeCarnet::create([
            'carnet_id' => $carnet->id,
            'type' => 'PRESENCE',
            'date_debut' => now(),
            'position_lat' => $data['latitude'] ?? $data['position_lat'] ?? 0,
            'position_lng' => $data['longitude'] ?? $data['position_lng'] ?? 0,
            'source_validation' => 'AUTOMATIQUE',
            'session_id' => (string) Str::uuid(),
            'statut_cloture' => 'EN_ATTENTE',
        ]);

        return response()->json($entree, 201);
    }

    // Marque un départ : referme la présence en cours (date_fin)
    public function depart(Request $request)
    {
        $data = $request->validate([
            'carnet_id' => 'required|string|exists:carnets_de_stage,id',
        ]);

        $carnet = CarnetDeStage::where('id', $data['carnet_id'])
            ->where('stagiaire_id', $request->user()->stagiaire->id)
            ->firstOrFail();

        // Vérification signature convention
        if (!$carnet->convention || $carnet->convention->statut !== 'signee') {
            return response()->json(['message' => 'Clôture de présence impossible. La convention doit être signée par les deux parties.'], 403);
        }

        $entree = EntreeCarnet::where('carnet_id', $carnet->id)
            ->where('type', 'PRESENCE')
            ->whereNull('date_fin')
            ->latest('date_debut')
            ->first();

        if (!$entree) {
            throw ValidationException::withMessages([
                'carnet_id' => 'Aucune présence en cours à clôturer pour ce carnet.',
            ]);
        }

        $entree->update(['date_fin' => now()]);

        return response()->json($entree->fresh());
    }

    // Liste les entrées de présence d'un carnet (pour vérifier visuellement)
    public function historique(Request $request, string $carnetId)
    {
        $user = $request->user();
        $carnet = CarnetDeStage::findOrFail($carnetId);

        // Autorisation : Stagiaire propriétaire ou Tuteur autorisé
        $isProprietaire = $user->role === 'stagiaire' && $carnet->stagiaire_id === $user->stagiaire->id;

        $isTuteurAutorise = false;
        if ($user->role === 'entreprise') {
            $autorisation = \App\Models\AutorisationPointage::where('stagiaire_id', $carnet->stagiaire_id)
                ->where('entreprise_id', $user->entreprise->id)
                ->where('statut', 'ACTIVE')
                ->exists();
            $isTuteurAutorise = $autorisation && ($carnet->entreprise_id === $user->entreprise->id);
        }

        if (!$isProprietaire && !$isTuteurAutorise) {
            return response()->json(['message' => 'Accès refusé au suivi de présence.'], 403);
        }

        // Règle de signature de convention : Accès au pointage bloqué tant que non signée
        if (!$carnet->convention || $carnet->convention->statut !== 'signee') {
            // Si c'est le tuteur qui consulte, on renvoie un statut explicite au lieu d'une erreur 403 brute
            // pour permettre au dashboard de gérer l'affichage "En attente de signature".
            if ($user->role === 'entreprise') {
                return response()->json([
                    'statut' => 'convention_non_signee',
                    'peut_suivre' => false,
                    'message' => 'La convention doit être signée par les deux parties pour accéder au suivi.'
                ], 200);
            }

            return response()->json(['message' => 'L\'accès au suivi de présence est bloqué. La convention doit être signée par les deux parties.'], 403);
        }

        return EntreeCarnet::where('carnet_id', $carnet->id)
            ->where('type', 'PRESENCE')
            ->orderByDesc('date_debut')
            ->get();
    }
}

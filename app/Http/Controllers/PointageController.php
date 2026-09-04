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
            'carnet_id' => 'nullable|string|exists:carnets_de_stage,id',
            'autorisation_pointage_id' => 'nullable|string|exists:autorisations_pointage,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'position_lat' => 'nullable|numeric',
            'position_lng' => 'nullable|numeric',
        ]);

        if (empty($data['carnet_id']) && empty($data['autorisation_pointage_id'])) {
            return response()->json(['message' => 'carnet_id ou autorisation_pointage_id requis.'], 422);
        }

        $stagiaireId = $request->user()->stagiaire->id;
        $carnet = null;
        $autorisation = null;

        if (!empty($data['autorisation_pointage_id'])) {
            $autorisation = \App\Models\AutorisationPointage::where('id', $data['autorisation_pointage_id'])
                ->where('stagiaire_id', $stagiaireId)
                ->firstOrFail();
            if ($autorisation->carnet_id) {
                $carnet = CarnetDeStage::find($autorisation->carnet_id);
            }
        } else {
            $carnet = CarnetDeStage::where('id', $data['carnet_id'])
                ->where('stagiaire_id', $stagiaireId)
                ->firstOrFail();
            $autorisation = \App\Models\AutorisationPointage::where('carnet_id', $carnet->id)->first();
            if (!$autorisation) {
                return response()->json(['message' => 'Aucune autorisation de pointage associée.'], 404);
            }
        }

        // Vérification signature convention
        if ($autorisation->statut !== 'CONVENTION_SIGNEE') {
            return response()->json(['message' => 'Pointage impossible. La convention doit être signée par les deux parties.'], 403);
        }

        // Vérification de la plage de dates du stage
        $today = now()->startOfDay();
        $debut = $autorisation->date_debut ? $autorisation->date_debut->startOfDay() : null;
        $fin = $autorisation->date_fin ? $autorisation->date_fin->startOfDay() : null;

        if ($debut && $fin && ($today->lt($debut) || $today->gt($fin))) {
            return response()->json([
                'message' => 'Pointage refusé. La période de stage (' . $debut->format('d/m/Y') . ' au ' . $fin->format('d/m/Y') . ') n\'est pas en cours.',
            ], 403);
        }

        // Vérification du jour de la semaine
        $joursSemaine = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $aujourdhui = $joursSemaine[now()->dayOfWeek];
        $joursAutorises = $autorisation->jours_presence ?? [];

        if (!empty($joursAutorises) && !in_array($aujourdhui, $joursAutorises)) {
            return response()->json([
                'message' => "Pointage refusé. Le stagiaire ne travaille pas le $aujourdhui d'après la convention.",
            ], 403);
        }

        // Empêche une double arrivée sans départ entre les deux
        $dejaOuverte = EntreeCarnet::where('autorisation_pointage_id', $autorisation->id)
            ->where('type', 'PRESENCE')
            ->whereNull('date_fin')
            ->first();

        if ($dejaOuverte) {
            // Si une présence est ouverte, on ne fait rien (on est déjà là)
            return response()->json($dejaOuverte, 200);
        }

        // Requalification automatique : si la dernière entrée terminée était une sortie provisoire (silencieuse),
        // ce retour annule le timestamp de fin pour reprendre la session.
        $derniereSortieSilencieuse = EntreeCarnet::where('autorisation_pointage_id', $autorisation->id)
            ->where('type', 'PRESENCE')
            ->whereNotNull('date_fin')
            ->where('statut_cloture', 'SORTIE_SILENCIEUSE')
            ->latest('date_fin')
            ->first();

        if ($derniereSortieSilencieuse) {
            $derniereSortieSilencieuse->update([
                'date_fin' => null,
                'statut_cloture' => 'EN_ATTENTE'
            ]);
            return response()->json($derniereSortieSilencieuse, 200);
        }

        $entree = EntreeCarnet::create([
            'carnet_id' => $carnet?->id,
            'autorisation_pointage_id' => $autorisation->id,
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

    // Marque un départ : referme la présence en cours (date_fin) avec statut EN_ATTENTE
    public function depart(Request $request)
    {
        $data = $request->validate([
            'carnet_id' => 'nullable|string|exists:carnets_de_stage,id',
            'autorisation_pointage_id' => 'nullable|string|exists:autorisations_pointage,id',
        ]);

        if (empty($data['carnet_id']) && empty($data['autorisation_pointage_id'])) {
            return response()->json(['message' => 'carnet_id ou autorisation_pointage_id requis.'], 422);
        }

        $stagiaireId = $request->user()->stagiaire->id;
        $carnet = null;
        $autorisation = null;

        if (!empty($data['autorisation_pointage_id'])) {
            $autorisation = \App\Models\AutorisationPointage::where('id', $data['autorisation_pointage_id'])
                ->where('stagiaire_id', $stagiaireId)
                ->firstOrFail();
            if ($autorisation->carnet_id) {
                $carnet = CarnetDeStage::find($autorisation->carnet_id);
            }
        } else {
            $carnet = CarnetDeStage::where('id', $data['carnet_id'])
                ->where('stagiaire_id', $stagiaireId)
                ->firstOrFail();
            $autorisation = \App\Models\AutorisationPointage::where('carnet_id', $carnet->id)->first();
            if (!$autorisation) {
                return response()->json(['message' => 'Aucune autorisation de pointage associée.'], 404);
            }
        }

        // Vérification signature convention
        if ($autorisation->statut !== 'CONVENTION_SIGNEE') {
            return response()->json(['message' => 'Clôture de présence impossible. La convention doit être signée par les deux parties.'], 403);
        }

        $entree = EntreeCarnet::where('autorisation_pointage_id', $autorisation->id)
            ->where('type', 'PRESENCE')
            ->whereNull('date_fin')
            ->latest('date_debut')
            ->first();

        if (!$entree) {
            return response()->json(['message' => 'Aucune présence en cours à clôturer.'], 200);
        }

        $entree->update([
            'date_fin' => now(),
            'statut_cloture' => 'SORTIE_SILENCIEUSE', // Silencieux, le cron ou le retour tranchera
        ]);

        return response()->json($entree->fresh());
    }

    // Confirmation explicite de pause (via notification ou bouton in-app)
    public function confirmerPause(Request $request)
    {
        $data = $request->validate([
            'carnet_id' => 'nullable|string|exists:carnets_de_stage,id',
            'autorisation_pointage_id' => 'nullable|string|exists:autorisations_pointage,id',
        ]);

        if (empty($data['carnet_id']) && empty($data['autorisation_pointage_id'])) {
            return response()->json(['message' => 'carnet_id ou autorisation_pointage_id requis.'], 422);
        }

        $stagiaireId = $request->user()->stagiaire->id;
        $autorisation = null;

        if (!empty($data['autorisation_pointage_id'])) {
            $autorisation = \App\Models\AutorisationPointage::where('id', $data['autorisation_pointage_id'])
                ->where('stagiaire_id', $stagiaireId)
                ->firstOrFail();
        } else {
            $carnet = CarnetDeStage::where('id', $data['carnet_id'])
                ->where('stagiaire_id', $stagiaireId)
                ->firstOrFail();
            $autorisation = \App\Models\AutorisationPointage::where('carnet_id', $carnet->id)->first();
            if (!$autorisation) {
                return response()->json(['message' => 'Aucune autorisation de pointage associée.'], 404);
            }
        }

        $entree = EntreeCarnet::where('autorisation_pointage_id', $autorisation->id)
            ->where('type', 'PRESENCE')
            ->whereNotNull('date_fin')
            ->where('statut_cloture', 'EN_ATTENTE')
            ->latest('date_fin')
            ->first();

        if ($entree) {
            $entree->update(['statut_cloture' => 'PAUSE_CONFIRMEE']);
            return response()->json(['message' => 'Pause confirmée avec succès.', 'entree' => $entree->fresh()]);
        }

        return response()->json(['message' => 'Aucune sortie en attente à passer en pause.'], 200);
    }

    // Confirmation explicite de fin de journée / départ définitif
    public function confirmerDepart(Request $request)
    {
        $data = $request->validate([
            'carnet_id' => 'nullable|string|exists:carnets_de_stage,id',
            'autorisation_pointage_id' => 'nullable|string|exists:autorisations_pointage,id',
        ]);

        if (empty($data['carnet_id']) && empty($data['autorisation_pointage_id'])) {
            return response()->json(['message' => 'carnet_id ou autorisation_pointage_id requis.'], 422);
        }

        $stagiaireId = $request->user()->stagiaire->id;
        $autorisation = null;

        if (!empty($data['autorisation_pointage_id'])) {
            $autorisation = \App\Models\AutorisationPointage::where('id', $data['autorisation_pointage_id'])
                ->where('stagiaire_id', $stagiaireId)
                ->firstOrFail();
        } else {
            $carnet = CarnetDeStage::where('id', $data['carnet_id'])
                ->where('stagiaire_id', $stagiaireId)
                ->firstOrFail();
            $autorisation = \App\Models\AutorisationPointage::where('carnet_id', $carnet->id)->first();
            if (!$autorisation) {
                return response()->json(['message' => 'Aucune autorisation de pointage associée.'], 404);
            }
        }

        $entree = EntreeCarnet::where('autorisation_pointage_id', $autorisation->id)
            ->where('type', 'PRESENCE')
            ->whereNotNull('date_fin')
            ->where('statut_cloture', 'EN_ATTENTE')
            ->latest('date_fin')
            ->first();

        if ($entree) {
            $entree->update(['statut_cloture' => 'DEPART_CONFIRME']);
            return response()->json(['message' => 'Départ définitif confirmé avec succès.', 'entree' => $entree->fresh()]);
        }

        return response()->json(['message' => 'Aucune sortie en attente à clôturer.'], 200);
    }

    // Liste les entrées de présence d'un carnet (pour vérifier visuellement)
    public function historique(Request $request, ?string $autorisationId = null)
    {
        $user = $request->user();
        $idAuto = $autorisationId ?: $request->route('autorisationId');

        if (!$idAuto) {
            return response()->json(['message' => 'Identifiant d\'autorisation de pointage manquant.'], 422);
        }

        $autorisation = \App\Models\AutorisationPointage::findOrFail($idAuto);

        // Autorisation : Stagiaire propriétaire ou Tuteur autorisé
        $isProprietaire = $user->role === 'stagiaire' && $autorisation->stagiaire_id === $user->stagiaire->id;

        $isTuteurAutorise = false;
        if ($user->role === 'entreprise') {
            $isTuteurAutorise = ($autorisation->entreprise_id === $user->entreprise->id);
        }

        if (!$isProprietaire && !$isTuteurAutorise) {
            return response()->json(['message' => 'Accès refusé au suivi de présence.'], 403);
        }

        // Règle de signature de convention : Accès au pointage bloqué tant que non signée
        if ($autorisation->statut !== 'CONVENTION_SIGNEE') {
            if ($user->role === 'entreprise') {
                return response()->json([
                    'statut' => 'convention_non_signee',
                    'peut_suivre' => false,
                    'message' => 'La convention doit être signée par les deux parties pour accéder au suivi.'
                ], 200);
            }

            return response()->json(['message' => 'L\'accès au suivi de présence est bloqué. La convention doit être signée par les deux parties.'], 403);
        }

        // Filtrage strict sur autorisation_pointage_id (fiabilisé par migration de backfill)
        return EntreeCarnet::where('autorisation_pointage_id', $autorisation->id)
            ->where('type', 'PRESENCE')
            ->orderByDesc('date_debut')
            ->get();
    }
}

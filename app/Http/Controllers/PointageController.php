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
            'position_lat' => 'required|numeric',
            'position_lng' => 'required|numeric',
        ]);

        $carnet = CarnetDeStage::where('id', $data['carnet_id'])
            ->where('stagiaire_id', $request->user()->stagiaire->id)
            ->firstOrFail();

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
            'position_lat' => $data['position_lat'],
            'position_lng' => $data['position_lng'],
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
        $carnet = CarnetDeStage::where('id', $carnetId)
            ->where('stagiaire_id', $request->user()->stagiaire->id)
            ->firstOrFail();

        return EntreeCarnet::where('carnet_id', $carnet->id)
            ->where('type', 'PRESENCE')
            ->orderByDesc('date_debut')
            ->get();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CarnetDeStage;
use App\Models\BilanReflexif;
use Illuminate\Http\Request;

class BilanReflexifController extends Controller
{
    // Le stagiaire écrit un bilan réflexif, optionnel, jamais visible du tuteur
    public function store(Request $request)
    {
        $data = $request->validate([
            'carnet_id' => 'required|string|exists:carnets_de_stage,id',
            'periode' => 'required|string|max:50',
            'contenu' => 'required|string|min:1',
        ]);

        $carnet = CarnetDeStage::where('id', $data['carnet_id'])
            ->where('stagiaire_id', $request->user()->stagiaire->id)
            ->firstOrFail();

        $bilan = BilanReflexif::create([
            'carnet_id' => $carnet->id,
            'periode' => $data['periode'],
            'contenu' => $data['contenu'],
        ]);

        return response()->json($bilan, 201);
    }

    // Le stagiaire consulte ses propres bilans (aucune route équivalente n'existe côté entreprise)
    public function index(Request $request, string $carnetId)
    {
        $carnet = CarnetDeStage::where('id', $carnetId)
            ->where('stagiaire_id', $request->user()->stagiaire->id)
            ->firstOrFail();

        return BilanReflexif::where('carnet_id', $carnet->id)
            ->orderByDesc('date_creation')
            ->get();
    }
}

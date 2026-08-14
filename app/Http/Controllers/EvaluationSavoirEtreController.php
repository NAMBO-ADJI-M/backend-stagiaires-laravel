<?php

namespace App\Http\Controllers;

use App\Models\CarnetDeStage;
use App\Models\EvaluationSavoirEtre;
use Illuminate\Http\Request;

class EvaluationSavoirEtreController extends Controller
{
    // L'entreprise évalue un critère de savoir-être pour un carnet
    public function store(Request $request)
    {
        $data = $request->validate([
            'carnet_id' => 'required|string|exists:carnets_de_stage,id',
            'critere_id' => 'required|string|exists:criteres_savoir_etre,id',
            'niveau' => 'required|in:A_AMELIORER,SATISFAISANT,REMARQUABLE',
            'commentaire' => 'nullable|string',
        ]);

        $carnet = CarnetDeStage::where('id', $data['carnet_id'])
            ->where('entreprise_id', $request->user()->entreprise->id)
            ->firstOrFail();

        $evaluation = EvaluationSavoirEtre::updateOrCreate(
            ['carnet_id' => $carnet->id, 'critere_id' => $data['critere_id']],
            ['niveau' => $data['niveau'], 'commentaire' => $data['commentaire'] ?? null]
        );

        return response()->json($evaluation, 201);
    }

    // Consultation réservée au tuteur (jamais exposée au stagiaire, aucune route stagiaire n'y accède)
    public function index(Request $request, string $carnetId)
    {
        $carnet = CarnetDeStage::where('id', $carnetId)
            ->where('entreprise_id', $request->user()->entreprise->id)
            ->firstOrFail();

        return EvaluationSavoirEtre::where('carnet_id', $carnet->id)
            ->with('critere')
            ->get();
    }
}

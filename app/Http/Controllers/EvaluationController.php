<?php

namespace App\Http\Controllers;

use App\Models\EvaluationCompetence;
use App\Models\CarnetDeStage;
use App\Models\IndicateurAssiduite;
use App\Models\ProgressionCompetence;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    // L'entreprise évalue un carnet, à tout moment (pas de restriction de date)
    public function store(Request $request)
    {
        $data = $request->validate([
            'carnet_id' => 'required|string|exists:carnets_de_stage,id',
            'jugee_utile' => 'required|boolean',
            // niveau_tuteur/appreciation_tuteur par compétence, optionnel à cette étape
            'competences' => 'nullable|array',
            'competences.*.competence_id' => 'required_with:competences|string|exists:competences,id',
            'competences.*.niveau_tuteur' => 'nullable|in:NON_ABORDEE,DECOUVERTE,EN_COURS,MAITRISEE',
            'competences.*.appreciation_tuteur' => 'nullable|string',
        ]);

        $carnet = CarnetDeStage::where('id', $data['carnet_id'])
            ->where('entreprise_id', $request->user()->entreprise->id) // seul le tuteur rattaché peut évaluer
            ->firstOrFail();

        $indicateur = IndicateurAssiduite::where('carnet_id', $carnet->id)->first();

        $evaluation = EvaluationCompetence::create([
            'carnet_id' => $carnet->id,
            'entreprise_id' => $request->user()->entreprise->id,
            'indicateur_assiduite_id' => $indicateur?->id,
            'jugee_utile' => $data['jugee_utile'],
        ]);

        // Enregistre le jugement du tuteur par compétence, si fourni (reste invisible du stagiaire)
        foreach ($data['competences'] ?? [] as $comp) {
            ProgressionCompetence::where('carnet_id', $carnet->id)
                ->where('competence_id', $comp['competence_id'])
                ->update(array_filter([
                    'niveau_tuteur' => $comp['niveau_tuteur'] ?? null,
                    'appreciation_tuteur' => $comp['appreciation_tuteur'] ?? null,
                ], fn ($v) => !is_null($v)));
        }

        return response()->json($evaluation->load('carnet'), 201);
    }

    // Historique des évaluations d'un carnet (côté entreprise)
    public function index(Request $request, string $carnetId)
    {
        $carnet = CarnetDeStage::where('id', $carnetId)
            ->where('entreprise_id', $request->user()->entreprise->id)
            ->firstOrFail();

        return EvaluationCompetence::where('carnet_id', $carnet->id)
            ->orderByDesc('date_evaluation')
            ->get();
    }
}

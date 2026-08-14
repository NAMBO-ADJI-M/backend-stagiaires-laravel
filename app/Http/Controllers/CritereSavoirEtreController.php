<?php

namespace App\Http\Controllers;

use App\Models\CritereSavoirEtre;
use Illuminate\Http\Request;

class CritereSavoirEtreController extends Controller
{
    // Liste les critères standard + ceux de l'entreprise connectée (si applicable)
    public function index(Request $request)
    {
        $entrepriseId = $request->user()->role === 'entreprise'
            ? $request->user()->entreprise->id
            : null;

        return CritereSavoirEtre::whereNull('entreprise_id')
            ->when($entrepriseId, fn ($q) => $q->orWhere('entreprise_id', $entrepriseId))
            ->orderBy('nom')
            ->get();
    }

    // L'entreprise ajoute son propre critère (uniquement en complément du socle standard)
    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $critere = CritereSavoirEtre::create([
            ...$data,
            'entreprise_id' => $request->user()->entreprise->id,
        ]);

        return response()->json($critere, 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Trajet;
use App\Models\Signalement;
use Illuminate\Http\Request;

class SignalementController extends Controller
{
    // Signaler un comportement ou trajet suspect — toujours rattaché à un trajet précis
    public function store(Request $request, string $trajetId)
    {
        $trajet = Trajet::findOrFail($trajetId);

        $data = $request->validate([
            'motif' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $signalement = Signalement::create([
            'trajet_id' => $trajet->id,
            'auteur_id' => $request->user()->id,
            'motif' => $data['motif'],
            'description' => $data['description'] ?? null,
        ]);

        return response()->json($signalement, 201);
    }
}

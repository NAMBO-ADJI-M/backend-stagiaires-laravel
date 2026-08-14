<?php

namespace App\Http\Controllers;

use App\Models\FicheStagiaireInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FicheStagiaireInviteController extends Controller
{
    // L'entreprise ajoute un nouveau stagiaire et génère son code d'invitation
    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email',
        ]);

        $code = strtoupper(Str::random(8)); // ex. "TC7X9K2P"

        $fiche = FicheStagiaireInvite::create([
            ...$data,
            'entreprise_id' => $request->user()->entreprise->id,
            'code_invitation' => $code,
            'date_expiration' => now()->addDays(30),
        ]);

        return response()->json($fiche, 201);
    }

    // Liste des invitations envoyées par l'entreprise connectée
    public function index(Request $request)
    {
        return FicheStagiaireInvite::where('entreprise_id', $request->user()->entreprise->id)
            ->orderByDesc('date_generation')
            ->get();
    }
}

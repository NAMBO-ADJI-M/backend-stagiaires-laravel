<?php

namespace App\Http\Controllers;
use App\Models\Trajet;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // Envoyer un message sur un trajet — ouvert dès qu'il est visible, pas besoin de réservation
    public function store(Request $request, string $trajetId)
    {
        $trajet = Trajet::findOrFail($trajetId);

        $data = $request->validate([
            'contenu' => 'required|string|min:1',
        ]);

        $message = Message::create([
            'trajet_id' => $trajet->id,
            'auteur_id' => $request->user()->id,
            'contenu' => $data['contenu'],
        ]);

        return response()->json($message->load('auteur:id,nom,prenom'), 201);
    }

    // Historique des messages d'un trajet
    public function index(string $trajetId)
    {
        return Message::where('trajet_id', $trajetId)
            ->with('auteur:id,nom,prenom')
            ->orderBy('date_envoi')
            ->get();
    }
}

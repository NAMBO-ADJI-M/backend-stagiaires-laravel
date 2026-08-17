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

        $stagiaireId = $request->user()->stagiaire->id;

        $message = Message::create([
            'trajet_id' => $trajet->id,
            'auteur_id' => $stagiaireId,
            'contenu' => $data['contenu'],
        ]);

        $message->load('auteur:id,nom,prenom');

        return response()->json($this->formatMessage($message, $stagiaireId), 201);
    }

    // Historique des messages d'un trajet
    public function index(Request $request, string $trajetId)
    {
        $messages = Message::where('trajet_id', $trajetId)
            ->with('auteur:id,nom,prenom')
            ->orderByDesc('date_envoi')
            ->get();

        $stagiaireId = $request->user()->stagiaire->id;

        return response()->json(
    $messages->map(fn (Message $m) => $this->formatMessage($m, $stagiaireId))
);
    }

    /**
     * Formate un message selon la structure attendue par MessagesScreen
     * (clés "message", "auteur" en chaîne, "cree_a").
     */
    private function formatMessage(Message $message, string $userId): array
    {
        $estMoi = $message->auteur_id === $userId;

        return [
            'id' => $message->id,
            'message' => $message->contenu,
            'auteur' => $estMoi
                ? 'Vous'
                : trim(($message->auteur->prenom ?? '').' '.($message->auteur->nom ?? '')),
            'cree_a' => $message->date_envoi?->toIso8601String(),
        ];
    }
}